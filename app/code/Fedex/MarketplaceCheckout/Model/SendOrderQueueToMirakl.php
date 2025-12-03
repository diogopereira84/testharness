<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceRates\Helper\Data;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\Connector\Helper\Order as MiraklOrder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;

class SendOrderQueueToMirakl
{
    /** @var string */
    private const SHIPPING_RATE_OPTION_KEY = 'shipping_rate_option';

    /** @var string */
    private const MIRAKL_SHIPPING_RATES_VALUE = 'mirakl-shipping-rates';

    /**
     * @param MiraklOrder $miraklOrder
     * @param OrderRepositoryInterface $orderRepository
     * @param ShopRepositoryInterface $shopRepository
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param MarketplaceHelper $marketplaceHelper
     */
    public function __construct(
        private MiraklOrder $miraklOrder,
        private OrderRepositoryInterface $orderRepository,
        private ShopRepositoryInterface $shopRepository,
        private LoggerInterface $logger,
        private Data $helper,
        private MarketplaceHelper $marketplaceHelper,
        private QuoteItemCollectionFactory $quoteItemCollectionFactory
    ) {
    }

    /**
     * @param string $message
     * @return void
     */
    public function execute(string $message)
    {
        $orderData = json_decode($message, true);
        try {
            if (isset($orderData['order_id'])) {
                $order = $this->orderRepository->get($orderData['order_id']);
                $this->updateMiraklShippingInformation($order);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' sendOrderToMiraklQueue Consumer - Order Increment ID ' . $order->getIncrementId());
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' sendOrderToMiraklQueue Consumer - Order Status ' . $order->getStatus());
                $this->miraklOrder->autoCreateMiraklOrder($order);
            }
        } catch (\Exception $e) {
            $this->logger->info(" ERROR SENDING ORDER TO MIRAKL ORDER ID {$orderData['order_id']}: " . $e->getMessage());
        }
    }

    /**
     * Setting the method_code and shipping_type_label for Order Items that
     * has Mirakl Shipping Rate associated through the Shop using getMiraklShopId
     *
     * @param OrderInterface $order
     * @return void
     * @throws \Exception
     */
    private function updateMiraklShippingInformation(OrderInterface $order): void
    {
        foreach ($order->getAllItems() as $item) {
            $miraklShopId = (int) $item->getMiraklShopId();
            $hasMiraklShippingRates = $this->shippingOptionHasMiraklShippingRates($miraklShopId);

            if (!$item->getMiraklOfferId() || !$hasMiraklShippingRates) {
                continue;
            }

            $additionalData = json_decode($item->getAdditionalData(), true);

            if ($this->marketplaceHelper->isD216694Enable()) {
                $additionalQty = $additionalData['quantity'] ?? null;
                $orderedQty = (float)$item->getQtyOrdered();

                if ($additionalQty !== null) {
                    $additionalQtyFloat = (float)$additionalQty;

                    if ($additionalQtyFloat !== $orderedQty) {
                        $quoteItem = $this->quoteItemCollectionFactory
                            ->create()
                            ->addFieldToFilter('quote_id', $order->getQuoteId())
                            ->addFieldToFilter('product_id', $item->getProductId())
                            ->getFirstItem();

                        $quoteItemQty = $quoteItem->getQty();

                        $this->logger->info('[MIRAKL QTY MISMATCH] ' . $order->getIncrementId(), [
                            'order_item_id' => $item->getItemId(),
                            'order_item_sku' => $item->getSku(),
                            'order_qty_ordered' => $orderedQty,
                            'order_additional_data_quantity' => $additionalQty,
                            'order_additional_data_full' => $item->getAdditionalData(),
                            'quote_item_id' => $quoteItem->getId(),
                            'quote_item_qty' => $quoteItemQty,
                            'quote_additional_data_full' => $quoteItem->getAdditionalData() ?? null

                        ]);
                    }
                }
            }

            $methodCode = $additionalData['mirakl_shipping_data']['method_code'] ?? '';
            $shippingTypeLabel = $additionalData['mirakl_shipping_data']['shipping_type_label'] ?? '';
            if ($methodCode && $shippingTypeLabel) {
                $item->setMiraklShippingType($methodCode);
                $item->setMiraklShippingTypeLabel($shippingTypeLabel);
                $item->save();
            }
        }
    }

    /**
     * @param int $miraklShopId
     * @return bool
     * @throws NoSuchEntityException
     */
    private function shippingOptionHasMiraklShippingRates(int $miraklShopId): bool
    {
        if ($miraklShopId === 0) {
            return false;
        }

        $shop = $this->shopRepository->getById($miraklShopId);
        $shippingRateOption = $shop->getShippingRateOption() ?? [];

        return isset($shippingRateOption[self::SHIPPING_RATE_OPTION_KEY])
            && $shippingRateOption[self::SHIPPING_RATE_OPTION_KEY] === self::MIRAKL_SHIPPING_RATES_VALUE;
    }
}
