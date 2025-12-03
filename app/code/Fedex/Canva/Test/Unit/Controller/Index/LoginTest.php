<?php
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Controller\Index;

use Fedex\Canva\Controller\Index\Login;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\SSO\Model\Login as LoginModal;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class LoginTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    private const CANVA_LAST_PRODUCT_URL = 'canva_last_product_url';
    private const CANVA_ERROR_PROFILE_POPUP = 'canva_error_profile_popup';

    protected Login $loginControllerMock;
    protected Context|MockObject $contextMock;
    protected Session|MockObject $sessionMock;
    protected ResponseFactory|MockObject $responseFactoryMock;
    protected CookieManagerInterface|MockObject $cookieManagerMock;
    protected CookieMetadataFactory|MockObject $cookieMetadataFactoryMock;
    protected PublicCookieMetadata|MockObject $publicCookieMetadataMock;
    protected LoggerInterface|MockObject $loggerMock;
    protected LoginModal|MockObject $loginMock;
    protected ResultFactory $resultFactoryMock;
    protected Redirect $redirectMock;
    protected \Exception $exceptionMock;

    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->onlyMethods(['getResultFactory'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock = $this
            ->getMockBuilder(ResultFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->redirectMock = $this
            ->getMockBuilder(Redirect::class)
            ->onlyMethods(['setUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseFactoryMock = $this
            ->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieMetadataFactoryMock = $this
            ->getMockBuilder(CookieMetadataFactory::class)
            ->onlyMethods(['createPublicCookieMetadata'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->publicCookieMetadataMock = $this
            ->getMockBuilder(PublicCookieMetadata::class)
            ->onlyMethods(['setDuration', 'setPath', 'setHttpOnly'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerMock = $this
            ->getMockBuilder(CookieManagerInterface::class)
            ->onlyMethods(['setPublicCookie', 'getCookie'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sessionMock = $this
            ->getMockBuilder(Session::class)
            ->addMethods(['setProfileRetrieveError', 'setLoginError', 'getProfileRetrieveError', 'getLoginError'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this
            ->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loginMock = $this
            ->getMockBuilder(LoginModal::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->exceptionMock = $this
            ->getMockBuilder(\Exception::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->loginControllerMock = $this->objectManager->getObject(
            Login::class,
            [
                'context' => $this->contextMock,
                'responseFactory' => $this->responseFactoryMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'cookieManager' => $this->cookieManagerMock,
                'session' => $this->sessionMock,
                'logger' => $this->loggerMock,
                'login' => $this->loginMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteSuccess()
    {
        $this->resultFactoryMock->method('create')->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->redirectMock->method('setUrl')->with('/canva');
        $this->loginMock->method('isCustomerLoggedIn')->willReturn(true);
        $this->sessionMock->method('getProfileRetrieveError')->willReturn(false);
        $this->sessionMock->method('getLoginError')->willReturn(false);

        $this->assertInstanceOf(Redirect::class, $this->loginControllerMock->execute());
    }

    /**
     * @return void
     */
    public function testExecuteRedirectToProduct()
    {
        $this->resultFactoryMock->method('create')->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->redirectMock->method('setUrl')->withConsecutive(['/canva'], ['product.html'])->willReturnSelf();

        $this->loginMock->method('isCustomerLoggedIn')->willReturn(false);

        $this->sessionMock->method('getProfileRetrieveError')->willReturn(true);
        $this->sessionMock->method('getLoginError')->willReturn(true);

        $this->cookieMetadataFactoryMock->method('createPublicCookieMetadata')->willReturn($this->publicCookieMetadataMock);
        $this->publicCookieMetadataMock->method('setDuration')->with(60)->willReturnSelf();
        $this->publicCookieMetadataMock->method('setPath')->with('/');
        $this->publicCookieMetadataMock->method('setHttpOnly')->with(false);
        $cookieMeta = $this->publicCookieMetadataMock;
        $this->cookieManagerMock->method('setPublicCookie')->withConsecutive([self::CANVA_ERROR_PROFILE_POPUP, true, $cookieMeta]);

        $this->sessionMock->method('setProfileRetrieveError')->willReturn(false);
        $this->sessionMock->method('setLoginError')->willReturn(false);

        $this->cookieManagerMock->expects($this->once())
            ->method('getCookie')
            ->with(self::CANVA_LAST_PRODUCT_URL)
            ->willReturn('product.html');

        $this->assertInstanceOf(Redirect::class, $this->loginControllerMock->execute());
    }
    public function testExecuteWillThrowException()
    {
        $this->resultFactoryMock->method('create')->with(ResultFactory::TYPE_REDIRECT)->willReturn($this->redirectMock);
        $this->redirectMock->expects($this->once())->method('setUrl')->with('/canva')->willReturnSelf();
        $this->loggerMock->expects($this->once())->method('critical')->with('Fedex\Canva\Controller\Index\Login::execute:69 Error while reading cookie: Teste');
        $exceptionMessage = 'Teste';
        $this->loginMock->method('isCustomerLoggedIn')->willThrowException(new \Exception($exceptionMessage));

        $this->assertInstanceOf(Redirect::class, $this->loginControllerMock->execute());
    }
}
