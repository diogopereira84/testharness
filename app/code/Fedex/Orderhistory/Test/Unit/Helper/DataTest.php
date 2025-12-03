<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\Cart\ViewModel\CheckoutConfig;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Fedex\Shipment\Helper\Data as ShipmentDataHelper;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Quote\Model\ResourceModel\Quote\Payment\Collection as PaymentCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\Cart\Controller\Dunc\Index;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item;
use Psr\Log\LoggerInterface;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $toggleConfig;
    protected $deliveryHelper;
    protected $quotePaymentFactoryMock;
    protected $quotePaymentMock;
    protected $quotePaymentCollectionMock;
    protected $requestMock;
    protected $session;
    protected $sso;
    protected $regionMock;
    protected $countryMock;
    protected $quoteFactoryMock;
    protected $productRepositoryInterfaceMock;
    protected $quoteMock;
    protected $urlInterfaceMock;
    protected $sdeHelperMock;
    protected $collectionFactoryMock;
    protected $collectionMock;
    protected $shipmentDataHelper;
    protected $shipmentInterface;
    protected $isReorderableMock;
    protected $isOrderCollectionMock;
    protected $isSelectMock;
    protected $isDataObjectMock;
    protected $selfreghelper;
    protected $orderCollection;
    protected $duncMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|OrderRepositoryInterface
     */
    private $orderRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dataMock;
    /**
     * @var \Fedex\Orderhistory\Helper\Data $helperData
     */
    protected $helperData;

    /**
     * @var CheckoutConfig|MockObject
     */
    private $checkoutConfigMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepositoryInterface;

    public const SHIPMENT_COMPLETATION_DATE = "2022-06-10 23:59:00";

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue','getToggleConfig'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->checkoutConfigMock = $this->createMock(CheckoutConfig::class);

        $this->deliveryHelper = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->setMethods([
                'isCommercialCustomer',
                'getAssignedCompany',
                'isEproCustomer',
                'getProductAttributeName',
                'getProductCustomAttributeValue'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quotePaymentFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\PaymentFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quotePaymentMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->setMethods(['getCollection', 'getQuoteId', 'getPoNumber'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quotePaymentCollectionMock = $this->getMockBuilder(PaymentCollection::class)
            ->setMethods(['addFieldToFilter', 'getSize', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->setMethods(['getFullActionName', 'getParam'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['create','getCustomer','getDuncResponse','setDuncResponse'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sso = $this->getMockBuilder(SsoConfiguration::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFclCustomer'])
            ->getMock();

        $this->regionMock = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->setMethods(['loadByCode', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->countryMock = $this->getMockBuilder(\Magento\Directory\Model\Country::class)
            ->setMethods(['loadByCode', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteFactoryMock = $this->getMockBuilder(\Magento\Quote\Model\QuoteFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryInterfaceMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods([
                'load',
                'getIsAlternate',
                'getBillingAddress',
                'getIsAlternatePickup',
                'getShippingAddress',
                'getEmail',
                'getFirstname',
                'getLastname',
                'getTelephone',
                'getPayment',
                'getPoNumber',
                'getCustomerTelephone',
                'getCustomerEmail',
                'getCustomerFirstname',
                'getCustomerLastname'
            ])->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->sdeHelperMock = $this->getMockBuilder(\Fedex\SDE\Helper\SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore'])
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
             ->disableOriginalConstructor()
             ->setMethods(['create','getFirstItem'])
             ->getMock();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addRegionNameFilter','getFirstItem','toArray'])
            ->getMock();

        $this->shipmentDataHelper = $this->getMockBuilder(ShipmentDataHelper::class)
            ->setMethods(['getShipmentById'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentInterface = $this->getMockBuilder(ShipmentInterface::class)
            ->setMethods(['getOrderCompletionDate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->isReorderableMock = $this->getMockBuilder(CollectionFactoryInterface::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->isOrderCollectionMock = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->isSelectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->isDataObjectMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['getReorderable', 'getDiff', 'getStatus'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfreghelper = $this
            ->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
            ->setMethods(['isSelfRegCompany','isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollection = $this->getMockBuilder(OrderCollection::class)
            ->setMethods(
                [
                    'getIsAlternate',
                    'getBillingAddress',
                    'getIsAlternatePickup',
                    'getShippingAddress',
                    'getEmail',
                    'getFirstname',
                    'getLastname',
                    'getTelephone',
                    'getShippingMethod'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->duncMock = $this->getMockBuilder(Index::class)
            ->setMethods(['callDuncApi'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->objectManager = new ObjectManager($this);

        $this->dataMock = $this->objectManager->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'deliveryHelper' => $this->deliveryHelper,
                'toggleConfig' => $this->toggleConfig,
                'checkoutConfig' => $this->checkoutConfigMock,
                'request' => $this->requestMock,
                'quotePaymentFactory' => $this->quotePaymentFactoryMock,
                'customerSession' => $this->session,
                'ssoConfiguration' => $this->sso,
                'quoteFactory' => $this->quoteFactoryMock,
                'country' => $this->countryMock,
                'urlBuilder' => $this->urlInterfaceMock,
                'sdeHelper' => $this->sdeHelperMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'shipmentDataHelper' => $this->shipmentDataHelper,
                'orderCollectionFactory' => $this->isReorderableMock,
                'productRepositoryInterface' => $this->productRepositoryInterfaceMock,
                'selfreghelper' => $this->selfreghelper,
                'duncCall' => $this->duncMock,
                'orderRepository' => $this->orderRepositoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test IsModuleEnabled for TRUE
     */
    public function testIsModuleEnabled()
    {
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->assertTrue($this->dataMock->isModuleEnabled());
    }

    /**
     * Test IsModuleEnabled for FALSE
     */
    public function testIsModuleEnabledwithFalse()
    {
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->assertFalse($this->dataMock->isModuleEnabled());
    }

    /**
     * Test IsSetOneColumn for TRUE |B-900085
     */
    public function testIsSetOneColumn()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_account_index');
        $this->assertTrue($this->dataMock->isSetOneColumn());
    }

    /**
     * Test IsSetOneColumnRetail for True
     */
    public function testIsSetOneColumnRetail()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_account_index');
        $this->assertTrue($this->dataMock->isSetOneColumnRetail());
    }

    /**
     * Test IsSetOneColumnRetail for False
     */
    public function testIsSetOneColumnRetailWithFalse()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_account_index');
        $this->assertFalse($this->dataMock->isSetOneColumnRetail());
    }

    /**
     * Test IsSetOneColumn for FALSE
     */
    public function testIsSetOneColumnwithFalse()
    {
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('cms_index_index');
        $this->assertFalse($this->dataMock->isSetOneColumn());
    }

    /**
     * Test PoNumberFromQuoteIds
     */
    public function testGetPoNumberFromQuoteIds()
    {
        $paramQuoteIds = [1, 2, 3];
        $expectedResult = ['1' => 'PO12345'];

        $this->quotePaymentFactoryMock->expects($this->any())->method('create')->willReturn($this->quotePaymentMock);
        $this->quotePaymentMock->expects($this->any())->method('getCollection')
            ->willReturn($this->quotePaymentCollectionMock);
        $this->quotePaymentCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quotePaymentCollectionMock->expects($this->any())->method('getSize')->willReturn(1);

        $this->quotePaymentCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->quotePaymentMock]));

        $this->quotePaymentMock->expects($this->any())->method('getQuoteId')->willReturn('1');
        $this->quotePaymentMock->expects($this->any())->method('getPoNumber')->willReturn('PO12345');

        $this->assertEquals($expectedResult, $this->dataMock->getPoNumberFromQuoteIds($paramQuoteIds));
    }

    /**
     * Test getCustomerSession |B-1053021
     */
    public function testGetCustomerSession()
    {
        $this->assertNotNull($this->dataMock->getCustomerSession());
    }

    /**
     * Test Case for isRetailOrderHistoryEnabled
     */
    public function testIsRetailOrderHistoryEnabled()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->assertTrue($this->dataMock->isRetailOrderHistoryEnabled());
    }

    /**
     * Test Case for isRetailOrderHistoryEnabled With toggle Off
     */
    public function testIsRetailOrderHistoryEnabledWithToggleOff()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->assertFalse($this->dataMock->isRetailOrderHistoryEnabled());
    }

    /**
     * Test isEnhancementEnabeled for TRUE
     */
    public function testIsEnhancementEnabeled()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->assertTrue($this->dataMock->isEnhancementEnabeled());
    }

    /**
     * Test IsModuleEnabled for FALSE
     */
    public function testIsEnhancementEnabeledwithFalse()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->assertFalse($this->dataMock->isEnhancementEnabeled());
    }

    /**
     * Test isEnhancementClass for TRUE |B-900085
     */
    public function testIsEnhancementClass()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_account_index');
        $this->assertTrue($this->dataMock->isEnhancementClass());
    }

    /**
     * Test isEnhancementClass for FALSE
     */
    public function testIsEnhancementClasswithFalse()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('cms_index_index');
        $this->assertFalse($this->dataMock->isEnhancementClass());
    }

    /**
     * Test isEnhancementClass for TRUE |B-900085
     */
    public function testIsRetailEnhancementClass()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('customer_account_index');
        $this->assertTrue($this->dataMock->isRetailEnhancementClass());
    }

    /**
     * Test isEnhancementClass for FALSE
     */
    public function testIsRetailEnhancementClasswithFalse()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('cms_index_index');
        $this->assertFalse($this->dataMock->isRetailEnhancementClass());
    }

    /**
     * Test getAlternateAddress
     */
    public function testGetAlternateAddress()
    {
        $address = [
            'name'=>'abc abc',
            'email'=>'abc@abc.com',
            'telephone'=>'123456',
            'is_alternate_pickup'=>1,
            'is_alternate'=>1
        ];
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getIsAlternate')->willReturn(1);
        $this->quoteMock->expects($this->any())->method('getIsAlternatePickup')->willReturn(1);
        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getEmail')->willReturn('abc@abc.com');
        $this->quoteMock->expects($this->any())->method('getFirstname')->willReturn('abc');
        $this->quoteMock->expects($this->any())->method('getLastname')->willReturn('abc');
        $this->quoteMock->expects($this->any())->method('getTelephone')->willReturn('123456');
        $this->assertEquals($address, $this->dataMock->getAlternateAddress(123));
    }

    /**
     * Test getAlternateAddress
     */
    public function testGetAlternateShippingAddress()
    {
        $this->isReorderableMock->expects($this->any())->method('create')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getIsAlternatePickup')->willReturn(1);
        $this->orderCollection->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->orderCollection->expects($this->any())->method('getFirstname')->willReturn('abc');
        $this->orderCollection->expects($this->any())->method('getLastname')->willReturn('abc');
        $this->orderCollection->expects($this->any())->method('getTelephone')->willReturn('123456');
        $this->orderCollection->expects($this->any())->method('getEmail')->willReturn('abc@abc.com');
        $this->orderCollection->expects($this->any())->method('getShippingMethod')->willReturn('test');
        $this->orderCollection->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getEmail')->willReturn('abc1@abc.com');
        $this->quoteMock->expects($this->any())->method('getFirstname')->willReturn('abc1');
        $this->quoteMock->expects($this->any())->method('getLastname')->willReturn('abc1');
        $this->quoteMock->expects($this->any())->method('getTelephone')->willReturn('1234567');
        $this->assertNotNull($this->dataMock->getAlternateShippingAddress($this->orderCollection));
    }

    /**
     * Test getAlternateAddress With Pickup
     */
    public function testGetAlternateShippingAddressWithPickup()
    {
        $this->isReorderableMock->expects($this->any())->method('create')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getIsAlternatePickup')->willReturn(1);
        $this->orderCollection->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->orderCollection->expects($this->any())->method('getFirstname')->willReturn('abc');
        $this->orderCollection->expects($this->any())->method('getLastname')->willReturn('abc');
        $this->orderCollection->expects($this->any())->method('getTelephone')->willReturn('123456');
        $this->orderCollection->expects($this->any())->method('getEmail')->willReturn('abc@abc.com');
        $this->orderCollection->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->orderCollection->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getEmail')->willReturn('abc1@abc.com');
        $this->quoteMock->expects($this->any())->method('getFirstname')->willReturn('abc1');
        $this->quoteMock->expects($this->any())->method('getLastname')->willReturn('abc1');
        $this->quoteMock->expects($this->any())->method('getTelephone')->willReturn('1234567');
        $this->assertNotNull($this->dataMock->getAlternateShippingAddress($this->orderCollection));
    }

    /**
     * Test testGetContactAddress
     */
    public function testGetContactAddress()
    {
        $address = ['name'=>'abc abc','email'=>'abc@abc.com','telephone'=>'123456'];
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getCustomerEmail')->willReturn('abc@abc.com');
        $this->quoteMock->expects($this->any())->method('getCustomerFirstname')->willReturn('abc');
        $this->quoteMock->expects($this->any())->method('getCustomerLastname')->willReturn('abc');
        $this->quoteMock->expects($this->any())->method('getCustomerTelephone')->willReturn('123456');
        $this->quoteMock->expects($this->any())->method('getTelephone')->willReturn('123456');
        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturnSelf();
        $this->assertEquals($address, $this->dataMock->getContactAddress($this->quoteMock));
    }

    /**
     * Test getPoNumber
     */
    public function testGetPoNumber()
    {
        $poNumber = '123456';
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getPoNumber')->willReturn('123456');
        $this->assertEquals($poNumber, $this->dataMock->getPoNumber(123));
    }

    /**
     * Test formatAddress
     */
    public function testFormatAddress()
    {
        $address =  [
            'address' => [
                'countryCode' => 'US',
                'name' => 'Test Name',
                'street' => 'Test Address1',
                'city' => 'Test City', 'stateOrProvinceCode' => 'Test NY',
                'postalCode' => '12222',
            ],
            'name' => 'Test Name',
            'phone' => '34567890',
            'email' => 'test@test.com'
        ];

        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getName')->willReturn('Test NY');

        $this->countryMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('getName')->willReturn('US');

        $response = $this->dataMock->formatAddress($address);
        $this->assertIsString($response);
    }

    /**
     * Test formatAddress
     */
    public function testFormatAddressWithoutState()
    {
        $address =  [
            'address' => [
                'countryCode' => 'US',
                'name' => 'Test Name',
                'street' => 'Test Address1',
                'city' => 'Test City', 'region' => 'NY',
                'postalCode' => '12222',
            ],
            'name' => 'Test Name',
            'phone' => '34567890',
            'email' => 'test@test.com'
        ];
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('toArray')->willReturn(['code' => 'test']);
        $this->dataMock->getRegionCode("ABCD");
        $this->countryMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('getName')->willReturn('US');

        $response = $this->dataMock->formatAddress($address);
        $this->assertIsString($response);
    }

    /**
     * Test enableCtrlPFunctionality
     * B-1160912 - Print the screen of view order and view quote via Ctrl+P
     */
    public function testEnableCtrlPFunctionality()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);

        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('sales_order_view');
        $this->assertTrue($this->dataMock->enableCtrlPFunctionality());
    }

    /**
     * Test enableCtrlPFunctionality for false
     * B-1160912 - Print the screen of view order and view quote via Ctrl+P
     */
    public function testEnableCtrlPFunctionalityWithFalse()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn('sales_order_view');
        $this->assertFalse($this->dataMock->enableCtrlPFunctionality());
    }

    /**
     * Test getPrintUrl
     * B-1160912 - Print the screen of view order and view quote via Ctrl+P
     */
    public function testGetPrintUrlWithOrderId()
    {
        $orderId = 123;
        $quoteId = null;

        $this->requestMock->method('getParam')->withConsecutive(['quote_id'], ['order_id'])
        ->willReturnOnConsecutiveCalls($quoteId, $orderId);
        $this->urlInterfaceMock->expects($this->any())->method('getUrl')->willReturn('Order_Url');
        $this->assertEquals('Order_Url', $this->dataMock->getPrintUrl());
    }

    /**
     * Test getPrintUrl
     * B-1160912 - Print the screen of view order and view quote via Ctrl+P
     */
    public function testGetPrintUrlWithQuoteId()
    {
        $orderId = null;
        $quoteId = 123;

        $this->requestMock->method('getParam')->withConsecutive(['quote_id'], ['order_id'])
        ->willReturnOnConsecutiveCalls($quoteId, $orderId);

        $this->urlInterfaceMock->expects($this->any())->method('getUrl')->willReturn('Quote_Url');
        $this->assertEquals('Quote_Url', $this->dataMock->getPrintUrl());
    }

    /**
     * Test getPrintUrl
     * B-1160912 - Print the screen of view order and view quote via Ctrl+P
     */
    public function testGetPrintUrlWithNull()
    {
        $this->assertNull($this->dataMock->getPrintUrl());
    }

    /**
     * Test getDuncOfficeUrl
     * B-1148619- Print Quote Receipt
     */
    public function testGetDuncOfficeUrl()
    {
        $documentOfficeApiUrl =
        'https://dunc.dmz.fedex.com/document/fedexoffice/v1/
        documents/contentReferenceId/preview?pageNumber=1&zoomFactor=0.2';
        $this->checkoutConfigMock->expects($this->any())
        ->method('getDocumentOfficeApiUrl')->willReturn($documentOfficeApiUrl);
        $this->assertEquals($documentOfficeApiUrl, $this->dataMock->getDuncOfficeUrl());
    }

    /**
     * Test testIsSDEHomepageEnable
     * B-1145903 - Show Order History with only shipped, ready for pickup or delivered
     */
    public function testIsSDEHomepageEnable()
    {
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->assertTrue($this->dataMock->IsSDEHomepageEnable());
    }

    /**
     * Test testIsSDEHomepageEnable for FALSE
     * B-1145903 - Show Order History with only shipped, ready for pickup or delivered
     */
    public function testIsSDEHomepageEnableForFalse()
    {
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->assertFalse($this->dataMock->IsSDEHomepageEnable());
    }

    /**
     * Test testisEProHomepageEnable
     */
    public function testisEProHomepageEnable()
    {
        /** B-1857860 */
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);

        $this->assertTrue($this->dataMock->isEProHomepageEnable());
    }

    /**
     * Test testisEProHomepageEnable for FALSE
     */
    public function testisEProHomepageEnableForFalse()
    {
        /** B-1857860 */
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->assertFalse($this->dataMock->isEProHomepageEnable());
    }

    /**
     * Test case for isPrintReceiptRetail with FCL is ON
     */
    public function testisPrintReceiptRetail()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(1);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->assertTrue($this->dataMock->isPrintReceiptRetail());
    }

    /**
     * Test case for isPrintReceiptRetail with FCL is OFF
     */
    public function testisPrintReceiptRetailWithFalseFCL()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->assertFalse($this->dataMock->isPrintReceiptRetail());
    }

    /**
     * Test case for getRegionCode
     */
    public function testgetRegionCode()
    {
        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->any())->method('addRegionNameFilter')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->collectionMock->expects($this->any())->method('toArray')->willReturn(['code' => 'test']);
        $this->dataMock->getRegionCode("ABCD");
    }

    /**
     * Test isRetailOrderHistoryReorderEnabled for TRUE
     */
    public function testIsRetailOrderHistoryReorderEnabled()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->assertTrue($this->dataMock->isRetailOrderHistoryReorderEnabled());
    }

    /**
     * Test isRetailOrderHistoryReorderEnabled for FALSE
     */
    public function testIsRetailOrderHistoryReorderEnabledWithoutToggle()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->assertFalse($this->dataMock->isRetailOrderHistoryReorderEnabled());
    }

    /**
     * Test Case for isCommercialReorderEnabled
     */
    public function testIsCommercialReorderEnabled()
    {
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->assertTrue($this->dataMock->isCommercialReorderEnabled());
    }

    /**
     * Test Case for isCommercialReorderEnabled
     */
    public function testIsCommercialReorderEnabledWithFalse()
    {
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->assertFalse($this->dataMock->isCommercialReorderEnabled());
    }

    /**
     * Test Case for isRetailItemDiscountToggle
     */
    public function testIsRetailItemDiscountToggle()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->assertTrue($this->dataMock->isRetailItemDiscountToggle());
    }

    /**
     * Test Case for isRetailItemDiscountToggle With toggle Off
     */
    public function testIsRetailItemDiscountToggleWithToggleOff()
    {
        $this->sso->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->assertFalse($this->dataMock->isRetailItemDiscountToggle());
    }

    /**
     * Test Case for getQuoteById
     */
    public function testGetQuoteById()
    {
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->with(12)->willReturn(true);

        $this->assertTrue($this->dataMock->getQuoteById(12));
    }

    /**
     * Test Case for Get Order Completion Date
     */
    public function testGetShipmentOrderCompletionDate()
    {
        $shipmentId = 123;
        $this->shipmentDataHelper->expects($this->any())->method('getShipmentById')->with($shipmentId)
        ->willReturn($this->shipmentInterface);
        $this->shipmentInterface->expects($this->any())->method('getOrderCompletionDate')
        ->willReturn(self::SHIPMENT_COMPLETATION_DATE);
        $this->assertEquals("Friday, June 10, 11:59pm", $this->dataMock->getShipmentOrderCompletionDate($shipmentId));
    }

    /**
     * Test Case for Get Order Completion Date
     */
    public function testGetShipmentOrderCompletionDateWithFalse()
    {
        $shipmentId = 123;
        $this->shipmentDataHelper->expects($this->any())->method('getShipmentById')->with($shipmentId)
        ->willReturn('');
        $this->assertFalse($this->dataMock->getShipmentOrderCompletionDate($shipmentId));
    }

    /**
     * Test Case for isReOrderable Order
     */
    public function testIsReOrderable()
    {
        $orderId = 7850;
        $this->isReorderableMock->expects($this->any())->method('create')->willReturn($this->isOrderCollectionMock);
        $this->isOrderCollectionMock->expects($this->exactly(2))->method('addFieldToFilter')->willReturnSelf();
        $this->isOrderCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->isSelectMock);
        $this->isSelectMock->expects($this->any())->method('where')->willReturnSelf();
        $this->isOrderCollectionMock->expects($this->any())->method('getFirstItem')
        ->willReturn($this->isDataObjectMock);
        $this->isDataObjectMock->expects($this->any())->method('getReorderable')
        ->willReturn(1);

        $this->assertTrue($this->dataMock->isReOrderable($orderId));
    }

    /**
     * Test Case for productAttributeSetName
     */
    public function testProductAttributeSetName()
    {
        $this->deliveryHelper->expects($this->any())->method('getProductAttributeName')
            ->with(123)
            ->willReturn('FXOPrint_Products');

        $this->assertEquals('FXOPrint_Products', $this->dataMock->productAttributeSetName(123));
    }

    /**
     * Test Case for getProductCustomAttributeValue
     */
    public function testGetProductCustomAttributeValue()
    {
        $this->deliveryHelper->expects($this->any())->method('getProductCustomAttributeValue')
            ->with(12, 'custmize')
            ->willReturn(true);

        $this->assertTrue($this->dataMock->getProductCustomAttributeValue(12, 'custmize'));
    }

    /**
     * Test Case for loadProductById
     */
    public function testLoadProductById()
    {
        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')->with(12)
        ->willReturn($this->isDataObjectMock);
        $this->isDataObjectMock->expects($this->any())->method('getStatus')->willReturn(1);
        $this->assertEquals($this->isDataObjectMock, $this->dataMock->loadProductById(12));
    }

    /**
     * Test Case for testLoadProductByIdWithElse
     */
    public function testLoadProductByIdWithElse()
    {
        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')->with(12)
        ->willReturn($this->isDataObjectMock);
        $this->isDataObjectMock->expects($this->any())->method('getStatus')->willReturn(0);
        $this->assertFalse($this->dataMock->loadProductById(12));
    }

    /**
     * Test Case for testLoadProductByIdWithException
     */
    public function testLoadProductByIdWithException()
    {
        $exception = new NoSuchEntityException;
        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')->with(15)
        ->willThrowException($exception);
        $this->assertFalse($this->dataMock->loadProductById(15));
    }

    /**
     * Test Case for testGetContactAddressForRetail
     */
    public function testGetContactAddressForRetail()
    {
		$ordeMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail', 'getBillingAddress'])
            ->disableOriginalConstructor()
            ->getMock();

        $ordeMock->expects($this->any())->method('getCustomerFirstname')->willReturn('Firstname');
        $ordeMock->expects($this->any())->method('getCustomerLastname')->willReturn('Lastname');
        $ordeMock->expects($this->any())->method('getCustomerEmail')->willReturn('Email');
        $ordeMock->expects($this->any())->method('getBillingAddress')->willReturnSelf();

        $this->assertIsArray($this->dataMock->getContactAddressForRetail($ordeMock));
    }

    /**
     * Assert getQuoteProductImage
     *
     * @return bool
     */
    public function testGetQuoteProductImage()
    {
        $imgRowData = 'imagedataer';
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->session->expects($this->any())->method('getDuncResponse')
            ->willReturn(['imagedataer'=> "kajdfkjakdsfjakdjfkajdfkadf"]);

        $this->assertNotNull($this->dataMock->getQuoteProductImage($imgRowData));
    }

    /**
     * Assert getQuoteProductImage
     *
     * @return bool
     */
    public function testGetQuoteProductImageWithDunc()
    {
        $imgRowData = 'imagedataerdd';
        $imageResponse = [
            'sucessful' => true,
            'output'=>[
                'imageByteStream' =>'adKAJSDKJASKDjAKSJDLKASFlaksdjflkajdflkjakdfjakfdjalkjfklajdf'
            ]
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->session->expects($this->any())->method('getDuncResponse')
            ->willReturn(['imagedataer'=> "kajdfkjakdsfjakdjfkajdfkadf"]);

        $this->duncMock->expects($this->any())->method('callDuncApi')
            ->willReturn($imageResponse);

        $this->assertNotNull($this->dataMock->getQuoteProductImage($imgRowData));
    }

    /**
     * Test Case for testIsCommercialCustomer
     */
    public function testIsCommercialCustomer()
    {
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);

        $this->assertTrue($this->dataMock->isCommercialCustomer());
    }

    /**
     * Test Case for testIsCommercialCustomerFalse
     */
    public function testIsCommercialCustomerFalse()
    {
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);

        $this->assertFalse($this->dataMock->isCommercialCustomer());
    }

    /**
     * Test case: Order contains legacy document (should return true)
     */
    public function testCheckOrderHasLegacyDocumentWithLegacyItem(): void
    {
        $orderId = 123;
        $orderMock = $this->createMock(Order::class);
        $orderItemMock = $this->createMock(Item::class);

        $orderItemMock->expects($this->once())
            ->method('getProductOptions')
            ->willReturn([
                'info_buyRequest' => [
                    'external_prod' => [
                        ['contentAssociations' => [['contentReference' => '12345']]]
                    ]
                ]
            ]);

        $orderMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$orderItemMock]);

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $result = $this->dataMock->checkOrderHasLegacyDocument($orderId);
        $this->assertIsArray($result);
    }

    /**
     * Test case: Order does not contain legacy document (should return false)
     */
    public function testCheckOrderHasLegacyDocumentWithoutLegacyItem(): void
    {
        $orderId = 456;
        $orderMock = $this->createMock(Order::class);
        $orderItemMock = $this->createMock(Item::class);

        $orderItemMock->expects($this->once())
            ->method('getProductOptions')
            ->willReturn([
                'info_buyRequest' => []
            ]);

        $orderMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$orderItemMock]);

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $result = $this->dataMock->checkOrderHasLegacyDocument($orderId);
        $this->assertIsArray($result);
    }

    /**
     * Test case: Exception occurs while fetching order (should return false)
     */
    public function testCheckOrderHasLegacyDocumentHandlesException(): void
    {
        $orderId = 789;

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willThrowException(new \Exception('Order not found'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Exception occurred while removing legacy document'));

        $result = $this->dataMock->checkOrderHasLegacyDocument($orderId);
        $this->assertFalse($result);
    }

    /**
     * Test getOrderShippingAddress
     */
    public function testGetOrderShippingAddress()
    {
        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFirstname', 'getLastname', 'getEmail', 'getTelephone'])
            ->getMock();
        $shippingAddressMock->expects($this->once())->method('getFirstname')->willReturn('John');
        $shippingAddressMock->expects($this->once())->method('getLastname')->willReturn('Doe');
        $shippingAddressMock->expects($this->once())->method('getEmail')->willReturn('john.doe@example.com');
        $shippingAddressMock->expects($this->once())->method('getTelephone')->willReturn('1234567890');

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingAddress'])
            ->getMock();
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddressMock);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'telephone' => '1234567890',
        ];

        $result = $this->dataMock->getOrderShippingAddress($orderMock);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test testGetContactAddressForOrder
     */
    public function testGetContactAddressForOrder()
    {
        $expectedAddress = [
            'name' => 'Abc Abc',
            'email' => 'abc@abc.com',
            'telephone' => '123456'
        ];

        // Mock billing address
        $billingAddressMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTelephone'])
            ->getMock();
        $billingAddressMock->expects($this->once())
            ->method('getTelephone')
            ->willReturn('123456');

        // Mock order
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getCustomerFirstname',
                'getCustomerLastname',
                'getCustomerEmail',
                'getBillingAddress'
            ])
            ->getMock();

        $orderMock->expects($this->once())
            ->method('getCustomerFirstname')
            ->willReturn('abc');

        $orderMock->expects($this->once())
            ->method('getCustomerLastname')
            ->willReturn('abc');

        $orderMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('abc@abc.com');

        $orderMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);

        // Run and assert
        $this->assertEquals($expectedAddress, $this->dataMock->getContactAddressForOrder($orderMock));
    }


}
