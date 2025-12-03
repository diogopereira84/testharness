<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMigration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * CatalogMigration ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogMigration extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Define main table
     */
    protected function _construct()
    {
        //here "catalog_migration_process" is table name and "id" is the primary key of custom table
        $this->_init('catalog_migration_process', 'id');
    }
}
