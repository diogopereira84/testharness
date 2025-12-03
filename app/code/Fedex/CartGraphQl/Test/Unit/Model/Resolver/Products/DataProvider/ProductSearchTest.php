<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver\Products\DataProvider;

use Fedex\CartGraphQl\Model\Resolver\Products\DataProvider\ProductSearch;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionPostProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch\ProductCollectionSearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;

class ProductSearchTest extends TestCase
{
    /**
     * @var ProductSearch
     */
    private $productSearch;

    /**
     * @var MockObject|CollectionFactory
     */
    private $collectionFactoryMock;

    /**
     * @var MockObject|ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactoryMock;

    /**
     * @var MockObject|CollectionProcessorInterface
     */
    private $collectionPreProcessorMock;

    /**
     * @var MockObject|CollectionPostProcessorInterface
     */
    private $collectionPostProcessorMock;

    /**
     * @var MockObject|SearchResultApplierFactory
     */
    private $searchResultApplierFactoryMock;

    /**
     * @var MockObject|ProductCollectionSearchCriteriaBuilder
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var MockObject|Visibility
     */
    private $catalogProductVisibilityMock;

    /**
     * Sets up the test environment, initializing mocks and the ProductSearch instance.
     */
    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactoryMock = $this->createMock(ProductSearchResultsInterfaceFactory::class);
        $this->collectionPreProcessorMock = $this->createMock(CollectionProcessorInterface::class);
        $this->collectionPostProcessorMock = $this->createMock(CollectionPostProcessorInterface::class);
        $this->searchResultApplierFactoryMock = $this->createMock(SearchResultApplierFactory::class);
        $this->searchCriteriaBuilderMock = $this->createMock(ProductCollectionSearchCriteriaBuilder::class);
        $this->catalogProductVisibilityMock = $this->createMock(Visibility::class);

        $this->productSearch = new ProductSearch(
            $this->collectionFactoryMock,
            $this->searchResultsFactoryMock,
            $this->collectionPreProcessorMock,
            $this->collectionPostProcessorMock,
            $this->searchResultApplierFactoryMock,
            $this->searchCriteriaBuilderMock,
            $this->catalogProductVisibilityMock
        );
    }

    /**
     * Tests the getList method with a custom ALS product collection load.
     */
    public function testGetListWithCustomAlsProductCollectionLoad(): void
    {
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $searchResultMock = $this->createMock(SearchResultInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addAttributeToSelect', 'addIdFilter', 'getItems', 'getSize', 'getSelect'])
            ->addMethods(['order'])
            ->getMock();

        $this->collectionFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock->expects(self::once())
            ->method('addAttributeToSelect')
            ->with('*');

        $collectionMock->expects(self::once())
            ->method('addIdFilter');

        $searchResultMock->expects(self::once())
            ->method('getItems')
            ->willReturn([]);

        $collectionMock->expects(self::once())
            ->method('getItems')
            ->willReturn([]);

        $collectionMock->expects(self::once())
            ->method('getSelect')
            ->willReturnSelf();

        $collectionMock->expects(self::once())
            ->method('getSize')
            ->willReturn(0);

        $this->searchResultsFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($searchResultMock);

        $result = $this->productSearch->getList($searchCriteriaMock, $searchResultMock, [], $contextMock);

        self::assertSame($searchResultMock, $result);
    }

    /**
     * Tests that the getList method correctly extracts product IDs from the search result items.
     *
     * @return void
     */
    public function testGetListExtractsProductIdsFromSearchResultItems(): void
    {
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $searchResultMock = $this->createMock(SearchResultInterface::class);
        $contextMock = $this->createMock(ContextInterface::class);

        $productMock1 = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock2 = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock1->expects(self::once())
            ->method('getId')
            ->willReturn(101);
        $productMock2->expects(self::once())
            ->method('getId')
            ->willReturn(202);

        $searchResultMock->expects(self::once())
            ->method('getItems')
            ->willReturn([$productMock1, $productMock2]);

        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addIdFilter', 'addAttributeToSelect', 'getSelect', 'getItems', 'getSize'])
            ->getMock();

        $this->collectionFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock->expects(self::once())
            ->method('addIdFilter')
            ->with([101, 202]);
        $collectionMock->expects(self::once())
            ->method('addAttributeToSelect')
            ->with('*');
        $selectMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['order'])
            ->getMock();
        $selectMock->expects(self::once())
            ->method('order')
            ->with($this->isInstanceOf(\Zend_Db_Expr::class));

        $collectionMock->expects(self::once())
            ->method('getSelect')
            ->willReturn($selectMock);
        $collectionMock->expects(self::once())
            ->method('getItems')
            ->willReturn([]);
        $collectionMock->expects(self::once())
            ->method('getSize')
            ->willReturn(2);

        $searchResultsFactoryMock = $this->searchResultsFactoryMock;
        $searchResultsFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($searchResultMock);

        $searchResultMock->expects(self::once())
            ->method('setItems')
            ->with([]);
        $searchResultMock->expects(self::once())
            ->method('setTotalCount')
            ->with(2);

        $result = $this->productSearch->getList($searchCriteriaMock, $searchResultMock, [], $contextMock);

        self::assertSame($searchResultMock, $result);
    }
}
