<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\ShipmentFactory;

/**
 * Test class for Fedex\Shipment\Model\ShipmentFactory
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ShipmentFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var ShipmentFactory |MockObject */
    protected $shipmentFactory;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->shipmentFactory = $this->objectManagerHelper->getObject(
            ShipmentFactory::class
        );
    }

    /**
     * Test testConstruct
     */
    public function testCreate()
    {
        $this->assertEquals(null, $this->shipmentFactory->create());
    }
}
