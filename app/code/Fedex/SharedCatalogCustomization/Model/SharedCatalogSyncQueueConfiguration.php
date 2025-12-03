<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface;

/**
 * SharedCatalogSyncQueueConfiguration Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SharedCatalogSyncQueueConfiguration extends AbstractModel implements SharedCatalogSyncQueueConfigurationInterface
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(\Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getSharedCatalogId()
    {
        return parent::getData(self::SHARED_CATALOG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSharedCatalogId($sharedCatalogId)
    {
        return $this->setData(self::SHARED_CATALOG_ID, $sharedCatalogId);
    }

    /**
     * @inheritDoc
     */
    public function getLegacyCatalogRootFolderId()
    {
        return parent::getData(self::LEGACY_CATALOG_ROOT_FOLDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLegacyCatalogRootFolderId($legacyCatalogRootFolderId)
    {
        return $this->setData(self::LEGACY_CATALOG_ROOT_FOLDER_ID, $legacyCatalogRootFolderId);
    }

    /**
     * @inheritDoc
     */
    public function getCategoryId()
    {
        return parent::getData(self::CATEGORY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCategoryId($categoryId)
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return parent::getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}
