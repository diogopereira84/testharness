<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model\Cart;

use Fedex\ProductBundle\Model\Cart\AddBundleToCart;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class AddBundleToCartTest extends TestCase
{
    private $cart;
    private $productRepository;
    private $logger;
    private $request;
    private $addBundleToCart;
    private $product;
    private $quote;

    protected function setUp(): void
    {
        $this->cart = $this->createMock(Cart::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->addBundleToCart = new AddBundleToCart(
            $this->cart,
            $this->productRepository,
            $this->logger,
            $this->request
        );
        $this->product = $this->createMock(Product::class);
        $this->quote = $this->createMock(Quote::class);
    }

    // --- execute() tests ---
    public function testExecuteThrowsExceptionIfProductNotFound()
    {
        $this->productRepository->method('getById')->willReturn(null);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Bundle product not found.');
        $this->addBundleToCart->execute(123, [7 => [10]], 1);
    }

    public function testExecuteThrowsExceptionIfProductIdIsMissing()
    {
        $this->product->method('getId')->willReturn(null);
        $this->productRepository->method('getById')->willReturn($this->product);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Bundle product not found.');
        $this->addBundleToCart->execute(123, [7 => [10]], 1);
    }

    public function testExecuteSavesQuoteIfQuoteIdIsMissing()
    {
        $this->product->method('getId')->willReturn(123);
        $this->productRepository->method('getById')->willReturn($this->product);
        $this->cart->method('addProduct')
            ->with($this->product, $this->anything())
            ->willReturnSelf();
        $this->cart->method('saveQuote')->willReturnSelf();
        $this->cart->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getId')->willReturn(null);
        $this->quote->method('save')->willReturnSelf();
        $this->request->method('getParam')->willReturnMap([
            ['productsData', null, [['instanceId' => 'foo']]],
            ['productsQtyData', null, null],
        ]);
        $this->addBundleToCart->execute(123, [7 => [10, 13]], 2);
    }

    public function testExecuteDoesNotSaveQuoteIfQuoteIdExists()
    {
        $this->product->method('getId')->willReturn(123);
        $this->productRepository->method('getById')->willReturn($this->product);
        $this->cart->method('addProduct')->willReturn($this->cart);
        $this->cart->method('saveQuote')->willReturn($this->cart);
        $this->cart->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getId')->willReturn(999);
        $this->quote->method('save')->willReturn($this->quote);
        $this->quote->expects($this->never())->method('save');
        $this->request->method('getParam')->willReturnMap([
            ['productsData', null, [['instanceId' => 'foo']]],
            ['productsQtyData', null, null],
        ]);
        $this->addBundleToCart->execute(123, [7 => [10, 13]], 2);
    }

    public function testExecuteThrowsExceptionAndLogsErrorOnFailure()
    {
        $this->product->method('getId')->willReturn(123);
        $this->product->method('addCustomOption')
            ->with('bundle_instance_id_hash', 'foo')
            ->willReturnSelf();
        $this->productRepository->method('getById')->willReturn($this->product);
        $this->cart->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getId')->willReturn(999);
        $this->cart->method('addProduct')->willThrowException(new LocalizedException(__('fail')));
        $this->request->method('getParam')->willReturnMap([
            ['productsData', null, [['instanceId' => 'foo']]],
            ['productsQtyData', null, null],
        ]);
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Bundle Add Error: fail'));
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('fail');
        $this->addBundleToCart->execute(123, [7 => [10]], 1);
    }

    public function testExecuteCorrectRequestInfoStructure()
    {
        $this->product->method('getId')->willReturn(123);
        $this->productRepository->method('getById')->willReturn($this->product);
        $this->cart->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getId')->willReturn(999);
        $this->cart->method('addProduct')->willReturn($this->cart);
        $this->cart->method('saveQuote')->willReturn($this->cart);
        $this->cart->method('getQuote')->willReturn($this->quote);
        $this->quote->method('save')->willReturn($this->quote);
        $this->request->method('getParam')->willReturnMap([
            ['productsData', null, [['instanceId' => 'foo']]],
            ['productsQtyData', null, null],
        ]);
        $this->cart->expects($this->once())
            ->method('addProduct')
            ->with(
                $this->product,
                $this->callback(function ($requestInfo) {
                    return $requestInfo['product'] === 123
                        && $requestInfo['qty'] === 2
                        && $requestInfo['bundle_option'] === [7 => [10, 13]]
                        && $requestInfo['bundle_option_qty'] === [10 => 1, 13 => 1];
                })
            )
            ->willReturn($this->cart);
        $this->cart->method('saveQuote')->willReturn($this->cart);
        $this->addBundleToCart->execute(123, [7 => [10, 13]], 2);
    }

    public function testExecuteWithProductsQtyDataAndBundleOptions()
    {
        $this->product->method('getId')->willReturn(123);
        $this->productRepository->method('getById')->willReturn($this->product);
        $this->cart->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getId')->willReturn(999);
        $this->cart->method('saveQuote')->willReturn($this->cart);

        // Mock extension attributes and bundle product options
        $extensionAttributes = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getBundleProductOptions'])
            ->getMock();
        $bundleProductOption = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getProductLinks'])
            ->getMock();
        $productLink1 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId', 'getSku'])
            ->getMock();
        $productLink2 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId', 'getSku'])
            ->getMock();
        $productLink1->method('getId')->willReturn(10);
        $productLink1->method('getSku')->willReturn('sku1');
        $productLink2->method('getId')->willReturn(13);
        $productLink2->method('getSku')->willReturn('sku2');
        $bundleProductOption->method('getProductLinks')->willReturn([$productLink1, $productLink2]);
        $extensionAttributes->method('getBundleProductOptions')->willReturn([$bundleProductOption]);
        $this->product->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $this->product->method('addCustomOption')->willReturnSelf();
        $this->cart->method('addProduct')->willReturn($this->cart);
        $productsQtyData = json_encode(['sku1' => 5, 'sku2' => 7]);
        $this->request->method('getParam')->willReturnMap([
            ['productsData', null, [['instanceId' => 'foo']]],
            ['productsQtyData', null, $productsQtyData],
        ]);

        $this->cart->expects($this->once())
            ->method('addProduct')
            ->with(
                $this->product,
                $this->callback(function ($requestInfo) {
                    return $requestInfo['bundle_option_qty'][10] === 5
                        && $requestInfo['bundle_option_qty'][13] === 7;
                })
            )
            ->willReturn($this->cart);
        $this->addBundleToCart->execute(123, [7 => [10, 13]], 2);
    }

    public function testExecuteWithNoProductsQtyDataOrBundleOptions()
    {
        $this->product->method('getId')->willReturn(123);
        $this->productRepository->method('getById')->willReturn($this->product);
        $this->cart->method('getQuote')->willReturn($this->quote);
        $this->quote->method('getId')->willReturn(999);
        $this->cart->method('saveQuote')->willReturn($this->cart);
        $this->product->method('getExtensionAttributes')->willReturn(null);
        $this->product->method('addCustomOption')->willReturnSelf();
        $this->cart->method('addProduct')->willReturn($this->cart);
        $this->request->method('getParam')->willReturnMap([
            ['productsData', null, [['instanceId' => 'foo']]],
            ['productsQtyData', null, null],
        ]);
        $this->cart->expects($this->once())
            ->method('addProduct')
            ->with(
                $this->product,
                $this->callback(function ($requestInfo) {
                    return $requestInfo['bundle_option_qty'][10] === 1
                        && $requestInfo['bundle_option_qty'][13] === 1;
                })
            )
            ->willReturn($this->cart);
        $this->addBundleToCart->execute(123, [7 => [10, 13]], 2);
    }

    // --- generateInstanceIdHash() tests ---
    public function testGenerateInstanceIdHashThrowsExceptionIfProductsDataNotArray()
    {
        $this->request->method('getParam')->willReturn('not_json');
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Product data must be a valid JSON string.');
        $reflection = new \ReflectionMethod($this->addBundleToCart, 'generateInstanceIdHash');
        $reflection->setAccessible(true);
        $reflection->invoke($this->addBundleToCart);
    }

    public function testGenerateInstanceIdHashThrowsExceptionIfProductsDataMissing()
    {
        $this->request->method('getParam')->willReturn(null);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Product data must be a valid JSON string.');
        $reflection = new \ReflectionMethod($this->addBundleToCart, 'generateInstanceIdHash');
        $reflection->setAccessible(true);
        $reflection->invoke($this->addBundleToCart);
    }

    public function testGenerateInstanceIdHashWorksWithArray()
    {
        $productsData = [
            ['instanceId' => 'a'],
            ['instanceId' => 'b'],
            ['instanceId' => 'c']
        ];
        $this->request->method('getParam')->willReturn($productsData);
        $reflection = new \ReflectionMethod($this->addBundleToCart, 'generateInstanceIdHash');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->addBundleToCart);
        $this->assertSame('a_b_c', $result);
    }

    public function testGenerateInstanceIdHashWorksWithJsonString()
    {
        $productsData = json_encode([
            ['instanceId' => 'x'],
            ['instanceId' => 'y']
        ]);
        $this->request->method('getParam')->willReturn($productsData);
        $reflection = new \ReflectionMethod($this->addBundleToCart, 'generateInstanceIdHash');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->addBundleToCart);
        $this->assertSame('x_y', $result);
    }

    public function testGenerateInstanceIdHashHandlesMissingInstanceId()
    {
        $productsData = [
            ['instanceId' => 'foo'],
            [],
            ['instanceId' => 'bar']
        ];
        $this->request->method('getParam')->willReturn($productsData);
        $reflection = new \ReflectionMethod($this->addBundleToCart, 'generateInstanceIdHash');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->addBundleToCart);
        $this->assertSame('foo__bar', $result);
    }
}

