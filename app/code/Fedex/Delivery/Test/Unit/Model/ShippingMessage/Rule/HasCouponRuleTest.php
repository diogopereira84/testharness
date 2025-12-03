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
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Fedex\Delivery\Model\ShippingMessage\Transport;
use Fedex\Delivery\Model\ShippingMessage\Rule\HasCouponRule;

class HasCouponRuleTest extends TestCase
{
    /**
     * @var (\Fedex\FXOPricing\Api\Data\AlertCollectionInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $alertCollectionMock;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestMock;
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
     * @var HasCouponRule
     */
    private HasCouponRule $hasCouponRule;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartMock = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getCouponCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->alertCollectionMock = $this->getMockForAbstractClass(AlertCollectionInterface::class);
        $this->rateMock = $this->getMockForAbstractClass(RateInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->rateQuoteMock = $this->getMockForAbstractClass(RateQuoteInterface::class);
        $this->transport = new Transport(
            $this->cartMock,
            $this->rateMock,
            $this->rateQuoteMock,
            $this->alertCollectionMock
        );
        $this->hasCouponRule = new HasCouponRule($this->requestMock);
    }

    /**
     * Test method isValid for cart with coupon
     *
     * @return void
     */
    public function testIsValidTrue(): void
    {
        $this->cartMock
            ->expects($this->once())
            ->method('getCouponCode')
            ->willReturn('FXO001');
        $this->assertTrue($this->hasCouponRule->isValid($this->transport));
    }

    /**
     * Test method isValid for cart without coupon
     *
     * @return void
     */
    public function testIsValidFalse(): void
    {
        $this->cartMock
            ->expects($this->once())
            ->method('getCouponCode')
            ->willReturn(null);
        $this->assertFalse($this->hasCouponRule->isValid($this->transport));
    }

}
