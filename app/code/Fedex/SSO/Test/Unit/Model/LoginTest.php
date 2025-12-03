<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\SSO\Test\Unit\Model;

use Fedex\Canva\Model\CanvaCredentials;
use Fedex\Delivery\Helper\Data as delivery;
use Fedex\SSO\Helper\Data;
use Fedex\SSO\Model\Login;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fedex\Base\Helper\Auth;

/**
 * Test class for LoginTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class LoginTest extends TestCase
{

    protected $resultJson;
    protected $customerSessionMock;
    protected $deliveryHelperMock;
    protected $cookieMetadataFactoryMock;
    protected $cookieManagerInterfaceMock;
    /**
     * @var (\Fedex\SSO\ViewModel\SsoConfiguration & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ssoConfigurationMock;
    protected $helperMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Fedex\Canva\Model\CanvaCredentials & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $CanvaCredentialsMock;
    protected $publicCookieMetadataMock;
    protected $login;
    /**
     * Context
     *
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

    /**
     * Session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * CookieMetadataFactory
     *
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * CookieManagerInterface
     *
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManagerInterface;

    /**
     * SsoConfiguration
     *
     * @var \Fedex\SSO\ViewModel\SsoConfiguration
     */
    protected $ssoConfiguration;

    /**
     * Data
     *
     * @var \Fedex\SSO\Helper\Data
     */
    protected $helper;

    /**
     * LoggerInterface
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * JsonFactory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    protected Auth|MockObject $baseAuthMock;

    /**
     * Test setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJson = $this->getMockBuilder(ResultJson::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this
            ->getMockBuilder(Session::class)
            ->setMethods(['isLoggedIn', 'setProfileRetrieveError', 'setLoginError'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->deliveryHelperMock = $this
            ->getMockBuilder(delivery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerInterfaceMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCookie'])
            ->getMockForAbstractClass();

        $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerProfile', 'getFCLCookieNameToggle', 'getFCLCookieConfigValue'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->CanvaCredentialsMock = $this->getMockBuilder(CanvaCredentials::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->login = $objectManager->getObject(
            Login::class,
            [
                'context' => $this->context,
                'resultJsonFactory' => $this->resultJsonFactory,
                'session' => $this->customerSessionMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'cookieManagerInterface' => $this->cookieManagerInterfaceMock,
                'ssoConfiguration' => $this->ssoConfigurationMock,
                'helper' => $this->helperMock,
                'logger' => $this->loggerMock,
                'canvaCredentials' => $this->CanvaCredentialsMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'authHelper' => $this->baseAuthMock
            ]
        );
    }

    /**
     * Function test Is Customer Logged In
     *
     * @return void
     */
    public function testIsCustomerLoggedIn()
    {
        $this->resultJsonFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultJson);

        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(0);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);
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
        $fdxLogin = '3435125141321';
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->helperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(1);
        $this->helperMock->expects($this->any())
            ->method('getFCLCookieNameToggle')
            ->willReturn(1);
        $this->helperMock->expects($this->any())
            ->method('getFCLCookieConfigValue')
            ->willReturn('sdffcdzfa');
        $success = ['message' => 'Login Success', 'success' => true];
        $this->resultJson->expects($this->any())->method('setData')->with($success)->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->login->isCustomerLoggedIn());
    }

    /**
     * Function test Is Customer Logged In With Not Fcl Customer
     *
     * @return void
     */
    public function testIsCustomerLoggedInWithNotFclCustomer()
    {
        $this->resultJsonFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(0);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);
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
        $fdxLogin = '3435125141321';
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->helperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(0);
        $this->customerSessionMock->expects($this->any())
            ->method('setProfileRetrieveError')
            ->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('setLoginError')
            ->willReturnSelf();
        $success = ['message' => 'Login Error', 'success' => 'error'];
        $this->resultJson->expects($this->any())->method('setData')->with($success)->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->login->isCustomerLoggedIn());
    }

    /**
     * Function test Is Customer Logged In With Logged Customer
     *
     * @return void
     */
    public function testIsCustomerLoggedInWithCustomerAlreadyLoggedin()
    {
        $this->resultJsonFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(0);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);
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
        $fdxLogin = 'no';
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $success = ['message' => 'Logout Success', 'success' => true];
        $this->resultJson->expects($this->any())->method('setData')->with($success)->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->login->isCustomerLoggedIn());
    }

    /**
     * Function test Is Logged In with Commerical True
     *
     * @return void
     */
    public function testIsCustomerLoggedInWithCommericalTrue()
    {
        $this->resultJsonFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(1);
        $success = ['message' => 'Already Login With Customer Session', 'success' => true];
        $this->resultJson->expects($this->any())->method('setData')->with($success)->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->login->isCustomerLoggedIn());
    }

    /**
     * Function test Is Customer Logged In with Error 401
     *
     * @return void
     */
    public function testIsCustomerLoggedInWithError401()
    {
        $this->resultJsonFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(0);
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(0);
        $this->cookieMetadataFactoryMock->expects($this->any())
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);
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
        $fdxLogin = '3435125141321';
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->helperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willReturn(401);
        $success = ['message' => 'Cookie Expired', 'success' => 'expired'];
        $this->resultJson->expects($this->any())->method('setData')->with($success)->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->login->isCustomerLoggedIn());
    }
    /**
     * Function Test Is Customer Logged In with Exception
     *
     * @return void
     */
    public function testIsCustomerLoggedInWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->resultJsonFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->resultJson);
        $this->deliveryHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(0);
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(0);
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
        $fdxLogin = '3435125141321';
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->cookieManagerInterfaceMock->expects($this->any())->method('getCookie')->willReturn($fdxLogin);
        $this->helperMock->expects($this->any())
            ->method('getCustomerProfile')
            ->willThrowException($exception);
        $success = ['message' => 'Exception message', 'success' => false];
        $this->resultJson->expects($this->any())->method('setData')->with($success)->willReturnSelf();

        $this->assertEquals($this->resultJson, $this->login->isCustomerLoggedIn());
    }
}
