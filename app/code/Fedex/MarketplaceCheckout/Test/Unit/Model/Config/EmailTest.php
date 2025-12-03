<?php

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Config;

use Fedex\MarketplaceCheckout\Model\OrderStoreRetriever;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\MarketplaceCheckout\Model\Config\Email as EmailConfig;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Exception;

class EmailTest extends TestCase
{
    /** @var ScopeConfigInterface  */
    private ScopeConfigInterface $scopeConfig;

    /** @var StoreManagerInterface  */
    private StoreManagerInterface $storeManager;

    /** @var StoreInterface  */
    private StoreInterface $store;

    /** @var OrderRepositoryInterface  */
    private OrderRepositoryInterface $orderRepository;

    /** @var OrderStoreRetriever  */
    private OrderStoreRetriever $orderStoreRetriever;

    /** @var EmailConfig  */
    private EmailConfig $emailConfig;

    /** @var string[] */
    private array $templates = [
        'shipped'            => 'fedex/transactional_email/order_shipment_delivery',
        'delivered'          => 'fedex/transactional_email/order_shipment_delivery',
        'delivered_multiple' => 'fedex/transactional_email/order_shipment_multiple_delivery',
        'ready_for_pickup'   => 'fedex/transactional_email/order_ready_for_pickup',
        'cancelled'          => 'fedex/transactional_email/order_cancelled',
        'confirmed'          => 'fedex/transactional_email/order_confirmed',
    ];

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->store = $this->createMock(StoreInterface::class);
        $this->orderRepository     = $this->createMock(OrderRepositoryInterface::class);
        $this->orderStoreRetriever = new OrderStoreRetriever($this->orderRepository);

        $this->emailConfig = new EmailConfig(
            $this->storeManager,
            $this->scopeConfig,
            $this->orderStoreRetriever
        );
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetEmailEnabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn('1');
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->store->expects($this->once())
            ->method('getId')
            ->willReturn('1');

        $this->assertTrue($this->emailConfig->getEmailEnabled('confirmed'));
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetEmailEnabledDisabled()
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn('0');
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->store->expects($this->once())
            ->method('getId')
            ->willReturn('1');

        $this->assertFalse($this->emailConfig->getEmailEnabled('confirmed'));
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetEmailEnabledValueNotInArray()
    {
        $this->scopeConfig->expects($this->never())
            ->method('getValue')
            ->willReturn(null);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->store->expects($this->once())
            ->method('getId')
            ->willReturn('1');

        $this->assertFalse($this->emailConfig->getEmailEnabled('test'));
    }

    /**
     * Test that the email template correctly handles the presence of an order ID.
     * @return void
     */
    public function testTemplateWithOrderId(): void
    {
        $status   = 'confirmed';
        $orderId  = 42;
        $storeId  = 5;
        $path     = $this->templates[$status];
        $expected = 'foo_template';

        $orderMock = $this->createMock(OrderInterface::class);
        $orderMock
            ->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->orderRepository
            ->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn($expected);

        $this->assertSame($expected, $this->emailConfig->getEmailTemplate($status, $orderId));
    }

    /**
     * Test that the email template returns false when no order ID is provided.
     * @return void
     */
    public function testTemplateWithOrderIdWhenNull(): void
    {
        $status  = 'confirmed';
        $orderId = 42;
        $storeId = 5;
        $path    = $this->templates[$status];

        $orderMock = $this->createMock(OrderInterface::class);
        $orderMock
            ->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->orderRepository
            ->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn(null);

        $this->assertFalse($this->emailConfig->getEmailTemplate($status, $orderId));
    }

    /**
     * Test that the email template returns false when an exception occurs.
     * @return void
     */
    public function testTemplateWithOrderIdOnException(): void
    {
        $status  = 'confirmed';
        $orderId = 42;
        $storeId = 99;

        $orderMock = $this->createMock(OrderInterface::class);
        $orderMock->method('getStoreId')->willReturn($storeId);

        $this->orderRepository
            ->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->willThrowException(new Exception('Failed to retrieve email template'));

        $this->assertFalse($this->emailConfig->getEmailTemplate($status, $orderId));
    }

    /**
     * Test that the email template correctly retrieves the default store template.
     * @return void
     */
    public function testTemplateWithDefaultStore(): void
    {
        $status   = 'shipped';
        $default  = 1;
        $path     = $this->templates[$status];
        $expected = 'bar_template';

        $this->orderRepository->expects($this->never())->method('get');

        $this->storeManager
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->store
            ->expects($this->once())
            ->method('getId')
            ->willReturn($default);

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE, $default)
            ->willReturn($expected);

        $this->assertSame($expected, $this->emailConfig->getEmailTemplate($status));
    }

    /**
     * Test that the email template returns false when the default store template is null.
     * @return void
     */
    public function testTemplateWithDefaultStoreWhenNull(): void
    {
        $status  = 'shipped';
        $default = 1;
        $path    = $this->templates[$status];

        $this->orderRepository->expects($this->never())->method('get');

        $this->storeManager
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->store
            ->expects($this->once())
            ->method('getId')
            ->willReturn($default);

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE, $default)
            ->willReturn(null);

        $this->assertFalse($this->emailConfig->getEmailTemplate($status));
    }

    /**
     * Test that the email template returns false when an exception occurs while retrieving the default store template.
     * @return void
     */
    public function testTemplateWithDefaultStoreOnException(): void
    {
        $status  = 'shipped';
        $default = 1;

        $this->orderRepository->expects($this->never())->method('get');

        $this->storeManager
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->store
            ->expects($this->once())
            ->method('getId')
            ->willReturn($default);

        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->willThrowException(new Exception('Failed to retrieve email template'));

        $this->assertFalse($this->emailConfig->getEmailTemplate($status));
    }
}
