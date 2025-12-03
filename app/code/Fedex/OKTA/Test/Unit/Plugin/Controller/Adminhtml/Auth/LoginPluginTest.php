<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceAdmin
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Plugin\Controller\Adminhtml\Auth;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OKTA\Model\Backend\LoginHandler;
use Fedex\OKTA\Model\Backend\LoginHandlerFactory;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Oauth\OktaTokenInterface;
use Fedex\OKTA\Model\Oauth\PostbackValidatorInterface;
use Fedex\OKTA\Model\Oauth\UrlBuilderInterface;
use Fedex\OKTA\Plugin\Controller\Adminhtml\Auth\LoginPlugin;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Backend\Controller\Adminhtml\Auth\Login;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\Http\Proxy;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class LoginPluginTest extends TestCase
{
    protected $layoutMock;
    protected $pageMock;
    protected $redirectMock;
    protected $loginControllerMock;
    protected $loginPlugin;
    private const LOGIN_BY_TOKEN_PARAM = '1d15c7df6711af3fc35884ce693378379096fe26';
    private const SAMPLE_CODE = 'sample-code';
    private const RETURN_URL = 'https://this.domain.com';
    private const OKTA_SAMPLE_RESPONSE = '{"access_token" : "1d15c7df6711af3fc35884ce693378379096fe26"}';
    private const CONSTRUCTED_URL = 'https://this.domain.com/home/';

    private ToggleConfig|MockObject $toggleConfigMock;
    private OktaHelper|MockObject $oktaHelperMock;
    private CookieMetadataFactory|MockObject $cookieMetadataFactoryMock;
    private UrlBuilderInterface|MockObject $urlBuilderMock;
    private OktaTokenInterface|MockObject $oktaTokenMock;
    private PageFactory|MockObject $pageFactoryMock;
    private LoginHandlerFactory|MockObject $loginHandlerFactoryMock;
    private LoginHandler|MockObject $loginHandlerMock;
    private RedirectFactory|MockObject $redirectFactoryMock;
    private Proxy|MockObject $requestMock;
    private ResultFactory|MockObject $resultFactoryMock;
    private BackendUrlInterface|MockObject $backendUrlMock;

    protected function setUp(): void
    {
        $this->layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->pageMock = $this->createMock(Page::class);
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->loginControllerMock = $this->createMock(Login::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $cookieManagerMock = $this->createMock(CookieManagerInterface::class);
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['setDurationOneYear', 'setPath', 'setHttpOnly'])
            ->onlyMethods(['createPublicCookieMetadata'])
            ->getMock();
        $this->urlBuilderMock = $this->createMock(UrlBuilderInterface::class);
        $postbackValidatorMock = $this->createMock(PostbackValidatorInterface::class);
        $this->oktaTokenMock = $this->createMock(OktaTokenInterface::class);
        $viewMock = $this->createMock(ViewInterface::class);
        $this->pageFactoryMock = $this->createMock(PageFactory::class);
        $this->loginHandlerFactoryMock = $this->createMock(LoginHandlerFactory::class);
        $this->loginHandlerMock = $this->createMock(LoginHandler::class);
        $this->redirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->requestMock = $this->createMock(Proxy::class);
        $authMock = $this->createMock(Auth::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->backendUrlMock = $this->createMock(BackendUrlInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $messageManagerMock = $this->createMock(MessageManagerInterface::class);

        $this->oktaHelperMock->method('getClientId')->willReturn('96da49381769303a6515a8785c7f19c383db376a');
        $this->oktaHelperMock->method('getDomain')->willReturn('https://okta.domain.com');
        $this->oktaHelperMock->method('getNonce')->willReturn('49afa7eb90ee7b404b57e1a5794642bd6de0bd7e');
        $this->oktaHelperMock->method('getRedirectUrl')->willReturn('oauth2/code/test');
        $this->urlBuilderMock->method('setClientId')->willReturnSelf();
        $this->urlBuilderMock->method('setDomain')->willReturnSelf();
        $this->urlBuilderMock->method('setNonce')->willReturnSelf();
        $this->urlBuilderMock->method('setRedirectUrl')->willReturnSelf();
        $this->urlBuilderMock->method('setScope')->willReturnSelf();
        $this->urlBuilderMock->method('setState')->willReturnSelf();
        $this->urlBuilderMock->method('setResponseType')->willReturnSelf();
        $this->urlBuilderMock->method('setResponseMode')->willReturnSelf();

        $this->loginPlugin = new LoginPlugin(
            $this->toggleConfigMock,
            $this->oktaHelperMock,
            $cookieManagerMock,
            $this->cookieMetadataFactoryMock,
            $this->urlBuilderMock,
            $postbackValidatorMock,
            $this->oktaTokenMock,
            $viewMock,
            $this->pageFactoryMock,
            $this->loginHandlerFactoryMock,
            $this->redirectFactoryMock,
            $this->requestMock,
            $authMock,
            $this->resultFactoryMock,
            $this->backendUrlMock,
            $loggerMock,
            $messageManagerMock
        );
    }

    public function common(): void
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(true);
        $this->oktaHelperMock->method('isEnabled')->willReturn(true);
        $this->requestMock->method('isPost')->willReturn(true);
        $this->requestMock->method('getParam')
            ->with(PostbackValidatorInterface::REQUEST_KEY_CODE)->willReturn(self::SAMPLE_CODE);
        $this->requestMock->method('getParams')->will($this->returnValueMap([1, null, 'email']));
        $this->oktaTokenMock->method('getToken')->with(self::SAMPLE_CODE)
            ->willReturn(self::OKTA_SAMPLE_RESPONSE);
        $this->loginHandlerFactoryMock->method('create')->willReturn($this->loginHandlerMock);
        $this->loginHandlerMock->method('loginByToken')
            ->with(self::LOGIN_BY_TOKEN_PARAM)->willReturn($this->any());
    }

    public function testAroundExecuteIfOKTAModuleDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(true);
        $this->oktaHelperMock->method('isEnabled')->willReturn(false);
        $proceed = function () {
            return $this->loginControllerMock->execute();
        };
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->loginPlugin->aroundExecute($this->loginControllerMock, $proceed);
        $this->assertEquals($this->loginControllerMock->execute(), $result);
    }

    public function testAroundExecuteToggleOff()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(false);
        $proceed = function () {
            return $this->loginControllerMock->execute();
        };
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->loginPlugin->aroundExecute($this->loginControllerMock, $proceed);
        $this->assertEquals($this->loginControllerMock->execute(), $result);
    }

    public function testAroundExecuteIfOKTAModuleEnabledAndIsPostEnabled()
    {
        $this->common();
        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactoryMock->method('setDurationOneYear')->willReturn(23444);
        $this->backendUrlMock->method('getStartupPageUrl')->willReturn(self::RETURN_URL);
        $this->redirectFactoryMock->method('create')->willReturn($this->redirectMock);
        $this->resultFactoryMock->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->redirectMock->method('setPath')->with(self::RETURN_URL)->willReturnSelf();
        $proceed = function () {
            return $this->loginControllerMock->execute();
        };
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->loginPlugin->aroundExecute($this->loginControllerMock, $proceed);
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testAroundExecuteIfOKTAModuleEnabledAndIsPostDisabled()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(true);
        $this->oktaHelperMock->method('isEnabled')->willReturn(true);
        $this->requestMock->method('isPost')->willReturn(false);
        $this->resultFactoryMock->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->urlBuilderMock->method('build')->willReturn(self::CONSTRUCTED_URL);
        $this->redirectMock->method('setUrl')->with(self::CONSTRUCTED_URL)->willReturnSelf();
        $proceed = function () {
            return $this->loginControllerMock->execute();
        };
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->loginPlugin->aroundExecute($this->loginControllerMock, $proceed);
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testAroundExecuteIfOKTAModuleEnabledAndIsPostEnabledThrowLocalizedException()
    {
        $this->common();
        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactoryMock->method('setDurationOneYear')->willReturn(23444);
        $this->backendUrlMock->method('getStartupPageUrl')->willReturn(self::RETURN_URL);
        $this->backendUrlMock->expects($this->once())
            ->method('getStartupPageUrl')
            ->will($this->throwException(new LocalizedException(new Phrase('Exception Mock'))));
        $this->layoutMock->method('unsetElement')->willReturn('');
        $this->pageFactoryMock->expects($this->once())
            ->method('create')->willReturn($this->pageMock);
        $this->resultFactoryMock->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->pageMock->method('getLayout')->willReturn($this->layoutMock);
        $proceed = function () {
            return $this->loginControllerMock->execute();
        };
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->loginPlugin->aroundExecute($this->loginControllerMock, $proceed);
        $this->assertInstanceOf(Page::class, $result);
    }

    public function testAroundExecuteIfOKTAModuleEnabledAndIsPostEnabledThrowException()
    {
        $this->common();
        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactoryMock->method('setDurationOneYear')->willReturn(23444);
        $this->backendUrlMock->method('getStartupPageUrl')->willReturn(self::RETURN_URL);
        $this->resultFactoryMock->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->backendUrlMock->expects($this->once())
            ->method('getStartupPageUrl')->will($this->throwException(new Exception()));
        $this->layoutMock->method('unsetElement')->willReturn('');
        $this->pageFactoryMock->expects($this->once())
            ->method('create')->willReturn($this->pageMock);
        $this->pageMock->method('getLayout')->willReturn($this->layoutMock);
        $proceed = function () {
            return $this->loginControllerMock->execute();
        };
        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->loginPlugin->aroundExecute($this->loginControllerMock, $proceed);
        $this->assertInstanceOf(Page::class, $result);
    }

}
