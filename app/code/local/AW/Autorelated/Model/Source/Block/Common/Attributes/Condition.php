<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Autorelated
 * @version    2.4.8
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

class AW_Autorelated_Model_Source_Block_Common_Attributes_Condition extends AW_Autorelated_Model_Source_Abstract
{
    public function toOptionArray()
    {
        $_helper = $this->_getHelper();
        return array(
            array('value' => '=', 'label' => $_helper->__('same as')),
            array('value' => '!=', 'label' => $_helper->__('is not same as')),
            array('value' => 'LIKE', 'label' => $_helper->__('contains')),
            array('value' => 'NOT LIKE', 'label' => $_helper->__("doesn't contain")),
            array('value' => '>', 'label' => $_helper->__('greater')),
            array('value' => '<', 'label' => $_helper->__('less')),
            array('value' => '>=', 'label' => $_helper->__('greater or equal')),
            array('value' => '<=', 'label' => $_helper->__('less or equal'))
        );
    }
}