<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\DeliveryInterface;
use Fedex\SubmitOrderSidebar\Api\Data\LineItemInterface;
use Magento\Sales\Model\Order;
use Fedex\SubmitOrderSidebar\Api\Data\DeliveryInterfaceFactory;
use Fedex\SubmitOrderSidebar\Api\Data\LineItemInterfaceFactory;
use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\FirstPartyDeliveryDataSource;
use Magento\Sales\Model\Order\Item;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class FirstPartyDeliveryDataSourceTest extends TestCase
{
    protected $pricingHelperMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var LineItemInterfaceFactory|MockObject
     */
    private $lineItemFactoryMock;

    /**
     * @var DeliveryInterfaceFactory|MockObject
     */
    private $deliveryFactoryMock;

    /**
     * @var FirstPartyDeliveryDataSource
     */
    private $firstPartyDeliveryDataSource;

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

        $this->firstPartyDeliveryDataSource = new FirstPartyDeliveryDataSource(
            $this->orderRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->lineItemFactoryMock,
            $this->deliveryFactoryMock,
            $this->pricingHelperMock,
            $this->toggleConfigMock
        );
    }

    public function testMap(): void
    {
        $unifiedDataLayerMock = $this->createMock(UnifiedDataLayerInterface::class);
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderMock = $this->createMock(Order::class);
        $orderItemMock = $this->createMock(Item::class);
        $this->pricingHelperMock->expects($this->any())->method('currency')->willReturn('100.00');

        $unifiedDataLayerMock->expects($this->atLeast(2))
            ->method('getDeliveries')
            ->willReturn([]);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('increment_id', '100000001')
            ->willReturn($this->searchCriteriaBuilderMock);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $this->orderRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$orderItemMock]);
        $checkoutData = [
            json_encode([
                'output' => [
                    'checkout' => [
                        'lineItems' => [
                            [
                                'retailPrintOrderDetails' => [
                                    [
                                        'origin' => [
                                            'orderNumber' => '100000001',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ];

        $this->firstPartyDeliveryDataSource->map($unifiedDataLayerMock, $checkoutData);
    }
}
