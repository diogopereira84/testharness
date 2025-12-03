<?php

declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Plugin\Block\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Fedex\Catalog\Plugin\Block\Product\ProductList\ToolbarPlugin;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ToolbarPluginTest extends TestCase
{
    protected $catalogMvpMock;
    protected $subjectMock;
    protected $toolbarPlugin;

    protected function setUp(): void
    {
        // Mocking CatalogMvp
        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Mocking Toolbar with additional methods
        $this->subjectMock = $this->getMockBuilder(Toolbar::class)
            ->disableOriginalConstructor()
            ->addMethods(['isMvpCatalogEnabled']) // Use addMethods to mock non-existent methods
            ->getMock();

        // Create the instance of ToolbarPlugin
        $objectManagerHelper = new ObjectManager($this);
        $this->toolbarPlugin = $objectManagerHelper->getObject(
            ToolbarPlugin::class,
            [
                'catalogMvp' => $this->catalogMvpMock
            ]
        );
    }

    /**
     * Test the aroundGetAvailableOrders method with an empty category
     */
    public function testAroundGetAvailableOrdersWithEmptyCategory()
    {
        // Mock the behavior of the catalogMvp
        $this->catalogMvpMock->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn(null); // Simulating empty category

        // Mock the behavior of isMvpCatalogEnabled
        $this->subjectMock->expects($this->once())
            ->method('isMvpCatalogEnabled')
            ->willReturn(true);

        // Call aroundGetAvailableOrders and test
        $result = $this->toolbarPlugin->aroundGetAvailableOrders($this->subjectMock, function () {
            return [
                'price' => 'value',
                'product_updated_date' => 'some date' // Simulating 'product_updated_date' in the result
            ];
        });

        // Assertions
        $this->assertArrayNotHasKey('price', $result); // Price should be unset due to empty category
        $this->assertArrayNotHasKey('product_updated_date', $result); // Product updated date should be removed
    }

    /**
     * Test the aroundGetAvailableOrders method with MVP Catalog enabled
     */
    public function testAroundGetAvailableOrdersWithMvpCatalogEnabled()
    {
        // Mock the behavior of the catalogMvp
        $this->catalogMvpMock->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn('some category'); // Simulating non-empty category

        // Mock the behavior of isMvpCatalogEnabled
        $this->subjectMock->expects($this->once())
            ->method('isMvpCatalogEnabled')
            ->willReturn(true);

        // Call aroundGetAvailableOrders and test
        $result = $this->toolbarPlugin->aroundGetAvailableOrders($this->subjectMock, function () {
            return [
                'price' => 'value',
                'name' => 'value',
                'product_updated_date' => 'some date' // Simulating 'product_updated_date' in the result
            ];
        });

        // Assertions
        $this->assertArrayNotHasKey('price', $result); // Price should be removed
        $this->assertArrayNotHasKey('name', $result); // Name should be removed
        $this->assertArrayNotHasKey('product_updated_date', $result); // Product updated date should be removed
        $this->assertArrayHasKey('name_asc', $result); // New value added
        $this->assertArrayHasKey('name_desc', $result); // New value added
    }

    /**
     * Test the aroundGetAvailableOrders method with MVP Catalog disabled
     */
    public function testAroundGetAvailableOrdersWithMvpCatalogDisabled()
    {
        // Mock the behavior of the catalogMvp
        $this->catalogMvpMock->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn('some category'); // Simulating non-empty category

        // Mock the behavior of isMvpCatalogEnabled
        $this->subjectMock->expects($this->once())
            ->method('isMvpCatalogEnabled')
            ->willReturn(false);

        // Call aroundGetAvailableOrders and test
        $result = $this->toolbarPlugin->aroundGetAvailableOrders($this->subjectMock, function () {
            return [
                'price' => 'value',
                'name' => 'value',
                'product_updated_date' => 'some date' // Simulating 'product_updated_date' in the result
            ];
        });

        // Assertions
        $this->assertArrayHasKey('price', $result); // Price should still be available
        $this->assertArrayHasKey('name', $result); // Name should still be available
        $this->assertArrayNotHasKey('product_updated_date', $result); // Product updated date should be removed
        $this->assertArrayNotHasKey('name_asc', $result); // New value should not be added
        $this->assertArrayNotHasKey('name_desc', $result); // New value should not be added
    }

    /**
     * Test the afterIsEnabledViewSwitcher method
     */
    public function testAfterIsEnabledViewSwitcherWithEmptyCategory()
    {
        // Mock the behavior of catalogMvp
        $this->catalogMvpMock->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn(null); // Simulating empty category

        // Call afterIsEnabledViewSwitcher and test
        $result = $this->toolbarPlugin->afterIsEnabledViewSwitcher($this->subjectMock, true);

        // Assertions: it should return false when there is no current category
        $this->assertFalse($result);
    }

    /**
     * Test the afterIsEnabledViewSwitcher method when current category is set
     */
    public function testAfterIsEnabledViewSwitcherWithCategory()
    {
        // Mock the behavior of catalogMvp
        $this->catalogMvpMock->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn('some category'); // Simulating non-empty category

        // Call afterIsEnabledViewSwitcher and test
        $result = $this->toolbarPlugin->afterIsEnabledViewSwitcher($this->subjectMock, true);

        // Assertions: it should return true when there is a current category
        $this->assertTrue($result);
    }
}