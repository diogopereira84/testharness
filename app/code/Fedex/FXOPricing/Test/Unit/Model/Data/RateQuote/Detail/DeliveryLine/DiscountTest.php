<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model\Data\RateQuote\Detail\DeliveryLine;

use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine\Discount;

class DiscountTest extends TestCase
{
    /**
     * Amount key
     */
    private const AMOUNT_KEY = 'amount';

    /**
     * Amount value
     */
    private const AMOUNT_VALUE = 9.99;

    /**
     * Amount value alternative
     */
    private const AMOUNT_VALUE_ALTERNATIVE = 10.50;

    /**
     * Type key
     */
    private const TYPE_KEY = 'type';

    /**
     * Type value
     */
    private const TYPE_VALUE = 'COUPON';

    /**
     * Type value alternative
     */
    private const TYPE_VALUE_ALTERNATIVE = 'OTHER';

    /**
     * @var Discount
     */
    private Discount $discount;

    /**
     * Setup tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->discount = new Discount(
            [
                self::AMOUNT_KEY => self::AMOUNT_VALUE,
                self::TYPE_KEY => self::TYPE_VALUE,
            ]
        );
    }

    /**
     * Test method getType
     *
     * @return void
     */
    public function testGetType()
    {
        $this->assertEquals(self::TYPE_VALUE, $this->discount->getType());
    }

    /**
     * Test method setType
     *
     * @return void
     */
    public function testSetType()
    {
        $this->discount->setType(self::TYPE_VALUE_ALTERNATIVE);
        $this->assertEquals(self::TYPE_VALUE_ALTERNATIVE, $this->discount->getType());
    }

    /**
     * Test method getAmount
     *
     * @return void
     */
    public function testGetAmount()
    {
        $this->assertEquals(self::AMOUNT_VALUE, $this->discount->getAmount());
    }

    /**
     * Test method setAmount
     *
     * @return void
     */
    public function testSetAmount()
    {
        $this->discount->setAmount(self::AMOUNT_VALUE_ALTERNATIVE);
        $this->assertEquals(self::AMOUNT_VALUE_ALTERNATIVE, $this->discount->getAmount());
    }
}
