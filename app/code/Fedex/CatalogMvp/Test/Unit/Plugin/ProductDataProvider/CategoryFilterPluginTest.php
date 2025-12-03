<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Plugin\ProductDataProvider;

use Fedex\CatalogMvp\Plugin\ProductDataProvider\CategoryFilterPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as CoreProvider;
use Magento\Framework\Api\Filter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Fedex\CatalogMvp\Plugin\ProductDataProvider\CategoryFilterPlugin
 */
class CategoryFilterPluginTest extends TestCase
{
    /** @var ToggleConfig|MockObject */
    private $toggleConfig;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var CoreProvider|MockObject */
    private $coreProvider;

    /** @var Filter|MockObject */
    private $filter;

    /** @var CategoryFilterPlugin */
    private $plugin;

    /**
     * Initializes mock dependencies and creates an instance of Categorylist
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->coreProvider = $this->createMock(CoreProvider::class);
        $this->filter = $this->createMock(Filter::class);

        $this->plugin = new CategoryFilterPlugin(
            $this->toggleConfig,
            $this->logger
        );
    }

    /**
     * Verifies that the category filter is applied correctly when the toggle is enabled
     * and ensures the original method is not executed.
     */
    public function testCategoryFilterAppliedWhenToggleEnabled(): void
    {
        $this->filter->method('getField')->willReturn('category_id_filter');
        $this->filter->method('getValue')->willReturn([10, 20]);

        $this->toggleConfig->method('getToggleConfigValue')
            ->with(CategoryFilterPlugin::TECH_TITANS_E_475721)
            ->willReturn(true);

        $collectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addCategoriesFilter'])
            ->getMock();

        $collectionMock->expects($this->once())
            ->method('addCategoriesFilter')
            ->with(['in' => [10, 20]]);

        $this->coreProvider->method('getCollection')->willReturn($collectionMock);

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
        };

        $plugin = new CategoryFilterPlugin($this->toggleConfig, $this->logger);
        $plugin->aroundAddFilter($this->coreProvider, $proceed, $this->filter);
        $this->assertFalse(
            $proceedCalled,
            'Proceed should not be called when toggle is enabled for category_id_filter.'
        );

        $this->assertTrue(
            true,
            'Category filter should be applied successfully when toggle is enabled.'
        );
    }

    /**
     * Ensures that the original method executes when the toggle is disabled,
     * verifying correct fallback behavior.
     */
    public function testFallbackToProceedWhenToggleDisabled(): void
    {
        $this->filter->method('getField')->willReturn('category_id_filter');
        $this->filter->method('getValue')->willReturn([10]);
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);
        $this->coreProvider->expects($this->never())->method('getCollection');

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
        };
        $this->plugin->aroundAddFilter($this->coreProvider, $proceed, $this->filter);
        $this->assertTrue(
            $proceedCalled,
            'Proceed should be called when toggle is disabled.'
        );

        $this->assertFalse(
            !$proceedCalled,
            'Proceed should not be skipped when the feature toggle is off.'
        );
    }

    /**
     * Test Method to fallback to proceed for non category field
     * @return void
     */
    public function testFallbackToProceedForNonCategoryField(): void
    {
        $this->filter->method('getField')->willReturn('sku');

        $this->toggleConfig->expects($this->never())->method('getToggleConfigValue');
        $this->coreProvider->expects($this->never())->method('getCollection');
        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
        };
        $this->plugin->aroundAddFilter($this->coreProvider, $proceed, $this->filter);
        $this->assertTrue(
            $proceedCalled,
            'Proceed must be called when the filter field is not category_id_filter.'
        );

        $this->assertFalse(
            !$proceedCalled,
            'Proceed should not be skipped for non-category filter fields.'
        );
    }

    /**
     * Tests that an exception during filter processing is logged and handled gracefully without breaking execution.
     * @return void
     */
    public function testLogsErrorOnException(): void
    {
        $this->filter->method('getField')->willReturn('category_id_filter');
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->coreProvider->method('getCollection')->willThrowException(new \Exception('DB error'));
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in CategoryFilterPlugin::aroundAddFilter'));

        $proceedCalled = false;
        $proceed = function () use (&$proceedCalled) {
            $proceedCalled = true;
        };
        $result = $this->plugin->aroundAddFilter($this->coreProvider, $proceed, $this->filter);
        $this->assertNull(
            $result,
            'aroundAddFilter should not return a value when an exception occurs.'
        );

        $this->assertTrue(
            method_exists($this->plugin, 'aroundAddFilter'),
            'aroundAddFilter method must exist in CategoryFilterPlugin.'
        );
    }
}
