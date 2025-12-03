<?php

namespace Fedex\Cart\Test\Unit\Observer\Frontend\Checkout;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Observer\Frontend\Checkout\RedirectOnEmptyCart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event as MagentoEvent;
use Magento\Checkout\Model\Cart;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\Action\Action as ControllerAction;
use Magento\Framework\App\Response\Http as ResponseHttp;

class RedirectOnEmptyCartTest extends TestCase
{
    /**
     * @var Cart|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartMock;

    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlMock;

    /**
     * @var ResponseHttp|\PHPUnit\Framework\MockObject\MockObject
     */
    private $responseMock;

    /**
     * @var ControllerAction|\PHPUnit\Framework\MockObject\MockObject
     */
    private $controllerMock;

    /**
     * @var Observer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $observerMock;

    /**
     * @var RedirectOnEmptyCart
     */
    private $subject;

    protected function setUp(): void
    {
        $this->urlMock = $this->createMock(UrlInterface::class);

        $this->responseMock = $this->getMockBuilder(ResponseHttp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRedirect'])
            ->getMock();

        $this->controllerMock = $this->getMockForAbstractClass(
            ControllerAction::class,
            [],
            '',
            false,
            false,
            true,
            ['getResponse', 'execute']
        );
        $this->controllerMock->method('getResponse')->willReturn($this->responseMock);

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(['getControllerAction'])
            ->getMock();
        $this->observerMock->method('getControllerAction')->willReturn($this->controllerMock);

        $this->cartMock = $this->createMock(Cart::class);
        
        $this->subject = new RedirectOnEmptyCart($this->cartMock, $this->urlMock);
    }

    /**
     * Test that the observer's execute method does nothing when the shopping cart is not empty.
     * @return void
     */
    public function testExecuteDoesNothingWhenCartNotEmpty()
    {
        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getItemsCount')->willReturn(5);

        $this->cartMock->method('getQuote')->willReturn($quoteMock);
        $this->urlMock->expects($this->never())->method('getUrl');
        $this->responseMock->expects($this->never())->method('setRedirect');

        $result = $this->subject->execute($this->observerMock);

        $this->assertNull($result, 'Execute should return null');
    }

    /**
     * Test that the checkout process redirects when the shopping cart is empty.
     * @return void
     */
    public function testExecuteRedirectsWhenCartEmpty()
    {
        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getItemsCount')->willReturn(0);

        $this->cartMock->method('getQuote')->willReturn($quoteMock);

        $cartUrl = 'http://example.com/checkout/cart';
        $this->urlMock->expects($this->once())
            ->method('getUrl')
            ->with('checkout/cart')
            ->willReturn($cartUrl);

        $this->responseMock->expects($this->once())
            ->method('setRedirect')
            ->with($cartUrl);

        $result = $this->subject->execute($this->observerMock);

        $this->assertNull($result, 'Execute should return null');
    }
}
