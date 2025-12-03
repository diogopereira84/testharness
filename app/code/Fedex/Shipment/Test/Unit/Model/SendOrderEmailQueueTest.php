<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Shipment\Test\Unit\Model;

use Fedex\Shipment\Model\SendOrderEmailQueue;
use Fedex\Shipment\Api\Data\SendOrderEmailMessageInterface;
use Fedex\Shipment\Helper\ShipmentEmail;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;
use Psr\Log\LoggerInterface;

class SendOrderEmailQueueTest extends TestCase
{
    protected $shipmentEmailMock;
    protected $sendOrderEmailQueue;
    const SHIPMENT_ID = 456;
    const ORDER_ID = 123;
    const SHIPMENT_STATUS = 'shipped';
    protected $marketplaceHelper;
    protected $logger;

    protected function setUp(): void
    {
        $this->shipmentEmailMock = $this->getMockBuilder(ShipmentEmail::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceHelper = $this->getMockBuilder(MarketPlaceHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sendOrderEmailQueue = new SendOrderEmailQueue(
            $this->shipmentEmailMock,
            $this->marketplaceHelper,
            $this->logger
        );
    }

    public function testExecuteWithValidData()
    {
        $messageMock = $this->getMockBuilder(SendOrderEmailMessageInterface::class)
            ->getMock();
        $messageMock->expects($this->once())
            ->method('getShipmentStatus')
            ->willReturn(self::SHIPMENT_STATUS);
        $messageMock->expects($this->once())
            ->method('getOrderId')
            ->willReturn(self::ORDER_ID);
        $messageMock->expects($this->once())
            ->method('getShipmentId')
            ->willReturn(self::SHIPMENT_ID);

        $this->shipmentEmailMock->expects($this->once())
            ->method('sendEmail')
            ->with(self::SHIPMENT_STATUS, self::ORDER_ID, self::SHIPMENT_ID);

        $this->sendOrderEmailQueue->execute($messageMock);
    }

    public function testExecuteWithMissingData()
    {
        $messageMock = $this->getMockBuilder(SendOrderEmailMessageInterface::class)->getMock();
        $messageMock->expects($this->once())->method('getShipmentStatus')->willReturn(null);
        $messageMock->expects($this->once())->method('getOrderId')->willReturn(self::ORDER_ID);
        $messageMock->expects($this->once())->method('getShipmentId')->willReturn(self::SHIPMENT_ID);

        $this->shipmentEmailMock->expects($this->never())->method('sendEmail');

        $this->sendOrderEmailQueue->execute($messageMock);
    }

    public function testExecuteWithToggleDisabled()
    {
        $messageMock = $this->getMockBuilder(SendOrderEmailMessageInterface::class)->getMock();

        $this->sendOrderEmailQueue->execute($messageMock);
    }
}
