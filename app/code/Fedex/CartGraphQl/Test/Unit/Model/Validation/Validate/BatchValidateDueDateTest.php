<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateDueDate;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BatchValidateDueDateTest extends TestCase
{
    /**
     * @var ToggleConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $toggleConfig;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var BatchValidateDueDate
     */
    private $batchValidateDueDate;

    /**
     * Setup method to initialize mock objects.
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->batchValidateDueDate = new BatchValidateDueDate(
            $this->toggleConfig,
            $this->loggerMock
        );

        $this->toggleConfig->method('getToggleConfigValue')
            ->with(BatchValidateDueDate::TOGGLE_ORDER_DUE_DATES_FAILING)
            ->willReturn(false);
    }

    /**
     * Test validation with a future date (valid)
     *
     * @return void
     */
    public function testValidateWithFutureDate(): void
    {
        $futureDate = (new \DateTime())->modify('+1 day')->format('Y-m-d');
        $requestMock = $this->createResolverRequest('addOrUpdateDueDate', $futureDate);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);

        $this->loggerMock->expects($this->never())->method('error');

        $this->batchValidateDueDate->validate($requestCommandMock);
        $this->addToAssertionCount(1);
    }

    /**
     * Test validation with a past date (invalid)
     *
     * @return void
     */
    public function testValidateWithPastDateThrowsException(): void
    {
        $pastDate = (new \DateTime())->modify('-1 day')->format('Y-m-d');
        $requestMock = $this->createResolverRequest('addOrUpdateDueDate', $pastDate);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Due date should not be the past date.'));

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Due date should not be the past date.');

        $this->batchValidateDueDate->validate($requestCommandMock);
    }

    /**
     * Test that validation is skipped when the toggle is enabled
     *
     * @return void
     */
    public function testValidationSkippedWhenToggleEnabled(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(BatchValidateDueDate::TOGGLE_ORDER_DUE_DATES_FAILING)
            ->willReturn(true);

        $this->batchValidateDueDate = new BatchValidateDueDate(
            $this->toggleConfig,
            $this->loggerMock
        );

        $pastDate = (new \DateTime())->modify('-1 day')->format('Y-m-d');
        $requestMock = $this->createResolverRequest('addOrUpdateDueDate', $pastDate);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);

        $this->loggerMock->expects($this->never())->method('error');

        $this->batchValidateDueDate->validate($requestCommandMock);
    }

    /**
     * Test validation for a resolver not in the RESOLVERS list
     *
     * @return void
     */
    public function testValidateSkipsNonTargetedResolvers(): void
    {
        $pastDate = (new \DateTime())->modify('-1 day')->format('Y-m-d');
        $requestMock = $this->createResolverRequest('someOtherResolver', $pastDate);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);

        $this->loggerMock->expects($this->never())->method('error');

        $this->batchValidateDueDate->validate($requestCommandMock);
    }

    /**
     * Test the validateIsPastDate method directly
     *
     * @return void
     * @throws \Exception
     */
    public function testValidateIsPastDateMethod(): void
    {
        $futureDate = (new \DateTime())->modify('+1 day')->format('Y-m-d');
        $pastDate = (new \DateTime())->modify('-1 day')->format('Y-m-d');
        
        $reflectionMethod = new \ReflectionMethod(
            BatchValidateDueDate::class,
            'validateIsPastDate'
        );
        $reflectionMethod->setAccessible(true);

        $this->assertTrue(
            $reflectionMethod->invoke($this->batchValidateDueDate, $futureDate),
            'Future date should be valid'
        );

        $this->assertFalse(
            $reflectionMethod->invoke($this->batchValidateDueDate, $pastDate),
            'Past date should be invalid'
        );
    }

    /**
     * Helper method to create a resolver request mock with specific field name and due date
     *
     * @param string $fieldName The name of the resolver field
     * @param string $dueDate The due date value
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createResolverRequest(string $fieldName, string $dueDate)
    {
        $fieldMock = $this->createMock(Field::class);
        $fieldMock->method('getName')->willReturn($fieldName);

        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getField')->willReturn($fieldMock);
        $requestMock->method('getArgs')->willReturn([
            'input' => ['due_date' => $dueDate]
        ]);

        return $requestMock;
    }
}
