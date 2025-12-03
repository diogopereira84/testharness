<?php

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\OrderStoreRetrieverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderStoreRetriever implements OrderStoreRetrieverInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'sales_order_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getStoreIdFromOrder($orderId): int
    {
        $order = $this->orderRepository->get($orderId);
        return $order->getStoreId();
    }

}
