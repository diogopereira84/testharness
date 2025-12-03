<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\DataSourceInterface;
use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Model\OrderRepository;

class OrderDataSource implements DataSourceInterface
{
    /**
     * @param OrderRepository       $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PricingHelper         $pricingHelper
     */
    public function __construct(
        private readonly OrderRepository        $orderRepository,
        private readonly SearchCriteriaBuilder  $searchCriteriaBuilder,
        private readonly PricingHelper          $pricingHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function map(UnifiedDataLayerInterface $unifiedDataLayer, array $checkoutData = []): void
    {
        $orderNumber = '';

        if (isset($checkoutData[0])) {
            $checkoutData = json_decode($checkoutData[0], true);
            if (isset($checkoutData["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["origin"]["orderNumber"])) {
                $orderNumber = $checkoutData["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["origin"]["orderNumber"];
            }
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $orderNumber)->create();
        $order = $this->orderRepository->getList($searchCriteria)->getFirstItem();

        $unifiedDataLayer->setOrderNumber($orderNumber ?? '');
        $unifiedDataLayer->setOrderTotal(
            (string)$this->pricingHelper->currency(
                (string)$order->getGrandTotal(),
                true,
                false
            )
        );
        $unifiedDataLayer->setCurrency($order->getOrderCurrencyCode() ?? '');
        $unifiedDataLayer->setSite($order->getStore()->getCode() ?? '');
        $unifiedDataLayer->setPromoCode($order->getCouponCode() ?? '');
    }
}
