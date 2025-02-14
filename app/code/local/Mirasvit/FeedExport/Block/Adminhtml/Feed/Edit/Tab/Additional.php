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


class Mirasvit_FeedExport_Block_Adminhtml_Feed_Edit_Tab_Additional extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('current_model');
        $form  = new Varien_Data_Form();
        $form->setFieldNameSuffix('feed');
        $this->setForm($form);

        $email = $form->addFieldset('email_fieldset', array('legend' => __('Email Notifications')));

        $email->addField('notification_emails', 'text', array(
            'name'  => 'notification_emails',
            'label' => __('Email Addresses'),
            'value' => $model->getNotificationEmails(),
        ));

        $email->addField('notification_events', 'multiselect', array(
            'name'   => 'notification_events',
            'label'  => __('Emails'),
            'value'  => $model->getNotificationEvents(),
            'values' => Mage::getSingleton('feedexport/system_config_source_emailEvent')->toOptionArray(),
        ));

        $export = $form->addFieldset('new_fieldset', array('legend' => __('Export Configuration')));

        $export->addField('export_only_new', 'select', array(
            'label'    => __('Export Only New Products'),
            'required' => false,
            'name'     => 'export_only_new',
            'value'    => $model->getExportOnlyNew(),
            'values'   => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
        ));

        if ($model->getExportOnlyNew()) {
            $export->addField('export_only_new_reset', 'link', array(
                'value' => __('Reset Exported Products'),
                'href'  => Mage::getUrl('*/*/resetProducts', array('id' => $model->getId())),
            ));
        }

        $reports = $form->addFieldset('reports_fieldset', array('legend' => __('Reports Configuration')));

        $reports->addField('report_enabled', 'select', array(
            'label'    => __('Enable Reports'),
            'required' => false,
            'name'     => 'report_enabled',
            'value'    => $model->getReportEnabled(),
            'values'   => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'note'     => 'If enabled, extension append to product url two special arguments (fee=, fep=) for track clicks and orders',
        ));

        $filter = $form->addFieldset('filter_fieldset', array('legend' => __('Content Filter')));

        $filter->addField('allowed_chars', 'text', array(
            'name'      => 'allowed_chars',
            'required'  => false,
            'label'     => __('Allowed Characters'),
            'value'     => $model->getAllowedChars(),
            'note'      => Mage::helper('feedexport/help')->field('allowed_chars'),
        ));

        $filter->addField('ignored_chars', 'text', array(
            'name'      => 'ignored_chars',
            'required'  => false,
            'label'     => __('Ignored Characters'),
            'value'     => $model->getIgnoredChars(),
            'note'      => Mage::helper('feedexport/help')->field('ignored_chars'),
        ));


        return parent::_prepareForm();
    }
}