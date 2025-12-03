<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceAdmin
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Plugin\Controller\Adminhtml\Auth;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Plugin\Controller\Adminhtml\Auth\LogoutPlugin;
use Magento\Backend\Controller\Adminhtml\Auth\Logout;
use Magento\Backend\Model\Auth;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class LogoutPluginTest extends TestCase
{
    protected $authMock;
    private LogoutPlugin $logoutPlugin;
    private ToggleConfig|MockObject $toggleConfigMock;
    private OktaHelper|MockObject $oktaHelperMock;
    private ManagerInterface|MockObject $messageManagerMock;
    private PageFactory|MockObject $pageFactoryMock;
    private Logout|MockObject $logoutControllerMock;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->authMock = $this->createMock(Auth::class);
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->pageFactoryMock = $this->createMock(PageFactory::class);
        $this->logoutControllerMock = $this->getMockBuilder(Logout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logoutPlugin = new LogoutPlugin(
            $this->toggleConfigMock,
            $this->oktaHelperMock,
            $this->pageFactoryMock,
            $this->authMock,
            $this->messageManagerMock
        );
    }

    public function testAroundExecuteToggleConfigIsOff(): void
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('tigers_b2185176_remove_adobe_commerce_overrides')
            ->willReturn(false);
        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logoutControllerMock->method('execute')->willReturn($redirectMock);
        $result = $this->logoutControllerMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    public function testAroundExecuteOktaHelperIsEnabled(): void
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->oktaHelperMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logoutControllerMock->method('execute')->willReturn($redirectMock);
        $resultPage = $this->createMock(Page::class);
        $this->authMock->expects($this->once())
            ->method('logout');
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('You have logged out.'));
        $this->pageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultPage);
        $proceed = function () {
            return $this->logoutControllerMock->execute();
        };
        $this->assertEquals($resultPage, $this->logoutPlugin->aroundExecute($this->logoutControllerMock, $proceed));
    }
}
