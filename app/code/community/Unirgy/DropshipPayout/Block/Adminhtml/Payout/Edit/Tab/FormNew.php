<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPayout
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipPayout_Block_Adminhtml_Payout_Edit_Tab_FormNew extends Unirgy_Dropship_Block_Adminhtml_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setDestElementId('payout_form_new');
    }

    protected function _prepareForm()
    {
        $payout = Mage::registry('payout_data');
        $hlp = Mage::helper('udpayout');
        $id = $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('payout_form', array(
            'legend'=>Mage::helper('udropship')->__('Payout Info')
        ));
        $this->_addElementTypes($fieldset);

        $fieldset->addField('all_vendors', 'select', array(
            'name'      => 'all_vendors',
            'label'     => Mage::helper('udropship')->__('Vendor Selection'),
            'class'     => 'required-entry',
            'required'  => true,
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('udropship')->__('All Active Vendors'),
                0 => Mage::helper('udropship')->__('Selected Vendors'),
            ),
        ));

        if (Mage::getStoreConfigFlag('udropship/vendor/autocomplete_htmlselect')) {
            $fieldset->addField('vendor_ids', 'udropship_vendor', array(
                'name'      => 'vendor_ids[]',
                'label'     => Mage::helper('udropship')->__('Vendors'),
            ));
        } else {
            $fieldset->addField('vendor_ids', 'multiselect', array(
                'name'      => 'vendor_ids[]',
                'label'     => Mage::helper('udropship')->__('Vendors'),
                'values'   => Mage::getSingleton('udropship/source')->setPath('vendors')->toOptionArray(),
            ));
        }

        /*
        $fieldset->addField('payout_status', 'select', array(
            'name'      => 'payout_status',
            'label'     => Mage::helper('udropship')->__('Status'),
            'options'   => Mage::getSingleton('udpayout/source')->setPath('payout_status')->toOptionHash(),
        ));
        */

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('date_from', 'date', array(
            'name'   => 'date_from',
            'label'  => Mage::helper('udropship')->__('Orders From Date'),
            'title'  => Mage::helper('udropship')->__('Orders From Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
            'class'     => 'required-entry',
            'required'  => true,
        ));
        $fieldset->addField('date_to', 'date', array(
            'name'   => 'date_to',
            'label'  => Mage::helper('udropship')->__('Orders To Date'),
            'title'  => Mage::helper('udropship')->__('Orders To Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso,
            'class'     => 'required-entry',
            'required'  => true,
        ));
        $fieldset->addField('use_locale_timezone', 'select', array(
            'name'      => 'use_locale_timezone',
            'label'     => Mage::helper('udropship')->__('Use Locale Timezone'),
            'type'      => 'options',
            'options'   => array(
                1 => Mage::helper('udropship')->__('Yes'),
                0 => Mage::helper('udropship')->__('No'),
            ),
        ));

        $fieldset->addField('notes', 'textarea', array(
            'name'      => 'notes',
            'label'     => Mage::helper('udropship')->__('Notes'),
        ));

        if ($payout) {
            $form->setValues($payout->getData());
        }

        return parent::_prepareForm();
    }

}
