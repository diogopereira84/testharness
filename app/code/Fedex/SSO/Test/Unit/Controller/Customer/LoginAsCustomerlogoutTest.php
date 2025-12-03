<?php
namespace Fedex\SSO\Test\Unit\Controller\Customer;

use Fedex\SSO\Controller\Customer\LoginAsCustomerlogout;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\LoginAsCustomerApi\Api\DeleteAuthenticationDataForUserInterface;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use PHPUnit\Framework\TestCase;

class LoginAsCustomerlogoutTest extends TestCase
{
    protected $resultFactoryMock;
    protected $customerSessionMock;
    protected $resultMock;
    private $controller;
    private $request;
    private $resultFactory;
    private $eventManager;
    private $customerSession;
    private $logger;
    private $cookieManager;
    private $cookieMetadataFactory;
    private $toggleConfig;
    private $deleteAuthenticationDataForUser;
    private $getLoggedAsCustomerAdminId;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->eventManager = $this->createMock(EventManager::class);
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods([
                'getId',
                'unsLoggedAsCustomerAdmindId'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cookieManager = $this->createMock(CookieManagerInterface::class);
        $this->cookieMetadataFactory = $this->createMock(CookieMetadataFactory::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->deleteAuthenticationDataForUser = $this->createMock(DeleteAuthenticationDataForUserInterface::class);
        $this->getLoggedAsCustomerAdminId = $this->createMock(GetLoggedAsCustomerAdminIdInterface::class);
        $this->resultMock = $this->createMock(Raw::class);

        $this->controller = new LoginAsCustomerlogout(
            $this->request,
            $this->resultFactoryMock,
            $this->eventManager,
            $this->customerSessionMock,
            $this->logger,
            $this->cookieManager,
            $this->cookieMetadataFactory,
            $this->toggleConfig,
            $this->deleteAuthenticationDataForUser,
            $this->getLoggedAsCustomerAdminId
        );
    }

    public function testExecuteWithImpersonatorEnabled()
    {

        // Mock the toggle config to return true
        $this->toggleConfig->method('getToggleConfigValue')
            ->with('mazegeeks_ctc_admin_impersonator')
            ->willReturn(true);

        // Simulate a logged-in customer session
        $this->customerSessionMock->expects($this->any())->method('getId')->willReturn(true);

        // Expect deleteAuthenticationDataForUser->execute to be called with customer ID 1
        $this->deleteAuthenticationDataForUser->expects($this->any())
            ->method('execute')
            ->with(1);

        // Expect customer session to clear impersonation ID
        $this->customerSessionMock->expects($this->any())->method('unsLoggedAsCustomerAdmindId');

        // Expect the result contents to be set to $isLoggedout (false by default)
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteWithImpersonatorDisabled()
    {

        // Mock the toggle config to return false
        $this->toggleConfig->method('getToggleConfigValue')
            ->with('mazegeeks_ctc_admin_impersonator')
            ->willReturn(false);

        // Expect the result contents to be set to $isLoggedout (false by default)
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->willReturnSelf();

        $this->controller->execute();
    }
}
