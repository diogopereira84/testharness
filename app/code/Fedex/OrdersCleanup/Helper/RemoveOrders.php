<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\OrdersCleanup\Helper;

use Exception;
use Magento\Framework\App\Helper\Context;
use Fedex\OrdersCleanup\Model\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory;

class RemoveOrders extends AbstractHelper
{

    public array $errorOrders = [];
    public array $successOrders = [];
    protected int $terminateLimit;
    protected int $totalProcessedRecords = 0;
    protected int $deletedRecords =  0;
    protected int $failedRecords = 0;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param OrderFactory $orderResourceFactory
     * @param Config $moduleConfig
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly OrderFactory $orderResourceFactory,
        private readonly Config                   $moduleConfig,
        private readonly Data                     $helper,
    ) {
        parent::__construct($context);
    }

    /**
     * Delete order related data
     *
     * @param $incrementId
     */
    public function deleteRecordByIncrementId($incrementId): void
    {
        $resource = $this->orderResourceFactory->create();
        $connection = $resource->getConnection();

        $connection->delete(
            $resource->getTable(Config::SALES_ORDER_GRID),
            $connection->quoteInto('increment_id = ?', $incrementId)
        );
    }

    /**
     * Delete order related data
     *
     * @param $orderId
     */
    public function deleteRecord($orderId): void
    {
        $resource   = $this->orderResourceFactory->create();
        $connection = $resource->getConnection();

        $connection->delete(
            $resource->getTable(Config::SALES_INVOICE_GRID),
            $connection->quoteInto('order_id = ?', $orderId)
        );

        $connection->delete(
            $resource->getTable(Config::SALES_SHIPMENT_GRID),
            $connection->quoteInto('order_id = ?', $orderId)
        );

        $connection->delete(
            $resource->getTable(Config::ORDER_PRODUCING_ADDRESS),
            $connection->quoteInto('order_id = ?', $orderId)
        );

        $connection->delete(
            $resource->getTable(Config::SALES_CREDITMEMO_GRID),
            $connection->quoteInto('order_id = ?', $orderId)
        );
    }

    /**
     * Remove eligible orders for guest and logged-in users
     *
     * @return void
     * @throws Exception
     */
    public function removeOrders(): void
    {
        if (!$this->moduleConfig->isRemoveEnabled()) {
            return;
        }

        $time_start = microtime(true);
        $this->terminateLimit = $this->moduleConfig->getTerminateLimit();
        $connection = $this->resourceConnection->getConnection();

        $this->processOrderDeletion($this->getLoggedInUserLastDate(), true, $connection);
        $this->processOrderDeletion($this->getGuestUserLastDate(), false, $connection);

        $time_end = microtime(true);
        $execution_time = $time_end - $time_start;

        $this->helper->logMessage(
            __METHOD__ . ':' . __LINE__,
            'Total Processed Record: ' . $this->totalProcessedRecords .
            ', Deleted Record: ' . $this->deletedRecords .
            ', Failed Record: ' . $this->failedRecords .
            ', Execution Time: ' . $execution_time . ' seconds',
            true
        );
    }

    /**
    * Core order deletion logic.
    *
    *@return void
    */
    private function processOrderDeletion(?string $date, bool $isLoggedIn, $connection): void
    {
        if (!$this->moduleConfig->isSgcOrderCleanupProcessEnabled()) {
            return;
        }
        $userType = $isLoggedIn ? 'logged-in' : 'guest';

        if ($date === null) {
            $this->helper->logMessage(
                __METHOD__ . ':' . __LINE__,
                "Skipping deletion for {$userType} users. Retention is set to -1.",
                true
            );
            return;
        }

        $stopDate = new \DateTime($date);
        $stopDate->modify("+1 day");
        $endDate = $stopDate->format('Y-m-d');

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('updated_at', ['lt' => $endDate]);
        $orderCollection->addFieldToFilter('updated_at', ['gteq' => $date]);
        $orderCollection->addFieldToFilter('customer_is_guest', $isLoggedIn ? 0 : 1);
        $orderCollection->walk(function ($order) use ($connection, $userType) {
            if ($this->failedRecords > $this->terminateLimit) {
                return;
            }
            try {
                ++$this->totalProcessedRecords;
                $logs = [
                    'entity_id' => $order->getIncrementId(),
                    'customer_id' => $order->getCustomerId(),
                    'ext_order_id' => $order->getExtOrderId(),
                    'ext_customer_id' => $order->getExtCustomerId(),
                    'customer_is_guest' => $userType === 'guest' ? 1 : 0,
                ];
                $orderId = $order->getId();
                $connection->delete($connection->getTableName('sales_order'), ['entity_id = ?' => $orderId]);
                $this->deleteRecord($orderId);
                $this->deleteRecordByIncrementId($order->getIncrementId());
                ++$this->deletedRecords;
                $this->helper->logMessage(
                    __METHOD__ . ':' . __LINE__,
                    'The following order has been deleted. ' . json_encode($logs),
                    false
                );
            } catch (\Exception $e) {
                $logs['error'] = $e->getMessage();
                ++$this->failedRecords;
                $this->helper->logMessage(
                    __METHOD__ . ':' . __LINE__,
                    'Unable to delete the following order ' . json_encode($logs),
                    true
                );
            }
        });
    }

    /**
     * Get last eligible order date for logged-in users
     *
     * @throws Exception
     */
    public function getLoggedInUserLastDate(): ?string
    {
        $age = $this->moduleConfig->getLoggedInUsersRetentionDays();
        //Do not process deletions for this user type. Leave all orders, no cleanup for this group
        if ((int)$age === -1) {
            return null;
        }

        $previousDate = new \DateTime();
        $previousDate->modify('-' . $age . ' days');
        return $previousDate->format('Y-m-d');
    }

    /**
     * Get last eligible order date for guest users
     *
     * @throws Exception
     */
    public function getGuestUserLastDate(): ?string
    {
        $age = $this->moduleConfig->getGuestUsersRetentionDays();
        //Do not process deletions for this user type. Leave all orders, no cleanup for this group
        if ((int)$age === -1) {
            return null;
        }

        $previousDate = new \DateTime();
        $previousDate->modify('-' . $age . ' days');
        return $previousDate->format('Y-m-d');
    }

}
