<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection Collection
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(
            \Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfiguration::class,
            \Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration::class
        );
    }
}
