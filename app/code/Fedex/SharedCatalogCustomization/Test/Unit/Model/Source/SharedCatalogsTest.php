<?php
/**
 * @category  Fedex
 * @package   Fedex_SharedCatalogCustomization
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model\Source;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\SharedCatalogCustomization\Model\Source\SharedCatalogs;
use Fedex\SharedCatalogCustomization\Ui\Component\Form\Field\AdvancedSearchSharedCatalog;
use Magento\Catalog\Api\Data\CategorySearchResultsInterface;
use Magento\Store\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Store\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\Data\GroupInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SharedCatalogsTest extends TestCase
{
    /**
     * @var CategoryListInterface|MockObject
     */
    private $categoryList;

    /**
     * @var GroupCollectionFactory|MockObject
     */
    private $groupCollectionFactory;

    /**
     * @var SortOrderBuilder|MockObject
     */
    private $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;


    /**
     * @var RequestInterface
     */
    private $requestInterface;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SharedCatalogs
     */
    private $sharedCatalogs;

    /**
     * @var ConfigInterface
     */
    private $configInterface;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->categoryList = $this->createMock(
            CategoryListInterface::class
        );

        $this->sortOrderBuilder = $this->createMock(
            SortOrderBuilder::class
        );

        $this->searchCriteriaBuilder = $this->createMock(
            SearchCriteriaBuilder::class
        );

        $this->groupCollectionFactory = $this->createMock(
            GroupCollectionFactory::class
        );

        $this->requestInterface = $this->createMock(
            RequestInterface::class
        );

        $this->configInterface = $this->createMock(
            ConfigInterface::class
        );

        $this->logger = $this->createMock(
            LoggerInterface::class
        );

        $this->sharedCatalogs = new SharedCatalogs(
            $this->categoryList,
            $this->sortOrderBuilder,
            $this->searchCriteriaBuilder,
            $this->groupCollectionFactory,
            $this->requestInterface,
            $this->configInterface,
            $this->logger
        );
    }

    /**
     * Test for toOptionArray method when editing page
     *
     * @return void
     */
    public function testToOptionArrayEditCompany()
    {

        $rootCategoryId = 1234;
        $categoryName = 'Category Name';
        $categoryId = 3;
        $CompanyId = 2;

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($CompanyId);

        $groupInterfaceMock = $this->createMock(
            GroupInterface::class
        );
        $groupInterfaceMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');
        $groupInterfaceMock->expects($this->once())
            ->method('getRootCategoryId')
            ->willReturn($rootCategoryId);
        $groupInterfaceMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $groupCollectionMock = $this->createMock(
            GroupCollection::class
        );
        $groupCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('code', 'ondemand')
            ->willReturnSelf();
        $groupCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($groupInterfaceMock);

        $this->groupCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($groupCollectionMock);
        $this->sortOrderBuilder->expects($this->once())
            ->method('setField')
            ->with(CategoryInterface::KEY_NAME)
            ->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())
            ->method('setAscendingDirection')
            ->willReturnSelf();
        $sortOrder = $this->createMock(SortOrder::class);
        $this->sortOrderBuilder->expects($this->once())
            ->method('create')
            ->willReturn($sortOrder);

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('parent_id', $rootCategoryId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addSortOrder')
            ->with($sortOrder)
            ->willReturnSelf();
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);

        $searchResults = $this->createMock(
            CategorySearchResultsInterface::class
        );
        $this->categoryList->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $categoryInterface = $this->createMock(CategoryInterface::class);
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn(new \ArrayIterator([$categoryInterface]));
        $categoryInterface->expects($this->any())
            ->method('getName')
            ->willReturn($categoryName);
        $categoryInterface->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);

        $this->assertEquals(
            [
                [
                    'label' => $categoryName,
                    'value' => $categoryId
                ]
            ],
            $this->sharedCatalogs->toOptionArray()
        );
    }

    /**
     * Test for toOptionArray method for new company
     *
     * @return void
     */
    public function testToOptionArrayNewCompany()
    {

        $rootCategoryId = 1234;
        $categoryName = 'Category Name';
        $categoryId = 3;
        $CompanyId = false;

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($CompanyId);

        $groupInterfaceMock = $this->createMock(
            GroupInterface::class
        );
        $groupInterfaceMock->expects($this->any())
            ->method('getCode')
            ->willReturn('ondemand');
        $groupInterfaceMock->expects($this->once())
            ->method('getRootCategoryId')
            ->willReturn($rootCategoryId);
        $groupInterfaceMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $groupCollectionMock = $this->createMock(
            GroupCollection::class
        );
        $groupCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('code', 'ondemand')
            ->willReturnSelf();
        $groupCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($groupInterfaceMock);

        $this->groupCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($groupCollectionMock);
        $this->sortOrderBuilder->expects($this->once())
            ->method('setField')
            ->with(CategoryInterface::KEY_NAME)
            ->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())
            ->method('setAscendingDirection')
            ->willReturnSelf();
        $sortOrder = $this->createMock(SortOrder::class);
        $this->sortOrderBuilder->expects($this->once())
            ->method('create')
            ->willReturn($sortOrder);

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('parent_id', $rootCategoryId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addSortOrder')
            ->with($sortOrder)
            ->willReturnSelf();
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);

        $searchResults = $this->createMock(
            CategorySearchResultsInterface::class
        );
        $this->categoryList->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);

        $categoryInterface = $this->createMock(CategoryInterface::class);
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn(new \ArrayIterator([$categoryInterface]));
        $categoryInterface->expects($this->any())
            ->method('getName')
            ->willReturn($categoryName);
        $categoryInterface->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);

        $this->assertEquals(
            [
                [
                    'label' => AdvancedSearchSharedCatalog::CREATE_NEW_LABEL,
                    'value' => AdvancedSearchSharedCatalog::CREATE_NEW_VALUE
                ],
                [
                    'label' => $categoryName,
                    'value' => $categoryId
                ]
            ],
            $this->sharedCatalogs->toOptionArray()
        );
    }
}
