<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMigration\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * CatalogMigration Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogMigration extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Fedex\CatalogMigration\Model\ResourceModel\CatalogMigration');
    }
}
