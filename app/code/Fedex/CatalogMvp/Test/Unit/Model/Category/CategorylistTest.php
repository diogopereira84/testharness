<?php

namespace Fedex\CatalogMvp\Test\Unit\Model\Category;

use Fedex\CatalogMvp\Model\Category\Categorylist;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\Category;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CategorylistTest extends TestCase
{
    /** @var CollectionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryCollectionFactory;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Categorylist */
    private $categoryList;

    /** @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $toggleConfig;

    /**
     * Initializes mock dependencies and creates an instance of Categorylist
     * @return void
     */
    protected function setUp(): void
    {
        $this->categoryCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->DisableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->categoryList = new Categorylist(
            $this->categoryCollectionFactory,
            $this->logger,
            $this->toggleConfig
        );
    }

    /**
     * Verifies that toOptionArray() returns an empty array
     * when the toggle is disabled and no categories are available.
     *
     * @return void
     */
    public function testToOptionArrayWithEmptyCollection(): void
    {
        $collectionMock = $this->createMock(Collection::class);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false); // feature toggle disabled

        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToSort')->willReturnSelf();
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->categoryCollectionFactory->method('create')->willReturn($collectionMock);

        $result = $this->categoryList->toOptionArray();
        $this->assertIsArray($result, 'Expected toOptionArray() to always return an array.');

        $isCollectionEmpty = iterator_count($collectionMock->getIterator()) === 0;
        $this->assertTrue($isCollectionEmpty, 'Expected collection to be empty.');

        $isResultEmpty = empty($result);
        $this->assertTrue($isResultEmpty, 'Expected result to be empty when toggle is off and no categories exist.');
    }

    /**
     * Verifies that toOptionArray() correctly builds a category hierarchy
     * when categories exist and the feature toggle is enabled.
     *
     * @return void
     */
    public function testToOptionArrayWithCategories(): void
    {
        $categoryRoot = $this->createMock(Category::class);
        $categoryChild = $this->createMock(Category::class);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $categoryRoot->method('getId')->willReturn(2);
        $categoryRoot->method('getParentId')->willReturn(1);
        $categoryRoot->method('getName')->willReturn('Root Category');

        $categoryChild->method('getId')->willReturn(3);
        $categoryChild->method('getParentId')->willReturn(2);
        $categoryChild->method('getName')->willReturn('Child Category');

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->method('addAttributeToSelect')->willReturnSelf();
        $collectionMock->method('addAttributeToSort')->willReturnSelf();
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(
            new \ArrayIterator([$categoryRoot, $categoryChild])
        );

        $this->categoryCollectionFactory->method('create')->willReturn($collectionMock);

        $result = $this->categoryList->toOptionArray();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Root Category', $result[0]['label']);
        $this->assertArrayHasKey('optgroup', $result[0]);
        $this->assertEquals('Child Category', $result[0]['optgroup'][0]['label']);
    }

    /**
     * Verifies that toOptionArray() gracefully handles exceptions
     * by logging the error and returning an empty array.
     *
     * @return void
     */
    public function testToOptionArrayWithException(): void
    {
        $this->categoryCollectionFactory->method('create')
            ->will($this->throwException(new \Exception('Test exception')));

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error in Categorylist toOptionArray: Test exception')
            );

        $result = $this->categoryList->toOptionArray();

        $this->assertIsArray($result, 'The result should always be an array even on exception.');
        $isExceptionHandled = empty($result);
        $this->assertTrue($isExceptionHandled, 'Expected result to be empty when exception is handled gracefully.');

        $isUnexpectedData = !empty($result);
        $this->assertFalse($isUnexpectedData, 'Unexpected data should not be present when an exception occurs.');
    }

    /**
     * Verifies that prepareCategoryHierarchy() returns an empty array
     * when an invalid (non-existing) parent ID is provided.
     *
     * @return void
     */
    public function testPrepareCategoryHierarchyWithInvalidParent(): void
    {
        $reflection = new \ReflectionClass(Categorylist::class);
        $method = $reflection->getMethod('prepareCategoryHierarchy');
        $method->setAccessible(true); // allow calling protected method

        $categories = [];
        $childrenMap = [];
        $parentId = 999; // Non-existing parent ID

        $result = $method->invokeArgs($this->categoryList, [$categories, $childrenMap, $parentId]);
        $this->assertIsArray($result, 'Expected result type to be array.');
        $isParentFound = isset($childrenMap[$parentId]);
        $this->assertFalse($isParentFound, 'Parent ID should not be found in the children map.');
        $isResultEmpty = empty($result);
        $this->assertTrue($isResultEmpty, 'Expected result to be empty for invalid parent.');
    }
}
