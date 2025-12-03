<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Model\Entity\Attribute\Source;

use Fedex\ProductEngine\Model\Entity\Attribute\Source\MultiselectAttributes;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeSearchResultsInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\ResourceModel\Entity\AttributeFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MultiselectAttributesTest extends TestCase
{

    protected MultiselectAttributes $multiselectAttributesMock;
    protected AttributeFactory|MockObject $eavAttrEntityMock;
    protected AttributeRepositoryInterface|MockObject $attributeRepositoryMock;
    protected AbstractAttribute|MockObject $abstractAttributeMock;
    protected SearchCriteriaBuilder|MockObject $searchCriteriaBuilderMock;
    protected SearchCriteria|MockObject $searchCriteriaMock;
    protected AttributeSearchResultsInterface|MockObject $attributeSearchResultsMock;
    protected AttributeInterface|MockObject $attributeInterfaceMock;
    protected SortOrderBuilder|MockObject $sortOrderBuilderMock;
    protected SortOrder|MockObject $sortOrderMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->eavAttrEntityMock = $this->createMock(AttributeFactory::class);
        $this->attributeRepositoryMock = $this->createMock(AttributeRepositoryInterface::class);
        $this->abstractAttributeMock = $this->createMock(AbstractAttribute::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->attributeSearchResultsMock = $this->getMockBuilder(AttributeSearchResultsInterface::class)
            ->onlyMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeInterfaceMock = $this->createMock(AttributeInterface::class);
        $this->sortOrderBuilderMock = $this->createMock(SortOrderBuilder::class);
        $this->sortOrderMock = $this->createMock(SortOrder::class);

        $this->multiselectAttributesMock = $this->getMockBuilder(MultiselectAttributes::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs(
                [
                    $this->eavAttrEntityMock,
                    $this->attributeRepositoryMock,
                    $this->searchCriteriaBuilderMock,
                    $this->sortOrderBuilderMock
                ])
            ->getMock();
        $this->multiselectAttributesMock = new MultiselectAttributes(
            $this->eavAttrEntityMock,
            $this->attributeRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->sortOrderBuilderMock
        );
    }

    public function testGetOptionArray()
    {
        $attrSetId = 1;
        $this->sortOrderBuilderMock->expects($this->once())->method('setField')->with('position')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('setAscendingDirection')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('create')->willReturn($this->sortOrderMock);

        $this->searchCriteriaBuilderMock->expects($this->atMost(3))->method('addFilter')
            ->withConsecutive(['frontend_input', 'multiselect'], ['attribute_code', ['visible_attributes', 'canva_size'], 'nin'], ['attribute_set_id', $attrSetId])
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('addSortOrder')->with($this->sortOrderMock)->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($this->searchCriteriaMock);

        $this->attributeInterfaceMock->expects($this->once())->method('getDefaultFrontendLabel')->willReturn('Default Frontend Label');
        $this->attributeInterfaceMock->expects($this->once())->method('getAttributeCode')->willReturn('attribute_code');
        $this->attributeSearchResultsMock->expects($this->once())->method('getItems')->willReturn([$this->attributeInterfaceMock]);
        $this->attributeRepositoryMock->expects($this->once())->method('getList')
            ->with(Product::ENTITY, $this->searchCriteriaMock)->willReturn($this->attributeSearchResultsMock);

        $this->abstractAttributeMock->expects($this->once())->method('getAttributeSetId')->willReturn($attrSetId);
        $this->multiselectAttributesMock->setAttribute($this->abstractAttributeMock);

        $this->assertEquals(['attribute_code' => 'Default Frontend Label'], $this->multiselectAttributesMock->getOptionArray());
    }

    public function testGetOptionText(): void
    {
        $attrSetId = 1;
        $this->sortOrderBuilderMock->expects($this->once())->method('setField')->with('position')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('setAscendingDirection')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('create')->willReturn($this->sortOrderMock);

        $this->searchCriteriaBuilderMock->expects($this->atMost(3))->method('addFilter')
            ->withConsecutive(['frontend_input', 'multiselect'], ['attribute_code', ['visible_attributes', 'canva_size'], 'nin'], ['attribute_set_id', $attrSetId])
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('addSortOrder')->with($this->sortOrderMock)->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($this->searchCriteriaMock);

        $this->attributeInterfaceMock->expects($this->once())->method('getDefaultFrontendLabel')->willReturn('Default Frontend Label');
        $this->attributeInterfaceMock->expects($this->once())->method('getAttributeCode')->willReturn('attribute_code');
        $this->attributeSearchResultsMock->expects($this->once())->method('getItems')->willReturn([$this->attributeInterfaceMock]);
        $this->attributeRepositoryMock->expects($this->once())->method('getList')
            ->with(Product::ENTITY, $this->searchCriteriaMock)->willReturn($this->attributeSearchResultsMock);

        $this->abstractAttributeMock->expects($this->once())->method('getAttributeSetId')->willReturn($attrSetId);
        $this->multiselectAttributesMock->setAttribute($this->abstractAttributeMock);

        $this->assertEquals('Default Frontend Label', $this->multiselectAttributesMock->getOptionText('attribute_code'));
    }

    public function testGetOptionTextFalse(): void
    {
        $attrSetId = 1;
        $this->sortOrderBuilderMock->expects($this->once())->method('setField')->with('position')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('setAscendingDirection')->willReturnSelf();
        $this->sortOrderBuilderMock->expects($this->once())->method('create')->willReturn($this->sortOrderMock);

        $this->searchCriteriaBuilderMock->expects($this->atMost(3))->method('addFilter')
            ->withConsecutive(['frontend_input', 'multiselect'], ['attribute_code', ['visible_attributes', 'canva_size'], 'nin'], ['attribute_set_id', $attrSetId])
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('addSortOrder')->with($this->sortOrderMock)->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($this->searchCriteriaMock);

        $this->attributeInterfaceMock->expects($this->once())->method('getDefaultFrontendLabel')->willReturn('Default Frontend Label');
        $this->attributeInterfaceMock->expects($this->once())->method('getAttributeCode')->willReturn('attribute_code_2');
        $this->attributeSearchResultsMock->expects($this->once())->method('getItems')->willReturn([$this->attributeInterfaceMock]);
        $this->attributeRepositoryMock->expects($this->once())->method('getList')
            ->with(Product::ENTITY, $this->searchCriteriaMock)->willReturn($this->attributeSearchResultsMock);

        $this->assertFalse($this->multiselectAttributesMock->getOptionText('attribute_code'));
    }

    /**
     * @return void
     */
    public function testGetFlatColumns(): void
    {
        $attributeCode = 'attribute_code';
        $this->abstractAttributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $this->multiselectAttributesMock->setAttribute($this->abstractAttributeMock);

        $flatColumnsExpected = [
            $attributeCode => [
                'unsigned' => false,
                'default' => '',
                'extra' => null,
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 1,
                'nullable' => false,
                'comment' => $attributeCode . ' column',
            ]
        ];
        $this->assertEquals($flatColumnsExpected, $this->multiselectAttributesMock->getFlatColumns());
    }

    /**
     * @return void
     */
    public function testGetFlatIndexes(): void
    {
        $this->abstractAttributeMock->expects($this->atMost(2))->method('getAttributeCode')->willReturn('attribute_code');
        $this->multiselectAttributesMock->setAttribute($this->abstractAttributeMock);

        $indexesExpected = ['IDX_ATTRIBUTE_CODE' => ['type' => 'index', 'fields' => ['attribute_code']]];
        $this->assertEquals($indexesExpected, $this->multiselectAttributesMock->getFlatIndexes());
    }
}
