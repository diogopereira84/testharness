<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Shipment\Model;

use Fedex\Shipment\Api\Data\SendOrderEmailMessageInterfaceFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;

class SendOrderEmailPublisher
{
    public const QUEUE_NAME = 'sendOrderEmailQueue';

    /**
     * @param PublisherInterface $publisher
     * @param SendOrderEmailMessageInterfaceFactory $sendOrderEmailMessageFactory
     * @param MarketPlaceHelper $marketplaceHelper
     * @param CheckoutHelper $checkoutHelper
     */
    public function __construct(
        private PublisherInterface                    $publisher,
        private SendOrderEmailMessageInterfaceFactory $sendOrderEmailMessageFactory,
        private MarketPlaceHelper                     $marketplaceHelper,
        private CheckoutHelper                        $checkoutHelper
    ) {
    }

    /**
     * @param string $orderStatus
     * @param int $orderId
     * @param int|null $shipmentId
     * @return void
     */
    public function execute(string $orderStatus, int $orderId, int $shipmentId = null, $track = null): void
    {
        /** @var \Fedex\Shipment\Model\Data\SendOrderEmailMessage $sendOrderEmailMessage */
        $sendOrderEmailMessage = $this->sendOrderEmailMessageFactory->create();
        $sendOrderEmailMessage->setShipmentStatus($orderStatus);
        $sendOrderEmailMessage->setOrderId($orderId);
        $sendOrderEmailMessage->setShipmentId($shipmentId);
        if ($this->checkoutHelper->isEssendantToggleEnabled()) {
            $sendOrderEmailMessage->setTrack($track["tracking_number"] ?? null);
        }

        $this->publisher->publish(self::QUEUE_NAME, $sendOrderEmailMessage);
    }
}
