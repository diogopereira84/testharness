<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model\ShippingMessage\Rule;

use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Fedex\Delivery\Model\ShippingMessage\Transport;
use Fedex\Delivery\Model\ShippingMessage\Rule\IsShippingRule;

class IsShippingRuleTest extends TestCase
{
    /**
     * @var (\Fedex\FXOPricing\Api\Data\AlertCollectionInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $alertCollectionMock;
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
     * @var MockObject|TransportInterface
     */
    private MockObject|TransportInterface $transport;

    /**
     * @var IsShippingRule
     */
    private IsShippingRule $isShippingRule;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartMock = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getIsFromShipping'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();;
        $this->rateMock = $this->getMockForAbstractClass(RateInterface::class);
        $this->alertCollectionMock = $this->getMockForAbstractClass(AlertCollectionInterface::class);
        $this->rateQuoteMock = $this->getMockForAbstractClass(RateQuoteInterface::class);
        $this->transport = new Transport(
            $this->cartMock,
            $this->rateMock,
            $this->rateQuoteMock,
            $this->alertCollectionMock
        );
        $this->isShippingRule = new IsShippingRule();
    }

    /**
     * Test method isValid for cart with delivery type as shipping
     *
     * @return void
     */
    public function testIsValidTrue(): void
    {
        $this->cartMock
            ->expects($this->once())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->assertTrue($this->isShippingRule->isValid($this->transport));
    }

    /**
     * Test method isValid for cart without delivery type as shipping
     *
     * @return void
     */
    public function testIsValidFalse(): void
    {
        $this->cartMock
            ->expects($this->once())
            ->method('getIsFromShipping')
            ->willReturn(false);
        $this->assertFalse($this->isShippingRule->isValid($this->transport));
    }

}
