<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMigration\Model\ResourceModel\CatalogMigration;

/**
 * Collection Class
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
            'Fedex\CatalogMigration\Model\CatalogMigration',
            'Fedex\CatalogMigration\Model\ResourceModel\CatalogMigration'
        );
    }
}
