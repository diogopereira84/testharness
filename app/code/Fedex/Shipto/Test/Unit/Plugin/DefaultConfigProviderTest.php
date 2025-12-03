<?php
/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Test\Unit\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\ResourceModel\Quote\Item\Option\Collection;
use Magento\Quote\Model\ResourceModel\Quote\Item\Option\CollectionFactory;
use Magento\Checkout\Model\DefaultConfigProvider as Subject;
use Fedex\Shipto\Plugin\DefaultConfigProvider;
use Magento\Framework\DataObject;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\Shipto\Model\ProductionLocation;
use Fedex\Shipto\Model\ResourceModel\ProductionLocation\Collection as ProCollection;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Catalog\Helper\Image;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class DefaultConfigProviderTest extends TestCase
{
    protected $checkoutSession;
    /**
     * @var (\Magento\Catalog\Helper\Image & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $imageHelper;
    protected $deliveryHelper;
    protected $productModel;
    protected $quote;
    protected $quoteItem;
    /**
     * @var (\Magento\Customer\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerSession;
    protected $companyRepository;
    protected $companyInterface;
    protected $timezone;
    protected $date;
    protected $productionLocationFactory;
    protected $proCollection;
    protected $productionLocation;
    protected $itemOption;
    protected $itemOptionCollection;
    protected $toggleConfig;
    protected $subject;
    protected $selfRegHelper;
    /**
     * @var (\Magento\Quote\Api\CartRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteRepository;
    protected $adminConfigHelper;
    protected $inBranchValidation;
    protected $defaultConfigProvider;
    private $productRepositoryMock;
    private $marketplaceCheckoutHelper;

    private $productCollectionFactoryMock;

    private $productCollectionMock;

    const PRODUCT_DATA = '{
        "external_prod":[{
            "priceable":false,
            "preview_url":"https:\/\/example.com","fxo_product":true
        }]
    }';
    protected function setUp(): void
    {
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
        ->disableOriginalConstructor()
        ->setMethods(['getQuote'])
        ->getMock();
        $this->imageHelper = $this->getMockBuilder(Image::class)
        ->disableOriginalConstructor()
        ->setMethods(['init','setImageFile','getUrl'])
        ->getMock();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['getProductAttributeName','getProductCustomAttributeValue'])
        ->getMock();
        $this->productModel = $this->getMockBuilder(Product::class)
        ->disableOriginalConstructor()
        ->setMethods(['load','getAttributeSetId'])
        ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
        ->disableOriginalConstructor()
        ->setMethods(['getAllVisibleItems'])
        ->getMock();
        $this->quoteItem = $this->getMockBuilder(Item::class)
        ->disableOriginalConstructor()
        ->setMethods(['getItemId','getProductId','getOptionByCode'])
        ->getMock();
        $this->customerSession = $this->getMockBuilder(Session::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCustomerCompany','isLoggedIn'])
        ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
        ->disableOriginalConstructor()
        ->setMethods(['get'])
        ->getMockForAbstractClass();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
        ->disableOriginalConstructor()
        ->setMethods([
            'getRecipientAddressFromPo',
            'getAllowProductionLocation',
            'getProductionLocationOption',
            'getStorefrontLoginMethodOption'
        ])
        ->getMockForAbstractClass();

        $this->timezone = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $this->date = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['gmtDate'])
            ->getMock();

        $this->productionLocationFactory = $this->getMockBuilder(ProductionLocationFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->proCollection = $this->getMockBuilder(ProCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getSize'])
            ->getMock();


        $this->productionLocation = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getData','addFieldToFilter'])
            ->getMock();

        $this->itemOption = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->itemOptionCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
        ->disableOriginalConstructor()
        ->setMethods(['getToggleConfigValue'])
        ->getMock();

        $this->subject = $this->getMockBuilder(Subject::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->selfRegHelper = $this->getMockBuilder(SelfReg::class)
        ->disableOriginalConstructor()
        ->setMethods(['isSelfRegCustomer'])
        ->getMock();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isUploadToQuoteToggle', 'checkoutQuotePriceisDashable', 'checkoutQuoteItemPriceableValue'])
            ->getMock();

        $this->inBranchValidation = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->setMethods(['isInBranchUser','getAllowedInBranchLocation'])
            ->getMock();
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $this->productCollectionMock = $this->createMock(ProductCollection::class);
        $this->productCollectionFactoryMock->method('create')->willReturn($this->productCollectionMock);
        $objectManagerHelper = new ObjectManager($this);

        $this->defaultConfigProvider = $objectManagerHelper->getObject(
            DefaultConfigProvider::class,
            [
                'customerSession' => $this->customerSession,
                'companyRepository' => $this->companyRepository,
                'itemOption' => $this->itemOption,
                'timezone' => $this->timezone,
                'date' => $this->date,
                'toggleConfig' => $this->toggleConfig,
                'productionLocationFactory' => $this->productionLocationFactory,
                'selfRegHelper' => $this->selfRegHelper,
                'checkoutSession' => $this->checkoutSession,
                'imageHelper' => $this->imageHelper,
                'deliveryHelper' => $this->deliveryHelper,
                'productModel' => $this->productModel,
                'quoteRepository'=> $this->quoteRepository,
                'adminConfigHelper' => $this->adminConfigHelper,
                'inBranchValidation' =>$this->inBranchValidation,
                'productRepository'=> $this->productRepositoryMock,
                'marketplaceCheckoutHelper'=> $this->marketplaceCheckoutHelper,
                'productCollectionFactory' => $this->productCollectionFactoryMock
            ]
        );
    }

    public function testAfterGetConfig()
    {
        $result['is_covidPeakSeason'] = true;
        $result['is_commercial'] = true;
        $result['customerData']['addresses'] = ['Test'];
        $result['totalsData']['items'] = [['item_id' => 11573, 'name' => 'Flyers']];
        $result['quoteData']['price_dash'] = 0;


        $locationData = ['id'=>1,'location_id'=>'023','location_name'=>'test'];

        $expectedResult['product_image_data'] = [];
        $expectedResult['is_covidPeakSeason'] = true;
        $expectedResult['is_commercial'] = true;
        $expectedResult['customerData']['addresses'] = [];
        $expectedResult['is_production_location'] = true;
        $expectedResult['totalsData']['items'] = [['item_id' => 11573, 'name' => 'Flyers']];
        $expectedResult['restricted_production_location'] = json_encode([$locationData]);
        $expectedResult['hco_price_update'] = true;
        $expectedResult['quoteData']['price_dash'] = 0;

        $itemsData = [
            new DataObject([
                'code' => 'info_buyRequest',
                'value' => '{"external_prod": [{"userProductName":"Flyers"}]}'
            ])
        ];
        $this->checkoutSession->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->quoteItem]);
        $this->quoteItem->expects($this->any())->method('getItemId')->willReturn(23);
        $this->quoteItem->expects($this->any())->method('getProductId')->willReturn(2);
        $productId = 2;
        $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')
            ->with($productId)->willReturn($this->productModel);
        $this->productModel->expects($this->any())->method('getAttributeSetId')->willReturn(12);
        $this->deliveryHelper->expects($this->any())->method('getProductAttributeName')->willReturn("PrintOnDemand");
        $this->deliveryHelper->expects($this->any())->method('getProductCustomAttributeValue')->willReturn(true);
        $this->selfRegHelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->date->expects($this->any())->method('gmtDate')->willReturn('2021-12-02', '2022-01-31');
        $this->timezone->expects($this->any())->method('date')->willReturnSelf();
        $this->timezone->expects($this->any())->method('format')
         ->willReturn('2021-12-02 12:12:12', '2022-01-31 00:00:00');
        $this->companyInterface->expects($this->any())->method('getAllowProductionLocation')->willReturnSelf();
        $this->toggleConfig->method('getToggleConfigValue')->willReturnMap([
            ['explorers_e_450676_personal_address_book', true],
            ['hawks_d_227849_performance_improvement_checkout_product_load', false]
        ]);

        $this->productionLocationFactory->expects($this->any())->method('create')
            ->willReturnSelf();

        $this->productionLocation->expects($this->any())->method('getCollection')
            ->willReturn($this->proCollection);

        $this->proCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturn([$this->productionLocation]);

        $this->productionLocation->expects($this->any())->method('getData')
            ->willReturn($locationData);

        $this->adminConfigHelper->expects($this->any())->method('isUploadToQuoteToggle')->willReturn(false);

        $this->itemOption->expects($this->any())->method('create')->willReturn($this->itemOptionCollection);
        $this->itemOptionCollection->expects($this->any())->method('addFieldToFilter')
        ->with('item_id', 11573)->willReturnSelf();
        $this->itemOptionCollection->expects($this->any())->method('load')->willReturn($itemsData);
        $this->defaultConfigProvider->afterGetConfig($this->subject, $result);
    }
    // Test method addProductionLocationInfo
    public function testAddProductionLocationInfoWithToogleOn()
    {
        $result = [
            'restricted_production_location' => false,
            'is_production_location' => false,
            'is_restricted_store_production_location_option' => true,
            'recommended_production_location'=>true,
            'has_selected_prod_loc' => false
        ];
        $companyId = 1;

        // Mock quote with production location ID
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getProductionLocationId'])
            ->getMock();
        
        $quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        
        $quoteMock->expects($this->any())
            ->method('getProductionLocationId')
            ->willReturn(456);
        
        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->toggleConfig->method('getToggleConfigValue')->willReturnMap([
            ['explorers_e_450676_personal_address_book', true],
            ['hawks_d_227849_performance_improvement_checkout_product_load', false],
            ['explorers_restricted_and_recommended_production', true],
            ['explorers_site_level_quoting_stores', false],
            ['mazegeeks_e_482379_allow_customer_to_choose_production_location_updates', true]
        ]);

        $this->productionLocation->method('getCollection')->willReturnSelf();
        $this->productionLocation->method('addFieldToFilter')->willReturnSelf();
        $this->productionLocation->method('getData')->willReturn(false);
        $this->productionLocationFactory->method('create')->willReturn($this->productionLocation);

        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');
        $this->inBranchValidation->expects($this->any())->method('isInBranchUser')->willReturn(true);
        $this->inBranchValidation->expects($this->any())->method('getAllowedInBranchLocation')->willReturn('38017');
        $this->companyInterface->expects($this->any())->method('getRecipientAddressFromPo')->willReturn(1);
        $this->companyInterface->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->companyInterface->expects($this->any())->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');

        $result = $this->defaultConfigProvider->addProductionLocationInfo($result, $this->companyInterface, $companyId);

        $this->assertNotNull($result['is_production_location']);
        $this->assertNotNull($result['restricted_production_location']);
        // Assert that production location selection is detected correctly
        $this->assertEquals(true, $result['has_selected_prod_loc']);
        $this->assertEquals(true, $result['is_simplified_production_location']);
    }
    // Test method addProductionLocationInfo
    public function testAddProductionLocationInfoWithToogleOff()
    {
        $result = [
            'restricted_production_location' => false,
            'is_production_location' => false,
            'is_restricted_store_production_location_option' => false,
            'has_selected_prod_loc' => false
        ];
        $companyId = 1;

        // Mock quote with no production location ID
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getProductionLocationId'])
            ->getMock();
        
        $quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        
        $quoteMock->expects($this->any())
            ->method('getProductionLocationId')
            ->willReturn(null);
        
        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->toggleConfig->method('getToggleConfigValue')->willReturnMap([
            ['explorers_e_450676_personal_address_book', false],
            ['hawks_d_227849_performance_improvement_checkout_product_load', false],
            ['explorers_restricted_and_recommended_production', true],
            ['explorers_site_level_quoting_stores', false],
            ['mazegeeks_e_482379_allow_customer_to_choose_production_location_updates', true]
        ]);

        $this->productionLocation->method('getCollection')->willReturnSelf();
        $this->productionLocation->method('addFieldToFilter')->willReturnSelf();
        $this->productionLocation->method('getData')->willReturn(false);
        $this->productionLocationFactory->method('create')->willReturn($this->productionLocation);

        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getStorefrontLoginMethodOption')
            ->willReturn('commercial_store_epro');
        $this->companyInterface->expects($this->any())->method('getRecipientAddressFromPo')->willReturn(1);
        $this->companyInterface->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->companyInterface->expects($this->any())->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');

        $result = $this->defaultConfigProvider->addProductionLocationInfo($result, $this->companyInterface, $companyId);

        $this->assertNotNull($result['is_production_location']);
        $this->assertNotNull($result['restricted_production_location']);
        // Assert that production location selection is detected correctly
        $this->assertEquals(false, $result['has_selected_prod_loc']);
        $this->assertEquals(true, $result['is_simplified_production_location']);
    }


    public function testPrepareItemsArrayWithInfoBuyRequest()
    {
        $items = [
            ['item_id' => 1, 'name' => 'OldName']
        ];
        $itemOptionData = [
            new \Magento\Framework\DataObject([
                'code' => 'info_buyRequest',
                'value' => '{"external_prod": [{"userProductName":"NewName"}]}'
            ])
        ];
        $this->itemOption->method('create')->willReturn($this->itemOptionCollection);
        $this->itemOptionCollection->method('addFieldToFilter')->willReturnSelf();
        $this->itemOptionCollection->method('load')->willReturn($itemOptionData);

        $result = $this->defaultConfigProvider->prepareItemsArray($items);

        $this->assertEquals('NewName', $result[0]['name']);
    }

    public function testPrepareItemsArrayWithoutInfoBuyRequest()
    {
        $items = [
            ['item_id' => 1, 'name' => 'OldName']
        ];
        $itemOptionData = [
            new \Magento\Framework\DataObject([
                'code' => 'other_code',
                'value' => 'irrelevant'
            ])
        ];
        $this->itemOption->method('create')->willReturn($this->itemOptionCollection);
        $this->itemOptionCollection->method('addFieldToFilter')->willReturnSelf();
        $this->itemOptionCollection->method('load')->willReturn($itemOptionData);

        $result = $this->defaultConfigProvider->prepareItemsArray($items);

        $this->assertEquals('OldName', $result[0]['name']);
    }

    public function testAfterGetConfigWithPerformanceImprovementToggle()
    {
        $result = [
            'totalsData' => [
                'items' => [
                    ['item_id' => 10, 'product_id' => 100, 'name' => 'TestProduct']
                ]
            ],
            'quoteData' => ['price_dash' => 0]
        ];

        $mockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getFile'])
            ->getMock();
        $mockProduct->method('getAttributeSetId')->willReturn(12);
        $mockProduct->method('getFile')->willReturn('test.jpg');

        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllVisibleItems')->willReturn([
            new class {
                public function getItemId()
                {
                    return 10;
                }
                public function getProductId()
                {
                    return 100;
                }
            }
        ]);

        $this->toggleConfig->method('getToggleConfigValue')->willReturnMap([
            ['hawks_d_227849_performance_improvement_checkout_product_load', true],
            ['explorers_e_450676_personal_address_book', false]
        ]);

        $this->productCollectionMock->method('setFlag')->willReturnSelf();
        $this->productCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $this->productCollectionMock->method('addMediaGalleryData')->willReturnSelf();
        $this->productCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollectionMock->method('getItemById')->with(100)->willReturn($mockProduct);

        $this->deliveryHelper->method('getProductAttributeName')->willReturn('PrintOnDemand');
        $this->imageHelper->method('init')->willReturnSelf();
        $this->imageHelper->method('setImageFile')->willReturnSelf();
        $this->imageHelper->method('getUrl')->willReturn('http://image.url/test.jpg');

        $this->selfRegHelper->method('isSelfRegCustomer')->willReturn(false);
        $this->timezone->method('date')->willReturnSelf();
        $this->date->method('gmtDate')->willReturn('2022-01-01');
        $this->timezone->method('format')->willReturn('2022-01-01 00:00:00');

        $this->itemOption->method('create')->willReturn($this->itemOptionCollection);
        $this->itemOptionCollection->method('addFieldToFilter')->willReturnSelf();
        $this->itemOptionCollection->method('load')->willReturn([]);

        $actualResult = $this->defaultConfigProvider->afterGetConfig($this->subject, $result);

        $this->assertArrayHasKey('product_image_data', $actualResult);
        $this->assertArrayHasKey(10, $actualResult['product_image_data']);
        $this->assertEquals('http://image.url/test.jpg', $actualResult['product_image_data'][10]);
    }

    public function testAfterGetConfigWithPerformanceImprovementToggleAndNoMatchingProduct()
    {
        $result = [
            'totalsData' => [
                'items' => [
                    ['item_id' => 11, 'product_id' => 101, 'name' => 'NoImageProduct']
                ]
            ],
            'quoteData' => ['price_dash' => 0]
        ];

        $this->checkoutSession->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getAllVisibleItems')->willReturn([
            new class {
                public function getItemId()
                {
                    return 11;
                }
                public function getProductId()
                {
                    return 101;
                }
            }
        ]);

        $this->toggleConfig->method('getToggleConfigValue')->willReturnMap([
            ['hawks_d_227849_performance_improvement_checkout_product_load', true],
            ['explorers_e_450676_personal_address_book', false]
        ]);

        $this->productCollectionMock->method('setFlag')->willReturnSelf();
        $this->productCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $this->productCollectionMock->method('addMediaGalleryData')->willReturnSelf();
        $this->productCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollectionMock->method('getItemById')->with(101)->willReturn(null);

        $this->deliveryHelper->method('getProductAttributeName')->willReturn('PrintOnDemand');
        $this->selfRegHelper->method('isSelfRegCustomer')->willReturn(false);
        $this->timezone->method('date')->willReturnSelf();
        $this->date->method('gmtDate')->willReturn('2022-01-01');
        $this->timezone->method('format')->willReturn('2022-01-01 00:00:00');

        $this->itemOption->method('create')->willReturn($this->itemOptionCollection);
        $this->itemOptionCollection->method('addFieldToFilter')->willReturnSelf();
        $this->itemOptionCollection->method('load')->willReturn([]);

        $actualResult = $this->defaultConfigProvider->afterGetConfig($this->subject, $result);

        $this->assertArrayHasKey('product_image_data', $actualResult);
        $this->assertEmpty($actualResult['product_image_data']);
    }

    public function testAfterGetConfigWithMixedAttributeSets()
    {
        $result = [
            'totalsData' => [
                'items' => [
                    ['item_id' => 20, 'product_id' => 200, 'name' => 'PrintOnDemandProduct'],
                    ['item_id' => 21, 'product_id' => 201, 'name' => 'OtherProduct']
                ]
            ],
            'quoteData' => ['price_dash' => 0],
            'has_selected_prod_loc' => false
        ];

        $printOnDemandProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getFile'])
            ->getMock();
        $printOnDemandProduct->method('getAttributeSetId')->willReturn(12);
        $printOnDemandProduct->method('getFile')->willReturn('printondemand.jpg');

        $otherProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetId', 'getFile'])
            ->getMock();
        $otherProduct->method('getAttributeSetId')->willReturn(99); // Not PrintOnDemand
        $otherProduct->method('getFile')->willReturn('other.jpg');

        // Mock quote with no ID to test the no-quote scenario for production location detection
        $noIdQuoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllVisibleItems', 'getId', 'getProductionLocationId'])
            ->getMock();
        
        $noIdQuoteMock->method('getAllVisibleItems')->willReturn([
            new class {
                public function getItemId()
                {
                    return 20;
                }
                public function getProductId()
                {
                    return 200;
                }
            },
            new class {
                public function getItemId()
                {
                    return 21;
                }
                public function getProductId()
                {
                    return 201;
                }
            }
        ]);
        
        $noIdQuoteMock->method('getId')->willReturn(null);
        
        $this->checkoutSession->method('getQuote')->willReturn($noIdQuoteMock);

        $this->toggleConfig->method('getToggleConfigValue')->willReturnMap([
            ['hawks_d_227849_performance_improvement_checkout_product_load', true],
            ['explorers_e_450676_personal_address_book', false],
            ['explorers_restricted_and_recommended_production', true],
            ['explorers_site_level_quoting_stores', false],
            ['mazegeeks_e_482379_allow_customer_to_choose_production_location_updates', true]
        ]);

        $this->productCollectionMock->method('setFlag')->willReturnSelf();
        $this->productCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $this->productCollectionMock->method('addMediaGalleryData')->willReturnSelf();
        $this->productCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollectionMock->method('getItemById')->willReturnMap([
            [200, $printOnDemandProduct],
            [201, $otherProduct]
        ]);

        $this->deliveryHelper->method('getProductAttributeName')->willReturnCallback(function ($attributeSetId) {
            return $attributeSetId === 12 ? 'PrintOnDemand' : 'OtherType';
        });

        $this->imageHelper->method('init')->willReturnSelf();
        $this->imageHelper->method('setImageFile')->willReturnSelf();
        $this->imageHelper->method('getUrl')->willReturn('http://image.url/printondemand.jpg');

        $this->selfRegHelper->method('isSelfRegCustomer')->willReturn(false);
        $this->timezone->method('date')->willReturnSelf();
        $this->date->method('gmtDate')->willReturn('2022-01-01');
        $this->timezone->method('format')->willReturn('2022-01-01 00:00:00');

        $this->itemOption->method('create')->willReturn($this->itemOptionCollection);
        $this->itemOptionCollection->method('addFieldToFilter')->willReturnSelf();
        $this->itemOptionCollection->method('load')->willReturn([]);

        $this->companyInterface->method('getAllowProductionLocation')->willReturn(1);
        $this->companyInterface->method('getProductionLocationOption')->willReturn('recommended_location_all_locations');
        $this->companyRepository->method('get')->willReturn($this->companyInterface);

        $actualResult = $this->defaultConfigProvider->afterGetConfig($this->subject, $result);

        $this->assertArrayHasKey('product_image_data', $actualResult);
        $this->assertArrayHasKey(20, $actualResult['product_image_data']);
        $this->assertEquals('http://image.url/printondemand.jpg', $actualResult['product_image_data'][20]);
        $this->assertArrayNotHasKey(21, $actualResult['product_image_data']);
        // Assert that when quote has no ID, has_selected_prod_loc remains false
        $this->assertEquals(false, $actualResult['has_selected_prod_loc']);
    }

    /**
     * Tests that the addProductionLocationInfo method does not modify the result
     * when the "Allow Customer Choose Production Location" toggle is off.
     * @return void
     */
    public function testProductionLocationInfo_WhenToggleIsOff_ShouldNotModifyResult()
    {
        $companyId = 1;
        $result = [];

        $isAllowCustomerChooseProductionLocationToggle = false;
        $resultAfter = $this->defaultConfigProvider->addProductionLocationInfo(
            $result,
            $this->companyInterface,
            $companyId,
            $isAllowCustomerChooseProductionLocationToggle
        );
        $this->assertArrayNotHasKey('restricted_production_location', $resultAfter);
    }

    /**
     * Tests the addProductionLocationInfo method to ensure that when there are no production locations,
     * the 'recommended_production_location' key is not set in the result array.
     * 
     * This test simulates a scenario where no production locations are available and verifies that the 
     * result array does not contain the 'recommended_production_location' key.
     *
     * @return void
     */
    public function testProductionLocationInfo_WhenNoLocations_ShouldSetIsProductionLocationTrueAndNoLocationData()
    {
        $companyId = 1;
        $result = [];

        $isAllowCustomerChooseProductionLocationToggle = true;
        $collectionMock = $this->getMockBuilder(ProCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter','getIterator'])
            ->getMock();

        $collectionMock->method('addFieldToFilter')->willReturnSelf();

        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $prodLocationModelMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();

        $prodLocationModelMock->method('getCollection')->willReturn($collectionMock);

        $this->productionLocationFactory->method('create')->willReturn($prodLocationModelMock);

        $resultAfter = $this->defaultConfigProvider->addProductionLocationInfo(
            $result,
            $this->companyInterface,
            $companyId,
            $isAllowCustomerChooseProductionLocationToggle
        );
        $this->assertArrayNotHasKey('recommended_production_location', $resultAfter);
    }

    /**
     * Tests the addProductionLocationInfo method to ensure that when the
     * "Allow Customer Choose Production Location" toggle is enabled, the method
     * correctly adds the production locations to the result array.
     * 
     * This test simulates a scenario where the toggle is enabled and the collection
     * contains both recommended and restricted production locations. It checks that
     * the 'is_simplified_production_location' key is added to the result array.
     *
     * @return void
     */
    public function testProductionLocationInfo_WhenToggleIsOnAndCollectionsHaveData_ShouldAddLocationsCorrectly()
    {
        $companyId = 1;
        $result = [];

        $isAllowCustomerChooseProductionLocationToggle = true;
        $recommendedLocationData = ['id' => 10, 'is_recommended_store' => 1];
        $restrictedLocationData = ['id' => 20, 'is_recommended_store' => 0];

        $recommendedLocationMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $recommendedLocationMock->method('getData')->willReturn($recommendedLocationData);

        $restrictedLocationMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $restrictedLocationMock->method('getData')->willReturn($restrictedLocationData);

        $collectionMock = $this->getMockBuilder(ProCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter','getIterator'])
            ->getMock();

        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMockIterator = new \ArrayIterator([$recommendedLocationMock, $restrictedLocationMock]);

        $collectionMock->method('getIterator')->willReturn($collectionMockIterator);

        $prodLocationModelMock = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();

        $prodLocationModelMock->method('getCollection')->willReturn($collectionMock);

        $this->productionLocationFactory->method('create')->willReturn($prodLocationModelMock);

        $resultAfter = $this->defaultConfigProvider->addProductionLocationInfo(
            $result,
            $this->companyInterface,
            $companyId,
            $isAllowCustomerChooseProductionLocationToggle
        );
        $this->assertArrayHasKey('is_simplified_production_location', $resultAfter);
    }
}
