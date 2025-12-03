<?php
namespace Fedex\CacheErrorPages\Test\Unit\Plugin;

use Fedex\CacheErrorPages\Plugin\CacheNoRoutePagePlugin;
use Fedex\CacheErrorPages\Model\Cache\Page404CacheConfig;
use Magento\Cms\Controller\Noroute\Index as NoRouteIndex;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CacheNoRoutePagePluginTest extends TestCase
{
    private $cache;
    private $page404CacheConfig;
    private $logger;
    private $state;
    private $plugin;
    private $subject;
    private $request;
    private $response;
    private $toggleConfig;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->page404CacheConfig = $this->createMock(Page404CacheConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->state = $this->createMock(State::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->plugin = new CacheNoRoutePagePlugin(
            $this->cache,
            $this->page404CacheConfig,
            $this->logger,
            $this->state,
            $this->toggleConfig
        );
        $this->subject = $this->createMock(NoRouteIndex::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['setBody', 'setHttpResponseCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testAroundDispatchReturnsProceedIfNotFrontend()
    {
        $this->state->method('getAreaCode')->willReturn('adminhtml');
        $proceed = fn($request) => 'proceed_result';
        $result = $this->plugin->aroundDispatch($this->subject, $proceed, $this->request);
        $this->assertEquals('proceed_result', $result);
    }

    public function testAroundDispatchReturnsProceedIfCacheDisabled()
    {
        $this->state->method('getAreaCode')->willReturn('frontend');
        $this->page404CacheConfig->method('isCacheEnabled')->willReturn(false);
        $proceed = fn($request) => 'proceed_result';
        $result = $this->plugin->aroundDispatch($this->subject, $proceed, $this->request);
        $this->assertEquals('proceed_result', $result);
    }

    public function testAroundDispatchReturnsProceedIfNoCache()
    {
        $this->state->method('getAreaCode')->willReturn('frontend');
        $this->page404CacheConfig->method('isCacheEnabled')->willReturn(true);
        $this->page404CacheConfig->method('getCacheKey')->willReturn('key');
        $this->cache->method('load')->willReturn(false);
        $proceed = fn($request) => 'proceed_result';
        $result = $this->plugin->aroundDispatch($this->subject, $proceed, $this->request);
        $this->assertEquals('proceed_result', $result);
    }

    public function testAroundDispatchReturnsCachedResponse()
    {
        $this->state->method('getAreaCode')->willReturn('frontend');
        $this->page404CacheConfig->method('isCacheEnabled')->willReturn(true);
        $this->page404CacheConfig->method('getCacheKey')->willReturn('key');
        $this->cache->method('load')->willReturn('cached_html');
        $this->subject->method('getResponse')->willReturn($this->response);
        $this->response->expects($this->once())->method('setBody')->with('cached_html')->willReturnSelf();
        $this->response->expects($this->once())->method('setHttpResponseCode')->with(404)->willReturnSelf();
        $proceed = function ($request) {
            $this->fail('Proceed should not be called when cache is hit');
        };
        $result = $this->plugin->aroundDispatch($this->subject, $proceed, $this->request);
        $this->assertSame($this->response, $result);
    }

    public function testAroundDispatchHandlesLocalizedException()
    {
        $this->state->method('getAreaCode')->willThrowException(new LocalizedException(__('error')));
        $proceed = fn($request) => 'proceed_result';
        $this->page404CacheConfig->method('isCacheEnabled')->willReturn(false);
        $result = $this->plugin->aroundDispatch($this->subject, $proceed, $this->request);
        $this->assertEquals('proceed_result', $result);
    }
}
