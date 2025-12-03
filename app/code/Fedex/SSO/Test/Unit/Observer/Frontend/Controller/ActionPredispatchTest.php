<?php
/**
 * Copyright © By infogain All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SSO\Test\Unit\Frontend\Controller;

use Fedex\SSO\Observer\Frontend\Controller\ActionPredispatch;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Login\Helper\Login;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\UrlInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Base\Helper\Auth;
use Magento\Framework\App\Request\Http as RequestHttp;

class ActionPredispatchTest extends \PHPUnit\Framework\TestCase
{
    protected $customerSessionFactory;
    protected $urlInterface;
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Fedex\SSO\Test\Unit\Frontend\Controller\Event & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eventMock;
    protected $requestHttp;
    /**
     * @var (\Magento\Framework\App\ActionFlag & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $actionFlagMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfigMock;
    protected $observer;
    protected $customerSession;
    protected $storeManagerInterface;
    protected $storeMock;
    /**
     * @var (\Magento\Framework\App\Response\RedirectInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $redirectInterfaceMock;
    protected $responseMock;
    /**
     * @var (\Magento\Customer\Api\Data\CustomerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerMock;
    protected $toggleConfig;
    protected $login;
    protected $selfRegHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $actionPredispatch;
    protected Auth|MockObject $baseAuthMock;
    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->customerSessionFactory = $this->getMockBuilder(CustomerSessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMockForAbstractClass();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRedirect', 'getFullActionName'])
            ->getMock();
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestHttp = $this->getMockBuilder(RequestHttp::class)
            ->setMethods(['getServer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionFlagMock = $this->getMockBuilder(ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction', 'getResponse', 'setRedirect', 'getEvent'])
            ->getMock();
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isLoggedIn', 'getCustomer', 'getCreatedIn',
                'logout', 'setBeforeAuthUrl', 'setLastCustomerId', 'getId',
                'unsOndemandCompanyInfo','unsCustomerCompany', 'getOndemandCompanyInfo', 'setOndemandCompanyInfo','setUserPermissionData'
            ])
            ->getMock();
        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();
        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName','getStoreId','getCode'])
            ->getMock();

        $this->redirectInterfaceMock = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRefererUrl'])
            ->getMockForAbstractClass();

        $this->responseMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect'])
            ->getMock();

        $this->customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->login = $this->getMockBuilder(Login::class)
            ->setMethods(['handleCustomerSession','getOndemandStoreUrl','getRetailStoreUrl','getCompanyId', 'getOndemandCompanyData','getUrlExtensionCookie','setUrlExtensionCookie'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['checkPermission'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->actionPredispatch = $this->objectManager->getObject(
            ActionPredispatch::class,
            [
                'logger' => $this->loggerMock,
                'storeManager' => $this->storeManagerInterface,
                'login' => $this->login,
                'toggleConfig' => $this->toggleConfig,
                'actionFlag' => $this->actionFlagMock,
                'sessionFactory' => $this->customerSessionFactory,
                'urlInterface' => $this->urlInterface,
                'selfRegHelper'=>$this->selfRegHelperMock,
                'authHelper' => $this->baseAuthMock,
                'http' => $this->requestHttp
            ]
        );
    }

    /**
     * Test execute function with Centeralised toggle on
     */
    public function testExecuteToggleOn()
    {
        $this->requestHttp->expects($this->any())
            ->method('getServer')
            ->willReturn('https://loginascustomer/login/index');
        $this->customerSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);
        $this->customerSession->expects($this->any())
            ->method('unsOndemandCompanyInfo')
            ->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn('');
        $this->login->expects($this->any())
            ->method('getOndemandCompanyData')
            ->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('setOndemandCompanyInfo')
            ->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('unsCustomerCompany')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('handleCustomerSession');
        $this->login->expects($this->any())
            ->method('getUrlExtensionCookie')
            ->willReturn("l6site51");
        $this->urlInterface->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("https://staging3.office.fedex.com/ondemand/checkout/cart/");
        $result = $this->actionPredispatch->execute($this->observer);
        $this->assertEquals($this->actionPredispatch,$result);
    }

    /**
     * Test execute function with Centeralised toggle on customer session not ondemand
     */
    public function testExecuteToggleOnCustomerSession()
    {
        $this->requestHttp->expects($this->any())
            ->method('getServer')
            ->willReturn('https://loginascustomer/login/index');
        $this->customerSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->login->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(23);
        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand23');

        $this->observer->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observer->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('setUrlExtensionCookie')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('getOndemandStoreUrl')
            ->willReturn("https://staging3.office.fedex.com/ondemand");
            $this->login->expects($this->any())
            ->method('getUrlExtensionCookie')
            ->willReturn("l6site51");
        $this->urlInterface->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("https://staging3.office.fedex.com/ondemand/checkout/cart/");
        $result = $this->actionPredispatch->execute($this->observer);
        $this->assertEquals($this->actionPredispatch,$result);
    }

    /**
     * Test execute function with Centeralised toggle on customer session not retail
     */
    public function testExecuteToggleOnCustomerSessionNotRetail()
    {
        $this->requestHttp->expects($this->any())
            ->method('getServer')
            ->willReturn('https://loginascustomer/login/index');
        $permissionData=['label'=>'Shared Credit Cards::shared_credit_cards'];
        $this->customerSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->login->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(null);
        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand23');

        $this->observer->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observer->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('getRetailStoreUrl')
            ->willReturn("https://staging3.office.fedex.com/");
        $this->login->expects($this->any())
            ->method('getUrlExtensionCookie')
            ->willReturn("l6site51");
        $this->urlInterface->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("https://staging3.office.fedex.com/ondemand/checkout/cart/");
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->selfRegHelperMock->expects($this->any())->method('checkPermission')->willReturn($permissionData);
        $result = $this->actionPredispatch->execute($this->observer);
        $this->assertEquals($this->actionPredispatch,$result);
    }

    public function testExecuteToggleOnCustomerSessionNotRetailAuthToggleOn()
    {
        $this->requestHttp->expects($this->any())
            ->method('getServer')
            ->willReturn('https://loginascustomer/login/index');
        $permissionData = ['label' => 'Shared Credit Cards::shared_credit_cards'];
        $this->customerSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);
        $this->baseAuthMock->method('isLoggedIn')
            ->willReturn(true);
        $this->login->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(null);
        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand23');

        $this->observer->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observer->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('getRetailStoreUrl')
            ->willReturn("https://staging3.office.fedex.com/");
        $this->login->expects($this->any())
            ->method('getUrlExtensionCookie')
            ->willReturn("l6site51");
        $this->urlInterface->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("https://staging3.office.fedex.com/ondemand/checkout/cart/");
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->selfRegHelperMock->expects($this->any())->method('checkPermission')->willReturn($permissionData);
        $result = $this->actionPredispatch->execute($this->observer);
        $this->assertEquals($this->actionPredispatch, $result);
    }

    /**
     * Test execute with retail home mixup toggle is on
     */
    public function testExecuteToggleOnRetailMixup()
    {
        $this->requestHttp->expects($this->any())
            ->method('getServer')
            ->willReturn('https://loginascustomer/login/index');
        $this->customerSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->login->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(null);
        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');
        $this->observer->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();
        $this->observer->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('getRetailStoreUrl')
            ->willReturn("https://staging3.office.fedex.com/");
        $this->login->expects($this->any())
            ->method('getUrlExtensionCookie')
            ->willReturn("l6site51");
        $this->urlInterface->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("https://staging3.office.fedex.com/ondemand/checkout/cart/");
        $result = $this->actionPredispatch->execute($this->observer);
        $this->assertEquals($this->actionPredispatch, $result);
    }

     /**
     * Test execute function with Centeralised toggle on customer session not retail without permission
     */
    public function testExecuteToggleOnCustomerSessionWithoutPermissionNotRetail()
    {
        $this->requestHttp->expects($this->any())
            ->method('getServer')
            ->willReturn('https://loginascustomer/login/index');
        $permissionData=['label'=>'Shared Credit Cards::shared_credit_cards'];
        $this->customerSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSession);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->login->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(null);
        $this->storeManagerInterface->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand23');

        $this->observer->expects($this->any())
            ->method('getControllerAction')
            ->willReturnSelf();

        $this->observer->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->responseMock->expects($this->any())
            ->method('setRedirect')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('getRetailStoreUrl')
            ->willReturn("https://staging3.office.fedex.com/");
        $this->login->expects($this->any())
            ->method('getUrlExtensionCookie')
            ->willReturn("l6site51");
        $this->urlInterface->expects($this->any())
            ->method('getCurrentUrl')
            ->willReturn("https://staging3.office.fedex.com/ondemand/checkout/cart/");
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->selfRegHelperMock->expects($this->any())->method('checkPermission')->willReturn([]);
        $result = $this->actionPredispatch->execute($this->observer);
        $this->assertEquals($this->actionPredispatch,$result);
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
        $this->assertEquals(true, $this->actionPredispatch->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

}
