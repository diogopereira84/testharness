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
use Fedex\Company\Model\Company\Source\Mask\LettersOnlyOption;

class LettersOnlyOptionTest extends TestCase
{
    /**
     * Field label value
     */
    private const LABEL = 'Letters Only';

    /**
     * Field value value
     */
    private const VALUE = 'validate-alpha';

    /**
     * @var LettersOnlyOption
     */
    private LettersOnlyOption $lettersOnlyOption;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->lettersOnlyOption = new LettersOnlyOption();
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
            $this->lettersOnlyOption->getLabel()
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
            $this->lettersOnlyOption->getValue()
        );
    }
}
