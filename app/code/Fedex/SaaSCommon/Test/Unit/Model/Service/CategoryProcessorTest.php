<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Service;

use Fedex\Ondemand\Api\Data\ConfigInterface;
use Fedex\SaaSCommon\Model\Service\CategoryProcessor;
use Fedex\SaaSCommon\Model\Service\AllowedCustomerGroupsService;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class CategoryProcessorTest extends TestCase
{
    private $logger;
    private $collectionFactory;
    private $allowedCustomerGroupsService;
    private $ondemandConfig;
    private $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->allowedCustomerGroupsService = $this->createMock(AllowedCustomerGroupsService::class);
        $this->ondemandConfig = $this->createMock(ConfigInterface::class);

        $this->processor = new CategoryProcessor(
            $this->logger,
            $this->collectionFactory,
            $this->allowedCustomerGroupsService,
            $this->ondemandConfig
        );
    }

    public function testProcessUpdatesProducts()
    {
        $categoryId = 10;
        $collection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $collection->expects($this->exactly(2))
            ->method('addAttributeToSelect')
            ->withConsecutive(
                ['category_ids'],
                ['allowed_customer_groups']
            )
            ->willReturnSelf();
        $collection->expects($this->once())->method('addCategoriesFilter')->with(['in' => [$categoryId]])->willReturnSelf();
        $collection->expects($this->once())->method('addStoreFilter')->with(0)->willReturnSelf();

        $product1 = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getId', 'getCategoryIds', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $product2 = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getId', 'getCategoryIds', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $product1->expects($this->exactly(3))->method('getId')->willReturn(1);
        $product1->expects($this->once())->method('getCategoryIds')->willReturn([10, 20]);
        $product1->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('2,1');

        $product2->expects($this->exactly(3))->method('getId')->willReturn(2);
        $product2->expects($this->once())->method('getCategoryIds')->willReturn([10, 30]);
        $product2->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('4,3');

        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$product1, $product2]));

        $this->allowedCustomerGroupsService->expects($this->exactly(2))
            ->method('getAllowedCustomerGroupsFromCategories')
            ->withConsecutive([[10, 20]], [[10, 30]])
            ->willReturnOnConsecutiveCalls(['1', '2'], ['3', '4']);

        $this->allowedCustomerGroupsService->expects($this->exactly(2))
            ->method('updateAttributes')
            ->withConsecutive(
                [[1], '1,2'],
                [[2], '3,4']
            );

        $this->processor->process($categoryId);
    }

    public function testProcessUpdatesProductsWithAllCustomerGroups()
    {
        $categoryId = 10;
        $collection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $collection->expects($this->exactly(2))
            ->method('addAttributeToSelect')
            ->withConsecutive(
                ['category_ids'],
                ['allowed_customer_groups']
            )
            ->willReturnSelf();
        $collection->expects($this->once())->method('addCategoriesFilter')->with(['in' => [$categoryId]])->willReturnSelf();
        $collection->expects($this->once())->method('addStoreFilter')->with(0)->willReturnSelf();

        $product1 = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getId', 'getCategoryIds', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $product2 = $this->getMockBuilder(Product::class)
            ->onlyMethods(['getId', 'getCategoryIds', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $product1->expects($this->exactly(3))->method('getId')->willReturn(1);
        $product1->expects($this->once())->method('getCategoryIds')->willReturn([10, 20]);
        $product1->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('2,1');

        $product2->expects($this->exactly(3))->method('getId')->willReturn(2);
        $product2->expects($this->once())->method('getCategoryIds')->willReturn([10, 30]);
        $product2->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('4,3');

        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$product1, $product2]));

        $this->ondemandConfig->expects($this->exactly(2))
            ->method('getB2bPrintProductsCategory')
            ->willReturn(10);

        $this->allowedCustomerGroupsService->expects($this->exactly(2))
            ->method('updateAttributes')
            ->withConsecutive(
                [[1], '-1'],
                [[2], '-1']
            );

        $this->processor->process($categoryId);
    }

    public function testProcessSkipsAlreadyUpdatedProducts()
    {
        $categoryId = 11;
        $collection = $this->createMock(Collection::class);

        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $collection->expects($this->any())->method('addAttributeToSelect')->willReturnSelf();
        $collection->expects($this->any())->method('addCategoriesFilter')->willReturnSelf();
        $collection->expects($this->any())->method('addStoreFilter')->willReturnSelf();

        $product = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId', 'getCategoryIds', 'getData'])
            ->getMock();

        $product->expects($this->any())->method('getId')->willReturn(5);
        $product->expects($this->once())->method('getCategoryIds')->willReturn([11]);
        $product->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('x');

        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$product, $product]));

        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('getAllowedCustomerGroupsFromCategories')
            ->with([11])
            ->willReturn(['x']);

        $this->allowedCustomerGroupsService->expects($this->never())
            ->method('updateAttributes');

        $this->processor->process($categoryId);
    }

    public function testProcessLogsException()
    {
        $categoryId = 12;
        $this->collectionFactory->expects($this->once())
            ->method('create')
            ->willThrowException(new LocalizedException(__('fail')));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error processing category ID 12: fail'), $this->arrayHasKey('exception'));

        $this->processor->process($categoryId);
    }
}
