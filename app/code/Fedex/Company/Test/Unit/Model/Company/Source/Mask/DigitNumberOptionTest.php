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
use Fedex\Company\Model\Company\Source\Mask\DigitNumberOption;

class DigitNumberOptionTest extends TestCase
{
    /**
     * Field label value
     */
    private const LABEL = '6 Digit Number';

    /**
     * Field value value
     */
    private const VALUE = 'validate-number-6';

    /**
     * CSS class value
     */
    private const CSS_CLASS = 'minimum-length-6 maximum-length-6';

    /**
     * @var DigitNumberOption
     */
    private DigitNumberOption $digitNumberOption;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->digitNumberOption = new DigitNumberOption();
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
            $this->digitNumberOption->getLabel()
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
            $this->digitNumberOption->getValue()
        );
    }

    /**
     * Test getCssClass method
     *
     * @return void
     */
    public function testGetCssClass(): void
    {
        $this->assertEquals(
            self::CSS_CLASS,
            $this->digitNumberOption->getCssClass()
        );
    }
}
