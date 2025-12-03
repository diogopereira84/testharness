<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Model;

use Fedex\Shipment\Api\MessageInterface;
use Fedex\Shipment\Api\SubscriberInterface;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Fedex\Shipment\Helper\Data;
use Fedex\Shipment\Helper\ShipmentEmail;

class Subscriber implements SubscriberInterface
{
    /**
    * Subscriber constructor.
    */
    public function __construct(
        protected LoggerInterface $logger,
        protected Data $helperData,
        protected DateTime $dateTime,
        protected ShipmentEmail $shipmentHelper
    )
    {
    }
    
    /**
     * Use to process data from queue
     * to send email
     */
    public function processMessage(MessageInterface $message)
    {
        $messageQueueShipmentId = $message->getMessage();
        try {
            $currentDate = $this->dateTime->date('Y-m-d');
            $shipmentStatusValue = "ready_for_pickup";
            $orderId = $this->helperData->getOrderIdByShipmentId($messageQueueShipmentId);
            $result = $this->shipmentHelper->sendEmail($shipmentStatusValue, $orderId, $messageQueueShipmentId);
            if ($this->isJson($result)) {
                $this->helperData->setShipmentEmail($shipmentId, $currentDate);
            }
            $this->logger->info(__METHOD__.':'.__LINE__.':Message sent for order '.$orderId);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.print_r($e->getMessage(), true));
        }

        return true;
    }

    public function isJson($string, $returnData = false)
    {
        $data = json_decode($string);
        if (json_last_error() == JSON_ERROR_NONE) {
            if ($returnData) {
                return $data;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}
