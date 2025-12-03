<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\RateQuote\Detail;

use Fedex\FXOPricing\Api\Data\RateQuoteDiscountInterfaceFactory;
use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\Discount;
use Fedex\FXOPricing\Model\RateQuote\Detail\DiscountBuilder;

class DiscountBuilderTest extends TestCase
{
    protected $discountFactoryMock;
    private const DISCOUNT_DATA = [
        'type' => 'COUPON',
        'amount' => 9.99,
    ];
    private DiscountBuilder $discountBuilder;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->discountFactoryMock = $this->getMockBuilder(RateQuoteDiscountInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->discountBuilder = new DiscountBuilder(
            $this->discountFactoryMock
        );
    }

    /**
     * Test method build
     *
     * @return void
     */
    public function testBuild(): void
    {
        $this->discountFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn(new Discount());

        $discount = $this->discountBuilder->build(self::DISCOUNT_DATA);

        $this->assertEquals(array_keys(self::DISCOUNT_DATA), array_keys($discount->toArray()));
        $this->assertEquals(self::DISCOUNT_DATA['type'], $discount->getType());
        $this->assertEquals(self::DISCOUNT_DATA['amount'], $discount->getAmount());
    }

}
