<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Model;

use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory;
use Fedex\PoliticalDisclosure\Model\ResourceModel\OrderDisclosure as Resource;
use Magento\Framework\Exception\LocalizedException;

class OrderDisclosureRepository implements OrderDisclosureRepositoryInterface
{
    /**
     * @param \Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory $factory
     * @param Resource $resource
     */
    public function __construct(
        private OrderDisclosureFactory $factory,
        private Resource $resource
    ) {}

    /**
     * @param int $orderId
     * @return OrderDisclosure|null
     */
    public function getByOrderId(int $orderId): ?OrderDisclosure
    {
        $model = $this->factory->create();
        $this->resource->load($model, $orderId, 'order_id');

        if (!$model->getId()) {
            return null;
        }

        return $model;
    }

    /**
     * @param OrderDisclosure $entity
     * @return OrderDisclosure
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(OrderDisclosure $entity): OrderDisclosure
    {
        $this->resource->save($entity);
        return $entity;
    }

    /**
     * @param int $orderId
     * @return bool
     * @throws \Exception
     */
    public function deleteByOrderId(int $orderId): bool
    {
        $model = $this->getByOrderId($orderId);
        if (!$model) {
            return false;
        }
        $this->resource->delete($model);
        return true;
    }

    /**
     * @param int $quoteId
     * @return OrderDisclosure|null
     */
    public function getByQuoteId(int $quoteId): ?OrderDisclosure
    {
        $model = $this->factory->create();
        $this->resource->load($model, $quoteId, 'quote_id');

        if (!$model->getId()) {
            return null;
        }

        return $model;
    }
}
