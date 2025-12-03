<?php

namespace Fedex\SubmitOrderSidebar\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Exception;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\SubmitOrderSidebar\Plugin\SubmitOrderEmailUpdate;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\Service\OrderService;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;

class SubmitOrderEmailUpdateTest extends TestCase
{
    protected $quote;
    protected $order;
    protected $orderService;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $submitOrderPlugin;
    /**
     * @var CartFactory
     */
    protected $cartFactory;

    /**
     * @var CheckoutSession $checkoutSession
     */
    protected $checkoutSession;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
        ->setMethods(['getAlternateContactAvailable'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
        ->setMethods(['create','getQuote'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
        ->setMethods(['getShippingAddress','getEmail','getData'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->order = $this->getMockBuilder(OrderCollection::class)
        ->setMethods(['getShippingAddress','setEmail'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->orderService = $this->getMockBuilder(\Magento\Sales\Model\Service\OrderService::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->submitOrderPlugin = $this->objectManager->getObject(
            SubmitOrderEmailUpdate::class,
            [
                'cartFactory' => $this->cartFactory,
                'checkoutSession' => $this->checkoutSession
            ]
        );
    }

    /**
     * Test case for beforePlace
     */
    public function testBeforePlace()
    {
        $this->checkoutSession->expects($this->any())->method('getAlternateContactAvailable')->willReturn(1);
        $this->cartFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('getEmail')->willReturn('ayush.sood@infogain.com');
        $this->order->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->order->expects($this->any())->method('setEmail')->willReturn('ayush.sood@infogain.com');
        $this->assertNotNull($this->submitOrderPlugin->beforePlace($this->orderService, $this->order));
    }
}
