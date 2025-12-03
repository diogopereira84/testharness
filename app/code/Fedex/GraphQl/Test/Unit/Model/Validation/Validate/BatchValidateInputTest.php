<?php
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\GraphQl\Model\Validation\Validate\BatchValidateInput;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class BatchValidateInputTest extends TestCase
{
    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var BatchValidateInput */
    private $batchValidateInput;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->batchValidateInput = new BatchValidateInput($this->loggerMock);
    }

    public function testValidateThrowsExceptionForInvalidInput(): void
    {
        // Create mocks for required classes
        $fieldMock = $this->createMock(\Magento\Framework\GraphQl\Config\Element\Field::class);
        $fieldMock->expects($this->once())
            ->method('getName')
            ->willReturn('createOrUpdateOrder');

        $requestMock = $this->createMock(\Magento\Framework\GraphQl\Query\Resolver\ResolveRequest::class);
        $requestMock->expects($this->once())
            ->method('getField')
            ->willReturn($fieldMock);

        $requestMock->expects($this->once())
            ->method('getArgs')
            ->willReturn(['input' => null]);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->expects($this->once())
            ->method('getRequests')
            ->willReturn([$requestMock]);

        // Expect logger info
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('"input" value should be specified'));

        // Expect exception
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('"input" value should be specified');

        // Invoke validate
        $this->batchValidateInput->validate($requestCommandMock);
    }

    public function testValidatePassesWithValidInput(): void
    {
        // Create mocks for required classes
        $fieldMock = $this->createMock(\Magento\Framework\GraphQl\Config\Element\Field::class);
        $fieldMock->expects($this->once())
            ->method('getName')
            ->willReturn('createOrUpdateOrder');

        $requestMock = $this->createMock(\Magento\Framework\GraphQl\Query\Resolver\ResolveRequest::class);
        $requestMock->expects($this->once())
            ->method('getField')
            ->willReturn($fieldMock);

        $requestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn(['input' => ['key' => 'value']]);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->expects($this->once())
            ->method('getRequests')
            ->willReturn([$requestMock]);

        // Expect no logger info
        $this->loggerMock->expects($this->never())
            ->method('info');

        // Invoke validate without exceptions
        $this->batchValidateInput->validate($requestCommandMock);
        $this->assertTrue(true); // Ensure the test completes successfully
    }
}
