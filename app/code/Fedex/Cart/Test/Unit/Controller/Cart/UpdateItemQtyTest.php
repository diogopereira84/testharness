<?php

namespace Fedex\Cart\Test\Unit\Controller\Cart;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Controller\Cart\UpdateItemQty;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\Update;
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Magento\Checkout\Model\Cart as CustomerCart;
use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\Exception\LocalizedException;

class UpdateItemQtyTest extends TestCase
{
    private $updateItemQty;
    private $request;
    private $response;
    private $context;
    private $quantityProcessor;
    private $formKeyValidator;
    private $checkoutSession;
    private $json;
    private $logger;
    private $update;
    private $nonCustomizableProduct;
    private $cart;
    private $helper;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->addMethods(['getMethod', 'isPost'])
            ->getMockForAbstractClass();

        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['representJson'])
            ->getMockForAbstractClass();

        $this->context = $this->createMock(Context::class);
        $this->quantityProcessor = $this->createMock(RequestQuantityProcessor::class);
        $this->formKeyValidator = $this->createMock(FormKeyValidator::class);
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->json = $this->createMock(Json::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->update = $this->createMock(Update::class);
        $this->nonCustomizableProduct = $this->createMock(NonCustomizableProduct::class);
        $this->cart = $this->createMock(CustomerCart::class);
        $this->helper = $this->createMock(Data::class);

        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getResponse')->willReturn($this->response);

        $this->updateItemQty = new UpdateItemQty(
            $this->context,
            $this->quantityProcessor,
            $this->formKeyValidator,
            $this->checkoutSession,
            $this->json,
            $this->logger,
            $this->update,
            $this->nonCustomizableProduct,
            $this->cart,
            $this->helper
        );
    }

    public function testExecute()
    {
        $cartParam = ['1' => ['qty' => 2]];
        $this->request->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $product = $this->createMock(Product::class);
        $product->expects($this->any())->method('getId')->willReturn(1);

        $item = $this->createMock(Item::class);
        $item->expects($this->atMost(2))->method('getProduct')->willReturn($product);
        $item->expects($this->any())->method('save')->willReturnSelf();

        $quote = $this->createMock(Quote::class);
        $quote->method('getItemById')->with(1)->willReturn($item);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->request->method('getParam')->with('cart')->willReturn($cartParam);
        $this->quantityProcessor->expects($this->any())->method('process')
            ->with($cartParam)
            ->willReturn($cartParam);

        $this->helper->method('isCBBToggleEnabled')->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isProductPunchoutDisabledForThirdPartyItem')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isD213961Enabled')
            ->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('validateProductMaxQty')
            ->with(1, 2)
            ->willReturn('');

        $this->update->expects($this->any())
            ->method('updateThirdPartyItem')
            ->with($item, $product)
            ->willReturn($item);

        $this->response->expects($this->any())->method('representJson')->with('')->willReturnSelf();
        $result = $this->updateItemQty->execute();
        $this->assertNull($result);
    }

    public function testExecuteD213961Enabled()
    {
        $cartParam = ['1' => ['qty' => 2]];
        $this->request->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $product = $this->createMock(Product::class);

        $item = $this->createMock(Item::class);
        $item->expects($this->atMost(2))->method('getProduct')->willReturn($product);
        $item->expects($this->any())->method('save')->willReturnSelf();

        $quote = $this->createMock(Quote::class);
        $quote->method('getItemById')->with(1)->willReturn($item);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->request->method('getParam')->with('cart')->willReturn($cartParam);
        $this->quantityProcessor->expects($this->any())->method('process')
            ->with($cartParam)
            ->willReturn($cartParam);

        $this->helper->method('isCBBToggleEnabled')->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isProductPunchoutDisabledForThirdPartyItem')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isD213961Enabled')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('validateProductMaxQty')
            ->with($product, 2)
            ->willReturn('');

        $this->update->expects($this->any())
            ->method('updateThirdPartyItem')
            ->with($item, $product)
            ->willReturn($item);

        $this->response->expects($this->any())->method('representJson')->with('')->willReturnSelf();
        $result = $this->updateItemQty->execute();
        $this->assertNull($result);
    }

    public function testExecuteCbbToggleEnabled()
    {
        $cartParam = ['1' => ['qty' => 2]];
        $this->request->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $product = $this->createMock(Product::class);
        $product->expects($this->any())->method('getId')->willReturn(1);

        $item = $this->createMock(Item::class);
        $item->expects($this->atMost(2))->method('getProduct')->willReturn($product);
        $item->expects($this->any())->method('save')->willReturnSelf();

        $quote = $this->createMock(Quote::class);
        $quote->method('getItemById')->with(1)->willReturn($item);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->request->method('getParam')->with('cart')->willReturn($cartParam);
        $this->quantityProcessor->expects($this->any())->method('process')
            ->with($cartParam)
            ->willReturn($cartParam);

        $this->helper->method('isCBBToggleEnabled')->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isProductPunchoutDisabledForThirdPartyItem')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isD213961Enabled')
            ->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('validateProductMaxQty')
            ->with(1, 2)
            ->willReturn('');

        $this->update->expects($this->any())
            ->method('updateThirdPartyItem')
            ->with($item, $product, null, $cartParam)
            ->willReturn($item);

        $this->cart->expects($this->any())->method('suggestItemsQty')->with($cartParam)->willReturn($cartParam);
        $this->cart->expects($this->any())->method('updateItems')->with($cartParam)->willReturnSelf();
        $this->cart->expects($this->any())->method('save')->willReturnSelf();

        $this->response->expects($this->any())->method('representJson')->with('')->willReturnSelf();
        $result = $this->updateItemQty->execute();
        $this->assertNull($result);
    }

    public function testExecuteCbbToggleEnabledD213961Enabled()
    {
        $cartParam = ['1' => ['qty' => 2]];
        $this->request->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $product = $this->createMock(Product::class);

        $item = $this->createMock(Item::class);
        $item->expects($this->atMost(2))->method('getProduct')->willReturn($product);
        $item->expects($this->any())->method('save')->willReturnSelf();

        $quote = $this->createMock(Quote::class);
        $quote->method('getItemById')->with(1)->willReturn($item);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->request->method('getParam')->with('cart')->willReturn($cartParam);
        $this->quantityProcessor->expects($this->any())->method('process')
            ->with($cartParam)
            ->willReturn($cartParam);

        $this->helper->method('isCBBToggleEnabled')->willReturn(true);

        $this->nonCustomizableProduct->method('isD213961Enabled')->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isProductPunchoutDisabledForThirdPartyItem')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isD213961Enabled')
            ->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('validateProductMaxQty')
            ->with($product, 2)
            ->willReturn('');

        $this->update->expects($this->any())
            ->method('updateThirdPartyItem')
            ->with($item, $product, null, $cartParam)
            ->willReturn($item);

        $this->cart->expects($this->any())->method('suggestItemsQty')->with($cartParam)->willReturn($cartParam);
        $this->cart->expects($this->any())->method('updateItems')->with($cartParam)->willReturnSelf();
        $this->cart->expects($this->any())->method('save')->willReturnSelf();

        $this->response->expects($this->any())->method('representJson')->with('')->willReturnSelf();
        $result = $this->updateItemQty->execute();
        $this->assertNull($result);
    }

    public function testUpdateItemQuantity()
    {
        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getProduct', 'getQty', 'clearMessage', 'setHasError', 'setQty'])
            ->addMethods(['getProductId', 'getHasError'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())->method('clearMessage');
        $item->expects($this->any())->method('setHasError')->with(false);
        $item->expects($this->any())->method('setQty')->with(2);
        $item->method('getHasError')->willReturn(false);

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('updateItemQuantity');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty, $item, 2);
        $this->assertNull($result);
    }

    public function testUpdateItemQuantityWillThrowException()
    {
        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['clearMessage', 'setHasError', 'setQty', 'getMessage'])
            ->addMethods(['getHasError'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())->method('clearMessage');
        $item->expects($this->any())->method('setHasError')->with(false);
        $item->expects($this->any())->method('setQty')->with(2);
        $item->method('getHasError')->willReturn(true);
        $item->method('getMessage')->willReturn('Test Message');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(__('Test Message'));

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('updateItemQuantity');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty, $item, 2);

        $this->assertNull($result);
    }

    public function testJsonResponse()
    {
        $this->response->expects($this->any())->method('representJson');

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('jsonResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty, 'Error message');

        $this->assertNull($result);
    }

    public function testGetResponseData()
    {
        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('getResponseData');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty, 'Error message');

        $this->assertEquals(['success' => false, 'error_message' => 'Error message'], $result);
    }

    public function testValidateRequest()
    {
        $this->request->method('isPost')->willReturn(true);

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('validateRequest');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty);

        $this->assertNull($result);
    }

    public function testValidateRequestWillThrowException()
    {
        $this->request->method('isPost')->willReturn(false);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(__('Page Not Found'));

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('validateRequest');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty);

        $this->assertNull($result);
    }

    public function testValidateFormKey()
    {
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('validateFormKey');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty);

        $this->assertNull($result);
    }

    public function testValidateFormKeyWillThrowException()
    {
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(__('Something went wrong while saving the page. Please refresh the page and try again.'));

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('validateFormKey');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty);

        $this->assertNull($result);
    }

    public function testValidateCartData()
    {
        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('validateCartData');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty, ['cartData']);

        $this->assertNull($result);
    }

    public function testValidateCartDataWillThrowException()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(__('Something went wrong while saving the page. Please refresh the page and try again.'));

        $reflection = new \ReflectionClass(UpdateItemQty::class);
        $method = $reflection->getMethod('validateCartData');
        $method->setAccessible(true);

        $result = $method->invoke($this->updateItemQty, null);

        $this->assertNull($result);
    }

    public function testExecuteWithLocalizedException()
    {
        $cartParam = ['1' => ['qty' => 2]];
        $this->request->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $product = $this->createMock(Product::class);
        $product->expects($this->any())->method('getId')->willReturn(1);

        $item = $this->createMock(Item::class);
        $item->expects($this->atMost(2))->method('getProduct')->willReturn($product);

        $quote = $this->createMock(Quote::class);
        $quote->method('getItemById')->with(1)->willReturn($item);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->request->method('getParam')->with('cart')->willReturn($cartParam);
        $this->quantityProcessor->expects($this->any())->method('process')
            ->with($cartParam)
            ->willReturn($cartParam);

        $this->helper->method('isCBBToggleEnabled')->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isProductPunchoutDisabledForThirdPartyItem')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isD213961Enabled')
            ->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('validateProductMaxQty')
            ->with(1, 2)
            ->willReturn('Maximum quantity exceeded');

        $this->update->expects($this->never())->method('updateThirdPartyItem');

        $result = $this->updateItemQty->execute();
        $this->assertNull($result);
    }

    public function testExecuteWithGenericException()
    {
        $this->request->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $this->request->method('getParam')->with('cart')->willThrowException(new \Exception('Test Exception'));

        $this->logger->expects($this->any())
            ->method('critical')
            ->with('Test Exception');
        $result = $this->updateItemQty->execute();
        $this->assertNull($result);
    }

    public function testExecuteCbbToggleEnabledD213961EnabledCatch()
    {
        $cartParam = ['1' => ['qty' => 2]];
        $this->request->method('isPost')->willReturn(true);
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $product = $this->createMock(Product::class);

        $item = $this->createMock(Item::class);
        $item->expects($this->atMost(2))->method('getProduct')->willReturn($product);
        $item->expects($this->any())->method('save')->willReturnSelf();

        $quote = $this->createMock(Quote::class);
        $quote->method('getItemById')->with(1)->willReturn($item);
        $this->checkoutSession->method('getQuote')->willReturn($quote);

        $this->request->method('getParam')->with('cart')->willReturn($cartParam);
        $this->quantityProcessor->expects($this->any())->method('process')
            ->with($cartParam)
            ->willReturn($cartParam);

        $this->helper->method('isCBBToggleEnabled')->willReturn(true);

        $this->nonCustomizableProduct->method('isD213961Enabled')->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isProductPunchoutDisabledForThirdPartyItem')
            ->willReturn(true);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('isD213961Enabled')
            ->willReturn(false);

        $this->nonCustomizableProduct->expects($this->any())
            ->method('validateProductMaxQty')
            ->with($product, 2)
            ->willReturn('23');

        $this->update->expects($this->any())
            ->method('updateThirdPartyItem')
            ->with($item, $product, null, $cartParam)
            ->willReturn($item);

        $this->cart->expects($this->any())->method('suggestItemsQty')->with($cartParam)->willReturn($cartParam);
        $this->cart->expects($this->any())
            ->method('updateItems')
            ->willThrowException(new LocalizedException(__('Error message')));
        
        $this->cart->expects($this->any())->method('save')->willReturnSelf();

        $this->response->expects($this->any())->method('representJson')->with('')->willReturnSelf();
        $result = $this->updateItemQty->execute();
        $this->assertEquals('', $result);
    }
}
