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
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Integration\Model\Oauth\Token;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Fedex\OktaMFTF\Gateway\Response\Introspect;
use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Fedex\OktaMFTF\Gateway\Okta;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\User\Model\User;
use Magento\Framework\Logger\LoggerProxy;
use Magento\Integration\Model\AdminTokenService;
use Fedex\OktaMFTF\Plugin\TokenGeneration;
use PHPUnit\Framework\TestCase;

class TokenGenerationTest extends TestCase
{
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlMock;
    protected $httpMock;
    /**
     * @var (\Magento\Framework\App\Response\Http & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $responseMock;
    protected $configMock;
    protected $userMock;
    protected $oktaMock;
    protected $adminTokenMock;
    protected $tokenFactoryMock;
    protected $tokenMock;
    protected $introspectMock;
    protected $plugin;
    protected function setUp(): void
    {
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
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->oktaMock = $this->getMockBuilder(Okta::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adminTokenMock = $this->getMockBuilder(AdminTokenService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenFactoryMock = $this->getMockBuilder(TokenModelFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->setMethods(['createAdminToken'])
            ->getMock();
        $this->introspectMock = $this->getMockBuilder(Introspect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new TokenGeneration(
            $this->tokenFactoryMock,
            $this->configMock,
            $this->httpMock,
            $this->userMock,
            $this->oktaMock
        );
    }

    public function testAroundCreateAdminAccessTokenNotEnabled(): void
    {
        $this->configMock->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->plugin->aroundCreateAdminAccessToken($this->adminTokenMock, function () {
            // block for testing purpose
        }, 'test', 'test');
    }

    public function testAroundExecuteEnabled(): void
    {
        $this->configMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->introspectMock->expects($this->once())->method('isActive')->willReturn(true);
        $this->httpMock->expects($this->once())
            ->method('setParams')
            ->with([
                'client_id' => 'test',
                'client_secret' => 'test'
            ]);
        $this->userMock->expects($this->once())->method('load')->willReturn($this->userMock);
        $this->oktaMock->expects($this->once())->method('introspect')->willReturn($this->introspectMock);
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->tokenMock);
        $this->tokenMock->expects($this->once())->method('createAdminToken')->willReturn($this->tokenMock);

        $this->plugin->aroundCreateAdminAccessToken($this->adminTokenMock, function () {
            // block for testing purpose
        }, 'test', 'test');
    }
}
