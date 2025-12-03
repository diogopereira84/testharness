<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\PlaceOrder;

use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\PlaceOrder\SaveQuoteIntegrationRetryData;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Psr\Log\LoggerInterface;

class SaveQuoteIntegrationRetryDataTest extends TestCase
{
    protected $cartIntegrationRepositoryMock;
    protected $cartIntegrationMock;
    protected $saveQuoteIntegrationRetryData;

    protected $logger;

    protected function setUp(): void
    {
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->cartIntegrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->saveQuoteIntegrationRetryData = new SaveQuoteIntegrationRetryData(
            $this->cartIntegrationRepositoryMock,
            $this->logger
        );
    }

    public function testExecute()
    {
        $quoteId = '4381';
        $transactionId = '6d1dd203-a1db-494b-a59b-e63c8e9a8042';

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willReturn($this->cartIntegrationMock);

        $this->cartIntegrationMock->expects($this->once())
            ->method('setRetryTransactionApi')
            ->with(true);

        $this->cartIntegrationMock->expects($this->once())
            ->method('setFjmpRateQuoteId')
            ->with($transactionId);

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->cartIntegrationMock);

        $this->saveQuoteIntegrationRetryData->execute($quoteId, $transactionId);
    }

    public function testExecuteWithNoSuchEntityException()
    {
        $quoteId = '4381';
        $transactionId = '6d1dd203-a1db-494b-a59b-e63c8e9a8042';

        $exception = new NoSuchEntityException(
            __('No such entity found with quote_id = %1', $quoteId)
        );

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willThrowException($exception);

        $this->cartIntegrationRepositoryMock->expects($this->never())
            ->method('save');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in Fetching Quote Integration:'));

        $this->saveQuoteIntegrationRetryData->execute($quoteId, $transactionId);
    }
}
