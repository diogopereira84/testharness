<?php

namespace Fedex\SharedCatalogCustomization\Api;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface;

/**
 * Interface SharedCatalogSyncQueueConfigurationRepositoryInterface
 */
interface SharedCatalogSyncQueueConfigurationRepositoryInterface
{
    /**
     * GetById
     *
     * @param int $id
     * @return SharedCatalogSyncQueueConfigurationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Save
     *
     * @param SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration
     * @return SharedCatalogSyncQueueConfigurationInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration);

    /**
     * Delete
     *
     * @param SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration);

     /**
      * GetBySharedCatalogId
      *
      * @param int $id
      * @return SharedCatalogSyncQueueConfigurationInterface
      * @throws \Magento\Framework\Exception\NoSuchEntityException
      */
    public function getBySharedCatalogId($id);
}
