<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue;

/**
 * Collection Collection
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define model & resource model
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(
            'Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue',
            'Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue'
        );
    }
}
