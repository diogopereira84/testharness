<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Manuel Rosario <manuel.rosario.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model;

use Fedex\MarketplaceWebhook\Api\Data\CreateInvoiceMessageInterfaceFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

class CreateInvoicePublisher
{
    public const QUEUE_NAME = 'createInvoiceMarketplaceOnlyOrders';

    /**
     * @param PublisherInterface $publisher
     * @param CreateInvoiceMessageInterfaceFactory $createInvoiceMessage
     */
    public function __construct(
        private PublisherInterface $publisher,
        private CreateInvoiceMessageInterfaceFactory $createInvoiceMessageFactory
    ) {
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function execute(int $orderId): void
    {
        $createInvoice3POnly = $this->createInvoiceMessageFactory->create();
        $createInvoice3POnly->setOrderId($orderId);

        $this->publisher->publish(self::QUEUE_NAME, $createInvoice3POnly);
    }
}
