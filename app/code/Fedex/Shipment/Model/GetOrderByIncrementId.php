<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\Shipment\Model;

use Fedex\Shipment\Api\GetOrderByIncrementIdInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;

readonly class GetOrderByIncrementId implements GetOrderByIncrementIdInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private SearchCriteriaBuilder    $searchCriteriaBuilder
    ) {}

    /**
     * @param string $incrementId
     * @return OrderInterface|null
     */
    public function execute(string $incrementId): ?OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId, 'eq')
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();

        if (count($orders) !== 1) {
            return null;
        }

        return array_shift($orders);
    }
}
