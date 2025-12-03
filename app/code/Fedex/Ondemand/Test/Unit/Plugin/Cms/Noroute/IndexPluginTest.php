<?php

declare(strict_types=1);

namespace Fedex\Ondemand\Test\Unit\Plugin\Cms\Noroute;

use Fedex\Ondemand\Plugin\Cms\Noroute\IndexPlugin;
use Magento\Cms\Controller\Noroute\Index;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\Login\Helper\Login;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Psr\Log\LoggerInterface;

class IndexPluginTest extends TestCase
{
    /**
     * @var IndexPlugin
     */
    private $indexPlugin;

    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlInterface;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfig;

    /**
     * @var Ondemand|\PHPUnit\Framework\MockObject\MockObject
     */
    private $onDemandHelper;

    /**
     * @var AuthHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authHelper;
    /**
     * @var Login|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loginHelper;
    /**
     * @var Http|\PHPUnit\Framework\MockObject\MockObject
     */
    private $http;
    /**
     * @var CookieManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cookieManager;
    /**
     * @var CookieMetadataFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cookieMetadataFactory;
    /**
     * @var PublicCookieMetadata|\PHPUnit\Framework\MockObject\MockObject
     */
    private $publicCookieMetadata;

    /**
     * @var Index|\PHPUnit\Framework\MockObject\MockObject
     */
    private $indexController;

    /**
     * @var \Magento\Framework\App\Response\Http|\PHPUnit\Framework\MockObject\MockObject
     */
    private $response;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */

    private $logger;

    protected function setUp(): void
    {
        $this->urlInterface = $this->createMock(UrlInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->onDemandHelper = $this->createMock(Ondemand::class);
        $this->authHelper = $this->createMock(AuthHelper::class);
        $this->loginHelper = $this->createMock(Login::class);
        $this->http = $this->createMock(Http::class);
        $this->cookieManager = $this->createMock(CookieManagerInterface::class);
        $this->cookieMetadataFactory = $this->createMock(CookieMetadataFactory::class);
        $this->publicCookieMetadata = $this->createMock(PublicCookieMetadata::class);
        $this->indexController = $this->createMock(Index::class);
        $this->response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->logger = $this->createMock(LoggerInterface::class);


        $this->indexController->method('getResponse')->willReturn($this->response);

        $this->indexPlugin = new IndexPlugin(
            $this->urlInterface,
            $this->toggleConfig,
            $this->onDemandHelper,
            $this->authHelper,
            $this->loginHelper,
            $this->http,
            $this->cookieManager,
            $this->cookieMetadataFactory,
            $this->logger
        );
    }

    /**
     * Test case where toggle is enabled and URL contains 'selfreg/landing', so redirect occurs
     * @return void
     */
    public function testAroundExecuteRedirectsWhenToggleIsEnabledAndUrlMatches()
    {
        $currentUrl = 'http://example.com/selfreg/landing/somevalue';
        $expectedRedirectUrl = 'http://example.com/restructure/company/redirect/url/somevalue';

        $this->urlInterface->method('getCurrentUrl')->willReturn($currentUrl);
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->urlInterface->method('getUrl')->willReturn($expectedRedirectUrl);
        $companyData = ['company_url_extension'=>'test'];
        $this->onDemandHelper->method('getCompanyFromUrlExtension')->willReturn($companyData);

        $this->response->expects($this->once())->method('setRedirect')->with($expectedRedirectUrl);

        $result = $this->indexPlugin->aroundExecute($this->indexController, function () {
            return $this->createMock(ResultInterface::class);
        });

        $this->assertNull($result);
    }

    /**
     * Test case where user is not logged in, so redirect to login page
     * @return void
     */
    public function testAroundExecuteRedirectsNotLoggedIn()
    {
        $currentUrl = 'http://example.com/ondemand/testsite/uploadtoquote/index/view/somevalue';
        $expectedRedirectUrl = 'http://example.com/restructure/company/redirect/url/somevalue';
        $this->authHelper->method('isLoggedIn')->willReturn(false);
        $this->urlInterface->method('getCurrentUrl')->willReturn($currentUrl);
        $this->cookieMetadataFactory->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->method('setPath')->willReturnSelf();
        $this->publicCookieMetadata->method('setDuration')->willReturnSelf();
        $this->publicCookieMetadata->method('setHttpOnly')->willReturnSelf();
        $this->cookieManager->method('setPublicCookie')
            ->willReturnSelf();
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->urlInterface->method('getUrl')->willReturn($expectedRedirectUrl);
        $companyData = ['company_url_extension'=>'test'];
        $this->onDemandHelper->method('getCompanyFromUrlExtension')->willReturn($companyData);

        $this->response->expects($this->once())->method('setRedirect')->with($expectedRedirectUrl);

        $result = $this->indexPlugin->aroundExecute($this->indexController, function () {
            return $this->createMock(ResultInterface::class);
        });

        $this->assertNull($result);
    }

    /**
     * Test case where company data found, so redirect to login page
     * @return void
     */
    public function testAroundExecuteRedirectsNotLoggdInSkip()
    {
        $currentUrl = 'http://example.com/ondemand/testsite/uploadtoquote/index/view/somevalue';
        $expectedRedirectUrl = 'http://example.com/restructure/company/redirect/url/somevalue';
        $this->authHelper->method('isLoggedIn')->willReturn(false);
        $this->urlInterface->method('getCurrentUrl')->willReturn($currentUrl);
        $this->cookieMetadataFactory->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->method('setPath')->willReturnSelf();
        $this->publicCookieMetadata->method('setDuration')->willReturnSelf();
        $this->publicCookieMetadata->method('setHttpOnly')->willReturnSelf();
        $this->cookieManager->method('setPublicCookie')
            ->willReturnSelf();
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->urlInterface->method('getUrl')->willReturn($expectedRedirectUrl);
        $companyData = ['company_url_extension'=>'test'];
        $this->onDemandHelper->method('getCompanyFromUrlExtension')->willReturn('');
        $result = $this->indexPlugin->aroundExecute($this->indexController, function () {
            return $this->createMock(ResultInterface::class);
        });
    }

    /**
     * Test case where toggle is disabled, so no redirect
     * @return void
     */
    public function testAroundExecuteDoesNotRedirectWhenToggleIsDisabled()
    {
        $currentUrl = 'http://example.com/selfreg/landing/somevalue';

        $this->urlInterface->method('getCurrentUrl')->willReturn($currentUrl);
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $result = $this->indexPlugin->aroundExecute($this->indexController, function () {
            return $this->createMock(ResultInterface::class);
        });

        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Test case where URL does not contain 'selfreg/landing', so no redirect
     * @return void
     */
    public function testAroundExecuteDoesNotRedirectWhenUrlDoesNotContainLanding()
    {
        $currentUrl = 'http://example.com/otherpage';
        $this->urlInterface->method('getCurrentUrl')->willReturn($currentUrl);
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);

        $result = $this->indexPlugin->aroundExecute($this->indexController, function () {
            return $this->createMock(ResultInterface::class);
        });

        $this->assertInstanceOf(ResultInterface::class, $result);
    }
}
