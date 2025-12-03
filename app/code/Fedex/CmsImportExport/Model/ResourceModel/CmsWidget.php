<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * CmsWidget ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CmsWidget extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('widget_instance', 'instance_id');
    }
}
