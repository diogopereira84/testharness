<?php

namespace Fedex\OKTA\Test\Unit\Controller\Rewrite\Adminhtml\Backend\Auth;

use Exception;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;
use Fedex\OKTA\Controller\Rewrite\Adminhtml\Backend\Auth\Login;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Backend\LoginHandler;
use Fedex\OKTA\Model\Oauth\OktaTokenInterface;
use Fedex\OKTA\Model\Oauth\PostbackValidatorInterface;
use Fedex\OKTA\Model\Backend\LoginHandlerFactory;
use Fedex\OKTA\Model\Oauth\UrlBuilder;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\UrlFactory;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\App\BackendAppList;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backend\Helper\Data;
use Magento\Framework\App\Request\Http\Proxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Laminas\Uri\Http;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class LoginTest extends TestCase
{
    protected $pageMock;
    protected $layoutMock;
    protected $viewMock;
    protected $uriMock;
    /**
     * @var (\Magento\Backend\App\BackendAppList & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $backendAppListMock;
    /**
     * @var (\Magento\Backend\Model\UrlFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $backendUrlFactoryMock;
    /**
     * @var (\Magento\Backend\App\Area\FrontNameResolver & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $frontNameResolverMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\Stdlib\CookieManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cookieManagerInterfaceMock;
    protected $cookieMetadataFactoryMock;
    private const METHOD_IS_ENABLED = 'isEnabled';
    private const METHOD_IS_CREATED = 'create';
    private const METHOD_IS_POST = 'isPost';
    private const METHOD_GET_PARAM = 'getParam';
    private const METHOD_GET_TOKEN = 'getToken';
    private const METHOD_LOGIN_BY_TOKEN = 'loginByToken';
    private const LOGIN_BY_TOKEN_PARAM = 'testTokenValue';
    private const METHOD_GET_STARTUP_PAGE_URL = 'getStartupPageUrl';
    private const METHOD_GET_UNSET_ELEMENT = 'unsetElement';
    private const METHOD_GET_LAYOUT = 'getLayout';
    private const SAMPLE_CODE = 'testValue';
    private const RETURN_URL = 'testURL';
    private const OKTA_SAMPLE_RESPONSE = '{"access_token" : "testTokenValue"}';
    private const CLIENT_ID = 'testClientId';
    private const DOMAIN = 'testDomain';
    private const NONCE = 'testNonce';
    private const REDIRECT_URL = 'testRedirectUrl';
    private const CONSTRUCTED_URL = 'testConstructedUrl';
    private const GET_CLIENT_ID_METHOD = 'getClientId';
    private const GET_DOMAIN_METHOD = 'getDomain';
    private const GET_NONCE_METHOD = 'getNonce';
    private const GET_REDIRECT_URL_METHOD = 'getRedirectUrl';
    private const SET_CLIENT_ID_METHOD = 'setClientId';
    private const SET_DOMAIN_METHOD = 'setDomain';
    private const SET_NONCE_METHOD = 'setNonce';
    private const SET_REDIRECT_URL_METHOD = 'setRedirectUrl';
    private const SET_SCOPE_METHOD = 'setScope';
    private const SET_STATE_METHOD = 'setState';
    private const SET_RESPONSE_TYPE_METHOD = 'setResponseType';
    private const SET_RESPONSE_MODE_METHOD = 'setResponseMode';

    /**
     * @var LoginHandlerFactory|MockObject
     */
    private $loginHandlerFactoryMock;

    /**
     * @var OktaHelper|MockObject
     */
    private $oktaHelperMock;

    /**
     * @var PostbackValidatorInterface|MockObject
     */
    private $postbackValidatorMock;

    /**
     * @var UrlBuilder|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlInterfaceMock;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactoryMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManagerMock;

    /**
     * @var Auth|MockObject
     */
    private $authMock;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    private $redirectMock;

    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var object
     */
    private $loginMock;

    /**
     * @var Proxy|MockObject
     */
    private $requestMock;

    /**
     * @var LoginHandler|MockObject
     */
    private $loginHandlerMock;

    /**
     * @var \Magento\Backend\Model\UrlInterface|MockObject
     */
    private $backendUrlMock;

    /**
     * @var OktaTokenInterface|MockObject
     */
    private $oktaTokenMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManagerInterface;

    /**
     * @return void
     */
    public function extracted(): void
    {
        $this->oktaHelperMock->method(self::METHOD_IS_ENABLED)->willReturn(true);
        $this->requestMock->method(self::METHOD_IS_POST)->willReturn(true);
        $this->requestMock->method(self::METHOD_GET_PARAM)
            ->with(PostbackValidatorInterface::REQUEST_KEY_CODE)->willReturn(self::SAMPLE_CODE);
        $this->requestMock
            ->method('getParams')
            ->will($this->returnValueMap([1, null, 'email']));
        $this->oktaTokenMock->method(self::METHOD_GET_TOKEN)
            ->with(self::SAMPLE_CODE)->willReturn(self::OKTA_SAMPLE_RESPONSE);
        $this->loginHandlerFactoryMock->method(self::METHOD_IS_CREATED)->willReturn($this->loginHandlerMock);
        $this->loginHandlerMock->method(self::METHOD_LOGIN_BY_TOKEN)
            ->with(self::LOGIN_BY_TOKEN_PARAM)->willReturn($this->any());
    }

    /**
     * To setup the mock objects.
     */
    protected function setUp(): void
    {
        $this->loginHandlerFactoryMock = $this->createMock(LoginHandlerFactory::class);
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->postbackValidatorMock = $this->getMockForAbstractClass(PostbackValidatorInterface::class);
        $this->urlBuilderMock = $this->createMock(UrlBuilder::class);
        $this->urlInterfaceMock = $this->getMockForAbstractClass(UrlInterface::class);
        $this->oktaTokenMock = $this->getMockForAbstractClass(OktaTokenInterface::class);
        $this->resultPageFactoryMock = $this->createMock(PageFactory::class);
        $this->pageMock = $this->createMock(Page::class);
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->authMock = $this->createMock(Auth::class);
        $this->resultRedirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->requestMock = $this->createMock(Proxy::class);
        $this->loginHandlerMock = $this->createMock(LoginHandler::class);
        $this->backendUrlMock = $this->getMockForAbstractClass(\Magento\Backend\Model\UrlInterface::class);
        $this->layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->viewMock = $this->getMockForAbstractClass(ViewInterface::class);
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->uriMock = $this->createMock(Http::class);
        $this->backendAppListMock = $this->createMock(BackendAppList::class);
        $this->backendUrlFactoryMock = $this->createMock(UrlFactory::class);
        $this->frontNameResolverMock = $this->createMock(FrontNameResolver::class);

        $this->oktaHelperMock->method(self::GET_CLIENT_ID_METHOD)->willReturn(self::CLIENT_ID);
        $this->oktaHelperMock->method(self::GET_DOMAIN_METHOD)->willReturn(self::DOMAIN);
        $this->oktaHelperMock->method(self::GET_NONCE_METHOD)->willReturn(self::NONCE);
        $this->oktaHelperMock->method(self::GET_REDIRECT_URL_METHOD)->willReturn(self::REDIRECT_URL);
        $this->urlBuilderMock->method(self::SET_CLIENT_ID_METHOD)->willReturnSelf();
        $this->urlBuilderMock->method(self::SET_DOMAIN_METHOD)->willReturnSelf();
        $this->urlBuilderMock->method(self::SET_NONCE_METHOD)->willReturnSelf();
        $this->urlBuilderMock->method(self::SET_REDIRECT_URL_METHOD)->willReturnSelf();
        $this->urlBuilderMock->method(self::SET_SCOPE_METHOD)->willReturnSelf();
        $this->urlBuilderMock->method(self::SET_STATE_METHOD)->willReturnSelf();
        $this->urlBuilderMock->method(self::SET_RESPONSE_TYPE_METHOD)->willReturnSelf();
        $this->urlBuilderMock->method(self::SET_RESPONSE_MODE_METHOD)->willReturnSelf();

        $this->objectManager = new ObjectManager($this);

        $this->contextMock->method('getAuth')->willReturn($this->authMock);
        $this->contextMock->method('getMessageManager')->willReturn($this->messageManagerMock);
        $this->contextMock->method('getResultRedirectFactory')->willReturn($this->resultRedirectFactoryMock);
        $this->contextMock->method('getHelper')->willReturn($this->helperMock);
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getBackendUrl')->willReturn($this->backendUrlMock);
        $this->contextMock->method('getResultFactory')->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->once())->method('getView')->willReturn($this->viewMock);

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
                    ->getMockForAbstractClass();

        $this->cookieManagerInterfaceMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPublicCookie','deleteCookie'])
            ->getMockForAbstractClass();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPublicCookieMetadata', 'setDurationOneYear', 'setPath', 'setHttpOnly'])
            ->getMock();

        $this->loginMock = $this->objectManager->getObject(
            Login::class,
            [
                'loginHandlerFactory' => $this->loginHandlerFactoryMock,
                'oktaHelper' => $this->oktaHelperMock,
                'postbackValidator' => $this->postbackValidatorMock,
                'urlBuilder' => $this->urlBuilderMock,
                'urlInterface' => $this->urlInterfaceMock,
                'oktaToken' => $this->oktaTokenMock,
                'context' => $this->contextMock,
                'resultPageFactory' => $this->resultPageFactoryMock,
                'logger' => $this->loggerMock,
                'backendAppList' => $this->backendAppListMock,
                'backendUrlFactory' => $this->backendUrlFactoryMock,
                'frontNameResolver' => $this->frontNameResolverMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'cookieManager' => $this->cookieManagerInterfaceMock
            ]
        );
    }

    /**
     * Test to run : OKTA module is Disabled
     */
    public function testExecuteIfOKTAModuleDisabled()
    {
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->requestMock->method('getUri')->willReturn($this->uriMock);
        $this->oktaHelperMock->method(self::METHOD_IS_ENABLED)->willReturn(false);
        $this->authMock->method('isLoggedIn')->willReturn(false);
        $this->requestMock->method('getUri')->willReturn('*');
        $this->resultRedirectFactoryMock->method(self::METHOD_IS_CREATED)->willReturn($this->redirectMock);
        $this->redirectMock->method('setPath')->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->loginMock->execute());
    }

    /**
     * Test to run : OKTA module is disabled and request is a POST request
     */
    public function testExecuteIfOKTAModuleEnabledAndIsPostEnabled()
    {
        $this->extracted();
        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactoryMock->method('setDurationOneYear')->willReturn(23444);
        $this->backendUrlMock->method('getStartupPageUrl')->willReturn(self::RETURN_URL);
        $this->resultRedirectFactoryMock->method(self::METHOD_IS_CREATED)->willReturn($this->redirectMock);
        $this->resultFactoryMock->method(self::METHOD_IS_CREATED)
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->redirectMock->method('setPath')->with(self::RETURN_URL)->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->loginMock->execute());
    }

    /**
     * Test to run : OKTA module is disabled and request is not a POST request.
     */
    public function testExecuteIfOKTAModuleEnabledAndIsPostDisabled()
    {
        $this->oktaHelperMock->method(self::METHOD_IS_ENABLED)->willReturn(true);
        $this->requestMock->method(self::METHOD_IS_POST)->willReturn(false);
        $this->resultFactoryMock->method(self::METHOD_IS_CREATED)
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->urlBuilderMock->method('build')->willReturn(self::CONSTRUCTED_URL);
        $this->redirectMock->method('setUrl')->with(self::CONSTRUCTED_URL)->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->loginMock->execute());
    }

    public function testExecuteIfOKTAModuleEnabledAndIsPostEnabledThrowLocalizedException()
    {
        $this->extracted();
        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactoryMock->method('setDurationOneYear')->willReturn(23444);
        $this->backendUrlMock->method(self::METHOD_GET_STARTUP_PAGE_URL)->willReturn(self::RETURN_URL);
        $this->backendUrlMock->expects($this->once())
            ->method(self::METHOD_GET_STARTUP_PAGE_URL)
            ->will($this->throwException(new LocalizedException(new Phrase('Exception Mock'))));
        $this->layoutMock->method(self::METHOD_GET_UNSET_ELEMENT)->willReturn('');
        $this->resultPageFactoryMock->expects($this->once())
            ->method(self::METHOD_IS_CREATED)->willReturn($this->pageMock);
        $this->resultFactoryMock->method(self::METHOD_IS_CREATED)
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->pageMock->method(self::METHOD_GET_LAYOUT)->willReturn($this->layoutMock);

        $this->assertInstanceOf(Page::class, $this->loginMock->execute());
    }

    public function testExecuteIfOKTAModuleEnabledAndIsPostEnabledThrowException()
    {
        $this->extracted();
        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')->willReturnSelf();
        $this->cookieMetadataFactoryMock->method('setDurationOneYear')->willReturn(23444);
        $this->backendUrlMock->method(self::METHOD_GET_STARTUP_PAGE_URL)->willReturn(self::RETURN_URL);
        $this->resultFactoryMock->method(self::METHOD_IS_CREATED)
            ->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->backendUrlMock->expects($this->once())
            ->method(self::METHOD_GET_STARTUP_PAGE_URL)->will($this->throwException(new Exception()));
        $this->layoutMock->method(self::METHOD_GET_UNSET_ELEMENT)->willReturn('');
        $this->resultPageFactoryMock->expects($this->once())
            ->method(self::METHOD_IS_CREATED)->willReturn($this->pageMock);
        $this->pageMock->method(self::METHOD_GET_LAYOUT)->willReturn($this->layoutMock);

        $this->assertInstanceOf(Page::class, $this->loginMock->execute());
    }
}
