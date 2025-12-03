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
use Fedex\Delivery\Model\ShippingMessage\Rule\IsEnabledRule;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

class IsEnabledRuleTest extends TestCase
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
     * @var MockObject|DeliveryHelper
     */
    private MockObject|DeliveryHelper $deliveryHelperMock;

    /**
     * @var IsEnabledRule
     */
    private IsEnabledRule $isEnabledRule;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartMock = $this->getMockForAbstractClass(CartInterface::class);
        $this->rateMock = $this->getMockForAbstractClass(RateInterface::class);
        $this->alertCollectionMock = $this->getMockForAbstractClass(AlertCollectionInterface::class);
        $this->rateQuoteMock = $this->getMockForAbstractClass(RateQuoteInterface::class);
        $this->deliveryHelperMock = $this->createMock(DeliveryHelper::class);
        $this->transport = new Transport(
            $this->cartMock,
            $this->rateMock,
            $this->rateQuoteMock,
            $this->alertCollectionMock
        );
        $this->isEnabledRule = new IsEnabledRule($this->deliveryHelperMock);
    }

    /**
     * Test method isValid for the feature toggle enabled
     *
     * @return void
     */
    public function testIsValidTrue(): void
    {
        $this->deliveryHelperMock
            ->expects($this->once())
            ->method('isGroundShippingPromoMessagingActive')
            ->willReturn(true);
        $this->assertTrue($this->isEnabledRule->isValid($this->transport));
    }

    /**
     * Test method isValid for the feature toggle disabled
     *
     * @return void
     */
    public function testIsValidFalse(): void
    {
        $this->deliveryHelperMock
            ->expects($this->once())
            ->method('isGroundShippingPromoMessagingActive')
            ->willReturn(false);
        $this->assertFalse($this->isEnabledRule->isValid($this->transport));
    }
}
