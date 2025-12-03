<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Cron;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use Fedex\Shipment\Helper\ShipmentEmail;
use Fedex\Shipment\Helper\Data;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\Shipment\Api\MessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Data to send ready for pickup email before 5 days
 */
class ReadyForPickupQueueProcessCron
{
    /**
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $date
     * @param ShipmentEmail $shipmentEmail
     * @param Data $helperData
     * @param LoggerInterface $logger
     * */

    public function __construct(
        public ShipmentRepositoryInterface $shipmentRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected DateTime $dateTime,
        protected ShipmentEmail $shipmentEmail,
        protected Data $helperData,
        private PublisherInterface $publisher,
        private MessageInterface $message,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Use to call function to add shipment id
     * in queue
     * @return string
     */
    public function execute()
    {
        $shipmentStatusValue = "ready_for_pickup";
        $shipmentStatus = $this->helperData->getShipmentStatus($shipmentStatusValue);
        $currentDate = $this->dateTime->date('Y-m-d');
        $dateAfterXDays = $this->dateTime->date('Y-m-d', strtotime($currentDate." +5 days"));
        try {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter("shipment_status", $shipmentStatus)->create();
            $shipments = $this->shipmentRepository->getList($searchCriteria);
            $shipmentRecords = $shipments->getItems();

            if (count($shipmentRecords)>0) {
                foreach ($shipmentRecords as $shipmentItem) {
                    $pickupAllowedDate = $shipmentItem->getPickupAllowedUntilDate();
                    $createdAtDate = $this->dateTime->date('Y-m-d', strtotime($shipmentItem->getCreatedAt()));
                    $pickupDate = $this->dateTime->date('Y-m-d', strtotime($pickupAllowedDate));
                    if ($pickupDate==$dateAfterXDays && $createdAtDate<$currentDate) {
                        $shipmentId = $shipmentItem->getEntityId();
                        $this->message->setMessage($shipmentId);
                        $this->publisher->publish('readyForPickupEmailQueue', $this->message);
                    }
                }
                return true;
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }
}
