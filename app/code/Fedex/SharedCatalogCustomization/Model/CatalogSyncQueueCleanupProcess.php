<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess as ResourceModel;

/**
 * CatalogSyncQueueCleanupProcess Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogSyncQueueCleanupProcess extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Retrieve catalog syn queue clean up process id
     *
     * @return int
     */
    public function getCatalogSyncQueueCleanupProcessId()
    {
        return $this->getId();
    }
}
