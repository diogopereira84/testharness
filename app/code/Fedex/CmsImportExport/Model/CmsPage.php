<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CmsImportExport\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\CmsImportExport\Model\ResourceModel\CmsPage as ResourceModel;

/**
 * CmsPage Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CmsPage extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
