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
 * @package   Fast Asynchronous Re-indexing
 * @version   1.1.6
 * @build     363
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_MstCore_Model_Resource_Urlrewrite extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('mstcore/urlrewrite', 'urlrewrite_id');
    }
}