<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Test\Unit\Model;

use Fedex\Base\Exception\RecursionException;
use PHPUnit\Framework\TestCase;
use Fedex\Base\Model\DataObject;

class DataObjectTest extends TestCase
{
    /**
     * DataObject data
     */
    private const DATA = [
        'foo' => 'bar',
        'bar' => 'foo',
    ];

    /**
     * @var DataObject
     */
    private DataObject $dataObject;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->dataObject = new DataObject(self::DATA);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        unset($this->dataObject);
    }

    /**
     * @test
     * @throws RecursionException
     */
    public function testToArrayRecursive(): void
    {
        $nthChild = new DataObject(self::DATA);
        $nthChild2 = new DataObject(self::DATA);
        $nthChild2->setData('nth_child3', new DataObject(self::DATA));
        $nthChild->setData('nth_child2',$nthChild2);
        $this->dataObject->setData('nth_child', $nthChild);
        $this->assertEquals(
            [
                'foo' => 'bar',
                'bar' => 'foo',
                'nth_child' => [
                    'foo' => 'bar',
                    'bar' => 'foo',
                    'nth_child2' => [
                        'foo' => 'bar',
                        'bar' => 'foo',
                        'nth_child3' => [
                            'foo' => 'bar',
                            'bar' => 'foo',
                        ]
                    ]
                ]
            ],
            $this->dataObject->toArrayRecursive()
        );

        $this->assertEquals(
            [
                'foo' => 'bar',
                'nth_child' => [
                    'foo' => 'bar',
                    'bar' => 'foo',
                    'nth_child2' => [
                        'foo' => 'bar',
                        'bar' => 'foo',
                        'nth_child3' => [
                            'foo' => 'bar',
                            'bar' => 'foo',
                        ]
                    ]
                ]
            ],
            $this->dataObject->toArrayRecursive(['foo', 'nth_child'])
        );
    }

    /**
     * @test
     * @throws RecursionException
     */
    public function testToArrayRecursiveException(): void
    {
        $this->expectException(RecursionException::class);
        $nthChild2 = new DataObject(self::DATA);
        $nthChild2->setData('nth_child', $nthChild2);
        $this->dataObject->setData('nth_child2', $nthChild2);
        $this->dataObject->toArrayRecursive(['nth_child2']);
    }
}
