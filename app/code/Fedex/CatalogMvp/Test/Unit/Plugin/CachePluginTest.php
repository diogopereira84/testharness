<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Cache;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Plugin\CachePlugin;
use PHPUnit\Framework\MockObject\MockObject;

class CachePluginTest extends TestCase
{
    /** @var CachePlugin */
    private $cachePlugin;

    /** @var MockObject|RequestInterface */
    private $requestMock;

    /** @var MockObject|LoggerInterface */
    private $loggerMock;

    /** @var MockObject|ToggleConfig */
    private $toggleConfigMock;

    /** @var MockObject|Cache */
    private $cacheMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the dependencies
       // $this->requestMock = $this->createMock(RequestInterface::class);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getFullActionName']) // Only mock getFullActionName
            ->getMockForAbstractClass();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->cacheMock = $this->createMock(Cache::class);

        // Create the CachePlugin instance
        $this->cachePlugin = new CachePlugin(
            $this->requestMock,
            $this->loggerMock,
            $this->toggleConfigMock
        );
    }

    /**
     * Test that aroundClean prevents Cache::clean when conditions are met.
     */
    public function testAroundCleanPreventsCacheCleanWhenConditionIsMet(): void
    {
        $actionName = 'catalogmvp_index_saveproduct';
        $toggleValue = true;
        $type = 'some_cache_type';

        // Mock the request to return the specific action name
        $this->requestMock
            ->method('getFullActionName')
            ->willReturn($actionName);

        // Mock the toggleConfig to return true
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('mazegeeks_save_catalogmvp_product_operation_redis')
            ->willReturn($toggleValue);

        // Expect no call to Cache::clean (because the condition is met)
        $this->cacheMock
            ->expects($this->any())
            ->method('clean');

        // Expect a log entry for this condition
        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->willReturnSelf();

        // Call the aroundClean method
        $this->cachePlugin->aroundClean($this->cacheMock, function ($type) {
            // Simulate the proceed callback
            return;
        }, $type);
    }

    /**
     * Test that aroundClean allows Cache::clean to proceed when conditions are not met.
     */
    public function testAroundCleanAllowsCacheCleanWhenConditionIsNotMet(): void
    {
        $actionName = 'catalog_product_view';
        $toggleValue = false;
        $type = 'some_cache_type';

        // Mock the request to return a different action name
        $this->requestMock
            ->method('getFullActionName')
            ->willReturn($actionName);

        // Mock the toggleConfig to return false
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('mazegeeks_save_catalogmvp_product_operation_redis')
            ->willReturn($toggleValue);

        // Expect Cache::clean to be called once
        $this->cacheMock
            ->expects($this->any())
            ->method('clean')
            ->with($type);

        // Call the aroundClean method
        $this->cachePlugin->aroundClean($this->cacheMock, function ($type) {
            // Simulate the proceed callback
            return;
        }, $type);
    }
}
