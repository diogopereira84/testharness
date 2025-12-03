<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Test\Unit\Block\Order;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Page\Title;
use Fedex\Orderhistory\Helper\Data;
use Fedex\Orderhistory\Block\Order\History;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\BlockInterface;
use Fedex\SharedDetails\ViewModel\SharedEnhancement;

class HistoryTest extends TestCase
{
    protected $customerMock;
    protected $pageTitleMock;
    protected $layout;
    protected $url;
    protected $sharedEnhancement;
    /**
     * @var string
     */
    protected $_template = 'Magento_Sales::order/history.phtml';

    /**
     * @var string
     */
    protected $_customEproTemplate = 'Fedex_Orderhistory::order/history.phtml';

    /**
     * @var string
     */
    protected $_customRetailTemplate = 'Fedex_Orderhistory::order/retail-history.phtml';

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestInterface;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $orderCollectionFactory;

    /**
     * @var CollectionFactoryInterface|MockObject
     */
    private $orderCollectionFactoryInterface;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var Config|MockObject
     */
    protected $orderConfig;

    /**
     * @var \Magento\Framework\View\Page\Config|MockObject
     */
    protected $pageConfig;

    /**
     * @var Title|MockObject
     */
    protected $pageTitle;

    /**
     * @var Data|MockObject
     */
    protected $orderHistoryDataHelper;

    /**
     * @var History|MockObject
     */
    protected $historyMock;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);

        $this->orderCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'columns'])
            ->getMock();

        $this->orderCollectionFactoryInterface = $this->getMockBuilder(CollectionFactoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->objectManager = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->objectManager->expects($this->any())->method('get')->willReturn($this->orderCollectionFactoryInterface);

        ObjectManager::setInstance($this->objectManager);

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(['getCustomerId', 'getCustomer','getCustomerCompany'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId'])
            ->getMock();

        $this->orderConfig = $this->getMockBuilder(Config::class)
            ->setMethods(['getVisibleOnFrontStatuses'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderHistoryDataHelper = $this->getMockBuilder(Data::class)
            ->setMethods([
                'isModuleEnabled',
                'isRetailOrderHistoryEnabled',
                'isSDEHomepageEnable',
                'getIsSdeStore',
            ])->disableOriginalConstructor()
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->pageConfig = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBlock', 'addCrumb', 'toHtml'])
            ->getMockForAbstractClass();

        $this->url = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $this->sharedEnhancement = $this->getMockBuilder(SharedEnhancement::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSharedOrderPage'])
            ->getMock();
    }

    public function testGetOrders()
    {
        $data = [];
        $customerId = 25;

        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->requestInterface->expects($this->exactly(4))->method('getParam')->withConsecutive(
            ['sortby'],
            ['sortby'],
            ['orderby'],
            ['orderby']
        )->willReturnOnConsecutiveCalls('created_at', 'created_at', 'DESC', 'DESC');

        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerSession->expects($this->once())->method('getCustomerCompany')->willReturnSelf();
        $this->sharedEnhancement->expects($this->once())->method('isSharedOrderPage')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getGroupId')->willReturn(89);

        $statuses = ['pending', 'processing', 'comlete'];

        $this->orderConfig->expects($this->any())->method('getVisibleOnFrontStatuses')->willReturn($statuses);

        $orderCollection = $this->getMockBuilder(CollectionFactory::class)
        ->setMethods([
            'addFieldToSelect',
            'addFieldToFilter',
            'setOrder',
            'getSelect',
            'join',
            'where',
            'columns',
            'addFilterToMap'
        ])->disableOriginalConstructor()
        ->getMock();

        $orderCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $orderCollection->expects($this->any())->method('join')->willReturnSelf();
        $orderCollection->expects($this->any())->method('where')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToSelect')->with('*')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryDataHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(true);
        $orderCollection->expects($this->any())->method('setOrder')->with('created_at', 'DESC')->willReturnSelf();

        $this->orderCollectionFactoryInterface->expects($this->atLeastOnce())->method('create')
        ->willReturn($orderCollection);

        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($orderCollection, $this->historyMock->getOrders());
    }

    public function testGetOrdersIsDisplaySharedOrdersOfCompanyDisabled()
    {
        $data = [];
        $customerId = 25;

        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->requestInterface->expects($this->exactly(4))->method('getParam')->withConsecutive(
            ['sortby'],
            ['sortby'],
            ['orderby'],
            ['orderby']
        )->willReturnOnConsecutiveCalls('created_at', 'created_at', 'DESC', 'DESC');

        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerSession->expects($this->once())->method('getCustomerCompany')->willReturnSelf();
        $this->sharedEnhancement->expects($this->once())->method('isSharedOrderPage')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getGroupId')->willReturn(89);

        $statuses = ['pending', 'processing', 'comlete'];

        $this->orderConfig->expects($this->any())->method('getVisibleOnFrontStatuses')->willReturn($statuses);

        $orderCollection = $this->getMockBuilder(CollectionFactory::class)
        ->setMethods([
            'addFieldToSelect',
            'addFieldToFilter',
            'setOrder',
            'getSelect',
            'join',
            'where',
            'columns',
            'addFilterToMap'
        ])->disableOriginalConstructor()
        ->getMock();

        $orderCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $orderCollection->expects($this->any())->method('join')->willReturnSelf();
        $orderCollection->expects($this->any())->method('where')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToSelect')->with('*')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryDataHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(true);
        $orderCollection->expects($this->any())->method('setOrder')->with('created_at', 'DESC')->willReturnSelf();

        $this->orderCollectionFactoryInterface->expects($this->atLeastOnce())->method('create')
        ->willReturn($orderCollection);

        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($orderCollection, $this->historyMock->getOrders());
    }

    public function testGetOrdersWithoutSharedOrder()
    {
        $data = [];
        $customerId = 25;

        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->requestInterface->expects($this->exactly(4))->method('getParam')->withConsecutive(
            ['sortby'],
            ['sortby'],
            ['orderby'],
            ['orderby']
        )->willReturnOnConsecutiveCalls('created_at', 'created_at', 'DESC', 'DESC');

        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerSession->expects($this->once())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getGroupId')->willReturn(89);
        $this->sharedEnhancement->expects($this->once())->method('isSharedOrderPage')->willReturn(false);
        $statuses = ['pending', 'processing', 'comlete'];

        $this->orderConfig->expects($this->once())->method('getVisibleOnFrontStatuses')->willReturn($statuses);

        $orderCollection = $this->createPartialMock(
            Collection::class,
            ['addFieldToSelect', 'addFieldToFilter', 'setOrder']
        );

        $orderCollection->expects($this->any())->method('addFieldToSelect')->with('*')->willReturnSelf();
        $orderCollection->method('addFieldToFilter')->withConsecutive(
            ['status', ['in' => $statuses]],
            ['main_table.status', ['neq' => 'pending']],
            ['ext_order_id', ['notnull' => true]]
        )->willReturnOnConsecutiveCalls($orderCollection, $orderCollection);

        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryDataHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(true);
        $orderCollection->expects($this->any())->method('setOrder')->with('created_at', 'DESC')->willReturnSelf();

        $this->orderCollectionFactoryInterface->expects($this->atLeastOnce())->method('create')
        ->willReturn($orderCollection);

        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($orderCollection, $this->historyMock->getOrders());
    }

    public function testGetOrdersWithoutToggle()
    {
        $data = [];
        $customerId = 25;

        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->requestInterface->expects($this->exactly(4))->method('getParam')->withConsecutive(
            ['sortby'],
            ['sortby'],
            ['orderby'],
            ['orderby']
        )->willReturnOnConsecutiveCalls('created_at', 'created_at', 'DESC', 'DESC');

        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $this->customerSession->expects($this->once())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getGroupId')->willReturn(89);
        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->sharedEnhancement->expects($this->once())->method('isSharedOrderPage')->willReturn(true);

        $statuses = ['pending', 'processing', 'comlete'];

        $this->orderConfig->expects($this->any())->method('getVisibleOnFrontStatuses')->willReturn($statuses);

        $orderCollection = $this->getMockBuilder(CollectionFactory::class)
        ->setMethods([
            'addFieldToSelect',
            'addFieldToFilter',
            'setOrder',
            'getSelect',
            'join',
            'where',
            'columns',
            'addFilterToMap'
        ])->disableOriginalConstructor()
        ->getMock();

        $orderCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $orderCollection->expects($this->any())->method('join')->willReturnSelf();
        $orderCollection->expects($this->any())->method('where')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToSelect')->with('*')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(false);
        $orderCollection->expects($this->any())->method('setOrder')->with('created_at', 'DESC')->willReturnSelf();

        $this->orderCollectionFactoryInterface->expects($this->atLeastOnce())->method('create')
            ->willReturn($orderCollection);

        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($orderCollection, $this->historyMock->getOrders());
    }

    public function testGetOrdersWithoutToggleWithRetail()
    {
        $data = [];
        $customerId = 25;

        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->requestInterface->expects($this->exactly(4))->method('getParam')
        ->withConsecutive(
            ['sortby'],
            ['sortby'],
            ['orderby'],
            ['orderby']
        )->willReturnOnConsecutiveCalls('created_at', 'created_at', 'DESC', 'DESC');

        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerSession->expects($this->once())->method('getCustomer')->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())->method('getGroupId')->willReturn(89);

        $statuses = ['pending', 'processing', 'comlete'];

        $this->orderConfig->expects($this->any())->method('getVisibleOnFrontStatuses')->willReturn($statuses);

        $orderCollection = $this->getMockBuilder(CollectionFactory::class)
        ->setMethods([
            'addFieldToSelect',
            'addFieldToFilter',
            'setOrder',
            'getSelect',
            'join',
            'columns',
            'addFilterToMap'
        ])->disableOriginalConstructor()
        ->getMock();

        $orderCollection->expects($this->any())->method('getSelect')->willReturnSelf();
        $orderCollection->expects($this->any())->method('join')->willReturnSelf();
        $orderCollection->expects($this->any())->method('columns')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToSelect')->with('*')->willReturnSelf();
        $orderCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(false);
        $orderCollection->expects($this->any())->method('setOrder')->with('created_at', 'DESC')->willReturnSelf();

        $this->orderCollectionFactoryInterface->expects($this->atLeastOnce())->method('create')
            ->willReturn($orderCollection);

        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($orderCollection, $this->historyMock->getOrders());
    }

    public function testGetOrdersWithoutCustomerId()
    {
        $data = [];

        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->requestInterface->expects($this->exactly(4))->method('getParam')
        ->withConsecutive(
            ['sortby'],
            ['sortby'],
            ['orderby'],
            ['orderby']
        )->willReturnOnConsecutiveCalls('created_at', 'created_at', 'DESC', 'DESC');

        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn('');

        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->historyMock->getOrders();
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function testGetTemplate()
    {
        $data = [];
        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(false);
        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($this->_customEproTemplate, $this->historyMock->getTemplate());
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function testGetTemplateRetail()
    {
        $data = [];
        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(true);
        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($this->_customRetailTemplate, $this->historyMock->getTemplate());
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function testGetTemplateDefault()
    {
        $data = [];
        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->atLeastOnce())->method('set')->willReturnSelf();

        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('isRetailOrderHistoryEnabled')->willReturn(false);
        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->assertEquals($this->_template, $this->historyMock->getTemplate());
    }

    /**
     * Prepare Layout for Epro
     * D-97122 Breadcrumb issue for sde
     */
    public function testPrepareLayoutForEpro()
    {
        $data = [];
        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->context->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->orderHistoryDataHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->context->expects($this->any())->method('getUrlBuilder')->willReturn($this->url);
        $this->layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->historyMock->_prepareLayout();
    }

    /**
     * Prepare Layout for SDE
     * D-97122 Breadcrumb issue for sde
     */
    public function testPrepareLayoutForSDE()
    {
        $data = [];
        $this->context->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->atLeastOnce())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->context->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->orderHistoryDataHelper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->orderHistoryDataHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->context->expects($this->any())->method('getUrlBuilder')->willReturn($this->url);
        $this->layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->historyMock = new History(
            $this->context,
            $this->orderCollectionFactory,
            $this->customerSession,
            $this->orderConfig,
            $this->orderHistoryDataHelper,
            $this->requestInterface,
            $this->sharedEnhancement,
            $data,
            $this->pageConfig,
            $this->pageTitleMock
        );

        $this->historyMock->_prepareLayout();
    }
}
