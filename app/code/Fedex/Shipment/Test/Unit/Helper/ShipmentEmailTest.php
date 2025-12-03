<?php

namespace Fedex\Shipment\Test\Unit\Helper;

use Exception;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Email\Helper\SendEmail;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Model\Email;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Shipment\Helper\Data;
use Fedex\Shipment\Model\ProducingAddress;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Fedex\Shipment\Model\ResourceModel\ProducingAddress\Collection as ProCollection;
use Fedex\Shipment\Model\Shipment as ModelShipment;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\FujitsuReceipt\Model\FujitsuReceipt;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface as Company;
use Fedex\Shipment\Helper\ShipmentEmail;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Fedex\MarketplaceCheckout\Block\Order\Email\Items;
use Fedex\Shipment\Helper\OrderConfirmationTemplateProvider;
use Fedex\MarketplaceCheckout\Model\Config\Email as EmailConfig;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Test class for ShipmentEmail
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ShipmentEmailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Fedex\FujitsuReceipt\Model\FujitsuReceipt & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fujitsuReceiptMock;
    /**
     * @var (\Magento\Framework\View\LayoutInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $layout;
    /**
     * @var (\Magento\Customer\Api\CustomerRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerRepository;
    /**
     * @var (\Fedex\Company\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyHelper;
    protected $companyManager;
    protected $marketplaceRatesHelper;
    protected $shipmentEmail;
    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var objectManagerHelper|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configInterface;

    /**
     * @var PunchoutHelper|MockObject
     */
    protected $punchoutHelper;

    /**
     * @var Data|MockObject
     */
    protected $helper;

    /**
     * @var ProducingAddressFactory|MockObject
     */
    protected $producingAddressFactory;

    /**
     * @var ShippingInformationInterface|MockObject
     */
    protected $addressInformationMock;

    /**
     * @var AddressInterface|MockObject
     */
    protected $addressInterfaceMock;

    /**
     * @var CountryFactory|MockObject
     */
    protected $countryFactory;

    /**
     * @var Country|MockObject
     */
    protected $countryMock;

    /**
     * @var Collection|MockObject
     */
    protected $countryCollectionMock;

    /**
     * @var SendEmail|MockObject
     */
    protected $mail;

    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var DateTime|MockObject
     */
    protected $date;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $timezone;

    /**
     * @var ShipmentInterface|MockObject
     */
    protected $shipmentInterface;

    /**
     * @var InfoInterface|MockObject
     */
    protected $paymentinfoInterface;

    /**
     * @var MethodInterface|MockObject
     */
    protected $methodInterface;

    /**
     * @var Order|MockObject
     */
    protected $order;

    /**
     * @var OrderFactory|MockObject
     */
    protected $orderFactory;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    protected $orderRepository;

    /**
     * @var Shipment|MockObject
     */
    protected $shipmentMock;

    /**
     * @var ModelShipment|MockObject
     */
    protected $shipment;

    /**
     * @var ProCollection|MockObject
     */
    protected $producingAddressCollection;

    /**
     * @var ProducingAddress|MockObject
     */
    protected $producingAddress;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * @var ShipmentRepositoryInterface|MockObject
     */
    protected $shipmentRepository;

    /**
     * @var Email|MockObject
     */
    private $emailHelper;

    /**
     * @var (\Magento\Sales\Api\Data\OrderPaymentInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderPaymentMock;

    /**
     * @var Items|MockObject
     */
    protected $itemsBlock;
    /**
     * @var OrderConfirmationTemplateProvider|MockObject
     */
    protected $orderConfirmationTemplateProvider;
    /**
     * @var EmailConfig|MockObject
     */
    protected $emailConfig;

    /** @var StoreManagerInterface|MockObject */
    private $storeManager;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = $this->getMockBuilder(Data::class)
            ->setMethods(
                [
                    'getOrderById',
                    'getShipmentById',
                    'getShipmentItems',
                    'getFirstTrackingNumber',
                    'getOrderItems',
                    'isMixedOrder',
                    'order'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->producingAddressFactory = $this->getMockBuilder(ProducingAddressFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'create',
                    'getCollection',
                    'addFieldToFilter',
                    'load'
                ]
            )
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getToggleConfigValue'
                ]
            )
            ->getMock();
        $this->addressInformationMock = $this->getMockBuilder(
            ShippingInformationInterface::class
        )
            ->setMethods(
                [
                    'getShippingDescription',
                    'getCustomerEmail',
                    'getCustomerLastName',
                    'getCustomerFirstName',
                    'getPayment',
                    'getShippingTaxAmount',
                    'getShippingAmount',
                    'getGrandTotal',
                    'getCustomTaxAmount',
                    'getIncrementId',
                    'getCustomerId',
                    'getShippingAddress',
                    'getShipmentAddress',
                    'getSubtotal',
                    'getDiscountAmount',
                    'getShippingMethod',
                    'getId',
                    'getMiraklIsOfferInclTax',
                    'getRetailTransactionId',
                    'getMethod'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressInterfaceMock = $this->getMockBuilder(AddressInterface::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->countryFactory = $this->getMockBuilder(CountryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->countryMock = $this->getMockBuilder(Country::class)
            ->setMethods(['loadByCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->countryCollectionMock = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getName'])
            ->getMock();
        $this->mail = $this->getMockBuilder(SendEmail::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->date = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->timezone = $this->getMockBuilder(TimezoneInterface::class)
            ->setMethods(['format'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipmentInterface = $this->getMockBuilder(ShipmentInterface::class)
            ->setMethods(
                [
                    'getShippingAccountNumber',
                    'OrderCompletionDate',
                    'getFxoWorkOrderNumber',
                    'getOrderCompletionDate',
                    'getIsCompletionEmailSent',
                    'getIsCancellationEmailSent',
                    'setIsCancellationEmailSent',
                    'setIsCompletionEmailSent'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->paymentinfoInterface = $this->getMockBuilder(InfoInterface::class)
            ->setMethods(
                [
                    'getMethodInstance',
                    'getTitle',
                    'getFedexAccountNumber',
                    'getMethod',
                    'getRetailTransactionId',
                    'getPoNumber',
                    'getCcLast4',
                    'getCcOwner'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->methodInterface = $this->getMockBuilder(MethodInterface::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIncrementId',
                'getShippingAddress',
                'getPayment',
                'getCustomerId',
                'getCustomTaxAmount',
                'getShippingAmount',
                'getShippingTaxAmount',
                'getGrandTotal',
                'getShipmentAddress',
                'getSubtotal',
                'getDiscountAmount',
                'getShippingMethod',
                'getId',
                'getMiraklIsOfferInclTax',
                'getRetailTransactionId',
                'getMethod',
                'getItems',
                'getBillingAddress',
                'getMethodInstance',
                'getCustomerFirstName',
                'getShippingDescription',
                'getCustomerEmail',
                'getCustomerLastName',
                'getAllVisibleItems',
                'getCreatedAt'
            ])
            ->getMock();
        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shipmentMock = $this->getMockBuilder(Shipment::class)
            ->setMethods(
                [
                    'getIncrementId',
                    'getShippingAccountNumber',
                    'OrderCompletionDate',
                    'getFxoWorkOrderNumber',
                    'getOrderCompletionDate',
                    'getEstimatedDeliveryDuration'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipment = $this->getMockBuilder(ModelShipment::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'OrderCompletionDate',
                    'getShipmentStatus',
                    'getTracks',
                    'addTrack',
                    'getCollection',
                    'getOrder',
                    'getId',
                    'getItemsCollection',
                    'setReadyForPickupEmailSent',
                    'setReadyForPickupEmailSentDate',
                    'save',
                    'getIncrementId',
                    'getShippingAccountNumber',
                    'getOrderCompletionDate',
                    'getFxoWorkOrderNumber',
                    'getEstimatedDeliveryDuration'
                ]
            )
            ->getMock();
        $this->producingAddressCollection = $this->getMockBuilder(
            ProCollection::class
        )
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'load',
                    'addFieldToFilter',
                    'getAddress',
                    'getPhoneNumber',
                    'getEmailAddress',
                    'getItems'
                ]
            )
            ->getMock();
        $this->producingAddress = $this->getMockBuilder(ProducingAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCollection',
                    'addFieldToFilter',
                    'load'
                ]
            )
            ->getMock();
        $this->shipmentRepository = $this->createMock(
            ShipmentRepositoryInterface::class
        );

        $this->fujitsuReceiptMock = $this->createMock(FujitsuReceipt::class);
        $this->emailHelper = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmailHtml', 'getEmailLogoUrl', 'minifyHtml','getFormattedCstDate'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['createBlock'])
            ->getMockForAbstractClass();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getById'])
            ->addMethods(['getExtensionAttributes', 'getCompanyAttributes', 'getCompanyId'])
            ->getMockForAbstractClass();
        $this->companyHelper = $this->createMock(CompanyHelper::class);
        $this->companyManager = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByCustomerId'])
            ->getMockForAbstractClass();
        $this->marketplaceRatesHelper = $this->createMock(MarketplaceRatesHelper::class);
        $this->orderPaymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getRetailTransactionId',
                    'getMethodInstance',
                    'getMethod',
                    'getTitle',
                    'getFedexAccountNumber'
                ]
            )
            ->getMockForAbstractClass();
        $this->itemsBlock = $this->getMockBuilder(Items::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getItemsHtml',
                'setName',
                'setArea',
                'setData',
                'toHtml'
            ])
            ->getMock();
        $this->orderConfirmationTemplateProvider = $this->getMockBuilder(OrderConfirmationTemplateProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfirmationstatus'])
            ->getMock();
        $this->emailConfig = $this->getMockBuilder(EmailConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTemplateId', 'getEmailTemplate'])
            ->getMock();

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseUrl'])
            ->getMock();
        $storeMock->method('getBaseUrl')
            ->willReturn('https://fedex.com/');

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->storeManager->method('getStore')
            ->willReturn($storeMock);

        $this->shipmentEmail = $this->objectManagerHelper->getObject(
            ShipmentEmail::class,
            [
                'logger' => $this->logger,
                'configInterface' => $this->configInterface,
                'mail' => $this->mail,
                'punchoutHelper' => $this->punchoutHelper,
                'helper' => $this->helper,
                'producingAddressFactory' => $this->producingAddressFactory,
                'countryFactory' => $this->countryFactory,
                'customerSession' => $this->customerSession,
                'timezone' => $this->timezone,
                'toggleConfig' => $this->toggleConfig,
                'shipmentRepository' =>$this->shipmentRepository,
                'fujitsuReceipt' => $this->fujitsuReceiptMock,
                'emailHelper' => $this->emailHelper,
                'layout' => $this->layout,
                'customerRepository' => $this->customerRepository,
                'companyHelper' => $this->companyHelper,
                'marketplaceRatesHelper' => $this->marketplaceRatesHelper,
                'storeManager' => $this->storeManager,
            ]
        );
    }

    public function testsendEmail()
    {
        $shipmentId = 15;
        $this->logger->expects($this->any())
            ->method('info')
            ->willReturnSelf();
        $this->helper->expects($this->any())
            ->method('getOrderById')
            ->willReturn($this->order);
        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn('2345');
        $this->order->expects($this->any())
            ->method('getIncrementId')
            ->willReturn('2020075757021738');
        $this->order->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(145);
        $this->order->expects($this->any())
            ->method('getDiscountAmount')
            ->willReturn(0.5);
        $this->order->expects($this->any())
            ->method('getCustomTaxAmount')
            ->willReturn(1);
        $this->order->expects($this->any())
            ->method('getGrandTotal')
            ->willReturn(18.5);
        $this->order->expects($this->any())
            ->method('getShippingAmount')
            ->willReturn(18.5);
        $this->order->expects($this->any())
            ->method('getShippingTaxAmount')
            ->willReturn(18.5);
        $this->order->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');
        $this->order->expects($this->any())
            ->method('getShippingDescription')
            ->willReturn('Fedex Shipment');
        $this->order->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn('john.doe@example.com');
        $this->order->expects($this->any())
            ->method('getCustomerFirstName')
            ->willReturn('John');
        $this->order->expects($this->any())
            ->method('getCustomerLastName')
            ->willReturn('Doe');

        $this->order->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->addressInterfaceMock);
        $this->addressInterfaceMock->expects($this->any())
            ->method('getData')
            ->willReturnOnConsecutiveCalls(
                'John',
                'Doe',
                null,
                '33590 S Dixie Hwy',
                'Florida City',
                'FL',
                '33034',
                'John',
                'Doe',
                null,
                '33590 S Dixie Hwy',
                'Florida City',
                'FL',
                '33034'
            );
        $this->testGetTokenDataSuccess();
        //$this->testGetTemplateOrderDataWithElse();
        $this->testgetFormattedShippingAddressArray();
        $this->producingAddressFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->producingAddress);
        $this->producingAddress->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->producingAddressCollection);
        $this->producingAddressCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->with("order_id", "123")
            ->willReturnSelf();
        $this->producingAddressCollection->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->producingAddressCollection]);
        $this->order->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->paymentinfoInterface->expects($this->any())
            ->method('getPoNumber')
            ->willReturn("789456123");
        $this->order->expects($this->any())->method('getMiraklIsOfferInclTax')->willReturn('asd');
        $item1 = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId'])
            ->getMockForAbstractClass();

        $item2 = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId'])
            ->getMockForAbstractClass();

        $item1->expects($this->any())->method('getMiraklOfferId')->willReturn(15);

        $item2->expects($this->any())->method('getMiraklOfferId')->willReturn(15);

        $this->order->expects($this->any())
            ->method('getItems')
            ->willReturn([$item1, $item2]);

        $this->paymentinfoInterface->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($this->methodInterface);
        $this->methodInterface->expects($this->any())
            ->method('getTitle')
            ->willReturn('title');
        $this->producingAddressCollection->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->producingAddressCollection->expects($this->any())
            ->method('getAddress')
            ->willReturn('33590 S Dixie Hwy, Florida City, FL 33034');
        $this->producingAddressCollection->expects($this->any())
            ->method('getPhoneNumber')
            ->willReturn('1234567890');
        $this->producingAddressCollection->expects($this->any())
            ->method('getEmailAddress')
            ->willReturn('john.doe@example.com');
        $this->producingAddressCollection->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->producingAddressCollection]);
        $this->shipment->expects($this->any())
            ->method('getIncrementId')
            ->willReturn('2020075757021738');
        $this->shipment->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willReturn('1234567890');
        $this->shipment->expects($this->any())
            ->method('getOrderCompletionDate')
            ->willReturn('2023-10-01');
        $this->shipment->expects($this->any())
            ->method('getFxoWorkOrderNumber')
            ->willReturn('FXO123456');
        $this->shipment->expects($this->any())
            ->method('getOrderCompletionDate')
            ->willReturn('2023-10-01');
        $this->shipment->expects($this->any())
            ->method('getEstimatedDeliveryDuration')
            ->willReturn('3-5 business days');
        $this->shipment->expects($this->any())
            ->method('getTracks')
            ->willReturn([$this->shipmentInterface]);
        $this->shipmentInterface->expects($this->any())
            ->method('getShippingAccountNumber')
            ->willReturn('1234567890');
        $this->shipmentInterface->expects($this->any())
            ->method('getOrderCompletionDate')
            ->willReturn('2023-10-01');
        $this->shipmentInterface->expects($this->any())
            ->method('getFxoWorkOrderNumber')
            ->willReturn('FXO123456');
        $shippingAddressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $shippingAddressMock->expects($this->any())->method('getData')->willReturnOnConsecutiveCalls(
            'John',
            'Doe',
            null,
            '33590 S Dixie Hwy',
            'Florida City',
            'FL',
            '33034'
        );

        $this->order->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);
        $this->order->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($shippingAddressMock);
        $this->order->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturnSelf();
        $this->order->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2023-10-01 12:00:00');
        $this->emailHelper->expects($this->any())
            ->method('getFormattedCstDate')
            ->willReturn('2023-10-01 12:00:00');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->withAnyParameters(
                'explorers_order_email_alternate_pick_up_person',
                'mazegeeks_D192133_fix'
            )
            ->willReturn(1);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getCcLast4')
            ->willReturn('1234');
        $this->paymentinfoInterface->expects($this->any())
            ->method('getCcOwner')
            ->willReturn('MK');
        $this->layout->expects($this->any())
            ->method('createBlock')
            ->willReturn($this->itemsBlock);
        $this->itemsBlock->expects($this->any())
            ->method('setName')
            ->willReturnself();
        $this->itemsBlock->expects($this->any())
            ->method('setArea')
            ->willReturnself(   );
        $this->itemsBlock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->itemsBlock->expects($this->any())
            ->method('toHtml')
            ->willReturn('<div>Item HTML</div>');
        $this->orderConfirmationTemplateProvider->expects($this->any())
            ->method('getConfirmationstatus')
            ->willReturn('166756');
        $result = $this->shipmentEmail->sendEmail("confirmed","123",null, null);
        $this->assertTrue($result);
    }

    public function testgetFormattedShippingAddressArray()
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['getShippingAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->countryFactory->expects($this->any())->method('create')->willReturn($this->countryMock);
        $this->countryMock->expects($this->any())->method('loadByCode')->willReturn($this->countryCollectionMock);
        $this->countryCollectionMock->expects($this->any())->method('getName')->willReturn('Texas');

        $shippingAddressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $shippingAddressMock->expects($this->any())->method('getData')->willReturnOnConsecutiveCalls(
            'John',
            'Doe',
            null,
            '33590 S Dixie Hwy',
            'Florida City',
            'FL',
            '33034'
        );

        $orderMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $result = $this->shipmentEmail->getFormattedShippingAddressArray($orderMock);
        $this->assertIsArray($result);
    }
    /**
     * Test testGetTemplateOrderDataWithElse method.
     */
    public function testGetTemplateOrderDataWithElse()
    {
        $orderId = 1594;
        $shipmentId = 15;
        $orderId = 1594;
        $shipmentId = 15;

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(
                [
                    'getShippingDescription',
                    'getCustomerEmail',
                    'getCustomerLastName',
                    'getCustomerFirstName',
                    'getPayment',
                    'getShippingTaxAmount',
                    'getShippingAmount',
                    'getGrandTotal',
                    'getCustomTaxAmount',
                    'getIncrementId',
                    'getCustomerId',
                    'getShippingAddress',
                    'getShipmentAddress',
                    'getSubtotal',
                    'getDiscountAmount',
                    'getShippingMethod',
                    'getId',
                    'getMiraklIsOfferInclTax',
                    'getRetailTransactionId',
                    'getMethod',
                    'getItems',
                    'getBillingAddress'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock->expects($this->any())->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');

        $shippingAddressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $shippingAddressMock->expects($this->any())->method('getData')->willReturnOnConsecutiveCalls(
            'John',
            'Doe',
            null,
            '33590 S Dixie Hwy',
            'Florida City',
            'FL',
            '33034',
            'John',
            'Doe',
            null,
            '33590 S Dixie Hwy',
            'Florida City',
            'FL',
            '33034',
            'John Alternate',
            'Doe Alternate',
            null,
            '33591 S Dixie Hwy',
            'Florida City',
            'FL',
            '33035'
        );

        $orderMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($shippingAddressMock);
        $orderMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $item1 = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId'])
            ->getMockForAbstractClass();

        $item2 = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId'])
            ->getMockForAbstractClass();

        $item1->expects($this->any())->method('getMiraklOfferId')->willReturn(15);

        $item2->expects($this->any())->method('getMiraklOfferId')->willReturn(15);

        $orderMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$item1, $item2]);

        $orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);

        $orderMock->expects($this->any())->method('getMiraklIsOfferInclTax')->willReturn('asd');
        $this->paymentinfoInterface->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($this->methodInterface);
        $this->methodInterface->expects($this->any())
            ->method('getTitle')
            ->willReturn('title');
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");

        $this->helper->expects($this->any())->method('getOrderById')->willReturn($orderMock);

        $this->helper->expects($this->any())->method('getShipmentItems')->willReturn(['name' => 'product', 'qty' => 2]);
        $this->addressInformationMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->addressInterfaceMock);
        $this->addressInterfaceMock->expects($this->any())
            ->method('getData')->willReturn('string');
        $this->countryFactory->expects($this->any())->method('create')->willReturn($this->countryMock);
        $this->countryMock->expects($this->any())->method('loadByCode')->willReturn($this->countryCollectionMock);
        $this->countryCollectionMock->expects($this->any())->method('getName')->willReturn('Texas');
        $this->producingAddressFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->producingAddress);
        $this->producingAddress->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->producingAddressCollection);
        $this->producingAddressCollection->expects($this->any())
            ->method('addFieldToFilter')
            ->with("shipment_id", $shipmentId)
            ->willReturnSelf();
        $this->producingAddressCollection->expects($this->any())
            ->method('load')
            ->willReturn([$this->producingAddressCollection]);
        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->shipmentMock->expects($this->any())->method('OrderCompletionDate')->willReturn('2-2-2021');
        $this->shipmentMock->expects($this->any())->method('getFxoWorkOrderNumber')->willReturn('2-2-2021');
        $this->shipmentMock->expects($this->any())->method('getOrderCompletionDate')->willReturn('');
        $this->shipmentMock->expects($this->any())->method('getEstimatedDeliveryDuration')->willReturn('2-2-2021');
        $this->shipmentMock->expects($this->any())->method('getShippingAccountNumber')->willReturn('2-2-2021');
        $this->date->expects($this->any())->method('gmtDate')->willReturn("2020-06-06");
        $this->timezone->expects($this->any())->method('date')->willReturnSelf();
        $this->timezone->expects($this->any())->method('format')->willReturn("2020-06-06 12:12:12");
        $this->addressInformationMock->expects($this->any())->method('getIncrementId')->willReturn(34234342);
        $this->addressInformationMock->expects($this->any())->method('getSubtotal')->willReturn(342);
        $this->addressInformationMock->expects($this->any())->method('getDiscountAmount')->willReturn(10);
        $this->addressInformationMock->expects($this->any())->method('getCustomTaxAmount')->willReturn(342);
        $this->addressInformationMock->expects($this->any())->method('getGrandTotal')->willReturn(342);
        $this->addressInformationMock->expects($this->any())->method('getShippingAmount')->willReturn(342);
        $this->addressInformationMock->expects($this->any())->method('getShippingTaxAmount')->willReturn(342);
        $this->addressInformationMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getMethodInstance')
            ->willReturn($this->methodInterface);
        $this->methodInterface->expects($this->any())
            ->method('getTitle')
            ->willReturn('title');
        $this->paymentinfoInterface->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn('');
        $this->paymentinfoInterface->expects($this->any())
            ->method('getMethod')
            ->willReturn('fedexaccount');
        $this->addressInformationMock->expects($this->any())
            ->method('getShippingDescription')
            ->willReturn('Fedex Shipment');
        $this->addressInformationMock->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');
        $this->addressInformationMock->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn('vibhanshu@infogain.com');
        $this->addressInformationMock->expects($this->any())
            ->method('getCustomerLastName')
            ->willReturn('sharma');
        $this->addressInformationMock->expects($this->any())
            ->method('getCustomerFirstName')
            ->willReturn('vibhanshu');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $result = $this->shipmentEmail->getTemplateOrderData($orderId, 'cancelled', $shipmentId);
        $this->assertTrue(!empty($result));
    }

    public function testGetBccEmailWithEmptyCustomerId()
    {
        $shipmentStatus = 'confirmed';
        $customerId = null;

        $testMethod = new \ReflectionMethod(ShipmentEmail::class, 'getBccEmail');
        $bccEmail = $testMethod->invoke($this->shipmentEmail, $shipmentStatus, $customerId);
        $this->assertEquals('' , $bccEmail);
    }

    public function testGetBccEmailWithNotConfirmedStatus()
    {
        $shipmentStatus = 'pending';
        $customerId = 123;

        $testMethod = new \ReflectionMethod(ShipmentEmail::class, 'getBccEmail');
        $bccEmail = $testMethod->invoke($this->shipmentEmail, $shipmentStatus, $customerId);
        $this->assertEquals('' , $bccEmail);
    }

    public function testGetBccEmailWithNoCompany()
    {
        $shipmentStatus = 'confirmed';
        $customerId = 123;
        $this->companyManager->method('getByCustomerId')->willReturn(null);
        $bccEmail = $this->shipmentEmail->getBccEmail($shipmentStatus, $customerId);
        $this->assertEquals('', $bccEmail);
    }

    public function testGetBccEmailWithValidCompany()
    {
        $shipmentStatus = 'confirmed';
        $customerId = 123;

        $companyMock = $this->getMockBuilder(Company::class)
        ->disableOriginalConstructor()
        ->setMethods(['getBccCommaSeperatedEmail'])
        ->getMockForAbstractClass();
        $companyMock->method('getBccCommaSeperatedEmail')->willReturn('test@example.com, second@example.com');

        $this->companyManager->method('getByCustomerId')->willReturn($companyMock);
        $bccEmail = $this->shipmentEmail->getBccEmail($shipmentStatus, $customerId);
        $expectedEmail = '"bcc":[
            {"address":"test@example.com"},
            {"address":"second@example.com"}
        ],';
        $this->assertEquals('', $bccEmail);
    }

    public function testGetBccEmailWithEmptyBccEmail()
    {
        $shipmentStatus = 'confirmed';
        $customerId = 123;

        $companyMock = $this->getMockBuilder(Company::class)
        ->disableOriginalConstructor()
        ->setMethods(['getBccCommaSeperatedEmail'])
        ->getMockForAbstractClass();
        $companyMock->method('getBccCommaSeperatedEmail')->willReturn('');

        $this->companyManager->method('getByCustomerId')->willReturn($companyMock);
        $bccEmail = $this->shipmentEmail->getBccEmail($shipmentStatus, $customerId);
        $this->assertEquals('', $bccEmail);
    }

    public function testGetConfirmationStatusWithNullCustomerId()
    {
        $shipmentStatus = 'confirmed';
        $customerId = null;
        $result = $this->shipmentEmail->getConfirmationstatus($shipmentStatus, $customerId);
        $this->assertTrue($result);
    }

    public function testGetConfirmationStatusWithNotConfirmedShipmentStatus()
    {
        $shipmentStatus = 'pending';
        $customerId = 123;
        $result = $this->shipmentEmail->getConfirmationstatus($shipmentStatus, $customerId);
        $this->assertTrue($result);
    }

    public function testGetConfirmationStatusWithCompanyNotFound()
    {
        $shipmentStatus = 'confirmed';
        $customerId = 123;

        $this->companyManager->method('getByCustomerId')->willReturn(null);
        $result = $this->shipmentEmail->getConfirmationstatus($shipmentStatus, $customerId);
        $this->assertTrue($result);
    }

    public function testGetConfirmationStatusWithCompanySuccessEmailEnabled()
    {
        $shipmentStatus = 'confirmed';
        $customerId = 123;

        $companyMock = $this->getMockBuilder(Company::class)
        ->disableOriginalConstructor()
        ->setMethods(['getIsSuccessEmailEnable'])
        ->getMockForAbstractClass();
        $companyMock->method('getIsSuccessEmailEnable')->willReturn(true);
        $this->companyManager->method('getByCustomerId')->willReturn($companyMock);
        $result = $this->shipmentEmail->getConfirmationstatus($shipmentStatus, $customerId);
        $this->assertTrue($result);
    }

    public function testGetConfirmationStatusWithCompanySuccessEmailDisabled()
    {
        $shipmentStatus = 'confirmed';
        $customerId = 123;

        $companyMock = $this->getMockBuilder(Company::class)
        ->disableOriginalConstructor()
        ->setMethods(['getIsSuccessEmailEnable'])
        ->getMockForAbstractClass();
        $companyMock->method('getIsSuccessEmailEnable')->willReturn(false);
        $this->companyManager->method('getByCustomerId')->willReturn($companyMock);
        $result = $this->shipmentEmail->getConfirmationstatus($shipmentStatus, $customerId);
        $this->assertTrue($result);
    }

    public function testGetTazEmailUrl()
    {
        $expectedUrl = 'https://example.com/taz/email/api';
        $this->configInterface->method('getValue')
            ->with('fedex/taz/taz_email_api_url')
            ->willReturn($expectedUrl);
        $result = $this->shipmentEmail->getTazEmailUrl();
        $this->assertEquals($expectedUrl, $result);
    }


    public function testGetTrackOrderUrl()
    {
        $expectedUrl = 'https://example.com/track/order';
        $this->configInterface->method('getValue')
            ->with('fedex/general/track_order_url', ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedUrl);
        $result = $this->shipmentEmail->getTrackOrderUrl();
        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetOrderShipmentByShipmentIdSuccess()
    {
        $shipmentId = 123;
        $expectedShipmentMock = $this->createMock(ShipmentInterface::class);
        $this->shipmentRepository->method('get')
            ->with($shipmentId)
            ->willReturn($expectedShipmentMock);
        $result = $this->shipmentEmail->getOrderShipmentByShipmentId($shipmentId);
        $this->assertSame($expectedShipmentMock, $result);
    }

    public function testGetOrderShipmentByShipmentIdThrowsException()
    {
        $shipmentId = 123;
        $this->shipmentRepository->method('get')
            ->with($shipmentId)
            ->will($this->throwException(new \Exception("Shipment not found")));
        $this->logger->expects($this->any())
            ->method('critical')
            ->with("Error while loading shipment Shipment not found");
        $result = $this->shipmentEmail->getOrderShipmentByShipmentId($shipmentId);
        $this->assertNull($result);
    }

    public function testGetTokenDataSuccess()
    {
        $expectedAuthToken = 'auth_token_value';
        $expectedAccessToken = 'access_token_value';
        $this->punchoutHelper->method('getAuthGatewayToken')
            ->willReturn($expectedAuthToken);
        $this->punchoutHelper->method('getTazToken')
            ->willReturn($expectedAccessToken);
        $result = $this->shipmentEmail->getTokenData();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('auth_token', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertEquals($expectedAuthToken, $result['auth_token']);
        $this->assertEquals($expectedAccessToken, $result['access_token']);
    }

    public function testGetTokenDataThrowsException()
    {
        $this->punchoutHelper->method('getAuthGatewayToken')
            ->willThrowException(new \Exception("Failed to get auth token"));
        $this->punchoutHelper->method('getTazToken')
            ->willReturn('any_value');
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to get auth token'));
        $result = $this->shipmentEmail->getTokenData();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('400', $result['code']);
        $this->assertEquals("Failed to get auth token", $result['message']);
    }
    public function testEscapeCharacterWithNormalString()
    {
        $input = "Hello, World! This is a test: #$%^&*()";
        $expected = "Hello, World This is a test ";
        $result = $this->shipmentEmail->escapeCharacter($input);
        $this->assertEquals($expected, $result);
    }

    public function testEscapeCharacterWithOnlyAllowedCharacters()
    {
        $input = "AllowedString123";
        $expected = "AllowedString123";
        $result = $this->shipmentEmail->escapeCharacter($input);
        $this->assertEquals($expected, $result);
    }

    public function testEscapeCharacterWithEmptyString()
    {
        $input = "";
        $expected = "";
        $result = $this->shipmentEmail->escapeCharacter($input);
        $this->assertEquals($expected, $result);
    }

    public function testEscapeCharacterWithOnlySpecialCharacters()
    {
        $input = "!@#$%^&*()_+";
        $expected = " ";
        $result = $this->shipmentEmail->escapeCharacter($input);
        $this->assertEquals($expected, $result);
    }

    public function testGetShipmentItemsList()
    {
        $orderData = [
            "shipment_items" => [
                ["name" => "Item A", "qty" => 2],
                ["name" => "Item B", "qty" => 3],
            ]
        ];
        $result = $this->shipmentEmail->getShipmentItemsList($orderData, '', 1);
        $this->assertNotNull($result);
    }

    public function testGetDateCompletionWithCompletionDate()
    {
        $orderCompletionDate = '2023-09-23 14:30:00';
        $estimatedDeliveryDuration = null;

        $expectedOutput = 'Sep 23, 2023 at 2:30 PM';
        $result = $this->shipmentEmail->getDateCompletion($orderCompletionDate, $estimatedDeliveryDuration);

        $this->assertEquals($expectedOutput, $result);
    }

    public function testGetDateCompletionWithEstimatedDeliveryDuration()
    {
        $orderCompletionDate = null;
        $estimatedDeliveryDuration = '2-3 business days';

        $expectedOutput = '2-3 business days';
        $result = $this->shipmentEmail->getDateCompletion($orderCompletionDate, $estimatedDeliveryDuration);

        $this->assertEquals($expectedOutput, $result);
    }

    public function testGetDateCompletionWithNoInputs()
    {
        $orderCompletionDate = null;
        $estimatedDeliveryDuration = null;

        $expectedOutput = 'NA';
        $result = $this->shipmentEmail->getDateCompletion($orderCompletionDate, $estimatedDeliveryDuration);

        $this->assertEquals($expectedOutput, $result);
    }

    public function testHandlingSdeEmailIssueWithMixedShipments()
    {
        $mixedShipments = [1, 2, 3];
        $incrementId = '12345';
        $shipmentMock = $this->createMock(Shipment::class);
        $this->helper->method('getShipmentById')->willReturn($shipmentMock);
        $this->shipmentRepository->expects($this->exactly(3))
            ->method('save')
            ->with($shipmentMock);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Email sent successfully for order: ' . $incrementId));
        $result = $this->shipmentEmail->handlingSdeEmailIssue(null, null, false, $incrementId, $mixedShipments);
        $this->assertEquals('sent', $result);
    }

    public function testHandlingSdeEmailIssueWithCancelledShipment()
    {
        $incrementId = '12345';
        $this->shipmentInterface->method('setIsCancellationEmailSent')->willReturnSelf();
        $this->shipmentInterface->method('setIsCompletionEmailSent')->willReturnSelf();
        $shipmentStatus = 'cancelled';
        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipmentInterface);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Email sent successfully for order: ' . $incrementId));
        $result = $this->shipmentEmail->handlingSdeEmailIssue($this->shipmentInterface, $shipmentStatus, false, $incrementId, []);
        $this->assertEquals('sent', $result);
    }

    public function testHandlingSdeEmailIssueWithDeliveredShipment()
    {
        $incrementId = '12345';
        $this->shipmentInterface->method('setIsCancellationEmailSent')->willReturnSelf();
        $this->shipmentInterface->method('setIsCompletionEmailSent')->willReturnSelf();
        $shipmentStatus = 'delivered';
        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipmentInterface);
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Email sent successfully for order: ' . $incrementId));
        $result = $this->shipmentEmail->handlingSdeEmailIssue($this->shipmentInterface, $shipmentStatus, false, $incrementId, []);
        $this->assertEquals('sent', $result);
    }

    public function testIsMixedOrderCancellationsReturnsTrue()
    {
        $shipmentStatus = "cancelled";
        $this->helper->method('isMixedOrder')->willReturn(true);
        $reflectionMethod = new \ReflectionMethod(ShipmentEmail::class, 'isMixedOrderCancellations');
        $reflectionMethod->setAccessible(true);
        $this->assertTrue($reflectionMethod->invoke($this->shipmentEmail, $shipmentStatus, $this->order));
    }

    public function testIsMixedOrderCancellationsReturnsFalseWhenNotCancelled()
    {
        $shipmentStatus = "shipped";
        $reflectionMethod = new \ReflectionMethod(ShipmentEmail::class, 'isMixedOrderCancellations');
        $reflectionMethod->setAccessible(true);
        $this->assertFalse($reflectionMethod->invoke($this->shipmentEmail, $shipmentStatus, $this->order));
    }

    public function testIsMixedOrderCancellationsReturnsFalseWhenNotMixedOrder()
    {
        $shipmentStatus = "cancelled";
        $this->helper->method('isMixedOrder')->willReturn(false);
        $reflectionMethod = new \ReflectionMethod(ShipmentEmail::class, 'isMixedOrderCancellations');
        $reflectionMethod->setAccessible(true);
        $this->assertFalse($reflectionMethod->invoke($this->shipmentEmail, $shipmentStatus, $this->order));
    }

    public function testGetMarketplaceIndividualShippingAmountReturnsArray()
    {
        $expectedShippingAmounts = [
            'seller1' => 5.00,
            'seller2' => 10.00,
        ];
        $this->marketplaceRatesHelper
            ->method('getMktShippingTotalAmountPerItem')
            ->with($this->order)
            ->willReturn($expectedShippingAmounts);
        $reflectionMethod = new \ReflectionMethod(ShipmentEmail::class, 'getMarketplaceIndividualShippingAmount');
        $reflectionMethod->setAccessible(true);
        $this->assertEquals($expectedShippingAmounts, $reflectionMethod->invoke($this->shipmentEmail, $this->order));
    }

    public function testGetMarketplaceIndividualShippingAmountReturnsEmptyArray()
    {
        $this->marketplaceRatesHelper
            ->method('getMktShippingTotalAmountPerItem')
            ->with($this->order)
            ->willReturn(null);
        $reflectionMethod = new \ReflectionMethod(ShipmentEmail::class, 'getMarketplaceIndividualShippingAmount');
        $reflectionMethod->setAccessible(true);
        $this->assertEquals([], $reflectionMethod->invoke($this->shipmentEmail, $this->order));
    }
}

