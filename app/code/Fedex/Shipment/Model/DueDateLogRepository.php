<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\Shipment\Model;

use Fedex\Shipment\Api\DueDateLogRepositoryInterface;
use Fedex\Shipment\Model\ResourceModel\DueDateLog\CollectionFactory;
use Magento\Framework\Data\Collection;
use Fedex\Shipment\Model\ResourceModel\DueDateLog;
use Magento\Framework\Exception\LocalizedException;

readonly class DueDateLogRepository implements DueDateLogRepositoryInterface
{
    /**
     * @param CollectionFactory $dueDateLogCollectionFactory
     * @param DueDateLog $dueDateLogResource
     */
    public function __construct(
        private readonly CollectionFactory $dueDateLogCollectionFactory,
        private readonly DueDateLog $dueDateLogResource
    ) {}

    /**
     * @param int $orderId
     * @return mixed
     */
    public function getByOrderId(int $orderId): mixed
    {
        $collection = $this->dueDateLogCollectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId)
            ->setOrder('updated_at', Collection::SORT_ORDER_DESC)
            ->setPageSize(1)
            ->setCurPage(1);

        $item = $collection->getFirstItem();
        return $item->getId() ? $item : null;
    }

    /**
     * @throws \Exception
     */
    public function isNewerThanLast(int $orderId, string $dueDate): bool
    {
        $connection = $this->dueDateLogResource->getConnection();
        $tableName = $this->dueDateLogResource->getMainTable();

        $select = $connection->select()
            ->from($tableName, ['max_updated_at' => new \Zend_Db_Expr('MAX(updated_at)')])
            ->where('order_id = ?', (int)$orderId);

        $lastUpdatedAt = $connection->fetchOne($select);

        if (!$lastUpdatedAt) {
            return true;
        }

        $newDate = new \DateTimeImmutable($dueDate);
        $lastDate = new \DateTimeImmutable($lastUpdatedAt);

        return $newDate > $lastDate;
    }

    /**
     * @param $order
     * @param $newDescription
     * @return void
     */
    public function updateOrderShippingDescription($order, $newDescription)
    {
        $connection = $order->getResource()->getConnection();
        $orderId = $order->getId();
        $connection->update(
            $connection->getTableName('sales_order'),
            ['shipping_description' => $newDescription],
            ['entity_id = ?' => $orderId]
        );
    }
}
