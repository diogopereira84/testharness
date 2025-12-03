<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Plugin;

use Exception;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Fedex\OktaMFTF\Gateway\Response\Introspect;
use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Fedex\OktaMFTF\Gateway\Okta;
use Fedex\OktaMFTF\Model\Login as LoginOktaMFTF;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\User\Model\User;
use Fedex\OktaMFTF\Plugin\LoginValidation;
use Magento\Framework\Logger\LoggerProxy;
use Fedex\OKTA\Controller\Rewrite\Adminhtml\Backend\Auth\Login;
use PHPUnit\Framework\TestCase;

class LoginValidationTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $managerMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlMock;
    protected $httpMock;
    protected $responseMock;
    protected $configMock;
    protected $loginOktaMock;
    protected $userMock;
    protected $oktaMock;
    /**
     * @var (\Magento\Framework\Logger\LoggerProxy & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $loggerMock;
    protected $loginMock;
    protected $introspectMock;
    protected $plugin;
    private const METHOD_IS_ENABLED = 'isEnabled';
    protected function setUp(): void
    {
        $this->managerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->httpMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loginOktaMock = $this->getMockBuilder(LoginOktaMFTF::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->oktaMock = $this->getMockBuilder(Okta::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerProxy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loginMock = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResponse'])
            ->getMock();
        $this->introspectMock = $this->getMockBuilder(Introspect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new LoginValidation(
            $this->managerMock,
            $this->urlMock,
            $this->httpMock,
            $this->configMock,
            $this->loginOktaMock,
            $this->userMock,
            $this->oktaMock,
            $this->loggerMock
        );
    }

    public function testAroundExecuteNotEnabled(): void
    {
        $this->configMock->expects($this->once())->method(self::METHOD_IS_ENABLED)->willReturn(false);

        $this->plugin->aroundExecute($this->loginMock, function () {
            // block for testing purpose
        });
    }

    public function testAroundExecuteEnabled(): void
    {
        $this->configMock->expects($this->once())->method(self::METHOD_IS_ENABLED)->willReturn(true);
        $this->introspectMock->expects($this->once())->method('isActive')->willReturn(true);
        $this->httpMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['client_id'], ['client_secret'])
            ->willReturnOnConsecutiveCalls('someId', 'clientSecret');
        $this->userMock->expects($this->once())->method('load')->willReturn($this->userMock);
        $this->oktaMock->expects($this->once())->method('introspect')->willReturn($this->introspectMock);
        $this->loginOktaMock->expects($this->once())->method('authenticate')->with($this->userMock);
        $this->loginMock->expects($this->once())->method('getResponse')->willReturn($this->responseMock);

        $this->plugin->aroundExecute($this->loginMock, function () {
            // block for testing purpose
        });
    }

    public function testAroundExecuteException(): void
    {
        $this->configMock->expects($this->once())->method(self::METHOD_IS_ENABLED)->willReturn(true);
        $this->introspectMock->expects($this->once())->method('isActive')->willReturn(false);
        $this->httpMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['client_id'], ['client_secret'])
            ->willReturnOnConsecutiveCalls('someId', 'clientSecret');
        $this->oktaMock->expects($this->once())->method('introspect')->willReturn($this->introspectMock);

        $this->expectException(Exception::class);

        $this->plugin->aroundExecute($this->loginMock, function () {
            // block for testing purpose
        });
    }
}
