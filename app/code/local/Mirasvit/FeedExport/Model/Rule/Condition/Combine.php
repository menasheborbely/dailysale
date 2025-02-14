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
 * @package   Advanced Product Feeds
 * @version   1.1.2
 * @build     486
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_FeedExport_Model_Rule_Condition_Combine extends Mage_Rule_Model_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('feedexport/rule_condition_combine');
    }

    public function getNewChildSelectOptions()
    {
        if ($this->getRule()->getType()) {
            $type = $this->getRule()->getType();
        } else {
            $type = Mage::app()->getRequest()->getParam('rule_type');
        }

        if ($type == Mirasvit_FeedExport_Model_Rule::TYPE_ATTRIBUTE) {
            $itemAttributes = $this->_getProductAttributes();
            $condition      = 'product';
        } else {
            $itemAttributes = $this->_getPerformanceAttributes();
            $condition      = 'performance';
        }

        $attributes = array();
        foreach ($itemAttributes as $code => $label) {
            $group = Mage::helper('feedexport/html')->getAttributeGroup($code);
            $attributes[$group][] = array(
                'value' => 'feedexport/rule_condition_'.$condition.'|'.$code,
                'label' => $label
            );
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive($conditions, array(
            array(
                'value' => 'feedexport/rule_condition_combine',
                'label' => Mage::helper('feedexport')->__('Conditions Combination')
            )
        ));

        foreach ($attributes as $group => $arrAttributes) {
            $conditions = array_merge_recursive($conditions, array(
                array(
                    'label' => $group,
                    'value' => $arrAttributes
                ),
            ));
        }

        return $conditions;
    }

    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }

        return $this;
    }

    protected function _getProductAttributes()
    {
        $productCondition  = Mage::getModel('feedexport/rule_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();

        return $productAttributes;
    }

    protected function _getPerformanceAttributes()
    {
        $performanceCondition  = Mage::getModel('feedexport/rule_condition_performance');
        $performanceAttributes = $performanceCondition->loadAttributeOptions()->getAttributeOption();

        return $performanceAttributes;
    }
}
