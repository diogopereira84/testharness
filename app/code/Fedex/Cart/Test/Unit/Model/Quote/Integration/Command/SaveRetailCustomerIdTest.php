<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira <eoliveira@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\Integration\Command;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Model\Quote\Integration\Command\SaveRetailCustomerId;
use Magento\Framework\Exception\CouldNotSaveException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveRetailCustomerIdTest extends TestCase
{
    /**
     * @var SaveRetailCustomerId
     */
    private SaveRetailCustomerId $instance;

    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    private CartIntegrationRepositoryInterface|MockObject $cartIntegrationRepositoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    /**
     * @var CartIntegrationInterface|MockObject
     */
    private CartIntegrationInterface|MockObject $cartIntegrationMock;

    protected function setUp(): void
    {
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->instance = new SaveRetailCustomerId(
            $this->cartIntegrationRepositoryMock,
            $this->loggerMock
        );

        $this->cartIntegrationMock = $this->createMock(CartIntegrationInterface::class);
    }

    public function testExecuteEmptyRetailCustomerId(): void
    {
        $retailCustomerId = "";
        $this->cartIntegrationMock->expects(static::once())
            ->method('setRetailCustomerId')
            ->with(null)
            ->willReturnSelf();

        $this->cartIntegrationRepositoryMock->expects(static::once())
            ->method('save')
            ->with($this->cartIntegrationMock);

        $this->instance->execute($this->cartIntegrationMock, $retailCustomerId);
    }

    public function testExecuteEmptyRetailCustomerIdWithException(): void
    {
        $retailCustomerId = "123456";
        $this->cartIntegrationMock->expects(static::once())
            ->method('setRetailCustomerId')
            ->with($retailCustomerId)
            ->willReturnSelf();
        
        $exception = new CouldNotSaveException(__("Some Message"));
        $this->cartIntegrationRepositoryMock->expects(static::once())
            ->method('save')
            ->with($this->cartIntegrationMock)
            ->willThrowException($exception);
        
        $this->loggerMock->expects(static::once())
            ->method('error');

        $this->instance->execute($this->cartIntegrationMock, $retailCustomerId);
    }

    public function testExecute(): void
    {
        $retailCustomerId = "123456";
        $this->cartIntegrationMock->expects(static::once())
            ->method('setRetailCustomerId')
            ->with($retailCustomerId)
            ->willReturnSelf();

        $this->cartIntegrationRepositoryMock->expects(static::once())
            ->method('save')
            ->with($this->cartIntegrationMock);

        $this->instance->execute($this->cartIntegrationMock, $retailCustomerId);
    }
}
