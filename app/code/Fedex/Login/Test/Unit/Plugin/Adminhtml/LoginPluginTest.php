<?php

namespace Fedex\Login\Test\Unit\Plugin\Adminhtml;

use Fedex\Login\Plugin\Adminhtml\LoginPlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\LoginAsCustomerAdminUi\Controller\Adminhtml\Login\Login;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Controller\ResultFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Message\ManagerInterface;

class LoginPluginTest extends TestCase
{
    private LoginPlugin $plugin;
    private $cookieManager;
    private $resultFactory;
    private $toggleConfig;
    private $messageManagerMock;
    private $requestInterfaceMock;

    protected function setUp(): void
    {
        $this->cookieManager = $this->createMock(CookieManagerInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);

        $this->plugin = new LoginPlugin(
            $this->cookieManager,
            $this->resultFactory,
            $this->toggleConfig,
            $this->messageManagerMock
        );
    }

    /**
     * Test execute method with toggle enable and cookie
     *
     * @return void
     */
    public function testAfterExecuteWhenToggleIsEnabledAndCookieExists()
    {
        // Arrange
        $loginMock = $this->createMock(Login::class);
        $resultMock = $this->createMock(ResultInterface::class);
        $this->toggleConfig->method('getToggleConfigValue')
            ->with('mazegeeks_ctc_admin_impersonator')
            ->willReturn(true);
        $this->cookieManager->method('getCookie')
            ->with('mage-cache-sessid')
            ->willReturn('some_value');
        $jsonResultMock = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMockForAbstractClass();
        $jsonResultMock->method('setData')
            ->with(['redirectUrl' => 'customer/index/edit?id=1234', 'login_error' => true]);
        $this->resultFactory->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($jsonResultMock);
        $loginMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestInterfaceMock);
        $this->requestInterfaceMock->expects($this->once())
            ->method('getParam')
            ->willReturn("1234");
        $loginMock->expects($this->once())
            ->method('getUrl')
            ->willReturn("customer/index/edit?id=1234");
        $result = $this->plugin->afterExecute($loginMock, $resultMock);
        $this->assertSame($jsonResultMock, $result);
    }

    /**
     * Test execute method with toggle disable
     *
     * @return void
     */
    public function testAfterExecuteWhenToggleIsDisabled()
    {
        // Arrange
        $loginMock = $this->createMock(Login::class);
        $resultMock = $this->createMock(ResultInterface::class);

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('mazegeeks_ctc_admin_impersonator')
            ->willReturn(false);

        // Act
        $result = $this->plugin->afterExecute($loginMock, $resultMock);

        // Assert
        $this->assertSame($resultMock, $result);
    }

    /**
     * Test execute method with toggle enable and cookie not exist
     *
     * @return void
     */
    public function testAfterExecuteWhenCookieDoesNotExist()
    {
        // Arrange
        $loginMock = $this->createMock(Login::class);
        $resultMock = $this->createMock(ResultInterface::class);

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('mazegeeks_ctc_admin_impersonator')
            ->willReturn(true);

        $this->cookieManager->method('getCookie')
            ->with('mage-cache-sessid')
            ->willReturn(null);

        // Act
        $result = $this->plugin->afterExecute($loginMock, $resultMock);

        // Assert
        $this->assertSame($resultMock, $result);
    }
}
