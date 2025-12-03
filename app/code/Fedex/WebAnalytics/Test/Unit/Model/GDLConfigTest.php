<?php
/**
 * @category Fedex
 * @package  Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model;

use Fedex\WebAnalytics\Model\CmsPage\PageTypeResolverInterface;
use Fedex\WebAnalytics\Model\UrlProcessor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\WebAnalytics\Model\GDLConfig;
use Magento\Framework\DataObject;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class GDLConfigTest extends TestCase
{
    private const GET_CURRENT_URL_METHOD = 'getValue';
    private const IS_SET_FLAG_METHOD = 'isSetFlag';
    private const GET_PAGE_ID_METHOD = 'getPageId';
    private const GET_PAGE_TYPE_METHOD = 'getPageType';
    private const RESOLVE = 'resolve';

    public const RESPONSE = "<script> window.FDXPAGEID = 'US/en/office/home';
    window.FDX = window.FDX || {};
    window.FDX.GDL = window.FDX.GDL || [];
    window.FDX.GDL.push([ 'event:publish', [ 'page', 'pageinfo', {pageId: 'US/en/office/home', pageType: ''} ] ]);
    </script>
    <script src='//www.fedex.com/gdl/gdl-fedex.js' async></script>";

    public const RESPONSE_WITH_NONCE = "<script type=\"text/javascript\"> window.FDXPAGEID = 'US/en/office/home';
    window.FDX = window.FDX || {};
    window.FDX.GDL = window.FDX.GDL || [];
    window.FDX.GDL.push([ 'event:publish', [ 'page', 'pageinfo', {pageId: 'US/en/office/home', pageType: ''} ] ]);
    </script><script type=\"text/javascript\" async=\"1\" src=\"https://www.fedex.com/gdl/gdl-fedex.js\"> </script>";

    public function testIsActive(): void
    {
        $scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_CURRENT_URL_METHOD, self::IS_SET_FLAG_METHOD]
        );
        $scopeConfigMock->expects($this->once())->method(self::IS_SET_FLAG_METHOD)
            ->with(GDLConfig::XML_PATH_FEDEX_GDL_ACTIVE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $secureHtmlRendered = $this->createMock(SecureHtmlRenderer::class);
        $urlProcessor = $this->createMock(UrlProcessor::class);
        $pageTypeResolverInterface = $this->createMock(PageTypeResolverInterface::class);

        $config = new GDLConfig($scopeConfigMock, $secureHtmlRendered, $urlProcessor, $pageTypeResolverInterface);
        $this->assertEquals(true, $config->isActive());
    }

    public function testGetScriptCode(): void
    {
        $scriptCode = '<script type="text/javascript">
                    console.log(1);
                </script>';
        $scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_CURRENT_URL_METHOD, self::IS_SET_FLAG_METHOD]
        );
        $scopeConfigMock->expects($this->once())->method(self::GET_CURRENT_URL_METHOD)
            ->with(GDLConfig::XML_PATH_FEDEX_GDL_SCRIPT_CODE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($scriptCode);

        $secureHtmlRendered = $this->createMock(SecureHtmlRenderer::class);
        $urlProcessor = $this->createMock(UrlProcessor::class);
        $pageTypeResolverInterface = $this->createMock(PageTypeResolverInterface::class);

        $config = new GDLConfig($scopeConfigMock, $secureHtmlRendered, $urlProcessor, $pageTypeResolverInterface);
        $this->assertEquals($scriptCode, $config->getScriptCode());
    }

    public function testGetSubDomainPrefix(): void
    {
        $domain = 'domain.com';
        $scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_CURRENT_URL_METHOD, self::IS_SET_FLAG_METHOD]
        );
        $scopeConfigMock->expects($this->once())->method(self::GET_CURRENT_URL_METHOD)
            ->with(GDLConfig::XML_PATH_FEDEX_GDL_SUBDOMAIN_PREFIX, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($domain);

        $secureHtmlRendered = $this->createMock(SecureHtmlRenderer::class);
        $urlProcessor = $this->createMock(UrlProcessor::class);
        $pageTypeResolverInterface = $this->createMock(PageTypeResolverInterface::class);

        $config = new GDLConfig($scopeConfigMock, $secureHtmlRendered, $urlProcessor, $pageTypeResolverInterface);
        $this->assertEquals($domain, $config->getSubDomainPrefix());
    }

    public function testGetPageTypes(): void
    {
        $PageType = '{id: "some"}';
        $scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_CURRENT_URL_METHOD, self::IS_SET_FLAG_METHOD]
        );
        $scopeConfigMock->expects($this->once())
            ->method(self::GET_CURRENT_URL_METHOD)
            ->with(GDLConfig::XML_PATH_FEDEX_GDL_PAGE_TYPES, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($PageType);

        $secureHtmlRendered = $this->createMock(SecureHtmlRenderer::class);
        $urlProcessor = $this->createMock(UrlProcessor::class);
        $pageTypeResolverInterface = $this->createMock(PageTypeResolverInterface::class);

        $config = new GDLConfig($scopeConfigMock, $secureHtmlRendered, $urlProcessor, $pageTypeResolverInterface);
        $this->assertEquals($PageType, $config->getPageTypes());
    }

    /**
     * Test After Get Includes With Toggle On
     */
    public function testGetScriptFullyRendered()
    {
        $scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            [self::GET_CURRENT_URL_METHOD, self::IS_SET_FLAG_METHOD]
        );
        $scopeConfigMock->expects($this->atMost(2))->method(self::IS_SET_FLAG_METHOD)
            ->with(GDLConfig::XML_PATH_FEDEX_GDL_ACTIVE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);
        $scopeConfigMock->expects($this->atMost(4))->method(self::GET_CURRENT_URL_METHOD)
            ->withConsecutive(
                [GDLConfig::XML_PATH_FEDEX_GDL_SCRIPT_CODE, ScopeInterface::SCOPE_STORE, null],
                [GDLConfig::XML_PATH_FEDEX_GDL_SCRIPT_CODE, ScopeInterface::SCOPE_STORE, null],
                [GDLConfig::XML_PATH_FEDEX_GDL_SUBDOMAIN_PREFIX, ScopeInterface::SCOPE_STORE, null],
                [GDLConfig::XML_PATH_FEDEX_GDL_PAGE_TYPES, ScopeInterface::SCOPE_STORE, null],
            )
            ->willReturnOnConsecutiveCalls(self::RESPONSE, self::RESPONSE, 'https://www.fedex.com', 'productpage');

        $secureHtmlRendered = $this->createMock(SecureHtmlRenderer::class);
        $secureHtmlRendered->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);
                    if ($attributes->getData('src')) {
                        $attributes->setData('src', str_replace("'", '', $attributes->getData('src')));
                    }
                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );

        $urlProcessor = $this->createPartialMock(
            UrlProcessor::class,
            [self::GET_PAGE_ID_METHOD, self::GET_PAGE_TYPE_METHOD]
        );

        $pageTypeResolverInterface = $this->createPartialMock(
            PageTypeResolverInterface::class,
            [self::RESOLVE]
        );
        $pageTypeResolverInterface->expects($this->any())->method('resolve')->willReturn('productpage');

        $config = new GDLConfig($scopeConfigMock, $secureHtmlRendered, $urlProcessor, $pageTypeResolverInterface);
        $this->assertEquals(self::RESPONSE_WITH_NONCE, $config->getScriptFullyRendered());
    }
}
