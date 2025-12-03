<?php

namespace Fedex\SharedCatalogCustomization\Api\Data;

/**
 * Interface SharedCatalogSyncQueueConfigurationInterface
 */
interface SharedCatalogSyncQueueConfigurationInterface
{
    public const ENTITY_ID = 'id';
    public const SHARED_CATALOG_ID = 'shared_catalog_id';
    public const LEGACY_CATALOG_ROOT_FOLDER_ID = 'legacy_catalog_root_folder_id';
    public const CATEGORY_ID = 'category_id';
    public const STATUS = 'status';

    /**
     * SetId
     *
     * @param int $id
     * @return void
     */
    public function setId($id);

    /**
     * GetId
     *
     * @return int
     */
    public function getId();

    /**
     * SetSharedCatalogId
     *
     * @param int $sharedCatlaogId
     * @return void
     */
    public function setSharedCatalogId($sharedCatlaogId);

    /**
     * SetSharedCatalogId
     *
     * @return int
     */
    public function getSharedCatalogId();

    /**
     * SetLegacyCatalogRootFolderId
     *
     * @param string $legacyCatalogRootFolderId
     * @return void
     */
    public function setLegacyCatalogRootFolderId($legacyCatalogRootFolderId);

    /**
     * GetLegacyCatalogRootFolderId
     *
     * @return string
     */
    public function getLegacyCatalogRootFolderId();

    /**
     * SetCategoryId
     *
     * @param int $categoryId
     * @return void
     */
    public function setCategoryId($categoryId);

    /**
     * GetCategoryId
     *
     * @return int
     */
    public function getCategoryId();

    /**
     * SetStatus
     *
     * @param int $status
     * @return void
     */
    public function setStatus($status);

    /**
     * GetStatus
     *
     * @return int
     */
    public function getStatus();
}
