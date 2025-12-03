<?php

namespace Fedex\CacheErrorPages\Test\Unit\Plugin;

use Fedex\CacheErrorPages\Plugin\CacheResultPagePlugin;
use Fedex\CacheErrorPages\Model\Cache\ErrorPageCache;
use Fedex\CacheErrorPages\Model\Cache\Page404CacheConfig;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CacheResultPagePluginTest extends TestCase
{
    private $storeManager;
    private $scopeConfig;
    private $cache;
    private $logger;
    private $page404CacheConfig;
    private $state;
    private $plugin;
    private $subject;
    private $httpResponse;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->page404CacheConfig = $this->createMock(Page404CacheConfig::class);
        $this->state = $this->createMock(State::class);
        $this->plugin = new CacheResultPagePlugin(
            $this->storeManager,
            $this->scopeConfig,
            $this->cache,
            $this->logger,
            $this->page404CacheConfig,
            $this->state
        );
        $this->subject = $this->createMock(ResultPage::class);
        $this->httpResponse = $this->createMock(HttpResponse::class);
    }

    public function testAfterRenderResultReturnsResultIfNotFrontend()
    {
        $this->state->method('getAreaCode')->willReturn('adminhtml');
        $result = 'result';
        $actual = $this->plugin->afterRenderResult($this->subject, $result, $this->httpResponse);
        $this->assertEquals($result, $actual);
    }

    public function testAfterRenderResultReturnsResultIfNot404()
    {
        $this->state->method('getAreaCode')->willReturn('frontend');
        $this->httpResponse->method('getHttpResponseCode')->willReturn(200);
        $result = 'result';
        $actual = $this->plugin->afterRenderResult($this->subject, $result, $this->httpResponse);
        $this->assertEquals($result, $actual);
    }

    public function testAfterRenderResultReturnsResultIfCacheDisabled()
    {
        $this->state->method('getAreaCode')->willReturn('frontend');
        $this->httpResponse->method('getHttpResponseCode')->willReturn(404);
        $this->page404CacheConfig->method('isCacheEnabled')->willReturn(false);
        $result = 'result';
        $actual = $this->plugin->afterRenderResult($this->subject, $result, $this->httpResponse);
        $this->assertEquals($result, $actual);
    }

    public function testAfterRenderResultSavesCacheIfEnabledAnd404()
    {
        $this->state->method('getAreaCode')->willReturn('frontend');
        $this->httpResponse->method('getHttpResponseCode')->willReturn(404);
        $this->page404CacheConfig->method('isCacheEnabled')->willReturn(true);
        $this->httpResponse->method('getBody')->willReturn('html');
        $this->page404CacheConfig->method('getCacheKey')->willReturn('key');
        $this->page404CacheConfig->method('getCacheLifetime')->willReturn(123);
        $this->cache->expects($this->once())->method('save')->with(
            'html',
            'key',
            [ErrorPageCache::CACHE_TAG],
            123
        );
        $result = 'result';
        $actual = $this->plugin->afterRenderResult($this->subject, $result, $this->httpResponse);
        $this->assertEquals($result, $actual);
    }

    public function testAfterRenderResultHandlesLocalizedException()
    {
        $this->state->method('getAreaCode')->willThrowException(new LocalizedException(__('error')));
        $result = 'result';
        $actual = $this->plugin->afterRenderResult($this->subject, $result, $this->httpResponse);
        $this->assertEquals($result, $actual);
    }
}
