<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\UploadToQuote\Test\Unit\Model\Config;

use Fedex\UploadToQuote\Model\Config\Source\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;

class ProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Product
     */
    protected $model;

    /**
     * @var ProductRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $searchCriteriaBuilderMock;

    /**
     * @var attributeSetCollectionFactory $attributeSetCollectionFactory
     */
    protected $attributeSetCollectionFactory;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeSetCollectionFactory = $this
            ->getMockBuilder(AttributeSetCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
                'addFieldToSelect',
                'addFieldToFilter',
                'getFirstItem',
                'getAttributeSetId'
            ])->getMock();

        $this->model = $objectManager->getObject(
            Product::class,
            [
                'productRepository' => $this->productRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'attributeSetCollection'=>$this->attributeSetCollectionFactory
            ]
        );
    }

    /**
     * Test method toOptionArray
     *
     * @return array
     */
    public function testToOptionArray()
    {
        $product1 = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product1->expects($this->once())
            ->method('getName')
            ->willReturn('Product 1');
        $product1->expects($this->once())
            ->method('getSku')
            ->willReturn('SKU1');
        $product1->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $product2 = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product2->expects($this->once())
            ->method('getName')
            ->willReturn('Product 2');
        $product2->expects($this->once())
            ->method('getSku')
            ->willReturn('SKU2');
        $product2->expects($this->once())
            ->method('getId')
            ->willReturn(2);
        $searchResultsMock = $this->getMockBuilder(SearchResults::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$product1, $product2]);

        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('getFirstItem')
            ->willReturnSelf();
        $this->attributeSetCollectionFactory->expects($this->once())
            ->method('getAttributeSetId')
            ->willReturn('15');

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilter')
            ->withConsecutive(
                [ProductInterface::STATUS, Status::STATUS_ENABLED],
                ['attribute_set_id', 15]
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock(SearchCriteria::class));

        $this->productRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($searchResultsMock);

        $expectedResult = [
            ['value' => 1, 'label' => 'Product 1 (SKU: SKU1)'],
            ['value' => 2, 'label' => 'Product 2 (SKU: SKU2)'],
        ];

        $this->assertEquals($expectedResult, $this->model->toOptionArray());
    }
}
