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
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\EavGraphQl\Model\Resolver\Query\Type;
use Magento\EavGraphQl\Model\Resolver\Query\Attribute;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\Product;
use Fedex\ProductGraphQl\Model\Resolver\Product\GetProductEngineProperties;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Eav\Api\Data\AttributeInterface;

class GetProductEnginePropertiesTest extends TestCase
{
    protected $productModelMock;
    protected $fieldMock;
    protected $resolveInfoMock;
    protected $attributeInterfaceMock;
    protected $abstractAttributeMock;
    protected $sourceInterfaceMock;
    protected $productResourceMock;
    protected $getProductEnginePropertiesMock;
    /**
     * @var Type|MockObject
     */
    protected $typeMock;

    /**
     * @var Attribute|MockObject
     */
    protected $attributeMock;

    /**
     * @var EavConfig|MockObject
     */
    protected $eavConfigMock;

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
        $this->typeMock = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getType'])
            ->getMockForAbstractClass();

        $this->attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMockForAbstractClass();

        $this->eavConfigMock = $this->getMockBuilder(EavConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMockForAbstractClass();

        $this->productModelMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'load', 'getResource'])
            ->addMethods(['getVisibleAttributes'])
            ->getMockForAbstractClass();

        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->attributeInterfaceMock = $this->getMockBuilder(AttributeInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFrontendInput'])
            ->getMockForAbstractClass();

        $this->abstractAttributeMock = $this->getMockBuilder(AbstractAttribute::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreLabel'])
            ->onlyMethods(['getSource'])
            ->getMock();

        $this->sourceInterfaceMock = $this->getMockBuilder(SourceInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllOptions'])
            ->getMockForAbstractClass();

        $this->modelMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();

        $this->productResourceMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product::class);

        $this->getProductEnginePropertiesMock = new GetProductEngineProperties(
            $this->typeMock,
            $this->attributeMock,
            $this->eavConfigMock,
            $this->productModelMock
        );
    }

    /**
     *  Test case for testResolve
     */
    public function testResolve()
    {
        $this->modelMock->expects($this->exactly(1))->method('getId')->willReturn(542);
        $this->productModelMock->expects($this->any())->method('load')->with(542)->willReturnSelf();
        $this->productModelMock->expects($this->exactly(1))->method('getVisibleAttributes')
        ->willReturn('paper_type');
        $attributeCode = 'paper_type';
        $this->attributeMock->expects($this->any())->method('getAttribute')
        ->with($attributeCode, 'catalog_product')
        ->willReturn($this->attributeInterfaceMock);
        $this->attributeInterfaceMock->expects($this->any())->method('getFrontendInput')
        ->willReturn('multiselect');
        $this->typeMock->expects($this->any())->method('getType')
        ->with($attributeCode, 'catalog_product')
        ->willReturn('String');
        $this->productModelMock->expects($this->any())->method('getData')
        ->with($attributeCode)
        ->willReturn('12,15,default-12');
        $this->abstractAttributeMock->expects($this->once())->method('getStoreLabel')
        ->willReturn('Paper Type');
        $this->productResourceMock->expects($this->once())->method('getAttribute')
        ->with($attributeCode)
        ->willReturn($this->abstractAttributeMock);
        $this->productModelMock->expects($this->any())->method('getResource')
        ->willReturn($this->productResourceMock);

        $this->testGetAttributeOptions();
        $this->assertNotNull($this->getProductEnginePropertiesMock->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            ['model' => $this->modelMock],
            null
        ));
    }

    /**
     *  Test case for testResolveWithNoResults
     */
    public function testResolveWithNoResults()
    {
        $this->modelMock->expects($this->exactly(1))->method('getId')->willReturn(542);
        $this->productModelMock->expects($this->any())->method('load')->with(542)->willReturnSelf();
        $this->productModelMock->expects($this->exactly(1))->method('getVisibleAttributes')->willReturn('');
        $this->assertEquals(
            [],
            $this->getProductEnginePropertiesMock->resolve(
                $this->fieldMock,
                $this->contextMock,
                $this->resolveInfoMock,
                ['model' => $this->modelMock],
                null
            )
        );
    }

    /**
     *  Test case for testGetAttributeOptions
     */
    public function testGetAttributeOptions()
    {
        $attributeSelectedOptions = ['12','15','default-12'];
        $attributeCode = 'paper_type';

        $allOptions = [
            [
                'choice_id' => 1448988900543,
                'value' => 12,
                'label' => 'Antique Gray',
                'default' => true
            ]
        ];

        $this->eavConfigMock->expects($this->any())->method('getAttribute')
        ->with('catalog_product', $attributeCode)
        ->willReturn($this->abstractAttributeMock);

        $this->sourceInterfaceMock->expects($this->any())->method('getAllOptions')
        ->willReturn($allOptions);

        $this->abstractAttributeMock->expects($this->any())->method('getSource')
        ->willReturn($this->sourceInterfaceMock);

        $result = $this->getProductEnginePropertiesMock->getAttributeOptions(
            $attributeCode,
            $attributeSelectedOptions
        );

        $this->assertNotNull($result);
    }

    /**
     *  Test case for testConvertStringToArrayAttributeValue
     */
    public function testConvertStringToArrayAttributeValue()
    {
        $attributeValues = 'paper_type,paper_size';
        $expectedAttributeValues = ['paper_type', 'paper_size'];
        $result = $this->getProductEnginePropertiesMock->convertStringToArrayAttributeValue($attributeValues);

        $this->assertEquals($expectedAttributeValues, $result);
    }

    /**
     *  Test case for testConvertStringToArrayAttributeValueWithElseIf
     */
    public function testConvertStringToArrayAttributeValueWithElseIf()
    {
        $attributeValues = 'paper_type|paper_size';
        $expectedAttributeValues = ['paper_type', 'paper_size'];
        $result = $this->getProductEnginePropertiesMock->convertStringToArrayAttributeValue($attributeValues);

        $this->assertEquals($expectedAttributeValues, $result);
    }

    /**
     *  Test case for testConvertStringToArrayAttributeValueWithElse
     */
    public function testConvertStringToArrayAttributeValueWithElse()
    {
        $attributeValues = 'paper_type';
        $expectedAttributeValues = ['paper_type'];
        $result = $this->getProductEnginePropertiesMock->convertStringToArrayAttributeValue($attributeValues);

        $this->assertEquals($expectedAttributeValues, $result);
    }
}
