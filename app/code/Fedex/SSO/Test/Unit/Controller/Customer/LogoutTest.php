<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Controller\Index;

use Exception;
use Fedex\Canva\Model\CanvaCredentials;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\Controller\Customer\Logout;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SSO\Helper\Data;
use Fedex\SSO\Model\Config;

class LogoutTest extends TestCase
{
    protected $resultFactoryMock;
    protected $customerSessionMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $cookieManagerMock;
    protected $cookieMetadataFactoryMock;
    protected $canvaCredentialsMock;
    protected $sdeHelperMock;
    protected $publicCookieMetadataMock;
    protected $eventManagerMock;
    protected $toggleConfigMock;
    protected $ssoHelperMock;
    /**
     * @var (\Fedex\SSO\Model\Config & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ssoConfigurationMock;
    protected $resultMock;
    protected $requestMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerLogout;
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods([
                'logout',
                'setLastCustomerId',
                'getId',
                'unsFclFdxLogin',
                'getCustomerId',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->canvaCredentialsMock = $this->getMockBuilder(CanvaCredentials::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['dispatch'])
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
          $this->ssoHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getV3Logout', 'getFCLCookieNameToggle', 'getFCLCookieConfigValue','callFclLogoutApi'])
            ->getMock();
         $this->ssoConfigurationMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->createMock(Raw::class);
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'getParams'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->customerLogout = $this->objectManager->getObject(
            Logout::class,
            [
                'request' => $this->requestMock,
                'resultFactory' => $this->resultFactoryMock,
                'customerSession' => $this->customerSessionMock,
                'logger' => $this->loggerMock,
                'cookieManager' => $this->cookieManagerMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'canvaCredentials' => $this->canvaCredentialsMock,
                'sdeHelper' => $this->sdeHelperMock,
                '_eventManager' => $this->eventManagerMock,
                'toggleConfig' => $this->toggleConfigMock,
                '_request' => $this->requestMock,
                'ssoConfiguration' => $this->ssoConfigurationMock,
                'ssoHelper' => $this->ssoHelperMock,

            ]
        );
    }

    /**
     * Test method for centralize login toggle on For SSO
     */
    public function testExecuteWithCentralizeToggleForSSO()
    {
        $this->setCookieData();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(1);
        $params = ["login_method" => 'commercial_store_sso'];
        $this->requestMock->expects($this->once())->method('getParams')->willReturn($params);
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
            ->method('dispatch')->with('user_logout_success', [])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->with(1)
            ->willReturnSelf();
        $this->assertSame($this->resultMock, $this->customerLogout->execute());
    }

    /**
     * Test method for centralize login toggle on For FCL
     */
    public function testExecuteWithCentralizeToggleForFCL()
    {
        $this->setCookieData();

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')->willReturn(1);
        $params = ["login_method" => 'commercial_store_wlgn'];
        $this->requestMock->expects($this->once())->method('getParams')->willReturn($params);
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
            ->method('dispatch')->with('user_logout_success', [])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);
        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->with(1)
            ->willReturnSelf();
        $this->assertSame($this->resultMock, $this->customerLogout->execute());
    }

    /**
     * Test method for testExecuteForOtherStoresWithException
     */
    public function testExecuteForOtherStoresWithException()
    {
        $this->setCookieData();

        $this->ssoHelperMock->expects($this->any())
        ->method('getFCLCookieNameToggle')->willReturn(true);
        $this->ssoHelperMock->expects($this->any())->method('callFclLogoutApi');

        $this->ssoHelperMock->expects($this->any())
        ->method('getFCLCookieConfigValue')->willReturn('sdfasda');

        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);

        $this->cookieManagerMock->expects($this->any())
            ->method('deleteCookie')
            ->willReturnSelf();

        $exception = new Exception();
        $this->canvaCredentialsMock->expects($this->any())
            ->method('fetch')
            ->willThrowException($exception);
        $this->customerSessionMock->expects($this->any())
            ->method('unsFclFdxLogin')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);
        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($this->resultMock);

        $this->resultMock->expects($this->any())
            ->method('setContents')
            ->with(0)
            ->willReturnSelf();

        $this->assertSame($this->resultMock, $this->customerLogout->execute());
    }

    /**
     * Common function to set Test Cookies data
     */
    public function setCookieData()
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

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
    }
}
