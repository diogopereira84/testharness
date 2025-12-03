<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Model\Config;

use Fedex\ProductCustomAtrribute\Model\Config\AbstractConfig;
use Fedex\ProductEngine\Model\Config\Backend;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{
    private const PREFIX_KEY                = 'product_engine/general';
    public const XPATH_PRODUCT_ENGINE_URL   = 'url';
    public const XPATH_CANVA_LINK   = 'default_canva_link';
    private Backend $backendMock;
    private ScopeConfigInterface $scopeConfigMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->backendMock = new Backend(
            $this->scopeConfigMock
        );
    }

    public function testGetProductEngineUrl(): void
    {
        $peUrl = 'https://wwwtest.fedex.com/templates/components/apps/easyprint/content/staticProducts';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(self::PREFIX_KEY.'/'.self::XPATH_PRODUCT_ENGINE_URL, ScopeInterface::SCOPE_STORE, null)->willReturn($peUrl);
        $this->assertEquals($peUrl, $this->backendMock->getProductEngineUrl());
    }
}
