<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\IntegrationNote\Command;

use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterfaceFactory;
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\Get;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetTest extends TestCase
{
    /**
     * @var Get
     */
    private Get $instance;

    /**
     * @var IntegrationNoteResourceModel|MockObject
     */
    private IntegrationNoteResourceModel|MockObject $integrationNoteResourceModelMock;

    /**
     * @var CartIntegrationNoteInterfaceFactory|MockObject
     */
    private CartIntegrationNoteInterfaceFactory|MockObject $cartIntegrationNoteFactoryMock;

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
        $this->instance = new Get(
            $this->integrationNoteResourceModelMock,
            $this->cartIntegrationNoteFactoryMock
        );

        $this->integrationNoteMock = $this->createMock(IntegrationNote::class);
    }

    public function testExecuteWithException(): void
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

    public function testExecute(): void
    {
        $quoteIntegrationNoteId = 1;

        $this->executeBasicTests($quoteIntegrationNoteId);
        $this->integrationNoteMock->expects(static::once())
            ->method('getId')
            ->willReturn($quoteIntegrationNoteId);

        $result = $this->instance->execute($quoteIntegrationNoteId);
        static::assertInstanceOf(CartIntegrationNoteInterface::class, $result);
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
