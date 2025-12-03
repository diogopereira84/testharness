<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Plugin\Model\Quote;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\MarketplaceCheckout\Plugin\Model\Quote\ShippingMethodManagementPlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Quote\Model\ResourceModel\Quote\AddressFactory as QuoteAddressResourceFactory;
use Magento\Quote\Model\ShippingMethodManagementInterface;
use Mirakl\Connector\Helper\Quote as MiraklQuoteHelper;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollection;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\FrontendDemo\Helper\Quote\Item as QuoteItemHelper;
use Mirakl\FrontendDemo\Model\Quote\Updater as QuoteUpdater;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;
use Fedex\MarketplaceRates\Helper\Data;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceCheckout\Model\Config;
use Fedex\MarketplaceCheckout\Helper\BuildDeliveryDate;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\MarketplaceCheckout\Api\FedexRateApiDataInterface;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use Fedex\Delivery\Helper\Data as RetailHelper;
use Fedex\Cart\ViewModel\CheckoutConfig;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use Fedex\MarketplaceCheckout\Model\FreightCheckoutPricing;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Magento\Customer\Api\Data\CustomerInterface;
use Mirakl\Connector\Model\Quote\Cache as MiraklQuoteCache;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Catalog\Model\Product;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Fedex\MarketplaceProduct\Model\Shop;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Mirakl\MMP\Common\Domain\Shipping\DeliveryTime;

class ShippingMethodManagementPluginTest extends TestCase
{
    /**
     * @var ShippingMethodManagementPlugin
     */
    protected $shippingMethodManagementPlugin;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var QuoteItemHelper
     */
    protected $quoteItemHelper;

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @var QuoteAddressResourceFactory
     */
    protected $quoteAddressResourceFactory;

    /**
     * @var QuoteSynchronizer
     */
    protected $quoteSynchronizer;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var ShopManagementInterface
     */
    protected $shopManagementInterface;

    /**
     * @var OfferCollector
     */
    protected $offerCollector;

    /**
     * @var BuildDeliveryDate
     */
    protected $buildDeliveryDate;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var MiraklQuoteHelper
     */
    protected $miraklQuoteHelper;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * @var OfferCollectionFactory
     */
    protected $offerCollectionFactory;

    /**
     * @var RetailHelper
     */
    protected $retailHelper;

    /**
     * @var CheckoutConfig
     */
    protected $checkoutConfig;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var ShopRepositoryInterface
     */
    protected $shopRepositoryInterface;

    /**
     * @var PackagingCheckoutPricing
     */
    protected $packagingCheckoutPricing;

    /**
     * @var FreightCheckoutPricing
     */
    protected $freightCheckoutPricing;

    /**
     * @var NonCustomizableProduct
     */
    protected $nonCustomizableProduct;

    /**
     * @var AddressInterface
     */
    protected $addressInterface;

    /**
     * @var CustomerInterface
     */
    protected $customerInterface;

    /**
     * @var Quote
     */
    protected $quoteMock;

    /**
     * @var OfferCollection
     */
    protected $offerCollection;

    /**
     * @var ShippingMethodManagementInterface
     */
    protected $shippingMethodManagementInterface;

    /**
     * @var CartInterface
     */
    protected $cartInterface;

    /**
     * @var QuoteItem
     */
    protected $quoteItem;

    /**
     * @var MiraklQuoteCache
     */
    protected $miraklQuoteCache;

    /**
     * @var ShopInterface
     */
    protected $shopInterface;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepositoryInterface;

    /**
     * @var AbstractItem
     */
    protected $abstractItem;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var CartItemInterface
     */
    protected $cartItemInterface;

    /**
     * @var ProductInterface
     */
    protected $productInterface;

    /**
     * @var Shop
     */
    protected $shop;

    /**
     * @var QuoteItemOption
     */
    protected $quoteItemOption;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var RegionCollection
     */
    protected $regionCollection;

    /**
     * @var ShippingFeeType
     */
    protected $shippingFeeType;

    /**
     * @var DeliveryTime
     */
    protected $deliveryTime;

    /**
     * @var ShippingFeeTypeCollection
     */
    protected $shippingFeeTypeCollection;

    /**
     * @var LoggerInterface
     */
    protected $loggerInterface;

    /**
     * @var Config
     */
    protected $config;

    protected function setUp(): void
    {
        $this->cartRepositoryInterface = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomOption'])
            ->onlyMethods(['getActive'])
            ->getMockForAbstractClass();

        $this->quoteHelper = $this->getMockBuilder(QuoteHelper::class)
            ->disableOriginalConstructor()
            ->addMethods(['isShippingMethodAvailable'])
            ->onlyMethods(['isMiraklQuote','isFullMiraklQuote'])
            ->getMock();

        $this->quoteItemHelper = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemShippingTypes'])
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getProductType','getProduct','isDeleted','getQty','setQty','getSku','getId'])
            ->addMethods(['getAdditionalData','getWeight','getMiraklShippingType','getParentItemId','getMiraklOfferId'])
            ->getMock();

        $this->quoteUpdater = $this->getMockBuilder(QuoteUpdater::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemShippingTypeByCode','getItemSelectedShippingType'])
            ->getMock();

        $this->quoteAddressResourceFactory = $this->getMockBuilder(QuoteAddressResourceFactory::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();

        $this->quoteSynchronizer = $this->getMockBuilder(QuoteSynchronizer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getGroupedItems'])
            ->getMock();

        $this->data = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'isFreightShippingEnabled',
                    'getResponseFromFedexRatesAPI',
                    'getFedexShippingMethods',
                    'handleMethodTitle'
                ]
            )
            ->getMock();

        $this->shopManagementInterface = $this->getMockBuilder(ShopManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShopByProduct','getProduct'])
            ->getMock();

        $this->offerCollector = $this->getMockBuilder(OfferCollector::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteItems'])
            ->getMock();

        $this->buildDeliveryDate = $this->getMockBuilder(BuildDeliveryDate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllowedDeliveryDate'])
            ->getMock();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfig','getToggleConfigValue'])
            ->getMock();

        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();

        $this->miraklQuoteHelper = $this->getMockBuilder(MiraklQuoteHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFullMiraklQuote'])
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMockForAbstractClass();

        $this->offerCollectionFactory = $this->getMockBuilder(OfferCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->offerCollection = $this->getMockBuilder(OfferCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getItems','getAdditionalInfo','getId'])
            ->getMock();

        $this->retailHelper = $this->getMockBuilder(RetailHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomer', 'getAssignedCompany'])
            ->getMock();

        $this->checkoutConfig = $this->getMockBuilder(CheckoutConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer'])
            ->getMock();

        $this->marketplaceHelper = $this->getMockBuilder(MarketplaceHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCustomerShippingAccount3PEnabled','isVendorSpecificCustomerShippingAccountEnabled'])
            ->getMock();

        $this->shopRepositoryInterface = $this->getMockBuilder(ShopRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMock();

        $this->packagingCheckoutPricing = $this->getMockBuilder(PackagingCheckoutPricing::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPackagingItems','findSellerRecord'])
            ->getMock();

        $this->freightCheckoutPricing = $this->getMockBuilder(FreightCheckoutPricing::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->nonCustomizableProduct = $this->getMockBuilder(NonCustomizableProduct::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMktCbbEnabled'])
            ->getMock();

        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isVirtual',
                'getItemsCount',
                'getShippingAddress',
                'getAllItems',
                'getData',
                'getProduct',
                'getStoreId'
                ])
            ->getMock();

        $this->shippingMethodManagementInterface = $this->createMock(ShippingMethodManagementInterface::class);

        $this->cartInterface = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->miraklQuoteCache = $this->getMockBuilder(MiraklQuoteCache::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuoteControlHash'])
            ->getMock();

        $this->productRepositoryInterface = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','getProduct'])
            ->getMockForAbstractClass();

        $this->shopInterface = $this->getMockBuilder(ShopInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getShopByProduct',
                    'getProduct',
                    'getShippingRateOption',
                    'getTimezone',
                    'getData',
                    'getShippingMethods',
                    'getSellerAltName',
                    'getBusinessDays',
                    'getWeight'
                ]
            )
            ->getMockForAbstractClass();

        $this->abstractItem = $this->getMockBuilder(AbstractItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct','getById'])
            ->getMockForAbstractClass();

        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','getCustomOption','getProductionDays'])
            ->getMock();

        $this->cartItemInterface = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->productInterface = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->shop = $this->getMockBuilder(Shop::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTimezone','getShippingRateOption'])
            ->getMock();

        $this->quoteItemOption = $this->getMockBuilder(QuoteItemOption::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCity', 'getRegionCode', 'getPostcode', 'getCountryId', 'getCompany'])
            ->getMock();

        $this->regionCollection = $this->getMockBuilder(RegionCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addRegionNameFilter', 'getFirstItem','addCountryCodeFilter'])
            ->getMock();

        $this->shippingFeeType = $this->getMockBuilder(ShippingFeeType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDeliveryTime','getLatestDeliveryDate','getCode'])
            ->getMock();

        $this->deliveryTime = $this->getMockBuilder(DeliveryTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLatestDeliveryDate'])
            ->getMock();

        $this->shippingFeeTypeCollection = $this->getMockBuilder(ShippingFeeTypeCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode','getLabel','getData','getDeliveryTime'])
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['isShippingManagementRefactorEnabled'])
            ->getMock();
        
        $this->config->expects($this->any())
            ->method('isShippingManagementRefactorEnabled')
            ->willReturn(false);

        $objectManagerHelper = new ObjectManager($this);

        $this->shippingMethodManagementPlugin = $objectManagerHelper->getObject(
            ShippingMethodManagementPlugin::class,
            [
                'quoteRepository' =>  $this->cartRepositoryInterface,
                'quoteHelper' => $this->quoteHelper,
                'quoteItemHelper' => $this->quoteItemHelper,
                'quoteUpdater' => $this->quoteUpdater,
                'quoteAddressResourceFactory' => $this->quoteAddressResourceFactory,
                'quoteSynchronizer' => $this->quoteSynchronizer,
                'data' => $this->data,
                'shopManagement' => $this->shopManagementInterface,
                'offerCollector' => $this->offerCollector,
                'buildDeliveryDate' => $this->buildDeliveryDate,
                'collectionFactory' => $this->collectionFactory,
                'toggleConfig' => $this->toggleConfig,
                'timezone' => $this->timezoneInterface,
                'miraklQuoteHelper' => $this->miraklQuoteHelper,
                'request' => $this->requestInterface,
                'offerCollectionFactory' => $this->offerCollectionFactory,
                'retailHelper' => $this->retailHelper,
                'checkoutConfig' => $this->checkoutConfig,
                'marketplaceHelper' => $this->marketplaceHelper,
                'shopRepository' => $this->shopRepositoryInterface,
                'packagingCheckoutPricing' => $this->packagingCheckoutPricing,
                'freightCheckoutPricing' => $this->freightCheckoutPricing,
                'nonCustomizableProduct' => $this->nonCustomizableProduct,
                'config' => $this->config
            ]
        );
    }

    /**
     * Tests that isAddressValid returns true when all required address fields are present.
     */
    public function testIsAddressValidReturnsTrueWhenAllFieldsArePresent()
    {
        $this->addressInterface->expects($this->any())->method('getPostcode')->willReturn('12345');
        $this->addressInterface->expects($this->any())->method('getCity')->willReturn('Test City');
        $this->addressInterface->expects($this->any())->method('getRegionCode')->willReturn('CA');
        $this->addressInterface->expects($this->any())->method('getStreet')->willReturn(['123 Test St']);
        $result = $this->shippingMethodManagementPlugin->isAddressValid($this->addressInterface);
        $this->assertTrue($result);
    }

    /**
     * Tests that isAddressValid returns false when the region code is missing from the address.
     *
     * This test sets up the address interface mock to return a valid postcode and city,
     * but an empty string for the region code. It also asserts that the getStreet method
     * is never called. The test then verifies that isAddressValid returns false under these conditions.
     */
    public function testIsAddressValidReturnsFalseWhenRegionCodeIsMissing()
    {
        $this->addressInterface->expects($this->any())->method('getPostcode')->willReturn('12345');
        $this->addressInterface->expects($this->any())->method('getCity')->willReturn('Test City');
        $this->addressInterface->expects($this->any())->method('getRegionCode')->willReturn('');
        $this->addressInterface->expects($this->never())->method('getStreet');

        $result = $this->shippingMethodManagementPlugin->isAddressValid($this->addressInterface);
        $this->assertFalse($result);
    }

    /**
     * Tests that getCompanyAllowedShippingMethods returns the mapped shipping methods
     */
    public function testGetCompanyAllowedShippingMethodsReturnsMappedMethods()
    {
        $companyMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAllowedDeliveryOptions'])
            ->getMock();

        $this->retailHelper->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterface);

        $this->retailHelper->expects($this->any())
            ->method('getAssignedCompany')
            ->with($this->customerInterface)
            ->willReturn($companyMock);

        $companyMock->expects($this->any())
            ->method('getAllowedDeliveryOptions')
            ->willReturn(json_encode(['TWO_DAY', 'GROUND_US', 'CUSTOM_OPTION']));

        $result = $this->shippingMethodManagementPlugin->getCompanyAllowedShippingMethods();
        $this->assertContains(['shipping_method_name' => 'FEDEX_2_DAY'], $result);
    }

    /**
     * Tests that getElectedAddress() returns null when the 'addressCounted' property is empty.
     *
     * This test uses reflection to set the private/protected 'addressCounted' property of the
     * ShippingMethodManagementPlugin instance to an empty array, then asserts that calling
     * getElectedAddress() returns null.
     */
    public function testGetElectedAddressReturnsNullWhenAddressCountedIsEmpty()
    {
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $property = $reflection->getProperty('addressCounted');
        $property->setAccessible(true);
        $property->setValue($this->shippingMethodManagementPlugin, []);

        $result = $this->shippingMethodManagementPlugin->getElectedAddress();
        $this->assertNull($result);
    }

    /**
     * Tests the getElectedAddress method to ensure it returns the address
     * with the maximum sum of region values from the addressCounted property.
     *
     * The test sets up two addresses with different region values, assigns them
     * to the addressCounted property via reflection, and asserts that the address
     * with the highest sum of region values is returned.
     *
     * - Mocks constant names for city, state, and postal code if not defined.
     * - Uses reflection to set the addressCounted property.
     * - Asserts that the correct address is returned by getElectedAddress().
     */
    public function testGetElectedAddressReturnsArrayWithMaxSum()
    {
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        if (!$reflection->hasConstant('CITY')) {
            $cityConst = $reflection->getConstant('CITY') ?? 'city';
            $stateConst = $reflection->getConstant('STATE_OR_PROVINCE') ?? 'stateOrProvinceCode';
            $postalConst = $reflection->getConstant('POSTAL_CODE') ?? 'postalCode';
        } else {
            $cityConst = $reflection->getConstant('CITY');
            $stateConst = $reflection->getConstant('STATE_OR_PROVINCE');
            $postalConst = $reflection->getConstant('POSTAL_CODE');
        }

        $addressCounted = [
            [
                $cityConst => 'Los Angeles',
                $stateConst => 'CA',
                $postalConst => '90001',
                'region1' => 2,
                'region2' => 5
            ],
            [
                $cityConst => 'San Francisco',
                $stateConst => 'CA',
                $postalConst => '94101',
                'region1' => 10,
                'region2' => 1
            ]
        ];
        $property = $reflection->getProperty('addressCounted');
        $property->setAccessible(true);
        $property->setValue($this->shippingMethodManagementPlugin, $addressCounted);

        $result = $this->shippingMethodManagementPlugin->getElectedAddress();
        $this->assertEquals($addressCounted[1], $result);
    }

    /**
     * Tests that the aroundEstimateByExtendedAddress plugin method returns an empty array
     * - Asserts that the result is an empty array.
     */
    public function testAroundEstimateByAddressIdReturnsEmptyArrayForVirtualQuote()
    {
        $cartId = 1;
        $addressId = 2;
        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(true);
        $this->quoteMock->expects($this->never())->method('getItemsCount');

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $proceed = function () {
            $this->fail('Proceed should not be called for virtual quote');
        };

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByAddressId(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $addressId
        );

        $this->assertSame([], $result);
    }

    /**
     * Tests that the aroundEstimateByAddressId plugin method returns an empty array
     * when the quote has no items.
     *
     * This test sets up a quote mock to simulate a non-virtual quote with zero items.
     * It asserts that the proceed callback is not called and that the plugin returns
     * an empty array as expected.
     */
    public function testAroundEstimateByAddressIdReturnsEmptyArrayForQuoteWithNoItems()
    {
        $cartId = 1;
        $addressId = 2;
        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getItemsCount')->willReturn(0);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $proceed = function () {
            $this->fail('Proceed should not be called for quote with no items');
        };

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByAddressId(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $addressId
        );

        $this->assertSame([], $result);
    }

    /**
     * Tests the getOfferItemsByOfferId method to ensure it returns the expected offer items for a given offer ID.
     *
     * This test mocks the offer collection factory and offer collection to simulate retrieving items by offer ID.
     * It verifies that the method returns the correct array of items.
     */
    public function testGetOfferItemsByOfferId()
    {
        $offerId = 1;
        $expectedItems = ['item1', 'item2'];
        $this->offerCollectionFactory->method('create')->willReturn($this->offerCollection);
        $this->offerCollection->method('addFieldToFilter')->willReturnSelf();
        $this->offerCollection->method('getItems')->willReturn($expectedItems);
        $result = $this->shippingMethodManagementPlugin->getOfferItemsByOfferId($offerId);
        $this->assertEquals($expectedItems, $result);
    }

    /**
     * Tests the getFilteredOffers method to ensure it returns the expected offers
     * for a given product SKU and shop ID.
     *
     */
    public function testGetFilteredOffers()
    {
        $productSku = 'test-product-sku';
        $shopId = 123;
        $expectedOffers = ['offer1', 'offer2'];
        $this->offerCollectionFactory->method('create')->willReturn($this->offerCollection);
        $this->offerCollection->method('addFieldToFilter')->willReturnSelf();
        $this->offerCollection->method('getItems')->willReturn($expectedOffers);
        $result = $this->shippingMethodManagementPlugin->getFilteredOffers($productSku, $shopId);
        $this->assertEquals($expectedOffers, $result);
    }

    /**
     * Tests that the aroundEstimateByExtendedAddress method returns an empty array when the address is invalid.
     */
    public function testAroundEstimateByExtendedAddressReturnsEmptyArrayWhenAddressIsInvalid()
    {
        $cartId = 1;
      
        // Instead of mocking the entire class with disableOriginalConstructor,
        // use the existing instance and just override one method
        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods(['isAddressValid'])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('isAddressValid')
            ->with($this->addressInterface)
            ->willReturn(false);

        $proceed = function () {
            $this->fail('Proceed should not be called when address is invalid');
        };

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );

        $this->assertSame([], $result);
    }

    /**
     * Tests the aroundEstimateByExtendedAddress plugin method to ensure it returns the result of the
     */
    public function testAroundEstimateByExtendedAddressReturnsProceedResultWhenNotMiraklQuote()
    {
        $cartId = 1;
        $expectedResult = ['method1', 'method2'];

        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods(['isAddressValid'])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('isAddressValid')
            ->with($this->addressInterface)
            ->willReturn(true);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(false);

        $proceed = function ($cartIdArg, $addressArg) use ($cartId, $expectedResult) {
            $this->assertEquals(1, $cartIdArg);
            return $expectedResult;
        };

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the aroundEstimateByExtendedAddress plugin method to ensure it returns an empty array
     */
    public function testAroundEstimateByExtendedAddressReturnsEmptyArrayForVirtualQuote()
    {
        $cartId = 1;
        $shippingMethods = ['method1'];
      
        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods(['isAddressValid', 'isPickup', 'handleMiraklShippingTypesForAllSellers'])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('isAddressValid')
            ->with($this->addressInterface)
            ->willReturn(true);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $proceed = function () use ($shippingMethods) {
            return $shippingMethods;
        };

        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(true);
        $this->quoteMock->expects($this->never())->method('getItemsCount');

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );

        $this->assertSame([], $result);
    }

    /**
     * Tests that the aroundEstimateByExtendedAddress plugin method returns an empty array
     * - Asserts that the result is an empty array.
     */
    public function testAroundEstimateByExtendedAddressReturnsEmptyArrayForQuoteWithNoItems()
    {
        $cartId = 1;
        $shippingMethods = ['method1'];
        
        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods(['isAddressValid', 'isPickup', 'handleMiraklShippingTypesForAllSellers'])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('isAddressValid')
            ->with($this->addressInterface)
            ->willReturn(true);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $proceed = function () use ($shippingMethods) {
            return $shippingMethods;
        };

        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getItemsCount')->willReturn(0);

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );

        $this->assertSame([], $result);
    }

    /**
     * Tests that the isPickup method returns true when 'isPickup' is set in the request data.
     *
     */
    public function testIsPickupReturnsTrueWhenIsPickupIsSetInRequest()
    {
        $requestData = ['isPickup' => true];
        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($this->shippingMethodManagementPlugin, $this->requestInterface);
        $method = $reflection->getMethod('isPickup');
        $method->setAccessible(true);
        $result = $method->invoke($this->shippingMethodManagementPlugin);
        $this->assertTrue($result);
    }

    /**
     * Tests that the getLbsWeight method correctly converts weight from ounces to pounds.
     *
     * This test sets up a quote item with a weight unit of 'oz.' and a weight of 32.
     * It then uses reflection to access the protected/private getLbsWeight method of the
     * ShippingMethodManagementPlugin and asserts that the returned value is 2 (i.e., 32 oz = 2 lbs).
     */
    public function testGetLbsWeightReturnsConvertedWeightWhenUnitIsOz()
    {
        $this->quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode(['weight_unit' => 'oz.']));

        $this->quoteItem->expects($this->any())
            ->method('getWeight')
            ->willReturn(32);

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('getLbsWeight');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$this->quoteItem]);
        $this->assertEquals(2, $result);
    }

    /**
     * Tests that the getLbsWeight method returns the original weight
     * when the weight unit is not 'oz' (ounces).
     *
     * This test sets up a quote item with a weight unit of 'lb' (pounds)
     * and a weight value of 5. It then uses reflection to access the
     * protected/private getLbsWeight method of the ShippingMethodManagementPlugin
     * and asserts that the returned value is equal to the original weight (5).
     */
    public function testGetLbsWeightReturnsOriginalWeightWhenUnitIsNotOz()
    {
        $this->quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode(['weight_unit' => 'lb']));

        $this->quoteItem->expects($this->any())
            ->method('getWeight')
            ->willReturn(5);

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('getLbsWeight');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$this->quoteItem]);
        $this->assertEquals(5, $result);
    }

    /**
     * Tests that the getFedExAccountNumber method returns the FedEx account number
     * when it is present in the request data.
     *
     * This test sets up a mock request containing a 'fedEx_account_number', injects
     * it into the ShippingMethodManagementPlugin instance using reflection, and then
     * invokes the getFedExAccountNumber method to verify that it returns the expected
     * account number.
     */
    public function testGetFedExAccountNumberReturnsAccountNumberWhenPresent()
    {
        $accountNumber = '123456789';
        $requestData = ['fedEx_account_number' => $accountNumber];
        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($this->shippingMethodManagementPlugin, $this->requestInterface);

        $method = $reflection->getMethod('getFedExAccountNumber');
        $method->setAccessible(true);

        $result = $method->invoke($this->shippingMethodManagementPlugin);
        $this->assertEquals($accountNumber, $result);
    }

    /**
     * Tests that isLoadingDockSelected returns an empty string when the 'hasLiftGate' key is missing from the request data.
     *
     */
    public function testIsLoadingDockSelectedReturnsEmptyStringWhenHasLiftGateIsMissing()
    {
        $requestData = [];
        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($this->shippingMethodManagementPlugin, $this->requestInterface);

        $method = $reflection->getMethod('isLoadingDockSelected');
        $method->setAccessible(true);

        $result = $method->invoke($this->shippingMethodManagementPlugin);
        $this->assertSame('', $result);
    }

    /**
     * Tests that getItemsWithOfferCombined correctly skips quote items that are either deleted or child items.
     *
     * This test sets up a quote item to be marked as deleted and ensures that:
     * - The getParentItemId method is never called on the deleted item.
     * - The offerCollector returns an array containing the deleted quote item.
     * - The getItemsWithOfferCombined method returns an empty array, indicating that deleted or child items are skipped.
     */
    public function testGetItemsWithOfferCombinedSkipsDeletedOrChildItems()
    {
        $this->quoteItem->expects($this->any())->method('isDeleted')->willReturn(true);
        $this->quoteItem->expects($this->never())->method('getParentItemId');

        $this->offerCollector->expects($this->any())
            ->method('getQuoteItems')
            ->with($this->cartInterface)
            ->willReturn([$this->quoteItem]);

        $result = $this->shippingMethodManagementPlugin->getItemsWithOfferCombined($this->cartInterface);
        $this->assertSame([], $result);
    }

    /**
     * Tests that the getItemsWithOfferCombined method skips quote items
     * that do not have the 'mirakl_offer' custom option.
     *
     * This test sets up a quote item without the 'mirakl_offer' custom option
     * and verifies that the resulting array from getItemsWithOfferCombined is empty,
     * ensuring that only items with the required custom option are included.
     */
    public function testGetItemsWithOfferCombinedSkipsItemsWithoutOfferCustomOption()
    {
        $this->quoteItem->expects($this->any())->method('isDeleted')->willReturn(false);
        $this->quoteItem->expects($this->any())->method('getParentItemId')->willReturn(null);
        $this->product->expects($this->any())->method('getCustomOption')->with('mirakl_offer')->willReturn(null);
        $this->quoteItem->expects($this->any())->method('getProduct')->willReturn($this->product);

        $this->offerCollector->expects($this->any())
            ->method('getQuoteItems')
            ->with($this->cartInterface)
            ->willReturn([$this->quoteItem]);

        $result = $this->shippingMethodManagementPlugin->getItemsWithOfferCombined($this->cartInterface);
        $this->assertSame([], $result);
    }

    /**
     * Tests that the getItemsWithOfferCombined method correctly merges quote items with the same offer ID.
     * - Calls getItemsWithOfferCombined and verifies the result is as expected.
     */
    public function testGetItemsWithOfferCombinedMergesItemsWithSameOfferId()
    {
        $offerId = 42;
        $shopId = 99;
        $offerData = json_encode(['offer_id' => $offerId]);
        $this->quoteItem->expects($this->any())->method('isDeleted')->willReturn(false);
        $this->quoteItem->expects($this->any())->method('getParentItemId')->willReturn(null);
        $this->quoteItem->expects($this->any())->method('getData')->with('mirakl_shop_id')->willReturn($shopId);
        $this->quoteItem->expects($this->any())->method('getQty')->willReturn(2);
        $this->quoteItem->expects($this->any())->method('setQty')->with($this->greaterThan(2))->willReturnSelf();
        $this->quoteItemOption->expects($this->any())->method('getValue')->willReturn($offerData);
        $this->product->expects($this->any())->method('getCustomOption')
            ->with('mirakl_offer')
            ->willReturn($this->quoteItemOption);
        $this->product->expects($this->any())->method('getProductionDays')->willReturn('2,3');
        $this->quoteItem->expects($this->any())->method('getProduct')->willReturn($this->product);

        $this->offerCollector->expects($this->any())
            ->method('getQuoteItems')
            ->with($this->cartInterface)
            ->willReturn([$this->quoteItem]);

        $result = $this->shippingMethodManagementPlugin->getItemsWithOfferCombined($this->cartInterface);
        $this->assertInstanceOf(QuoteItem::class, $result[$shopId]);
    }

    /**
     * Tests that the addEndOfDayTextInDeliveryDate method appends the "End of Day" text
     * to the formatted delivery date when the shipping method label contains the
     * "GROUND_US_NO_MARK" constant.
     *
     * The test uses reflection to access the protected/private method and constants.
     * It verifies that the resulting string matches the expected format:
     * "Day, Month Date, End of Day".
     *
     * @covers \Fedex\MarketplaceCheckout\Plugin\Model\Quote\ShippingMethodManagementPlugin::addEndOfDayTextInDeliveryDate
     */
    public function testAddEndOfDayTextInDeliveryDateAddsEodTextWhenLabelContainsGroundUsNoMark()
    {
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('addEndOfDayTextInDeliveryDate');
        $method->setAccessible(true);

        if (!$reflection->hasConstant('GROUND_US_NO_MARK')) {
            $groundUsNoMark = 'GROUND_US_NO_MARK';
            $eodText = 'End of Day';
        } else {
            $groundUsNoMark = $reflection->getConstant('GROUND_US_NO_MARK');
            $eodText = $reflection->getConstant('EOD_TEXT');
        }

        $label = 'Shipping via ' . $groundUsNoMark;
        $deliveryDate = '2024-06-01';

        if (!$reflection->hasConstant('EOD_TEXT')) {
            $eodText = 'End of Day';
        }
        $expectedDate = date('l, F d', strtotime($deliveryDate)) . ', ' . $eodText;

        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$label, $deliveryDate]);
        $this->assertEquals($expectedDate, $result);
    }

    /**
     * Tests that the checkCBBSampleProductInCartByShop method returns false
     * when the 'additionalData' property of a quote item is null.
     *
     * This test sets up a mock quote item to return null for getAdditionalData(),
     * constructs a shop array containing this item, and uses reflection to invoke
     * the protected/private checkCBBSampleProductInCartByShop method.
     * It then asserts that the result is false.
     */
    public function testCheckCBBSampleProductInCartByShopReturnsFalseWhenAdditionalDataIsNull()
    {
        $this->quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(null);

        $shop = ['items' => [$this->quoteItem]];

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('checkCBBSampleProductInCartByShop');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$shop]);
        $this->assertFalse($result);
    }

    /**
     * Tests that checkCBBSampleProductInCartByShop returns false when a quote item has 'punchout_enabled' set to true in its additional data.
     *
     * This test sets up a mock quote item with 'punchout_enabled' enabled, adds it to a shop array,
     * and uses reflection to invoke the protected/private method checkCBBSampleProductInCartByShop.
     * It asserts that the method returns false, verifying the correct behavior when punchout is enabled.
     */
    public function testCheckCBBSampleProductInCartByShopReturnsFalseWhenPunchoutEnabled()
    {
        $this->quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode(['punchout_enabled' => true]));

        $shop = ['items' => [$this->quoteItem]];

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('checkCBBSampleProductInCartByShop');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$shop]);
        $this->assertFalse($result);
    }

    /**
     * Tests that the checkCBBSampleProductInCartByShop method returns true
     * when the 'force_mirakl_shipping_options' flag is set to true in the offer's additional info.
     *
     * - Asserts that the method returns true under these conditions.
     */
    public function testCheckCBBSampleProductInCartByShopReturnsTrueWhenForceMiraklShippingOptionsIsTrue()
    {
        $miraklOfferId = 123;
        $this->quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode(['punchout_enabled' => false]));
        $this->quoteItem->expects($this->any())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn($miraklOfferId);

        $offerMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAdditionalInfo'])
            ->getMock();
        $offerMock->expects($this->any())
            ->method('getAdditionalInfo')
            ->willReturn(['force_mirakl_shipping_options' => 'true']);

        $this->offerCollectionFactory->method('create')->willReturn($this->offerCollection);
        $this->offerCollection->method('addFieldToFilter')->willReturnSelf();
        $this->offerCollection->method('getItems')->willReturn([$offerMock]);
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('checkCBBSampleProductInCartByShop');
        $method->setAccessible(true);

        $shop = ['items' => [$this->quoteItem]];
        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$shop]);
        $this->assertTrue($result);
    }

    /**
     * Tests that the getItemShippingTypes method returns the expected shipping types for a given quote item and address.
     *
     */
    public function testGetItemShippingTypesReturnsExpectedResult()
    {
        $expectedShippingTypes = ['type1', 'type2'];
        $this->quoteItemHelper->expects($this->any())
            ->method('getItemShippingTypes')
            ->with($this->quoteItem, $this->addressInterface)
            ->willReturn($expectedShippingTypes);

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('getItemShippingTypes');
        $method->setAccessible(true);

        $result = $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [$this->quoteItem, $this->addressInterface]
        );
        $this->assertSame($expectedShippingTypes, $result);
    }

    /**
     * Tests that getItemSelectedShippingType returns the expected result when a shipping type code exists.
     */
    public function testGetItemSelectedShippingTypeReturnsByCodeWhenShippingTypeCodeExists()
    {
        $shippingTypeCode = 'EXPRESS';
        $expectedResult = 'ShippingTypeByCode';

        $this->quoteItem->expects($this->any())
            ->method('getMiraklShippingType')
            ->willReturn($shippingTypeCode);

        $this->quoteUpdater->expects($this->any())
            ->method('getItemShippingTypeByCode')
            ->with($this->quoteItem, $shippingTypeCode)
            ->willReturn($expectedResult);

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('getItemSelectedShippingType');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$this->quoteItem]);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests that getItemSelectedShippingType returns the selected shipping type
     */
    public function testGetItemSelectedShippingTypeReturnsSelectedShippingTypeWhenNoShippingTypeCode()
    {
        $expectedResult = 'SelectedShippingType';

        $this->quoteItem->expects($this->any())
            ->method('getMiraklShippingType')
            ->willReturn(null);

        $this->quoteUpdater->expects($this->any())
            ->method('getItemSelectedShippingType')
            ->with($this->quoteItem)
            ->willReturn($expectedResult);

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('getItemSelectedShippingType');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->shippingMethodManagementPlugin, [$this->quoteItem]);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the createDataForFedexRatesApi method to ensure it builds the correct request
     * array and calls the helper with expected parameters.
     **/
    public function testCreateDataForFedexRatesApiBuildsRequestAndCallsHelper()
    {
        $shipDate = '2024-06-01';
        $shipAccountNumber = '123456789';
        $customerShippingAccount3PEnabled = true;
        $regionArray = ['code' => 'TN'];

        $shopArrayData = [
            'additional_info' => [
                'contact_info' => [
                    'city' => 'Memphis',
                    'state' => 'TN',
                    'zip_code' => '38116',
                    'country' => 'US'
                ],
                'shipping_zones' => ['us']
            ]
        ];

        $this->shopInterface->method('getData')->willReturn($shopArrayData);
        $this->address->method('getCity')->willReturn('Dallas');
        $this->address->method('getRegionCode')->willReturn('TX');
        $this->address->method('getPostcode')->willReturn('75001');
        $this->address->method('getCountryId')->willReturn('US');
        $this->address->method('getCompany')->willReturn(null);

        $regionMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toArray'])
            ->getMock();
        $regionMock->method('toArray')->willReturn($regionArray);

        $this->regionCollection->method('addRegionNameFilter')->willReturnSelf();
        $this->regionCollection->method('getFirstItem')->willReturn($regionMock);

        $this->collectionFactory->method('create')->willReturn($this->regionCollection);
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $property = $reflection->getProperty('totalCartWeight');
        $property->setAccessible(true);
        $property->setValue($this->shippingMethodManagementPlugin, 10.5);

        $expectedRequestArray = [
            'rateRequestControlParameters' => [
                'rateSortOrder' => 'SERVICENAMETRADITIONAL',
                'returnTransitTimes' => true,
            ],
            'requestedShipment' => [
                'shipDateStamp' => $shipDate,
                'shipper' => [
                    'accountNumber' => ['key' => $shipAccountNumber],
                    'address' => [
                        'city' => 'Memphis',
                        'stateOrProvinceCode' => 'TN',
                        'postalCode' => '38116',
                        'countryCode' => 'US',
                    ]
                ],
                'recipient' => [
                    'address' => [
                        'city' => 'Dallas',
                        'stateOrProvinceCode' => 'TX',
                        'postalCode' => '75001',
                        'countryCode' => 'US',
                        'residential' => true,
                    ]
                ],
                'pickupType' => 'DROPOFF_AT_FEDEX_LOCATION',
                'rateRequestType' => ['ACCOUNT'],
                'requestedPackageLineItems' => [
                [
                    'weight' => [
                        'units' => 'LB',
                        'value' => 10.5
                    ]
                ]
                ],
                'preferredCurrency' => 'USD',
            ],
                'carrierCodes' => ['FDXE', 'FDXG'],
                'returnLocalizedDateTime' => true,
            ];
        $expectedJson = json_encode($expectedRequestArray);

        $this->data->method('getResponseFromFedexRatesAPI')
            ->with($customerShippingAccount3PEnabled, $shipAccountNumber, $expectedJson)
            ->willReturn('fedex_response');

        $method = $reflection->getMethod('createDataForFedexRatesApi');
        $method->setAccessible(true);
        $response = $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [$shipDate, $this->shopInterface, $this->address, $shipAccountNumber, $customerShippingAccount3PEnabled]
        );

        $this->assertEquals('fedex_response', $response);
    }

    /**
     * Tests that the handleMiraklShippingTypes method skips processing
     * when the quote is not a Mirakl quote or when there is no shipping address.
     *
     * This test verifies that:
     * - If the quote is not identified as a Mirakl quote by the quote helper,
     *   or if the shipping address is null,
     * - The shipping methods array remains unchanged (empty).
     *
     * Mocks:
     * - The quoteHelper mock is set up to return false for isMiraklQuote.
     *
     * Assertions:
     * - Asserts that the $shippingMethods array is empty after invoking the method.
     */
    public function testHandleMiraklShippingTypesSkipsIfNotMiraklQuoteOrNoShippingAddress()
    {
        $shippingMethods = [];
        $error = [];
        $shippingAddress = null;

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('handleMiraklShippingTypes');
        $method->setAccessible(true);
        $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [$this->quoteMock,
            &$shippingMethods,
            &$error,
            $shippingAddress
            ]
        );
        $this->assertSame([], $shippingMethods);
    }

    /**
     * Tests the `aroundEstimateByExtendedAddress` method
     *
     * the result of the plugin's method matches the expected shipping methods.
     */
    public function testAroundEstimateByExtendedAddressHandlesMiraklShippingTypesForAllSellers()
    {
        $cartId = 1;
        $storeId = 1;
        $productId = 123;
        $shippingMethods = ['method1'];
        $expectedOffers = [$this->offerCollection];

        $shippingMethodMock1 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock1->expects($this->any())
            ->method('getBaseAmount')
            ->willReturn(10);

        $shippingMethodMock2 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock2->expects($this->any())
            ->method('getBaseAmount')
            ->willReturn(20);

        $shippingMethods = [$shippingMethodMock1, $shippingMethodMock2];

        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods(['isAddressValid', 'isPickup', 'handleMiraklShippingTypesForAllSellers'])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('isAddressValid')
            ->with($this->addressInterface)
            ->willReturn(true);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $proceed = function () use ($shippingMethods) {
            return $shippingMethods;
        };

        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getItemsCount')->willReturn(2);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getData')->willReturn(1);
        $this->quoteItem->expects($this->any())->method('getAdditionalData')
            ->willReturn('{"shippingTypes":["GROUND_US"]}');
        $this->quoteItem->expects($this->any())->method('getWeight')->willReturn(1);

        $this->quoteSynchronizer->expects($this->any())
            ->method('getGroupedItems')
            ->willReturn([$this->quoteItem]);

        $this->quoteItem->expects($this->any())->method('getMiraklShippingType')->willReturn(1);

        $this->quoteUpdater->expects($this->any())
            ->method('getItemShippingTypeByCode')
            ->willReturn('test_shipping_type');

        $this->shopManagementInterface->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($this->shopInterface);

        $this->quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->product);

        $this->quoteMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->productRepositoryInterface->expects($this->any())
            ->method('getById')
            ->with($productId, false, $storeId)
            ->willReturn($this->productInterface);

        $this->shopInterface->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn([
                'origin_combined_offers' => true,
                'origin_shop_city' => 'Test City',
                'origin_shop_state' => 'Test State',
                'origin_shop_zipcode' => '12345',
                'freight_enabled' => true
            ]);

        $this->offerCollectionFactory->method('create')->willReturn($this->offerCollection);
        $this->offerCollection->method('addFieldToFilter')->willReturnSelf();
        $this->offerCollection->method('getItems')->willReturn($expectedOffers);
        
        $this->addressInterface->expects($this->any())
            ->method('getRegionCode')
            ->willReturn('CA');

        $this->offerCollection->expects($this->any())
            ->method('getAdditionalInfo')
            ->willReturn([
                'origin_address_states' => 'CA,NY',
                'origin_address_reference' => 'ref123',
                'origin_city' => 'Los Angeles',
                'origin_state' => 'CA',
                'origin_zipcode' => '90001'
            ]);

        $this->offerCollection->method('getId')->willReturn(1);

        $this->data->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isMktCbbEnabled')
            ->willReturn(true);

        $this->quoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $this->packagingCheckoutPricing->expects($this->any())
            ->method('getPackagingItems')
            ->willReturn([]);

        $this->shopRepositoryInterface->expects($this->any())
            ->method('getById')
            ->willReturn($this->shopInterface);

        $this->shopInterface->method('getTimezone')->willReturn('America/Chicago');
        $this->data->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(false);

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );

        $this->assertEquals($shippingMethods, $result);
    }

    /**
     * Tests the aroundEstimateByAddressId method of ShippingMethodManagementPlugin.
     *
     */
    public function testAroundEstimateByAddressIdReturnsProceedResultAndHandlesMiraklShippingTypes()
    {
        $cartId = 1;
        $addressId = 2;
        $shippingMethods = ['method1', 'method2'];

        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getItemsCount')->willReturn(2);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods(['isPickup', 'handleMiraklShippingTypesForAllSellers'])
            ->getMock();

        $requestData = ['isPickup' => true];
            $this->requestInterface->expects($this->any())
                ->method('getContent')
                ->willReturn(json_encode($requestData));

        $this->miraklQuoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);
        $proceed = function ($cartIdArg, $addressIdArg) use ($cartId, $addressId, $shippingMethods) {
            $this->assertEquals($cartId, $cartIdArg);
            $this->assertEquals($addressId, $addressIdArg);
            return $shippingMethods;
        };

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByAddressId(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $addressId
        );

        $this->assertSame($shippingMethods, $result);
    }

    /**
     * Tests that the handleMiraklShippingTypes method correctly populates shipping methods with FedEx rates.
     *
     * This test sets up a mock quote item with Mirakl shipping type and various dependencies,
     * simulating a scenario where FedEx shipping rates are available and should be returned
     * as shipping methods. It verifies that the resulting shipping methods array contains
     * the expected FedEx carrier code.
     *
     */
    public function testHandleMiraklShippingTypesPopulatesShippingMethodsWithFedexRates()
    {
        $shippingMethods = [];
        $error = [];
        $shippingAddress = $this->addressInterface;
        $shippingTypeCode = 'EXPRESS';
        $expectedResult = 'ShippingTypeByCode';
        $accountNumber = '123456789';
        $requestData = ['fedEx_account_number' => $accountNumber];

        $this->quoteItem->expects($this->any())->method('getMiraklOfferId')->willReturn(1);
        $this->quoteItem->expects($this->any())->method('getQty')->willReturn(2);
        $this->quoteItem->expects($this->any())->method('getAdditionalData')
            ->willReturn(json_encode(['business_days' => 3]));
        $this->quoteItem->expects($this->any())->method('getSku')->willReturn('sku1');
        $this->quoteItem->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->quoteItem->expects($this->any())->method('getData')->willReturnMap([
            ['mirakl_offer_id', 1],
            ['mirakl_shop_name', 'ShopName']
        ]);
        $this->quoteItem->expects($this->any())->method('getId')->willReturn(10);
        $this->quoteItem->expects($this->any())->method('getWeight')->willReturn(5);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);

        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods([
                'getItemsWithOfferCombined',
                'getItemSelectedShippingType',
                'getFilteredOffers',
                'getLbsWeight',
                'createDataForFedexRatesApiEnhancement',
                'getFedExAccountNumber'
            ])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('getItemsWithOfferCombined')
            ->willReturn([$this->quoteItem]);
        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('getLbsWeight')
            ->with($this->quoteItem)
            ->willReturn(5);

        $this->quoteItem->expects($this->any())
            ->method('getMiraklShippingType')
            ->willReturn($shippingTypeCode);

        $this->quoteUpdater->expects($this->any())
            ->method('getItemShippingTypeByCode')
            ->with($this->quoteItem, $shippingTypeCode)
            ->willReturn($expectedResult);

        $this->shopInterface->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn([
                'origin_combined_offers' => true,
                'origin_shop_city' => 'Test City',
                'origin_shop_state' => 'Test State',
                'origin_shop_zipcode' => '12345',
                'freight_enabled' => true,
                'shipping_rate_option' => 'fedex-shipping-rates',
                'shipping_cut_off_time' => '17:00',
                'shipping_seller_holidays' => 'test_holiday',
                'additional_processing_days' => 1,
                'shipping_account_number' => 'acc123',
                'customer_shipping_account_enabled' => true,
                'shipping_methods' => json_encode([
                    ['shipping_method_name' => 'FEDEX_2_DAY']
                ]),
            ]);

        $this->shopManagementInterface->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($this->shopInterface);

        $this->quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->product);

        $this->shopInterface->method('getTimezone')->willReturn('America/Chicago');

        $this->checkoutConfig->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->testGetCompanyAllowedShippingMethodsReturnsMappedMethods();

        $offerMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAdditionalInfo', 'getId'])
            ->getMock();
        $offerMock->method('getAdditionalInfo')->willReturn([
            'origin_address_states' => 'TN',
            'origin_address_reference' => 'ref1',
            'origin_city' => 'Memphis',
            'origin_state' => 'TN',
            'origin_zipcode' => '38116',
            'shipping_rate_option' => 'fedex-shipping-rates',
        ]);
        $offerMock->method('getId')->willReturn(1);

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('getFilteredOffers')
            ->willReturn([$offerMock]);

        $shippingAddress = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shippingAddress->method('getRegionCode')->willReturn('TN');

        $this->timezoneInterface->expects($this->any())
            ->method('formatDateTime')
            ->willReturn('2024-06-01T10:00:00');

        $this->buildDeliveryDate->expects($this->any())
            ->method('getAllowedDeliveryDate')
            ->willReturn(strtotime('2024-06-02'));

        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));

        $this->marketplaceHelper->expects($this->any())
            ->method('isCustomerShippingAccount3PEnabled')
            ->willReturn(true);
        $this->marketplaceHelper->expects($this->any())
            ->method('isVendorSpecificCustomerShippingAccountEnabled')
            ->willReturn(true);

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('createDataForFedexRatesApiEnhancement')
            ->willReturn([
                [
                    'operationalDetail' => [
                        'deliveryDate' => '2024-06-03 17:00:00'
                    ],
                    'serviceDescription' => [
                        'serviceType' => 'FEDEX_2_DAY',
                        'description' => 'FedEx 2 Day'
                    ],
                    'ratedShipmentDetails' => [
                        ['totalNetFedExCharge' => 25.00]
                    ]
                ]
            ]);

        $this->data->expects($this->any())
            ->method('handleMethodTitle')
            ->willReturn('FedEx 2 Day');

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('handleMiraklShippingTypes');
        $method->setAccessible(true);

        $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [$this->quoteMock, &$shippingMethods, &$error, $shippingAddress]
        );

        $this->assertEquals('marketplace_FEDEX_2_DAY', $shippingMethods[0]['carrier_code']);
    }

    /**
     * Tests the createDataForFedexRatesApiEnhancement method to ensure it correctly prepares
     */
    public function testCreateDataForFedexRatesApiEnhancement()
    {
        $shipDate = '2024-06-01';
        $shipAccountNumber = '123456789';
        $customerShippingAccount3PEnabled = true;
        $weight = 10.5;
        $totalPackageCount = 2;

        $this->shopInterface->expects($this->any())
            ->method('getData')
            ->willReturn([
                "additional_info" => [
                    "contact_info" => [
                        "state" => "CA",
                        "city" => "Los Angeles",
                        "zip_code" => "90001",
                        "country" => "US"
                    ],
                    "shipping_zones" => ["US"]
                ]
            ]);

        $this->addressInterface->expects($this->any())
            ->method('getCity')
            ->willReturn("New York");
        $this->addressInterface->expects($this->any())
            ->method('getRegionCode')
            ->willReturn("NY");
        $this->addressInterface->expects($this->any())
            ->method('getPostcode')
            ->willReturn("10001");
        $this->addressInterface->expects($this->any())
            ->method('getCountryId')
            ->willReturn("US");
        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode([
                'address' => [
                    'custom_attributes' => [
                        ['attribute_code' => 'residence_shipping', 'value' => true]
                    ]
                ]
            ]));

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->data->expects($this->any())
            ->method('getResponseFromFedexRatesAPI')
            ->willReturn('{"response":"success"}');
        $this->regionCollection->expects($this->any())
            ->method('addRegionNameFilter')
            ->willReturnSelf();
        $this->regionCollection->expects($this->any())
            ->method('addCountryCodeFilter')
            ->willReturnSelf();
        $this->regionCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn(new \Magento\Framework\DataObject(['code' => 'NY']));

        $this->collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->regionCollection);
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('createDataForFedexRatesApiEnhancement');
        $method->setAccessible(true);

        $result = $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [
                $shipDate,
                $this->shopInterface,
                $this->addressInterface,
                $shipAccountNumber,
                $customerShippingAccount3PEnabled,
                $weight,
                $totalPackageCount
            ]
        );
        $this->assertStringContainsString('success', $result);
    }

    /**
     * Tests the createDataForFedexRatesApiEnhancement method to ensure it correctly handles the
     * 'residence_shipping' custom attribute value when preparing data for the FedEx Rates API enhancement.
     *
     */
    public function testCreateDataForFedexRatesApiEnhancementHandlesResidenceShippingValue()
    {
        $shipDate = '2024-06-01';
        $shipAccountNumber = '123456789';
        $customerShippingAccount3PEnabled = true;
        $weight = 10.5;
        $totalPackageCount = 2;

        $this->shopInterface->expects($this->any())
            ->method('getData')
            ->willReturn([
                "additional_info" => [
                    "contact_info" => [
                        "state" => "CA",
                        "city" => "Los Angeles",
                        "zip_code" => "90001",
                        "country" => "US"
                    ],
                    "shipping_zones" => ["US"]
                ]
            ]);

        $this->addressInterface->expects($this->any())
            ->method('getCity')
            ->willReturn("New York");
        $this->addressInterface->expects($this->any())
            ->method('getRegionCode')
            ->willReturn("NY");
        $this->addressInterface->expects($this->any())
            ->method('getPostcode')
            ->willReturn("10001");
        $this->addressInterface->expects($this->any())
            ->method('getCountryId')
            ->willReturn("US");

        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode([
                'address' => [
                    'custom_attributes' => [
                        ['attribute_code' => 'residence_shipping', 'value' => true]
                    ]
                ]
            ]));

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->data->expects($this->any())
            ->method('getResponseFromFedexRatesAPI')
            ->willReturn('{"response":"success"}');

        $this->regionCollection->expects($this->any())
            ->method('addRegionNameFilter')
            ->willReturnSelf();
        $this->regionCollection->expects($this->any())
            ->method('addCountryCodeFilter')
            ->willReturnSelf();
        $this->regionCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn(new \Magento\Framework\DataObject(['code' => 'NY']));

        $this->collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->regionCollection);

        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('createDataForFedexRatesApiEnhancement');
        $method->setAccessible(true);

        $result = $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [
                $shipDate,
                $this->shopInterface,
                $this->addressInterface,
                $shipAccountNumber,
                $customerShippingAccount3PEnabled,
                $weight,
                $totalPackageCount
            ]
        );

        $this->toggleConfig->expects($this->exactly(1))
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $result = $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [
                $shipDate,
                $this->shopInterface,
                $this->addressInterface,
                $shipAccountNumber,
                $customerShippingAccount3PEnabled,
                $weight,
                $totalPackageCount
            ]
        );
        $this->assertStringContainsString('success', $result);
    }

    /**
     * Tests the handleMiraklShippingTypes method to ensure it populates shipping methods
     * without FedEx rates for Mirakl quotes.
     *
     * - Asserts that the shippingMethods array contains the expected carrier code.
     */
    public function testHandleMiraklShippingTypesPopulatesShippingMethodsWithOutFedexRates()
    {
        $shippingMethods = [];
        $error = [];
        $shippingTypeCode = 'EXPRESS';
        $expectedResult1 = 'ShippingTypeByCode';
        $accountNumber = '123456789';
        $requestData = ['fedEx_account_number' => $accountNumber];
        $expectedShippingTypes = [$this->shippingFeeTypeCollection];
        $this->quoteItem->expects($this->any())->method('getMiraklOfferId')->willReturn(1);
        $this->shippingFeeTypeCollection->expects($this->any())
            ->method('getCode')
            ->willReturn('test');
        $this->shippingFeeTypeCollection->expects($this->any())
            ->method('getLabel')
            ->willReturn('test');
        $this->shippingFeeTypeCollection->expects($this->any())
            ->method('getData')
            ->willReturn('test');
        $this->shippingFeeType->expects($this->any())
            ->method('getCode')
            ->willReturn($expectedResult1);
        $this->quoteItem->expects($this->any())->method('getQty')->willReturn(2);
        $this->quoteItem->expects($this->any())->method('getAdditionalData')
            ->willReturn(json_encode(['business_days' => 3]));
        $this->quoteItem->expects($this->any())->method('getSku')->willReturn('sku1');
        $this->quoteItem->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->quoteItem->expects($this->any())->method('getData')->willReturnMap([
            ['mirakl_offer_id', 1],
            ['mirakl_shop_name', 'ShopName']
        ]);
        $this->quoteItem->expects($this->any())->method('getId')->willReturn(10);
        $this->quoteItem->expects($this->any())->method('getWeight')->willReturn(5);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);
        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods([
                'getItemsWithOfferCombined',
                'getItemSelectedShippingType',
                'getFilteredOffers',
                'getLbsWeight',
                'createDataForFedexRatesApiEnhancement',
                'getFedExAccountNumber',
                'getItemShippingTypes'
            ])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('getItemsWithOfferCombined')
            ->willReturn([$this->quoteItem]);
        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('getLbsWeight')
            ->with($this->quoteItem)
            ->willReturn(5);

        $this->quoteItem->expects($this->any())
            ->method('getMiraklShippingType')
            ->willReturn($shippingTypeCode);

        $this->quoteUpdater->expects($this->any())
            ->method('getItemShippingTypeByCode')
            ->with($this->quoteItem, $shippingTypeCode)
            ->willReturn($this->shippingFeeType);

        $this->shopInterface->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn([
                'origin_combined_offers' => true,
                'origin_shop_city' => 'Test City',
                'origin_shop_state' => 'Test State',
                'origin_shop_zipcode' => '12345',
                'freight_enabled' => true,
                'shipping_rate_option' => 'fedex-shipping-rates1',
                'shipping_cut_off_time' => '17:00',
                'shipping_seller_holidays' => 'test_holiday',
                'additional_processing_days' => 1,
                'shipping_account_number' => 'acc123',
                'customer_shipping_account_enabled' => true,
                'shipping_methods' => json_encode([
                    ['shipping_method_name' => 'FEDEX_2_DAY']
                ]),
            ]);

        $this->shopManagementInterface->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($this->shopInterface);

        $this->shopInterface->method('getTimezone')->willReturn('America/Chicago');

        $this->checkoutConfig->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);
        $offerMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAdditionalInfo', 'getId'])
            ->getMock();
        $offerMock->method('getAdditionalInfo')->willReturn([
            'origin_address_states' => 'TN',
            'origin_address_reference' => 'ref1',
            'origin_city' => 'Memphis',
            'origin_state' => 'TN',
            'origin_zipcode' => '38116',
            'shipping_rate_option' => 'fedex-shipping-rates1',
        ]);
        $offerMock->method('getId')->willReturn(1);

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('getFilteredOffers')
            ->willReturn([$offerMock]);

        $this->addressInterface->method('getRegionCode')->willReturn('TN');

        $this->timezoneInterface->expects($this->any())
            ->method('formatDateTime')
            ->willReturn('2024-06-01T10:00:00');

        $this->buildDeliveryDate->expects($this->any())
            ->method('getAllowedDeliveryDate')
            ->willReturn(strtotime('2024-06-02'));

        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));

        $this->marketplaceHelper->expects($this->any())
            ->method('isCustomerShippingAccount3PEnabled')
            ->willReturn(true);
        $this->marketplaceHelper->expects($this->any())
            ->method('isVendorSpecificCustomerShippingAccountEnabled')
            ->willReturn(true);
        $this->quoteItemHelper->expects($this->any())
            ->method('getItemShippingTypes')
            ->with($this->quoteItem, $this->addressInterface)
            ->willReturn($expectedShippingTypes);
        $reflection = new \ReflectionClass($this->shippingMethodManagementPlugin);
        $method = $reflection->getMethod('handleMiraklShippingTypes');
        $method->setAccessible(true);

        $method->invokeArgs(
            $this->shippingMethodManagementPlugin,
            [$this->quoteMock, &$shippingMethods, &$error, $this->addressInterface]
        );
        $this->assertEquals('marketplace_1', $shippingMethods[0]['carrier_code']);
    }

    /**
     * Tests the behavior of the ShippingMethodManagementPlugin::aroundEstimateByExtendedAddress method
     * when the "isMktCbbEnabled" feature is disabled.
     *
     * - Asserting that the resulting shipping method contains the correct carrier code.
     */
    public function testAroundEstimateByExtendedAddressHandlesIsMktCbbDisabled()
    {
        $cartId = 1;
        $storeId = 1;
        $productId = 123;
        $shippingMethods = ['method1'];
        $expectedOffers = [$this->offerCollection];
        $expectedShippingTypes = [$this->shippingFeeTypeCollection];

        $shippingMethodMock1 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock1->expects($this->any())
            ->method('getBaseAmount')
            ->willReturn(10);

        $shippingMethodMock2 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock2->expects($this->any())
            ->method('getBaseAmount')
            ->willReturn(20);

        $shippingMethods = [$shippingMethodMock1, $shippingMethodMock2];
        $this->quoteItem->expects($this->any())->method('getMiraklOfferId')->willReturn(1);

        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods(['isAddressValid', 'isPickup', 'handleMiraklShippingTypesForAllSellers'])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('isAddressValid')
            ->with($this->addressInterface)
            ->willReturn(true);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $proceed = function () use ($shippingMethods) {
            return $shippingMethods;
        };

        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getItemsCount')->willReturn(2);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getData')->willReturn(1);
        $this->quoteItem->expects($this->any())->method('getAdditionalData')
            ->willReturn('{"shippingTypes":["GROUND_US"]}');
        $this->quoteItem->expects($this->any())->method('getWeight')->willReturn(1);

        $this->quoteSynchronizer->expects($this->any())
            ->method('getGroupedItems')
            ->willReturn([$this->quoteItem]);

        $this->quoteItem->expects($this->any())->method('getMiraklShippingType')->willReturn(1);

        $this->shopManagementInterface->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($this->shopInterface);

        $this->quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->product);

        $this->quoteMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->productRepositoryInterface->expects($this->any())
            ->method('getById')
            ->with($productId, false, $storeId)
            ->willReturn($this->productInterface);

        $this->shopInterface->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn([
                'origin_combined_offers' => true,
                'origin_shop_city' => 'Test City',
                'origin_shop_state' => 'Test State',
                'origin_shop_zipcode' => '12345',
                'freight_enabled' => true,
                'seller_name' => 'Test Seller',
            ]);

        $this->offerCollectionFactory->method('create')->willReturn($this->offerCollection);
        $this->offerCollection->method('addFieldToFilter')->willReturnSelf();
        $this->offerCollection->method('getItems')->willReturn($expectedOffers);
        
        $this->addressInterface->expects($this->any())
            ->method('getRegionCode')
            ->willReturn('CA');

        $this->offerCollection->expects($this->any())
            ->method('getAdditionalInfo')
            ->willReturn([
                'origin_address_states' => 'CA,NY',
                'origin_address_reference' => 'ref123',
                'origin_city' => 'Los Angeles',
                'origin_state' => 'CA',
                'origin_zipcode' => '90001'
            ]);

        $this->offerCollection->method('getId')->willReturn(1);

        $this->data->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isMktCbbEnabled')
            ->willReturn(false);

        $this->quoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $this->packagingCheckoutPricing->expects($this->any())
            ->method('getPackagingItems')
            ->willReturn([]);

        $this->shopRepositoryInterface->expects($this->any())
            ->method('getById')
            ->willReturn($this->shopInterface);

        $this->shopInterface->method('getTimezone')->willReturn('America/Chicago');
        $this->data->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(false);

            $this->quoteItem->expects($this->any())
            ->method('getMiraklShippingType')
            ->willReturn($this->shippingFeeType);

        $this->quoteUpdater->expects($this->any())
            ->method('getItemShippingTypeByCode')
            ->willReturn($this->shippingFeeType);

        $this->quoteItemHelper->expects($this->any())
            ->method('getItemShippingTypes')
            ->with($this->quoteItem, $this->addressInterface)
            ->willReturn($expectedShippingTypes);

        $this->deliveryTime->method('getLatestDeliveryDate')->willReturn(new \DateTime('2025-06-14'));
        $this->shippingFeeTypeCollection->method('getDeliveryTime')->willReturn($this->deliveryTime);
        $this->shopInterface->method('getSellerAltName')
            ->willReturn('test_seller');
    
        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );
        $this->assertEquals('marketplace_1', $result[0]['carrier_code']);
    }

    /**
     * Tests the `aroundEstimateByExtendedAddress` method of the ShippingMethodManagementPlugin
     * when freight shipping is enabled.
     *
     * - Executes the `aroundEstimateByExtendedAddress` method
     */
    public function testAroundEstimateByExtendedAddressIsFreightShippingEnabled()
    {
        $cartId = 1;
        $storeId = 1;
        $productId = 123;
        $shippingMethods = ['method1'];
        $expectedOffers = [$this->offerCollection];

        $shippingMethodMock1 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock1->expects($this->any())
            ->method('getBaseAmount')
            ->willReturn(10);

        $shippingMethodMock2 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock2->expects($this->any())
            ->method('getBaseAmount')
            ->willReturn(20);

        $shippingMethods = [$shippingMethodMock1, $shippingMethodMock2];

        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
            ->setConstructorArgs([
                $this->cartRepositoryInterface,
                $this->quoteHelper,
                $this->quoteItemHelper,
                $this->quoteUpdater,
                $this->quoteAddressResourceFactory,
                $this->quoteSynchronizer,
                $this->data,
                $this->shopManagementInterface,
                $this->offerCollector,
                $this->buildDeliveryDate,
                $this->collectionFactory,
                $this->toggleConfig,
                $this->timezoneInterface,
                $this->miraklQuoteHelper,
                $this->requestInterface,
                $this->offerCollectionFactory,
                $this->retailHelper,
                $this->checkoutConfig,
                $this->marketplaceHelper,
                $this->shopRepositoryInterface,
                $this->packagingCheckoutPricing,
                $this->freightCheckoutPricing,
                $this->nonCustomizableProduct,
                $this->config
            ])
            ->onlyMethods([
                'isAddressValid',
                'isPickup',
                'handleMiraklShippingTypesForAllSellers',
                'getCompanyAllowedShippingMethods'
                ])
            ->getMock();

        $this->shippingMethodManagementPlugin->expects($this->any())
            ->method('isAddressValid')
            ->with($this->addressInterface)
            ->willReturn(true);

        $this->cartRepositoryInterface->expects($this->any())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $proceed = function () use ($shippingMethods) {
            return $shippingMethods;
        };

        $this->quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getItemsCount')->willReturn(2);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getData')->willReturn(1);
        $this->quoteItem->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode([
                'shippingTypes' => ['GROUND_US'],
                'business_days' => 3
            ]));
        $this->quoteItem->expects($this->any())->method('getWeight')->willReturn(1);

        $this->quoteSynchronizer->expects($this->any())
            ->method('getGroupedItems')
            ->willReturn([$this->quoteItem]);

        $this->quoteItem->expects($this->any())->method('getMiraklShippingType')->willReturn(1);

        $this->quoteUpdater->expects($this->any())
            ->method('getItemShippingTypeByCode')
            ->willReturn('test_shipping_type');

        $this->shopManagementInterface->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($this->shopInterface);

        $this->quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->product);

        $this->quoteMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->productRepositoryInterface->expects($this->any())
            ->method('getById')
            ->with($productId, false, $storeId)
            ->willReturn($this->productInterface);

        $this->shopInterface->method('getShippingRateOption')
            ->willReturn([
                'freight_enabled' => true,
                'shipping_cut_off_time' => '17:00',
                'shipping_seller_holidays' => 'test_holiday',
                'additional_processing_days' => 1,
                'shipping_account_number' => '123456',
                'customer_shipping_account_enabled' => true,
            ]);
        $this->offerCollectionFactory->method('create')->willReturn($this->offerCollection);
        $this->offerCollection->method('addFieldToFilter')->willReturnSelf();
        $this->offerCollection->method('getItems')->willReturn($expectedOffers);
        
        $this->addressInterface->expects($this->any())
            ->method('getRegionCode')
            ->willReturn('CA');

        $this->offerCollection->expects($this->any())
            ->method('getAdditionalInfo')
            ->willReturn([
                'origin_address_states' => 'CA,NY',
                'origin_address_reference' => 'ref123',
                'origin_city' => 'Los Angeles',
                'origin_state' => 'CA',
                'origin_zipcode' => '90001',
                'business_days' => [],
            ]);

        $this->offerCollection->method('getId')->willReturn(1);
        $this->nonCustomizableProduct->expects($this->any())
            ->method('isMktCbbEnabled')
            ->willReturn(true);

        $this->quoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $this->packagingCheckoutPricing->expects($this->any())
            ->method('getPackagingItems')
            ->willReturn([1]);

        $this->shopRepositoryInterface->expects($this->any())
            ->method('getById')
            ->willReturn($this->shopInterface);

        $this->shopInterface->method('getTimezone')->willReturn('America/Chicago');
        $this->data->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $this->packagingCheckoutPricing->method('findSellerRecord')->willReturn([
            ['packaging' => ['type' => 'pallet']]
        ]);

        $this->timezoneInterface->expects($this->any())
            ->method('formatDateTime')
            ->willReturn('2024-06-01T10:00:00');

        $this->buildDeliveryDate->expects($this->any())
            ->method('getAllowedDeliveryDate')
            ->willReturn(strtotime('2024-06-02'));

        $requestData = [
            'address' => [
            'custom_attributes' => [
                    [
                        'attribute_code' => 'residence_shipping',
                        'value' => true,
                    ]
                ]
            ]
        ];
        $this->requestInterface->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('tiger_d213977')
            ->willReturn(true);

        $this->freightCheckoutPricing->method('execute')->willReturn(
            [
                [
                FedexRateApiDataInterface::SERVICE_TYPE => 'FEDEX_GROUND',
                FedexRateApiDataInterface::OPERATIONAL_DETAIL => [
                    FedexRateApiDataInterface::DELIVERY_DATA => '2024-06-02T10:00:00'
                ],
                    FedexRateApiDataInterface::RATED_SHIPMENT_DETAILS => [
                        [
                            'totalNetFedExCharge' => 25.75,
                            FedexRateApiDataInterface::SHIPMENT_RATE_DETAIL => [
                                FedexRateApiDataInterface::SURCHARGES => [
                                    [
                                        'type' => 'LIFTGATE_DELIVERY',
                                        'amount' => 15.50
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->checkoutConfig->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfig')
            ->willReturn(true);

        $this->shippingMethodManagementPlugin
            ->method('getCompanyAllowedShippingMethods')
            ->willReturn([
                [
                    'shipping_method_name' => 'FEDEX_GROUND',
                    'shipping_method_label' => 'FedEx Ground'
                ]
            ]);

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );

        $this->assertContains($shippingMethodMock1, $result);
    }

    /**
     * Tests the FedEx shipping rate configuration flow for the ShippingMethodManagementPlugin.
     *
     * @return void
     */
    public function testFedExShippingRateConfigurationFlow()
    {
        $cartId = 1;
        $storeId = 1;
        $productId = 123;
        $shippingMethods = ['method1'];
        $expectedOffers = [$this->offerCollection];
        $expectedShippingTypes = [$this->shippingFeeTypeCollection];

        $shippingMethodMock1 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock1->method('getBaseAmount')->willReturn(10);

        $shippingMethodMock2 = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock2->method('getBaseAmount')->willReturn(20);

        $shippingMethods = [$shippingMethodMock1, $shippingMethodMock2];

        $this->quoteItem->method('getMiraklOfferId')->willReturn(1);
        $this->quoteItem->method('getData')->willReturn(1);
        $this->quoteItem->expects($this->any())
           ->method('getAdditionalData')
           ->willReturn(json_encode([
               'shippingTypes' => ['GROUND_US'],
               'business_days' => 3
           ]));
        $this->quoteItem->method('getWeight')->willReturn(5);
        $this->quoteItem->method('getProduct')->willReturn($this->product);

        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getItemsCount')->willReturn(2);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->quoteMock->method('getAllItems')->willReturn([$this->quoteItem]);
        $this->quoteMock->method('getStoreId')->willReturn($storeId);

        $this->cartRepositoryInterface->method('getActive')->with($cartId)->willReturn($this->quoteMock);
        $this->quoteHelper->method('isMiraklQuote')->with($this->quoteMock)->willReturn(true);
        $this->quoteHelper->method('isFullMiraklQuote')->with($this->quoteMock)->willReturn(true);

        $this->quoteSynchronizer->method('getGroupedItems')->willReturn([$this->quoteItem]);

        $this->shopManagementInterface->method('getShopByProduct')->willReturn($this->shopInterface);

        $this->shopRepositoryInterface->method('getById')->willReturn($this->shopInterface);
        $this->shopInterface->method('getSellerAltName')->willReturn('test_seller');
        $this->shopInterface->method('getTimezone')->willReturn('America/Chicago');

        $this->productRepositoryInterface->method('getById')
           ->with($productId, false, $storeId)
           ->willReturn($this->productInterface);

        $this->addressInterface->method('getRegionCode')->willReturn('CA');

        $this->offerCollectionFactory->method('create')->willReturn($this->offerCollection);
        $this->offerCollection->method('addFieldToFilter')->willReturnSelf();
        $this->offerCollection->method('getItems')->willReturn($expectedOffers);
        $this->offerCollection->method('getId')->willReturn(1);
        $this->offerCollection->method('getAdditionalInfo')->willReturn([
           'origin_address_states' => 'CA,NY',
           'origin_address_reference' => 'ref123',
           'origin_city' => 'Los Angeles',
           'origin_state' => 'CA',
           'origin_zipcode' => '90001'
        ]);

        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(false);
        $this->data->method('isFreightShippingEnabled')->willReturn(false);

        $this->quoteItem->method('getMiraklShippingType')->willReturn($this->shippingFeeType);
        $this->quoteUpdater->method('getItemShippingTypeByCode')->willReturn($this->shippingFeeType);
        $this->quoteItemHelper->method('getItemShippingTypes')
           ->with($this->quoteItem, $this->addressInterface)
           ->willReturn($expectedShippingTypes);

        $this->deliveryTime->method('getLatestDeliveryDate')->willReturn(new \DateTime('2025-06-14'));
        $this->shippingFeeTypeCollection->method('getDeliveryTime')->willReturn($this->deliveryTime);

        $this->shopInterface->method('getShippingRateOption')->willReturn([
           'shipping_rate_option' => 'fedex-shipping-rates',
           'shipping_cut_off_time' => '16:00',
           'shipping_seller_holidays' => 'test_holiday',
           'additional_processing_days' => 2,
           'shipping_account_number' => 'vendor_acc_001',
           'customer_shipping_account_enabled' => true,
           'origin_combined_offers' => true,
           'origin_shop_city' => 'Test City',
           'origin_shop_state' => 'Test State',
           'origin_shop_zipcode' => '12345',
           'freight_enabled' => true,
           'seller_name' => 'Test Seller',
           'shipping_methods' => json_encode([
               ['shipping_method_name' => 'FEDEX_2_DAY']
           ]),
        ]);

        $this->shippingMethodManagementPlugin = $this->getMockBuilder(ShippingMethodManagementPlugin::class)
           ->setConstructorArgs([
               $this->cartRepositoryInterface,
               $this->quoteHelper,
               $this->quoteItemHelper,
               $this->quoteUpdater,
               $this->quoteAddressResourceFactory,
               $this->quoteSynchronizer,
               $this->data,
               $this->shopManagementInterface,
               $this->offerCollector,
               $this->buildDeliveryDate,
               $this->collectionFactory,
               $this->toggleConfig,
               $this->timezoneInterface,
               $this->miraklQuoteHelper,
               $this->requestInterface,
               $this->offerCollectionFactory,
               $this->retailHelper,
               $this->checkoutConfig,
               $this->marketplaceHelper,
               $this->shopRepositoryInterface,
               $this->packagingCheckoutPricing,
               $this->freightCheckoutPricing,
               $this->nonCustomizableProduct,
               $this->config
           ])
           ->onlyMethods([
            'isAddressValid',
            'getFedExAccountNumber',
            'createDataForFedexRatesApiEnhancement',
            'handleMiraklShippingTypesForAllSellers'
            ])
           ->getMock();

        $this->shippingMethodManagementPlugin->method('isAddressValid')->willReturn(true);

        $this->timezoneInterface->expects($this->any())
           ->method('formatDateTime')
           ->willReturn('2024-06-01T10:00:00');

        $this->buildDeliveryDate->expects($this->any())
           ->method('getAllowedDeliveryDate')
           ->willReturn(strtotime('2024-06-02'));

        $this->toggleConfig->expects($this->any())
           ->method('getToggleConfigValue')
           ->with('tiger_d213977')
           ->willReturn(true);

             $this->checkoutConfig->expects($this->any())
           ->method('isSelfRegCustomer')
           ->willReturn(true);
        $this->toggleConfig->expects($this->any())
           ->method('getToggleConfig')
           ->willReturn(true);

        $this->testGetCompanyAllowedShippingMethodsReturnsMappedMethods();

        $this->shippingMethodManagementPlugin->expects($this->any())
           ->method('createDataForFedexRatesApiEnhancement')
           ->willReturn([
               [
                   'operationalDetail' => [
                       'deliveryDate' => '2024-06-03 17:00:00'
                   ],
                   'serviceDescription' => [
                       'serviceType' => 'FEDEX_2_DAY',
                       'description' => 'FedEx 2 Day'
                   ],
                   'ratedShipmentDetails' => [
                       ['totalNetFedExCharge' => 25.00]
                   ]
               ]
           ]);

        $proceed = function () use ($shippingMethods) {
            return $shippingMethods;
        };

        $result = $this->shippingMethodManagementPlugin->aroundEstimateByExtendedAddress(
            $this->shippingMethodManagementInterface,
            $proceed,
            $cartId,
            $this->addressInterface
        );

        $expected = [
            'carrier_code' => 'marketplace_FEDEX_2_DAY',
            'method_code' => 'FEDEX_2_DAY',
            'carrier_title' => 'Fedex',
            'method_title' => '',
            'amount' => 25.0,
            'base_amount' => 25.0,
            'available' => true,
            'price_incl_tax' => 25.0,
            'price_excl_tax' => 25.0,
            'offer_id' => 1,
            'title' => 1,
            'selected' => 'marketplace_FEDEX_2_DAY_FEDEX_2_DAY',
            'selected_code' => 'FEDEX_2_DAY',
            'item_id' => null,
            'shipping_type_label' => 'FedEx 2 Day',
            'deliveryDate' => 'Monday, June 3, 5:00pm',
            'deliveryDateText' => 'Monday, June 3, 5:00pm',
            'marketplace' => true,
            'seller_id' => 1,
            'seller_name' => 'test_seller',
        ];

        $this->assertEquals($expected, $result[2]);
    }
}
