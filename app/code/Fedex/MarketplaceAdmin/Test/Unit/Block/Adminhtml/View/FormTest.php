<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Block\Adminhtml\View;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceAdmin\Block\Adminhtml\View\Form;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Sales\Model\Order;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use  Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface;

class FormTest extends TestCase
{
    protected $orderDetailsDataMapper;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $orderItemRepository;
    /**
     * @var Form
     */
    private $form;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var Admin
     */
    private $adminHelper;
    /**
     * @var CarrierFactory
     */
    private $carrierFactory;
    /**
     * @var ShippingHelper
     */
    private $shippingHelper;
    /**
     * @var TaxHelper
     */
    private $taxHelper;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var ConfigInterface
     */
    protected $instoreConfig;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
        $this->adminHelper = $this->createMock(Admin::class);
        $this->carrierFactory = $this->createMock(CarrierFactory::class);
        $this->shippingHelper = $this->createMock(ShippingHelper::class);
        $this->taxHelper = $this->createMock(TaxHelper::class);
        $this->orderDetailsDataMapper = $this->createMock(OrderDetailsDataMapper::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->orderItemRepository = $this->createMock(OrderItemRepositoryInterface::class);
        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->instoreConfig = $this->createMock(ConfigInterface::class);

        $this->form = new Form(
            $this->helper,
            $this->priceCurrency,
            $this->context,
            $this->registry,
            $this->adminHelper,
            $this->carrierFactory,
            $this->orderDetailsDataMapper,
            $this->toggleConfig,
            $this->orderItemRepository,
            $this->timezone,
            $this->instoreConfig,
            [],
            $this->shippingHelper,
            $this->taxHelper
        );
    }

    /**
     * Test Shipping description toggle disabled.
     *
     * @return void
     */
    public function testGetCustomShippingDescriptionToggleDisabled()
    {
        $order = $this->createMock(Order::class);

        $shipping = $this->getMockBuilder(\Magento\Sales\Model\Order\Shipment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingDueDate', 'getMiraklShippingReference'])
            ->getMock();

        $shipping->expects($this->any())
            ->method('getShippingDueDate')
            ->willReturn('2025-07-02 13:00:00');

        $order->expects($this->once())
            ->method('getShippingDescription')
            ->willReturn('Shipping Description');

        $result = $this->form->getCustomShippingDescription($order, $shipping);

        $this->assertEquals('Shipping Description', $result);
    }

    /**
     * Test Shipping description toggle enabled.
     *
     * @return void
     */
    public function testGetCustomShippingDescriptionToggleEnabled()
    {
        $order = $this->createMock(Order::class);

        $shipping = $this->getMockBuilder(\Magento\Sales\Model\Order\Shipment::class)
            ->setMethods(['getShippingDueDate', 'getMiraklShippingReference'])
            ->disableOriginalConstructor()
            ->getMock();

        $shipping->expects($this->any())
            ->method('getShippingDueDate')
            ->willReturn('2025-07-02 13:00:00');

        $shipping->expects($this->once())
            ->method('getMiraklShippingReference')
            ->willReturn(123);

        $this->helper->expects($this->once())
            ->method('getMktShipping')
            ->with($order)
            ->willReturn(
                [
                    'method_title' => 'Mirakl Shipping',
                    'deliveryDate' => 'Thursday, November 30, 12:20pm',
                ]
            );

        $result = $this->form->getCustomShippingDescription($order, $shipping);

        $this->assertEquals('Mirakl Shipping - Thursday, November 30, 12:20pm', $result);
    }

    /**
     * Test getMiraklShipping method.
     *
     * @return void
     */
    public function testGetMiraklShipping()
    {
        $order = $this->createMock(Order::class);

        $this->helper->expects($this->once())
            ->method('getMktShipping')
            ->with($order)
            ->willReturn(
                [
                    'method_title' => 'Mirakl Shipping',
                    'deliveryDate' => 'Thursday, November 30, 12:20pm',
                ]
            );

        $result = $this->form->getMiraklShipping($order);

        $this->assertEquals('Mirakl Shipping - Thursday, November 30, 12:20pm', $result);
    }

    /**
     * Test getExtendedDate method.
     *
     * @return void
     */
    public function testGetExtendedDate()
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $shipping = $this->createMock(\Magento\Sales\Model\Order\Shipment::class);
        $item = $this->getMockBuilder(\Magento\Sales\Model\Order\Shipment\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklOfferId', 'getOrderItemId'])
            ->getMock();
        $orderItem = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiraklOfferId', 'getOrderItemId'])
            ->getMock();

        $item->expects($this->once())
            ->method('getOrderItemId')
            ->willReturn(1);

        $order->expects($this->once())
            ->method('getIncrementId')
            ->willReturn('1');

        $orderItem->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(123);

        $shipping->expects($this->once())
            ->method('getItems')
            ->willReturn([$item]);

        $this->orderItemRepository->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($orderItem);

        $this->helper->expects($this->once())
            ->method('getMktShipping')
            ->willReturn(['seller_id' => 123]);

        $this->orderDetailsDataMapper->expects($this->once())
            ->method('getMiraklOrderValue')
            ->willReturn(['order_id' => 123]);

        $this->orderDetailsDataMapper->expects($this->once())
            ->method('getExtendedDeliveryDate')
            ->willReturn([
                'dayOfWeek' => 'Monday',
                'dateFormatted' => '2022-01-01',
                'timeFormatted' => '10:00 AM'
            ]);

        $result = $this->form->getExtendedDate($order, $shipping);

        $this->assertEquals('Monday, 2022-01-01, 10:00 AM', $result);
    }

    /**
     * Test IsOrderTrackingDeliveryDateUpdateEnable method.
     *
     * @return void
     */
    public function testIsOrderTrackingDeliveryDateUpdateEnable()
    {
        $this->orderDetailsDataMapper->expects($this->once())
            ->method('isOrderTrackingDeliveryDateUpdateEnable')
            ->willReturn(true);

        $result = $this->form->isOrderTrackingDeliveryDateUpdateEnable();

        $this->assertTrue($result);
    }

    /**
     * Test IsOrderTrackingDeliveryDateUpdateEnable false method.
     *
     * @return void
     */
    public function testIsOrderTrackingDeliveryDateUpdateEnableFalse()
    {
        $this->orderDetailsDataMapper->expects($this->once())
            ->method('isOrderTrackingDeliveryDateUpdateEnable')
            ->willReturn(false);

        $result = $this->form->isOrderTrackingDeliveryDateUpdateEnable();

        $this->assertFalse($result);
    }
}
