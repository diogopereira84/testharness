<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\TrackOrder\Block\Order;

use \Magento\Framework\View\Element\Template;
use Magento\Framework\App\Action\Context;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Magento\Sales\Model\OrderFactory;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Magento\Sales\Model\Order;
use Fedex\TrackOrder\ViewModel\TrackOrderHome;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

/**
 * Track Order Details Block class
 */
class OrderDetails extends \Magento\Framework\View\Element\Html\Link
{
    const SHIPPING_TYPE_PICKUP = 'fedexshipping_PICKUP';
    const TRACKORDERS_ORDER_DETAILS_PAGE = 'trackorder_order_details_page_';
    const TRACKORDERS_ORDER_STATUS_COMPONENT = 'trackorder_order_status_component_';
    const TRACKORDERS_ORDER_MKT_ORDER_STATUS_COMPONENT = 'trackorder_mkt_order_status_component_';
    const TRACKORDERS_ORDER_MKT_MULTIPLE_3P_SELLERS_TRACKER = 'trackorder_mkt_multiple_3p_sellers_tracker_';

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderDetailsDataMapper $orderDetailsDataMapper
     * @param OrderFactory $orderFactory,
     * @param OrderHistoryEnhacement $orderHistoryEnhacementViewModel
     * @param TrackOrderHome $trackOrderHome
     * @param array $data
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     *
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        private OrderDetailsDataMapper $orderDetailsDataMapper,
        private OrderFactory $orderFactory,
        private OrderHistoryEnhacement $orderHistoryEnhacementViewModel,
        private TrackOrderHome $trackOrderHome,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @codeCoverageIgnore
     * @return OrderDetails
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * get order details and build the response
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
    public function isOrderDelayed($orderData, $key)
    {
        return $this->orderDetailsDataMapper->isOrderDelayed($orderData, $key);
    }

    /**
     * function to check if mkt order delivery is delayed
     * @return boolean
     */
    public function isMktOrderDelayed($orderData, $key)
    {
        return $this->orderDetailsDataMapper->isMktOrderDelayed($orderData, $key);
    }

    /**
     * function to get order status heading for table display
     * @return string
     */
    public function getOrderStatusHeading($orderData, $key)
    {
        return $this->orderDetailsDataMapper->getOrderStatusHeading($orderData, $key);
    }

    /**
     * get order status details to handle status details and status icons
     * @return string
     */
    public function getOrderStatusDetail($orderData, $key): string
    {
        return $this->orderDetailsDataMapper->getOrderStatusDetail($orderData, $key);
    }

    /*
     *  Get Order Details
     * @param $orderIncrementId
     * @return Order
     */
    public function getOrderByIncrementId($orderIncrementId): Order
    {
        return $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
    }

    /**
     * @param $order
     * @return int
     */
    public function getMiraklItemCount($itemsCollection): int
    {
        $miraklItemCount = 0;
        foreach ($itemsCollection as $shipmentitem) {
            $item = $shipmentitem->getOrderItem();
            if ($item->getData('mirakl_offer_id')) {
                $miraklItemCount++;
            }
        }

        return $miraklItemCount;
    }

    /**
     * @param $productObj
     * @return string
     */
    public function getItemThumbnailUrl($productObj): string
    {
        return $this->orderHistoryEnhacementViewModel->getItemThumbnailUrl($productObj);
    }

    /**
     * @param $order
     * @return string
     */
    public function getOrderShippingDescription($order): ?string
    {
        if ($this->isOrderShippingTypePickup($order)) {
            $shippingAddress = $order->getShippingAddress();
            return implode(',', $shippingAddress->getStreet()) . ', ' .
                $shippingAddress->getCity() . ', ' .
                $shippingAddress->getCountryId() . ' ' . $shippingAddress->getPostcode();
        }

        $shippingDescription = $order->getShippingDescription();
        if (!empty($shippingDescription)) {
            $shippingDescription = explode('-', $shippingDescription);
            $shippingDescription = $shippingDescription[0] ?? null;
        }

        return $shippingDescription;
    }

    /**
     * Get shipping type
     * @return string
     */
    public function getOrderShippingType($order): string
    {
        return $this->isOrderShippingTypePickup($order)
            ? $this->orderDetailsDataMapper::DISPLAY_TITLE_PICKUP
            : $this->orderDetailsDataMapper::DISPLAY_TITLE_SHIPMENT;
    }

    /**
     * check shipping type of order is pickup
     * @return boolean
     */
    public function isOrderShippingTypePickup($order): bool
    {
        return $order->getShippingMethod() === self::SHIPPING_TYPE_PICKUP;
    }

    /**
     * Get view model object
     * @return TrackOrderHome
     */
    public function getTrackOrderHomeViewModel(): TrackOrderHome
    {
        return $this->trackOrderHome;
    }

    public function getTrackorderDetailsPage($orderIds, $orderData, $orderKey)
    {
        $trackorderOrderDetailsPage = self::TRACKORDERS_ORDER_DETAILS_PAGE.$orderKey;
        $trackorderOrderDetailsPageBlock = $this->getLayout()->createBlock(
            OrderDetails::class,
            $trackorderOrderDetailsPage,
            [
                'data' => [
                    'search' => $orderIds,
                    'order_data' => $orderData,
                    'order_key' => $orderKey,
                    'track_order_home_view_model' => $this->trackOrderHome
                ]
            ]
        );
        $trackorderOrderDetailsPageBlock->setTemplate('Fedex_TrackOrder::order_details_page.phtml');

        return $trackorderOrderDetailsPageBlock->toHtml();
    }

    public function getTrackorderOrderStatusComponent($orderData, $key, $orderStatusDetail, $identifier = null)
    {
        $uniqueIdentifier = ($identifier ? '_'.$identifier : '');
        $trackorderOrderStatusComponent = self::TRACKORDERS_ORDER_STATUS_COMPONENT.$key.$uniqueIdentifier;
        $trackorderOrderStatusComponentBlock = $this->getLayout()->createBlock(
            OrderDetails::class,
            $trackorderOrderStatusComponent,
            [
                'data'      => [
                    'order_data' => $orderData,
                    'order_key' => $key,
                    'order_status' => $orderStatusDetail,
                    'track_order_home_view_model' => $this->trackOrderHome
                ]
            ]
        );
        $trackorderOrderStatusComponentBlock->setTemplate('Fedex_TrackOrder::order_status_component.phtml');

        return $trackorderOrderStatusComponentBlock->toHtml();
    }

    public function getTrackorderMktOrderStatusComponent($orderData, $key, $sellerId = null, $seller = null, $identifier = null, $trackNumber = null)
    {
        $sellerOrOrderInfo = $sellerId ?? $key;
        $uniqueIdentifier = ($identifier ? '_' . $identifier : '');
        $orderId = $orderData[$key]['order_id'];
        $trackorderMktOrderStatusComponent = self::TRACKORDERS_ORDER_MKT_ORDER_STATUS_COMPONENT . $sellerOrOrderInfo . $uniqueIdentifier;
        if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
            $trackorderMktOrderStatusComponent .= $trackNumber;
        }
        $trackorderMktOrderStatusComponent .= '_' . $orderId;
        $trackorderMktOrderStatusComponentBlock = $this->getLayout()->createBlock(
            OrderDetails::class,
            $trackorderMktOrderStatusComponent,
            [
                'data' => [
                    'order_data' => $orderData,
                    'order_key' => $key,
                    'seller_id' => $sellerId,
                    'seller' => $seller,
                    'track_order_home_view_model' => $this->trackOrderHome,
                    'track' => $trackNumber ?? ''
                ]
            ]
        );
        $trackorderMktOrderStatusComponentBlock->setTemplate('Fedex_TrackOrder::mkt_order_status_component.phtml');

        return $trackorderMktOrderStatusComponentBlock->toHtml();
    }

    public function getTrackorderMktMultiple3PSellersTracker($orderData, $key)
    {
        $trackorderMktMultiple3PSellersTracker = self::TRACKORDERS_ORDER_MKT_MULTIPLE_3P_SELLERS_TRACKER . $key;
        $trackorderMktMultiple3PSellersTrackerBlock = $this->getLayout()->createBlock(
            OrderDetails::class,
            $trackorderMktMultiple3PSellersTracker,
            [
                'data' => [
                    'order_data' => $orderData,
                    'order_key' => $key,
                    'track_order_home_view_model' => $this->trackOrderHome
                ]
            ]
        );

        if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
            $trackorderMktMultiple3PSellersTrackerBlock->setTemplate('Fedex_TrackOrder::marketplace/multiple_3p_sellers_tracker_enhancement.phtml');
        } else {
            $trackorderMktMultiple3PSellersTrackerBlock->setTemplate('Fedex_TrackOrder::marketplace/multiple_3p_sellers_tracker.phtml');
        }
        return $trackorderMktMultiple3PSellersTrackerBlock->toHtml();
    }
}
