<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Model\Company\Source\Mask;

use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\Company\Source\Mask\NumbersOnlyOption;

class NumbersOnlyOptionTest extends TestCase
{
    /**
     * Field label value
     */
    private const LABEL = 'Numbers Only';

    /**
     * Field value value
     */
    private const VALUE = 'validate-number';

    /**
     * @var NumbersOnlyOption
     */
    private NumbersOnlyOption $numbersOnlyOption;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->numbersOnlyOption = new NumbersOnlyOption();
    }

    /**
     * Test getLabel method
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $this->assertEquals(
            self::LABEL,
            $this->numbersOnlyOption->getLabel()
        );
    }

    /**
     * Test getValue method
     *
     * @return void
     */
    public function testGetValue(): void
    {
        $this->assertEquals(
            self::VALUE,
            $this->numbersOnlyOption->getValue()
        );
    }
}
