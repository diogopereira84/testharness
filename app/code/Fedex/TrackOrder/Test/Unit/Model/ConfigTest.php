<?php
/**
 * @category    Fedex
 * @package     Fedex_TrackOrder
 */

declare(strict_types=1);

namespace Fedex\TrackOrder\Test\Unit\Model;

use Fedex\TrackOrder\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;

    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testGetMetaTitle()
    {
        $expectedValue = 'Order Tracking Meta Title';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_META_TITLE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getMetaTitle());
    }

    public function testGetMetaDescription()
    {
        $expectedValue = 'Order Tracking Meta Description';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_META_DESCRIPTION, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getMetaDescription());
    }

    public function testGetTrackOrderHeader()
    {
        $expectedValue = 'Track Your Order';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_TRACK_ORDER_HEADER, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getTrackOrderHeader());
    }

    public function testGetTrackOrderDescription()
    {
        $expectedValue = 'Track your order using the form below.';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_TRACK_ORDER_DESCRIPTION, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getTrackOrderDescription());
    }

    public function testGetTrackShipmentUrl()
    {
        $expectedValue = 'https://trackshipment.example.com';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_TRACK_SHIPMENT_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getTrackShipmentUrl());
    }

    public function testGetOrderDetailXapiUrl()
    {
        $expectedValue = 'https://orderdetailapi.example.com';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_ORDER_DETAIL_XAPI_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getOrderDetailXapiUrl());
    }

    public function testGetLegacyTrackOrderUrl()
    {
        $expectedValue = 'https://legacytrackorder.example.com';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_LEGACY_TRACK_ORDER_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->config->getLegacyTrackOrderUrl());
    }

    public function testGetProductDueDateMessage()
    {
        $expectedValue = 'Test Due Date Message 1P';
        $this->scopeConfig->method('getValue')
            ->with(Config::XML_PATH_PRODUCT_DUE_DATE_MESSAGE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expectedValue);

        $result = $this->scopeConfig->getValue(
            Config::XML_PATH_PRODUCT_DUE_DATE_MESSAGE, ScopeInterface::SCOPE_STORE, null
        );

        $this->assertEquals($expectedValue, $result);
    }
}
