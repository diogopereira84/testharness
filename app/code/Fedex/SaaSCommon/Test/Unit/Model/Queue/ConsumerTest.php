<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Queue;

use Fedex\SaaSCommon\Model\Queue\Consumer;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Fedex\SaaSCommon\Model\Service\ProductProcessor;
use Fedex\SaaSCommon\Model\Service\CategoryProcessor;
use Fedex\SaaSCommon\Model\Service\CustomerGroupProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Magento\Customer\Model\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConsumerTest extends TestCase
{
    private $logger;
    private $productProcessor;
    private $categoryProcessor;
    private $customerGroupProcessor;
    private $consumer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->productProcessor = $this->createMock(ProductProcessor::class);
        $this->categoryProcessor = $this->createMock(CategoryProcessor::class);
        $this->customerGroupProcessor = $this->createMock(CustomerGroupProcessor::class);

        $this->consumer = new Consumer(
            $this->logger,
            $this->productProcessor,
            $this->categoryProcessor,
            $this->customerGroupProcessor
        );
    }

    public function testProcessReturnsEarlyIfEntityIdIsNull()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(null);
        $request->method('getEntityType')->willReturn(Product::ENTITY);

        // None of the processors should be called
        $this->productProcessor->expects($this->never())->method('process');
        $this->categoryProcessor->expects($this->never())->method('process');
        $this->customerGroupProcessor->expects($this->never())->method('process');

        $this->consumer->process($request);
    }

    public function testProcessReturnsEarlyIfEntityTypeIsNull()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(123);
        $request->method('getEntityType')->willReturn(null);

        $this->productProcessor->expects($this->never())->method('process');
        $this->categoryProcessor->expects($this->never())->method('process');
        $this->customerGroupProcessor->expects($this->never())->method('process');

        $this->consumer->process($request);
    }

    public function testProcessProductEntityCallsProductProcessor()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(10);
        $request->method('getEntityType')->willReturn(Product::ENTITY);

        $this->productProcessor->expects($this->once())->method('process')->with(10);
        $this->categoryProcessor->expects($this->never())->method('process');
        $this->customerGroupProcessor->expects($this->never())->method('process');

        $this->consumer->process($request);
    }

    public function testProcessCategoryEntityCallsCategoryProcessor()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(20);
        $request->method('getEntityType')->willReturn(Category::ENTITY);

        $this->productProcessor->expects($this->never())->method('process');
        $this->categoryProcessor->expects($this->once())->method('process')->with(20);
        $this->customerGroupProcessor->expects($this->never())->method('process');

        $this->consumer->process($request);
    }

    public function testProcessGroupEntityCallsCustomerGroupProcessor()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(30);
        $request->method('getEntityType')->willReturn(Group::ENTITY);

        $this->productProcessor->expects($this->never())->method('process');
        $this->categoryProcessor->expects($this->never())->method('process');
        $this->customerGroupProcessor->expects($this->once())->method('process')->with(30);

        $this->consumer->process($request);
    }

    public function testProcessHandlesExceptionAndLogsCritical()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(40);
        $request->method('getEntityType')->willReturn(Product::ENTITY);

        $exception = new \Exception('Test error');
        $this->productProcessor->expects($this->once())
            ->method('process')
            ->with(40)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                $this->stringContains('Error processing entity ID 40: Test error'),
                $this->arrayHasKey('exception')
            );

        $this->consumer->process($request);
    }

    public function testProcessWithUnknownEntityTypeDoesNothing()
    {
        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(50);
        $request->method('getEntityType')->willReturn('unknown_type');

        $this->productProcessor->expects($this->never())->method('process');
        $this->categoryProcessor->expects($this->never())->method('process');
        $this->customerGroupProcessor->expects($this->never())->method('process');
        $this->logger->expects($this->never())->method('critical');

        $this->consumer->process($request);
    }
}
