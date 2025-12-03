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
use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterfaceFactory;
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\DeleteById;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteByIdTest extends TestCase
{
    /**
     * @var DeleteById
     */
    private DeleteById $instance;

    /**
     * @var IntegrationNoteResourceModel|MockObject
     */
    private IntegrationNoteResourceModel|MockObject $integrationNoteResourceModelMock;

    /**
     * @var CartIntegrationNoteInterfaceFactory|MockObject
     */
    private CartIntegrationNoteInterfaceFactory|MockObject $cartIntegrationNoteFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

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
        $this->cartIntegrationNoteFactoryMock = $this->createMock(CartIntegrationNoteInterfaceFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->instance = new DeleteById(
            $this->integrationNoteResourceModelMock,
            $this->cartIntegrationNoteFactoryMock,
            $this->loggerMock
        );

        $this->integrationNoteMock = $this->createMock(IntegrationNote::class);
    }

    public function testExecuteQuoteIntegrationNoteNotExists(): void
    {
        $quoteIntegrationNoteId = 1;

        $this->executeBasicTests($quoteIntegrationNoteId);

        $this->integrationNoteMock->expects(static::once())
            ->method('getId')
            ->willReturn(null);
        
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(
            'Quote integration note with id "' . $quoteIntegrationNoteId . '" does not exist.'
        );

        $this->instance->execute($quoteIntegrationNoteId);
    }

    public function testExecuteQuoteIntegrationNoteFailDelete(): void
    {
        $quoteIntegrationNoteId = 1;

        $this->executeBasicTests($quoteIntegrationNoteId);

        $this->integrationNoteMock->expects(static::once())
            ->method('getId')
            ->willReturn($quoteIntegrationNoteId);
        
        $exception = new Exception('Generic Error');
        $this->integrationNoteResourceModelMock->expects(static::once())
            ->method('delete')
            ->with($this->integrationNoteMock)
            ->willThrowException($exception);
          
        $this->loggerMock->expects(static::once())
            ->method('error')
            ->with('Generic Error');
        
        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not delete quote integration note.');

        $this->instance->execute($quoteIntegrationNoteId);
    }

    public function testExecute(): void
    {
        $quoteIntegrationNoteId = 1;

        $this->executeBasicTests($quoteIntegrationNoteId);

        $this->integrationNoteMock->expects(static::once())
            ->method('getId')
            ->willReturn($quoteIntegrationNoteId);
        
        $this->integrationNoteResourceModelMock->expects(static::once())
            ->method('delete')
            ->with($this->integrationNoteMock);

        $this->instance->execute($quoteIntegrationNoteId);
    }

    private function executeBasicTests(int $quoteIntegrationNoteId): void
    {
        $this->cartIntegrationNoteFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($this->integrationNoteMock);
      
        $this->integrationNoteResourceModelMock->expects(static::once())
            ->method('load')
            ->with(
                $this->integrationNoteMock,
                $quoteIntegrationNoteId,
                CartIntegrationNoteInterface::QUOTE_INTEGRATION_NOTE_ID
            );
    }
}
