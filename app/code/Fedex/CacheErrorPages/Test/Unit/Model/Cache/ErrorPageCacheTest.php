<?php

namespace Fedex\CacheErrorPages\Test\Unit\Model\Cache;

use Fedex\CacheErrorPages\Model\Cache\ErrorPageCache;
use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use PHPUnit\Framework\TestCase;

class ErrorPageCacheTest extends TestCase
{
    public function testConstants()
    {
        $this->assertEquals('error_page_cache', ErrorPageCache::TYPE_IDENTIFIER);
        $this->assertEquals('ERROR_PAGE_CACHE', ErrorPageCache::CACHE_TAG);
    }

    public function testConstruct()
    {
        $frontend = $this->createMock(\Magento\Framework\Cache\FrontendInterface::class);
        $pool = $this->createMock(FrontendPool::class);
        $pool->expects($this->once())
            ->method('get')
            ->with(ErrorPageCache::TYPE_IDENTIFIER)
            ->willReturn($frontend);

        $cache = new ErrorPageCache($pool);
        $this->assertInstanceOf(TagScope::class, $cache);
    }
}

