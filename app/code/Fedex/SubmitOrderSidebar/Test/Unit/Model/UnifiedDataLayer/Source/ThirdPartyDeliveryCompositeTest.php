<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\ThirdPartyDeliveryComposite;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\ThirdPartyDeliveryDataSource;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\DB\Select;

class ThirdPartyDeliveryCompositeTest extends TestCase
{
    /**
     * @var ThirdPartyDeliveryDataSource
     */
    protected $thirdPartyDeliveryDataSource;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var ThirdPartyDeliveryComposite
     */
    protected $thirdPartyDeliveryComposite;

    protected function setUp(): void
    {
        $this->thirdPartyDeliveryDataSource = $this->createMock(ThirdPartyDeliveryDataSource::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderItemCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(
                [
                    'addFieldToFilter',
                    'getIterator',
                    'getSelect',
                    'create',
                    'getTable'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->thirdPartyDeliveryComposite = new ThirdPartyDeliveryComposite(
            $this->thirdPartyDeliveryDataSource,
            $this->searchCriteriaBuilder,
            $this->orderRepository,
            $this->orderItemCollectionFactory
        );
    }

    /**
     * Test map() function.
     *
     * @return void
     */
    public function testMap(): void
    {
        $unifiedDataLayer = $this->createMock(UnifiedDataLayerInterface::class);
        $checkoutData = [
            json_encode([
                'output' => [
                    'checkout' => [
                        'lineItems' => [
                            [
                                'retailPrintOrderDetails' => [
                                    [
                                        'origin' => [
                                            'orderNumber' => '12345678'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ])
        ];

        $order = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->setMethods(
                [
                    'getId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchResults = $this->getMockBuilder(SearchResultsInterface::class)
            ->setMethods(['getFirstItem'])->disableOriginalConstructor()->getMockForAbstractClass();
        $orderItemCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(
                [
                    'addFieldToFilter',
                    'getIterator',
                    'getSelect',
                    'getTable'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $select = $this->createMock(Select::class);

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('increment_id', '12345678')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);

        $this->orderRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getId')
            ->willReturn(42);

        $this->orderItemCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($orderItemCollection);

        $orderItemCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('order_id', 42)
            ->willReturnSelf();

        $orderItemCollection->expects($this->any())
            ->method('getTable')
            ->willReturn('mirakl_shop');

        $orderItem1 = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderItemInterface::class)
            ->setMethods(['getMiraklShopId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $orderItem1->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop123');

        $orderItem2 = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderItemInterface::class)
            ->setMethods(['getMiraklShopId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $orderItem2->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop1234');

        $orderItemCollection->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$orderItem1, $orderItem2]));

        $orderItemCollection->expects($this->once())
            ->method('getSelect')
            ->willReturn($select);

        $select->expects($this->once())
            ->method('join')
            ->with(
                ['miraklShopTable' => $orderItemCollection->getTable('mirakl_shop')],
                'main_table.mirakl_shop_id = miraklShopTable.id',
                ['name' => 'miraklShopTable.name']
            )
            ->willReturnSelf();

        $this->thirdPartyDeliveryDataSource->expects($this->any())
            ->method('setShopId')
            ->withConsecutive([$orderItem1->getMiraklShopId()], [$orderItem2->getMiraklShopId()]);
        $this->thirdPartyDeliveryDataSource->expects($this->any())
            ->method('map')
            ->with($unifiedDataLayer, $checkoutData);

        $this->thirdPartyDeliveryComposite->map($unifiedDataLayer, $checkoutData);
    }
}
