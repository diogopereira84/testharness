<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\DeliveryInterface;
use Fedex\SubmitOrderSidebar\Api\Data\DeliveryInterfaceFactory;
use Fedex\SubmitOrderSidebar\Api\Data\LineItemInterface;
use Fedex\SubmitOrderSidebar\Api\Data\LineItemInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\ThirdPartyDeliveryDataSource;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ThirdPartyDeliveryDataSourceTest extends TestCase
{
    /**
     * @var (\Magento\Sales\Api\OrderRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderRepositoryMock;
    /**
     * @var (\Magento\Framework\Api\SearchCriteriaBuilder & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchCriteriaBuilderMock;
    protected $lineItemFactoryMock;
    protected $deliveryFactoryMock;
    /**
     * @var (\Magento\Framework\Pricing\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $pricingHelperMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    /**
     * @var ThirdPartyDeliveryDataSource
     */
    protected $thirdPartyDeliveryDataSource;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->lineItemFactoryMock = $this->createMock(LineItemInterfaceFactory::class);
        $this->deliveryFactoryMock = $this->createMock(DeliveryInterfaceFactory::class);
        $this->pricingHelperMock = $this->createMock(PricingHelper::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->deliveryFactoryMock->method('create')
            ->willReturn(
                $this->getMockBuilder(DeliveryInterface::class)
                    ->disableOriginalConstructor()->setMethods([
                        'toArray',
                    ])
                    ->getMockForAbstractClass()
            );
        $this->lineItemFactoryMock->method('create')
            ->willReturn(
                $this->getMockBuilder(LineItemInterface::class)
                    ->disableOriginalConstructor()->setMethods([
                        'toArray',
                    ])
                    ->getMockForAbstractClass()
            );

        $this->thirdPartyDeliveryDataSource = new ThirdPartyDeliveryDataSource(
            $this->orderRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->lineItemFactoryMock,
            $this->deliveryFactoryMock,
            $this->pricingHelperMock,
            $this->toggleConfigMock
        );

        $this->thirdPartyDeliveryDataSource->setShopId('your_shop_id');
    }

    protected function invokeFilterOrderItems(Item $item): bool
    {
        $reflection = new \ReflectionClass($this->thirdPartyDeliveryDataSource);
        $method = $reflection->getMethod('filterOrderItems');
        $method->setAccessible(true);
        return $method->invoke($this->thirdPartyDeliveryDataSource, $item);
    }

    protected function invokeGetProducerType(Item $item): string
    {
        $reflection = new \ReflectionClass($this->thirdPartyDeliveryDataSource);
        $method = $reflection->getMethod('getProducerType');
        $method->setAccessible(true);
        return $method->invoke($this->thirdPartyDeliveryDataSource, $item);
    }

    /**
     * Test filterOrderItems method.
     *
     * @return void
     */
    public function testFilterOrderItems(): void
    {
        $item1 = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getMiraklShopId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $item1->method('getMiraklShopId')->willReturn('your_shop_id');

        $item2 = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getMiraklShopId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $item2->method('getMiraklShopId')->willReturn('other_shop_id');

        $this->assertTrue($this->invokeFilterOrderItems($item1));
        $this->assertFalse($this->invokeFilterOrderItems($item2));
    }

    /**
     * Test getProducerType method.
     *
     * @return void
     */
    public function testGetProducerType(): void
    {
        $item = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'getMiraklShopName'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $item->method('getMiraklShopName')->willReturn('Shop Name');

        $producerType = $this->invokeGetProducerType($item);

        $this->assertEquals('Shop Name', $producerType);
    }
}
