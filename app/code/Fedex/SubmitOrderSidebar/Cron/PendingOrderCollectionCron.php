<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Exception\CouldNotSaveException;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Class use to update oms order from pending to new status
 */
class PendingOrderCollectionCron
{

    public const INCREMENT_ID = 'increment_id';
    public const QUOTE_ID = 'quote_id';

    /**
     * PendingOrderCollectionCron constructor
     *
     * @param LoggerInterface $logger
     * @param DeliveryHelper $deliveryHelper
     * @param ToggleConfig $toggleConfig
     * @param SubmitOrderModelAPI $submitOrderModelAPI
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected DeliveryHelper $deliveryHelper,
        protected ToggleConfig $toggleConfig,
        protected SubmitOrderModelAPI $submitOrderModelAPI,
        protected OrderCollectionFactory $orderCollectionFactory
    )
    {
    }

    /**
     * Used to update pending to new
     *
     * @return this
     */
    public function getPendingStatusOrderCollection()
    {
        try {
            $isEproCustomer = $this->deliveryHelper->isEproCustomer();
            if (!$isEproCustomer) {
                $orders = $this->orderCollectionFactory->create()
                ->addFieldToSelect(['created_at', self::INCREMENT_ID, 'quote_id', 'status'])
                ->addFieldToFilter('status', ['in' => ['pending']])
                ->addFieldToFilter(
                    'created_at',
                    [
                        'from' => strtotime('-1 day', time()),
                        'to' => time(),
                        'datetime' => true,
                    ]
                );

                $orderCollectionDataArray = array_filter($orders->getData());
                foreach ($orderCollectionDataArray as $orderCollectionData) {
                    if (!empty($orderCollectionData[self::INCREMENT_ID])) {
                        $gtn = $orderCollectionData[self::INCREMENT_ID];
                        // validate order transaction details by retail transaction id
                        $this->validateOrderTransaction($gtn);
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__. ': Error while deleting pending order by cron ' . $e->getMessage()
            );
        }

        return $this;
    }

    /**
     * Validate Order Transaction
     *
     * @param int|string $gtn
     */
    public function validateOrderTransaction($gtn)
    {
        $retailTransactionId = $this->submitOrderModelAPI->getRetailTransactionIdByGtnNumber($gtn);
        if (empty($retailTransactionId)) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__
                .': Delete order with Pending Status for the GTN:'. $gtn.' while running Delete Pending Orders Cron.'
            );
            $this->submitOrderModelAPI->deleteOrderWithPendingStatus($gtn);
        }
    }
}
