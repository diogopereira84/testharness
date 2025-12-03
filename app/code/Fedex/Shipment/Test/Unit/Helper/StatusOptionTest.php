<?php

namespace Fedex\Shipment\Test\Unit\Helper;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\Shipment;
use Fedex\Shipment\Model\ShipmentFactory;
use Fedex\Shipment\Helper\StatusOption;

/**
 * Test class for Fedex\Shipment\Helper\StatusOption
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class StatusOptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StatusOption|MockObject
     */
    protected $stausOption;
 
    /**
     * @var ObjectManagerHelper|MockObject
     */
    protected $objectManagerHelper;
 
    /**
     * @var ShipmentFactory|MockObject
     */
 
    protected $shipmentStatusFactory;
 
    /**
     * @var Shipment|MockObject
     */
    protected $shipment;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->shipment = $this->createMock(Shipment::class);
        $this->shipmentStatusFactory =
        $this->createPartialMock(ShipmentFactory::class, ['create']);
        $this->shipmentStatusFactory->expects($this->any())->method('create')->willReturn($this->shipment);
    
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->stausOption = $this->objectManagerHelper->getObject(
            StatusOption::class,
            [
                'shipmentStatusFactory' => $this->shipmentStatusFactory
            ]
        );
    }

    /**
     * Test testShipmentStatus method.
     */
    public function testShipmentStatus()
    {
        $datavalue = ["value"=>"new", "label"=>"New"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($datavalue);
        $this->shipment->expects($this->once())->method('getCollection')->willReturn([$varienObject]);
        $this->assertEquals('New', $this->stausOption->shipmentStatus("new"));
    }

    /**
     * Test testShipmentStatusEmpty method.
     */
    public function testShipmentStatusEmpty()
    {
        $this->shipment->expects($this->once())->method('getCollection')->willReturnSelf();
        $this->assertNull($this->stausOption->shipmentStatus("new"));
    }
}
