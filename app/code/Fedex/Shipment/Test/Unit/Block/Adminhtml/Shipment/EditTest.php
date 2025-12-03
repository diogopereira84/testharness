<?php

namespace Fedex\Shipment\Test\Unit\Block\Adminhtml\Shipment;

use Fedex\Shipment\Block\Adminhtml\Shipment\Edit;
use Fedex\Shipment\Model\Shipment;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Block\Adminhtml\Shipment\Edit
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class EditTest extends TestCase
{
    /**
     * @var objectManagerHelper|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var Registry|MockObject
     */
    protected $registry;

    /**
     * @var Edit|MockObject
     */
    protected $edit;
    private MockObject|Shipment $shipment;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->edit = $this->objectManagerHelper->getObject(
            Edit::class,
            [
                'registry' => $this->registry
            ]
        );
    }

    /**
     * Test testGetHeaderText method.
     */
    public function testGetHeaderText()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Shipment\Block\Adminhtml\Shipment\Edit::class,
            '_getSaveAndContinueUrl',
        );
        $testMethod->invoke($this->edit);
        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shipmentData = ["id" => "2", "title" => "Test"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($shipmentData);
        $this->registry->expects($this->any())->method('registry')->with("shipment")->willReturn($varienObject);
        $this->assertNotNull($this->edit->getHeaderText());
    }

    /**
     * Test testGetHeaderTextWithoutId method.
     */
    public function testGetHeaderTextWithoutId()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Shipment\Block\Adminhtml\Shipment\Edit::class,
            '_prepareLayout',
        );
        $testMethod->invoke($this->edit);
        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shipmentData = ["id" => "", "title" => "Test"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($shipmentData);
        $this->registry->expects($this->any())->method('registry')->with("shipment")->willReturn($varienObject);
        $this->assertNotNull($this->edit->getHeaderText());
    }
}
