<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedDetails\Model;

use Fedex\SharedDetails\Api\PickupStoreEmailResolverInterface;
use Psr\Log\LoggerInterface;
use Fedex\Shipment\Model\ResourceModel\ProducingAddress\CollectionFactory as ProducingAddressCollectionFactory;

class PickupStoreEmailResolver implements PickupStoreEmailResolverInterface
{
    /**
     * Constructor
     *
     * @param ProducingAddressCollectionFactory $producingAddressCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ProducingAddressCollectionFactory $producingAddressCollectionFactory,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get store emails for multiple order IDs.
     *
     * @param int[] $orderIds
     * @return array<int, string|null> [order_id => store_email]
     */
    public function getStoreEmailsByOrderIds(array $orderIds): array
    {
        $emails = [];
        if (empty($orderIds)) {
            return $emails;
        }
        try {
            $collection = $this->producingAddressCollectionFactory->create();
            $collection->addFieldToFilter('order_id', ['in' => $orderIds]);
            foreach ($collection as $producingAddress) {
                $orderId = (int)$producingAddress->getOrderId();
                $emails[$orderId] = $producingAddress->getEmailAddress() ?? null;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__.':'.__LINE__. " Failed to get store emails: " .
                $e->getMessage()
            );
        }
        return $emails;
    }
}
