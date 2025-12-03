<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess;

/**
 * CatalogSyncQueueProcess Collection
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define model & resource model
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(
            'Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcess',
            'Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess'
        );
    }
}
