<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * CatalogSyncQueue Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogSyncQueue extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue');
    }

    /**
     * Retrieve catalog syn queue id
     *
     * @return int
     */
    public function getCatalogSyncQueueId()
    {
        return $this->getId();
    }
}
