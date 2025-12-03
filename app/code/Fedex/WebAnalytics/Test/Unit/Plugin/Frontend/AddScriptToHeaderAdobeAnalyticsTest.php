<?php
/**
 * @category Fedex
 * @package  Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Plugin\Frontend;

use Fedex\WebAnalytics\Model\CmsPage\PageTypeResolverInterface;
use Magento\Framework\DataObject;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\WebAnalytics\Plugin\Frontend\AddScriptToHeaderAdobeAnalytics;
use Magento\Framework\View\Page\Config as PageConfig;
use PHPUnit\Framework\TestCase;
use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;
use Fedex\WebAnalytics\Model\UrlProcessor;
use Magento\Cms\Model\Page;

class AddScriptToHeaderAdobeAnalyticsTest extends TestCase
{
    protected $configInterface;
    protected $urlProcessor;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_objectManager;
    private const IS_ACTIVE_METHOD = 'isActive';
    private const GET_SUBDOMAIN_PREFIX_METHOD = 'getSubDomainPrefix';
    private const GET_SCRIPT_CODE_METHOD = 'getScriptCode';
    private const GET_PAGE_ID_METHOD = 'getPageId';
    private const GET_PAGE_TYPE_METHOD = 'getPageType';
    private const GET_PAGE_TYPES_METHOD = 'getPageTypes';

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

    /**
     * @var AddScriptToHeaderAdobeAnalytics
     */
    protected $_addScriptToHeaderAdobeAnalytics;

    /**
     * @var Domain|MockObject
     */
    protected $domain;

    /**
     * @var PageId|MockObject
     */
    protected $pageId;

    /**
     * @var PageTypeResolverInterface|MockObject
     */
    protected $pageTypeResolver;

    /**
     * @var PageConfig|MockObject
     */
    private PageConfig|MockObject $pageConfig;

    /**
     * @var SecureHtmlRenderer|MockObject
     */
    private SecureHtmlRenderer|MockObject $secureHtmlRendererMock;

    /**
     * Test setUp
     */
    public function setUp() : void
    {
        $this->configInterface = $this->getMockBuilder(GDLConfigInterface::class)
            ->setMethods([self::IS_ACTIVE_METHOD,self::GET_SUBDOMAIN_PREFIX_METHOD,self::GET_SCRIPT_CODE_METHOD, self::GET_PAGE_TYPES_METHOD])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->urlProcessor = $this->getMockBuilder(UrlProcessor::class)
            ->setMethods([self::GET_PAGE_ID_METHOD, self::GET_PAGE_TYPE_METHOD])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTypeResolver = $this->getMockBuilder(PageTypeResolverInterface::class)
            ->setMethods(['resolve'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->secureHtmlRendererMock = $this
            ->getMockBuilder(SecureHtmlRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_addScriptToHeaderAdobeAnalytics = $this->_objectManager->getObject(
            AddScriptToHeaderAdobeAnalytics::class,
            [
                'configInterface' => $this->configInterface,
                'urlProcessor' => $this->urlProcessor,
                'pageTypeResolver' => $this->pageTypeResolver,
                'secureHtmlRenderer' => $this->secureHtmlRendererMock,
            ]
        );
        $this->pageConfig = $this->createMock(PageConfig::class);
        $this->configInterface->expects($this->any())->method(self::GET_SUBDOMAIN_PREFIX_METHOD)
            ->willReturn($this->domain);
        $this->urlProcessor->expects($this->any())->method(self::GET_PAGE_ID_METHOD)->with($this->domain)
            ->willReturn($this->pageId);
        $this->urlProcessor->expects($this->any())->method(self::GET_PAGE_TYPE_METHOD)
            ->with()->willReturn('ssd');
    }

    /**
     * Test After Get Includes
     */
    public function testAfterGetIncludes()
    {
        $this->configInterface->expects($this->any())->method(self::IS_ACTIVE_METHOD)->willReturn(true);
        $this->configInterface->expects($this->any())->method(self::GET_SCRIPT_CODE_METHOD)
            ->willReturn(self::RESPONSE);
        $this->secureHtmlRendererMock->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);
                    if ($attributes->getData('src')) {
                        $attributes->setData('src', str_replace("'", '', $attributes->getData('src')));
                    }
                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );
        $this->assertEquals(self::RESPONSE_WITH_NONCE, $this->_addScriptToHeaderAdobeAnalytics->afterGetIncludes($this->pageConfig, ''));
    }

    /**
     * Test After Get Includes With Toggle On
     */
    public function testAfterGetIncludesWithToggle()
    {
        $this->configInterface->expects($this->any())->method(self::IS_ACTIVE_METHOD)->willReturn(true);
        $this->configInterface->expects($this->any())->method(self::GET_SCRIPT_CODE_METHOD)
            ->willReturn(self::RESPONSE);
        $this->pageTypeResolver->expects($this->any())->method('resolve')->willReturn('productpage');
        $this->secureHtmlRendererMock->method('renderTag')
            ->willReturnCallback(
                function (string $tag, array $attributes, string $content): string {
                    $attributes = new DataObject($attributes);
                    if ($attributes->getData('src')) {
                        $attributes->setData('src', str_replace("'", '', $attributes->getData('src')));
                    }
                    return "<$tag {$attributes->serialize()}>$content</$tag>";
                }
            );
        $this->assertEquals(
            self::RESPONSE_WITH_NONCE,
            $this->_addScriptToHeaderAdobeAnalytics->afterGetIncludes($this->pageConfig, '')
        );
    }

    /**
     * Test After Get Includes Without Config
     */
    public function testAfterGetIncludesWithoutConfig()
    {
        $this->configInterface->expects($this->any())->method(self::IS_ACTIVE_METHOD)->willReturn(true);
        $this->configInterface->expects($this->any())->method(self::GET_SCRIPT_CODE_METHOD)
            ->willReturn('');
        $this->assertNotNull($this->_addScriptToHeaderAdobeAnalytics->afterGetIncludes($this->pageConfig, ''));
    }

    /**
     * Test After Get Includes Without Config
     */
    public function testAfterGetIncludesWithWrongScript()
    {
        $wrongScript = "<scriptWrong> window.FDXPAGEID = 'US/en/office/home';
    window.FDX = window.FDX || {};
    window.FDX.GDL = window.FDX.GDL || [];
    window.FDX.GDL.push([ 'event:publish', [ 'page', 'pageinfo', {pageId: 'US/en/office/home', pageType: ''} ] ]);
    </scriptWrong>
    <scriptWrong src='https://www.fedex.com/gdl/gdl-fedex.js' async></scriptWrong>";
        $this->configInterface->expects($this->any())->method(self::IS_ACTIVE_METHOD)->willReturn(true);
        $this->configInterface->expects($this->any())->method(self::GET_SCRIPT_CODE_METHOD)
            ->willReturn($wrongScript);
        $this->assertNotNull($this->_addScriptToHeaderAdobeAnalytics->afterGetIncludes($this->pageConfig, ''));
    }
}
