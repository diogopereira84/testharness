<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * CatalogSyncQueueProcess Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogSyncQueueProcess extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess');
    }

    /**
     * Retrieve catalog syn queue process id
     *
     * @return int
     */
    public function getCatalogSyncQueueProcessId()
    {
        return $this->getId();
    }
}
