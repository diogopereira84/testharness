<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\Integration;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Exception\StateException;
use Fedex\Cart\Api\Data\CartIntegrationInterfaceFactory;
use Fedex\Cart\Model\ResourceModel\Quote\Integration as ResourceData;
use Fedex\Cart\Model\ResourceModel\Quote\Integration\CollectionFactory as IntegrationCollectionFactory;
use Psr\Log\LoggerInterface;

class Repository implements CartIntegrationRepositoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * Repository constructor.
     * @param ResourceData $resource
     * @param IntegrationCollectionFactory $integrationCollectionFactory
     * @param CartIntegrationInterfaceFactory $integrationInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected ResourceData $resource,
        protected IntegrationCollectionFactory $integrationCollectionFactory,
        protected CartIntegrationInterfaceFactory $integrationInterfaceFactory,
        protected DataObjectHelper $dataObjectHelper,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param CartIntegrationInterface $integration
     * @return DataInterface|\Magento\Framework\Model\AbstractModel
     * @throws CouldNotSaveException
     */
    public function save(CartIntegrationInterface $integration)
    {
        try {
            /** @var DataInterface|\Magento\Framework\Model\AbstractModel $integration */
            $this->resource->save($integration);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Could not save the data: ' . $exception->getMessage());
            throw new CouldNotSaveException(__(
                'Could not save the data: %1',
                $exception->getMessage()
            ));
        }
        return $integration;
    }

    /**
     * @param $integrationId
     * @return CartIntegrationInterface|\Magento\Framework\Model\AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function getById($integrationId)
    {
        if (!isset($this->instances[$integrationId])) {
            /** @var \Fedex\Cart\Api\Data\CartIntegrationInterface|\Magento\Framework\Model\AbstractModel $data */
            $data = $this->integrationInterfaceFactory->create();
            $this->resource->load($data, $integrationId);
            if (!$data->getId()) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Requested integration id  '. $integrationId .'  does not exist.');
                throw new NoSuchEntityException(__('Requested integration doesn\'t exist'));
            }
            $this->instances[$integrationId] = $data;
        }
        return $this->instances[$integrationId];
    }

    /**
     * @param CartIntegrationInterface $integration
     * @return bool
     * @throws CouldNotSaveException
     */
    public function delete(CartIntegrationInterface $integration): bool
    {
        /** @var \Fedex\Cart\Api\Data\CartIntegrationInterface|\Magento\Framework\Model\AbstractModel $data */
        $id = $integration->getId();
        try {
            unset($this->instances[$id]);
            $this->resource->delete($integration);
        } catch (ValidatorException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            throw new CouldNotSaveException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Unable to remove integration id.');
            throw new StateException(__('Unable to remove integration %1', $id));
        }
        unset($this->instances[$id]);
        return true;
    }

    /**
     * @param $integrationId
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function deleteById($integrationId): bool
    {
        $data = $this->getById($integrationId);
        return $this->delete($data);
    }

    /**
     * @param $quoteId
     * @return CartIntegrationInterface|\Magento\Framework\Model\AbstractModel|mixed
     * @throws NoSuchEntityException
     */
    public function getByQuoteId($quoteId)
    {
        if (!isset($this->instances[$quoteId])) {
            /** @var \Fedex\Cart\Api\Data\CartIntegrationInterface|\Magento\Framework\Model\AbstractModel $data */
            $data = $this->integrationInterfaceFactory->create();
            $this->resource->load($data, $quoteId, 'quote_id');
            if (!$data->getQuoteId()) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Requested quote id '. $quoteId .' does not exist.');
                throw new NoSuchEntityException(__('Requested integration doesn\'t exist'));
            }
            $this->instances[$quoteId] = $data;
        }
        return $this->instances[$quoteId];
    }
}
