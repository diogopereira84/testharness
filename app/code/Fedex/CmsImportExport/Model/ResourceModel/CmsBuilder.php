<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * CmsBuilder ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CmsBuilder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
       /**
        * Define main table
        */
    protected function _construct()
    {
        $this->_init('pagebuilder_template', 'template_id');
    }
}
