<?php

namespace Fedex\FXOPricing\Test\Unit\Model;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;

use Psr\Log\LoggerInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\Company;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Quote\Model\Quote;
use Fedex\FXOPricing\Model\FXORequestBuilder;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;

class FXORequestBuilderTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Delivery\Helper\Data)
     */
    protected $deliveryHelper;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Company\Helper\Data)
     */
    protected $companyHelper;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Punchout\Helper\Data)
     */
    protected $punchoutHelper;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Checkout\Model\Session)
     */
    protected $checkoutSession;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\EnvironmentManager\ViewModel\ToggleConfig)
     */
    protected $toggleConfig;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Cart\Helper\Data)
     */
    protected $cartDataHelper;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\Quote\Helper\Data)
     */
    protected $quote;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Quote\Model\Quote)
     */
    protected $quoteHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Checkout\Model\Cart)
     */
    protected $fxoRequestBuilder;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Fedex\MarketplaceProduct\Api\ShopManagementInterface)
     */
    protected $shopManagement;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Magento\Directory\Model\ResourceModel\Region\CollectionFactory)
     */
    protected $collectionFactory;


    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods([
                'isCommercialCustomer',
                'getCompanySite',
                'getGateToken',
                'getApiToken',
                'isSdeCustomer',
                'getRateRequestShipmentSpecialServices'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)
            ->setMethods(['getFedexAccountNumber', 'getCustomerCompany', 'getCompanyName', 'getSiteName'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->setMethods(['getAuthGatewayToken', 'getTazToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['getRemoveFedexAccountNumber', 'setServiceType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData', 'formatPrice', 'encryptData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteHelper = $this->getMockBuilder(QuoteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shopManagement = $this->getMockBuilder(ShopManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods([
                'getId',
                'getAllVisibleItems',
                'getAllItems',
                'deleteItem',
                'getData',
                'setData',
                'getCouponCode',
                'setDiscount',
                'setCouponCode',
                'setSubtotal',
                'setBaseSubtotal',
                'setGrandTotal',
                'setBaseGrandTotal',
                'save',
                'getCustomerShippingAddress',
                'getCustomerPickupLocationData',
                'getIsFromShipping',
                'getIsFromPickup',
                'getItemById'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->fxoRequestBuilder = $this->objectManager->getObject(
            FXORequestBuilder::class,
            [
                'logger' => $this->logger,
                'deliveryHelper' => $this->deliveryHelper,
                'companyHelper' => $this->companyHelper,
                'punchoutHelper' => $this->punchoutHelper,
                'checkoutSession' => $this->checkoutSession,
                'toggleConfig' => $this->toggleConfig,
                'quoteHelper' => $this->quoteHelper,
                'shopManagement'     => $this->shopManagement,
                'collectionFactory'  => $this->collectionFactory,
            ]
        );
    }

    /**
     * Test case for getAuthenticationDetails
     */
    public function testGetAuthenticationDetails()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('getCompanySite')->willReturn('Retail');
        $this->deliveryHelper->expects($this->any())->method('getGateToken')->willReturn('asxde');
        $this->deliveryHelper->expects($this->any())->method('getApiToken')->willReturn(['token' => 'axbsgth']);
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn('1234');
        $this->checkoutSession->expects($this->any())->method('getRemoveFedexAccountNumber')->willReturn(false);
        $this->quote->expects($this->any())->method('getData')->willReturn(null);
        $this->deliveryHelper->expects($this->any())->method('isSdeCustomer')->willReturn(true);
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')->willReturnSelf();
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->companyHelper->expects($this->any())->method('getCompanyName')->willReturn('Fedex');
        $this->cartDataHelper->expects($this->any())->method('decryptData')->willReturn('12345');
        $this->cartDataHelper->expects($this->any())->method('encryptData')->willReturn('12345');
        $this->assertNotNull($this->fxoRequestBuilder->getAuthenticationDetails(
            $this->quote,
            $this->cartDataHelper
        ));
    }

    /**
     * Test case for getAuthenticationDetailsWithFalseCommercialCustomer
     */
    public function testGetAuthenticationDetailsWithFalseCommercialCustomer()
    {
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn('test');
        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn('test2');
        $this->assertNotNull($this->fxoRequestBuilder->getAuthenticationDetails(
            $this->quote,
            $this->cartDataHelper
        ));
    }
    /**
     * Test getAuthenticationDetails with company site name
     */
    public function testGetAuthenticationDetailsWithCompanySiteName()
    {
        $company = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getSiteName'])
            ->getMock();

        $company->expects($this->any())  
            ->method('getSiteName')
            ->willReturn('TestSiteName');

        $this->deliveryHelper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn('asxde');
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn(['token' => 'axbsgth']);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(null);

        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFedexAccountNumber', 'getCustomerCompany'])
            ->addMethods(['isCommercialCustomer'])
            ->getMock();

        $this->companyHelper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn($company);
        $this->companyHelper->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn('1234');

        $reflectionClass = new \ReflectionClass($this->fxoRequestBuilder);
        $companyHelperProperty = $reflectionClass->getProperty('companyHelper');
        $companyHelperProperty->setAccessible(true);
        $companyHelperProperty->setValue($this->fxoRequestBuilder, $this->companyHelper);

        $result = $this->fxoRequestBuilder->getAuthenticationDetails($this->quote, $this->cartDataHelper);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('site', $result);
        $this->assertEquals('TestSiteName', $result['site']);
        $this->assertArrayHasKey('siteName', $result);
        $this->assertNull($result['siteName']);
    }
    /**
     * Test case for getPickShipData
     */
    public function testGetPickShipData()
    {
        $itemData = [
            'productAssociations' => [
                [
                    'id' => 1,
                    'name' => 'Product A',
                    'is_marketplace' => true,
                ],
                [
                    'id' => 2,
                    'name' => 'Product B',
                    'is_marketplace' => false,
                ],
                [
                    'id' => 3,
                    'name' => 'Product C',
                    'is_marketplace' => true,
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCustomerShippingAddress')->willReturn(
            [
                'street' => 'XYZ',
                'city' => 'XYZ',
                'regionData' => 1234,
                'zipcode' => 1771,
                'addrClassification' => 'HOME',
                'shipMethod' => 'test',
                'fedExAccountNumber' => 12345678,
                'fedExShippingAccountNumber' => 12345678,
                'productionLocationId' => null
            ]
        );
        $this->quote->expects($this->any())->method('getCustomerPickupLocationData')->willReturn(null);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('getRateRequestShipmentSpecialServices')
            ->willReturn('asxde');
        $this->assertNotNull($this->fxoRequestBuilder->getPickShipData($this->quote, $itemData));
    }

    /**
     * Test case for getPickShipData
     */
    public function testGetPickShipDataWithToggleOn()
    {
        $itemData = [
            'productAssociations' => [
                [
                    'id' => 1,
                    'name' => 'Product A',
                    'is_marketplace' => true,
                ],
                [
                    'id' => 2,
                    'name' => 'Product B',
                    'is_marketplace' => false,
                ],
                [
                    'id' => 3,
                    'name' => 'Product C',
                    'is_marketplace' => true,
                ]
            ]
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quote->expects($this->any())->method('getCustomerShippingAddress')->willReturn(
            [
                'street' => 'XYZ',
                'city' => 'XYZ',
                'regionData' => 1234,
                'zipcode' => 1771,
                'addrClassification' => 'HOME',
                'shipMethod' => 'test',
                'fedExAccountNumber' => 12345678,
                'fedExShippingAccountNumber' => 12345678,
                'productionLocationId' => null
            ]
        );
        $this->quote->expects($this->any())->method('getCustomerPickupLocationData')->willReturn(null);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('getRateRequestShipmentSpecialServices')
            ->willReturn('asxde');
        $this->assertNotNull($this->fxoRequestBuilder->getPickShipData($this->quote, $itemData));
    }

    /**
     * Test case for getPickShipDataWithPickupData
     */
    public function testGetPickShipDataWithPickupData()
    {
        $itemData = [
            'productAssociations' => [
                [
                    'id' => 1,
                    'name' => 'Product A',
                    'is_marketplace' => true,
                ],
                [
                    'id' => 2,
                    'name' => 'Product B',
                    'is_marketplace' => false,
                ],
                [
                    'id' => 3,
                    'name' => 'Product C',
                    'is_marketplace' => true,
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCustomerShippingAddress')->willReturn(null);
        $this->quote->expects($this->any())->method('getCustomerPickupLocationData')->willReturn(
            [
                'locationId'         => 12,
                'fedExAccountNumber' => 12345678,
            ]
        );
        $this->quote->expects($this->any())->method('getIsFromShipping')->willReturn(false);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('getRateRequestShipmentSpecialServices')
            ->willReturn('asxde');
        $this->assertNotNull($this->fxoRequestBuilder->getPickShipData($this->quote, $itemData));
    }
    /**
     * Test for getPickShipData with Mixed Mirakl Cart (Mirakl quote but not full Mirakl)
     */
    public function testGetPickShipDataWithMixedMiraklCart()
    {
        $shopData = [
            'shipping_methods' => json_encode([
                [
                    'shipping_method_name' => 'standard',
                    'shipping_method_code' => 'STD'
                ]
            ]),
            'additional_info' => [
                'contact_info' => [
                    'street_1' => '456 Seller St',
                    'city' => 'Nashville',
                    'state' => 'Tennessee',
                    'zip_code' => '37215',
                ],
                'shipping_zones' => ['us']
            ]
        ];

        $shopDataMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\ShopInterface::class)
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $shopDataMock->expects($this->any())
            ->method('getData')
            ->willReturn($shopData);

        $this->shopManagement = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\ShopManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shopManagement->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($shopDataMock);

        $reflectionClass = new \ReflectionClass($this->fxoRequestBuilder);
        $shopManagementProperty = $reflectionClass->getProperty('shopManagement');
        $shopManagementProperty->setAccessible(true);
        $shopManagementProperty->setValue($this->fxoRequestBuilder, $this->shopManagement);

        $region = ['code' => 'TN'];
        $regionItem = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->onlyMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $regionItem->expects($this->any())
            ->method('toArray')
            ->willReturn($region);

        $collection = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->any())
            ->method('addRegionNameFilter')
            ->willReturnSelf();
        $collection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($regionItem);

        $this->collectionFactory = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($collection);

        $collectionFactoryProperty = $reflectionClass->getProperty('collectionFactory');
        $collectionFactoryProperty->setAccessible(true);
        $collectionFactoryProperty->setValue($this->fxoRequestBuilder, $this->collectionFactory);
        $itemData = [
            'productAssociations' => [
                [
                    'id' => 1,
                    'name' => 'Product A',
                    'is_marketplace' => true,
                ],
                [
                    'id' => 2,
                    'name' => 'Product B',
                    'is_marketplace' => false,
                ]
            ]
        ];

        $this->quote->expects($this->any())
            ->method('getCustomerShippingAddress')
            ->willReturn([
                'street' => ['123 Main St'],
                'city' => 'Memphis',
                'regionData' => 'TN',
                'zipcode' => '38120',
                'addrClassification' => 'HOME',
                'shipMethod' => 'GROUND',
                'fedExAccountNumber' => '123456789',
                'fedExShippingAccountNumber' => '123456789'
            ]);

        $this->quote->expects($this->any())
            ->method('getCustomerPickupLocationData')
            ->willReturn(null);

        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(false);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);
        $this->quoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->willReturn(false);

        $marketPlaceShippingData = [
            'method_code' => 'standard',
            'method_title' => 'Standard Shipping', 
            'amount' => 5.99,
            'deliveryDate' => '2025-05-20',
            'reference_id' => '5678',
            'item_id' => 42
        ];

        $item1 = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklOfferId', 'getAdditionalData', 'getData', 'getProduct'])
            ->getMock();
        $item1->expects($this->any())
            ->method('getMiraklOfferId')
            ->willReturn('mirakl-offer-123');
        $item1->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode([
                'mirakl_shipping_data' => $marketPlaceShippingData
            ]));
        $item1->expects($this->any())
            ->method('getData')
            ->with('mirakl_shop_id')
            ->willReturn('123');

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item1->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);

        $this->quote->expects($this->any())
            ->method('getItemById')
            ->willReturn($item1);
        $item2 = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMock();
        $item2->expects($this->any())
            ->method('getMiraklOfferId')
            ->willReturn(null);

        $this->quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item1, $item2]);

        $this->deliveryHelper->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')
            ->willReturn('SIGNATURE_OPTION');

        $result = $this->fxoRequestBuilder->getPickShipData($this->quote, $itemData);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('arrRecipients', $result);
        $this->assertArrayHasKey('fedExAccountNumber', $result);
        $this->assertNotNull($result['arrRecipients']);
    }
    /**
     * Test for getPickShipData with Full Mirakl Cart
     */
    public function testGetPickShipDataWithFullMiraklCart()
    {
        $shopData = [
            'shipping_methods' => json_encode([
                [
                    'shipping_method_name' => 'standard',
                    'shipping_method_code' => 'STD'
                ],
                [
                    'shipping_method_name' => 'express', 
                    'shipping_method_code' => 'EXP'
                ]
            ]),
            'additional_info' => [
                'contact_info' => [
                    'street_1' => '456 Seller St',
                    'city' => 'Nashville',
                    'state' => 'Tennessee',
                    'zip_code' => '37215',
                ],
                'shipping_zones' => ['us']
            ]
        ];

        $shopDataMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\ShopInterface::class)
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $shopDataMock->expects($this->any())
            ->method('getData')
            ->willReturn($shopData);

        $this->shopManagement = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\ShopManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shopManagement->expects($this->any())
            ->method('getShopByProduct')
            ->willReturn($shopDataMock);

        $reflectionClass = new \ReflectionClass($this->fxoRequestBuilder);
        $shopManagementProperty = $reflectionClass->getProperty('shopManagement');
        $shopManagementProperty->setAccessible(true);
        $shopManagementProperty->setValue($this->fxoRequestBuilder, $this->shopManagement);

        $region = ['code' => 'TN'];
        $regionItem = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->onlyMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $regionItem->expects($this->any())
            ->method('toArray')
            ->willReturn($region);

        $collection = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->any())
            ->method('addRegionNameFilter')
            ->willReturnSelf();
        $collection->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($regionItem);

        $this->collectionFactory = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($collection);

        $collectionFactoryProperty = $reflectionClass->getProperty('collectionFactory');
        $collectionFactoryProperty->setAccessible(true);
        $collectionFactoryProperty->setValue($this->fxoRequestBuilder, $this->collectionFactory);
        $itemData = [
            'productAssociations' => [
                [
                    'id' => 1,
                    'name' => 'Product A',
                    'is_marketplace' => true,
                ]
            ]
        ];

        $this->quote->expects($this->any())
            ->method('getCustomerShippingAddress')
            ->willReturn([
                'street' => ['123 Main St'],
                'city' => 'Memphis',
                'regionData' => 'TN',
                'zipcode' => '38120',
                'addrClassification' => 'HOME',
                'shipMethod' => 'GROUND',
                'fedExAccountNumber' => '123456789',
                'fedExShippingAccountNumber' => '123456789'
            ]);

        $this->quote->expects($this->any())
            ->method('getCustomerPickupLocationData')
            ->willReturn(null);
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(false);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);
        $this->quoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->willReturn(true);

        $marketPlaceShippingData = [
            'method_code' => 'express',
            'method_title' => 'Express Shipping', 
            'amount' => 9.99,
            'deliveryDate' => '2025-05-18',
            'reference_id' => '9876',
            'item_id' => 43
        ];

        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklOfferId', 'getAdditionalData', 'getData', 'getProduct'])
            ->getMock();
        $item->expects($this->any())
            ->method('getMiraklOfferId')
            ->willReturn('mirakl-offer-123');
        $item->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn(json_encode([
                'mirakl_shipping_data' => $marketPlaceShippingData
            ]));
        $item->expects($this->any())
            ->method('getData')
            ->with('mirakl_shop_id')
            ->willReturn('456');

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);

        $this->quote->expects($this->any())
            ->method('getItemById')
            ->willReturn($item);

        $this->quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item]);

        $result = $this->fxoRequestBuilder->getPickShipData($this->quote, $itemData);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('arrRecipients', $result);
        $this->assertArrayHasKey('fedExAccountNumber', $result);
    }
    /**
     * Test for getPickShipData with no Mirakl shipping data
     */
    public function testGetPickShipDataWithNoMiraklShippingData()
    {
        $itemData = [
            'productAssociations' => [
                [
                    'id' => 1,
                    'name' => 'Product A',
                    'is_marketplace' => true,
                ]
            ]
        ];
        $this->quote->expects($this->any())
            ->method('getCustomerShippingAddress')
            ->willReturn([
                'street' => ['123 Main St'],
                'city' => 'Memphis',
                'regionData' => 'TN',
                'zipcode' => '38120',
                'addrClassification' => 'HOME',
                'shipMethod' => 'GROUND',
                'fedExAccountNumber' => '123456789',
                'fedExShippingAccountNumber' => '123456789'
            ]);
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);
        $this->quoteHelper->expects($this->any())
            ->method('isFullMiraklQuote')
            ->willReturn(false);
        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->getMock();
        $item->expects($this->any())
            ->method('getMiraklOfferId')
            ->willReturn('mirakl-offer-123');
        $item->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn('{}');

        $this->quote->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item]);

        $result = $this->fxoRequestBuilder->getPickShipData($this->quote, $itemData);

        $this->assertNotNull($result);
    }
    /**
     * Test case for getPickShipDataWithPickupDataAndToggleOff
     */
    public function testGetShipmentId()
    {
        $this->quote->expects($this->any())->method('getData')->willReturn(4094);
        $this->assertNotNull($this->fxoRequestBuilder->getShipmentId($this->quote));
    }
    /**
     * Test Method for getShipmentId with toggle off
     */
    public function testGetShipmentIdWithoutToggle()
    {
        $this->quote->expects($this->any())->method('getData')->willReturn(false);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNotNull($this->fxoRequestBuilder->getShipmentId($this->quote));
    }
    /**
     * Test Method for getOrder Notes
     */
    public function testGetOrderNotes()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertNull($this->fxoRequestBuilder->getOrderNotes());
    }
    /**
     * Test Method for getOrder Notes with toggle off
     */
    public function testGetOrderNotesToggleOff()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertNull($this->fxoRequestBuilder->getOrderNotes());
    }
    /**
     * Test for the private setPickup method
     *
     * @dataProvider setPickupDataProvider
     */
    public function testSetPickup(
        $isFromPickup,
        $pickupData,
        $isMixedCart,
        $isShippingDataAvailable,
        $shippingAddress,
        $referenceId,
        $itemData,
        $expectedResult,
        $isMiraklQuote
    ) {
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn($isFromPickup);
        $this->quote->expects($this->any())
            ->method('getData')
            ->with('requestedPickupDateTime')
            ->willReturn('2025-05-15T10:00:00');

        $quoteHelper = $this->getMockBuilder(QuoteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn($isMiraklQuote);

        $reflectionClass = new \ReflectionClass($this->fxoRequestBuilder);
        $method = $reflectionClass->getMethod('setPickup');
        $method->setAccessible(true);

        $quoteHelperProperty = $reflectionClass->getProperty('quoteHelper');
        $quoteHelperProperty->setAccessible(true);
        $quoteHelperProperty->setValue($this->fxoRequestBuilder, $quoteHelper);

        $result = $method->invokeArgs(
            $this->fxoRequestBuilder,
            [
                $this->quote,
                $pickupData,
                $isMixedCart,
                $isShippingDataAvailable,
                $shippingAddress,
                $referenceId,
                $itemData
            ]
        );

        $this->assertEquals($expectedResult, $result);
    }
    /**
     * Data provider for testSetPickup
     */
    public function setPickupDataProvider()
    {
        $referenceId = '1234';
        $locationId = '7890';
        $fedExAccountNumber = '123456789';
        $productAssociations = [['id' => 1, 'name' => 'Product A', 'is_marketplace' => false]];

        return [
            'valid_pickup_not_mirakl' => [
                'isFromPickup' => true,
                'pickupData' => ['locationId' => $locationId, 'fedExAccountNumber' => $fedExAccountNumber],
                'isMixedCart' => false,
                'isShippingDataAvailable' => false,
                'shippingAddress' => ['street' => '123 Main St'],
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => [$productAssociations]],
                'expectedResult' => [
                    'arrRecipients' => [
                        0 => [
                            'reference' => $referenceId,
                            'contact' => null,
                            'pickUpDelivery' => [
                                'location' => [
                                    'id' => $locationId,
                                ],
                                'requestedPickupLocalTime' => '2025-05-15T10:00:00',
                            ],
                            'productAssociations' => $productAssociations
                        ],
                    ],
                    'fedExAccountNumber' => $fedExAccountNumber
                ],
                'isMiraklQuote' => false
            ],
            'valid_pickup_mixed_cart' => [
                'isFromPickup' => true,
                'pickupData' => ['locationId' => $locationId, 'fedExAccountNumber' => $fedExAccountNumber],
                'isMixedCart' => true,
                'isShippingDataAvailable' => true,
                'shippingAddress' => ['street' => '123 Main St'],
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => [$productAssociations]],
                'expectedResult' => [
                    'arrRecipients' => [
                        0 => [
                            'reference' => $referenceId,
                            'contact' => null,
                            'pickUpDelivery' => [
                                'location' => [
                                    'id' => $locationId,
                                ],
                                'requestedPickupLocalTime' => '2025-05-15T10:00:00',
                            ],
                            'productAssociations' => $productAssociations
                        ],
                    ],
                    'fedExAccountNumber' => $fedExAccountNumber
                ],
                'isMiraklQuote' => true
            ],
            'pickup_false' => [
                'isFromPickup' => false,
                'pickupData' => ['locationId' => $locationId],
                'isMixedCart' => false,
                'isShippingDataAvailable' => false,
                'shippingAddress' => null,
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => [$productAssociations]],
                'expectedResult' => [],
                'isMiraklQuote' => false
            ],
            'pickup_null_location_id' => [
                'isFromPickup' => true,
                'pickupData' => ['locationId' => 'null'],
                'isMixedCart' => false,
                'isShippingDataAvailable' => false,
                'shippingAddress' => null,
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => [$productAssociations]],
                'expectedResult' => [],
                'isMiraklQuote' => false
            ],
            'mirakl_quote_without_shipping_data' => [
                'isFromPickup' => true,
                'pickupData' => ['locationId' => $locationId],
                'isMixedCart' => true,
                'isShippingDataAvailable' => false,
                'shippingAddress' => ['street' => '123 Main St'],
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => [$productAssociations]],
                'expectedResult' => [],
                'isMiraklQuote' => true
            ]
        ];
    }
    public function testGetPickShipData_PickupWithRequestedPickupDateTime()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods([
                'getIsFromPickup',
                'getCustomerPickupLocationData',
                'getIsFromShipping',
                'getCustomerShippingAddress',
            ])
            ->getMock();

        $quote->method('getIsFromPickup')->willReturn(true);
        $quote->method('getCustomerPickupLocationData')
            ->willReturn(['locationId' => 'LOC1', 'fedExAccountNumber' => 'ACC1']);
        $quote->method('getIsFromShipping')->willReturn(false);
        $quote->method('getCustomerShippingAddress')->willReturn(null);

        $quote->method('getData')
            ->willReturnCallback(function ($key) {
                if ($key === 'requestedPickupDateTime') {
                    return '2025-06-01T12:34:56';
                }
                return null;
            });

        $itemData = ['productAssociations' => [
            ['id' => 1, 'is_marketplace' => false]
        ]];

        $result = $this->fxoRequestBuilder->getPickShipData($quote, $itemData);

        $this->assertSame(
            '2025-06-01T12:34:56',
            $result['arrRecipients'][0]['pickUpDelivery']['requestedPickupLocalTime']
        );
    }

    public function testGetPickShipData_PickupWithEmptyRequestedPickupDateTime()
    {
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods([
                'getIsFromPickup',
                'getCustomerPickupLocationData',
                'getIsFromShipping',
                'getCustomerShippingAddress',
            ])
            ->getMock();

        $quote->method('getIsFromPickup')->willReturn(true);
        $quote->method('getCustomerPickupLocationData')
            ->willReturn(['locationId' => 'LOC2', 'fedExAccountNumber' => 'ACC2']);
        $quote->method('getIsFromShipping')->willReturn(false);
        $quote->method('getCustomerShippingAddress')->willReturn(null);

        $quote->method('getData')
            ->willReturnCallback(function ($key) {
                if ($key === 'requestedPickupDateTime') {
                    return '';  // empty => !empty(...) is false => null branch
                }
                return null;
            });

        $itemData = ['productAssociations' => [
            ['id' => 2, 'is_marketplace' => false]
        ]];

        $result = $this->fxoRequestBuilder->getPickShipData($quote, $itemData);

        $this->assertNull(
            $result['arrRecipients'][0]['pickUpDelivery']['requestedPickupLocalTime']
        );
    }

    /**
     * Test for the private setShippingOption method
     * 
     * @dataProvider setShippingOptionDataProvider
     */
    public function testSetShippingOption(
        bool   $isFromShipping,
        array  $shippingAddress,
        bool   $isMixedCart,
        bool   $isShippingDataAvailable,
        ?array $pickupLocationData,
        string $referenceId,
        array  $itemData,
        array  $shippingData,
        bool   $isMiraklQuote,
        bool   $isFullMiraklQuote,
        ?string $specialServices,
        $quoteItem,
        array  $shopData,
        array  $region,
        array  $expectedResult
    ) {
        $this->quote
            ->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn($isFromShipping);

        $mockQuoteHelper = $this->getMockBuilder(\Fedex\MarketplaceProduct\Helper\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockQuoteHelper->method('isMiraklQuote')->willReturn($isMiraklQuote);
        $mockQuoteHelper->method('isFullMiraklQuote')->willReturn($isFullMiraklQuote);

        $rp = new \ReflectionProperty($this->fxoRequestBuilder, 'quoteHelper');
        $rp->setAccessible(true);
        $rp->setValue($this->fxoRequestBuilder, $mockQuoteHelper);

        if ($isFromShipping) {
            $this->checkoutSession
                ->expects($this->once())
                ->method('setServiceType')
                ->with($shippingAddress['shipMethod']);
        }

        $this->deliveryHelper
            ->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')
            ->willReturn($specialServices);

        if ($quoteItem === false) {
            $this->quote
                ->expects($this->any())
                ->method('getItemById')
                ->willReturn(false);
        } else if ($quoteItem !== null) {
            $this->quote
                ->expects($this->any())
                ->method('getItemById')
                ->with((int)$shippingData[array_key_first($shippingData)]['item_id'])
                ->willReturn($quoteItem);
        }
        if ($quoteItem) {
            $shopDataMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\ShopInterface::class)
                ->addMethods(['getData'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
            $shopDataMock->method('getData')->willReturn($shopData);

            $shopManagementMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\ShopManagementInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
            $shopManagementMock->method('getShopByProduct')->willReturn($shopDataMock);

            $rpShop = new \ReflectionProperty($this->fxoRequestBuilder, 'shopManagement');
            $rpShop->setAccessible(true);
            $rpShop->setValue($this->fxoRequestBuilder, $shopManagementMock);

            $regionItem = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['toArray'])
                ->getMock();
            $regionItem->method('toArray')->willReturn($region);

            $collection = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
            $collection->method('addRegionNameFilter')->willReturnSelf();
            $collection->method('getFirstItem')->willReturn($regionItem);

            $factory = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class)
                ->disableOriginalConstructor()
                ->getMock();
            $factory->method('create')->willReturn($collection);

            $rpColl = new \ReflectionProperty($this->fxoRequestBuilder, 'collectionFactory');
            $rpColl->setAccessible(true);
            $rpColl->setValue($this->fxoRequestBuilder, $factory);
        }

        $m = new \ReflectionMethod($this->fxoRequestBuilder, 'setShippingOption');
        $m->setAccessible(true);
        $actual = $m->invokeArgs(
            $this->fxoRequestBuilder,
            [
                $this->quote,
                $shippingAddress,
                $isMixedCart,
                $isShippingDataAvailable,
                $referenceId,
                $itemData,
                $pickupLocationData,
                $shippingData
            ]
        );

        $this->assertEquals($expectedResult, $actual);
    }

    /**
     * Data provider for testSetShippingOption
     */
    public function setShippingOptionDataProvider()
    {
        $referenceId = '1234';
        $shippingAddress = [
            'street' => ['123 Main St'],
            'city' => 'Memphis',
            'regionData' => 'TN',
            'zipcode' => '38120',
            'addrClassification' => 'HOME',
            'shipMethod' => 'GROUND',
            'fedExAccountNumber' => '123456789',
            'fedExShippingAccountNumber' => '123456789',
        ];

        $productAssociations = [
            [
                ['id' => 1, 'name' => 'Product A', 'is_marketplace' => false],
            ],
            [
                ['id' => 2, 'name' => 'Product B', 'is_marketplace' => true],
            ]
        ];

        $shopDataArray = [
            'shipping_methods' => json_encode([
                [
                    'shipping_method_name' => 'standard',
                    'shipping_method_code' => 'STD'
                ]
            ]),
            'additional_info' => [
                'contact_info' => [
                    'street_1' => '456 Seller St',
                    'city' => 'Nashville',
                    'state' => 'Tennessee',
                    'zip_code' => '37215',
                ],
                'shipping_zones' => ['us']
            ]
        ];

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();

        $quoteItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);

        $region = ['code' => 'TN'];

        return [
            'regular_shipping_not_mirakl' => [
                'isFromShipping' => true,
                'shippingAddress' => $shippingAddress,
                'isMixedCart' => false,
                'isShippingDataAvailable' => false,
                'pickupLocationData' => null,
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => $productAssociations],
                'shippingData' => [],
                'isMiraklQuote' => false,
                'isFullMiraklQuote' => false,
                'specialServices' => 'SIGNATURE_OPTION',
                'quoteItem' => null,
                'shopData' => [],
                'region' => [],
                'expectedResult' => [
                    'arrRecipients' => [
                        0 => [
                            'contact' => null,
                            'reference' => $referenceId,
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => ['123 Main St'],
                                    'city' => 'Memphis',
                                    'stateOrProvinceCode' => 'TN',
                                    'postalCode' => '38120',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'HOME',
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => 'GROUND',
                                'productionLocationId' => null,
                                'fedExAccountNumber' => '123456789',
                                'deliveryInstructions' => null,
                                'specialServices' => 'SIGNATURE_OPTION'
                            ],
                            'productAssociations' => [
                                ['id' => 1, 'name' => 'Product A', 'is_marketplace' => false]
                            ]
                        ],
                    ],
                    'fedExAccountNumber' => '123456789'
                ]
            ],
            'mixed_cart_shipping_with_mirakl' => [
                'isFromShipping' => true,
                'shippingAddress' => $shippingAddress,
                'isMixedCart' => true,
                'isShippingDataAvailable' => true,
                'pickupLocationData' => null,
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => $productAssociations],
                'shippingData' => [
                    1 => [
                        'reference_id' => '5678',
                        'deliveryDate' => '2025-05-20',
                        'method_code' => 'standard',
                        'method_title' => 'Standard Shipping',
                        'amount' => 5.99,
                        'item_id' => 42
                    ]
                ],
                'isMiraklQuote' => true,
                'isFullMiraklQuote' => false,
                'specialServices' => null,
                'quoteItem' => $quoteItem,
                'shopData' => $shopDataArray,
                'region' => $region,
                'expectedResult' => [
                    'arrRecipients' => [
                        0 => [
                            'contact' => null,
                            'reference' => $referenceId,
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => ['123 Main St'],
                                    'city' => 'Memphis',
                                    'stateOrProvinceCode' => 'TN',
                                    'postalCode' => '38120',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'HOME',
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => 'GROUND',
                                'productionLocationId' => null,
                                'fedExAccountNumber' => '123456789',
                                'deliveryInstructions' => null,
                            ],
                            'productAssociations' => [
                                ['id' => 1, 'name' => 'Product A', 'is_marketplace' => false]
                            ]
                        ],
                        [
                            'contact' => null,
                            'reference' => '5678',
                            'externalDelivery' => [
                                'address' => [
                                    'streetLines' => ['123 Main St'],
                                    'city' => 'Memphis',
                                    'stateOrProvinceCode' => 'TN',
                                    'postalCode' => '38120',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'HOME',
                                ],
                                'originAddress' => [
                                    'streetLines' => ['456 Seller St'],
                                    'city' => 'Nashville',
                                    'stateOrProvinceCode' => 'TN',
                                    'postalCode' => '37215',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'HOME'
                                ],
                                'estimatedShipDates' => [
                                    'minimumEstimatedShipDate' => '2025-05-20',
                                    'maximumEstimatedShipDate' => '2025-05-20'
                                ],
                                'skus' => [
                                    [
                                        'skuDescription' => 'Standard Shipping',
                                        'skuRef' => 'STD',
                                        'code' => 'STD',
                                        'unitPrice' => 5.99,
                                        'price' => 5.99,
                                        'qty' => '1'
                                    ]
                                ]
                            ],
                            'productAssociations' => [
                                ['id' => 2, 'name' => 'Product B', 'is_marketplace' => true]
                            ]
                        ]
                    ],
                    'fedExAccountNumber' => '123456789'
                ]
            ],
            'marketplace_shipping_without_methods' => [
                'isFromShipping' => true,
                'shippingAddress' => $shippingAddress,
                'isMixedCart' => true,
                'isShippingDataAvailable' => true,
                'pickupLocationData' => null,
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => $productAssociations],
                'shippingData' => [
                    1 => [
                        'reference_id' => '5678',
                        'deliveryDate' => '2025-05-20',
                        'method_code' => 'standard',
                        'method_title' => 'Standard Shipping',
                        'amount' => 5.99,
                        'item_id' => 42
                    ]
                ],
                'isMiraklQuote' => true,
                'isFullMiraklQuote' => false,
                'specialServices' => null,
                'quoteItem' => $quoteItem,
                'shopData' => [
                    'shipping_methods' => '',
                    'additional_info' => [
                        'contact_info' => [
                            'street_1' => '456 Seller St',
                            'city' => 'Nashville',
                            'state' => 'Tennessee',
                            'zip_code' => '37215',
                        ],
                        'shipping_zones' => ['us']
                    ]
                ],
                'region' => $region,
                'expectedResult' => [
                    'arrRecipients' => [
                        0 => [
                            'contact' => null,
                            'reference' => $referenceId,
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => ['123 Main St'],
                                    'city' => 'Memphis',
                                    'stateOrProvinceCode' => 'TN',
                                    'postalCode' => '38120',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'HOME',
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => 'GROUND',
                                'productionLocationId' => null,
                                'fedExAccountNumber' => '123456789',
                                'deliveryInstructions' => null,
                            ],
                            'productAssociations' => [
                                ['id' => 1, 'name' => 'Product A', 'is_marketplace' => false]
                            ]
                        ],
                        [
                            'contact' => null,
                            'reference' => '5678',
                            'externalDelivery' => [
                                'address' => [
                                    'streetLines' => ['123 Main St'],
                                    'city' => 'Memphis',
                                    'stateOrProvinceCode' => 'TN',
                                    'postalCode' => '38120',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'HOME',
                                ],
                                'originAddress' => [
                                    'streetLines' => ['456 Seller St'],
                                    'city' => 'Nashville',
                                    'stateOrProvinceCode' => 'TN',
                                    'postalCode' => '37215',
                                    'countryCode' => 'US',
                                    'addressClassification' => 'HOME'
                                ],
                                'estimatedShipDates' => [
                                    'minimumEstimatedShipDate' => '2025-05-20',
                                    'maximumEstimatedShipDate' => '2025-05-20'
                                ],
                                'skus' => [
                                    [
                                        'skuDescription' => 'Standard Shipping',
                                        'skuRef' => 'standard',
                                        'code' => 'standard',
                                        'unitPrice' => 5.99,
                                        'price' => 5.99,
                                        'qty' => '1'
                                    ]
                                ]
                            ],
                            'productAssociations' => [
                                ['id' => 2, 'name' => 'Product B', 'is_marketplace' => true]
                            ]
                        ]
                    ],
                    'fedExAccountNumber' => '123456789'
                ]
            ],
            'mirakl_quote_with_boolean_quote_item' => [
                'isFromShipping' => true,
                'shippingAddress' => $shippingAddress,
                'isMixedCart' => true,
                'isShippingDataAvailable' => true,
                'pickupLocationData' => null,
                'referenceId' => $referenceId,
                'itemData' => ['productAssociations' => $productAssociations],
                'shippingData' => [
                    1 => [
                        'reference_id' => '5678',
                        'deliveryDate' => '2025-05-20',
                        'method_code' => 'standard',
                        'method_title' => 'Standard Shipping',
                        'amount' => 5.99,
                        'item_id' => 42
                    ]
                ],
                'isMiraklQuote' => true,
                'isFullMiraklQuote' => false,
                'specialServices' => null,
                'quoteItem' => false,
                'shopData' => $shopDataArray,
                'region' => $region,
                'expectedResult' => [
                    'arrRecipients' => null,
                    'fedExAccountNumber' => '123456789'
                ]
            ],
        ];
    }
}
