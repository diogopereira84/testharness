<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Block\Adminhtml\View;

use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Mirakl\Api\Helper\Order as MiraklHelper;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use  Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Form extends \Magento\Shipping\Block\Adminhtml\View\Form
{

    /**
     * @param Data $helper
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param OrderDetailsDataMapper $orderDetailsDataMapper
     * @param ToggleConfig $toggleConfig
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param TimezoneInterface $timezone
     * @param ConfigInterface $instoreConfig
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        private Data         $helper,
        protected PriceCurrencyInterface $priceCurrency,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        private OrderDetailsDataMapper $orderDetailsDataMapper,
        private ToggleConfig $toggleConfig,
        private OrderItemRepositoryInterface $orderItemRepository,
        private readonly TimezoneInterface $timezone,
        private readonly ConfigInterface $instoreConfig,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    )
    {
        parent::__construct($context, $registry, $adminHelper, $carrierFactory, $data, $shippingHelper, $taxHelper);
    }

    /**
     * @param $order
     * @param $shipping
     * @return mixed
     */
    public function getCustomShippingDescription($order, $shipping): mixed
    {
        if (!$shipping->getMiraklShippingReference()) {
            return $order->getShippingDescription();
        }
        return $this->getMiraklShipping($order, $shipping);
    }

    /**
     * Get ord
     *
     * @param $order
     * @param $shipping
     * @return array|string
     * @throws NoSuchEntityException
     */
    public function getExtendedDate($order, $shipping) {

        $date = [];
        foreach ($shipping->getItems() as $item) {
            $orderItemId = $item->getOrderItemId();
            $orderItem   = $this->orderItemRepository->get($orderItemId);
            if (!$orderItem->getMiraklOfferId()) {
                return $date;
            }
        }

        $shipmentData    = $this->helper->getMktShipping($order, null, $shipping);
        $orderData       = $this->orderDetailsDataMapper->getMiraklOrderValue($order->getIncrementId(), (int)$shipmentData['seller_id']);
        $date            = $this->orderDetailsDataMapper->getExtendedDeliveryDate($orderData);

        if (!empty($date)) {
            return $date['dayOfWeek'] .', '. $date['dateFormatted'] .', '. $date['timeFormatted'];
        }

        return $date;
    }

    /**
     * Toggle for E-433689 Order Tracking Delivery Date Update (3p only)
     *
     * @return bool
     */
    public function isOrderTrackingDeliveryDateUpdateEnable(){
        return $this->orderDetailsDataMapper->isOrderTrackingDeliveryDateUpdateEnable();
    }

    /**
     * @param $order
     * @return mixed|void
     */
    public function getMiraklShipping($order, $shipment = null)
    {
        $shipping = $this->helper->getMktShipping($order, null, $shipment);
        return $shipping['method_title'] . ' - '. $shipping['deliveryDate'];
    }

    public function displayMktPrice($order, $shipping, $block)
    {
        if (!$shipping->getMiraklShippingReference()) {
            return $block->displayPriceAttribute('shipping_amount', false, ' ');
        }

        $res = $this->priceCurrency->format($this->helper->getMktShippingTotalAmount($order, $shipping));
        return '<strong>' . $res . '</strong>';
    }

    /**
     * @param $order
     * @param $shipping
     * @return string|null
     * @throws \Exception
     */
    public function getUpdatedTime($order, $shipping) {
        if (!$this->orderDetailsDataMapper->isOrderDueDateChanged($order->getId())) {
            return null;
        }

        $rawUpdatedTime = $this->orderDetailsDataMapper->getRecentUpdatedTime(
            $shipping->getId(),
            $shipping->getShippingDueDate()
        );

        if (!$rawUpdatedTime) {
            return null;
        }

        $formattedTime = $this->timezone
            ->date(new \DateTime($rawUpdatedTime), null, false)
            ->format('M j, Y g:i:s A');

        return $formattedTime . ' | Delivery Date Updated';
    }
}
