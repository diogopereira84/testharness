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
use Fedex\CartGraphQl\Model\PlaceOrder\SubmitOrder;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Fedex\CartGraphQl\Model\PlaceOrder\RequestData;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Psr\Log\LoggerInterface;

class SubmitOrderTest extends TestCase
{
    protected $requestDataMock;
    protected $cartIntegrationRepositoryMock;
    protected $orderFactoryMock;
    protected $submitOrderBuilderMock;
    protected $submitOrder;
    protected $quoteMock;
    protected $cartIntegrationMock;
    protected $orderMock;
    protected $fuseBidViewModel;

    protected $logger;

    protected function setUp(): void
    {
        $this->requestDataMock = $this->createMock(RequestData::class);
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->submitOrderBuilderMock = $this->createMock(SubmitOrderBuilder::class);
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->setMethods(['isFuseBidToggleEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->submitOrder = new SubmitOrder(
            $this->requestDataMock,
            $this->cartIntegrationRepositoryMock,
            $this->orderFactoryMock,
            $this->submitOrderBuilderMock,
            $this->fuseBidViewModel,
            $this->logger
        );

        $this->quoteMock = $this->getMockBuilder(Quote::class)
        ->setMethods(['getId', 'getIsBid', 'getReservedOrderId'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->cartIntegrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->orderMock = $this->createMock(Order::class);
    }

    public function testExecuteWithRetryTransaction()
    {
        $quoteId = '4381';
        $note = ['Test Note'];
        $reservedOrderId = '2020205626047905';
        $fjmpRateQuoteId = '6d1dd203-a1db-494b-a59b-e63c8e9a8042';

        $requestData = (object)['pickupData' => true];

        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->quoteMock->expects($this->once())
            ->method('getReservedOrderId')
            ->willReturn($reservedOrderId);

        $this->requestDataMock->expects($this->once())
            ->method('build')
            ->with($this->quoteMock, $note)
            ->willReturn($requestData);

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willReturn($this->cartIntegrationMock);

        $this->cartIntegrationMock->expects($this->once())
            ->method('getRetryTransactionApi')
            ->willReturn(true);

        $this->cartIntegrationMock->expects($this->once())
            ->method('getFjmpRateQuoteId')
            ->willReturn($fjmpRateQuoteId);

        $this->orderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('loadByIncrementId')
            ->with($reservedOrderId)
            ->willReturnSelf();

        $response = ['rateQuoteResponse' => []];

        $this->submitOrderBuilderMock->expects($this->once())
            ->method('instoreBuildRetryTransaction')
            ->with($this->orderMock, $this->quoteMock, $requestData)
            ->willReturn($response);

        $expectedResponse = $response;
        $expectedResponse['rateQuoteResponse']['transactionId'] = $fjmpRateQuoteId;

        $this->assertEquals($expectedResponse, $this->submitOrder->execute($this->quoteMock, $note));
    }

    public function testExecuteWithoutRetryTransaction()
    {
        $quoteId = '4381';

        $requestData = (object)['pickupData' => true];

        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->requestDataMock->expects($this->once())
            ->method('build')
            ->with($this->quoteMock, null)
            ->willReturn($requestData);

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willReturn($this->cartIntegrationMock);

        $this->cartIntegrationMock->expects($this->once())
            ->method('getRetryTransactionApi')
            ->willReturn(false);

        $response = ['rateQuoteResponse' => []];

        $this->submitOrderBuilderMock->expects($this->once())
            ->method('build')
            ->with($requestData, true)
            ->willReturn($response);

        $this->fuseBidViewModel->expects($this->any())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);

        $this->quoteMock->expects($this->any())
            ->method('getIsBid')
            ->willReturn(true);

        $this->assertEquals($response, $this->submitOrder->execute($this->quoteMock, null));
    }

    public function testExecuteWithNoSuchEntityException()
    {
        $quoteId = '4381';
        $note = ['Test Note'];
        $requestData = (object)['pickupData' => true];

        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->requestDataMock->expects($this->once())
            ->method('build')
            ->with($this->quoteMock, $note)
            ->willReturn($requestData);

        $exception = new NoSuchEntityException(
            __('No such entity found with quote_id = %1', $quoteId)
        );
        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in Fetching Quote Integration:'));

        $this->fuseBidViewModel->expects($this->once())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('getIsBid')
            ->willReturn(true);

        $expectedResponse = ['rateQuoteResponse' => ['status' => 'success']];
        $this->submitOrderBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                $requestData,
                true,
                false,
                $this->quoteMock
            )
            ->willReturn($expectedResponse);

        $result = $this->submitOrder->execute($this->quoteMock, $note);
        $this->assertEquals($expectedResponse, $result);
    }
}
