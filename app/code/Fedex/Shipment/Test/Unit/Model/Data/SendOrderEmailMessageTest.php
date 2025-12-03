<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Shipment\Test\Unit\Model\Data;

use Fedex\Shipment\Model\Data\SendOrderEmailMessage;
use PHPUnit\Framework\TestCase;

class SendOrderEmailMessageTest extends TestCase
{
    public function testGetSetShipmentStatus()
    {
        $message = new SendOrderEmailMessage();
        $shipmentStatus = 'shipped';
        $this->assertNull($message->getShipmentStatus());
        $message->setShipmentStatus($shipmentStatus);
        $this->assertEquals($shipmentStatus, $message->getShipmentStatus());
    }

    public function testGetSetOrderId()
    {
        $message = new SendOrderEmailMessage();
        $orderId = 123;
        $this->assertNull($message->getOrderId());
        $message->setOrderId($orderId);
        $this->assertEquals($orderId, $message->getOrderId());
    }

    public function testGetSetShipmentId()
    {
        $message = new SendOrderEmailMessage();
        $shipmentId = 456;
        $this->assertNull($message->getShipmentId());
        $message->setShipmentId($shipmentId);
        $this->assertEquals($shipmentId, $message->getShipmentId());
    }
}
