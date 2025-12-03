<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\CatalogMvp\Plugin\ProductPluginCache;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\MockObject\MockObject;

class ProductPluginCacheTest extends TestCase
{
    /** @var ProductPluginCache */
    private $plugin;

    /** @var MockObject|RequestInterface */
    private $requestMock;

    /** @var MockObject|LoggerInterface */
    private $loggerMock;

    /** @var MockObject|ToggleConfig */
    private $toggleConfigMock;

    /** @var Product */
    private $productMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getFullActionName']) // Only mock getFullActionName
            ->getMockForAbstractClass();

        // Create the plugin instance
        $this->plugin = new ProductPluginCache(
            $this->requestMock,
            $this->loggerMock,
            $this->toggleConfigMock
        );

        // Mock the Product model
        $this->productMock = $this->createMock(Product::class);
    }

    /**
     * Test the afterGetIdentities method when the condition is met
     */
    public function testAfterGetIdentitiesWithCondition(): void
    {
        $actionName = 'catalogmvp_index_saveproduct';
        $toggleValue = true;

        // Mock the request to return a specific action name
        $this->requestMock
            ->method('getFullActionName')
            ->willReturn($actionName);

        // Mock the toggle configuration to return true
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('mazegeeks_save_catalogmvp_product_operation_redis')
            ->willReturn($toggleValue);

        // Expect the logger to be called
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->willReturnSelf();

        // Call the plugin method and assert the result
        $result = $this->plugin->afterGetIdentities($this->productMock, ['cache_tag_1', 'cache_tag_2']);
        $this->assertEmpty($result, 'Expected an empty array when the condition is met.');
    }

    /**
     * Test the afterGetIdentities method when the condition is not met
     */
    public function testAfterGetIdentitiesWithoutCondition(): void
    {
        $actionName = 'catalog_product_view';
        $toggleValue = true;

        // Mock the request to return a different action name
        $this->requestMock
            ->method('getFullActionName')
            ->willReturn($actionName);

        // Mock the toggle configuration to return true
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('mazegeeks_save_catalogmvp_product_operation_redis')
            ->willReturn($toggleValue);

        // Call the plugin method and assert the result is unchanged
        $result = $this->plugin->afterGetIdentities($this->productMock, ['cache_tag_1', 'cache_tag_2']);
        $this->assertEquals(['cache_tag_1', 'cache_tag_2'], $result, 'Expected the original result to be returned.');
    }

    /**
     * Test the afterGetCacheTags method when the condition is met
     */
    public function testAfterGetCacheTagsWithCondition(): void
    {
        $actionName = 'catalogmvp_index_saveproduct';
        $toggleValue = true;

        // Mock the request to return a specific action name
        $this->requestMock
            ->method('getFullActionName')
            ->willReturn($actionName);

        // Mock the toggle configuration to return true
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('mazegeeks_save_catalogmvp_product_operation_redis')
            ->willReturn($toggleValue);

        // Expect the logger to be called
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->willReturnSelf();

        // Call the plugin method and assert the result
        $result = $this->plugin->afterGetCacheTags($this->productMock, ['cache_tag_1', 'cache_tag_2']);
        $this->assertEmpty($result, 'Expected an empty array when the condition is met.');
    }

    /**
     * Test the afterGetCacheTags method when the condition is not met
     */
    public function testAfterGetCacheTagsWithoutCondition(): void
    {
        $actionName = 'catalog_product_view';
        $toggleValue = true;

        // Mock the request to return a different action name
        $this->requestMock
            ->method('getFullActionName')
            ->willReturn($actionName);

        // Mock the toggle configuration to return true
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('mazegeeks_save_catalogmvp_product_operation_redis')
            ->willReturn($toggleValue);

        // Call the plugin method and assert the result is unchanged
        $result = $this->plugin->afterGetCacheTags($this->productMock, ['cache_tag_1', 'cache_tag_2']);
        $this->assertEquals(['cache_tag_1', 'cache_tag_2'], $result, 'Expected the original result to be returned.');
    }
}
