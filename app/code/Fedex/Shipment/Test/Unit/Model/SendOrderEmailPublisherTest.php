<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Shipment\Test\Unit\Model;

use Fedex\Shipment\Api\Data\SendOrderEmailMessageInterfaceFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Shipment\Model\SendOrderEmailPublisher;
use Fedex\Shipment\Api\Data\SendOrderEmailMessageInterface;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;

class SendOrderEmailPublisherTest extends TestCase
{
    protected $publisherMock;
    protected $sendOrderEmailMessageFactoryMock;
    protected $sendOrderEmailPublisher;
    protected $marketplaceHelper;
    protected $logger;
    protected $checkoutHelper;

    protected function setUp(): void
    {
        $this->publisherMock = $this->createMock(PublisherInterface::class);

        $this->sendOrderEmailMessageFactoryMock =
            $this->createMock(SendOrderEmailMessageInterfaceFactory::class);

        $this->marketplaceHelper = $this->getMockBuilder(MarketPlaceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutHelper = $this->getMockBuilder(CheckoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sendOrderEmailPublisher = new SendOrderEmailPublisher(
            $this->publisherMock,
            $this->sendOrderEmailMessageFactoryMock,
            $this->marketplaceHelper,
            $this->checkoutHelper
        );

        parent::setUp();
    }

    public function testExecute(): void
    {
        $orderStatus = 'processing';
        $orderId = 123;
        $shipmentId = 456;

        $sendOrderEmailMessageMock = $this->createMock(SendOrderEmailMessageInterface::class);
        $sendOrderEmailMessageMock->expects($this->once())->method('setShipmentStatus')->with($orderStatus);
        $sendOrderEmailMessageMock->expects($this->once())->method('setOrderId')->with($orderId);
        $sendOrderEmailMessageMock->expects($this->once())->method('setShipmentId')->with($shipmentId);

        $this->sendOrderEmailMessageFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($sendOrderEmailMessageMock);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(SendOrderEmailPublisher::QUEUE_NAME, $sendOrderEmailMessageMock);

        $this->sendOrderEmailPublisher->execute($orderStatus, $orderId, $shipmentId);
    }
}
