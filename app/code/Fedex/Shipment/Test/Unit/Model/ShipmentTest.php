<?php

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\Shipment\Model\Shipment;

/**
 * Test class for Fedex\Shipment\Model\Shipment
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ShipmentTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var Shipment |MockObject */
    protected $shipment;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->shipment = $this->objectManagerHelper->getObject(
            Shipment::class
        );
    }

    /**
     * Test testConstruct
     */
    public function testConstruct()
    {
        $this->assertTrue(true);
    }
}
