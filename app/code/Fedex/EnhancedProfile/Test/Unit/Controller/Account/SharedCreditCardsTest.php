<?php
namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;
use Fedex\EnhancedProfile\Controller\Account\SharedCreditCards;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Backend\Model\View\Result\Redirect;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\Ondemand\Model\Config as OndemandConfig;

class SharedCreditCardsTest extends TestCase
{
    protected $contextMock;
    protected $redirectMock;
    protected $pageMock;
    protected $pageConfig;
    protected $pageTitle;
    protected $enhancedProfileMock;
    protected $ondemandConfigMock;
    /**
     * @var SharedCreditCards
     */
    private $sharedCreditCardsController;

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

    /** @var EnhancedProfile|MockObject */
    private $enhancedProfile;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->createMock(Context::class);

        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

            $this->deliveryDataHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['getCustomer','getCustomAttribute','getValue','getToggleConfigurationValue','isCompanyAdminUser','isSelfRegCustomerAdminUser','checkPermission','isSdeCustomer'])
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
            ->setMethods(['getConfig', 'getTitle'])
            ->getMock();
            $this->pageConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitle = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->setMethods(['set'])
            ->getMock();

        $this->enhancedProfileMock = $this->getMockBuilder(
            EnhancedProfile::class)->setMethods(
            [
                'isCompanySettingToggleEnabled',
            ]
         )->disableOriginalConstructor()
         ->getMock();

        $this->ondemandConfigMock = $this->getMockBuilder(OndemandConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMyAccountTabNameValue', 'getSitePaymentsTabNameValue'])
            ->getMock();

        $this->sharedCreditCardsController = $this->objectManager->getObject(
            SharedCreditCards::class,
            [
                'context' => $this->contextMock,
                'resultPageFactory' => $this->resultPageFactory,
                'deliveryDataHelper' => $this->deliveryDataHelper,
                'scopeConfig' => $this->scopeConfig,
                'url' => $this->url,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'enhancedProfile' => $this->enhancedProfileMock,
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
        $this->deliveryDataHelper->method('isSdeCustomer')->willReturn(false);
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
        $this->ondemandConfigMock->expects($this->any())
            ->method('getMyAccountTabNameValue')
            ->willReturn('My Account | FedEx Office');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getSitePaymentsTabNameValue')
            ->willReturn('Site Level Payments');
        $result = $this->sharedCreditCardsController->execute();
        $this->assertNotNull($result);
    }

    /**
     * Test Execute for SelfRegCustomer Non Admin User
     */
    public function testExecuteForSelfRegCustomerAdminUser()
    {
        $this->deliveryDataHelper->method('isCompanyAdminUser')->willReturn(true);
        $result = $this->sharedCreditCardsController->execute();
        $this->assertNull($result);
    }

    /**
     * Test Execute for Site Level Payment Title
     */
    public function testExecuteForSiteLevelPaymentTitle()
    {
        $this->deliveryDataHelper->method('isCompanyAdminUser')->willReturn(true);
        $this->deliveryDataHelper->method('getToggleConfigurationValue')->willReturn(true);
        $this->deliveryDataHelper->method('checkPermission')->willReturn(true);
        $this->deliveryDataHelper->method('isSdeCustomer')->willReturn(false);
        $this->enhancedProfileMock->expects($this->any())->method('isCompanySettingToggleEnabled')->willReturn(true);
        $this->resultPageFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->pageMock);
        $this->pageMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')
            ->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())->method('set')
            ->willReturn('Site Level Payments');
        $result = $this->sharedCreditCardsController->execute();

        $this->assertNotNull($result);
    }

    /**
     * Test Execute for Non SelfRegCustomer Admin User
     */
    public function testExecuteForNonSelfRegCustomerAdminUser()
    {
        $this->deliveryDataHelper->method('isSelfRegCustomerAdminUser')->willReturn(false);
        $isRolesAndPermissionEnabled = $this->deliveryDataHelper->getToggleConfigurationValue('change_customer_roles_and_permissions');
        $defaultNoRouteUrl = 'some_default_no_route_url';
        $this->scopeConfig->method('getValue')->willReturn($defaultNoRouteUrl);
        $redirectUrl = 'some_redirect_url';
        $this->url->method('getUrl')->willReturn($redirectUrl);
        $result = $this->sharedCreditCardsController->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $result);
    }
}
