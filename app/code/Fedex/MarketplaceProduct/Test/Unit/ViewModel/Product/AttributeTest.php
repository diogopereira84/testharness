<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\ViewModel\Product;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Fedex\MarketplaceProduct\Helper\Data;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\ViewModel\Product\Attribute;
use Mirakl\Connector\Model\Offer as OfferModel;
use Fedex\Catalog\Model\Config as CatalogConfig;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;
use Magento\Catalog\Helper\Data as CataloHelper;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Fedex\MarketplaceCheckout\Helper\Data as ToggleHelperData;
use Fedex\MarketplaceProduct\Model\Config\Backend\MarketplaceProduct;
use Fedex\MarketplaceProduct\Model\Config as ToggleConfig;
use Magento\CatalogInventory\Model\Configuration;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Mirakl\Connector\Model\Offer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AttributeTest extends TestCase
{
    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $checkoutHelper;
    /**
     * @var (\Magento\Catalog\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogHelper;
    /**
     * @var (\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $collectionFactory;
    /**
     * @var ToggleHelperData
     */
    private $toggleHelperData;

    /**
     * @var MarketplaceProduct
     */
    private $marketplaceProduct;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ShopManagementInterface
     */
    private $shopManagement;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Attribute
     */
    private $attribute;

    /**
     * @var OfferModel
     */
    private OfferModel $offer;

    /**
     * @var CatalogConfig
     */
    private CatalogConfig $catalogConfig;

    /**
     * @var NonCustomizableProduct
     */
    private NonCustomizableProduct $nonCustomizableProductModel;

    /**
     * @var SsoConfiguration
     */
    private SsoConfiguration $ssoConfiguration;
    private $jsonSerializerMock;

    /**
     * @var (ToggleConfig&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $toggleConfig;

    /**
     * @var (Configuration&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $configuration;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->shopManagement = $this->createMock(ShopManagementInterface::class);
        $this->helper = $this->getMockBuilder(Data::class)
            ->setMethods(
                [
                    'getPriceRanges',
                    'getBestOffer',
                    'getAllOffers',
                    'hasAvailableOffersForProduct',
                    'getOfferErrorRelationMessage',
                    'getCustomAttributes',
                    'canMovePageTitleToNewLocation'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->offer = $this->getMockBuilder(OfferModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPriceRanges','getAdditionalInfo', 'getData', 'getId'])
            ->addMethods(['getOriginPrice', 'getDiscountRanges', 'getDiscountPrice'])
            ->getMock();
        $this->catalogConfig = $this->createMock(CatalogConfig::class);
        $this->nonCustomizableProductModel = $this->createMock(NonCustomizableProduct::class);
        $this->ssoConfiguration = $this->createMock(SsoConfiguration::class);
        $this->checkoutHelper = $this->createMock(CheckoutHelper::class);
        $this->catalogHelper = $this->createMock(CataloHelper::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->marketplaceProduct = $this->createMock(MarketplaceProduct::class);
        $this->toggleHelperData = $this->createMock(ToggleHelperData::class);
        $this->jsonSerializerMock = $this->createMock(Json::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->configuration = $this->createMock(Configuration::class);

        $this->attribute = new Attribute(
            $this->registry,
            $this->shopManagement,
            $this->helper,
            $this->productRepository,
            $this->priceCurrency,
            $this->urlBuilder,
            $this->catalogConfig,
            $this->nonCustomizableProductModel,
            $this->checkoutHelper,
            $this->catalogHelper,
            $this->collectionFactory,
            $this->ssoConfiguration,
            $this->marketplaceProduct,
            $this->toggleHelperData,
            $this->jsonSerializerMock,
            $this->toggleConfig,
            $this->configuration
        );
    }

    public function testHasAvailableOffersForProduct()
    {
        $product = $this->createMock(Product::class);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $this->helper->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->willReturn(true);

        $this->assertEquals(true, $this->attribute->hasAvailableOffersForProduct());
    }

    /**
     * Test getOfferErrorRelationMessage method.
     *
     * @return void
     */
    public function testGetOfferErrorRelationMessage()
    {
        $message = 'test message';

        $this->helper->expects($this->once())
            ->method('getOfferErrorRelationMessage')
            ->willReturn($message);

        $this->assertEquals($message, $this->attribute->getOfferErrorRelationMessage());
    }

    public function testGetAllOffers()
    {
        $product = $this->createMock(Product::class);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $this->helper->expects($this->once())
            ->method('getAllOffers')
            ->with($product)
            ->willReturn(['Test Offer']);

        $this->assertEquals(['Test Offer'], $this->attribute->getAllOffers());
    }

    public function testGetBestOffer()
    {
        $this->offer->method('getData')->with('shop_id')->willReturn(1);
        $product = $this->createMock(Product::class);

        $this->registry->expects($this->any())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $this->helper->expects($this->any())
            ->method('getBestOffer')
            ->with($product)
            ->willReturn($this->offer);

        $this->assertEquals($this->offer, $this->attribute->getBestOffer());
    }

    public function testFormatCurrency()
    {
        $this->priceCurrency->expects($this->once())
            ->method('convertAndFormat')
            ->with(10, true, PriceCurrencyInterface::DEFAULT_PRECISION)
            ->willReturn('Test Currency');

        $this->assertEquals('Test Currency', $this->attribute->formatCurrency(10));
    }

    public function testGetMarketplaceInfo()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCategoryPunchout'])
            ->onlyMethods(['getSku'])
            ->getMock();
        $product->method('getSku')->willReturn('Test Sku');
        $product->method('getCategoryPunchout')->willReturn(false);

        $this->offer->method('getAdditionalInfo')->willReturn(['shop_sku' => 'testsku']);
        $this->offer->method('getId')->willReturn(123);

        $this->helper->method('getBestOffer')->willReturn($this->offer);

        $this->registry->method('registry')->with('product')->willReturn($product);

        $skuInfo = [
            'sku' => 'Test Sku',
            'offer_id' => 123,
            'seller_sku' => 'testsku'
        ];

        $this->urlBuilder->method('getUrl')
            ->with('marketplacepunchout/index/index', $skuInfo)
            ->willReturn('Test Url');

        $this->nonCustomizableProductModel->method('isMktCbbEnabled')->willReturn(true);

        $this->helper->method('getCustomAttributes')
            ->with([$this->offer])
            ->willReturn(['punchout-flow-enhancement' => 'true']);

        $this->ssoConfiguration->method('getHomeUrl')->willReturn('https://example.com');

        $expectedResult = [
            'url' => 'Test Url',
            'punchout_enable' => true,
            'punchout_url' => 'https://example.com' . Attribute::REQUEST_URL,
            'sku_info' => $skuInfo,
            'cbb_toggle' => true
        ];

        $result = $this->attribute->getMarketplaceInfo();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetProduct()
    {
        $product = $this->createMock(Product::class);
        $this->registry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $this->assertEquals($product, $this->attribute->getProduct());
    }
    public function testGetDiscountPrice()
    {
        $this->testGetBestOffer();
        $this->assertIsString($this->attribute->getDiscountPrice());
    }

    public function testHasPriceRanges()
    {
        $discountRange = $this->getMockBuilder(
            \Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection::class
        )->setMethods(['getPriceRanges'])->disableOriginalConstructor()->getMockForAbstractClass();

        $this->offer->method('getPriceRanges')->withAnyParameters()->willReturn($discountRange);

        $product = $this->createMock(Product::class);

        $this->registry->expects($this->any())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $discountRange->method('getPriceRanges')->withAnyParameters()->willReturn(1);

        $this->helper->expects($this->any())
            ->method('getBestOffer')
            ->with($product)
            ->willReturn($this->offer);

        $this->assertInstanceOf(
            \Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection::class,
            $this->attribute->hasPriceRanges()
        );
    }

    public function testGetBasePriceWithoutFormat()
    {
        $product = $this->createMock(Product::class);
        $product->method('getData')
            ->with('base_price')
            ->willReturn('10');

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $this->assertEquals('10', $this->attribute->getBasePriceWithoutFormat());
    }

    public function testGetMinMaxPunchoutInfo()
    {
        $product = $this->createMock(Product::class);
        $this->nonCustomizableProductModel->expects($this->once())
            ->method('getMinMaxPunchoutInfo')
            ->with($this->offer)
            ->willReturn(['min' => 1, 'max' => 2]);

        $this->assertEquals(['min' => 1, 'max' => 2], $this->attribute->getMinMaxPunchoutInfo($this->offer, $product));
    }

    public function testReturnsNullWhenNoCategoryIds()
    {
        $product = $this->createMock(Product::class);
        $product->method('getCategoryIds')->willReturn([]);

        $this->assertNull($this->attribute->getDirectChildCategoryWithChildren($product));
    }

    public function testReturnsNullWhenNoMatchingCategory()
    {
        $product = $this->createMock(Product::class);
        $product->method('getCategoryIds')->willReturn([1,2]);
        $product->method('getStore')->willReturn($this->getStoreMock(10));

        $category1 = $this->getCategoryMock(5, 9, false);
        $category2 = $this->getCategoryMock(6, 8, false);

        $collection = $this->getCategoryCollectionMock([$category1, $category2]);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertNull($this->attribute->getDirectChildCategoryWithChildren($product));
    }

    public function testReturnsCategoryWhenMatching()
    {
        $product = $this->createMock(Product::class);
        $product->method('getCategoryIds')->willReturn([1,2]);
        $product->method('getStore')->willReturn($this->getStoreMock(10));

        $category1 = $this->getCategoryMock(5, 10, true);
        $category2 = $this->getCategoryMock(6, 8, true);

        $collection = $this->getCategoryCollectionMock([$category1, $category2]);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertSame($category1, $this->attribute->getDirectChildCategoryWithChildren($product));
    }
    public function testIsProductFxoNonCustomizableProduct_WithArgument()
    {
        $product = $this->createMock(Product::class);

        $this->nonCustomizableProductModel->expects($this->once())
            ->method('checkIfNonCustomizableProduct')
            ->with($product)
            ->willReturn(true);

        $this->assertTrue($this->attribute->isProductFxoNonCustomizableProduct($product));
    }

    public function testIsProductFxoNonCustomizableProduct_WithoutArgument()
    {
        $product = $this->createMock(Product::class);
        $this->registry->expects($this->once())
            ->method('registry')
            ->with('product')
            ->willReturn($product);

        $this->nonCustomizableProductModel->expects($this->once())
            ->method('checkIfNonCustomizableProduct')
            ->with($product)
            ->willReturn(false);

        $this->assertFalse($this->attribute->isProductFxoNonCustomizableProduct());
    }

    private function getStoreMock($rootCategoryId)
    {
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->method('getRootCategoryId')->willReturn($rootCategoryId);
        return $store;
    }

    private function getCategoryMock($id, $parentId, $hasChildren)
    {
        $category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $category->method('getParentId')->willReturn($parentId);
        $category->method('hasChildren')->willReturn($hasChildren);
        return $category;
    }

    private function getCategoryCollectionMock($categories)
    {
        $collection = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category\Collection::class);
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('addIsActiveFilter')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator($categories));
        return $collection;
    }

    private function getCategoryFactoryMock($collection)
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return $factory;
    }

    private function setupProductAndOfferMocks(): array
    {
        $childSku = 'child-sku-01';
        $offerId = 987;
        $finalPrice = 99.99;
        $attributeId = 123;
        $attributeCode = 'color';
        $attributeValue = 'Red';

        $parentProductMock = $this->createMock(Product::class);
        $childProductMock = $this->createMock(Product::class);
        $offerMock = $this->getMockBuilder(Offer::class)
            ->onlyMethods(['getId'])
            ->addMethods(['getProductSku', 'getFinalPrice', 'getMinOrderQuantity', 'getMaxOrderQuantity'])
            ->disableOriginalConstructor()
            ->getMock();

        $offerMock->method('getProductSku')->willReturn($childSku);
        $offerMock->method('getId')->willReturn($offerId);
        $offerMock->method('getFinalPrice')->willReturn($finalPrice);

        $configurableTypeMock = $this->createMock(Configurable::class);
        $configurableAttributeMock = $this->getMockBuilder(\Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configurableAttributeMock->method('getData')->with('attribute_id')->willReturn($attributeId);
        $configurableTypeMock->method('getConfigurableAttributes')->with($parentProductMock)->willReturn([$configurableAttributeMock]);
        $parentProductMock->method('getTypeInstance')->willReturn($configurableTypeMock);

        $this->productRepository->method('get')->with($childSku)->willReturn($childProductMock);

        $attributeMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->method('getId')->willReturn($attributeId);
        $attributeMock->method('getAttributeCode')->willReturn($attributeCode);
        $childProductMock->method('getAttributes')->willReturn([$attributeMock]);
        $childProductMock->method('getData')->with($attributeCode)->willReturn($attributeValue);

        return [$parentProductMock, $offerMock, $childSku, $offerId, $finalPrice, $attributeId, $attributeValue];
    }

    public function testGetOfferDataWithVariantDetailsReturnsEmptyWhenEssendantToggleIsDisabled()
    {
        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(false);
        $parentProductMock = $this->createMock(Product::class);
        $offerMock = $this->createMock(Offer::class);

        $result = $this->attribute->getOfferDataWithVariantDetails([$offerMock], $parentProductMock);

        $this->assertEmpty($result);
    }

    public function testGetOfferDataWithVariantDetailsWithoutMinMaxQtyToggle()
    {
        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $this->toggleConfig->method('isConfigurableMinMaxWrongQtyToggleEnabled')->willReturn(false);

        [$parentProductMock, $offerMock, $childSku, $offerId, $finalPrice, $attributeId, $attributeValue] = $this->setupProductAndOfferMocks();

        $result = $this->attribute->getOfferDataWithVariantDetails([$offerMock], $parentProductMock);

        $this->assertArrayHasKey($childSku, $result);
        $this->assertEquals($offerId, $result[$childSku]['offer-id']);
        $this->assertEquals($finalPrice, $result[$childSku]['final-price']);
        $this->assertEquals($attributeValue, $result[$childSku]['attributes'][$attributeId]);
        $this->assertArrayNotHasKey('min-qty', $result[$childSku]);
        $this->assertArrayNotHasKey('max-qty', $result[$childSku]);
    }

    public function testGetOfferDataWithVariantDetailsWithMinMaxQtyFromOffer()
    {
        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $this->toggleConfig->method('isConfigurableMinMaxWrongQtyToggleEnabled')->willReturn(true);

        [$parentProductMock, $offerMock, $childSku] = $this->setupProductAndOfferMocks();

        $offerMock->method('getMinOrderQuantity')->willReturn(5);
        $offerMock->method('getMaxOrderQuantity')->willReturn(50);

        $result = $this->attribute->getOfferDataWithVariantDetails([$offerMock], $parentProductMock);

        $this->assertEquals(5, $result[$childSku]['min-qty']);
        $this->assertEquals(50, $result[$childSku]['max-qty']);
    }

    public function testGetOfferDataWithVariantDetailsWithMinMaxQtyFromGlobalConfig()
    {
        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $this->toggleConfig->method('isConfigurableMinMaxWrongQtyToggleEnabled')->willReturn(true);

        [$parentProductMock, $offerMock, $childSku] = $this->setupProductAndOfferMocks();

        $offerMock->method('getMinOrderQuantity')->willReturn(0);
        $offerMock->method('getMaxOrderQuantity')->willReturn(0);

        $this->configuration->method('getMinSaleQty')->willReturn(1);
        $this->configuration->method('getMaxSaleQty')->willReturn(100);

        $result = $this->attribute->getOfferDataWithVariantDetails([$offerMock], $parentProductMock);

        $this->assertEquals(1, $result[$childSku]['min-qty']);
        $this->assertEquals(100, $result[$childSku]['max-qty']);
    }

    public function testGetCategoryAttributes()
    {
        $product = $this->createMock(Product::class);
        $expectedAttributes = ['attribute1' => 'value1'];
        $this->marketplaceProduct->expects($this->once())
            ->method('getCategoryAttributes')
            ->with($product)
            ->willReturn($expectedAttributes);

        $this->assertEquals($expectedAttributes, $this->attribute->getCategoryAttributes($product));
    }

    public function testIsMoveReferenceFromStoreToCategoryToggleEnabled()
    {
        $this->toggleHelperData->expects($this->once())
            ->method('isMoveReferenceFromStoreToCategoryToggleEnabled')
            ->willReturn(true);

        $this->assertTrue($this->attribute->isMoveReferenceFromStoreToCategoryToggleEnabled());
    }

    public function testGetShopByProductReturnsShopForSimpleProduct()
    {
        $product = $this->createMock(Product::class);
        $shop = $this->createMock(ShopInterface::class);

        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(false);
        $this->shopManagement->expects($this->once())
            ->method('getShopByProduct')
            ->with($product)
            ->willReturn($shop);

        $this->assertSame($shop, $this->attribute->getShopByProduct($product));
    }

    public function testGetShopByProductForConfigurableProductWithChild()
    {
        $parentProduct = $this->createMock(Product::class);
        $childProduct = $this->createMock(Product::class);
        $shop = $this->createMock(ShopInterface::class);
        $configurableType = $this->createMock(Configurable::class);

        $this->registry->method('registry')->with('product')->willReturn($parentProduct);
        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $parentProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $parentProduct->method('getTypeInstance')->willReturn($configurableType);
        $configurableType->method('getUsedProducts')->with($parentProduct)->willReturn([$childProduct]);

        $this->shopManagement->expects($this->once())
            ->method('getShopByProduct')
            ->with($childProduct)
            ->willReturn($shop);

        $this->assertSame($shop, $this->attribute->getShopByProduct($parentProduct));
    }

    public function testGetOriginPrice()
    {
        $this->offer->method('getOriginPrice')->willReturn(123.45);
        $this->helper->method('getBestOffer')->willReturn($this->offer);
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));

        $this->assertEquals('123.45', $this->attribute->getOriginPrice());
    }

    public function testGetDiscountRanges()
    {
        $this->offer->method('getDiscountRanges')->willReturn('10|10.50,20|9.50');
        $this->helper->method('getBestOffer')->willReturn($this->offer);
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));

        $this->assertEquals(['10|10.50', '20|9.50'], $this->attribute->getDiscountRanges());
    }
    
    public function testGetDiscountRangesReturnsFalseWhenEmpty()
    {
        $this->offer->method('getDiscountRanges')->willReturn('');
        $this->helper->method('getBestOffer')->willReturn($this->offer);
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));

        $this->assertFalse($this->attribute->getDiscountRanges());
    }

    public function testGetDiscountSaved()
    {
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));
        $this->helper->method('getBestOffer')->willReturn($this->offer);

        $this->offer->method('getDiscountRanges')->willReturn('1|10.00');
        $this->offer->method('getDiscountPrice')->willReturn(10.00);
        $this->offer->method('getOriginPrice')->willReturn(10.00);
        $this->assertFalse($this->attribute->getDiscountSaved());
    }

    public function testGetDiscountSavedFalse()
    {
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));
        $this->helper->method('getBestOffer')->willReturn($this->offer);

        $this->offer->method('getDiscountRanges')->willReturn(false);
        $this->assertFalse($this->attribute->getDiscountSaved());
    }

    public function testGetDiscountSavedWithOffer()
    {
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));
        $this->helper->method('getBestOffer')->willReturn($this->offer);
        $this->offer->method('getDiscountRanges')->willReturn('1|10.00');

        $this->offer->method('getDiscountPrice')->willReturn(8.50);
        $this->offer->method('getOriginPrice')->willReturn(10.00);
        $this->priceCurrency->method('convertAndFormat')->with(1.50)->willReturn('$1.50');
        $this->assertEquals('$1.50', $this->attribute->getDiscountSaved());
    }
    
    public function testIsProductHasOffer()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);

        $this->helper->expects($this->exactly(2))
            ->method('getBestOffer')
            ->with($product)
            ->willReturnOnConsecutiveCalls($this->offer, null);

        $this->assertTrue($this->attribute->isProductHasOffer());
        $this->assertFalse($this->attribute->isProductHasOffer());
    }

    public function testGetCategoryProductAdditionalInstructions()
    {
        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $product = $this->createMock(Product::class);
        $this->catalogHelper->method('getProduct')->willReturn($product);
        
        $instructions = 'Test Instructions';
        $this->marketplaceProduct->method('getCategoryAttributes')
            ->with($product)
            ->willReturn(['product_detail_page_additional_information' => $instructions]);

        $this->assertEquals($instructions, $this->attribute->getCategoryProductAdditionalInstructions());
    }
    
    public function testGetCategoryProductAdditionalInstructionsReturnsEmptyWhenToggleIsDisabled()
    {
        $this->checkoutHelper->method('isEssendantToggleEnabled')->willReturn(false);
        $this->assertEquals('', $this->attribute->getCategoryProductAdditionalInstructions());
    }
    
    public function testGetCatalogConfig()
    {
        $this->assertSame($this->catalogConfig, $this->attribute->getCatalogConfig());
    }

    public function testIsConfigurableProduct()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);

        $product->method('getTypeId')
            ->willReturnOnConsecutiveCalls(Configurable::TYPE_CODE, 'simple');

        $this->assertTrue($this->attribute->isConfigurableProduct());
        $this->assertFalse($this->attribute->isConfigurableProduct());
    }
    
    public function testGetProductSpecificationsForSimpleProduct()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn('simple-sku');
        $product->method('getData')->with('product_specifications')->willReturn('Simple Spec');

        $expected = ['simple-sku' => 'Simple Spec'];
        $this->assertEquals($expected, $this->attribute->getProductSpecifications());
    }

    public function testGetProductSpecificationsForConfigurableProduct()
    {
        $parentProduct = $this->createMock(Product::class);
        $childProduct1 = $this->createMock(Product::class);
        $childProduct2 = $this->createMock(Product::class);
        $configurableType = $this->createMock(Configurable::class);

        $this->registry->method('registry')->with('product')->willReturn($parentProduct);
        $parentProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $parentProduct->method('getTypeInstance')->willReturn($configurableType);
        $configurableType->method('getUsedProducts')->with($parentProduct)->willReturn([$childProduct1, $childProduct2]);

        $childProduct1->method('getSku')->willReturn('child-1');
        $childProduct1->method('getData')->with('product_specifications')->willReturn('Spec 1');
        $childProduct2->method('getSku')->willReturn('child-2');
        $childProduct2->method('getData')->with('product_specifications')->willReturn(null); // Test null case

        $expected = ['child-1' => 'Spec 1'];
        $this->assertEquals($expected, $this->attribute->getProductSpecifications());
    }
    
    public function testCanMovePageTitleToNewLocation()
    {
        $this->helper->method('canMovePageTitleToNewLocation')->willReturn(true);
        $this->assertTrue($this->attribute->canMovePageTitleToNewLocation());
    }
    
    public function testProductHasCanvaDesign()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);
        $product->method('getData')->with('has_canva_design')->willReturn(true);
        
        $this->assertTrue($this->attribute->productHasCanvaDesign());
    }
    
    public function testIsNewUnitCostAvailable()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);

        $this->catalogConfig->method('getTigerDisplayUnitCost3P1PProducts')->willReturn(true);
        $product->method('getData')->with('unit_cost')->willReturn('10.00');
        $this->assertTrue($this->attribute->isNewUnitCostAvailable());
    }
    
    public function testGetProductUnitCost()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);
        $product->method('getData')->with('unit_cost')->willReturn(15.50);
        $this->priceCurrency->method('convertAndFormat')->with(15.50)->willReturn('$15.50');

        $this->assertEquals('$15.50', $this->attribute->getProductUnitCost());
    }

    public function testIsNewUnitCostAvailableReturnsFalseWhenToggleDisabled()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);
        $product->method('getData')->with('unit_cost')->willReturn('10.00');

        $this->catalogConfig->method('getTigerDisplayUnitCost3P1PProducts')->willReturn(false);
        $this->assertFalse($this->attribute->isNewUnitCostAvailable());
    }

    /**
     * @return array
     */
    private function getPriceRangeMocks(): array
    {
        $priceRange1 = $this->getMockBuilder(\Mirakl\MMP\Common\Domain\DiscountRange::class)
            ->addMethods(['getPrice', 'getQuantityThreshold'])
            ->disableOriginalConstructor()
            ->getMock();

        $priceRange1->method('getPrice')->willReturn(100.00);
        $priceRange1->method('getQuantityThreshold')->willReturn(1);

        $priceRange2 = $this->getMockBuilder(\Mirakl\MMP\Common\Domain\DiscountRange::class)
            ->addMethods(['getPrice', 'getQuantityThreshold'])
            ->disableOriginalConstructor()
            ->getMock();
        $priceRange2->method('getPrice')->willReturn(90.00);
        $priceRange2->method('getQuantityThreshold')->willReturn(20);

        return [$priceRange1, $priceRange2];
    }

    /**
     * @param string $discountRangeString
     */
    private function setupDiscountAndPriceRangeMocks(string $discountRangeString = '10|99.00,20|89.00')
    {
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));
        $this->helper->method('getBestOffer')->willReturn($this->offer);
        $this->offer->method('getDiscountRanges')->willReturn($discountRangeString);

        $priceRanges = $this->getPriceRangeMocks();
        $this->offer->method('getPriceRanges')->willReturn($priceRanges);
    }

    public function testGetDiscount()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);
        $this->helper->method('getBestOffer')->with($product)->willReturn($this->offer);

        $this->offer->method('getDiscountRanges')->willReturn('1|10.00');
        $this->offer->method('getDiscountPrice')->willReturn(8.00);
        $this->offer->method('getOriginPrice')->willReturn(10.00);
        $this->assertEquals('8.00', $this->attribute->getDiscount());
    }

    public function testGetMinOriginPrice()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals(100.00, $this->attribute->getMinOriginPrice());
    }

    public function testGetMaxOriginPrice()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals(90.00, $this->attribute->getMaxOriginPrice());
    }

    public function testGetMaxOriginQuantity()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals(20, $this->attribute->getMaxOriginQuantity());
    }

    public function testGetMaxDiscount()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals(['20', '89.00'], $this->attribute->getMaxDiscount());
    }

    public function testGetMinDiscount()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals(['10', '99.00'], $this->attribute->getMinDiscount());
    }

    public function testGetMinDiscountPrice()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals('99.00', $this->attribute->getMinDiscountPrice());
    }

    public function testGetMinDiscountQuantity()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals('10', $this->attribute->getMinDiscountQuantity());
    }

    public function testGetMaxDiscountPrice()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals('89.00', $this->attribute->getMaxDiscountPrice());
    }

    public function testGetMaxDiscountQuantity()
    {
        $this->setupDiscountAndPriceRangeMocks();
        $this->assertEquals('20', $this->attribute->getMaxDiscountQuantity());
    }

    public function testHasDiscountTrue()
    {
        $this->setupDiscountAndPriceRangeMocks('10|99.00,20|89.00');
        $this->assertTrue($this->attribute->hasDiscount());
    }

    public function testHasDiscountFalse()
    {
        $this->setupDiscountAndPriceRangeMocks('10|101.00,20|95.00');
        $this->assertFalse($this->attribute->hasDiscount());
    }

    public function testHasDiscountAndPriceRanges()
    {
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));
        $this->helper->method('getBestOffer')->willReturn($this->offer);
        $priceRangeCollectionMock = $this->createMock(\Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection::class);

        $this->offer->method('getDiscountRanges')->willReturn('1|10.00');
        $this->offer->method('getPriceRanges')->willReturn($priceRangeCollectionMock);
        $this->assertTrue($this->attribute->hasDiscountAndPriceRanges());
    }

    public function testHasDiscountAndPriceRangesFalse()
    {
        $this->registry->method('registry')->with('product')->willReturn($this->createMock(Product::class));
        $this->helper->method('getBestOffer')->willReturn($this->offer);
        $priceRangeCollectionMock = $this->createMock(\Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection::class);

        $this->offer->method('getDiscountRanges')->willReturn('');
        $this->offer->method('getPriceRanges')->willReturn($priceRangeCollectionMock);
        $this->assertFalse($this->attribute->hasDiscountAndPriceRanges());
    }

    public function testHasDiscountForMoreQuantitiesTrue()
    {
        $this->setupDiscountAndPriceRangeMocks('10|99.00,20|89.00');
        $this->assertTrue($this->attribute->hasDiscountForMoreQuantities());
    }

    public function testHasDiscountForMoreQuantitiesFalse()
    {
        $this->setupDiscountAndPriceRangeMocks('10|99.00');
        $this->assertFalse($this->attribute->hasDiscountForMoreQuantities());
    }

    public function testHasDiscountPerQtyPriceTrue()
    {
        $this->setupDiscountAndPriceRangeMocks('10|99.00,20|89.00');
        $this->assertTrue($this->attribute->hasDiscountPerQtyPrice());
    }

    public function testHasDiscountPerQtyPriceFalse()
    {
        $this->setupDiscountAndPriceRangeMocks('10|99.00,20|95.00');
        $this->assertFalse($this->attribute->hasDiscountPerQtyPrice());
    }

    public function testGetTigerDisplayUnitCost3P1PProducts()
    {
        $this->catalogConfig->expects($this->once())
            ->method('getTigerDisplayUnitCost3P1PProducts')
            ->willReturn(true);
        $this->assertTrue($this->attribute->getTigerDisplayUnitCost3P1PProducts());
    }

    /**
     * @dataProvider basePriceAvailabilityProvider
     */
    public function testIsBasePriceAndBaseQuantityAvailable($baseQuantity, $basePrice, $expected)
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);

        $map = [
            ['base_quantity', null, $baseQuantity],
            ['base_price', null, $basePrice]
        ];
        $product->method('getData')->willReturnMap($map);

        $this->assertEquals($expected, $this->attribute->isBasePriceAndBaseQuantityAvailable());
    }

    public function basePriceAvailabilityProvider(): array
    {
        return [
            'both available' => ['10', '9.99', true],
            'quantity missing' => [null, '9.99', false],
            'price missing' => ['10', null, false],
            'both missing' => [null, null, false],
        ];
    }

    public function testGetBaseQuantity()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);
        $product->expects($this->once())
            ->method('getData')
            ->with('base_quantity')
            ->willReturn('5 units');

        $this->assertEquals('5 units', $this->attribute->getBaseQuantity());
    }

    public function testGetBasePrice()
    {
        $product = $this->createMock(Product::class);
        $this->registry->method('registry')->with('product')->willReturn($product);

        $product->expects($this->once())
            ->method('getData')
            ->with('base_price')
            ->willReturn(12.34);

        $this->priceCurrency->expects($this->once())
            ->method('convertAndFormat')
            ->with(12.34)
            ->willReturn('$12.34');

        $this->assertEquals('$12.34', $this->attribute->getBasePrice());
    }
}
