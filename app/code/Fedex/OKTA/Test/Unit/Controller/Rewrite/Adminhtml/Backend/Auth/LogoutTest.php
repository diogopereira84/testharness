<?php

namespace Fedex\OKTA\Test\Unit\Controller\Rewrite\Adminhtml\Backend\Auth;

use Fedex\OKTA\Controller\Rewrite\Adminhtml\Backend\Auth\Logout;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backend\Helper\Data;
use PHPUnit\Framework\TestCase;

/**
 * Class LogoutTest
 * @package Fedex\OKTA\Test\Unit\Controller\Rewrite\Adminhtml\Backend\Auth
 */
class LogoutTest extends TestCase
{

    private $oktaHelperMock;
    private $resultPageFactoryMock;
    private $contextMock;
    private $messageManagerMock;
    private $authMock;
    private $resultRedirectFactoryMock;
    private $redirectMock;
    private $helperMock;
    private $objectManager;
    private $logoutMock;
    private $pageMock;

    /**
     * Method to setup the mock objects to run before each test.
     */
    protected function setUp(): void
    {
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->resultPageFactoryMock = $this->createMock(PageFactory::class);

        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->authMock = $this->createMock(Auth::class);
        $this->resultRedirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->pageMock = $this->createMock(Page::class);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->contextMock->method('getAuth')->willReturn($this->authMock);
        $this->contextMock->method('getMessageManager')->willReturn($this->messageManagerMock);
        $this->contextMock->method('getResultRedirectFactory')->willReturn($this->resultRedirectFactoryMock);
        $this->contextMock->method('getHelper')->willReturn($this->helperMock);

        $this->logoutMock = $this->objectManager->getObject(
            Logout::class,
            [
                'oktaHelper' => $this->oktaHelperMock,
                'resultPageFactory' => $this->resultPageFactoryMock,
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * Test to run : OKTA module is enabled
     */
    public function testExecuteIfOKTAModuleEnabled()
    {
        $this->oktaHelperMock->method('isEnabled')->willReturn(true);
        $this->authMock->method('logout')->willReturn(null);
        $this->messageManagerMock->method('addSuccessMessage')->willReturn(null);
        $this->resultPageFactoryMock->method('create')->willReturn($this->pageMock);

        $this->assertInstanceOf(Page::class, $this->logoutMock->execute());
    }

    /**
     *Test to run : OKTA module is enabled
     */
    public function testExecuteIfOKTAModuleDisabled()
    {
        $this->oktaHelperMock->method('isEnabled')->willReturn(false);
        $this->authMock->method('logout')->willReturn(null);
        $this->messageManagerMock->method('addSuccessMessage')->willReturn(null);
        $this->resultRedirectFactoryMock->method('create')->willReturn($this->redirectMock);
        $this->helperMock->method('getHomePageUrl')->willReturn($this->any());
        $this->redirectMock->method('setPath')->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->logoutMock->execute());
    }
}
