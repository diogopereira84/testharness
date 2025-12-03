<?php

namespace Fedex\Login\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Psr\Log\LoggerInterface;
use Fedex\Login\Helper\Login;
use Fedex\Login\Controller\Index\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\EnvironmentManager\Model\Config\B212363OpenRedirectionMaliciousSiteFix;

class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    protected $loginMock;
    protected $httpRequestMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlBuilder;
    protected $fuseBidViewModel;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $indexMock;
    private $resultRedirectFactoryMock;
    private $redirectMock;
    private $openRedirectionMaliciousSiteFixMock;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMockForAbstractClass();

        $this->loginMock = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'handleCustomerSession',
                'getRetailStoreUrl',
                'getOndemandStoreUrl',
                'getRedirectUrl',
                'getCompanyId',
                'getStoreCode',
                'isEmailVerificationRequired',
                'sendUserVerificationEmail',
                'isWireMockLoginEnable',
                'getFuseBidQuoteUrl'
            ])->getMock();
        $this->httpRequestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMock();

        $this->resultRedirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->setMethods()
            ->disableOriginalConstructor()
            ->getMock();
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->setMethods(['isFuseBidToggleEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->openRedirectionMaliciousSiteFixMock = $this->getMockBuilder(B212363OpenRedirectionMaliciousSiteFix::class)
            ->setMethods(['getPath', 'isActive'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->indexMock = $this->objectManager->getObject(
                Index::class,
                [
                    'url'     => $this->urlBuilder,
                    'logger'  => $this->logger,
                    'login'   => $this->loginMock,
                    'request' => $this->httpRequestMock,
                    'fuseBidViewModel' => $this->fuseBidViewModel,
                    'resultRedirectFactory' => $this->resultRedirectFactoryMock,
                    'context'=> $this->context,
                    'openRedirectionMaliciousSiteFix' => $this->openRedirectionMaliciousSiteFixMock
                ]
            );
    }

    public function testExecute():void
    {
        $loginResponse = [
            'status' => 'success',
        ];

        $expectedRedirectUrl = 'http://example.com/success';
        $this->loginMock->expects($this->any())
            ->method('handleCustomerSession')
            ->willReturn($loginResponse);
        $this->loginMock->expects($this->any())
            ->method('getRedirectUrl')
            ->willReturn($expectedRedirectUrl);
        $this->redirectMock->expects($this->any())
            ->method('setUrl')
            ->with($expectedRedirectUrl);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);
        $this->fuseBidViewModel->method('isFuseBidToggleEnabled')->willReturn(true);
        $this->loginMock->expects($this->once())
            ->method('getFuseBidQuoteUrl')
            ->willReturn($expectedRedirectUrl);

        $result = $this->indexMock->execute();

       $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test Execute with request Params with fedex url
     */
    public function testExecuteWithRequestParams():void
    {
        $params = [
            'rc' => 'aHR0cHM6Ly93d3cuZmVkZXguY29tLw==',
        ];
        $this->httpRequestMock->expects($this->any())
        ->method('getParams')
        ->willReturn($params);
        $this->openRedirectionMaliciousSiteFixMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);

        $result = $this->indexMock->execute();

       $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test Execute with request Params with non fedex url
     */
    public function testExecuteWithRequestParamsWithNonFedexUrl():void
    {
        $params = [
            'rc' => 'aHR0cHM6Ly93d3cuc3luYWNrLmNvbS8=',
        ];
        $this->httpRequestMock->expects($this->any())
        ->method('getParams')
        ->willReturn($params);
        $this->openRedirectionMaliciousSiteFixMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);

        $result = $this->indexMock->execute();

       $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test Method for ExecuteWithNoLoginResponse
     */
    public function testExecuteWithNoLoginResponse():void
    {
        $loginResponse = [];

        $expectedRedirectUrl = 'http://example.com/retail_store_url';
        $this->loginMock->expects($this->any())
            ->method('handleCustomerSession')
            ->willReturn($loginResponse);
        $this->loginMock->expects($this->any())
            ->method('getRetailStoreUrl')
            ->willReturn($expectedRedirectUrl);
        $this->redirectMock->expects($this->any())
            ->method('setUrl')
            ->with($expectedRedirectUrl);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);

        $result = $this->indexMock->execute();

       $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test method for Null error code
     */
    public function testExecuteNullErrorCode()
    {
        $loginResponse = [
            'status' => 'errors',
            'code' => 'test',
        ];

        $expectedRedirectUrl = 'http://example.com/oauth/fail/';

        $this->loginMock->expects($this->once())
            ->method('handleCustomerSession')
            ->willReturn($loginResponse);
        $this->loginMock->expects($this->once())
            ->method('isEmailVerificationRequired')
            ->willReturn(true);
        $this->loginMock->expects($this->once())
            ->method('sendUserVerificationEmail')
            ->willReturn(null);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);
        $result = $this->indexMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test method for error
     */
    public function testExecuteErrorWithNullErrorCode()
    {
        $params = [
            'rc' => 'abdshfdsh',
        ];
        $loginResponse = [
            'status' => 'error'
        ];
        $this->httpRequestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($params);
        $expectedRedirectUrl = 'http://example.com/retail_store_url';

        $this->loginMock->expects($this->once())
            ->method('handleCustomerSession')
            ->willReturn($loginResponse);
        $this->loginMock->expects($this->once())
            ->method('getOndemandStoreUrl')
            ->willReturn($expectedRedirectUrl);
        $this->redirectMock->expects($this->once())
            ->method('setUrl')
            ->with($expectedRedirectUrl);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);
        $result = $this->indexMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test method for error
     */
    public function testExecuteError()
    {
        $loginResponse = [
            'status' => 'error',
            'code' => 'retail_login_error',
        ];

        $expectedRedirectUrl = 'http://example.com/retail_store_url';

        $this->loginMock->expects($this->once())
            ->method('handleCustomerSession')
            ->willReturn($loginResponse);
        $this->loginMock->expects($this->once())
            ->method('getRetailStoreUrl')
            ->willReturn($expectedRedirectUrl);
        $this->redirectMock->expects($this->once())
            ->method('setUrl')
            ->with($expectedRedirectUrl);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);
        $result = $this->indexMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }
}
