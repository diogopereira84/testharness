<?php
namespace Fedex\SDE\Test\Unit\Plugin\Checkout\Model;

use Fedex\SDE\Helper\SdeHelper;
use Fedex\SDE\Plugin\Checkout\Model\Cart;
use Magento\Checkout\Model\Cart as CartModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    protected $checkoutSessionMock;
    protected $sdeHelperMock;
    protected $quoteMock;
    protected $cartModelMock;
    protected $cart;
    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;

    /**
     * @var SdeHelper|MockObject
     */
    private $sdeHelper;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->sdeHelperMock = $this->createMock(SdeHelper::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->cartModelMock = $this->createMock(CartModel::class);

        $objectManagerHelper = new ObjectManager($this);

        $this->cart = $objectManagerHelper->getObject(
            Cart::class,
            [
                'checkoutSession' => $this->checkoutSessionMock,
                'sdeHelper' => $this->sdeHelperMock,
            ]
        );
    }

    /**
     * @test testafterGetQuote
     */
    public function testafterGetQuote()
    {
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->assertEquals($this->quoteMock, $this->cart->afterGetQuote($this->cartModelMock, $this->quoteMock));
    }

    /**
     * @test testafterGetQuote
     */
    public function testafterGetQuoteIfNotSdeStore()
    {
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);

        $this->checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->assertEquals($this->quoteMock, $this->cart->afterGetQuote($this->cartModelMock, $this->quoteMock));
    }
}
