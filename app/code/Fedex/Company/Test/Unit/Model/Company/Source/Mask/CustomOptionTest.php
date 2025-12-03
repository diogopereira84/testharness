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
use Fedex\Company\Model\Company\Source\Mask\CustomOption;

class CustomOptionTest extends TestCase
{
    /**
     * Field label value
     */
    private const LABEL = 'Custom';

    /**
     * Field value value
     */
    private const VALUE = 'custom';

    /**
     * @var CustomOption
     */
    private CustomOption $customOption;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->customOption = new CustomOption();
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
            $this->customOption->getLabel()
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
            $this->customOption->getValue()
        );
    }
}
