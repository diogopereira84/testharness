<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SharedDetails\Test\Unit\Controller\Order;

use Fedex\SharedDetails\Controller\Order\GenerateReport;
use Fedex\SharedDetails\Helper\CommercialReportHelper;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\SessionFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\Filesystem\File\WriteInterface as FileWriteInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use \Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Test class for Fedex\SharedDetails\Controller\Order\GenerateReportTest
 */
class GenerateReportTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $contextMock;
    /**
     * @var (\Magento\Framework\App\Action\Action & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ActionMock;
    /**
     * @var (\Magento\Framework\App\Action\HttpGetActionInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $httpMock;
    protected $requestMock;
    protected $orderMock;
    /**
     * @var (\Magento\Sales\Api\Data\OrderInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $order;
    protected $sessionFactoryMock;
    protected $customerSessionMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Catalog\Model\ResourceModel\Category\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryCollection;
    protected $orderRepositoryMock;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $request;
    /**
     * @var (\Fedex\SharedDetails\Test\Unit\Controller\Order\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $session;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestInterface;
    protected $paymentinfoInterface;
    /**
     * @var (\Magento\Eav\Api\AttributeSetRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attributeSetRepositoryInterface;
    /**
     * @var (\Magento\Eav\Api\Data\AttributeSetInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attributeSetMock;
    /**
     * @var (\Magento\Framework\Filesystem\File\WriteInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $stream;
    protected $pageFactoryMock;
    /**
     * @var (\Magento\Framework\Convert\ExcelFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $excelFactory;
    /**
     * @var (\Magento\Sales\Model\ResourceModel\Order\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productCollectionFactory;
    /**
     * @var (\Magento\Framework\View\Result\Page & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pageMock;
    protected $filesystem;
    protected $directoryWriteMock;
    /**
     * @var (\Magento\Framework\App\Filesystem\DirectoryList & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $directoryList;
    protected $commercialReportHelperMock;
    /**
     * @var (\Magento\Quote\Api\Data\CartInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartInterface;
    /**
     * @var (\Magento\Sales\Model\ResourceModel\Order\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderCollection;
    protected $customer;
    /**
     * @var (\Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productionLocationCollection;
    /**
     * @var GenerateReport
     */
    private $generateReportController;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var MockObject
     */
    protected $selectMock;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private CompanyRepositoryInterface $companyRepositoryMock;

    /**
     * @var CollectionFactoryInterface|MockObject
     */
    private $orderCollectionFactoryInterface;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;

    /** @var int */
    private $companyId;

    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;

    protected function setUp(): void
    {
        $this->companyId = 48;

        $this->objectManager = new ObjectManager($this);

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->contextMock = $this->createMock(Context::class);
        $this->ActionMock = $this->createMock(\Magento\Framework\App\Action\Action::class);
        $this->httpMock = $this->createMock(HttpGetActionInterface::class);
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'getAllItems', 'getPayment', 'getInvoiceCollection', 'getShipmentsCollection'])
            ->getMockForAbstractClass();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollectionFactoryInterface = $this->getMockBuilder(CollectionFactoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getCustomerCompany', 'getSecondaryEmail', 'getCustomer'])
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerCompany',
                'getCustomer',

            ])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical', 'error'])
            ->getMockForAbstractClass();

        $this->categoryCollection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addFieldToFilter',
                'addAttributeToSelect',
                'getSize',
                'getFirstItem',
                'getId',
                'setStoreId',
                'setOrder',
                'getIterator',
            ])->getMock();

        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'getSecondaryEmail'])
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->paymentinfoInterface = $this->getMockBuilder(InfoInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                ['getMethodInstance', 'getMethod']
            )
            ->getMockForAbstractClass();

        $this->attributeSetRepositoryInterface = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->attributeSetMock = $this->getMockBuilder(\Magento\Eav\Api\Data\AttributeSetInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetName', 'getCustomizable', 'getExternalProd', 'getAttributeSet'])
            ->getMockForAbstractClass();

        $this->stream = $this->getMockBuilder(FileWriteInterface::class)
            ->onlyMethods(['lock', 'unlock', 'close'])
            ->getMockForAbstractClass();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->excelFactory = $this->getMockBuilder(ExcelFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->productCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getSelect', 'join', 'where'])
            ->getMock();

        $this->pageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryWrite'])
            ->getMock();

        $this->directoryWriteMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDirectory', 'lock', 'create', 'openFile', 'writeCsv'])
            ->getMock();

        $this->filesystem->expects($this->any())->method('getDirectoryWrite')->willReturn($this->directoryWriteMock);

        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath', 'create'])
            ->getMock();

        $this->commercialReportHelperMock = $this->getMockBuilder(CommercialReportHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSet', 'sendEmail', 'getBranchId'])
            ->getMock();

        $this->cartInterface = $this
            ->getMockBuilder(CartInterface::class)
            ->setMethods(['getCustomerId', 'getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getGrandTotal', 'getCreatedAt', 'getSubtotal', 'getDiscount'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderCollectionFactoryInterface = $this->getMockBuilder(CollectionFactoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count', 'addFieldToSelect',
                'addAttributeToFilter', 'getSelect', 'join', 'where', 'getTable', 'getAllItems', 'setOrder', 'create'])
            ->getMockForAbstractClass();

        $this->orderCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count', 'addFieldToSelect',
                'addAttributeToFilter', 'getSelect', 'join', 'where', 'getTable', 'getAllItems', 'setOrder'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId', 'getSecondaryEmail'])
            ->getMock();

        $this->productionLocationCollection =
        $this->getMockBuilder(\Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getSize', 'getIterator'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->generateReportController = $this->objectManager->getObject(
            GenerateReport::class,
            [
                'context' => $this->contextMock,
                'companyRepository' => $this->companyRepositoryMock,
                'orderCollectionFactory' => $this->orderCollectionFactoryInterface,
                'customerSession' => $this->sessionFactoryMock,
                'logger' => $this->loggerMock,
                'resultPageFactory' => $this->pageFactoryMock,
                'directory' => $this->directoryWriteMock,
                'orderRepository' => $this->orderRepositoryMock,
                'commercialReportHelper' => $this->commercialReportHelperMock,
                'toggleConfig' => $this->toggleConfigMock,
            ]
        );
    }

    /**
     * Test method to Retrieve Headers row array for Export
     * @return string[]
     */
    public function testExportHeadersTranslations()
    {
        $expectedTranslations = [];
        $this->generateReportController->_getExportHeaders();
    }

    /**
     * test getProjectDetail
     */
    public function testGetProjectDetail()
    {
        $orderItem = [
            'product_id' => 123,
            'product_options' => 'catalog',
            'name' => 'test',
            'source' => 'Catalog',
        ];
        $productId = $orderItem['product_id'];
        $this->commercialReportHelperMock->expects($this->any())->method('getAttributeSet')
            ->with($productId)->willReturn('PrintOnDemand');
        $this->generateReportController->getProjectDetail($orderItem);
    }

    /**
     * test getProjectDetail on FXO
     */
    public function testGetProjectDetailFxo()
    {
        $orderItem = [
            'product_id' => 123,
            'product_options' => 'catalog',
            'name' => 'test',
            'source' => 'Catalog',
        ];
        $productId = $orderItem['product_id'];
        $this->commercialReportHelperMock->expects($this->any())->method('getAttributeSet')
            ->with($productId)->willReturn('FXOPrintProducts');
        $this->generateReportController->getProjectDetail($orderItem);
    }

    /**
     * test getProjectDetail on Other
     */
    public function testGetProjectDetailOther()
    {
        $orderItem = [
            'product_id' => 123,
            'product_options' => [
                'info_buyRequest' => [
                    'external_prod' => [
                        ['userProductName' => 'SampleProduct1'],
                        ['userProductName' => 'SampleProduct2'],
                    ],
                ],
            ],
        ];
        $productId = $orderItem['product_id'];
        $this->commercialReportHelperMock->expects($this->any())->method('getAttributeSet')
            ->with($productId)->willReturn('FXOPrintProducts');
        $this->generateReportController->getProjectDetail($orderItem);
    }

    /**
     * Test method to Get a row data of the particular columns
     *
     * @return string[]
     */
    public function testGetRowRecord()
    {
        $orderItem = [
            'order_id' => 123,
            'product_id' => 123,
            'product_options' => 'catalog',
            'name' => 'Sample Product Name',
            'source' => 'Send & Print',
            'qty_ordered' => '10',
            'price' => '10',
            'row_total' => '10',
            'discount_amount' => '5',
        ];
        $order = $this->getMockForAbstractClass(
            OrderInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getShippingAmount',
                'getEstimatedPickupTime',
                'getShippingAddress',
                'getPayment',
                'getMethod',
                'getBillingFields',
                'getCreatedAt', 'getBillingAddress', 'getLastName', 'getFirstName', 'getCustomTaxAmount', 'getTelephone', 'getEmail']

        );
        $this->orderRepositoryMock->expects($this->any())->method('get')
            ->with($orderItem['order_id'])->willReturn($order);
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(48);
        $company = $this->getMockForAbstractClass(
            CompanyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCompanyName'],
        );
        $company->expects($this->any())->method('getCompanyName')->willReturn('test');
        $this->companyRepositoryMock->expects($this->any())
            ->method('get')->willReturn($company);
        $pickUpDateTimeFormat = 'Monday, April 24, 4:00pm';

        $shippingAddress = $this->getMockBuilder(AddressInterface::class)
            ->getMockForAbstractClass();
        $order->expects($this->any())->method('getShippingAmount')->willReturn(20);

        $order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $shippingAddress->expects($this->any())->method('getStreet')->willReturn(['Street 1', 'Street 2']);
        $shippingAddress->expects($this->any())->method('getTelePhone')->willReturn('123213');
        $order->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddress);
        $order->expects($this->any())->method('getPayment')->willReturnSelf();
        $order->expects($this->any())->method('getMethod')->willReturn('fedexccpay');
        $order->expects($this->any())->method('getCreatedAt')->willReturn(date("d/m/Y"));
        $order->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $order->expects($this->any())->method('getBillingFields')->willReturn
            ('{"totalRecords":["abc"],"items":[{"fieldName":"abc","value":"2"}]}');
        $order->expects($this->any())->method('getFirstname')->willReturn('Test');
        $order->expects($this->any())->method('getLastname')->willReturn('Test');
        $this->commercialReportHelperMock->expects($this->any())->method
        ('getBranchId')->with($orderItem['order_id'])->willReturn('test');
        $this->assertNotNull($this->generateReportController->getRowRecord($orderItem));
    }

    /**
     * Test method to Get Payment Type
     *
     * @return null|string
     */
    public function testGetPaymentType()
    {
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->paymentinfoInterface->expects($this->any())->method('getMethod')->willReturn('fedexccpay');
        $order->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $paymentType =
        $this->generateReportController->getPaymentType($order);
        $this->assertEquals('Credit Card', $paymentType);
    }

    /**
     * Test method to Get Payment Type With Else
     *
     * @return null|string
     */
    public function testGetPaymentTypeElse()
    {
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->paymentinfoInterface->expects($this->any())->method('getMethod')->willReturn('fedexaccount');
        $order->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $paymentType = $this->generateReportController->getPaymentType($order);
        $this->assertEquals('FedEx account', $paymentType);
    }

    /**
     * Test method to Generate Report Execute action.
     *
     * @return PageFactory
     */
    public function testexecute()
    {
        $dateRange = "20/2/12 - 20/1/13";
        $this->pageFactoryMock->expects($this->any())->method('create')
            ->willReturnSelf();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                ['dateRange'],
                ['emailData']
            )->willReturnOnConsecutiveCalls(123, 'test');
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(48);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->customer->expects($this->any())->method('getSecondaryEmail')->willReturn("tst@gmail.com");
        $this->orderCollectionFactoryInterface->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('join')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('where')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('addFieldToFilter')->willReturn([$this->orderMock]);
        $this->orderCollectionFactoryInterface->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $result = $this->generateReportController->execute();
        $this->assertNotNull($result);
    }

    public function testElseExecute()
    {
        $dateRange = "20/2/12 - 20/1/13";
        $this->pageFactoryMock->expects($this->any())->method('create')
            ->willReturnSelf();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                ['dateRange'],
                ['emailData']
            )->willReturnOnConsecutiveCalls(123, null);
        $this->sessionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(48);
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())
            ->method('getGroupId')->willReturn(89);
        $this->customer->expects($this->any())->method('getSecondaryEmail')->willReturn("tst@gmail.com");
        $this->orderCollectionFactoryInterface->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('getSelect')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('join')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('where')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderCollectionFactoryInterface->expects($this->any())->method('setOrder')->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $result = $this->generateReportController->execute();
        $this->assertNotNull($result);
    }

    /**
     * Test method for get Order Billing Field Data
     *
     * @return array[]
     */
    public function testGetBillingFieldsData()
    {
        $billingFields = '{"totalRecords":["abc"],"items":[{"fieldName":"abc","value":"2"}]}';
        $paymentType = 'FedEx account';
        $result = $this->generateReportController->getBillingFieldsData($paymentType, $billingFields);
        $this->assertNotNull($result);
    }
}
