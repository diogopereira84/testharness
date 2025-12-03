<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Observer\Frontend\Company;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Observer\Frontend\Company\WlgnIntegration;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\Group;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\Controller\Result\ForwardFactory;
use Fedex\SelfReg\Block\Landing;
use Fedex\Base\Helper\Auth;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WlgnIntegrationTest extends TestCase
{
    protected $customerMock;
    protected $storeGroupMock;
    protected $selfRegHelper;
    protected $responseMock;
    protected $observerMock;
    protected $contextMock;
    protected $eventMock;
    protected $ondemandHelperMock;
    /**
     * @var (\Fedex\SDE\Helper\SdeHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sdeHelperMock;
    /**
     * @var (\Magento\Framework\Controller\Result\ForwardFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $forwardFactoryMock;
    /**
     * @var (\Fedex\SelfReg\Test\Unit\Observer\Frontend\Company\Forward & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultForwardMock;
    protected $selfRegLandingMock;
    /**
     * @var ObjectManager $_objectManager
     */
    protected $_objectManager;

    /**
     * @var WlgnIntegration $wlgnIntegration
     */
    protected $wlgnIntegration;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var StoreManagerInterface $storeManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var SessionFactory $sessionFactory
     */
    protected $sessionFactory;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var UrlInterface $urlInterface
     */
    protected $urlInterface;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    protected Auth|MockObject $baseAuthMock;

    /**
     * @var CookieManagerInterface|MockObject
     */
    protected $cookieManager;
    /**
     * @var CookieMetadataFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cookieMetadataFactory;
    /**
     * @var PublicCookieMetadata|\PHPUnit\Framework\MockObject\MockObject
     */
    private $publicCookieMetadata;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getToggleConfigValue',
                ]
            )
            ->getMock();

        $this->sessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'logout', 'setLastCustomerId', 'isLoggedIn',
				'setOndemandCompanyInfo', 'getOndemandCompanyInfo'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getGroup'])
            ->getMockForAbstractClass();

        $this->storeGroupMock = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getBaseUrl', 'getUrl'])
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->selfRegHelper = $this->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCompany', 'isSelfRegCustomer'])
            ->getMock();

        $this->responseMock = $this->getMockBuilder(\Magento\Framework\App\Response\Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect', 'sendResponse'])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent', 'getControllerAction', 'getResponse'])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods(['getModuleName', 'getControllerName', 'getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ondemandHelperMock = $this->getMockBuilder(Ondemand::class)
            ->setMethods(['isStoreRestructureOn', 'getOndemandStoreUrl', 'getOndemandCompanyData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getSsoLoginUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->forwardFactoryMock = $this->getMockBuilder(ForwardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultForwardMock = $this->getMockBuilder(Forward::class)
            ->setMethods(['setModule', 'setController', 'forward'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegLandingMock = $this->getMockBuilder(Landing::class)
            ->setMethods(['getLoginUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCookie', 'setPublicCookie', 'deleteCookie'])
            ->getMockForAbstractClass();
        $this->cookieMetadataFactory = $this->getMockBuilder(CookieMetadataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['createPublicCookieMetadata'])
            ->getMock();
        $this->publicCookieMetadata = $this->getMockBuilder(PublicCookieMetadata::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMock();

        $this->_objectManager = new ObjectManager($this);

        $this->wlgnIntegration = $this->_objectManager->getObject(
            WlgnIntegration::class,
            [
                'customerSessionFactory' => $this->sessionFactory,
                'storeManagerInterface' => $this->storeManagerInterface,
                'url' => $this->urlInterface,
                'toggleConfig' => $this->toggleConfig,
                'selfRegHelper' => $this->selfRegHelper,
                'ondemand' => $this->ondemandHelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'forwardFactory' => $this->forwardFactoryMock,
                'selfRegLanding' => $this->selfRegLandingMock,
                'http' => $this->responseMock,
                'authHelper' => $this->baseAuthMock,
                'session' => $this->customerSession
            ]
        );
    }

    /**
     * Test excute if store restructure enable
     * B-1515570
     */
    public function testExecuteIfStoreRestructureEnable()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $queryParameter = 'redirectUrl';
        $redirectUrl = 'https://staging3.office.fedex.com/selfreg/landing/';
        $ondemandUrl = 'https://staging3.office.fedex.com/ondemand/';

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');
        $this->contextMock->expects($this->any())->method('getControllerName')->willReturn('noroute');

        $this->ondemandHelperMock->expects($this->any())->method('isStoreRestructureOn')->willReturn(true);

        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('sde_default');

        $this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('sde_store');
	    $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandStoreUrl')->willReturn($ondemandUrl);

        $this->redirect($ondemandUrl);
        $this->assertEquals($this->observerMock, $this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * Test excute if store restructure enable and customer is not logged in
     * B-1515570
     */
    public function testExecuteWithNotLoggedInUser()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $queryParameter = 'redirectUrl';
        $redirectUrl = 'https://staging3.office.fedex.com/selfreg/landing/';
        $ondemandUrl = 'https://staging3.office.fedex.com/ondemand/';

        $companyInfo = ['ondemand_url' => true, 'url_extension' => false];

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');
        $this->contextMock->expects($this->any())->method('getControllerName')->willReturn('noroute');
        $this->contextMock->expects($this->any())->method('getFullActionName')->willReturn('cms_index_index');

        $this->ondemandHelperMock->expects($this->any())->method('isStoreRestructureOn')->willReturn(true);

        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');

        $this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

         $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn(null);

        $this->selfRegLandingMock->expects($this->any())->method('getLoginUrl')->willReturn($redirectUrl);
	    $this->redirect($redirectUrl);
        $this->assertEquals($this->observerMock, $this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * Test excute if store restructure enable and customer is logged in
     * @return void
     */
    public function testExecuteWithLoggedInUser()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $queryParameter = 'redirectUrl';
        $redirectUrl = 'https://staging3.office.fedex.com/selfreg/landing/';
        $ondemandUrl = 'https://staging3.office.fedex.com/ondemand/';

        $companyInfo = ['ondemand_url' => true, 'url_extension' => false];

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');
        $this->contextMock->expects($this->any())->method('getControllerName')->willReturn('noroute');
        $this->contextMock->expects($this->any())->method('getFullActionName')->willReturn('cms_index_index');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->cookieManager->expects($this->any())->method('getCookie')->with('emailhitquote')->willReturn('testcookievalue');
        $this->cookieMetadataFactory->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->method('setPath')->willReturnSelf();
        $this->ondemandHelperMock->expects($this->any())->method('isStoreRestructureOn')->willReturn(true);

        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');

        $this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

         $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn(null);

        $this->selfRegLandingMock->expects($this->any())->method('getLoginUrl')->willReturn($redirectUrl);
	    $this->redirect($redirectUrl);
        $this->wlgnIntegration->execute($this->observerMock);
    }

    /**
     * Test getEmailLinkCookie method
     * @return void
     */
    public function testgetEmailLinkCookie()
    {
        $this->cookieManager->expects($this->any())->method('getCookie')->with('emailhitquote')->willReturn('12345');
        $this->wlgnIntegration->getEmailLinkCookie();
    }

    /**
     * Test excute if store restructure enable and customer is not logged in
     * B-1515570
     */
    public function testExecuteForSelfRegNotLoggedInUser()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $queryParameter = 'redirectUrl';
        $redirectUrl = 'https://staging3.office.fedex.com/selfreg/landing/';
        $ondemandUrl = 'https://staging3.office.fedex.com/ondemand/';

        $companyInfoSelfReg = ['company_type' => 'selfreg', 'ondemand_url' => true, 'url_extension' => true];

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');
        $this->contextMock->expects($this->any())->method('getControllerName')->willReturn('noroute');

        $this->ondemandHelperMock->expects($this->any())->method('isStoreRestructureOn')->willReturn(true);

        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');

        $this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('sde_store');
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

	    $this->ondemandHelperMock->expects($this->any())->method('getOndemandStoreUrl')->willReturn($ondemandUrl);

        $this->redirect($ondemandUrl . 'ondemand');
        $this->assertEquals($this->observerMock, $this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * Test excute with not selfreg customer
     */
    public function testExecuteWithNotSelfRegCustomer()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $queryParameter = 'redirectUrl';
        $redirectUrl = 'https://staging3.office.fedex.com/selfreg/landing';

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('l6site51');

	$this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('sde_store');

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);

        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);

        $ondemandCompanyInfo = ['url_extension'=>'target','company_type'=>'sde','company_data'=>['storefront_login_method_option'=>'commercial_store_wlgn']];

        $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn($ondemandCompanyInfo);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setLastCustomerId')->willReturnSelf();

        $this->urlInterface->expects($this->any())->method('getUrl')->willReturn($redirectUrl);

        $this->observerMock->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observerMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->with($redirectUrl)
            ->willReturnSelf();

        $this->responseMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertIsObject($this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * Test excute with not selfreg customer with wlgn only
     */
    public function testExecuteWithNotSelfRegCustomerwithWlgnOnly()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $queryParameter = 'redirectUrl';
        $redirectUrl = 'https://staging3.office.fedex.com/selfreg/landing';

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('l6site51');

    $this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('sde_store');

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);

        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);

        $ondemandCompanyInfo = ['url_extension'=>'target','company_type'=>'selfreg','company_data'=>['storefront_login_method_option'=>'commercial_store_wlgn']];

        $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn($ondemandCompanyInfo);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setLastCustomerId')->willReturnSelf();

        $this->urlInterface->expects($this->any())->method('getUrl')->willReturn($redirectUrl);

        $this->observerMock->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observerMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->with($redirectUrl)
            ->willReturnSelf();

        $this->responseMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertNull($this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * Test excute with not selfreg customer with store front off
     */
    public function testExecuteWithNotSelfRegStorefrontfalse()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $queryParameter = 'redirectUrl';
        $redirectUrl = 'https://staging3.office.fedex.com/selfreg/landing';

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('l6site51');

        $this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('sde_store');

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);

        $this->sessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);

        $ondemandCompanyInfo = ['url_extension'=>'target','company_data'=>['storefront_login_method_option'=>'commercial_store_wlgn']];

        $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn($ondemandCompanyInfo);

        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);

        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setLastCustomerId')->willReturnSelf();

        $this->urlInterface->expects($this->any())->method('getUrl')->willReturn($redirectUrl);

        $this->observerMock->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observerMock->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->with($redirectUrl)
            ->willReturnSelf();

        $this->responseMock->expects($this->any())
            ->method('sendResponse')
            ->willReturnSelf();

        $this->assertNull($this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * Test excute if selfreg customer
     */
    public function testExecuteWithSelfRegCustomer()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';
        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('l6site51');

	$this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('sde_store');

        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('punchout');
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCompany')->willReturn(true);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);

        $this->assertIsObject($this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * Test excute for retail website
     */
    public function testExecuteForRetail()
    {
        $wlgnLoginUrl = 'https://wwwtest.fedex.com/secure-login/#/login-credentials';

        //B-1515570
        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getRequest')->willReturn($this->contextMock);
        $this->contextMock->expects($this->any())->method('getModuleName')->willReturn('not_selfreg');

        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');

	$this->storeManagerInterface->expects($this->any())->method('getGroup')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('getCode')->willReturn('sde_store');

        $this->assertIsObject($this->wlgnIntegration->execute($this->observerMock));
    }

    /**
     * test case for redirect method
     * B-1515570
     */
    public function redirect($redirectUrl)
    {
        $this->observerMock->expects($this->any())->method('getControllerAction')->willReturnSelf();
        $this->observerMock->expects($this->any())->method('getResponse')->willReturn($this->responseMock);
        $this->responseMock->expects($this->any())->method('setRedirect')->with($redirectUrl)->willReturnSelf();
    }

    /**
     * testGetOrCreateCustomerSession
     * @return void
     */
    public function testGetOrCreateCustomerSession()
    {
        $this->customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $result = $this->wlgnIntegration->getOrCreateCustomerSession();
        $this->assertSame($this->customerSession, $result);
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->wlgnIntegration->getToggleStatusForPerformanceImprovmentPhasetwo());
    }
}
