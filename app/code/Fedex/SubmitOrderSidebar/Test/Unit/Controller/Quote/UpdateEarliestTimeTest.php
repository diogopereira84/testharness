<?php

namespace Fedex\SubmitOrderSidebar\Test\Unit\Controller\Quote;

use Fedex\SubmitOrderSidebar\Controller\Quote\UpdateEarliestTime;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\Delivery\Helper\Data;
use PHPUnit\Framework\TestCase;

class UpdateEarliestTimeTest extends TestCase
{
    private $controller;
    private $resultJsonFactory;
    private $resultJson;
    private $checkoutSession;
    private $quoteRepository;
    private $request;
    private $cartFactory;
    private $quote;
    private $helper;

    protected function setUp(): void
    {
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->resultJson = $this->createMock(Json::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->quoteRepository = $this->createMock(CartRepositoryInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->cartFactory = $this->createMock(CartFactory::class);
        $this->quote = $this->createMock(Quote::class);
        $this->helper = $this->createMock(Data::class);

        $this->helper->method('isPromiseTimeWarningtoggleEnabled')->willReturn(true);

        $cartMock = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $cartMock->method('getQuote')->willReturn($this->quote);
        $this->cartFactory->method('create')->willReturn($cartMock);

        // Ensure JsonFactory returns a valid Json object
        $this->resultJsonFactory->method('create')->willReturn($this->resultJson);
        $this->resultJson->method('setData')->willReturnSelf();

        $this->controller = new UpdateEarliestTime(
            $this->resultJsonFactory,
            $this->checkoutSession,
            $this->quoteRepository,
            $this->createMock(LoggerInterface::class),
            $this->request,
            $this->helper,
            $this->cartFactory
        );
    }

    public function testExecuteSuccess()
    {
        $pickupTime = '2025-03-19 14:00:00';
        $this->request->method('getParam')->with('data')->willReturn($pickupTime);
        $this->quote->expects($this->once())->method('setData')->with('estimated_pickup_time', $pickupTime);
        $this->quoteRepository->expects($this->once())->method('save')->with($this->quote);
        
        $result = $this->controller->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteWithInvalidPickupTime()
    {
        $this->request->method('getParam')->with('data')->willReturn(null);
        $this->quoteRepository->expects($this->never())->method('save');

        $result = $this->controller->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteWithException()
    {
        $pickupTime = '2025-03-19 14:00:00';
        $this->request->method('getParam')->with('data')->willReturn($pickupTime);
        $this->quote->method('setData')->will($this->throwException(new \Exception('Test Exception')));

        $result = $this->controller->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
}
