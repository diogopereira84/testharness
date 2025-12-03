<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Validation\Validate;

use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateModel;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Psr\Log\LoggerInterface;

class BatchValidateModelTest extends TestCase
{
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var BatchValidateModel
     */
    private $batchValidateModel;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->batchValidateModel = new BatchValidateModel($this->loggerMock);
    }

    public function testValidateWithValidModel(): void
    {
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getValue')->willReturn(['model' => 'some_model']);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);

        // No exception should be thrown
        $this->batchValidateModel->validate($requestCommandMock);
    }

    public function testValidateWithMissingModel(): void
    {
        $requestMock = $this->createMock(ResolveRequest::class);
        $requestMock->method('getValue')->willReturn([]);

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class);
        $requestCommandMock->method('getRequests')->willReturn([$requestMock]);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('"model" value should be specified.');

        $this->batchValidateModel->validate($requestCommandMock);
    }
}
