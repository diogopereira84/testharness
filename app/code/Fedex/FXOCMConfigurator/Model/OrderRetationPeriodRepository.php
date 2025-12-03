<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Model;

use Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface;
use Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodSearchResultsInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\OrderRetationPeriodRepositoryInterface;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod as ResourceOrderRetationPeriod;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\CollectionFactory as OrderRetationPeriodCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderRetationPeriodRepository implements OrderRetationPeriodRepositoryInterface
{

    /**
     * @param ResourceOrderRetationPeriod $resource
     * @param OrderRetationPeriodInterfaceFactory $orderRetationPeriodFactory
     * @param OrderRetationPeriodCollectionFactory $orderRetationPeriodCollectionFactory
     * @param OrderRetationPeriodSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        protected ResourceOrderRetationPeriod $resource,
        protected OrderRetationPeriodInterfaceFactory $orderRetationPeriodFactory,
        protected OrderRetationPeriodCollectionFactory $orderRetationPeriodCollectionFactory,
        protected OrderRetationPeriodSearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function save(
        OrderRetationPeriodInterface $orderRetationPeriod
    ) {
        try {
            $this->resource->save($orderRetationPeriod);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the orderRetationPeriod: %1',
                $exception->getMessage()
            ));
        }
        return $orderRetationPeriod;
    }

    /**
     * @inheritDoc
     */
    public function get($orderRetationPeriodId)
    {
        $orderRetationPeriod = $this->orderRetationPeriodFactory->create();
        $this->resource->load($orderRetationPeriod, $orderRetationPeriodId);
        if (!$orderRetationPeriod->getId()) {
            throw new NoSuchEntityException(__('OrderRetationPeriod with id "%1" does not exist.', $orderRetationPeriodId));
        }
        return $orderRetationPeriod;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        OrderRetationPeriodInterface $orderRetationPeriod
    ) {
        try {
            $orderRetationPeriodModel = $this->orderRetationPeriodFactory->create();
            $this->resource->load($orderRetationPeriodModel, $orderRetationPeriod->getOrderretationperiodId());
            $this->resource->delete($orderRetationPeriodModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the OrderRetationPeriod: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($orderRetationPeriodId)
    {
        return $this->delete($this->get($orderRetationPeriodId));
    }
}

