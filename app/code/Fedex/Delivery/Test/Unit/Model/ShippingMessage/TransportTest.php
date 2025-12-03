<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model\ShippingMessage;

use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Fedex\Delivery\Model\ShippingMessage\Transport;

class TransportTest extends TestCase
{
    /**
     * @var (\Fedex\FXOPricing\Api\Data\AlertCollectionInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $alertCollectionMock;
    /**
     * Rate strategy
     */
    private const STRATEGY = 'rate';

    /**
     * @var MockObject|CartInterface
     */
    private MockObject|CartInterface $cartMock;

    /**
     * @var MockObject|RateInterface
     */
    private MockObject|RateInterface $rateMock;

    /**
     * @var MockObject|RateQuoteInterface
     */
    private MockObject|RateQuoteInterface $rateQuoteMock;

    /**
     * @var TransportInterface
     */
    private TransportInterface $transport;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartMock = $this->getMockForAbstractClass(CartInterface::class);
        $this->cartMock ->method('getId')->willReturn(123);
        $this->rateMock = $this->getMockForAbstractClass(RateInterface::class);
        $this->rateMock->method('getCurrency')->willReturn('USD');
        $this->rateQuoteMock = $this->getMockForAbstractClass(RateQuoteInterface::class);
        $this->rateQuoteMock->method('getCurrency')->willReturn('USD');
        $this->alertCollectionMock = $this->getMockForAbstractClass(AlertCollectionInterface::class);
        $this->transport = new Transport(
            $this->cartMock,
            $this->rateMock,
            $this->rateQuoteMock,
            $this->alertCollectionMock
        );
    }

    /**
     * Test methods getStrategy and setStrategy
     *
     * @return void
     */
    public function testGetAndSetStrategy(): void
    {
        $this->assertNull($this->transport->getStrategy());
        $this->transport->setStrategy(self::STRATEGY);
        $this->assertEquals(self::STRATEGY, $this->transport->getStrategy());
    }

    /**
     * Test method getCart
     *
     * @return void
     */
    public function testGetCart(): void
    {
        $this->assertEquals(123, $this->transport->getCart()->getId());
    }

    /**
     * Test method setCart
     *
     * @return void
     */
    public function testSetCart(): void
    {
        $cart = $this->getMockForAbstractClass(CartInterface::class);
        $cart->method('getId')->willReturn(345);
        $this->transport->setCart($cart);
        $this->assertEquals(345, $this->transport->getCart()->getId());
    }

    /**
     * Test method getFXORate
     *
     * @return void
     */
    public function testGetFXORate(): void
    {
        $this->assertEquals('USD', $this->transport->getFXORate()->getCurrency());
    }

    /**
     * Test method setFXORate
     *
     * @return void
     */
    public function testSetFXORate(): void
    {
        $rate = $this->getMockForAbstractClass(RateInterface::class);
        $rate->method('getCurrency')->willReturn('EUR');
        $this->transport->setFXORate($rate);
        $this->assertEquals('EUR', $this->transport->getFXORate()->getCurrency());
    }

    /**
     * Test method getFXORateQuote
     *
     * @return void
     */
    public function testGetFXORateQuote(): void
    {
        $this->assertEquals('USD', $this->transport->getFXORateQuote()->getCurrency());
    }

    /**
     * Test method setFXORateQuote
     *
     * @return void
     */
    public function testSetFXORateQuote(): void
    {
        $rateQuote = $this->getMockForAbstractClass(RateQuoteInterface::class);
        $rateQuote->method('getCurrency')->willReturn('EUR');
        $this->transport->setFXORateQuote($rateQuote);
        $this->assertEquals('EUR', $this->transport->getFXORateQuote()->getCurrency());
    }
}
