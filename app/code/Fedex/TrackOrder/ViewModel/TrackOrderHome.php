<?php
/**
 * @category Fedex
 * @package  Fedex_TrackOrder
 * @copyright   Copyright (c) 2021 FedEx
 * @author    Adithya Adithya <adithya.adithya@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\TrackOrder\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\EnvironmentManager\Model\Config\TrackOrderPod;
use Fedex\TrackOrder\Helper\OrderHelper;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Fedex\TrackOrder\Model\OrderDetailApi;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\TrackOrder\Model\Config;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class TrackOrderHome implements ArgumentInterface
{
    /**
     * @var array|null
     */
    private ?array $orderData = null;

    /**
     * @var string|null
     */
    private ?string $key = null;

    /**
     * @param TrackOrderPod $trackOrderPod
     * @param OrderDetailsDataMapper $orderDetailsDataMapper
     * @param Data $deliveryHelper
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param ToggleConfig $toggleConfig
     * @param Config $config
     * @param OrderDetailApi $OrderDetailApi
     * @param OrderHelper $OrderHelper
     * @param AssetRepository $assetRepository
     */
    public function __construct(
        private TrackOrderPod $trackOrderPod,
        private OrderDetailsDataMapper $orderDetailsDataMapper,
        protected Data $deliveryHelper,
        protected ScopeConfigInterface $scopeConfigInterface,
        protected ToggleConfig $toggleConfig,
        private Config $config,
        protected OrderDetailApi $OrderDetailApi,
        protected OrderHelper $OrderHelper,
        private AssetRepository $assetRepository
    )
    {
    }

    /**
     * Wrapper function
     */
    public function isTrackOrderActive()
    {
        return $this->trackOrderPod->isActive();
    }

    /**
     * Get order details and build the response
     * @param $orderIds
     *
     * @return Post[]
     */
    public function getOrderlist($orderIds)
    {
        return $this->orderDetailsDataMapper->getOrderlist($orderIds);
    }

    /**
     * function to check if order delivery is delayed
     * @return boolean
     */
    public function isOrderDelayed()
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->isOrderDelayed($this->orderData, $this->key);
    }

    /**
     * function to check if mkt order delivery is delayed
     * @return boolean
     */
    public function isMktOrderDelayed()
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->isMktOrderDelayed($this->orderData, $this->key);
    }

    /**
     * function to check if mkt order delivery is delayed
     * @return boolean
     */
    public function isMktOrderDelayedEnhancement($shopId)
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->isMktOrderDelayedEnhancement($this->orderData, $this->key, $shopId);
    }

    /**
     * function to get order status heading for table display
     * @return string
     */
    public function getOrderStatusHeading()
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->getOrderStatusHeading($this->orderData, $this->key);
    }

    /**
     * get order status details to handle status details and status icons
     * @return string
     */
    public function getOrderStatusDetail()
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->getOrderStatusDetail($this->orderData, $this->key);
    }

    /**
     * get order Mirakl status details to handle status details and status icons
     * @return string
     */
    public function getMktOrderStatusDetail($orderData, $key, $sellerId = null, $trackNumber = null)
    {
        return $this->orderDetailsDataMapper->getMktOrderStatusDetail($orderData, $key, $sellerId, $trackNumber);
    }

    /**
     * get order Mirakl status details to handle status details and status icons
     * @return string
     */
    public function getMktOrderStatusDetailEnhancement($shopId, $status = null)
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->getMktOrderStatusDetailEnhancement($this->orderData, $this->key, $shopId, $status);
    }

    /**
     * Toggle for E-433689 Order Tracking Delivery Date Update (3p only)
     *
     * @return bool
     */
    public function isOrderTrackingDeliveryDateUpdateEnable()
    {
        return $this->orderDetailsDataMapper->isOrderTrackingDeliveryDateUpdateEnable();
    }

    /**
     * @param $miraklOrderData
     * @return array
     */
    public function getExtendedDeliveryDate($miraklOrderData)
    {
        return $this->orderDetailsDataMapper->getExtendedDeliveryDate($miraklOrderData);
    }

    /**
     * @param $key
     * @param $shopId
     * @return array|string
     */
    public function getMktOrderData($shopId)
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->getMktOrderData($this->key, $shopId);
    }

    /**
     * check commercial customer
     * @return boolean
     */
    public function isCommercialCustomer()
    {
        return $this->deliveryHelper->isCommercialCustomer();
    }

    /**
     * Configuration Legacy Track Order URL
     *
     * @return String
     */
    public function getLegacyTrackOrderUrl()
    {
        return $this->orderDetailsDataMapper->getLegacyTrackOrderUrl();
    }

    /**
     * Toggle for updating xml fields
     *
     * @return string
     */
    public function getLabelText()
    {
        return 'Track My Print Order';
    }

    /**
     * Get track order header from config
     *
     * @return string
     */
    public function getTrackOrderHeader(): string
    {
        return $this->config->getTrackOrderHeader();
    }

    /**
     * Get track order description from config
     *
     * @return string
     */
    public function getTrackOrderDescription(): string
    {
        return $this->config->getTrackOrderDescription();
    }

    /**
     * Get track shipment URL from configuration.
     *
     * @return string
     */
    public function getTrackShipmentUrl(): string
    {
        return $this->config->getTrackShipmentUrl();
    }

    /**
     * Toggle for enabling Code Improvement
     * @return boolean
     */
    public function toggleCodeImprovementLogic(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue("tiger_B_2177449_order_tracking_improvement_toggle") ?? false;
    }

    /**
     * Get track details
     *
     * @return array
     */
    public function getTrackingDetails($orderData, $key, $sellerId = null, $seller = null): array
    {
        if ($seller) {
            return [
                'originStatusProgressKey' => $sellerId,
                'trackMktOrderUrl' => $seller['track_order_url'],
                'mktTrackingNumber' => $seller['tracking_number'],
            ];
        }

        $item = $orderData[$key]['orderMktItems'][0];

        return [
            'originStatusProgressKey' => $orderData[$key]['order_id'],
            'trackMktOrderUrl' => $item['track_order_url'],
            'mktTrackingNumber' => $item['tracking_number'],
        ];
    }

    /**
     * Get order details from API and build the response
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderlistApi($orderId)
    {
        return $this->OrderDetailApi->fetchOrderDetailFromApi($orderId);
    }

    /**
     * Get order ids segregated
     *
     * @param array $orderIds
     * @return array
     */
    public function getSegregatedOrderIds($orderIds)
    {
        return $this->OrderHelper->segregateOrderIds($orderIds);
    }

    /**
     * Get Order Due Date Message for 1P Products
     *
     * @return string
     */
    public function getProductDueDateMessage(): string
    {
        return $this->config->getProductDueDateMessage() ?? '';
    }

    /**
     * Initializes the ViewModel with order-specific context.
     *
     * @param array $orderData
     * @param string $key
     * @return $this
     */
    public function initOrderContext(array $orderData, string $key): self
    {
        $this->orderData = $orderData;
        $this->key = $key;
        return $this;
    }

    /**
     * Throws an exception if the order context has not been initialized.
     *
     * @throws \LogicException
     */
    private function guardAgainstUninitializedContext(): void
    {
        if ($this->orderData === null || $this->key === null) {
            throw new \LogicException('Order context must be initialized by calling initOrderContext() before using this method.');
        }
    }

    /**
     * Get Mirakl item count
     *
     * @return int
     */
    public function getMiraklItemCount(): int
    {
        $this->guardAgainstUninitializedContext();
        return count($this->orderData[$this->key]['orderMktItems'] ?? []);
    }

    /**
     * Get Fedex item count
     *
     * @return int
     */
    public function getFedexItemsCount(): int
    {
        $this->guardAgainstUninitializedContext();
        return count($this->orderData[$this->key]['orderItems'] ?? []);
    }

    /**
     * Get info image URL
     *
     * @return string
     */
    public function getInfoImageUrl(): string
    {
        return $this->assetRepository->getUrl('Fedex_TrackOrder::images/info.png');
    }

    /**
     * Get notification banner image URL
     *
     * @return string
     */
    public function getNotificationBannerImageUrl(): string
    {
        return $this->assetRepository->getUrl('Fedex_TrackOrder::images/Notification-banner.png');
    }

    /**
     * Get up arrow image URL
     *
     * @return string
     */
    public function getUpArrowImageUrl(): string
    {
        return $this->assetRepository->getUrl('Fedex_TrackOrder::images/up-arrow.png');
    }

    /**
     * Get down arrow image URL
     *
     * @return string
     */
    public function getDownArrowImageUrl(): string
    {
        return $this->assetRepository->getUrl('Fedex_TrackOrder::images/down-arrow.png');
    }

    /**
     * Gets order icon image URL
     *
     * @return string
     */
    public function getOrderedIconImageUrl(): string
    {
        return $this->assetRepository->getUrl('Fedex_TrackOrder::images/Ordered-icon.png');
    }

    /**
     * Gets order delay icon image URL
     *
     * @return string
     */
    public function getOrderedIconDelayImageUrl(): string
    {
        return $this->assetRepository->getUrl('Fedex_TrackOrder::images/Delay-icon.png');
    }

    /**
     * Check if order due date is delayed
     *
     * @return bool
     */
    public function isOrderDueDateDelayed()
    {
        $this->guardAgainstUninitializedContext();
        return $this->orderDetailsDataMapper->isOrderDueDateDelayed($this->orderData, $this->key);
    }

        /**
     * Gets order status detail icon image URL
     *
     * @return string
     */
    public function getOrderStatusDetailIconImageUrl(): string
    {
        return $this->assetRepository->getUrl('Fedex_TrackOrder::images/'. $this->getOrderStatusDetail() .'-icon.png');
    }
}
