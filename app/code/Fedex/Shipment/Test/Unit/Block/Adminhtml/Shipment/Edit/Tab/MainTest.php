<?php

namespace Fedex\Shipment\Test\Unit\Block\Adminhtml\Shipment\Edit\Tab;

use Fedex\Shipment\Block\Adminhtml\Shipment\Edit\Tab\Main;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Fedex\Shipment\Block\Adminhtml\Shipment\Edit\Tab\Main
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class MainTest extends TestCase
{
    /**
     * @var objectManagerHelper|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var objectMMainanagerHelper|MockObject
     */
    protected $main;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->main = $this->objectManagerHelper->getObject(Main::class);
    }

    /**
     * Test testGetTabLabel method.
     */
    public function testGetTabLabel()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Shipment\Block\Adminhtml\Shipment\Edit\Tab\Main::class,
            '_isAllowedAction'
        );
        $testMethod->invoke($this->main, 'id');
        $this->assertEquals("Item Information", $this->main->getTabLabel());
    }

    /**
     * Test testGetTabTitle method.
     */
    public function testGetTabTitle()
    {
        $this->assertEquals("Item Information", $this->main->getTabTitle());
    }

    /**
     * Test testCanShowTab method.
     */
    public function testCanShowTab()
    {
        $this->assertEquals(true, $this->main->canShowTab());
    }

    /**
     * Test testIsHidden method.
     */
    public function testIsHidden()
    {
        $this->assertEquals(false, $this->main->isHidden());
    }

    /**
     * Test testGetTargetOptionArray method.
     */
    public function testGetTargetOptionArray()
    {
        $this->assertNotNull($this->main->getTargetOptionArray());
    }
}
