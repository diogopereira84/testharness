<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Commerical\Test\Unit\Controller\Users;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Commercial\Controller\Users\Index;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;
use Fedex\Ondemand\Model\Config as OndemandConfig;

/**
 * Test class for Fedex\SharedDetails\Controller\Order\HistoryTest
 */
class IndexTest extends TestCase
{
    protected $contextMock;
    protected $redirectMock;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
    protected $companyContext;
    protected $resultFactory;
    protected $layoutMock;
    protected $blockMock;
    protected $ondemandConfigMock;
    /**
     * @var History
     */
    private $historyController;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var PageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultPageFactory;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $deliveryDataHelper;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;

    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $url;

    /**
     * @var RedirectFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultRedirectFactory;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->createMock(Context::class);

        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryDataHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['getCustomer','getCustomAttribute','getValue','getToggleConfigurationValue','isCompanyAdminUser','checkPermission'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->getMock();

        $this->redirectMock = $this->createPartialMock(
            Redirect::class,
            ['setPath']
        );

        $this->resultRedirectFactory = $this->createPartialMock(
                RedirectFactory::class,
                ['create']
            );

        $this->resultRedirectFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->redirectMock);

        $this->contextMock->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactory);

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfig', 'getTitle', 'getLayout'])
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->setMethods(['set'])
            ->getMock();
        $this->companyContext = $this->getMockBuilder(\Magento\Company\Model\CompanyContext::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCustomerId'])
        ->getMock();

        $this->resultFactory = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();

        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBlock'])
            ->getMockForAbstractClass();

        $this->blockMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setPageTitle'])
            ->getMockForAbstractClass();

        $this->ondemandConfigMock = $this->getMockBuilder(OndemandConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMyAccountTabNameValue', 'getManageUsersTabNameValue', 'getCompanyUsersTabNameValue'])
            ->getMock();

        $this->historyController = $this->objectManager->getObject(
            Index::class,
            [
                'context' => $this->contextMock,
                'resultPageFactory' => $this->resultPageFactory,
                'DeliveryhelperData' => $this->deliveryDataHelper,
                'scopeConfig' => $this->scopeConfig,
                'url' => $this->url,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'companyContext'=>$this->companyContext,
                'resultFactory'=>$this->resultFactory,
                'config' => $this->ondemandConfigMock
            ]
        );
    }

    /**
     * Test Execute for SelfRegCustomer Admin User
     */
    public function testExecute()
    {
        $this->deliveryDataHelper->method('isCompanyAdminUser')->willReturn(true);
        $this->deliveryDataHelper->method('getToggleConfigurationValue')->willReturn(true);
        $this->deliveryDataHelper->method('checkPermission')->willReturn(true);
        $this->companyContext->method('getCustomerId')->willReturn(true);
        $this->resultPageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->pageMock);
            $this->resultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->pageMock);
            $this->pageMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')
            ->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())->method('set')
            ->willReturn('Manage Users');
        $this->scopeConfig->method('getValue')->willReturn(true);
        $this->pageMock->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->layoutMock->expects($this->any())
            ->method('getBlock')
            ->willReturn($this->blockMock);
        $this->ondemandConfigMock->expects($this->any())
            ->method('getMyAccountTabNameValue')
            ->willReturn('My Account | FedEx Office');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getManageUsersTabNameValue')
            ->willReturn('Manage Users');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getCompanyUsersTabNameValue')
            ->willReturn('Company Users');
        $result = $this->historyController->execute();
        $this->assertNotNull($result);
        $result = $this->testExecuteForNonAdminUserWithOuthPermission();
    }

    /**
     * Test Execute for Non SelfRegCustomer Admin User
     */
    public function testExecuteForNonAdminUser()
    {
        $this->deliveryDataHelper->method('isCompanyAdminUser')->willReturn(false);
        $defaultNoRouteUrl = 'some_default_no_route_url';
        $this->scopeConfig->method('getValue')->willReturn($defaultNoRouteUrl);
        $redirectUrl = 'some_redirect_url';
        $this->url->method('getUrl')->willReturn($redirectUrl);
        $result = $this->historyController->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }
    
/**
     * Test Execute for SelfRegCustomer User without permission
     */
    public function testExecuteForNonAdminUserWithOuthPermission()
    {
        $this->deliveryDataHelper->method('isCompanyAdminUser')->willReturn(true);
        $customerMock = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
        ->getMock();
        $this->deliveryDataHelper->method('getCustomer')->willReturn($customerMock);
        $this->deliveryDataHelper->method('getToggleConfigurationValue')->will($this->onConsecutiveCalls(false, true));
        $customAttributeMock = $this->getMockBuilder(\Magento\Framework\Api\AttributeInterface::class)
        ->getMock();
        $customerMock->method('getCustomAttribute')->with('manage_user_permission')->willReturn($customAttributeMock);
        $customAttributeMock->method('getValue')->willReturn(true);
        $this->resultPageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->pageMock);
        $this->pageMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')
            ->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())->method('set')
            ->willReturn('Shared Orders');
        $result = $this->historyController->execute();
        $this->assertNotNull($result);
     
    }
}
