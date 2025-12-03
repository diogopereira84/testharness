<?php
/**
 * @category     Fedex
 * @package      Fedex_ProductGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Brajmohan Rajput <brajmohan.rajput.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ProductGraphQl\Test\Unit\Model\Resolver\Product;

use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Magento\GraphQl\Model\Query\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Product;
use Fedex\ProductGraphQl\Model\Resolver\Product\GetProductPresetId;

class GetProductPresetIdTest extends TestCase
{
    protected $fieldMock;
    protected $resolveInfoMock;
    protected $productModelMock;
    protected $getProductPresetIdMock;
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var \stdClass|MockObject
     */
    protected $modelMock;

    protected function setUp(): void
    {
        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->modelMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();

        $this->productModelMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load'])
            ->addMethods(['getPresetId'])
            ->getMock();

        $this->getProductPresetIdMock = new GetProductPresetId($this->productModelMock);
    }

    /**
     *  Test case for testResolve
     */
    public function testResolve()
    {
        $this->modelMock->expects($this->exactly(1))->method('getId')->willReturn(542);
        $this->productModelMock->expects($this->any())->method('load')->with(542)->willReturnSelf();
        $this->productModelMock->expects($this->exactly(1))->method('getPresetId')->willReturn('7378883');
        $this->assertEquals(
            '7378883',
            $this->getProductPresetIdMock->resolve(
                $this->fieldMock,
                $this->contextMock,
                $this->resolveInfoMock,
                ['model' => $this->modelMock],
                null
            )
        );
    }

    /**
     *  Test case for testResolveWithNoResults
     */
    public function testResolveWithNoResults()
    {
        $this->modelMock->expects($this->exactly(1))->method('getId')->willReturn(542);
        $this->productModelMock->expects($this->any())->method('load')->with(542)->willReturnSelf();
        $this->productModelMock->expects($this->exactly(1))->method('getPresetId')->willReturn('');
        $this->assertEquals(
            '',
            $this->getProductPresetIdMock->resolve(
                $this->fieldMock,
                $this->contextMock,
                $this->resolveInfoMock,
                ['model' => $this->modelMock],
                null
            )
        );
    }
}
