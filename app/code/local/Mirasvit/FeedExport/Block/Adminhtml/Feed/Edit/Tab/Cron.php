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


class Mirasvit_FeedExport_Block_Adminhtml_Feed_Edit_Tab_Cron extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('current_model');
        $form  = new Varien_Data_Form();
        $form->setFieldNameSuffix('feed');
        $this->setForm($form);

        $general = $form->addFieldset('general', array('legend' => __('Scheduled Task')));

        $general->addField('cron', 'select', array(
            'name'   => 'cron',
            'label'  => __('Status'),
            'value'  => $model->getCron(),
            'values' => Mage::getModel('adminhtml/system_config_source_enabledisable')->toOptionArray(),
            'note'  => Mage::helper('feedexport/help')->field('cron'),
        ));

        $general->addField('cron_day', 'multiselect', array(
            'label'    => __('Day'),
            'required' => false,
            'name'     => 'cron_day',
            'values'   => Mage::getSingleton('feedexport/system_config_source_day')->toOptionArray(),
            'value'    => $model->getCronDay()
        ));

        $general->addField('cron_time', 'multiselect', array(
            'label'    => __('Time'),
            'required' => false,
            'name'     => 'cron_time',
            'values'   => Mage::getSingleton('feedexport/system_config_source_time')->toOptionArray(),
            'value'    => $model->getCronTime()
        ));

        $general->addField('currrent_time', 'label', array(
            'name'   => 'currrent_time',
            'label'  => __('Current Time'),
            'value'  => Mage::getSingleton('core/date')->date('d.m.Y H:i'),
        ));

        $cron = Mage::getModel('cron/schedule')->getCollection()
            ->setOrder('executed_at', 'desc')
            ->setPageSize(1)
            ->getFirstItem();

        $last = '-';
        if ($cron->getExecutedAt()) {
            $last = Mage::getSingleton('core/date')->date('d.m.Y H:i', strtotime($cron->getExecutedAt()));
        }
        $general->addField('last_cron_run', 'label', array(
            'name'   => 'last_cron_run',
            'label'  => __('Last cron run time'),
            'value'  => $last,
        ));

        $cron = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', 'feedexport_job')
            ->setOrder('executed_at', 'desc')
            ->setPageSize(1)
            ->getFirstItem();
        $last = '-';
        if ($cron->getExecutedAt()) {
            $last = Mage::getSingleton('core/date')->date('d.m.Y H:i', strtotime($cron->getExecutedAt()));
        }
        $general->addField('last_job_run', 'label', array(
            'name'   => 'last_job_run',
            'label'  => __('Last feed job run time'),
            'value'  => $last,
        ));

        return parent::_prepareForm();
    }
}
