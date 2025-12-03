<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * CmsBlock ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CmsBlock extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     *
     * @return null
     */
    protected function _construct()
    {
        $this->_init('cms_block', 'row_id');
    }
}
