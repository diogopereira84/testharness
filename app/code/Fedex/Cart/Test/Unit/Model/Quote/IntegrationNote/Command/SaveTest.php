<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\IntegrationNote\Command;

use Exception;
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\Save;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Magento\Framework\Exception\CouldNotSaveException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    /**
     * @var Save
     */
    private Save $instance;

    /**
     * @var IntegrationNoteResourceModel|MockObject
     */
    private IntegrationNoteResourceModel|MockObject $integrationNoteResourceModelMock;

    /**
     * @var IntegrationNote|MockObject
     */
    private IntegrationNote|MockObject $integrationNoteMock;

    /**
     * Set Up
     */
    protected function setUp(): void
    {
        $this->integrationNoteResourceModelMock = $this->createMock(IntegrationNoteResourceModel::class);
        $this->instance = new Save($this->integrationNoteResourceModelMock);
        $this->integrationNoteMock = $this->createMock(IntegrationNote::class);
    }

    public function testExecuteWithException(): void
    {
        $exception = new Exception('Generic Error.');
        $this->integrationNoteResourceModelMock->expects(static::once())
            ->method('save')
            ->with($this->integrationNoteMock)
            ->willThrowException($exception);

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage(
            'Generic Error.'
        );

        $this->instance->execute($this->integrationNoteMock);
    }

    public function testExecute(): void
    {
        $cartIntegrationNoteId = 1;
        $this->integrationNoteResourceModelMock->expects(static::once())
            ->method('save')
            ->with($this->integrationNoteMock);
        
        $this->integrationNoteMock->expects(static::once())
            ->method('getId')
            ->willReturn($cartIntegrationNoteId);
        
        $result = $this->instance->execute($this->integrationNoteMock);
        static::assertSame($result, $cartIntegrationNoteId);
    }
}
