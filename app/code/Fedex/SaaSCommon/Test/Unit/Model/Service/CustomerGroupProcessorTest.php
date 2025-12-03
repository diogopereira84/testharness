<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Service;

use Fedex\SaaSCommon\Model\Service\CustomerGroupProcessor;
use Fedex\SaaSCommon\Model\Service\AllowedCustomerGroupsService;
use Fedex\SaaSCommon\Model\Service\CategoryProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class CustomerGroupProcessorTest extends TestCase
{
    private $logger;
    private $allowedCustomerGroupsService;
    private $categoryProcessor;
    private $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->allowedCustomerGroupsService = $this->createMock(AllowedCustomerGroupsService::class);
        $this->categoryProcessor = $this->createMock(CategoryProcessor::class);

        $this->processor = new CustomerGroupProcessor(
            $this->logger,
            $this->allowedCustomerGroupsService,
            $this->categoryProcessor
        );
    }

    public function testProcessReturnsEarlyOnEmptyCategoryIds()
    {
        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('getAllowedCategoriesFromCustomerGroup')
            ->with(1)
            ->willReturn([]);

        $this->categoryProcessor->expects($this->never())->method('process');
        $this->processor->process(1);
    }

    public function testProcessProcessesEachCategoryId()
    {
        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('getAllowedCategoriesFromCustomerGroup')
            ->with(2)
            ->willReturn([10, 20]);

        $this->categoryProcessor->expects($this->exactly(2))
            ->method('process')
            ->withConsecutive([10], [20]);

        $this->processor->process(2);
    }

    public function testProcessLogsException()
    {
        $this->allowedCustomerGroupsService->expects($this->once())
            ->method('getAllowedCategoriesFromCustomerGroup')
            ->with(3)
            ->willThrowException(new LocalizedException(__('fail')));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error processing customer group ID 3: fail'), $this->arrayHasKey('exception'));

        $this->processor->process(3);
    }
}

