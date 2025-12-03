<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Model\Company\Source\Mask;

use Fedex\Company\Model\Company\Source\Mask\SixLetterWordOption;
use PHPUnit\Framework\TestCase;

class SixLetterWordTest extends TestCase
{
    /**
     * Field label value
     */
    private const LABEL = '6 Letter Word';

    /**
     * Field value value
     */
    private const VALUE = 'validate-alpha-6';

    /**
     * CSS class value
     */
    private const CSS_CLASS = 'minimum-length-6 maximum-length-6';

    /**
     * @var SixLetterWordOption
     */
    private SixLetterWordOption $sixLetterWordOption;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->sixLetterWordOption = new SixLetterWordOption();
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
            $this->sixLetterWordOption->getLabel()
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
            $this->sixLetterWordOption->getValue()
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
            $this->sixLetterWordOption->getCssClass()
        );
    }
}
