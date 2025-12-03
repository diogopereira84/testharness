<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Test\Unit\Model\Escaper;

use Fedex\Base\Model\Escaper\Price;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    /**
     * Price string to be escaped
     */
    private const PRICE_TO_ESCAPE = '$(123///%%@$.45)';

    /**
     * @var Price
     */
    private Price $priceEscaper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->priceEscaper = new Price();
    }

    /**
     * Test method escape
     *
     * @return void
     */
    public function testEscape(): void
    {
        $this->assertEquals(
            123.45,
            $this->priceEscaper->escape(self::PRICE_TO_ESCAPE)
        );
    }
}
