<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\IntegrationItem;

use Fedex\Cart\Api\CartIntegrationItemRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationItemInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Exception\StateException;
use Fedex\Cart\Api\Data\CartIntegrationItemInterfaceFactory;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem as ResourceData;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem\CollectionFactory as IntegrationItemCollectionFactory;
use Psr\Log\LoggerInterface;

class Repository implements CartIntegrationItemRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * Repository constructor.
     * @param ResourceData $resource
     * @param IntegrationItemCollectionFactory $integrationItemCollectionFactory
     * @param CartIntegrationItemInterfaceFactory $integrationItemInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected ResourceData $resource,
        protected IntegrationItemCollectionFactory $integrationItemCollectionFactory,
        protected CartIntegrationItemInterfaceFactory $integrationItemInterfaceFactory,
        protected DataObjectHelper $dataObjectHelper,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function save(CartIntegrationItemInterface $integrationItem): CartIntegrationItemInterface
    {
        try {
            /** @var DataInterface|\Magento\Framework\Model\AbstractModel $integrationItem */
            $this->resource->save($integrationItem);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Could not save the data: ' . $exception->getMessage());
            throw new CouldNotSaveException(__(
                'Could not save the data: %1',
                $exception->getMessage()
            ));
        }
        return $integrationItem;
    }

    /**
     * @inheritDoc
     */
    public function saveByQuoteItemId(int $itemId, string $itemData): CartIntegrationItemInterface
    {
        try {
            /** @var \Fedex\Cart\Api\Data\CartIntegrationItemInterface|\Magento\Framework\Model\AbstractModel $data */
            try {
                $data = $this->getByQuoteItemId($itemId);
            } catch (NoSuchEntityException) {
                $data = $this->integrationItemInterfaceFactory->create();
                $data->setItemId($itemId);
            }

            $data->setItemData($itemData);

            $integrationItem = $this->save($data);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Could not save the data: ' . $exception->getMessage());
            throw new CouldNotSaveException(__(
                $exception->getMessage()
            ));
        }
        return $integrationItem;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $integrationItemId): CartIntegrationItemInterface
    {
        if (!isset($this->instances[$integrationItemId])) {
            /** @var \Fedex\Cart\Api\Data\CartIntegrationItemInterface|\Magento\Framework\Model\AbstractModel $data */
            $data = $this->integrationItemInterfaceFactory->create();
            $this->resource->load($data, $integrationItemId);
            if (!$data->getId()) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Requested integrationItem id does not exist. Integration Item ID: ' . $integrationItemId);
                throw new NoSuchEntityException(__('Requested integrationItem doesn\'t exist'));
            }
            $this->instances[$integrationItemId] = $data;
        }
        return $this->instances[$integrationItemId];
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteItemId(int $itemId): CartIntegrationItemInterface
    {
        if (!isset($this->instances[$itemId])) {
            /** @var \Fedex\Cart\Api\Data\CartIntegrationItemInterface|\Magento\Framework\Model\AbstractModel $data */
            $data = $this->integrationItemInterfaceFactory->create();
            $this->resource->load($data, $itemId, 'item_id');
            if (!$data->getItemId()) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Requested quote item id does not exist. Integration Item ID: ' . $itemId);
                throw new NoSuchEntityException(__('Requested quote item id doesn\'t exist'));
            }
            $this->instances[$itemId] = $data;
        }
        return $this->instances[$itemId];
    }

    /**
     * @inheritDoc
     */
    public function delete(CartIntegrationItemInterface $integrationItem): bool
    {
        /** @var \Fedex\Cart\Api\Data\CartIntegrationItemInterface|\Magento\Framework\Model\AbstractModel $data */
        $id = $integrationItem->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($integrationItem);
        } catch (ValidatorException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Unable to remove integrationItem id.');
            throw new StateException(__('Unable to remove integrationItem %1', $id));
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $integrationItemId): bool
    {
        $data = $this->getById($integrationItemId);
        return $this->delete($data);
    }
}
