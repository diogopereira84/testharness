<?php

namespace Fedex\Shipment\Test\Unit\Block\Adminhtml;

use Fedex\Shipment\Block\Adminhtml\Shipment;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Block\Adminhtml\Shipment
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ShipmentTest extends TestCase
{
    /**
     * @var objectManagerHelper|MockObject
     */
    protected $objectManagerHelper;

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
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->shipment = $this->objectManagerHelper->getObject(Shipment::class);
    }

    /**
     * Test testGridHtml method.
     */
    public function testGridHtml()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Shipment\Block\Adminhtml\Shipment::class,
            '_getCreateUrl',
        );
        $testMethod1 = new \ReflectionMethod(
            \Fedex\Shipment\Block\Adminhtml\Shipment::class,
            '_getAddButtonOptions',
        );
        $testMethod2 = new \ReflectionMethod(
            \Fedex\Shipment\Block\Adminhtml\Shipment::class,
            '_prepareLayout',
        );
        $testMethod->invoke($this->shipment);
        $testMethod1->invoke($this->shipment);
        $testMethod2->invoke($this->shipment);
        $response = $this->shipment->getGridHtml();
        $this->assertEquals('', $response);
    }
}
