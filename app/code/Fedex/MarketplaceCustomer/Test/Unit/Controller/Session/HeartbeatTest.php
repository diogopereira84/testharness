<?php

namespace Fedex\MarketplaceCustomer\Test\Unit\Controller\Session;

use Fedex\MarketplaceCustomer\Controller\Session\Heartbeat;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface as Logger;
use Magento\Customer\Model\Session as CustomerSession;
use Mirakl\Core\Model\Shop as MiraklShop;
use Magento\Framework\App\Response\Http;
use Mirakl\Core\Model\ResourceModel\ShopFactory as MiraklShopResourceFactory;
use Mirakl\Core\Model\ShopFactory as MiraklShopFactory;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Mirakl\Core\Model\ResourceModel\Shop;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SSO\Model\Config;
use Fedex\Canva\Model\CanvaCredentials;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class HeartbeatTest extends TestCase
{
    protected $miraklShopResourceFactoryMock;
    protected $publicCookieMetadataMock;
    protected Heartbeat $heartbeat;
    protected Context|MockObject $contextMock;
    protected HttpRequest|MockObject $requestMock;
    protected HttpResponseInterface|MockObject $responseMock;
    protected Json|MockObject $jsonMock;
    protected Logger|MockObject $loggerMock;
    protected CustomerSession|MockObject $customerSessionMock;
    protected PageFactory|MockObject $resultPageFactoryMock;
    protected MiraklShopResourceFactory|MockObject $miraklShopResourceFactory;
    protected MiraklShopFactory|MockObject $miraklShopFactoryMock;
    protected Http|MockObject $httpMock;
    protected CookieManagerInterface|MockObject $cookieManagerMock;
    protected CookieMetadataFactory|MockObject $cookieMetadataFactoryMock;
    protected SSOHelper|MockObject $ssoHelperMock;
    protected Config|MockObject $ssoConfigMock;
    protected CanvaCredentials|MockObject $canvaCredentialsMock;
    protected ToggleConfig|MockObject $toggleConfigMock;
    protected ManagerInterface|MockObject $eventManagerMock;


    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->setMethods([
                'logout',
                'setLastCustomerId',
                'getId',
                'unsFclFdxLogin',
                'getCustomerId',
                'isLoggedIn'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->createMock(HttpRequest::class);
        $this->responseMock = $this->createMock(HttpResponseInterface::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->miraklShopFactoryMock = $this->createMock(MiraklShopFactory::class);
        $this->miraklShopResourceFactoryMock = $this->createMock(MiraklShopResourceFactory::class);
        $this->resultPageFactoryMock = $this->createMock(PageFactory::class);
        $this->httpMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->httpMock->method('getHeaders')
            ->willReturnSelf();
        $this->httpMock->method('clearHeaders')
            ->willReturnSelf();
        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ssoHelperMock = $this->createMock(SSOHelper::class);
        $this->ssoConfigMock = $this->createMock(Config::class);
        $this->canvaCredentialsMock = $this->createMock(CanvaCredentials::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['dispatch'])
            ->getMockForAbstractClass();

        $this->heartbeat = new Heartbeat(
            $this->contextMock,
            $this->customerSessionMock,
            $this->miraklShopFactoryMock,
            $this->miraklShopResourceFactoryMock,
            $this->resultPageFactoryMock,
            $this->jsonMock,
            $this->loggerMock,
            $this->requestMock,
            $this->httpMock,
            $this->cookieManagerMock,
            $this->cookieMetadataFactoryMock,
            $this->ssoHelperMock,
            $this->ssoConfigMock,
            $this->canvaCredentialsMock,
            $this->toggleConfigMock,
            $this->eventManagerMock
        );
    }

    public function testExecute(): void
    {
        $requestContent = '{"seller_id": 2002}';
        $shopId = 2002;

        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->jsonMock->expects($this->once())
            ->method('unserialize')
            ->willReturn(json_decode($requestContent, true));
        $this->requestMock->expects($this->once())
            ->method('getContent')->willReturn($requestContent);

        $shopMock = $this->createMock(MiraklShop::class);
        $shopMock->method('getId')->willReturn($shopId);

        $miraklShopResourceMock = $this->createMock(Shop::class);
        $miraklShopResourceMock->expects($this->once())
            ->method('load')
            ->with($shopMock, $shopId);

        $this->miraklShopResourceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($miraklShopResourceMock);

        $this->miraklShopFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($shopMock);

        $this->httpMock->expects(self::atMost(3))
            ->method('setHeader')
            ->willReturnMap([
                ['Content-Type', 'text/plain'],
                ['Status', '200 Success']
            ]);

        $this->httpMock->expects($this->once())
            ->method('setStatusHeader')
            ->with(200, '1.1', 'Success')
            ->willReturnSelf();

        $result = $this->heartbeat->execute();
        $this->assertInstanceOf(Http::class, $result);
    }

    public function testExecuteCustomerNotLoggedIn(): void
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->httpMock->expects(self::atMost(3))
            ->method('setHeader')
            ->willReturnMap([
                ['Content-Type', 'text/plain'],
                ['Status', '403 Forbidden']
            ]);

        $this->httpMock->expects($this->once())
            ->method('setStatusHeader')
            ->with(403, '1.1', 'Forbidden')
            ->willReturnSelf();

        $result = $this->heartbeat->execute();
        $this->assertInstanceOf(Http::class, $result);
    }

    public function testExecuteMissingParameters(): void
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->atLeastOnce())
            ->method('getCustomerId')
            ->willReturn(3003);
        $this->requestMock->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode([]));
        $this->setCookieData();
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->cookieManagerMock->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('logout')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('setLastCustomerId')
            ->willReturnSelf();
        $this->eventManagerMock->expects($this->any())
            ->method('dispatch')
            ->with('user_logout_success', [])
            ->willReturnSelf();
        $this->httpMock->expects(self::atMost(3))
            ->method('setHeader')
            ->willReturnMap([
                ['Content-Type', 'text/plain'],
                ['Status', '403 Forbidden']
            ]);
        $this->httpMock->expects($this->once())
            ->method('setStatusHeader')
            ->with(403, '1.1', 'Forbidden')
            ->willReturnSelf();
        $result = $this->heartbeat->execute();
        $this->assertInstanceOf(Http::class, $result);
    }

    public function testGetShop(): void
    {
        $shopId = 2002;

        $shopMock = $this->createMock(MiraklShop::class);
        $shopMock->method('getId')->willReturn($shopId);

        $miraklShopResourceMock = $this->createMock(Shop::class);
        $miraklShopResourceMock->expects($this->once())
            ->method('load')
            ->with($shopMock, $shopId);

        $this->miraklShopResourceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($miraklShopResourceMock);

        $this->miraklShopFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($shopMock);

        $shop = $this->heartbeat->getShop($shopId);

        $this->assertInstanceOf(MiraklShop::class, $shop);
    }

    public function testCreateCsrfValidationException()
    {
        $result = $this->heartbeat->createCsrfValidationException($this->createMock(HttpRequest::class));
        $this->assertNull($result);
    }

    public function testValidateForCsrf()
    {
        $result = $this->heartbeat->validateForCsrf($this->createMock(HttpRequest::class));
        $this->assertTrue($result);
    }

    /**
     * @param array $response
     * @return Http
     */
    public function getHttpMock(array $response): Http
    {
        $jsonResponse = json_encode($response);
        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with($response)
            ->willReturn($jsonResponse);
        $this->httpMock->expects($this->once())
            ->method('setBody')
            ->with($jsonResponse)
            ->willReturnSelf();
        return $this->httpMock;
    }

    public function setCookieData(): void
    {
        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setDomain')
            ->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setPath')
            ->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setHttpOnly')
            ->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setSecure')
            ->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())
            ->method('setSameSite')
            ->willReturnSelf();
        $this->cookieMetadataFactoryMock->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);

    }

    public function testResponse200()
    {
        $this->httpMock->expects(self::atMost(3))
            ->method('setHeader')
            ->willReturnMap([
                ['Content-Type', 'text/plain'],
                ['Status', '200 Success']
            ]);
        $this->httpMock->expects($this->once())
            ->method('setStatusHeader')
            ->with(200, '1.1', 'Success')
            ->willReturnSelf();
        $response = $this->heartbeat->response200();
        $this->assertInstanceOf(Http::class, $response);
    }

    public function testResponse403()
    {
        $this->httpMock->expects(self::atMost(3))
            ->method('setHeader')
            ->willReturnMap([
                ['Content-Type', 'text/plain'],
                ['Status', '403 Forbidden']
            ]);
        $this->httpMock->expects($this->once())
            ->method('setStatusHeader')
            ->with(403, '1.1', 'Forbidden')
            ->willReturnSelf();
        $response = $this->heartbeat->response403();
        $this->assertInstanceOf(Http::class, $response);
    }

}
