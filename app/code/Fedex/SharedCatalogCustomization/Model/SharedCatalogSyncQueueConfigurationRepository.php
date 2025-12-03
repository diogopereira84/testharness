<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model;

use Fedex\SharedCatalogCustomization\Api\SharedCatalogSyncQueueConfigurationRepositoryInterface;
use Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * SharedCatalogSyncQueueConfigurationRepository Repository Class
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SharedCatalogSyncQueueConfigurationRepository implements SharedCatalogSyncQueueConfigurationRepositoryInterface
{
    /**
     * SharedCatalogSyncQueueConfigurationRepository Construct.
     *
     * @param SharedCatalogSyncQueueConfigurationFactory $sharedCatalogSyncQueueConfigurationFactory
     * @param SharedCatalogSyncQueueConfiguration $sharedCatalogSyncQueueConfiguration
     * @param LoggerInterface $logger
     */
    public function __construct(
        private SharedCatalogSyncQueueConfigurationFactory $sharedCatalogSyncQueueConfigurationFactory,
        private SharedCatalogSyncQueueConfiguration $sharedCatalogSyncQueueConfiguration,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * GetById
     *
     * @param int $id
     * @return SharedCatalogSyncQueueConfigurationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id)
    {
        $sharedCatalogSyncQueueConfiguration = $this->sharedCatalogSyncQueueConfigurationFactory->create();
        $this->sharedCatalogSyncQueueConfiguration->load($sharedCatalogSyncQueueConfiguration, $id);
        if (!$sharedCatalogSyncQueueConfiguration->getId()) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Unable to find catalog sync legacy configuration with ID ' . $id);
            throw new NoSuchEntityException(__('Unable to find catalog sync legacy configuration with ID "%1"', $id));
        }
        return $sharedCatalogSyncQueueConfiguration;
    }

    /**
     * Save
     *
     * @param SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration
     * @return SharedCatalogSyncQueueConfigurationInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @codeCoverageIgnore
     */
    public function save(SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration)
    {
        $this->sharedCatalogSyncQueueConfiguration->save($sharedCatalogSyncQueueConfiguration);
        return $sharedCatalogSyncQueueConfiguration;
    }

    /**
     * Delete
     *
     * @param SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @codeCoverageIgnore
     */
    public function delete(SharedCatalogSyncQueueConfigurationInterface $sharedCatalogSyncQueueConfiguration)
    {
        try {
            $this->sharedCatalogSyncQueueConfiguration->delete($sharedCatalogSyncQueueConfiguration);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Could not delete the catalog sync legacy configuration entry: ' . $exception->getMessage());
            throw new CouldNotDeleteException(
                __('Could not delete the catalog sync legacy configuration entry: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * GetBySharedCatalogId
     *
     * @param int $id
     * @return SharedCatalogSyncQueueConfigurationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBySharedCatalogId($id)
    {
        $sharedCatalogSyncQueueConfiguration = $this->sharedCatalogSyncQueueConfigurationFactory->create();
        $sharedCatalogSyncQueueConfiguration->load($id, 'shared_catalog_id');

        if (!$sharedCatalogSyncQueueConfiguration->getId()) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Unable to find catalog sync legacy configuration with ID ' . $id);
            throw new NoSuchEntityException(__('Unable to find catalog sync legacy configuration with ID "%1"', $id));
        }
        return $sharedCatalogSyncQueueConfiguration;
    }
}
