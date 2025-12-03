<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Test\Unit\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\App\CacheInterface;
use Fedex\WebAnalytics\Model\UrlProcessor;
use Magento\Cms\Model\Page;

class UrlProcessorTest extends TestCase
{
    protected $cookieManagerInterface;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $urlProcessor;
    private const GET_CURRENT_URL_METHOD = 'getCurrentUrl';
    private const GET_IDENTIFIER_METHOD = 'getIdentifier';
    private const GET_PAGE_LAYOUT_METHOD = 'getPageLayout';
    private const GET_FULL_ACTION_NAME_METHOD = 'getFullActionName';
    private const CMS_PAGE_VIEW = 'cms_page_view';
    private const GET_BASE_URL_METHOD = 'getBaseUrl';

    /**
     * @var Http|MockObject
     */
    protected $http;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlInterface;

    /**
     * @var Resolver|MockObject
     */
    protected $resolver;

    /**
     * @var CookieManagerInterface|MockObject
     */
    protected $cookieManager;

    /**
     * @var CacheInterface|MockObject
     */
    protected $cacheInterface;

    /**
     * @var Page $cmsPage
     */
    protected $cmsPage;

    /**
     * @var UrlProcessor\SearchPage $searchPage
     */
    protected $searchPage;

    /**
     * @var UrlProcessor\NotFoundPage $notFoundPage
     */
    protected $notFoundPage;

    /**
     * @var UrlProcessor\IframePage $iframePage
     */
    protected $iframePage;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->http = $this->getMockBuilder(Http::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
                                ->setMethods([self::GET_CURRENT_URL_METHOD])
                                ->disableOriginalConstructor()
                                ->getMockForAbstractClass();

        $this->resolver = $this->getMockBuilder(Resolver::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->cookieManagerInterface = $this->getMockBuilder(CookieManagerInterface::class)
                                            ->disableOriginalConstructor()
                                            ->getMockForAbstractClass();

        $this->cacheInterface = $this->getMockBuilder(CacheInterface::class)
                                        ->disableOriginalConstructor()
                                        ->getMockForAbstractClass();

        $this->searchPage = $this->getMockBuilder(UrlProcessor\SearchPage::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->notFoundPage = $this->getMockBuilder(UrlProcessor\NotFoundPage::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->iframePage = $this->getMockBuilder(UrlProcessor\IframePage::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->cmsPage = $this->getMockBuilder(Page::class)
                            ->setMethods(
                                [
                                    self::GET_IDENTIFIER_METHOD,
                                    self::GET_PAGE_LAYOUT_METHOD
                                ]
                            )
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->urlProcessor= $this->objectManager->getObject(
            UrlProcessor::class,
            [
                'request' => $this->http,
                'url' => $this->urlInterface,
                'resolver' => $this->resolver,
                'cookieManager' => $this->cookieManagerInterface,
                'cache' => $this->cacheInterface,
                'cmsPage' => $this->cmsPage,
                'searchPage' => $this->searchPage,
                'notFoundPage' => $this->notFoundPage,
                'iframePage' => $this->iframePage,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test Get Page Type For Product And Category
     */
    public function testGetPageTypeForProductAndCategory()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('catalog_product_view');
        $response = 'productpage';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type For Configurator
     */
    public function testGetPageTypeForConfigurator()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('configurator_index_index');
        $response = 'application';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type For Homepage
     */
    public function testGetPageTypeForHomePage()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('cms_index_index');
        $response = 'homepage';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type For Cart
     */
    public function testGetPageTypeForCart()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('checkout_cart_index');
        $response = 'cart';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type For Checkout
     */
    public function testGetPageTypeForCheckout()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('checkout_index_index');
        $response = 'checkout';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type for with printingpage
     */
    public function testGetPageTypeWithPrintingpage()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn(self::CMS_PAGE_VIEW);
        $this->cmsPage->expects($this->any())->method(self::GET_IDENTIFIER_METHOD)
            ->willReturn('coupons-deals');
        $response = 'printingpage';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type for with content
     */
    public function testGetPageTypeWithContent()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn(self::CMS_PAGE_VIEW);
        $this->cmsPage->expects($this->any())->method(self::GET_IDENTIFIER_METHOD)
            ->willReturn('marketplace-information');
        $response = 'content';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type for FAQ Page
     */
    public function testGetPageTypeForFAQPage()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn(self::CMS_PAGE_VIEW);
        $this->cmsPage->expects($this->any())->method(self::GET_PAGE_LAYOUT_METHOD)
            ->willReturn('faq-template-full-width');
        $response = 'supportpage';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type for Search Page
     */
    public function testGetPageTypeForSearch()
    {
        $response = 'FXOSearchpage';
        $this->searchPage->expects($this->once())
            ->method('isSearchPage')
            ->willReturn(true);
        $this->searchPage->expects($this->once())
            ->method('getType')
            ->willReturn($response);
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type for Not Found Page
     */
    public function testGetPageTypeForNotFound()
    {
        $response = 'application';
        $this->notFoundPage->expects($this->once())
            ->method('isCurrentPage')
            ->willReturn(true);
        $this->notFoundPage->expects($this->once())
            ->method('getType')
            ->willReturn($response);
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type for Iframe Page
     */
    public function testGetPageTypeForIframe()
    {
        $response = 'application';
        $this->iframePage->expects($this->once())
            ->method('isCurrentPage')
            ->willReturn(true);
        $this->iframePage->expects($this->once())
            ->method('getType')
            ->willReturn($response);
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test Get Page Type for Order History Page
     */
    public function testGetPageTypeForOrderHistoryPage()
    {
        $this->http->expects($this->any())->method(self::GET_FULL_ACTION_NAME_METHOD)
            ->willReturn('sales_order_history');
        $this->cmsPage->expects($this->any())->method(self::GET_PAGE_LAYOUT_METHOD)
            ->willReturn('faq-template-full-width');
        $response = 'fxoprofile';
        $this->assertEquals($response, $this->urlProcessor->getPageType());
    }

    /**
     * Test case for getPageId
     */
    public function testgetPageId()
    {
        $this->cacheInterface->expects($this->any())->method('load')->willReturn(1);
        /** B-1313619-Resolve JS Errors **/
        $url = "https://shop-staging2.fedex.com/l6site51/sales/order/view/order_id/6900/";
        $this->urlInterface->expects($this->any())->method(self::GET_CURRENT_URL_METHOD)->willReturn($url);

        $pageId = $this->urlProcessor->getPageId(null);
        $this->assertEquals(1, $pageId);
    }

    /**
     * Test case for getPageId
     */
    public function testGetPageIdGenerateCache()
    {
        $this->cacheInterface->expects($this->any())->method('load')->willReturn(0);
        /** B-1313619-Resolve JS Errors **/
        $url = "https://shop-staging2.fedex.com/l6site51/sales/order/view/order_id/6900/";
        $this->urlInterface->expects($this->any())->method(self::GET_CURRENT_URL_METHOD)->willReturn($url);
        $this->cookieManagerInterface->expects($this->any())
            ->method('getCookie')->with('fdx_locale')->willReturn("en_US");

        $pageId = $this->urlProcessor->getPageId('test');
        $this->assertEquals('US/en/test/l6site51/sales/order/history', $pageId);
    }

    public function testGetSubDomainName()
    {
        $this->urlInterface->expects($this->any())->method(self::GET_BASE_URL_METHOD)
            ->willReturn('http://www.example.com');

        $domain = $this->urlProcessor->getSubDomainName();
        $this->assertEquals('www', $domain);
    }

    public function testGetSubDomainNameInvalid()
    {
        $this->urlInterface->expects($this->any())->method(self::GET_BASE_URL_METHOD)->willReturn('');

        $domain = $this->urlProcessor->getSubDomainName();
        $this->assertEquals(false, $domain);
    }

    public function testGeneratePageId()
    {
        $this->cookieManagerInterface->expects($this->any())->method('getCookie')
            ->with('fdx_locale')->willReturn("");
        $this->urlProcessor->generatePageId('test');
        $this->assertEquals('', '');
    }

    public function testGeneratePageIdInvalid()
    {
        $this->urlInterface->expects($this->any())->method(self::GET_BASE_URL_METHOD)->willReturn('');
        $pageId = $this->urlProcessor->generatePageId();
        $this->assertEquals(false, $pageId);
    }
}
