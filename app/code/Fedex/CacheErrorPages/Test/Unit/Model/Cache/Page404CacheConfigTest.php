<?php

namespace Fedex\CacheErrorPages\Test\Unit\Model\Cache;

use Fedex\CacheErrorPages\Model\Cache\Page404CacheConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class Page404CacheConfigTest extends TestCase
{
    private $storeManager;
    private $scopeConfig;
    private $config;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Page404CacheConfig(
            $this->storeManager,
            $this->scopeConfig,
        );
    }

    public function testConstants()
    {
        $this->assertEquals('PAGE_404_CACHE_STORE_', Page404CacheConfig::PAGE_404_CACHE_STORE_KEY_PREFIX);
        $this->assertEquals('performance/cache_error_pages/enable_page_404', Page404CacheConfig::PAGE_404_CACHE_ENABLED_CONFIG_PATH);
        $this->assertEquals('performance/cache_error_pages/lifetime_page_404', Page404CacheConfig::PAGE_404_CACHE_MAX_AGE_CONFIG_PATH);
    }

    public function testIsCacheEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(Page404CacheConfig::PAGE_404_CACHE_ENABLED_CONFIG_PATH, ScopeInterface::SCOPE_STORES)
            ->willReturn(true);
        $this->assertTrue($this->config->isCacheEnabled());
    }

    public function testGetCacheLifetime()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Page404CacheConfig::PAGE_404_CACHE_MAX_AGE_CONFIG_PATH, ScopeInterface::SCOPE_STORES)
            ->willReturn('123');
        $this->assertEquals(123, $this->config->getCacheLifetime());
    }

    public function testGetCacheKey()
    {
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->getMock();
        $store->expects($this->once())->method('getId')->willReturn(5);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $this->assertEquals('PAGE_404_CACHE_STORE_5', $this->config->getCacheKey());
    }

    public function testGetCacheKeyThrowsNoSuchEntityException()
    {
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willThrowException(new NoSuchEntityException(__('error')));
        $this->expectException(NoSuchEntityException::class);
        $this->config->getCacheKey();
    }
}

