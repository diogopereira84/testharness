<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Service;

use Fedex\Ondemand\Api\Data\ConfigInterface;
use Fedex\SaaSCommon\Model\Service\ProductProcessor;
use Fedex\SaaSCommon\Model\Service\AllowedCustomerGroupsService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class ProductProcessorTest extends TestCase
{
    private $logger;
    private $productRepository;
    private $allowedCustomerGroupsService;
    private $ondemandConfig;
    private $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->allowedCustomerGroupsService = $this->createMock(AllowedCustomerGroupsService::class);
        $this->ondemandConfig = $this->createMock(ConfigInterface::class);

        $this->processor = new ProductProcessor(
            $this->logger,
            $this->productRepository,
            $this->allowedCustomerGroupsService,
            $this->ondemandConfig
        );
    }

    public function testProcessReturnsEarlyIfAlreadyUpdated()
    {
        // Set the updatedProductIds property using reflection
        $reflection = new \ReflectionClass($this->processor);
        $property = $reflection->getProperty('updatedProductIds');
        $property->setAccessible(true);
        $property->setValue($this->processor, [1 => true]);

        $this->productRepository->expects($this->never())->method('getById');
        $this->processor->process(1);
    }

    public function testProcessUpdatesProductIfRequired()
    {
        $productId = 2;
        $product = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCategoryIds', 'getData'])
            ->getMock();

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId, false, 0, true)
            ->willReturn($product);

        $product->expects($this->once())->method('getCategoryIds')->willReturn([10, 20]);
        $product->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('old_value');

        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('getAllowedCustomerGroupsFromCategories')
            ->with([10, 20])
            ->willReturn(['x']);

        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('updateAttributes')
            ->with([$productId], 'x');

        $this->processor->process($productId);

        // Check updatedProductIds is set
        $reflection = new \ReflectionClass($this->processor);
        $property = $reflection->getProperty('updatedProductIds');
        $property->setAccessible(true);
        $updated = $property->getValue($this->processor);
        $this->assertArrayHasKey($productId, $updated);
        $this->assertTrue($updated[$productId]);
    }

    public function testProcessUpdatesProductIfRequiredWithAllCustomerGroups()
    {
        $productId = 2;
        $product = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCategoryIds', 'getData'])
            ->getMock();

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with($productId, false, 0, true)
            ->willReturn($product);

        $product->expects($this->once())->method('getCategoryIds')->willReturn([10, 20]);
        $product->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('old_value');

        $this->ondemandConfig->expects($this->once())
            ->method('getB2bPrintProductsCategory')
            ->willReturn(20);

        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('updateAttributes')
            ->with([$productId], '-1');

        $this->processor->process($productId);

        // Check updatedProductIds is set
        $reflection = new \ReflectionClass($this->processor);
        $property = $reflection->getProperty('updatedProductIds');
        $property->setAccessible(true);
        $updated = $property->getValue($this->processor);
        $this->assertArrayHasKey($productId, $updated);
        $this->assertTrue($updated[$productId]);
    }

    public function testProcessSkipsUpdateIfAlreadySet()
    {
        $productId = 3;
        $product = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCategoryIds', 'getData'])
            ->getMock();

        $this->productRepository->expects($this->once())
            ->method('getById')
            ->willReturn($product);

        $product->expects($this->once())->method('getCategoryIds')->willReturn([30]);
        $product->expects($this->once())->method('getData')->with('allowed_customer_groups')->willReturn('y');

        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('getAllowedCustomerGroupsFromCategories')
            ->with([30])
            ->willReturn(['y']);

        $this->allowedCustomerGroupsService->expects($this->never())
            ->method('updateAttributes');

        $this->processor->process($productId);

        $reflection = new \ReflectionClass($this->processor);
        $property = $reflection->getProperty('updatedProductIds');
        $property->setAccessible(true);
        $updated = $property->getValue($this->processor);
        $this->assertArrayHasKey($productId, $updated);
        $this->assertTrue($updated[$productId]);
    }

    public function testProcessLogsException()
    {
        $productId = 4;
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->willThrowException(new LocalizedException(__('fail')));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error processing product ID 4: fail'), $this->arrayHasKey('exception'));

        $this->processor->process($productId);
    }
}
