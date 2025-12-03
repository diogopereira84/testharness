<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Commercial\Test\Unit\Plugin\Controller\Users;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Commercial\Plugin\Controller\Users\Index;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\BlockInterface;
use Fedex\Ondemand\Model\Config as OndemandConfig;

/**
 * Test class for Plugin Index
 */
class IndexTest extends TestCase
{
    protected $resultPage;
    protected $configMock;
    protected $titleMock;
    protected $layoutMock;
    protected $blockMock;
    protected $ondemandConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $objIndex;
    /**
     * @var DeliveryDataHelper $deliveryDataHelper
     */
    protected $deliveryDataHelper;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface $url
     */
    protected $url;

    /**
     * @var CommercialHelper $commercialHelper
     */
    protected $commercialHelper;

    /**
     * @var CompanyContext $companyContext
     */
    protected $companyContext;

    /**
     * @var RedirectFactory $responseFactory
     */
    protected $responseFactory;
    
    /**
     * Setup mock for all constructor
     */
    public function setUp(): void
    {
        $this->deliveryDataHelper = $this->getMockBuilder(DeliveryDataHelper::class)
            ->setMethods([
                'getToggleConfigurationValue',
                'isCompanyAdminUser',
                'getCustomer',
                'checkPermission'
            ])->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->commercialHelper = $this->getMockBuilder(CommercialHelper::class)
            ->setMethods(['isSelfRegAdminUpdates'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->setMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseFactory = $this->getMockBuilder(ResponseFactory::class)
            ->setMethods(['create', 'setRedirect', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->resultPage = $this->getMockBuilder(Page::class)
            ->setMethods(['getConfig', 'getTitle', 'set', 'getLayout'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->configMock = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->titleMock = $this->getMockBuilder(Title::class)
            ->onlyMethods(['set'])
            ->disableOriginalConstructor()
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
            ->onlyMethods(['getMyAccountTabNameValue', 'getManageUsersTabNameValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->objIndex = $this->objectManager->getObject(
            Index::class,
            [
                'deliveryDataHelper' => $this->deliveryDataHelper,
                'scopeConfig' => $this->scopeConfig,
                'url' => $this->url,
                'commercialHelper' => $this->commercialHelper,
                'companyContext' => $this->companyContext,
                'responseFactory' => $this->responseFactory,
                'config' => $this->ondemandConfigMock
            ]
        );
    }

    /**
     * Test beforeExecute
     *
     * @return void
     */
    public function testBeforeExecute()
    {
        $this->deliveryDataHelper->expects($this->any())->method('getToggleConfigurationValue')->willReturn(true);
        $this->deliveryDataHelper->expects($this->once())->method('isCompanyAdminUser')->willReturn(false);
        $this->executeRouting();

        $this->assertNull($this->objIndex->beforeExecute('testSubject'));
    }

    /**
     * Test afterExecute
     *
     * @return void
     */
    public function testAfterExecute()
    {
        $this->deliveryDataHelper->expects($this->any())->method('getToggleConfigurationValue')->willReturn(true);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn(true);
        $this->commercialHelper->expects($this->once())->method('isSelfRegAdminUpdates')->willReturn(true);
        $this->resultPage->expects($this->any())->method('getConfig')->willReturnSelf();
        $this->resultPage->expects($this->once())->method('getTitle')->willReturnSelf();
        $this->resultPage->expects($this->once())->method('set')->willReturnSelf();
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn(true);
        $this->resultPage->expects($this->any())->method('getConfig')->willReturn($this->configMock);
        $this->configMock->expects($this->any())->method('getTitle')->willReturn($this->titleMock);
        $this->titleMock->expects($this->any())->method('set')->willReturnSelf();
        $this->resultPage->expects($this->any())->method('getLayout')->willReturn($this->layoutMock);
        $this->layoutMock->expects($this->any())->method('getBlock')->willReturn($this->blockMock);
        $this->ondemandConfigMock->expects($this->any())
            ->method('getMyAccountTabNameValue')
            ->willReturn('My Account | FedEx Office');
        $this->ondemandConfigMock->expects($this->any())
            ->method('getManageUsersTabNameValue')
            ->willReturn('Manage Users');

        $this->assertIsobject($this->objIndex->afterExecute('testSubject', $this->resultPage));
    }

    /**
     * Test afterExecute without customer id
     *
     * @return void
     */
    public function testAfterExecuteWithoutCustomerId()
    {
        $this->deliveryDataHelper->expects($this->any())->method('getToggleConfigurationValue')->willReturn(true);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn(false);
        $this->executeRouting();

        $this->assertIsobject($this->objIndex->afterExecute('testSubject', $this->resultPage));
    }

    /**
     * Excute routing mock
     *
     * @return void
     */
    public function executeRouting()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn('noroute');
        $this->url->expects($this->once())->method('getUrl')->willReturn('https://fedex.com');
        $this->responseFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->responseFactory->expects($this->once())->method('setRedirect')->willReturnSelf();
        $this->responseFactory->expects($this->once())->method('sendResponse')->willReturnSelf();
    }
}
