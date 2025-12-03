<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * CmsPage ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CmsPage extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('cms_page', 'page_id');
    }
}
