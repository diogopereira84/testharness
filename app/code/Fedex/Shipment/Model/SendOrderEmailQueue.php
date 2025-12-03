<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Shipment\Model;

use Fedex\Shipment\Api\Data\SendOrderEmailMessageInterface;
use Fedex\Shipment\Helper\ShipmentEmail;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;

class SendOrderEmailQueue
{
    /**
     * @param ShipmentEmail $shipmentEmail
     * @param MarketPlaceHelper $marketplaceHelper
     */
    public function __construct(
        private ShipmentEmail $shipmentEmail,
        private MarketPlaceHelper $marketplaceHelper
    ) {
    }

    /**
     * @param SendOrderEmailMessageInterface $message
     * @return void
     */
    public function execute(SendOrderEmailMessageInterface $message): void
    {
        $shipmentStatus = $message->getShipmentStatus();
        $orderId = $message->getOrderId();
        $shipmentId = $message->getShipmentId();
        $track = $message->getTrack();

        if (!$shipmentStatus || !$orderId) {
            return;
        }

        $this->shipmentEmail->sendEmail($shipmentStatus, $orderId, $shipmentId, $track);
    }
}
