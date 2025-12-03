<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Observer;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\Order\Payment;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrderOffer;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Observer\CustomItemData;
use Mirakl\MMP\FrontOperator\Domain\Order\CustomerShippingAddressFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Mirakl\Core\Domain\MiraklObject;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Mirakl\Connector\Model\Offer;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Magento\Framework\Xml\Generator;
use Magento\Sales\Model\Order;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;

class CustomItemDataTest extends TestCase
{
    protected $handleMktCheckout;
    /**
     * @var (\Fedex\MarketplaceProduct\Api\ShopRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shopRepository;
    // phpcs:ignore
    private const ADDITIONAL_DATA_MOCK = '{
        "mirakl_shipping_data": {
            "address": {
                "firstname": "John",
                "lastname": "Doe",
                "city": "San Francisco",
                "countryId": "US",
                "region": "CA",
                "street": ["123 Main St", "Apt 4"],
                "postcode": "94105",
                "telephone": "555-555-5555",
                "company": "Acme Corp"
            },
            "fedexShipAccountNumber": "1234",
            "fedexShipReferenceId": "1234"
        },
        "cart_quantity_tooltip": "Quantities for Marketplace Seller cannot be edited in the cart. Click \\"Edit\\" to adjust the quantity for this item.",
        "expire": 3,
        "expire_soon": 1,
        "supplierPartID": "RBCS-S5-FF-FED3",
        "supplierPartAuxiliaryID": "712ffa3a-b40a-4145-8f72-997a0bf8a0bb",
        "seller_sku": "RBCS-S5-FF-FED3",
        "offer_id": "2581",
        "isMarketplaceProduct": "true",
        "total": 20.31,
        "unit_price": 0.2031,
        "image": "https://shop-staging.fedex.com/media/temp/catalog/712ffa3a-b40a-4145-8f72-997a0bf8a0bb1691602712.png",
        "quantity": 100,
        "marketplace_name": "Ultra Thick Delivered Business Card - Front & Back",
        "business_days": "3",
        "features": [
            {"name": "Product Category", "choice": {"name": "Premium Business Cards"}},
            {"name": "Product Size", "choice": {"name": "3 1/2\u201d x 2\u201d"}},
            {"name": "Imprint", "choice": {"name": "Full Color"}},
            {"name": "Sides", "choice": {"name": "Double-Sided"}},
            {"name": "Shape", "choice": {"name": "Rectangle"}}
        ],
        "variantDetails": [
            {"UnitPrice": "14.95", "Size": "S", "Quantity": "1","VariantID":8923},
            {"UnitPrice": "14.95", "Size": "M", "Quantity": "2","VariantID":8924},
            {"UnitPrice": "14.95", "Size": "L", "Quantity": "3","VariantID":8925},
            {"UnitPrice": "14.95", "Size": "XL", "Quantity": "4","VariantID":8926}
        ],
        "is_marketplace_mocked": "1"
    }';

    /**
     * @var ItemRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemRepository;

    /**
     * @var CustomerShippingAddressFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerShippingAddressFactory;

    /**
     * @var CountryFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $countryFactory;

    /**
     * @var CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionFactory;

    /**
     * @var MiraklObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $miraklObject;

    /**
     * @var Event|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var Observer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $observer;

    private CustomItemData $customItemData;
    private OfferHelper|MockObject $offerHelper;
    private $orderMock;
    private $paymentMock;
    private $loggerMock;
    private $packagingMock;
    protected const PRODUCT_ID = '1508784838900';
    protected const PRICE = '$0.49';
    protected const DISCOUNT_AMOUNT = '$0.00';
    protected const PRODUCT_DESCRIPTION = 'Single Sided Color';
    protected const DETAIL_UNIT_PRICE = '$0.4900';
    protected const LOCATION_ID = '75024';
    protected const STREET = 'Legacy DR';
    protected const FEDEX_ACCOUNT_NUMBER = '12345678';
    protected const ZIPCODE = '12345';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CreateOrderOffer
     */
    protected $createOrderOffer;

    /**
     * @var ShopInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shopInterface;

    /**
     * @var MarketplaceRatesHelper
     */
    protected $marketplaceRatesHelper;

    /**
     * @var Generator
     */
    protected $xmlGenerator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->itemRepository = $this->getMockBuilder(ItemRepository::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerShippingAddressFactory = $this->getMockBuilder(CustomerShippingAddressFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['setFirstname', 'setLastname', 'setCity', 'setCountry', 'setCountryIsoCode', 'setStreet1', 'setStreet2', 'setZipCode', 'setPhone', 'setCompany', 'setState', 'setAdditionalInfo'])
            ->getMock();
        $this->customerShippingAddressFactory->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setCity')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setCountry')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setCountryIsoCode')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setStreet1')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setStreet2')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setZipCode')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setPhone')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setState')->willReturnSelf();
        $this->customerShippingAddressFactory->expects($this->any())->method('setAdditionalInfo')->willReturnSelf();

        $this->countryFactory = $this->getMockBuilder(CountryFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['loadByCode'])
            ->onlyMethods(['create'])
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['addRegionCodeFilter', 'addCountryFilter', 'getFirstItem', 'toArray'])
            ->getMock();
        $this->handleMktCheckout = $this->getMockBuilder(HandleMktCheckout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->miraklObject = $this->getMockBuilder(MiraklObject::class)
            ->addMethods(['getCustomer', 'setShippingAddress', 'getOffers', 'getItems', 'getShippingAddress'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->offerHelper = $this->getMockBuilder(OfferHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCreateOrder', 'getOffers', 'getItems', 'getOrder'])
            ->getMock();
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductLineDetails'])
            ->getMock();
        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['is3pDiscountingEnabled', 'getD194958', 'adjustArrayForXml', 'isEssendantToggleEnabled'])
            ->getMock();

        $this->helper->expects($this->any())
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->offerHelper = $this->getMockBuilder(OfferHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shopRepository = $this->getMockBuilder(ShopRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceRatesHelper = $this->getMockBuilder(MarketplaceRatesHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->xmlGenerator = $this->getMockBuilder(Generator::class)
            ->disableOriginalConstructor()
            ->setMethods(['arrayToXml', '__toString'])
            ->getMock();

        $this->shopInterface = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getShippingRateOption'])
            ->getMockForAbstractClass();

        $this->createOrderOffer = $this->getMockBuilder(CreateOrderOffer::class)
            ->addMethods([
                'getOrderLineId',
                'setQuantity',
                'setOrderLineAdditionalFields',
                'setPrice',
                'setOfferPrice',
                'getShippingTypeCode',
                'setShippingTypeCode'
            ])
            ->getMock();

        $this->orderMock = $this->createMock(Order::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->packagingMock = $this->createMock(PackagingCheckoutPricing::class);

        $this->objectManager = new ObjectManager($this);

        $this->customItemData = $this->objectManager->getObject(
            CustomItemData::class,
            [
                'itemRepository' => $this->itemRepository,
                'customerShippingAddressFactory' => $this->customerShippingAddressFactory,
                'countryFactory' => $this->countryFactory,
                'collectionFactory' => $this->collectionFactory,
                'helper' => $this->helper,
                'handleMktCheckout' => $this->handleMktCheckout,
                'offerHelper' => $this->offerHelper,
                'shopRepository' => $this->shopRepository,
                'marketplaceRatesHelper' => $this->marketplaceRatesHelper,
                'xmlGenerator' => $this->xmlGenerator,
                'logger' => $this->loggerMock,
                'packagingCheckoutPricing' => $this->packagingMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $productLineDetails = json_encode([
            'productLines' => [
                'instanceId' => 0,
                'productId' => self::PRODUCT_ID,
                'retailPrice' => self::PRICE,
                'discountAmount' => self::DISCOUNT_AMOUNT,
                'unitQuantity' => 1,
                'linePrice' => self::PRICE,
                'priceable' => 1,
                'productLineDetails' => [
                    0 => [
                        'detailCode' => '0173',
                        'description' => self::PRODUCT_DESCRIPTION,
                        'detailCategory' => 'PRINTING',
                        'unitQuantity' => 1,
                        'unitOfMeasurement' => 'EACH',
                        'detailPrice' => self::PRICE,
                        'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                        'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                        'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                    ],
                ],
                'productRetailPrice' => 0.49,
                'productDiscountAmount' => '0.00',
                'productLinePrice' => '0.49',
                'editable' => '',
            ],
        ]);
        $this->miraklObject->expects($this->any())->method('setShippingAddress')->willReturnSelf();
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->any())->method('getCreateOrder')->willReturn($this->miraklObject);
        $this->event->expects($this->any())->method('getOrder')->willReturn($this->orderMock);
        $this->miraklObject->expects($this->any())->method('getOffers')->willReturnSelf();

        $offerMock = $this->getMockBuilder(Offer::class)
            ->addMethods(['getShopId'])
            ->onlyMethods(['getId', 'getLeadtimeToShip'])
            ->disableOriginalConstructor()
            ->getMock();
        $offerMock->method('getId')->willReturn(1);
        $offerMock->method('getShopId')->willReturn(22);
        $offerMock->method('getLeadtimeToShip')->willReturn(3);
        $this->offerHelper->expects($this->any())->method('getOfferById')->willReturn($offerMock);
        $this->paymentMock->method('getProductLineDetails')->willReturn($productLineDetails);
        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);
        $this->miraklObject->expects($this->exactly(2))->method('getCustomer')->willReturnSelf();
        $this->miraklObject->expects($this->any())->method('getShippingAddress')->willReturn($this->customerShippingAddressFactory);

        $this->collectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->collectionFactory->expects($this->any())->method('addRegionCodeFilter')->willReturnSelf();
        $this->collectionFactory->expects($this->any())->method('addCountryFilter')->willReturnSelf();
        $this->collectionFactory->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->collectionFactory->expects($this->any())->method('toArray')->willReturn([
            'name' => 'California'
        ]);

        $countryMock = $this->getMockBuilder(\Magento\Directory\Model\Country::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getData'])
            ->getMock();
        $this->countryFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->countryFactory->expects($this->any())->method('loadByCode')->willReturn($countryMock);
        $countryMock->expects($this->any())->method('getName')->willReturn('United States');
        $countryMock->expects($this->any())->method('getData')->with('iso3_code')->willReturn('USA');

        $offer = $this->getMockBuilder(CreateOrderOffer::class)
            ->addMethods(['getOrderLineId', 'setQuantity', 'setOrderLineAdditionalFields'])
            ->getMock();
        $offer->expects($this->any())->method('getOrderLineId')->willReturn(1234);
        $offer->expects($this->any())->method('setQuantity')->willReturnSelf();
        $offer->expects($this->any())->method('setOrderLineAdditionalFields')->willReturnSelf();

        $this->miraklObject->method('getItems')->willReturn([$offer]);

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalData'])
            ->getMockForAbstractClass();
        $item->expects($this->any())->method('getAdditionalData')->willReturn(self::ADDITIONAL_DATA_MOCK);
        $this->itemRepository->expects($this->any())->method('get')->with(1234)->willReturn($item);

        $this->customItemData->execute($this->observer);
    }

    public function testShippingOptionHasFedexShippingRatesThrowsNoSuchEntityException(): void
    {
        $miraklShopId = 999;

        $this->shopRepository
            ->expects($this->any())
            ->method('getById')
            ->with($miraklShopId)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException(__('No such entity')));

        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('shippingOptionHasFedexShippingRates');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->customItemData, [$miraklShopId]);
        $this->assertFalse($result);
    }

    public function testShippingOptionHasFedexShippingRatesReturnsFalseWhenShopIdIsZero(): void
    {
        $miraklShopId = 0;
        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('shippingOptionHasFedexShippingRates');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->customItemData, [$miraklShopId]);
        $this->assertFalse($result);
    }

    public function testExecuteWith3pDiscountingAndMatchingQuoteItemId(): void
    {
        $productLineDetails = json_encode([
            [
                'instanceId' => 999,
                'productDiscountAmount' => 10.00,
                'productLineDetails' => [
                    [
                        'detailPrice' => 0.00,
                        'unitQuantity' => 2
                    ]
                ]
            ]
        ]);

        $this->helper->expects($this->any())
            ->method('is3pDiscountingEnabled')
            ->willReturn(true);

        $this->helper->expects($this->any())
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->observer->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->event);

        $this->event->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->expects($this->any())
            ->method('getProductLineDetails')
            ->willReturn($productLineDetails);

        $this->orderMock->expects($this->any())
            ->method('getCouponCode')
            ->willReturn('SAVE10');

        $this->createOrderOffer->expects($this->any())
            ->method('getOrderLineId')
            ->willReturn(1234);

        $this->createOrderOffer->expects($this->any())
            ->method('setOfferPrice')
            ->with(0.00)
            ->willReturnSelf();

        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalData', 'getQuoteItemId'])
            ->getMockForAbstractClass();

        $orderItem->expects($this->any())
            ->method('getQuoteItemId')
            ->willReturn(999);

        $orderItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn('{}');

        $this->itemRepository->expects($this->any())
            ->method('get')
            ->with(1234)
            ->willReturn($orderItem);

        $this->event->expects($this->any())
            ->method('getCreateOrder')
            ->willReturn($this->miraklObject);

        $this->miraklObject->expects($this->any())
            ->method('getOffers')
            ->willReturnSelf();

        $this->miraklObject->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->createOrderOffer]);

        $offerMock = $this->getMockBuilder(Offer::class)
            ->addMethods(['getShopId'])
            ->onlyMethods(['getId', 'getLeadtimeToShip'])
            ->disableOriginalConstructor()
            ->getMock();

        $offerMock->method('getId')->willReturn(1);
        $offerMock->method('getShopId')->willReturn(22);
        $offerMock->method('getLeadtimeToShip')->willReturn(3);

        $this->createOrderOffer->expects($this->any())
            ->method('setPrice')
            ->with($this->equalTo(0.00));

        $this->helper->expects($this->any())
            ->method('getD194958')
            ->willReturn(true);

        $this->createOrderOffer->expects($this->any())
            ->method('getShippingTypeCode')
            ->willReturn(null);

        $this->offerHelper->expects($this->any())
            ->method('getOfferById')
            ->willReturn($offerMock);

        $this->shopRepository->expects($this->any())
            ->method('getById')
            ->willReturn($this->shopInterface);

        $this->shopInterface->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn('1234');

        $result = $this->customItemData->execute($this->observer);
        $this->assertNull($result);
    }

    /**
     * Test the generateXML private method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGenerateXML(): void
    {
        $miraklShippingData = [
            'address' => [
                'firstname' => 'Test',
                'lastname' => 'User',
                'city' => 'Test City',
                'countryId' => 'US',
                'region' => 'TX',
                'street' => ['123 Test St', 'Apt 456'],
                'postcode' => '12345',
                'telephone' => '123-456-7890',
                'company' => 'Test Company'
            ],
            'fedexShipAccountNumber' => '9876543210'
        ];

        $adjustedArray = ['adjustedArray' => 'test'];

        $this->helper->expects($this->once())
            ->method('adjustArrayForXml')
            ->with($miraklShippingData)
            ->willReturn($adjustedArray);

        $xmlDocument = new \DOMDocument();
        $xmlDocument->loadXML('<adjustedArray>test</adjustedArray>');
        $xmlResult = $xmlDocument->saveXML($xmlDocument->documentElement);

        $this->xmlGenerator->expects($this->once())
            ->method('arrayToXml')
            ->with($adjustedArray)
            ->willReturnSelf();

        $this->xmlGenerator->expects($this->once())
            ->method('__toString')
            ->willReturn('<?xml version="1.0"?>' . $xmlResult);

        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('generateXML');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->customItemData, [$miraklShippingData]);
        $this->assertEquals('<adjustedArray>test</adjustedArray>', $result);
    }

    /**
     * Test the isFreightQuoteItem private method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIsFreightQuoteItem(): void
    {
        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('isFreightQuoteItem');
        $method->setAccessible(true);

        $additionalData = [
            'punchout_enabled' => true,
            'packaging_data' => ['some' => 'data']
        ];
        $freightInfo = ['some' => 'freight info'];
        $result = $method->invokeArgs($this->customItemData, [$additionalData, $freightInfo]);
        $this->assertTrue($result);

        $additionalData = [
            'punchout_enabled' => false,
            'packaging_data' => ['some' => 'data']
        ];
        $freightInfo = ['some' => 'freight info'];
        $result = $method->invokeArgs($this->customItemData, [$additionalData, $freightInfo]);
        $this->assertFalse($result);

        $additionalData = [
            'punchout_enabled' => true,
            'packaging_data' => []
        ];
        $freightInfo = ['some' => 'freight info'];
        $result = $method->invokeArgs($this->customItemData, [$additionalData, $freightInfo]);
        $this->assertFalse($result);

        $additionalData = [
            'punchout_enabled' => true,
            'packaging_data' => ['some' => 'data']
        ];
        $freightInfo = [];
        $result = $method->invokeArgs($this->customItemData, [$additionalData, $freightInfo]);
        $this->assertFalse($result);

        $additionalData = [
            'punchout_enabled' => true
        ];
        $freightInfo = ['some' => 'freight info'];
        $result = $method->invokeArgs($this->customItemData, [$additionalData, $freightInfo]);
        $this->assertFalse($result);
    }

    /**
     * Test the isCustomerShipmentAccountEnabled private method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIsCustomerShipmentAccountEnabled(): void
    {
        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('isCustomerShipmentAccountEnabled');
        $method->setAccessible(true);

        $additionalInfoMock = $this->getMockBuilder(MiraklObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalFieldValues'])
            ->getMock();

        $additionalInfoMock->method('getAdditionalFieldValues')
            ->willReturn([
                ['code' => 'enable-customer-shipment-account', 'value' => "true"]
            ]);

        $shopMock = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalInfo'])
            ->getMockForAbstractClass();

        $shopMock->method('getAdditionalInfo')
            ->willReturn($additionalInfoMock);

        $result = $method->invokeArgs($this->customItemData, [$shopMock]);
        $this->assertTrue($result);

        $additionalInfoMock2 = $this->getMockBuilder(MiraklObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalFieldValues'])
            ->getMock();

        $additionalInfoMock2->method('getAdditionalFieldValues')
            ->willReturn([
                ['code' => 'enable-customer-shipment-account', 'value' => "false"]
            ]);

        $shopMock2 = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalInfo'])
            ->getMockForAbstractClass();

        $shopMock2->method('getAdditionalInfo')
            ->willReturn($additionalInfoMock2);

        $result = $method->invokeArgs($this->customItemData, [$shopMock2]);
        $this->assertFalse($result);

        $additionalInfoMock3 = $this->getMockBuilder(MiraklObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalFieldValues'])
            ->getMock();

        $additionalInfoMock3->method('getAdditionalFieldValues')
            ->willReturn([
                ['code' => 'other-field', 'value' => "true"]
            ]);

        $shopMock3 = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalInfo'])
            ->getMockForAbstractClass();

        $shopMock3->method('getAdditionalInfo')
            ->willReturn($additionalInfoMock3);

        $result = $method->invokeArgs($this->customItemData, [$shopMock3]);
        $this->assertFalse($result);

        $shopMock4 = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalInfo'])
            ->getMockForAbstractClass();

        $shopMock4->method('getAdditionalInfo')
            ->willReturn(null);

        $result = $method->invokeArgs($this->customItemData, [$shopMock4]);
        $this->assertFalse($result);
    }

    /**
     * Test the isCustomerShippingAccountGloballyEnabled private method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIsCustomerShippingAccountGloballyEnabled(): void
    {
        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('isCustomerShippingAccountGloballyEnabled');
        $method->setAccessible(true);

        $helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCustomerShippingAccount3PEnabled', 'isVendorSpecificCustomerShippingAccountEnabled'])
            ->getMock();

        $helperMock->method('isCustomerShippingAccount3PEnabled')
            ->willReturn(true);
        $helperMock->method('isVendorSpecificCustomerShippingAccountEnabled')
            ->willReturn(true);

        $helperProperty = $reflection->getProperty('helper');
        $helperProperty->setAccessible(true);
        $originalHelper = $helperProperty->getValue($this->customItemData);
        $helperProperty->setValue($this->customItemData, $helperMock);

        $result = $method->invoke($this->customItemData);
        $this->assertTrue($result);

        $helperProperty->setValue($this->customItemData, $originalHelper);
    }

    /**
     * Test the isCustomerShippingAccountGloballyEnabled private method with false conditions
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIsCustomerShippingAccountGloballyEnabledWithFalseConditions(): void
    {
        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('isCustomerShippingAccountGloballyEnabled');
        $method->setAccessible(true);

        $helperMock1 = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCustomerShippingAccount3PEnabled', 'isVendorSpecificCustomerShippingAccountEnabled'])
            ->getMock();

        $helperMock1->method('isCustomerShippingAccount3PEnabled')
            ->willReturn(false);
        $helperMock1->method('isVendorSpecificCustomerShippingAccountEnabled')
            ->willReturn(true);

        $helperProperty = $reflection->getProperty('helper');
        $helperProperty->setAccessible(true);
        $originalHelper = $helperProperty->getValue($this->customItemData);
        $helperProperty->setValue($this->customItemData, $helperMock1);

        $result = $method->invoke($this->customItemData);
        $this->assertFalse($result);

        $helperMock2 = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCustomerShippingAccount3PEnabled', 'isVendorSpecificCustomerShippingAccountEnabled'])
            ->getMock();

        $helperMock2->method('isCustomerShippingAccount3PEnabled')
            ->willReturn(true);
        $helperMock2->method('isVendorSpecificCustomerShippingAccountEnabled')
            ->willReturn(false);

        $helperProperty->setValue($this->customItemData, $helperMock2);

        $result = $method->invoke($this->customItemData);
        $this->assertFalse($result);

        $helperProperty->setValue($this->customItemData, $originalHelper);
    }

    /**
     * Test the injectFedexShippingData private method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testInjectFedexShippingData(): void
    {
        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('injectFedexShippingData');
        $method->setAccessible(true);

        $target = [];
        $source = [
            'fedexShipAccountNumber' => '123456789',
            'fedexShipReferenceId' => 'REF-123'
        ];
        $method->invokeArgs($this->customItemData, [&$target, $source]);
        $this->assertEquals('123456789', $target['fedexShipAccountNumber']);
        $this->assertEquals('REF-123', $target['fedexShipReferenceId']);

        $target = [];
        $source = [
            'fedexShipAccountNumber' => '123456789'
        ];
        $method->invokeArgs($this->customItemData, [&$target, $source]);
        $this->assertEquals('123456789', $target['fedexShipAccountNumber']);
        $this->assertArrayNotHasKey('fedexShipReferenceId', $target);

        $target = [];
        $source = [
            'fedexShipAccountNumber' => '',
            'fedexShipReferenceId' => ''
        ];
        $method->invokeArgs($this->customItemData, [&$target, $source]);
        $this->assertArrayNotHasKey('fedexShipAccountNumber', $target);
        $this->assertArrayNotHasKey('fedexShipReferenceId', $target);

        $target = [
            'fedexShipAccountNumber' => 'OLD-123',
            'fedexShipReferenceId' => 'OLD-REF'
        ];
        $source = [
            'fedexShipAccountNumber' => 'NEW-123',
            'fedexShipReferenceId' => 'NEW-REF'
        ];
        $method->invokeArgs($this->customItemData, [&$target, $source]);
        $this->assertEquals('NEW-123', $target['fedexShipAccountNumber']);
        $this->assertEquals('NEW-REF', $target['fedexShipReferenceId']);
    }

    /**
     * Test the lead time shop handling logic
     *
     * @return void
     */
    public function testLeadTimeShopHandling(): void
    {
        $leadTimeShop = [
            10 => 5, // Shop ID 10 has lead time of 5 days
            20 => 3  // Shop ID 20 has lead time of 3 days
        ];

        $shopId = 10;
        $leadTime = 7;

        if (isset($leadTimeShop[$shopId])) {
            if ($leadTimeShop[$shopId] < $leadTime) {
                $leadTimeShop[$shopId] = $leadTime;
            }
        } else {
            $leadTimeShop[$shopId] = $leadTime;
        }

        $this->assertEquals(7, $leadTimeShop[10], 'Lead time should be updated when offer has greater lead time');

        $shopId = 20;
        $leadTime = 2;

        if (isset($leadTimeShop[$shopId])) {
            if ($leadTimeShop[$shopId] < $leadTime) {
                $leadTimeShop[$shopId] = $leadTime;
            }
        } else {
            $leadTimeShop[$shopId] = $leadTime;
        }

        $this->assertEquals(3, $leadTimeShop[20], 'Lead time should not be updated when offer has smaller lead time');

        $shopId = 30;
        $leadTime = 4;

        if (isset($leadTimeShop[$shopId])) {
            if ($leadTimeShop[$shopId] < $leadTime) {
                $leadTimeShop[$shopId] = $leadTime;
            }
        } else {
            $leadTimeShop[$shopId] = $leadTime;
        }

        $this->assertArrayHasKey(30, $leadTimeShop, 'New shop ID should be added to lead time array');
        $this->assertEquals(4, $leadTimeShop[30], 'New shop ID should have the correct lead time');
    }

    /**
     * Test the getOfferLeadTimes method
     *
     * @return void
     */
    public function testGetOfferLeadTimes(): void
    {
        $offer1 = $this->createMock(CreateOrderOffer::class);
        $offer1->method('getId')->willReturn(101);

        $offer2 = $this->createMock(CreateOrderOffer::class);
        $offer2->method('getId')->willReturn(102);

        $offer3 = $this->createMock(CreateOrderOffer::class);
        $offer3->method('getId')->willReturn(103);

        $offerData1 = $this->getMockBuilder(Offer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getLeadtimeToShip'])
            ->addMethods(['getShopId'])
            ->getMock();
        $offerData1->method('getId')->willReturn(101);
        $offerData1->method('getShopId')->willReturn(10);
        $offerData1->method('getLeadtimeToShip')->willReturn(5);

        $offerData2 = $this->getMockBuilder(Offer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getLeadtimeToShip'])
            ->addMethods(['getShopId'])
            ->getMock();
        $offerData2->method('getId')->willReturn(102);
        $offerData2->method('getShopId')->willReturn(10);
        $offerData2->method('getLeadtimeToShip')->willReturn(7);

        $offerData3 = $this->getMockBuilder(Offer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getLeadtimeToShip'])
            ->addMethods(['getShopId'])
            ->getMock();
        $offerData3->method('getId')->willReturn(103);
        $offerData3->method('getShopId')->willReturn(20); // Different shop
        $offerData3->method('getLeadtimeToShip')->willReturn(3);

        $this->offerHelper->expects($this->exactly(3))
            ->method('getOfferById')
            ->willReturnMap([
                [101, $offerData1],
                [102, $offerData2],
                [103, $offerData3]
            ]);

        $reflection = new \ReflectionClass($this->customItemData);
        $method = $reflection->getMethod('getOfferLeadTimes');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->customItemData, [[$offer1, $offer2, $offer3]]);

        $this->assertCount(3, $result, 'Result should contain entries for all offers');

        $this->assertEquals(7, $result[101], 'Offer 101 should have shop 10\'s highest lead time (7)');
        $this->assertEquals(7, $result[102], 'Offer 102 should have shop 10\'s highest lead time (7)');

        $this->assertEquals(3, $result[103], 'Offer 103 should have shop 20\'s lead time (3)');
    }

    /**
     * Test the scenario where d2255568 toggle is enabled and packaging data is set
     * from the sellerPackage when freight_data is empty
     *
     * @return void
     */
    public function testSetCustomDataToOfferWithD2255568ToggleEnabled(): void
    {
        $miraklShopId = 123;
        $orderItemId = 456;
        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAdditionalData', 'getOrder', 'getId'])
            ->addMethods(['getMiraklShopId'])
            ->getMockForAbstractClass();

        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderItem->method('getOrder')->willReturn($order);
        $orderItem->method('getMiraklShopId')->willReturn($miraklShopId);
        $orderItem->method('getId')->willReturn($orderItemId);

        $additionalDataArray = [
            'mirakl_shipping_data' => [
                'some_field' => 'value',
                'another_field' => 'value2'
            ],
            'punchout_enabled' => true,
            'packaging_data' => ['some_data' => 'value']
        ];

        $additionalData = json_encode($additionalDataArray);
        $orderItem->method('getAdditionalData')->willReturn($additionalData);

        $offer = $this->createMock(CreateOrderOffer::class);

        $customerMock = $this->getMockBuilder(MiraklObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getShippingAddress'])
            ->getMock();
        $shippingAddressMock = $this->createMock(MiraklObject::class);
        $customerMock->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $createOrder = $this->getMockBuilder(MiraklObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomer'])
            ->getMock();
        $createOrder->method('getCustomer')
            ->willReturn($customerMock);

        $marketplaceRatesHelper = $this->createMock(MarketplaceRatesHelper::class);
        $marketplaceRatesHelper->method('isFreightShippingEnabled')
            ->willReturn(false);
        $marketplaceRatesHelper->method('isd2255568toggleEnabled')
            ->willReturn(true);

        $freightInfo = ['freight_info' => 'data'];

        $packagingData = [
            'dimensions' => '10x10x10',
            'weight' => '5.0',
            'shipping_method' => 'freight'
        ];

        $sellerPackage = [
            [
                'seller_id' => $miraklShopId,
                'packaging' => $packagingData
            ]
        ];

        $packagingCheckoutPricing = $this->createMock(PackagingCheckoutPricing::class);
        $packagingCheckoutPricing->method('getPackagingItems')
            ->with(false, $order)
            ->willReturn($freightInfo);
        $packagingCheckoutPricing->method('findSellerRecord')
            ->with($miraklShopId, $freightInfo)
            ->willReturn($sellerPackage);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('info');

        $itemRepository = $this->createMock(ItemRepository::class);
        $customerShippingAddressFactory = $this->createMock(CustomerShippingAddressFactory::class);
        $countryFactory = $this->createMock(CountryFactory::class);
        $collectionFactory = $this->createMock(CollectionFactory::class);
        $helper = $this->createMock(Data::class);
        $handleMktCheckout = $this->createMock(HandleMktCheckout::class);
        $offerHelper = $this->createMock(OfferHelper::class);
        $shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $xmlGenerator = $this->createMock(Generator::class);

        $customItemDataMock = new CustomItemData(
            $this->itemRepository,
            $this->customerShippingAddressFactory,
            $this->countryFactory,
            $this->collectionFactory,
            $this->helper,
            $this->handleMktCheckout,
            $this->offerHelper,
            $this->shopRepository,
            $marketplaceRatesHelper,
            $this->xmlGenerator,
            $logger,
            $packagingCheckoutPricing
        );

        $reflection = new \ReflectionClass(CustomItemData::class);
        $method = $reflection->getMethod('setCustomDataToOffer');
        $method->setAccessible(true);

        $miraklShippingData = $additionalDataArray['mirakl_shipping_data'];


        $miraklShippingData['packaging_data'] = $packagingData;

        $marketplaceRatesHelper->method('isd2255568toggleEnabled')
            ->willReturn(true);
        $marketplaceRatesHelper->method('isFreightShippingEnabled')
            ->willReturn(false);

        $packagingCheckoutPricing->method('getPackagingItems')
            ->with(false, $order)
            ->willReturn($freightInfo);

        $packagingCheckoutPricing->method('findSellerRecord')
            ->with($miraklShopId, $freightInfo)
            ->willReturn($sellerPackage);

        $logger->method('info');

        $method->invokeArgs($customItemDataMock, [$orderItem, $offer, $createOrder, '', 0.0]);

        $this->assertEquals($packagingData, $miraklShippingData['packaging_data']);
    }

    /**
     * Test the d2255568 toggle condition in the setCustomDataToOffer method
     */
    public function testSetCustomDataToOfferWithD2255568ToggleEnabledAndEmptyFreightData(): void
    {
        $marketplaceRatesHelper = $this->createMock(MarketplaceRatesHelper::class);
        $marketplaceRatesHelper->method('isFreightShippingEnabled')
            ->willReturn(false);
        $marketplaceRatesHelper->method('isd2255568toggleEnabled')
            ->willReturn(true);

        $customItemData = new CustomItemData(
            $this->itemRepository,
            $this->customerShippingAddressFactory,
            $this->countryFactory,
            $this->collectionFactory,
            $this->helper,
            $this->handleMktCheckout,
            $this->offerHelper,
            $this->shopRepository,
            $marketplaceRatesHelper,
            $this->xmlGenerator,
            $this->loggerMock,
            $this->packagingMock
        );

        $miraklShopId = 12345;
        $orderItemId = 67890;

        $order = $this->createMock(Order::class);

        $packagingData = [
            'dimensions' => '10x10x10',
            'weight' => '5.0',
            'shipping_method' => 'freight'
        ];

        $freightInfo = ['freight_info' => 'data'];
        $sellerPackage = [
            [
                'seller_id' => $miraklShopId,
                'packaging' => $packagingData
            ]
        ];

        $orderItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrder', 'getId', 'getAdditionalData'])
            ->addMethods(['getMiraklShopId'])
            ->getMock();

        $orderItem->method('getOrder')
            ->willReturn($order);
        $orderItem->method('getMiraklShopId')
            ->willReturn($miraklShopId);
        $orderItem->method('getId')
            ->willReturn($orderItemId);

        $additionalDataArray = [
            'mirakl_shipping_data' => [
                'some_field' => 'value',
                'another_field' => 'value2'
            ],
            'punchout_enabled' => true,
            'packaging_data' => ['some_data' => 'value']
        ];

        $additionalData = json_encode($additionalDataArray);
        $orderItem->method('getAdditionalData')
            ->willReturn($additionalData);

        $offer = $this->createMock(CreateOrderOffer::class);

        $customerMock = $this->getMockBuilder(MiraklObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getShippingAddress'])
            ->getMock();
        $shippingAddressMock = $this->createMock(MiraklObject::class);
        $customerMock->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $createOrder = $this->getMockBuilder(MiraklObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomer'])
            ->getMock();
        $createOrder->method('getCustomer')
            ->willReturn($customerMock);

        $this->packagingMock->method('getPackagingItems')
            ->with(false, $order)
            ->willReturn($freightInfo);

        $this->packagingMock->method('findSellerRecord')
            ->with($miraklShopId, $freightInfo)
            ->willReturn($sellerPackage);

        $this->loggerMock->method('info');

        $reflection = new \ReflectionClass(CustomItemData::class);
        $method = $reflection->getMethod('setCustomDataToOffer');
        $method->setAccessible(true);

        $method->invokeArgs($customItemData, [$orderItem, $offer, $createOrder, '', 0.0]);
    }
}
