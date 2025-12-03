<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Helper;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Model\Shop as ShopManagement;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\OrderApprovalB2b\Helper\OrderEmailHelper;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Company\Model\Company;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\CIDPSG\Helper\Email;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Model\Order;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order\Item;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order\Address;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Test case OrderEmailHelperTest Class
 */
class OrderEmailHelperTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $adminConfigHelperMock;
    protected $emailMock;
    protected $orderMock;
    protected $countryFactory;
    protected $countryCollectionMock;
    protected $uploadToQuoteViewModel;
    protected $itemMock;
    protected $attributeValueMock;
    protected $orderEmailHelper;
    private const ADDITIONAL_DATA_MOCK = '{
        "mirakl_shipping_data": {
            "address": {
                "altFirstName": "John",
                "altLastName": "Doe",
                "city": "San Francisco",
                "countryId": "US",
                "region": "CA",
                "street": ["123 Main St", "Apt 4"],
                "postcode": "94105",
                "altPhoneNumber": "555-555-5555",
                "company": "Acme Corp",
                "altEmail": "john@doe.com",
                "customAttributes": {
                 "attribute_code": "email_id",
                 "value": "test@test.com"
                }
            }
        }
    }';

    /**
     * @var AdminConfigHelper|MockObject
     */
    protected $adminConfigHelper;

    /**
     * @var Email|MockObject
     */
    protected $email;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    protected $orderRepositoryMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $timezoneInterface;

    /**
     * @var ProductInfoHandler|MockObject
     */
    protected $productInfoHandler;

    /**
     * @var ShopManagement|MockObject
     */
    protected $shopManagementMock;

    /**
     * @var Template|MockObject
     */
    protected $templateMock;

    /**
     * @var InfoInterface|MockObject
     */
    protected $paymentinfoInterface;

    /**
     * @var Country|MockObject
     */
    protected $countryMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepositoryMock;

    /**
     * @var Product|MockObject
     */
    protected $productMock;

    /**
     * @var ShopInterface|MockObject
     */
    protected $shopInterface;

    /**
     * @var Address|MockObject
     */
    protected $shippingAddressMock;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    protected $companyRepository;

    /**
     * @var SelfReg|MockObject
     */
    protected $selfRegHelper;

    /**
     * @var Company|MockObject
     */
    protected $companyMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var CustomerInterface|MockObject
     */
    protected $customerInterfaceMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getB2bOrderEmailTemplate',
                'convertPrice',
                'isOrderApprovalB2bEnabled',
                'isB2bOrderEmailEnabled',
                'isB2bOrderEmailEnabledForExpireCronEmail'

            ])->getMockForAbstractClass();

        $this->shopManagementMock = $this->getMockBuilder(ShopManagement::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShopByProduct'])
            ->getMockForAbstractClass();

        $this->emailMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadEmailTemplate','callGenericEmailApi','sendEmail'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore','getId','getBaseUrl','getCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyUrlExtention'])
            ->getMockForAbstractClass();

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMockForAbstractClass();

        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format','setTimezone'])
            ->getMockForAbstractClass();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCustomerEmail',
                'getCreatedAt',
                'getAllVisibleItems',
                'getItems',
                'getShippingAddress',
                'getBillingAddress',
                'getPayment',
                'getDiscountAmount',
                'getShippingMethod',
                'getShippingAmount',
                'getShippingDescription',
                'getMiraklIsOfferInclTax',
                'getCustomerLastName',
                'getCustomerFirstName',
            ])->getMockForAbstractClass();

        $this->shippingAddressMock = $this->createMock(Address::class);
        $this->shippingAddressMock->expects($this->any())->method('getData')->willReturnOnConsecutiveCalls(
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
        $this->orderMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddressMock);
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
        )->disableOriginalConstructor()
            ->setMethods(['getName'])->getMock();
        $this->countryFactory->expects($this->any())->method('create')->willReturn($this->countryMock);
        $this->countryMock->expects($this->any())->method('loadByCode')->willReturn($this->countryCollectionMock);
        $this->uploadToQuoteViewModel = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isProductLineItems', 'isItemPriceable','getPriceDash','isQuotePriceable'])
            ->getMockForAbstractClass();
        $this->productInfoHandler = $this->getMockBuilder(ProductInfoHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemExternalProd'])
            ->getMockForAbstractClass();
        $this->paymentinfoInterface = $this->getMockBuilder(InfoInterface::class)
            ->setMethods(
                [
                    'getMethodInstance',
                    'getTitle',
                    'getFedexAccountNumber',
                    'getMethod',
                    'getRetailTransactionId',
                    'getCcLast4',
                    'getCcOwner'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getName',
                    'getQtyOrdered',
                    'getRowTotalInclTax',
                    'getOptionByCode',
                    'getValue',
                    'getMiraklShopId',
                    'getMiraklOfferId',
                    'getProduct',
                    'getAdditionalData'
                ]
            )
            ->disableOriginalconstructor()
            ->getMock();
        $this->shopInterface = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSellerAltName'])
            ->getMockForAbstractClass();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getFirstname', 'getCustomAttribute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->setMethods(['getByCustomerId','getCompanyUrlExtention'])
            ->disableOriginalconstructor()
            ->getMockForAbstractClass();

        $this->selfRegHelper = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['getCompanyUserPermission'])
            ->disableOriginalconstructor()
            ->getMock();

        $this->companyMock = $this->getMockBuilder(Company::class)
            ->setMethods(['getId','getCompanyUrlExtention'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->attributeValueMock = $this->getMockBuilder(\Magento\Customer\Model\AttributeValue::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->orderEmailHelper = $objectManagerHelper->getObject(
            OrderEmailHelper::class,
            [
                'context' => $this->contextMock,
                'logger'=>$this->loggerMock,
                'adminConfigHelper'=>$this->adminConfigHelperMock,
                'email' => $this->emailMock,
                'storeManager' => $this->storeManager,
                'orderRepository' => $this->orderRepositoryMock,
                'timezoneInterface' =>$this->timezoneInterface,
                'productInfoHandler' => $this->productInfoHandler,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModel,
                'countryFactory' => $this->countryFactory,
                'shopInterface' => $this->shopInterface,
                'companyRepository' => $this->companyRepository,
                'selfRegHelper' => $this->selfRegHelper,
                'customerRepositoryInterface' => $this->customerRepositoryMock,
                'customerInterfaceMock' => $this->customerInterfaceMock
            ]
        );
        $this->orderEmailHelper->status = 'confirmed';
    }

    /**
     * Test method for sendQuoteGenericEmail function
     *
     * @return void
     */
    public function testSendOrderGenericEmail()
    {
        $orderData = ['order_id' => 41193, 'status' => 'confirmed'];
        $this->orderRepositoryMock->method('get')->willReturn($this->orderMock);
        $this->orderMock->method('getCustomerEmail')->willReturn('customer@example.com');
        $this->orderMock->method('getCreatedAt')->willReturn('2023-01-01 00:00:00');
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->orderMock->method('getItems')->willReturn([$this->itemMock]);
        $this->orderMock->method('getMiraklIsOfferInclTax')->willReturn(10);
        $this->orderMock->method('getDiscountAmount')->willReturn(1.2);
        $this->orderMock->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->orderMock->method('getShippingAmount')->willReturn(10);
        $this->orderMock->method('getShippingDescription')->willReturn('Fedex');
        $this->orderMock->method('getCustomerFirstName')->willReturn('John');
        $this->orderMock->method('getCustomerLastName')->willReturn('Dan');
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->itemMock->method('getMiraklShopId')->willReturn(123);
        $this->itemMock->method('getMiraklOfferId')->willReturn(123);
        $this->itemMock->method('getName')->willReturn('Product Name');
        $this->itemMock->method('getQtyOrdered')->willReturn(2);
        $this->itemMock->method('getRowTotalInclTax')->willReturn(19.99);
        $this->uploadToQuoteViewModel->method('isItemPriceable')->willReturn(true);
        $this->uploadToQuoteViewModel->method('isQuotePriceable')->willReturn(true);
        $this->adminConfigHelperMock->method('convertPrice')->willReturn('100');
        $this->storeManager->method('getId')->willReturn(45);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->emailMock->method('callGenericEmailApi')->willReturnSelf();
        $this->shopManagementMock->method('getShopByProduct')->willReturn($this->shopInterface);
        $this->adminConfigHelperMock->method('isOrderApprovalB2bEnabled')
            ->willReturn(true);
        $this->adminConfigHelperMock->method('isB2bOrderEmailEnabled')
            ->willReturn(true);
        $this->timezoneInterface->method('date')->willReturnSelf();
        $this->timezoneInterface->method('setTimezone')->willReturnSelf();
        $this->timezoneInterface->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->companyMock->method('getId')->willReturn(1);
        $this->companyRepository->method('getByCustomerId')
            ->willReturn($this->companyMock);
        $this->customerRepositoryMock->method('getById')
            ->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn('test');
        $this->customerInterfaceMock->expects($this->any())
        ->method('getCustomAttribute')->willReturn($this->attributeValueMock);
        $result = $this->orderEmailHelper->sendOrderGenericEmail($orderData);
        $this->testSendOrderGenericEmailReview();
        $this->testSendOrderGenericEmailDecline();
        $this->testSendOrderGenericEmailExpired();

        $this->assertNull($result);
    }

    /**
     * Test method for sendQuoteGenericEmailReview function
     *
     * @return void
     */
    public function testSendOrderGenericEmailReview()
    {
        $status = 'review';
        $adminReviewFlag = 1;
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->timezoneInterface->method('date')->willReturnSelf();
        $this->timezoneInterface->method('setTimezone')->willReturnSelf();
        $this->timezoneInterface->method('format')
            ->will($this->onConsecutiveCalls('2023-01-01', '2023-02-28'));
        $this->orderMock->expects($this->any())->method('getPayment')
            ->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->adminConfigHelperMock->method('isB2bOrderEmailEnabled')
            ->willReturn(true);
        $this->companyMock->method('getId')->willReturn(1);
        $this->companyRepository->method('getByCustomerId')->willReturn($this->companyMock);
        $this->customerRepositoryMock->method('getById')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn('test');
        $this->customerInterfaceMock->expects($this->any())
            ->method('getCustomAttribute')->willReturn($this->attributeValueMock);
        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(true);

        $this->assertNull($this->orderEmailHelper->prepareGenericEmailData(
            $this->orderMock,
            $status,
            $adminReviewFlag
        ));
    }

    /**
     * Test method for sendQuoteGenericEmailDecline function
     *
     * @return void
     */
    public function testSendOrderGenericEmailDecline()
    {
        $orderData = ['order_id' => 4113, 'status' => 'decline'];
        $result = $this->orderEmailHelper->sendOrderGenericEmail($orderData);

        $this->assertNull($result);
    }

    /**
     * Test method for sendQuoteGenericEmailExpired function
     *
     * @return void
     */
    public function testSendOrderGenericEmailExpired()
    {
        $orderData = ['order_id' => 4113, 'status' => 'expired'];
        $result = $this->orderEmailHelper->sendOrderGenericEmail($orderData);

        $this->assertNull($result);
    }

    /**
     * Test method for prepareGenericEmailDataReview function
     *
     * @return void
     */
    public function testPrepareGenericEmailDataReview()
    {
        $status = 'review';
        $adminReviewFlag = 1;
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->timezoneInterface->method('date')->willReturnSelf();
        $this->timezoneInterface->method('setTimezone')->willReturnSelf();
        $this->timezoneInterface->method('format')
            ->will($this->onConsecutiveCalls('2023-01-01', '2023-02-28'));
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->companyMock->method('getId')->willReturn(1);
        $this->companyRepository->method('getByCustomerId')->willReturn($this->companyMock);
        $this->customerRepositoryMock->method('getById')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn('test');
        $this->customerInterfaceMock->expects($this->any())
            ->method('getCustomAttribute')->willReturn($this->attributeValueMock);
        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(true);
        $this->adminConfigHelperMock->method('isB2bOrderEmailEnabled')
            ->willReturn(true);

        $this->assertNull($this->orderEmailHelper->prepareGenericEmailData(
            $this->orderMock,
            $status,
            $adminReviewFlag
        ));
    }

    /**
     * Test method for prepareGenericEmailData function
     *
     * @return void
     */
    public function testPrepareGenericEmailData()
    {
        $status = 'confirmed';
        $adminReviewFlag = 0;
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->timezoneInterface->method('date')->willReturnSelf();
        $this->timezoneInterface->method('setTimezone')->willReturnSelf();
        $this->timezoneInterface->method('format')
            ->will($this->onConsecutiveCalls('2023-01-01', '2023-02-28'));
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->customerRepositoryMock->method('getById')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn('test');
        $this->customerInterfaceMock->expects($this->any())
            ->method('getCustomAttribute')->willReturn($this->attributeValueMock);
        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(true);
        $this->testPrepareGenericEmailDataReview();
        $this->testPrepareGenericEmailRequestReview();

        $this->assertNull($this->orderEmailHelper->prepareGenericEmailData(
            $this->orderMock,
            $status,
            $adminReviewFlag
        ));
    }

    /**
     * Test method for prepareGenericEmailRequest function
     *
     * @return void
     */
    public function testPrepareGenericEmailRequest()
    {
        $this->adminConfigHelperMock->expects($this->any())->method('getB2bOrderEmailTemplate')
            ->willReturnSelf();
        $this->emailMock->expects($this->any())->method('loadEmailTemplate')->willReturn("Test");
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
        $this->orderRepositoryMock->method('get')->willReturn($this->orderMock);
        $this->orderMock->method('getCustomerEmail')->willReturn('customer@example.com');
        $this->orderMock->method('getCreatedAt')->willReturn('2023-01-01 00:00:00');
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->orderMock->method('getItems')->willReturn([$this->itemMock]);
        $this->orderMock->method('getMiraklIsOfferInclTax')->willReturn(10);
        $this->orderMock->method('getDiscountAmount')->willReturn(1.2);
        $this->orderMock->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->orderMock->method('getShippingAmount')->willReturn(10);
        $this->orderMock->method('getShippingDescription')->willReturn('Fedex');
        $this->orderMock->method('getCustomerFirstName')->willReturn('John');
        $this->orderMock->method('getCustomerLastName')->willReturn('Dan');
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->itemMock->method('getMiraklShopId')->willReturn(123);
        $this->itemMock->method('getMiraklOfferId')->willReturn(123);
        $this->itemMock->method('getName')->willReturn('Product Name');
        $this->itemMock->method('getQtyOrdered')->willReturn(2);
        $this->itemMock->method('getRowTotalInclTax')->willReturn(19.99);
        $this->timezoneInterface->method('date')->willReturnSelf();
        $this->timezoneInterface->method('setTimezone')->willReturnSelf();
        $this->timezoneInterface->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $status = 'confirmed';
        $email = 'test@test.com';
        $userName = 'Test';

        $this->assertNotNull($this->orderEmailHelper->prepareGenericEmailRequest(
            $this->orderMock,
            $status,
            $email,
            $userName
        ));
    }

    /**
     * Test method for prepareGenericEmailRequest function
     *
     * @return void
     */
    public function testPrepareGenericEmailRequestReview()
    {
        $status = 'review';
        $email = 'test@test.com';
        $userName = 'Test';
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);
        $this->orderMock->method('getItems')->willReturn([$this->itemMock]);
        $storeUrl = "https://www.fedex.com";
        $this->storeManager->method('getStore')->will($this->returnSelf());
        $this->storeManager->method('getBaseUrl')->willReturn($storeUrl);
        $this->storeManager->method('getCode')->willReturn("ondemand");
        $this->timezoneInterface->method('date')->willReturnSelf();
        $this->timezoneInterface->method('setTimezone')->willReturnSelf();
        $this->timezoneInterface->method('format')
            ->will($this->onConsecutiveCalls('2023-03-01', '2023-03-28'));
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->orderMock->expects($this->any())->method('getPayment')
            ->willReturn($this->paymentinfoInterface);

        $this->assertNotNull($this->orderEmailHelper->prepareGenericEmailRequest(
            $this->orderMock,
            $status,
            $email,
            $userName
        ));
    }

    /**
     * Test method for getTemplateId function
     *
     * @return void
     */
    public function testGetTemplateId()
    {
        $this->adminConfigHelperMock->method('getB2bOrderEmailTemplate')
            ->willReturn('confirmation_template_id');

        $this->assertEquals('confirmation_template_id', $this->orderEmailHelper->getTemplateId('confirmed'));
    }

    /**
     * Test method for getTemplateIdDecline function
     *
     * @return void
     */
    public function testGetTemplateIdDecline()
    {
        $this->adminConfigHelperMock->method('getB2bOrderEmailTemplate')
            ->willReturn('decline_template_id');

        $this->assertEquals('decline_template_id', $this->orderEmailHelper->getTemplateId('decline'));
    }

    /**
     * Test method for getTemplateIdReview function
     *
     * @return void
     */
    public function testGetTemplateIdReview()
    {
        $this->adminConfigHelperMock->method('getB2bOrderEmailTemplate')
            ->willReturn('review_template_id');

        $this->assertEquals('review_template_id', $this->orderEmailHelper->getTemplateId('review'));
    }

    /**
     * Test method for getTemplateIdExpired function
     *
     * @return void
     */
    public function testGetTemplateIdExpired()
    {
        $this->adminConfigHelperMock->method('getB2bOrderEmailTemplate')
            ->willReturn('expired_template_id');

        $this->assertEquals('expired_template_id', $this->orderEmailHelper->getTemplateId('expired'));
    }

    /**
     * Test method for getOrderEmailSubject function
     *
     * @return void
     */
    public function testGetOrderEmailSubject()
    {
        $this->testGetOrderEmailSubjectDecline();
        $this->testGetOrderEmailSubjectReview();

        $this->assertNotNull($this->orderEmailHelper->getOrderEmailSubject('confirmed'));
    }

    /**
     * Test method for getOrderEmailSubjectDecline function
     *
     * @return void
     */
    public function testGetOrderEmailSubjectDecline()
    {
        $this->assertNotNull($this->orderEmailHelper->getOrderEmailSubject('decline'));
    }

    /**
     * Test method for getOrderEmailSubjectReview function
     *
     * @return void
     */
    public function testGetOrderEmailSubjectReview()
    {
        $this->assertNotNull($this->orderEmailHelper->getOrderEmailSubject('review'));
    }

    /**
     * Test method for getOrderEmailSubjectExpired function
     *
     * @return void
     */
    public function testGetOrderEmailSubjectExpired()
    {
        $this->assertNotNull($this->orderEmailHelper->getOrderEmailSubject('expired'));
    }

    /**
     * Test method for getOrderInformationData function
     *
     * @return void
     */
    public function testGetOrderInformationData()
    {
        $this->orderMock->expects($this->any())->method('getPayment')->willReturn($this->paymentinfoInterface);
        $this->paymentinfoInterface->expects($this->any())
            ->method('getRetailTransactionId')
            ->willReturn("ADSKD2B3645A20F607X");
        $this->paymentinfoInterface->expects($this->any())
            ->method('getCcLast4')
            ->willReturn("1234");
        $this->paymentinfoInterface->expects($this->any())
            ->method('getCcOwner')
            ->willReturn("John Dan");
        $this->orderMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddressMock);

        $this->assertNotNull($this->orderEmailHelper->getOrderInformationData($this->orderMock));
    }

    /**
     * Test method for hasAlternateContact function
     *
     * @return void
     */
    public function testHasAlternateContact()
    {
        $this->assertNotNull($this->orderEmailHelper->hasAlternateContact($this->orderMock));
    }

    /**
     * Test method for getFormattedBillingShipping function
     *
     * @return void
     */
    public function testGetFormattedBillingShipping()
    {
        $this->orderMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn("fedexshipping_PICKUP");
        $this->orderMock->expects($this->any())
            ->method('getShippingDescription')
            ->willReturn("Fedex - Test");
        $this->orderMock->expects($this->any())
            ->method('getShippingAmount')
            ->willReturn(8.00);
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);

        $this->assertNotNull($this->orderEmailHelper->getFormattedBillingShipping($this->orderMock, true));
    }

    /**
     * Test method for getFormattedPickup function
     *
     * @return void
     */
    public function testGetFormattedPickup()
    {
        $this->itemMock->expects($this->once())->method('getAdditionalData')->willReturn(self::ADDITIONAL_DATA_MOCK);
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);

        $this->assertNotNull($this->orderEmailHelper->getFormattedPickup($this->orderMock));
    }

    /**
     * Test method for getFormattedShippingAddressArray function
     *
     * @return void
     */
    public function testGetFormattedShippingAddressArray()
    {
        $this->itemMock->method('getMiraklOfferId')->willReturn(123);
        $this->orderMock->method('getItems')->willReturn($this->itemMock);

        $this->assertNotNull($this->orderEmailHelper->getFormattedShippingAddressArray($this->orderMock));
    }

    /**
     * Test method for getFormattedAddressArray function
     *
     * @return void
     */
    public function testGetFormattedAddressArray()
    {
        $this->orderMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn("fedexshipping_PICKUP");
        $this->itemMock->method('getMiraklOfferId')->willReturn(123);
        $this->orderMock->method('getItems')->willReturn([$this->itemMock]);

        $this->assertNotNull($this->orderEmailHelper->getFormattedAddressArray($this->orderMock, false));
    }

    /**
     * Test method for formatOrderItems function
     *
     * @return void
     */
    public function testFormatOrderItems()
    {
        $this->orderMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddressMock);
        $this->orderMock->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn("fedexshipping_PICKUP");
        $this->orderMock->expects($this->any())
            ->method('getShippingDescription')
            ->willReturn("Fedex - Test");
        $this->orderMock->expects($this->any())
            ->method('getShippingAmount')
            ->willReturn(8.00);
        $this->orderMock->method('getAllVisibleItems')->willReturn([$this->itemMock]);

        $this->assertNotNull($this->orderEmailHelper->formatOrderItems($this->orderMock));
    }

    /**
     * Test Get company url extension
     *
     * @return void
     */
    public function testGetCompanyExtensionUrl()
    {
        $customerId  = 1;
        $orderUrl   = '/sales/order/view/order_id/1';
        $storeUrl = "https://www.fedex.com/";
        $url = $storeUrl.$orderUrl;
        $this->storeManager->method('getStore')->will($this->returnSelf());
        $this->storeManager->method('getBaseUrl')->willReturn($storeUrl);
        $this->storeManager->method('getCode')->willReturn("ondemand");

        $this->assertEquals(
            $url,
            $this->orderEmailHelper->getCompanyExtensionUrl($customerId, $orderUrl, $storeUrl)
        );
    }

    /**
     * Test Get company url extension
     *
     * @return void
     */
    public function testGetCompanyExtensionUrlIf()
    {
        $customerId  = 1;
        $orderUrl   = 'sales/order/view/order_id/1';
        $storeUrl = "https://www.fedex.com/";
        $this->storeManager->method('getStore')->will($this->returnSelf());
        $this->storeManager->method('getBaseUrl')->willReturn($storeUrl);
        $this->storeManager->method('getCode')->willReturn("ondemand");
        $this->companyMock->method('getId')->willReturn(1);
        $this->companyMock->method('getCompanyUrlExtention')->willReturn('b2b_order');
        $this->companyRepository->method('getByCustomerId')->willReturn($this->companyMock);

        $this->assertNotNull(
            $this->orderEmailHelper->getCompanyExtensionUrl($customerId, $orderUrl, $storeUrl)
        );
    }

    /**
     * Test Get company user detail not null
     *
     * @return void
     */
    public function testGetCompanyAdminUserDetailNotNull()
    {
        $customerId = 1;
        $this->companyMock->method('getId')->willReturn(1);
        $this->companyRepository->method('getByCustomerId')->willReturn($this->companyMock);
        $this->customerInterfaceMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn('test');
        $this->selfRegHelper->expects($this->any())
            ->method('getCompanyUserPermission')
            ->willReturn([1,2]);
        $this->customerRepositoryMock->method('getById')->willReturn($this->customerInterfaceMock);
        $this->customerInterfaceMock->expects($this->any())
        ->method('getCustomAttribute')->willReturn($this->attributeValueMock);
        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(true);

        $this->assertNotNull(
            $this->orderEmailHelper->getCompanyAdminUserDetail($customerId)
        );
    }
}
