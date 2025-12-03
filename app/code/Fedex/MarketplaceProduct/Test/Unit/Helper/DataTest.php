<?php

declare (strict_types = 1);

namespace Fedex\MarketplaceProduct\Test\Unit\Helper;

use Fedex\MarketplaceProduct\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Block\Product\Context as ProductContext;
use Mirakl\Connector\Helper\Offer as ConnectorOfferHelper;
use Mirakl\Connector\Helper\StockQty as StockQtyHelper;
use Mirakl\Connector\Model\Offer as OfferModel;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\Core\Model\Shop;
use PHPUnit\Framework\TestCase;
use Mirakl\Connector\Model\Product\Inventory\IsOperatorProductAvailable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Magento\Catalog\Helper\Data as CatalogHelper;

class DataTest extends TestCase
{
    private const XML_MESSAGE_PRODUCT_WITHOUT_OFFER = 'fedex/three_p_product/offer_product_without_relation_message';

    protected $data;

    protected $context;

    protected $config;

    protected $connectorOfferHelper;

    protected $stockQtyHelper;

    protected $isOperatorProductAvailable;

    protected $coreRegistry;

    protected $shopFactory;

    protected $shopResourceFactory;

    protected $marketPlaceHelper;

    protected $catalogHelper;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->config = $this->createMock(ScopeConfigInterface::class);
        $this->connectorOfferHelper = $this->getMockBuilder(ConnectorOfferHelper::class)
            ->setMethods(
                [
                    'getItems',
                    'getAllOffers',
                    'hasAvailableOffersForProduct',
                    'getOfferShop',
                    'getOfferCondition',
                    'getAvailableOffersForProduct'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockQtyHelper = $this->createMock(StockQtyHelper::class);
        $this->isOperatorProductAvailable = $this->getMockBuilder(IsOperatorProductAvailable::class)
                ->setMethods(['isOperatorProductAvailable','execute','setData'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->coreRegistry = $this->createMock(Registry::class);
        $productContext = $this->createMock(ProductContext::class);
        $productContext->method('getRegistry')->willReturn($this->coreRegistry);
        $this->shopFactory = $this->createMock(ShopFactory::class);

        $this->shopResourceFactory = $this->getMockBuilder(ShopResourceFactory::class)
            ->setMethods(['create', 'load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketPlaceHelper = $this->createMock(MarketplaceHelper::class);
        $this->catalogHelper = $this->createMock(CatalogHelper::class);

        $this->data = new Data(
            $this->context,
            $this->config,
            $this->connectorOfferHelper,
            $this->isOperatorProductAvailable,
            $productContext,
            $this->shopFactory,
            $this->shopResourceFactory,
            $this->marketPlaceHelper,
            $this->catalogHelper
        );
    }

    /**
     * @test
     */
    public function testGetShopByOffer()
    {
        $offers = [
            $this->createMock(OfferModel::class),
            $this->createMock(OfferModel::class)
        ];
        $offers[0]->method('getData')->with('shop_id')->willReturn(1);

        $shop = $this->createMock(Shop::class);
        $this->shopFactory->method('create')->willReturn($shop);
        $this->shopResourceFactory->method('create')->willReturn($this->shopResourceFactory);
        $this->shopResourceFactory->method('load')->with($shop, 1)->willReturn($shop);

        $this->assertInstanceOf(Shop::class, $this->data->getShopByOffer($offers));
    }

    /**
     * @test
     */
    public function testGetCustomAttributes()
    {
        $offers = [
            $this->createMock(OfferModel::class),
            $this->createMock(OfferModel::class)
        ];

        $shop = $this->createMock(Shop::class);
        $shop->method('getId')->willReturn(1);
        $offers[0]->method('getId')->willReturn(1);

        $this->shopFactory->expects($this->any())->method('create')->willReturn($shop);
        $this->shopResourceFactory->method('create')->willReturn($this->shopResourceFactory);
        $this->shopResourceFactory->expects($this->any())->method('load')->willReturn($shop);

        $shop->method('getAdditionalInfo')->willReturn([
            'additional_field_values' => [
                [
                    'code' => 'seller-alt-name',
                    'value' => 'My Seller Alt Name'
                ],
                [
                    'code' => 'tooltip',
                    'value' => 'My Tooltip'
                ]
            ]
        ]);

        $this->assertEquals([
            'seller-alt-name' => 'My Seller Alt Name',
            'tooltip' => 'My Tooltip',
            'shop_id' => 1,
            'offer_id' => 1
        ], $this->data->getCustomAttributes($offers));
    }

    /**
     * @test
     */
    public function testGetProduct()
    {
        $product = $this->createMock(Product::class);
        $this->coreRegistry->method('registry')->with('product')->willReturn($product);

        $this->assertInstanceOf(Product::class, $this->data->getProduct());
    }

    /**
     * @test
     */
    public function testGetShop()
    {
        $shop = $this->createMock(Shop::class);
        $this->shopFactory->method('create')->willReturn($shop);
        $this->shopResourceFactory->method('create')->willReturn($this->shopResourceFactory);
        $this->shopResourceFactory->method('load')->with($shop, 1)->willReturn($shop);

        $this->assertInstanceOf(Shop::class, $this->data->getShop(1));
    }

    /**
     * @test
     */
    public function testGetNewOfferStateId()
    {
        $this->config->expects($this->once())
            ->method('getValue')
            ->with(Data::XML_PATH_OFFER_NEW_STATE)
            ->willReturn('value');

        $this->assertEquals('value', $this->data->getNewOfferStateId());
    }

    /**
     * Test getOfferErrorRelationMessage method.
     *
     * @return void
     */
    public function testGetOfferErrorRelationMessage()
    {
        $this->config->expects($this->once())
            ->method('getValue')
            ->with(self::XML_MESSAGE_PRODUCT_WITHOUT_OFFER)
            ->willReturn('value');

        $this->assertEquals('value', $this->data->getOfferErrorRelationMessage());
    }

    /**
     * @test
     */
    public function testHasAvailableOffersForProduct()
    {
        $productMock = $this->createMock(Product::class);

        $this->connectorOfferHelper->expects($this->once())
            ->method('hasAvailableOffersForProduct')
            ->with($productMock)
            ->willReturn(true);

        $this->assertTrue($this->data->hasAvailableOffersForProduct($productMock));
    }

    /**
     * @test
     */
    public function testGetOfferShop()
    {
        $offerMock = $this->createMock(OfferModel::class);
        $shopMock = $this->createMock(Shop::class);

        $this->connectorOfferHelper->expects($this->once())
            ->method('getOfferShop')
            ->with($offerMock)
            ->willReturn($shopMock);

        $this->assertEquals($shopMock, $this->data->getOfferShop($offerMock));
    }

    /**
     * @test
     */
    public function testGetOfferCondition()
    {
        $offerMock = $this->createMock(OfferModel::class);
        $condition = 'condition';

        $this->connectorOfferHelper->expects($this->once())
            ->method('getOfferCondition')
            ->with($offerMock)
            ->willReturn($condition);

        $this->assertEquals($condition, $this->data->getOfferCondition($offerMock));
    }

    /**
     * @test
     */
    public function isOperatorProductAvailable()
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->any())
            ->method('isSalable')
            ->willReturn(true);
        $product->expects($this->any())
            ->method('hasData')
            ->with('operator_product_available')
            ->willReturn(false);
        $this->isOperatorProductAvailable->expects($this->any())
            ->method('execute')
            ->with($product)
            ->willReturn(true);
        $product->expects($this->any())
            ->method('setData')
            ->with('operator_product_available', true)
            ->willReturnSelf();
        $product->expects($this->any())
            ->method('getData')
            ->with('operator_product_available')
            ->willReturn(true);

        $this->data->isOperatorProductAvailable($product);
    }

    /**
     * @test
     */
    public function isProductNew()
    {
        $offerMock = $this->getMockBuilder(OfferModel::class)
            ->setMethods(['getStateCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->config
            ->expects($this->any())
            ->method('getValue')
            ->with('mirakl_frontend/offer/new_state')
            ->willReturn('new_state');

        $offerMock
            ->expects($this->any())
            ->method('getStateCode')
            ->willReturn('new_state');

        $this->assertTrue($this->data->isProductNew($offerMock));
    }

    /**
     * @test
     */
    public function testSortOffers()
    {
        $offer1 = $this->getMockBuilder(OfferModel::class)
            ->setMethods(['getStateCode', 'getPrice', 'getMinShippingPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $offer2 = $this->getMockBuilder(OfferModel::class)
            ->setMethods(['getStateCode', 'getPrice', 'getMinShippingPrice'])
            ->disableOriginalConstructor()
            ->getMock();

        $offer1->method('getStateCode')->willReturn('state1');
        $offer1->method('getPrice')->willReturn(10);
        $offer1->method('getMinShippingPrice')->willReturn(20);
        $offer2->method('getStateCode')->willReturn('state2');
        $offer2->method('getPrice')->willReturn(30);
        $offer2->method('getMinShippingPrice')->willReturn(40);
        $offers = [$offer1, $offer2];

        $this->data->sortOffers($offers);
    }

    /**
     * @test
     */
    public function testSortOffersDifferentStateCode()
    {
        $offer1 = $this->getMockBuilder(OfferModel::class)
            ->setMethods(['getStateCode', 'getPrice', 'getMinShippingPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $offer2 = $this->getMockBuilder(OfferModel::class)
            ->setMethods(['getStateCode', 'getPrice', 'getMinShippingPrice'])
            ->disableOriginalConstructor()
            ->getMock();

        $offer1->method('getStateCode')->willReturn('state');
        $offer1->method('getPrice')->willReturn(10);
        $offer1->method('getMinShippingPrice')->willReturn(20);
        $offer2->method('getStateCode')->willReturn('state');
        $offer2->method('getPrice')->willReturn(30);
        $offer2->method('getMinShippingPrice')->willReturn(40);
        $offers = [$offer1, $offer2];

        $this->data->sortOffers($offers);
    }

    /**
     * @test
     */
    public function testGetAllOffers()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $excludeOfferIds = null;
        $offers = [
            $this->getMockBuilder(OfferModel::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(OfferModel::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(OfferModel::class)
                ->disableOriginalConstructor()
                ->getMock(),
        ];
        $this->connectorOfferHelper->expects($this->any())
            ->method('getAvailableOffersForProduct')
            ->with($product, $excludeOfferIds)
            ->willReturnSelf();

        $this->connectorOfferHelper->expects($this->any())
            ->method('getItems')
            ->willReturn($offers);

        $this->assertEquals($offers, $this->data->getAllOffers($product, $excludeOfferIds));
    }

    /**
     * @test
     */
    public function testGetBestOffer()
    {
        $product = $this->createMock(Product::class);
        $excludeOfferIds = null;

        $product->expects($this->any())
            ->method('isSalable')
            ->willReturn(true);
        $product->expects($this->once())
            ->method('hasData')
            ->with('operator_product_available')
            ->willReturn(false);
        $this->isOperatorProductAvailable->expects($this->any())
            ->method('execute')
            ->with($product)
            ->willReturn(true);
        $product->expects($this->any())
            ->method('setData')
            ->with('operator_product_available', true)
            ->willReturnSelf();

        $offers = [$this->createMock(OfferModel::class)];

        $this->connectorOfferHelper->expects($this->any())
            ->method('getAvailableOffersForProduct')
            ->with($product, $excludeOfferIds)
            ->willReturnSelf();

        $this->connectorOfferHelper->expects($this->any())
            ->method('getItems')
            ->willReturn($offers);

        $this->data->getBestOffer($product);
    }
}
