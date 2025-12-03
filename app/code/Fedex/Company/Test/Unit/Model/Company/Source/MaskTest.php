<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Model\Company\Source;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\Company\Source\Mask\Collection;
use Fedex\Company\Model\Company\Source\Mask;

class MaskTest extends TestCase
{
    protected $mask;
    /**
     * Options as array
     */
    private const OPTIONS_ARRAY = [
        [
            'label' => 'label',
            'value' => 'value',
        ]
    ];

    /**
     * @var Collection|MockObject
     */
    private Collection|MockObject $collectionMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->collectionMock = $this->createMock(
            Collection::class
        );
        $this->mask = new Mask($this->collectionMock);
    }

    /**
     * Test toOptionArray method
     *
     * @return void
     */
    public function testToOptionArray(): void
    {
        $this->collectionMock->expects($this->once())
            ->method('toOptionArray')
            ->willReturn(self::OPTIONS_ARRAY);
        $this->assertEquals(
            self::OPTIONS_ARRAY,
            $this->mask->toOptionArray()
        );
    }
}
