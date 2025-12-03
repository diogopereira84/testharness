<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model\ShippingMessage;

use Fedex\Delivery\Model\ShippingMessage\RuleComposite;
use Fedex\Delivery\Model\ShippingMessage\RuleCompositeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Fedex\Delivery\Model\ShippingMessage\Transport;
use Fedex\Delivery\Model\ShippingMessage\Rule\RuleInterface;

class RuleCompositeTest extends TestCase
{
    /**
     * @var MockObject|TransportInterface
     */
    private MockObject|TransportInterface $transportMock;

    /**
     * @var RuleInterface
     */
    private RuleInterface $ruleOne;

    /**
     * @var RuleInterface
     */
    private RuleInterface $ruleTwo;

    /**
     * @var RuleCompositeInterface
     */
    private RuleCompositeInterface $composite;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->ruleOne = $this->getMockForAbstractClass(RuleInterface::class);
        $this->ruleTwo = $this->getMockForAbstractClass(RuleInterface::class);
        $this->transportMock = $this->getMockForAbstractClass(TransportInterface::class);
        $this->composite = new RuleComposite([
            $this->ruleOne,
            $this->ruleTwo
        ]);
    }

    /**
     * Test method isValid when all rules are valid
     *
     * @return void
     */
    public function testIsValidTrue(): void
    {
        $this->ruleOne
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->ruleTwo
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->assertTrue($this->composite->isValid($this->transportMock));
    }

    /**
     * Test method isValid when a rule is invalid
     *
     * @return void
     */
    public function testIsValidFalse(): void
    {
        $this->ruleOne
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->ruleTwo
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->assertFalse($this->composite->isValid($this->transportMock));
    }
}
