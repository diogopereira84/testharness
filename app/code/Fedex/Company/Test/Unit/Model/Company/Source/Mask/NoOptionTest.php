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
use Fedex\Company\Model\Company\Source\Mask\NoOption;

class NoOptionTest extends TestCase
{
    /**
     * Field label value
     */
    private const LABEL = 'No';

    /**
     * Field value value
     */
    private const VALUE = null;

    /**
     * @var NoOption
     */
    private NoOption $noOption;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->noOption = new NoOption();
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
            $this->noOption->getLabel()
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
            $this->noOption->getValue()
        );
    }
}
