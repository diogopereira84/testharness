<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\UnifiedDataLayer\Source;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Data\Collection;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\OrderRepository;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\OrderDataSource;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

class OrderDataSourceTest extends TestCase
{
    protected $checkoutData;
    protected $unifiedDataLayerMock;
    protected $searchCriteriaBuilderMock;
    protected $pricingHelperMock;
    protected $orderCollectionMock;
    protected $orderMock;
    protected $storeMock;
    /**
     * @var (\Magento\Store\Model\Website & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $websiteMock;
    protected $orderRepositoryMock;
    protected $orderDataSource;
    private const GRAND_TOTAL_METHOD = 'getGrandTotal';
    private const GRAND_TOTAL_VALUE = '100.00';
    private const ORDER_NUMBER_METHOD = 'setOrderNumber';
    private const ORDER_NUMBER_VALUE = '100000001';
    private const ORDER_CURRENCY_CODE_METHOD = 'getOrderCurrencyCode';
    private const ORDER_CURRENCY_CODE_VALUE = 'USD';
    private const STORE_CODE_METHOD = 'getCode';
    private const STORE_CODE_VALUE = 'Store Name';
    private const COUPON_CODE_METHOD = 'getCouponCode';
    private const COUPON_CODE_VALUE = 'some-coupon-code';
    private const ORDER_TOTAL_METHOD = 'setOrderTotal';
    private const CURRENCY_METHOD = 'setCurrency';
    private const SITE_METHOD = 'setSite';
    private const PROMO_CODE_METHOD = 'setPromoCode';

    protected function setUp(): void
    {
        $this->checkoutData = [
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
        $this->unifiedDataLayerMock = $this->createMock(UnifiedDataLayerInterface::class);
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->pricingHelperMock = $this->createMock(PricingHelper::class);
        $this->pricingHelperMock->expects($this->once())->method('currency')->willReturn(self::GRAND_TOTAL_VALUE);
        $this->orderCollectionMock = $this->createMock(Collection::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->websiteMock = $this->createMock(Website::class);
        $this->orderMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method(self::STORE_CODE_METHOD)
            ->willReturn(self::STORE_CODE_VALUE);
        $this->orderRepositoryMock = $this->createMock(OrderRepository::class);
        $this->orderDataSource = new OrderDataSource(
            $this->orderRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->pricingHelperMock
        );

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('increment_id', self::ORDER_NUMBER_VALUE)
            ->willReturn($this->searchCriteriaBuilderMock);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $this->orderRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->orderMock);
    }

    public function testMap()
    {
        $this->orderMock->expects($this->once())
            ->method(self::GRAND_TOTAL_METHOD)
            ->willReturn(self::GRAND_TOTAL_VALUE);
        $this->orderMock->expects($this->once())
            ->method(self::ORDER_CURRENCY_CODE_METHOD)
            ->willReturn(self::ORDER_CURRENCY_CODE_VALUE);
        $this->orderMock->expects($this->once())
            ->method(self::COUPON_CODE_METHOD)
            ->willReturn(self::COUPON_CODE_VALUE);
        $this->unifiedDataLayerMock->expects($this->once())
            ->method(self::ORDER_NUMBER_METHOD)
            ->with(self::ORDER_NUMBER_VALUE);
        $this->unifiedDataLayerMock->expects($this->once())
            ->method(self::ORDER_TOTAL_METHOD)
            ->with(self::GRAND_TOTAL_VALUE);
        $this->unifiedDataLayerMock->expects($this->once())
            ->method(self::CURRENCY_METHOD)
            ->with(self::ORDER_CURRENCY_CODE_VALUE);
        $this->unifiedDataLayerMock->expects($this->once())
            ->method(self::SITE_METHOD)
            ->with(self::STORE_CODE_VALUE);
        $this->unifiedDataLayerMock->expects($this->once())
            ->method(self::PROMO_CODE_METHOD)
            ->with(self::COUPON_CODE_VALUE);

        $this->orderDataSource->map($this->unifiedDataLayerMock, $this->checkoutData);
    }
}
