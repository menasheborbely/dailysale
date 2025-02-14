<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Full Page Cache
 * @version   1.0.1
 * @build     374
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */



class Mirasvit_Fpc_Model_Observer extends Varien_Debug
{
    //1 - only primary tags
    //2 - all tags
    protected $_cacheTagsLevel = 1;

    protected $_registerModelTagCalls = 0;

    public function __construct()
    {
        $this->_processor = Mage::getSingleton('fpc/processor');
        $this->_config = Mage::getSingleton('fpc/config');
        $this->_isEnabled = Mage::app()->useCache('fpc');
    }

    protected function _getCookie()
    {
        return Mage::getSingleton('fpc/cookie');
    }

    public function isAllowed()
    {
        return $this->_isEnabled;
    }

    /**
     * Clean full page cache.
     */
    public function cleanCache($observer)
    {
        if ($observer->getEvent()->getType() == 'fpc') {
            Mirasvit_Fpc_Model_Cache::getCacheInstance()->clean(Mirasvit_Fpc_Model_Processor::CACHE_TAG);
        }

        return $this;
    }

    public function flushCache($observer)
    {
        Mirasvit_Fpc_Model_Cache::getCacheInstance()->flush();
    }

    public function flushCacheAfterCatalogRuleSave($observer)
    {
        $obj = $observer->getObject();

        if (is_object($obj) && get_class($obj) == 'Mage_CatalogRule_Model_Rule') {
            $jobCode = 'fpc_flush_cache';
            $scheduledAtInterval = 10; //minutes
            $schedule = Mage::getModel('cron/schedule')->getCollection()
                        ->addFieldToFilter('job_code', array('eq' => $jobCode))
                        ->addFieldToFilter('status', array('eq' => 'pending'))
                        ->getFirstItem();
            if (!$schedule->hasData()) {
                $timecreated = strftime('%Y-%m-%d %H:%M:%S',  mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')));
                $timescheduled = strftime('%Y-%m-%d %H:%M:%S', mktime(date('H'), date('i') + $scheduledAtInterval, date('s'), date('m'), date('d'), date('Y')));
                Mage::getModel('cron/schedule')->setJobCode($jobCode)
                            ->setCreatedAt($timecreated)
                            ->setScheduledAt($timescheduled)
                            ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
                            ->save();
            }
        }
    }

    /**
     * Invalidate full page cache.
     */
    public function invalidateCache()
    {
        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('fpc')->__('Refresh "Full Page Cache" to apply changes at frontend'));

        return $this;
    }

    public function registerModelTag($observer)
    {
        if (!$this->isAllowed()) {
            return $this;
        }

        $object = $observer->getEvent()->getObject();

        if ($this->_cacheTagsLevel == 1
            && $this->_registerModelTagCalls < 2
            && $object && $object->getId()
            && ($tags = $object->getCacheIdTags())
        ) {
            foreach ($tags as $tagKey => $tagValue) {
                if ($this->checkIgnoredTags($tagValue)) {
                    unset($tags[$tagKey]);
                }
            }
            $this->_processor->addRequestTag($tags);
            $this->_registerModelTagCalls++;
        }


        if ($this->_cacheTagsLevel == 2
            && $object
            && $object->getId()
            && ($tags = $object->getCacheIdTags())
        ) {
            if ($this->_cacheTagsLevel == 1) {
                if (count($tags) > 0) {
                    $tags = array($tags[0]);
                }
            }
            foreach ($tags as $tagKey => $tagValue) {
                if ($this->checkIgnoredTags($tagValue)) {
                    unset($tags[$tagKey]);
                }
            }
            $this->_processor->addRequestTag($tags);
            $this->_registerModelTagCalls++;
        }

        return $this;
    }

    protected function checkIgnoredTags($tagValue)
    {
        $tagValue = strtolower($tagValue);
        $ignoredTags = array('quote', 'customer', 'eav_attribute', 'cms_block', 'all4coding_core_extension');
        foreach ($ignoredTags as $tag) {
            if (strpos($tagValue, $tag) !== false) {
                return true;
            }
        }

        return false;
    }

    public function registerProductTags($observer)
    {
        if (!$this->isAllowed() || $this->_cacheTagsLevel == 1) {
            return $this;
        }

        $object = $observer->getEvent()->getProduct();
        if ($object && $object->getId()) {
            $tags = $object->getCacheIdTags();
            if ($tags) {
                $this->_processor->addRequestTag($tags);
            }
        }
    }

    public function registerCollectionTag($observer)
    {
        if (!$this->isAllowed() || $this->_cacheTagsLevel == 1) {
            return $this;
        }

        $collection = $observer->getEvent()->getCollection();
        if ($collection) {
            foreach ($collection as $object) {
                $tags = $object->getCacheIdTags();
                if ($tags) {
                    $this->_processor->addRequestTag($tags);
                }
            }
        }

        return $this;
    }

    public function validateDataChanges(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getObject();
        $object = Mage::getModel('fpc/validator')->checkDataChange($object);
    }

    public function validateDataDelete(Varien_Event_Observer $observer)
    {
        $object = $observer->getEvent()->getObject();
        $object = Mage::getModel('fpc/validator')->checkDataDelete($object);
    }

    public function onStockItemSaveAfter(Varien_Event_Observer $observer)
    {
        if ($observer->getDataObject() && $observer->getDataObject()->getProductId()) {
            $productId = $observer->getDataObject()->getProductId();
            Mirasvit_Fpc_Model_Cache::getCacheInstance()->clean('CATALOG_PRODUCT_'.$productId);
        }
    }

    public function checkCronStatus() //check for fpc section in admin panel
    {
        if (($request = Mage::app()->getRequest())
            && Mage::app()->getRequest()->getParam('section') == 'fpc') {
                $cronStatus = Mage::helper('mstcore/cron')->checkCronStatus(false, false, 'Cron job is required for correct work of Full Page Cache.');
                if ($cronStatus !== true) {
                    Mage::getSingleton('adminhtml/session')->addError($cronStatus);
                }
        }
    }

    /**
     * Flush cache of dependent pages after save product (need if product isn't in cache we don't flush category by tags)
     */
    public function updateDependingCache($e)
    {
        $product = $e->getProduct();
        if (is_object($product) && ($productId = $product->getId())) {
            $tags = array();
            $tags[] = 'CATALOG_PRODUCT_' . $productId;
            $tags = $this->getCategoryTags($product, $tags);

            if ($product->getTypeId() == "simple") {
                $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($productId);
                if (!$parentIds) {
                    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);
                }
            }
            if ($parentIds) {
                foreach ($parentIds as $parentId) {
                    $tags[] = 'CATALOG_PRODUCT_' . $parentId;
                    $parenProduct = Mage::getModel('catalog/product')->load($parentId);
                    $tags = $this->getCategoryTags($parenProduct, $tags);
                }
            }

            if ($tags) {
                $tags = array_unique($tags);
                Mage::app()->getCache()->clean('matchingAnyTag', $tags);
            }
        }
    }

    protected function getCategoryTags($product, $tags)
    {
        if (!is_object($product)) {
            return $tags;
        }
        $categoryIds = $product->getCategoryIds();
        foreach ($categoryIds as $categoryId) {
            $tags[] = 'CATALOG_CATEGORY_' . $categoryId;
        }
        return $tags;
    }


    public function getConfig()
    {
        return Mage::getSingleton('fpc/config');
    }

    public function onOrderPlaceAfter($observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            return $this;
        }

        foreach ($order->getItemsCollection() as $item) {
            $productId = $item->getProductId();
            $tags = array();
            $tags[] = 'CATALOG_PRODUCT_' . $productId;
            $product = Mage::getModel('catalog/product')->load($productId);
            $stockStatus = Mage::getModel('cataloginventory/stock_item')
                     ->loadByProduct($product)
                     ->getIsInStock();
            if (!$stockStatus) {
                 $tags = $this->getCategoryTags($product, $tags);
            }

            if ($tags) {
                $tags = array_unique($tags);
                Mage::app()->getCache()->clean('matchingAnyTag', $tags);
            }
        }
    }
}
