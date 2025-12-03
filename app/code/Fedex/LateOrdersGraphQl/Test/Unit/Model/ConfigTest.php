<?php
// Unit test for Fedex\LateOrdersGraphQl\Model\Config
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\LateOrdersGraphQl\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigTest extends TestCase
{
    public function testGetLateOrderQueryWindowHoursWithValue()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_WINDOW_HOURS, ScopeInterface::SCOPE_STORE)
            ->willReturn('2');
        $config = new Config($scopeConfig);
        $this->assertEquals('2', $config->getLateOrderQueryWindowHours());
    }

    public function testGetLateOrderQueryWindowHoursWithNull()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_WINDOW_HOURS, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $config = new Config($scopeConfig);
        $this->assertNull($config->getLateOrderQueryWindowHours());
    }

    public function testgetLateOrderQueryMaxPaginationWithValue()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_MAX_PAGINATION, ScopeInterface::SCOPE_STORE)
            ->willReturn('25');
        $config = new Config($scopeConfig);
        $this->assertEquals(25, $config->getLateOrderQueryMaxPagination());
    }

    public function testgetLateOrderQueryMaxPaginationWithNull()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_MAX_PAGINATION, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $config = new Config($scopeConfig);
        $this->assertNull($config->getLateOrderQueryMaxPagination());
    }

    public function testgetLateOrderQueryDefaultPaginationWithValue()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DEFAULT_PAGINATION, ScopeInterface::SCOPE_STORE)
            ->willReturn('25');
        $config = new Config($scopeConfig);
        $this->assertEquals(25, $config->getLateOrderQueryDefaultPagination());
    }

    public function testgetLateOrderQueryDefaultPaginationWithNull()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DEFAULT_PAGINATION, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $config = new Config($scopeConfig);
        $this->assertNull($config->getLateOrderQueryDefaultPagination());
    }
}
