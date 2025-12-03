<?php

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\ThirdPartyProduct;

use Fedex\ExpiredItems\Model\Quote\UpdaterModel;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\Add;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\Update;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class AddTest extends TestCase
{
    /**
     * @var Add
     */
    private Add $add;

    /**
     * @var \YourNamespace\CartInterface
     */
    private $cart;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchBuilder;

    /**
     * @var Update
     */
    private $update;

    /**
     * @var NonCustomizableProduct
     */
    private $nonCustomizableProduct;

    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var MarketplaceCheckoutHelper
     */
    private $marketplaceCheckoutHelper;

    /**
     * @var UpdaterModel
     */
    private $updaterModel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Configurable
     */
    private $configurable;

    protected function setUp(): void
    {
        $this->cart = $this->createMock(Cart::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['setPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->searchBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->update = $this->createMock(Update::class);
        $this->nonCustomizableProduct = $this->createMock(NonCustomizableProduct::class);
        $this->checkoutHelper = $this->createMock(CheckoutHelper::class);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->updaterModel = $this->createMock(UpdaterModel::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configurable = $this->createMock(Configurable::class);

        $this->add = new Add(
            $this->cart,
            $this->productRepository,
            $this->request,
            $this->serializer,
            $this->searchBuilder,
            $this->update,
            $this->nonCustomizableProduct,
            $this->checkoutHelper,
            $this->marketplaceCheckoutHelper,
            $this->updaterModel,
            $this->logger,
            $this->configurable
        );
    }

    /**
     * Tests adding an item to the shopping cart.
     * @return void
     */
    public function testAddItemToCart()
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $product->method('getSku')->willReturn('testSku');

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $productSearchResults = $this->createMock(ProductSearchResultsInterface::class);
        $productSearchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $this->productRepository->method('getList')->with($searchCriteria)->willReturn($productSearchResults);
        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct', 'getQty', 'getId', 'clearMessage', 'setHasError', 'setQty'])
            ->addMethods(['getProductId', 'getMiraklOfferId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->method('getId')->willReturn(1);
        $quoteItem->method('getProduct')->willReturn($product);
        $quoteItem->method('getProductId')->willReturn(1);

        $this->cart->method('getItems')->willReturn([$quoteItem]);
        $this->cart->method('getQuoteProductIds')->willReturn([1, 2, 3]);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->expects($this->once())->method('getId')->willReturn(15);
        $quote->expects($this->once())->method('addItem')->with($quoteItem)->willReturnSelf();
        $quote->expects($this->once())->method('save')->willReturnSelf();
        $this->cart->method('getQuote')->willReturn($quote);
        $this->cart->method('addProduct')
            ->with($product, ['product' => 'testSku', 'qty' => 1, 'offer_id' => 123])
            ->willReturn($this->cart);
        $this->cart->method('save')->willReturn($this->cart);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->request->expects($this->once())->method('setPostValue')
            ->with('isMarketplaceProduct', true)
            ->willReturnSelf();
        $this->request->expects($this->any())->method('getParam')
            ->willReturnMap([
                ['qty', null, 1],
                ['offer_id', null, 123],
                ['super_attribute', null, ['color' => 'red']],
                ['punchout_disabled', null, 1],
            ]);

        $this->update->expects($this->once())->method('updateThirdPartyItemSellerPunchout')
            ->with(
                $quoteItem,
                $product,
                [
                    'sku' => 'testSku',
                    'qty' => 1,
                    'isMarketplaceProduct' => true,
                    'super_attribute' => ['color' => 'red']
                ]
            )
            ->willReturn($quoteItem);

        $this->marketplaceCheckoutHelper->expects($this->once())
            ->method('isToggleD214903Enabled')
            ->willReturn(true);
        $quoteItem->expects($this->once())->method('getMiraklOfferId')
            ->willReturn(null);
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Fedex\Cart\Model\Quote\ThirdPartyProduct\Add::addItemToCart:146 Quote ID: 15 Missing Mirakl Offer ID');
        $this->updaterModel->expects($this->once())
            ->method('synchronize')
            ->with($quote)
            ->willReturnSelf();

        $this->add->addItemToCart([
            'sku' => 'testSku',
            'qty' => 1,
            'isMarketplaceProduct' => true,
            'super_attribute' => ['color' => 'red']
        ]);

        $this->assertContains($quoteItem, $this->cart->getItems());
    }

    /**
     * Test that child product is assigned to $product when it exists and has an ID
     */
    public function testAddItemToCartWithChildProduct(): void
    {
        $configurableProduct = $this->createMock(ProductInterface::class);
        $configurableProduct->method('getId')->willReturn(1);
        $configurableProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $configurableProduct->method('getSku')->willReturn('configurable-sku');

        $childProduct = $this->createMock(ProductInterface::class);
        $childProduct->method('getId')->willReturn(2);
        $childProduct->method('getTypeId')->willReturn('simple');
        $childProduct->method('getSku')->willReturn('child-sku');

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $productSearchResults = $this->createMock(ProductSearchResultsInterface::class);
        $productSearchResults->expects($this->once())->method('getItems')->willReturn([$configurableProduct]);
        $this->productRepository->method('getList')->with($searchCriteria)->willReturn($productSearchResults);

        $this->cart->method('getQuoteProductIds')
            ->willReturn([]);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getMiraklOfferId'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(2);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $this->configurable->expects($this->once())
            ->method('getProductByAttributes')
            ->with(['color' => 'red'], $configurableProduct)
            ->willReturn($childProduct);

        $this->cart->expects($this->once())
            ->method('addProduct')
            ->with(
                $childProduct,
                $this->callback(function ($params) {
                    return $params['product'] === 'child-sku';
                })
            )
            ->willReturn($this->cart);

        $this->cart->method('getItems')->willReturn([$quoteItem]);
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('addItem')->willReturnSelf();
        $quote->method('save')->willReturnSelf();
        $quote->method('getId')->willReturn(1);
        $this->cart->method('getQuote')->willReturn($quote);
        $this->cart->method('save')->willReturn($this->cart);

        $this->request->method('setPostValue')->willReturnSelf();
        $this->request->method('getParam')->willReturnMap([
            ['qty', null, 1],
            ['offer_id', null, 123],
            ['punchout_disabled', null, false],
        ]);

        $this->update->method('updateThirdPartyItemSellerPunchout')
            ->willReturn($quoteItem);

        $this->marketplaceCheckoutHelper->method('isToggleD214903Enabled')->willReturn(false);

        $this->add->addItemToCart([
            'sku' => 'configurable-sku',
            'super_attribute' => ['color' => 'red']
        ]);

        $this->assertContains($quoteItem, $this->cart->getItems());
        $this->assertEquals(2, $quoteItem->getProductId());
    }

    /**
     * Test that product quantity is updated in cart when punchout is disabled and product is not configurable
     */
    public function testAddItemToCartWithPunchoutDisabledAndNonConfigurable(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn('simple-product');

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $productSearchResults = $this->createMock(ProductSearchResultsInterface::class);
        $productSearchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $this->productRepository->method('getList')->with($searchCriteria)->willReturn($productSearchResults);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->request->method('getParam')->willReturnMap([
            ['qty', null, 2],
            ['punchout_disabled', null, true],
            ['offer_id', null, null],
        ]);

        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(true);

        $this->cart->method('getQuoteProductIds')->willReturn([1]);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getHasError'])
            ->onlyMethods(['setQty', 'clearMessage', 'setHasError', 'getQty', 'getMessage'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $qty = 1;
        $quoteItem->method('getQty')->willReturnCallback(function () use (&$qty) {
            return $qty;
        });
        $quoteItem->method('setQty')->willReturnCallback(function ($newQty) use (&$qty) {
            $qty = $newQty;
        });
        $quoteItem->expects($this->once())->method('clearMessage');
        $quoteItem->expects($this->once())->method('setHasError')->with(false);
        $quoteItem->expects($this->once())->method('setQty')->with(3.0);
        $quoteItem->method('getHasError')->willReturn(false);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('getItems')->willReturn([$quoteItem]);
        $this->cart->method('getQuote')->willReturn($quote);

        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $this->cart->expects($this->never())->method('addProduct');

        $this->nonCustomizableProduct->method('validateProductMaxQty')->willReturn('');

        $this->add->addItemToCart([
            'sku' => 'simple-product'
        ]);

        $this->assertEquals(3.0, $quoteItem->getQty());
    }

    /**
     * Test that configurable product quantity is updated in cart when punchout is disabled
     * and product is configurable with Essendant toggle enabled
     */
    public function testAddItemToCartWithPunchoutDisabledAndConfigurable(): void
    {
        $configurableProduct = $this->createMock(ProductInterface::class);
        $configurableProduct->method('getId')->willReturn(1);
        $configurableProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $configurableProduct->method('getSku')->willReturn('config-product');

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $productSearchResults = $this->createMock(ProductSearchResultsInterface::class);
        $productSearchResults->expects($this->once())->method('getItems')->willReturn([$configurableProduct]);
        $this->productRepository->method('getList')->with($searchCriteria)->willReturn($productSearchResults);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->request->method('getParam')->willReturnMap([
            ['qty', null, 2],
            ['punchout_disabled', null, true],
            ['offer_id', null, null],
            ['super_attribute', null, ['color' => 'red']],
        ]);

        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(true);

        $this->cart->method('getQuoteProductIds')->willReturn([1]);

        $addPartialMock = $this->getMockBuilder(Add::class)
            ->setConstructorArgs([
                $this->cart,
                $this->productRepository,
                $this->request,
                $this->serializer,
                $this->searchBuilder,
                $this->update,
                $this->nonCustomizableProduct,
                $this->checkoutHelper,
                $this->marketplaceCheckoutHelper,
                $this->updaterModel,
                $this->logger,
                $this->configurable
            ])
            ->onlyMethods(['updateVariantProductQtyInCart', 'isPunchoutDisabledProductInCart'])
            ->getMock();

        $addPartialMock->method('isPunchoutDisabledProductInCart')
            ->with($configurableProduct)
            ->willReturn(true);

        $addPartialMock->expects($this->once())
            ->method('updateVariantProductQtyInCart')
            ->with(
                $configurableProduct,
                $this->callback(function ($params) {
                    return isset($params['product'])
                        && $params['product'] === 'config-product'
                        && isset($params['qty'])
                        && $params['qty'] === 2;
                })
            );

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getMiraklOfferId'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getMiraklOfferId')->willReturn('offer-123');

        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('addItem')->willReturnSelf();
        $quote->method('save')->willReturnSelf();
        $quote->method('getId')->willReturn(1);
        $this->cart->method('getQuote')->willReturn($quote);

        $this->update->method('updateThirdPartyItemSellerPunchout')
            ->willReturn($quoteItem);

        $this->marketplaceCheckoutHelper->method('isToggleD214903Enabled')
            ->willReturn(false);

        $addPartialMock->addItemToCart([
            'sku' => 'config-product',
            'super_attribute' => ['color' => 'red']
        ]);

        $this->assertSame($quoteItem, $this
            ->update
            ->updateThirdPartyItemSellerPunchout($quoteItem, $configurableProduct, [
                'sku' => 'config-product',
                'super_attribute' => ['color' => 'red']
            ]));

        $this->assertContains($quoteItem, $this->cart->getItems());

        $this->assertEquals('offer-123', $quoteItem->getMiraklOfferId());
    }

    /**
     * Test that when Essendant toggle is disabled, the cart is set to the result of addProduct
     */
    public function testAddItemToCartWithEssendantToggleDisabled(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn('test-product');

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $productSearchResults = $this->createMock(ProductSearchResultsInterface::class);
        $productSearchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $this->productRepository->method('getList')->with($searchCriteria)->willReturn($productSearchResults);

        $this->cart->method('getQuoteProductIds')
            ->willReturn([]);

        $resultCart = $this->createMock(Cart::class);
        $this->cart->expects($this->once())
            ->method('addProduct')
            ->with(
                $product,
                $this->callback(function ($params) {
                    return $params['product'] === 'test-product' &&
                        $params['qty'] === 1;
                })
            )
            ->willReturn($resultCart);

        $resultCart->expects($this->once())->method('save');

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getMiraklOfferId'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getMiraklOfferId')->willReturn('offer-123');

        $resultCart->expects($this->once())
            ->method('getItems')
            ->willReturn([$quoteItem]);

        $this->cart->expects($this->never())->method('getItems');

        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('addItem')->willReturnSelf();
        $quote->method('save')->willReturnSelf();
        $quote->method('getId')->willReturn(1);
        $this->cart->method('getQuote')->willReturn($quote);

        $this->marketplaceCheckoutHelper->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(false);

        $this->request->method('getParam')->willReturnMap([
            ['qty', null, 1],
            ['offer_id', null, null],
            ['punchout_disabled', null, false],
        ]);

        $this->request->expects($this->once())
            ->method('setPostValue')
            ->with('isMarketplaceProduct', true);

        $this->update->expects($this->once())
            ->method('updateThirdPartyItemSellerPunchout')
            ->with($quoteItem, $product)
            ->willReturn($quoteItem);

        $this->marketplaceCheckoutHelper->method('isToggleD214903Enabled')
            ->willReturn(false);

        $this->add->addItemToCart([
            'sku' => 'test-product'
        ]);

        $this->assertEquals(1, $quoteItem->getProductId());

        $this->assertEquals('offer-123', $quoteItem->getMiraklOfferId());

        $this->assertFalse($this->marketplaceCheckoutHelper->isToggleD214903Enabled());
    }

    /**
     * Test that an exception is thrown when no quote item is found
     *
     * @expectedException \Exception
     */
    public function testAddItemToCartThrowsExceptionWhenNoQuoteItemFound(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn('test-product');

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $productSearchResults = $this->createMock(ProductSearchResultsInterface::class);
        $productSearchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $this->productRepository->method('getList')->with($searchCriteria)->willReturn($productSearchResults);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->cart->method('getQuoteProductIds')->willReturn([]);

        $this->cart->expects($this->once())
            ->method('addProduct')
            ->willReturn($this->cart);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(999);

        $this->cart->expects($this->once())
            ->method('getItems')
            ->willReturn([$quoteItem]);

        $this->request->method('getParam')->willReturnMap([
            ['qty', null, 1],
            ['offer_id', null, null],
            ['punchout_disabled', null, false],
        ]);

        $this->request->method('setPostValue')->willReturnSelf();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error adding product to cart.');

        $this->add->addItemToCart([
            'sku' => 'test-product'
        ]);
    }

    /**
     * Test exception handling in addItemToCart method
     */
    public function testAddItemToCartHandlesExceptionAndRethrows(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn('test-product');

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchBuilder->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $productSearchResults = $this->createMock(ProductSearchResultsInterface::class);
        $productSearchResults->expects($this->once())->method('getItems')->willReturn([$product]);
        $this->productRepository->method('getList')->with($searchCriteria)->willReturn($productSearchResults);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId', 'getMiraklOfferId'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);

        $this->cart->method('getQuoteProductIds')->willReturn([]);
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $exception = new LocalizedException(__('Test exception message'));
        $quote = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quote->method('addItem')->willReturnSelf();
        $quote->method('getId')->willReturn(42);
        $quote->method('save')->willThrowException($exception);
        $this->cart->method('getQuote')->willReturn($quote);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $this->request->method('setPostValue')->willReturnSelf();
        $this->request->method('getParam')->willReturnMap([
            ['qty', null, 1],
            ['offer_id', null, null],
            ['punchout_disabled', null, false],
        ]);

        $this->cart->method('addProduct')->willReturnSelf();
        $this->cart->method('save')->willReturnSelf();

        $this->update->method('updateThirdPartyItemSellerPunchout')
            ->willReturn($quoteItem);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Quote ID: 42 Error: Test exception message'));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Test exception message');

        $this->add->addItemToCart([
            'sku' => 'test-product'
        ]);
    }

    /**
     * Test that product quantity is updated in cart
     */
    public function testUpdateProductQtyInCart()
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct', 'getQty', 'clearMessage', 'setHasError', 'setQty'])
            ->addMethods(['getProductId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getQty')->willReturn(1);

        $this->cart->method('getQuote')->willReturnSelf();
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $this->nonCustomizableProduct->method('validateProductMaxQty')->willReturn('');

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateProductQtyInCart');
        $method->setAccessible(true);

        $method->invoke($this->add, $product, 2);

        $this->addToAssertionCount(1);
    }

    /**
     * Test that item quantity is updated correctly
     */
    public function testUpdateItemQuantity()
    {
        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['clearMessage', 'setHasError', 'setQty'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->expects($this->once())->method('clearMessage')->willReturnSelf();
        $quoteItem->expects($this->once())->method('setHasError')->with(false)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setQty')->with(2)->willReturnSelf();

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateItemQuantity');
        $method->setAccessible(true);

        $method->invoke($this->add, $quoteItem, 2);

        $this->addToAssertionCount(1);
    }

    /**
     * Test that isPunchoutDisabledProductInCart returns true when product is in cart and punchout is disabled
     */
    public function testIsPunchoutDisabledProductInCart()
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);

        $this->nonCustomizableProduct->method('isMktCbbEnabled')->willReturn(true);
        $this->request->method('getParam')->willReturn(true);
        $this->cart->method('getQuoteProductIds')->willReturn([1]);

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('isPunchoutDisabledProductInCart');
        $method->setAccessible(true);

        $result = $method->invoke($this->add, $product);

        $this->assertTrue($result);
    }

    /**
     * Test that isPunchoutDisabledProductInCart returns false when product is not in cart
     */
    public function testUpdateVariantProductQtyInCart()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getCustomOption', 'getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->method('getCustomOption')->with('info_buyRequest')->willReturnSelf();
        $product->method('getValue')->willReturn(['super_attribute' => ['color' => 'red']]);
        $product->method('getId')->willReturn(1);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct', 'getQty', 'clearMessage', 'setHasError', 'setQty'])
            ->addMethods(['getProductId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getQty')->willReturn(1);
        $quoteItem->expects($this->once())->method('getProduct')->willReturn($product);
        $quoteItem->expects($this->once())->method('clearMessage')->willReturnSelf();
        $quoteItem->expects($this->once())->method('setHasError')->with(false)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setQty')->with(3)->willReturnSelf();

        $this->cart->method('getQuote')->willReturnSelf();
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $this->nonCustomizableProduct->method('validateProductMaxQty')
            ->with(1, 3)
            ->willReturn('');

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateVariantProductQtyInCart');
        $method->setAccessible(true);

        $method->invoke($this->add, $product, ['qty' => 2]);

        $this->addToAssertionCount(1);
    }

    /**
     * Test that updateVariantProductQtyInCart updates the quantity correctly
     * when the product is a variant and D213961 toggle is enabled
     */
    public function testUpdateVariantProductQtyInCartToggleOn()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getCustomOption', 'getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->method('getCustomOption')->with('info_buyRequest')->willReturnSelf();
        $product->method('getValue')->willReturn(['super_attribute' => ['color' => 'red']]);
        $product->method('getId')->willReturn(1);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct', 'getQty', 'clearMessage', 'setHasError', 'setQty'])
            ->addMethods(['getProductId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getQty')->willReturn(1);
        $quoteItem->expects($this->once())->method('getProduct')->willReturn($product);
        $quoteItem->expects($this->once())->method('clearMessage')->willReturnSelf();
        $quoteItem->expects($this->once())->method('setHasError')->with(false)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setQty')->with(3)->willReturnSelf();

        $this->cart->method('getQuote')->willReturnSelf();
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $this->nonCustomizableProduct->method('validateProductMaxQty')
            ->with($product, 3)
            ->willReturn('');
        $this->nonCustomizableProduct->method('isD213961Enabled')->willReturn(true);

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateVariantProductQtyInCart');
        $method->setAccessible(true);

        $method->invoke($this->add, $product, ['qty' => 2]);

        $this->addToAssertionCount(1);
    }

    /**
     * Test updateProductQtyInCart method when D213961 toggle is enabled
     * This tests the specific code path where product object is passed instead of product ID
     */
    public function testUpdateProductQtyInCartWithD213961Enabled(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId'])
            ->onlyMethods(['getQty', 'clearMessage', 'setHasError', 'setQty'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getQty')->willReturn(1);
        $quoteItem->expects($this->once())->method('clearMessage');
        $quoteItem->expects($this->once())->method('setHasError')->with(false);
        $quoteItem->expects($this->once())->method('setQty')->with(3.0);

        $this->cart->method('getQuote')->willReturnSelf();
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $this->nonCustomizableProduct->expects($this->once())
            ->method('isD213961Enabled')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->once())
            ->method('validateProductMaxQty')
            ->with(
                $this->identicalTo($product),
                $this->equalTo(3.0)
            )
            ->willReturn('');

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateProductQtyInCart');
        $method->setAccessible(true);

        $method->invoke($this->add, $product, 2.0);

        $this->addToAssertionCount(1);
    }

    /**
     * Test that updateProductQtyInCart throws exception when validation fails
     */
    public function testUpdateProductQtyInCartThrowsExceptionOnValidationFailure(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId'])
            ->onlyMethods(['getQty'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getQty')->willReturn(1);

        $this->cart->method('getQuote')->willReturnSelf();
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $this->nonCustomizableProduct->expects($this->once())
            ->method('isD213961Enabled')
            ->willReturn(false);

        $this->nonCustomizableProduct->expects($this->once())
            ->method('validateProductMaxQty')
            ->with(1, 3.0)
            ->willReturn('The requested quantity exceeds the maximum allowed limit.');

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateProductQtyInCart');
        $method->setAccessible(true);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The requested quantity exceeds the maximum allowed limit.');

        $method->invoke($this->add, $product, 2.0);
    }

    /**
     * Test that updateItemQuantity throws exception when item has an error
     */
    public function testUpdateItemQuantityThrowsExceptionOnError(): void
    {
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getHasError'])
            ->onlyMethods(['clearMessage', 'setHasError', 'setQty', 'getMessage'])
            ->getMock();

        $quoteItem->expects($this->once())->method('clearMessage');
        $quoteItem->expects($this->once())->method('setHasError')->with(false);
        $quoteItem->expects($this->once())->method('setQty')->with(5.0);

        $quoteItem->expects($this->once())
            ->method('getHasError')
            ->willReturn(true);
        $quoteItem->expects($this->once())
            ->method('getMessage')
            ->willReturn('Cannot add the item to shopping cart.');

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateItemQuantity');
        $method->setAccessible(true);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot add the item to shopping cart.');

        $method->invoke($this->add, $quoteItem, 5.0);
    }

    /**
     * Test that updateVariantProductQtyInCart throws exception when validation fails
     */
    public function testUpdateVariantProductQtyInCartThrowsExceptionOnValidationFailure(): void
    {
        $addPartialMock = $this->getMockBuilder(Add::class)
            ->setConstructorArgs([
                $this->cart,
                $this->productRepository,
                $this->request,
                $this->serializer,
                $this->searchBuilder,
                $this->update,
                $this->nonCustomizableProduct,
                $this->checkoutHelper,
                $this->marketplaceCheckoutHelper,
                $this->updaterModel,
                $this->logger,
                $this->configurable
            ])
            ->onlyMethods(['getSuperAttributes'])
            ->getMock();

        $addPartialMock->expects($this->once())
            ->method('getSuperAttributes')
            ->willReturn(['color' => 'red']);

        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId'])
            ->onlyMethods(['getProduct', 'getQty'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getProduct')->willReturn($product);
        $quoteItem->method('getQty')->willReturn(1);

        $this->cart->method('getQuote')->willReturnSelf();
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $this->nonCustomizableProduct->method('isD213961Enabled')
            ->willReturn(false);

        $this->nonCustomizableProduct->expects($this->once())
            ->method('validateProductMaxQty')
            ->with(1, 3.0)
            ->willReturn('The requested quantity exceeds the maximum allowed for this variant.');

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateVariantProductQtyInCart');
        $method->setAccessible(true);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The requested quantity exceeds the maximum allowed for this variant.');

        $method->invoke($addPartialMock, $product, ['super_attribute' => ['color' => 'red'], 'qty' => 2]);
    }

    /**
     * Test that updateVariantProductQtyInCart adds a new product when it is not already in the cart
     */
    public function testUpdateVariantProductQtyInCartAddsNewProduct(): void
    {
        $addPartialMock = $this->getMockBuilder(Add::class)
            ->setConstructorArgs([
                $this->cart,
                $this->productRepository,
                $this->request,
                $this->serializer,
                $this->searchBuilder,
                $this->update,
                $this->nonCustomizableProduct,
                $this->checkoutHelper,
                $this->marketplaceCheckoutHelper,
                $this->updaterModel,
                $this->logger,
                $this->configurable
            ])
            ->onlyMethods(['getSuperAttributes'])
            ->getMock();

        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(1);

        $addPartialMock->expects($this->once())
            ->method('getSuperAttributes')
            ->willReturn(['color' => 'blue']);

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getProductId'])
            ->onlyMethods(['getProduct'])
            ->getMock();
        $quoteItem->method('getProductId')->willReturn(1);
        $quoteItem->method('getProduct')->willReturn($product);

        $this->cart->method('getQuote')->willReturnSelf();
        $this->cart->method('getItems')->willReturn([$quoteItem]);

        $params = ['super_attribute' => ['color' => 'red'], 'qty' => 2];

        $this->cart->expects($this->once())
            ->method('addProduct')
            ->with(
                $this->identicalTo($product),
                $this->identicalTo($params)
            )
            ->willReturnSelf();

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('updateVariantProductQtyInCart');
        $method->setAccessible(true);

        $result = $method->invoke($addPartialMock, $product, $params);

        $this->assertNull($result, 'Expected null because updateVariantProductQtyInCart() does not return anything.');
    }

    /**
     * Test that super attributes are correctly retrieved.
     * @return void
     */
    public function testGetSuperAttributes()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getCustomOption', 'getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->method('getCustomOption')->with('info_buyRequest')->willReturnSelf();
        $product->method('getValue')->willReturn(['super_attribute' => ['color' => 'red']]);

        $this->serializer->expects($this->once())
            ->method('unserialize')
            ->willReturn(['super_attribute' => ['color' => 'red']]);

        $reflection = new \ReflectionClass(Add::class);
        $method = $reflection->getMethod('getSuperAttributes');
        $method->setAccessible(true);

        $result = $method->invoke($this->add, $product);

        $this->assertEquals(['color' => 'red'], $result);
    }
}
