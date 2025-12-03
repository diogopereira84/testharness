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
use Fedex\SubmitOrderSidebar\Api\Data\LineItemInterfaceFactory;
use Fedex\SubmitOrderSidebar\Api\Data\DeliveryInterfaceFactory;
use Fedex\SubmitOrderSidebar\Api\Data\UnifiedDataLayerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Item;

abstract class DeliveryDataSource implements DataSourceInterface
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LineItemInterfaceFactory $lineItemFactory
     * @param DeliveryInterfaceFactory $deliveryFactory
     * @param PricingHelper $pricingHelper
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder    $searchCriteriaBuilder,
        private readonly LineItemInterfaceFactory $lineItemFactory,
        private readonly DeliveryInterfaceFactory $deliveryFactory,
        private readonly PricingHelper            $pricingHelper,
        private readonly ToggleConfig             $toggleConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function map(UnifiedDataLayerInterface $unifiedDataLayer, array $checkoutData = []): void
    {
        $orderNumber = '';
        $shippingPrice = '0';

        if (isset($checkoutData[0])) {
            $checkoutData = json_decode($checkoutData[0], true);
            if (isset($checkoutData["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["origin"]["orderNumber"])) {
                $orderNumber = $checkoutData["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["origin"]["orderNumber"];
            }
            if (isset($checkoutData["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["deliveryLines"][0]["deliveryRetailPrice"])) {
                $shippingPrice = $checkoutData["output"]["checkout"]["lineItems"]
                [0]["retailPrintOrderDetails"][0]["deliveryLines"][0]["deliveryRetailPrice"];
            }
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $orderNumber)->create();
        $order = $this->orderRepository->getList($searchCriteria)->getFirstItem();
        $orderItems = array_filter($order->getAllItems(), function ($item) {
            return $this->filterOrderItems($item);
        });

        if (count($orderItems) > 0) {
            $delivery = $this->deliveryFactory->create();
            $delivery->setShipmentId((string)(count($unifiedDataLayer->getDeliveries())+1));
            $delivery->setProducedBy($this->getProducerType(current($orderItems)));
            $delivery->setDeliveryMethod(
                ($order->getShippingMethod() == 'fedexshipping_PICKUP')
                    ? 'PICKUP'
                    : 'SHIPPING'
            );
            $lineItems = [];
            foreach ($orderItems as $item) {
                $lineItem = $this->lineItemFactory->create();
                $lineItem->setProductName($item->getName() ?? '');
                $lineItem->setSkuId($item->getSku() ?? '');
                $lineItem->setQuantity((string)number_format((float)$item->getQtyOrdered(), 2, '.', ''));
                $lineItem->setPrice(
                    (string)$this->pricingHelper->currency(
                        $item->getRowTotal() - $item->getDiscountAmount(),
                        true,
                        false
                    )
                );
                if($this->toggleConfig->getToggleConfigValue('tigers_d187746')){
                    if ($item->getData('mirakl_offer_id')) {
                        $delivery->setDeliveryMethod('SHIPPING');
                    }
                    if ($delivery->getDeliveryMethod() == 'SHIPPING') {
                        if ($item->getData('mirakl_offer_id')) {
                            $shippingPrice = $this->getSellerShippingFee($item);
                        } elseif ($order->getData('shipping_incl_tax') > 0) {
                            $shippingPrice = (string)$order->getData('shipping_incl_tax');
                        }
                    }
                    if ($delivery->getDeliveryMethod() == 'PICKUP') {
                        $shippingPrice = '0.0';
                    }
                    $delivery->setPrice(
                        (string)$this->pricingHelper->currency(
                            $shippingPrice,
                            true,
                            false
                        )
                    );
                }
                $lineItems[] = $lineItem->toArray();
            }
            if(!$this->toggleConfig->getToggleConfigValue('tigers_d187746')){
                if ($order->getShippingInclTax() > 0) {
                    $shippingPrice = (string)$order->getShippingInclTax();
                }

                $delivery->setPrice(
                    (string)$this->pricingHelper->currency(
                        $shippingPrice,
                        true,
                        false
                    )
                );
            }
            $delivery->setLineItems($lineItems);
            if ($this->toggleConfig->getToggleConfigValue('tigers_d187746')) {
                $deliveriesSaved = $unifiedDataLayer->getDeliveries();
                $deliveryCurrent = $delivery->toArray();
                $isDeliverySet = false;
                foreach ($deliveriesSaved as $deliverySaved) {
                    if ($deliverySaved['producedBy'] == $deliveryCurrent['producedBy']) {
                        $isDeliverySet = true;
                    }
                }
                if (!$isDeliverySet) {
                    $allDeliveries = array_merge_recursive(
                        $deliveriesSaved,
                        array_values([$deliveryCurrent])
                    );
                    $unifiedDataLayer->setDeliveries($allDeliveries);
                }
            } else {
                $unifiedDataLayer->setDeliveries(array_merge_recursive(
                    $unifiedDataLayer->getDeliveries(),
                    array_values([$delivery->toArray()])
                ));
            }
        }
    }

    private function getSellerShippingFee(Item $sellerOrderItem): string
    {
        $shippingFee = '0.0';
        if ($sellerOrderItem->getAdditionalData()) {
            $additionalData = json_decode($sellerOrderItem->getAdditionalData(), true);
            $shippingFee = (string)$additionalData['mirakl_shipping_data']['amount'] ?? '0.0';
        }
        return $shippingFee;
    }

    /**
     * Filter order items
     *
     * @param Item $item
     *
     * @return bool
     */
    abstract protected function filterOrderItems(Item $item): bool;

    /**
     * Get producer type
     *
     * @param Item $item
     *
     * @return string
     */
    abstract protected function getProducerType(Item $item): string;
}
