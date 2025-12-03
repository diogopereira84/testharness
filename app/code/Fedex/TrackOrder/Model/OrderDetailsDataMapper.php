<?php
/**
 * @category    Fedex
 * @package     Fedex_TrackOrder
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Adithya Adithya <adithya.adithya@fedex.com>
 */

declare (strict_types = 1);

namespace Fedex\TrackOrder\Model;

use Fedex\InStoreConfigurations\Api\ConfigInterface as InStoreConfigInterface;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\Shipment\Helper\Data as ShipmentHelper;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\Api\Helper\Order as MiraklHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Mirakl\Api\Helper\Shipment as ShipmentApi;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\Shipment\Model\ResourceModel\DueDateLog\CollectionFactory;
use Fedex\Shipment\Api\GetOrderByIncrementIdInterface;
use Fedex\Shipment\Api\DueDateLogRepositoryInterface;

class OrderDetailsDataMapper
{
    public const STATUS_NEW = 'New';
    public const STATUS_ORDERED = 'Ordered';
    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_DELAY = 'Delay';
    public const STATUS_CANCELED = 'Cancelled';
    public const STATUS_SHIPPED = 'Shipped';
    public const STATUS_READY_FOR_PICKUP = 'Ready for Pickup';
    public const STATUS_DECLINED = 'Declined';
    public const STATUS_PENDING_APPROVAL = 'Pending Approval';

    public const CHECK_NEW = 'new';
    public const CHECK_CONFIRMED = 'confirmed';
    public const CHECK_IN_PROGRESS = 'in_process';
    public const CHECK_CANCELED = 'canceled';
    public const CHECK_CANCELLED = 'cancelled';
    public const CHECK_DELIVERED = 'delivered';
    public const CHECK_SHIPPED = 'shipped';
    public const CHECK_READY_FOR_PICKUP = 'ready_for_pickup';
    public const CHECK_COMPLETE = 'complete';
    public const CHECK_WAITING_ACCEPTANCE = 'waiting_acceptance';
    public const CHECK_RECEIVED = 'received';
    public const CHECK_SHIPPING = 'shipping';

    public const STATUS_SHIPPING = 'Shipping';
    public const CHECK_TO_COLLECT = 'to_collect';
    public const CHECK_WAITING_REFUND = 'waiting_refund';
    public const CHECK_WAITING_REFUND_PAYMENT = 'waiting_refund_payment';
    public const CHECK_CLOSED = 'closed';
    public const CHECK_REFUSED = 'refused';
    public const CHECK_INCIDENT_OPEN = 'incident_open';
    public const CHECK_REFUNDED = 'refunded';

    public const STATUS_COMPLETE = 'Complete';

    public const CHECK_STATUS_STAGING = 'staging';

    public const CHECK_WAITING_DEBIT = 'waiting_debit';

    public const CHECK_WAITING_DEBIT_PAYMENT = 'waiting_debit_payment';

    public const CHECK_DECLINED = 'declined';

    public const CHECK_PENDING_APPROVAL = 'pending_approval';

    public const CHECK_INCOMING = 'incoming';

    public const CHECK_READY_FOR_PRODUCTION = "ready_for_production";

    public const CHECK_IN_PRODUCTION = "in_production";

    public const CHECK_BINNED = "binned";

    public const DISPLAY_STATUS_EXPECTED_AVAILABILITY = 'Expected Availability';
    public const DISPLAY_STATUS_EXPECTED_DELIVERY = 'Expected Delivery';

    public const DELIVERY_PICKUP = 'pickup';
    public const DELIVERY_SHIPMENT = 'shipment';
    public const DISPLAY_TITLE_PICKUP = 'In-store pickup (FedEx Office)';
    public const DISPLAY_TITLE_SHIPMENT = 'Shipment (FedEx Office)';

    /**
     * XPATH FOR E-433689 Order Tracking Delivery Date Update (3p only)
     */
    public const XPATH_ORDER_TRACKING_DELIVERY_DATE_UPDATE = 'tiger_e433689';

    /**
     * XPATH FOR D-191698 Order tracking page not showing 1p cancellation status in mixed cart scenario
     */
    public const XPATH_ORDER_TRACKING_1P_CANCELLATION_MIXED_CART = 'tiger_d191698';

    /**
     * B-2177449 Order Tracking Improvement Toggle
     */
    public const CODE_IMPROVEMENT_TOGGLE = 'tiger_B_2177449_order_tracking_improvement_toggle';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $configInterface
     * @param ShipmentHelper $shipmentHelper
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param Image $imageHelper
     * @param ProductFactory $productFactory
     * @param Data $priceHelper
     * @param MiraklHelper $miraklHelper
     * @param ToggleConfig $toggleConfig
     * @param ShopManagement $shopManagement
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param CreditmemoRepository $creditmemoRepository
     * @param ShipmentApi $shipmentApi
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param CollectionFactory $dueDateLogCollectionFactory
     * @param GetOrderByIncrementIdInterface $getOrderByIncrementId
     * @param DueDateLogRepositoryInterface $dueDateLogRepository
     * @param ConfigInterface $productBundleConfig
     */
    public function __construct(
        private \Magento\Framework\App\Config\ScopeConfigInterface $configInterface,
        protected ShipmentHelper $shipmentHelper,
        protected JsonFactory $resultJsonFactory,
        protected PageFactory $resultPageFactory,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected OrderRepositoryInterface $orderRepository,
        protected Image $imageHelper,
        protected ProductFactory $productFactory,
        protected Data $priceHelper,
        protected MiraklHelper $miraklHelper,
        private ToggleConfig $toggleConfig,
        private ShopManagement $shopManagement,
        private ShipmentRepositoryInterface $shipmentRepository,
        private CreditmemoRepository $creditmemoRepository,
        private ShipmentApi $shipmentApi,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private readonly CollectionFactory $dueDateLogCollectionFactory,
        private readonly GetOrderByIncrementIdInterface $getOrderByIncrementId,
        private readonly DueDateLogRepositoryInterface $dueDateLogRepository,
        private ConfigInterface $productBundleConfig,
        private readonly InStoreConfigInterface $instoreConfig
    )
    {
    }

    /**
     * get shipment type of order
     * @return []
     */
    public function getShipmentType($orderData)
    {
        if ($this->toggleCodeImprovementLogic()) {
            $shippingMethod = $orderData->getShippingMethod();
            $deliveryType = strtolower(substr($shippingMethod, strpos($shippingMethod, '_') + 1));
            $shippingDescription = $orderData->getShippingDescription()
                ? explode('-', $orderData->getShippingDescription())
                : '';
            if (isset($shippingDescription[0])) {
                $shippingDescription = trim($shippingDescription[0]);
            }

            if ($deliveryType === self::DELIVERY_PICKUP) {
                return [self::DISPLAY_TITLE_PICKUP, self::DELIVERY_PICKUP, $shippingDescription];
            }

            return [self::DISPLAY_TITLE_SHIPMENT, self::DELIVERY_SHIPMENT, $shippingDescription];
        } else {
            $shippingMethod = $orderData->getShippingMethod();
            $position = strpos($shippingMethod, '_');
            $deliveryType = substr($shippingMethod, $position + 1);
            $deliveryType = strtolower($deliveryType);

            if ($deliveryType == (self::DELIVERY_PICKUP)) {
                $shipmentTitle = (self::DISPLAY_TITLE_PICKUP);
                $ShipmentValue = (self::DELIVERY_PICKUP);
            } else {
                $shipmentTitle = (self::DISPLAY_TITLE_SHIPMENT);
                $ShipmentValue = (self::DELIVERY_SHIPMENT);
            }

            $shippingDescription = $orderData->getShippingDescription()
                ? explode('-', $orderData->getShippingDescription())
                : '';
            if (isset($shippingDescription[0])) {
                $shippingDescription = trim($shippingDescription[0]);
            }

            return [$shipmentTitle, $ShipmentValue, $shippingDescription];
        }
    }

    /**
     * function to get order status heading for table display
     * @return string
     */
    public function getOrderStatusHeading($orderData, $key)
    {
        if ($this->toggleCodeImprovementLogic()) {
            if (!isset($orderData[$key]['order_status'], $orderData[$key]['shipment_type'][1])) {
                return null;
            }

            $status = strtolower($orderData[$key]['order_status']);
            $orderType = strtolower($orderData[$key]['shipment_type'][1]);

            if ($status === strtolower(self::CHECK_CANCELED)) {
                return self::STATUS_CANCELED;
            }

            if ($orderType === strtolower(self::DELIVERY_PICKUP)) {
                return self::DISPLAY_STATUS_EXPECTED_AVAILABILITY;
            }

            return self::DISPLAY_STATUS_EXPECTED_DELIVERY;
        } else {
            if (isset($orderData[$key]['order_status']) && isset($orderData[$key]['shipment_type'][1])) {
                $status = strtolower($orderData[$key]['order_status']);
                $orderType = strtolower($orderData[$key]['shipment_type'][1]);
                if ($status == strtolower((self::CHECK_CANCELED))) {
                    return (self::STATUS_CANCELED);
                } elseif ($orderType == strtolower((self::DELIVERY_PICKUP))) {
                    return (self::DISPLAY_STATUS_EXPECTED_AVAILABILITY);
                } else {
                    return (self::DISPLAY_STATUS_EXPECTED_DELIVERY);
                }
            }
        }
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore | get image thumbnail url
     */
    public function getItemThumbnailUrl($productObj)
    {
        if ($this->toggleCodeImprovementLogic()) {
            if (empty($productObj)) {
                return $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
            }

            return $this->imageHelper->init($productObj, 'product_page_image_small')
                ->setImageFile($productObj->getSmallImage())
                ->keepFrame(false)
                ->resize(140, 160)
                ->getUrl();
        } else {
            if (!empty($productObj)) {
                return $this->imageHelper->init($productObj, 'product_page_image_small')
                    ->setImageFile($productObj->getSmallImage())
                    ->keepFrame(false)
                    ->resize(140, 160)
                    ->getUrl();
            } else {
                return $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
            }
        }
    }

    /**
     * get pickup address
     * @return string
     */
    public function getPickupAddress($order)
    {
        if ($this->toggleCodeImprovementLogic()) {
            $address = $order->getShippingAddress();

            $street = implode(',', $address->getStreet() ?? []);
            $city = $address->getCity() ?? '';
            $region = $address->getRegion() ?? '';
            $postcode = $address->getPostcode() ?? '';

            return "{$street}, {$city}, {$region} {$postcode}";
        } else {
            return implode(',', $order->getShippingAddress()->getStreet()) . ', ' .
                $order->getShippingAddress()->getCity() . ', ' . $order->getShippingAddress()->getRegion() . ' ' .
                $order->getShippingAddress()->getPostcode();
        }
    }

    /**
     * get order tracking number
     * @param Object $order
     * @return null|string
     */
    public function getTrackingNumber($order)
    {
        $trackCollection = $order->getTracksCollection();
        if ($trackCollection->count()) {
            return $order->getTracksCollection()->getFirstItem()->getTrackNumber();
        }

        return null;
    }

    /**
     * get expected delivery/availability date
     * @return []
     */
    public function getExpectedDeliveryDate($order)
    {
        if ($this->toggleCodeImprovementLogic()) {
            $shippingMethod = $order->getShippingMethod();
            $dateFormat = "l, F j";
            $timeFormat = "g:ia";

            $formatDateTime = function (?string $dateTime) use ($dateFormat, $timeFormat): string {
                if (!$dateTime) {
                    return '';
                }
                return date($dateFormat, strtotime($dateTime)) . ', ' . strtolower(date($timeFormat, strtotime($dateTime)));
            };

            if ($shippingNewDueDate = $this->getShippingDueDate($order)) {
                $descriptionParts = explode(" ", $shippingNewDueDate);
                return $this->formatShippingDescription($descriptionParts);
            }

            if ($shippingMethod === 'fedexshipping_PICKUP') {
                $shipment = $this->shipmentHelper->getShipmentById($order->getShipmentsCollection()->getFirstItem()->getId());
                $completeDate = $shipment
                    ? ($shipment->getOrderCompletionDate() ?? $order->getEstimatedPickupTime())
                    : $order->getEstimatedPickupTime();
                $description = $formatDateTime($completeDate);

                $descriptionParts = explode(" ", $description);
                return $this->formatShippingDescription($descriptionParts);
            }

            $description = $order->getShippingDescription();
            $descriptionParts = explode('-', $description);

            if (isset($descriptionParts[1])) {
                $description = trim($descriptionParts[1]);
                $descriptionParts = explode(" ", $description);
                return $this->formatShippingDescription($descriptionParts);
            }

            return null;
        } else {
            $shippingDescription = '';
            if ($order->getShippingMethod() == 'fedexshipping_PICKUP') {
                $shipmentId = $order->getShipmentsCollection()->getFirstItem()->getId();
                $shipment = $this->shipmentHelper->getShipmentById($shipmentId);
                if ($shipment) {
                    $shipmentCompleteDate = $shipment->getOrderCompletionDate();
                    if($shipmentCompleteDate) {
                        $shippingDescription = date("l, F j", strtotime($shipmentCompleteDate)) . ', '
                            . strtolower(date("g:ia", strtotime($shipmentCompleteDate)));
                    } else {
                        $estimatedPickupTime = $order->getEstimatedPickupTime();
                        if (!is_null($estimatedPickupTime)) {
                            $shippingDescription = date("l, F j", strtotime($estimatedPickupTime)) . ', '
                                . strtolower(date("g:ia", strtotime($estimatedPickupTime)));
                        }
                    }

                }else{
                    $estimatedPickupTime = $order->getEstimatedPickupTime();
                    $shippingDescription = date("l, F j", strtotime($estimatedPickupTime)).', '
                        .strtolower(date("g:ia", strtotime($estimatedPickupTime)));
                }
                $shippingDescription = explode(" ", $shippingDescription);
                $estimatedDelivery = (isset($shippingDescription[0])) ? trim($shippingDescription[0], ', ') . "-" : '';
                $estimatedDelivery .= (isset($shippingDescription[1])) ? $shippingDescription[1] . " " : '';
                $estimatedDelivery .= (isset($shippingDescription[2])) ? $shippingDescription[2] . " " : '';
                $estimatedDelivery .= date('Y') . "-";
                $estimatedDelivery .= (isset($shippingDescription[3])) ? $shippingDescription[3] . " " : '';
                $shippingDescription = explode('-', $estimatedDelivery);
            } else {
                $shippingDescription = explode('-', $order->getShippingDescription());
                if (isset($shippingDescription[1])) {
                    $shippingDescription = trim($shippingDescription[1]);
                } else {
                    return null;
                }

                $shippingDescription = explode(" ", $shippingDescription);
                $estimatedDelivery = "";

                if (count($shippingDescription) >= 5) {
                    $estimatedDelivery = (isset($shippingDescription[0])) ? trim($shippingDescription[0], ', ') . "-" : '';
                    $estimatedDelivery .= (isset($shippingDescription[1])) ? $shippingDescription[1] . " " : '';
                    $estimatedDelivery .= (isset($shippingDescription[2])) ? $shippingDescription[2] . ", " : '';
                    $estimatedDelivery .= date('Y') . "-";
                    $estimatedDelivery .= (isset($shippingDescription[3])) ? $shippingDescription[3] . " " : '';
                    $estimatedDelivery .= (isset($shippingDescription[4])) ? $shippingDescription[4] . " " : '';
                    $estimatedDelivery .= (isset($shippingDescription[5])) ? $shippingDescription[5] . " " : '';

                    $shippingDescription = explode('-', $estimatedDelivery);
                } else {
                    $estimatedDelivery = (isset($shippingDescription[0])) ? trim($shippingDescription[0], ', ') . "-" : '';
                    $estimatedDelivery .= (isset($shippingDescription[1])) ? $shippingDescription[1] . " " : '';
                    $estimatedDelivery .= (isset($shippingDescription[2])) ? $shippingDescription[2] . " " : '';
                    $estimatedDelivery .= date('Y') . "-";
                    $estimatedDelivery .= (isset($shippingDescription[3])) ? $shippingDescription[3] . " " : '';
                    $shippingDescription = explode('-', $estimatedDelivery);
                }
            }
            return $shippingDescription;
        }
    }

    /**
     * get expected delivery/availability date
     * @param object $order
     * @return null|array
     */
    public function getCanceledDate($order)
    {
        if ($this->toggleCodeImprovementLogic()) {
            if ($order->getState() !== \Magento\Sales\Model\Order::STATE_CANCELED) {
                return null;
            }

            $dateFormat = "l-M j, Y";
            $timeFormat = "g:ia";
            $updatedAt = $order->getUpdatedAt();
            $orderCancelledDate = date($dateFormat, strtotime($updatedAt)) . '-' . strtolower(date($timeFormat, strtotime($updatedAt)));

            return explode('-', $orderCancelledDate);
        } else {
            $orderCancelledDate = '';
            if ($order->getState() == \Magento\Sales\Model\Order::STATE_CANCELED) {
                $orderCancelledDate = date("l-M j, Y", strtotime($order->getUpdatedAt())) . '-' .
                    strtolower(date("g:ia", strtotime($order->getUpdatedAt())));

                return explode('-', $orderCancelledDate);
            }

            return null;
        }
    }

    /**
     * Function to check if order delivery is delayed
     * @param  Object $orderData
     * @param  Int    $key
     * @return boolean
     */
    public function isOrderDelayed($orderData, $key)
    {
        if (isset($orderData[$key]['delivery_date'][1])) {
            if (strtotime($orderData[$key]['delivery_date'][1])) {
                $formattedDeliveryDate = date('Y-m-d', strtotime($orderData[$key]['delivery_date'][1]));
                $currentDate = date('Y-m-d');
                if ($currentDate > $formattedDeliveryDate) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Function to check if mkt order delivery is delayed
     * @param  Object $orderData
     * @param  Int    $key
     * @return boolean
     */
    public function isMktOrderDelayed($orderData, $key, $shopId = null): bool
    {
        if ($this->toggleCodeImprovementLogic()) {
            $currentDate = date('Y-m-d');

            if ($shopId) {
                $deliveryDate = $orderData[$key]['orderMktItems'][$shopId]['delivery_date'][1] ?? null;

                if ($deliveryDate) {
                    $formattedDeliveryDate = date('Y-m-d', strtotime($deliveryDate));

                    if ($currentDate > $formattedDeliveryDate) {
                        return false;
                    }
                }
            } else {
                foreach ($orderData[$key]['orderMktItems'] ?? [] as $mktItem) {
                    $deliveryDate = $mktItem['delivery_date'][1] ?? null;

                    if ($deliveryDate) {
                        $formattedDeliveryDate = date('Y-m-d', strtotime($deliveryDate));

                        if ($currentDate > $formattedDeliveryDate) {
                            return true;
                        }
                    }
                }
            }
        } else {
            if ($shopId) {
                if (isset($orderData[$key]['orderMktItems'])) {
                    if (isset($orderData[$key]['orderMktItems'][$shopId]['delivery_date'])) {
                        $formattedDeliveryDate = date('Y-m-d', strtotime($orderData[$key]['orderMktItems'][$shopId]['delivery_date'][1]));
                        $currentDate = date('Y-m-d');
                        if ($currentDate > $formattedDeliveryDate) {
                            return false;
                        }
                    }
                }
            } else {
                if (isset($orderData[$key]['orderMktItems'])) {
                    foreach ($orderData[$key]['orderMktItems'] as $mktItem) {
                        $formattedDeliveryDate = date('Y-m-d', strtotime($mktItem['delivery_date'][1]));
                        $currentDate = date('Y-m-d');
                        if ($currentDate > $formattedDeliveryDate) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Function to check if mkt order delivery is delayed
     * @param  Object $orderData
     * @param  Int    $key
     * @return boolean
     */
    public function isMktOrderDelayedEnhancement($orderData, $key, $shopId = null): bool
    {
        if ($this->toggleCodeImprovementLogic()) {
            if (!$shopId) {
                return false;
            }

            $deliveryDate = $orderData[$key]['orderMktItems'][$shopId]['delivery_date'][1] ?? null;

            if (!$deliveryDate) {
                return false;
            }

            $formattedDeliveryDate = date('Y-m-d', strtotime($deliveryDate));
            $currentDate = date('Y-m-d');

            if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
                return $currentDate > $formattedDeliveryDate;
            }

            return $currentDate <= $formattedDeliveryDate;
        } else {

            if ($shopId) {
                if (isset($orderData[$key]['orderMktItems'])) {
                    if (isset($orderData[$key]['orderMktItems'][$shopId]['delivery_date'])) {
                        $formattedDeliveryDate = date('Y-m-d', strtotime($orderData[$key]['orderMktItems'][$shopId]['delivery_date'][1]));
                        $currentDate = date('Y-m-d');
                        if ($currentDate > $formattedDeliveryDate) {
                            return false;
                        }
                    }
                }
            }

            return false;
        }
    }

    /**
     * get order status details to handle status details and status icons
     * @return string
     * @param $orderData
     * @param $key
     */
    public function getOrderStatusDetail($orderData, $key)
    {
        if (!isset($orderData[$key]['order_status'])) {
            return '';
        }

        $status = strtolower($orderData[$key]['order_status']);

        return match ($status) {
            self::CHECK_NEW, self::CHECK_INCOMING, self::CHECK_READY_FOR_PRODUCTION => self::STATUS_ORDERED,
            self::CHECK_CANCELED, self::CHECK_CANCELLED => self::STATUS_CANCELED,
            self::CHECK_IN_PROGRESS, self::CHECK_CONFIRMED => (
                $this->isOrderDelayed($orderData, $key)
                    ? self::STATUS_DELAY
                    : self::STATUS_PROCESSING
            ),
            self::CHECK_DELIVERED, self::CHECK_SHIPPED => self::STATUS_SHIPPED,
            self::CHECK_READY_FOR_PICKUP, self::CHECK_BINNED => self::STATUS_READY_FOR_PICKUP,
            self::CHECK_COMPLETE => self::STATUS_COMPLETE,
            self::CHECK_IN_PRODUCTION => self::STATUS_DELAY,
            default => self::STATUS_PROCESSING,
        };
    }

    /**
     * Get Mirakl status details to handle status details and status icons
     * @param $orderData
     * @param $key
     * @param $trackNumber
     * @param $sellerId
     * @param $essendentToggle
     * @return string
     */
    public function getMktOrderStatusDetail($orderData, $key, $sellerId = null, $trackNumber = null): string
    {
        if ($this->marketplaceCheckoutHelper->isEssendantToggleEnabled() && $sellerId && $trackNumber) {
            $miraklStatus = strtolower($orderData[$key]['shipped_orders'][$sellerId]['items'][$trackNumber]['status']);
        } else {
            if (!isset($orderData[$key]['mkt_order_status'])) {
                return '';
            }
            $miraklStatus = strtolower($orderData[$key]['mkt_order_status']);
        }

        return match ($miraklStatus) {
            self::CHECK_SHIPPING => (
            $this->isMktOrderDelayed($orderData, $key)
                ? self::STATUS_DELAY
                : self::STATUS_PROCESSING
            ),
            self::CHECK_SHIPPED,
            self::CHECK_RECEIVED,
            self::CHECK_COMPLETE,
            self::CHECK_TO_COLLECT,
            self::CHECK_CLOSED,
            self::CHECK_REFUSED,
            self::CHECK_INCIDENT_OPEN,
            self::CHECK_REFUNDED => self::STATUS_SHIPPED,
            self::CHECK_CANCELED => self::STATUS_CANCELED,
            default => self::STATUS_ORDERED,
        };
    }

    /**
     * get Mirakl status details to handle status details and status icons
     * @param $orderData
     * @param $key
     * @return string
     */
    public function getMktOrderStatusDetailEnhancement($orderData, $key, $shopId, $status = null): string
    {
        if ($this->isOrderTrackingDeliveryDateUpdateEnable()) {
            $miraklStatus = strtolower($status);
        } else {
            $miraklStatus = strtolower($this->getMiraklOrderValue((string) $key, $shopId));
        }

        return match($miraklStatus) {
            self::CHECK_SHIPPING => (
            $this->isMktOrderDelayedEnhancement($orderData, $key, $shopId)
                ? self::STATUS_DELAY
                : self::STATUS_PROCESSING
            ),
            self::CHECK_SHIPPED,
            self::CHECK_RECEIVED,
            self::CHECK_COMPLETE,
            self::CHECK_TO_COLLECT,
            self::CHECK_CLOSED,
            self::CHECK_REFUSED,
            self::CHECK_INCIDENT_OPEN,
            self::CHECK_REFUNDED => self::STATUS_SHIPPED,
            self::CHECK_CANCELED => self::STATUS_CANCELED,
            default => self::STATUS_ORDERED,
        };
    }

    /**
     * @param $key
     * @param $shopId
     * @return array
     */
    public function getMktOrderData($key, $shopId): array
    {
        $miraklOrderData = $this->getMiraklOrderValue((string) $key, $shopId);
        return $miraklOrderData;
    }

    /**
     * Configuration Track Order URL
     *
     * @return String
     */
    public function getTrackOrderUrl()
    {
        return $this->configInterface->getValue("fedex/general/track_order_url", ScopeInterface::SCOPE_STORE);
    }

    /**
     * Configuration Legacy Track Order URL
     *
     * @return String
     */
    public function getLegacyTrackOrderUrl()
    {
        return $this->configInterface->getValue("fedex/general/legacy_track_order_url", ScopeInterface::SCOPE_STORE);
    }

    /**
     * get order details and build the response
     * @return Post[]
     */
    public function getOrderlist($orderIds)
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();

        $queryOrderArray = $orderIds;

        //order details response array
        $orderDetailResponse = [];

        //array to hold valid orders from search criteria builder
        $validOrders = [];

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $queryOrderArray, 'IN')->create();
        $searchResult = $this->orderRepository->getList($searchCriteria);

        if ($searchResult->getTotalCount() > 0) {
            $orderCollection = $searchResult->getItems();
            foreach ($orderCollection as $order) {
                array_push($validOrders, $order->getIncrementId());
                if($this->productBundleConfig->isTigerE468338ToggleEnabled()){
                    $orderItems = $order->getAllVisibleItems();
                }else{
                    $orderItems = $order->getAllItems();
                }
                $i = 0;
                $mkt_i = 0;
                $order1pCancelled = false;
                $orderItemArray = [];
                $mktOrderItemArray = [];
                $mktShippingInfo = [];
                foreach ($orderItems as $item) {
                    $productObject = $item->getProduct();
                    if ($item->getMiraklOfferId()) {
                        $miraklShopId   = $item->getMiraklShopId();
                        $mktOrderItemArray = $this->populateMktItemDataEnhancement($mktOrderItemArray, $order, $item, $miraklShopId, $productObject);
                        if ($mktOrderItemArray[$miraklShopId]['items'][count($mktOrderItemArray[$miraklShopId]['items']) - 1]['shipment_type']) {
                            $mktShippingInfo[$miraklShopId] = [
                                'delivery_date' => $mktOrderItemArray[$miraklShopId]['delivery_date'],
                                'shipment_type' => $mktOrderItemArray[$miraklShopId]['items'][count($mktOrderItemArray[$miraklShopId]['items']) - 1]['shipment_type'],
                                'status' => $mktOrderItemArray[$miraklShopId]['items'][count($mktOrderItemArray[$miraklShopId]['items']) - 1]['status']
                            ];
                        }
                        $mkt_i++;
                    } else {
                        $proPrice = $this->priceHelper->currency($item->getprice(), true, false);
                        $orderItemArray[$i] = [
                            'id'      => $item->getId(),
                            'name'      => $item->getName(),
                            'sku'       => $item->getSku(),
                            'price'     => $proPrice,
                            'imgurl'    => $this->getItemThumbnailUrl($productObject),
                            'qty'       => (int) $item->getQtyOrdered()
                        ];
                        if ($item->getProductType() === Type::TYPE_BUNDLE) {
                            $orderItemArray[$i]['is_bundle'] = true;
                            $orderItemArray[$i]['children'] = $this->getBundleChildrenItems($item);
                        }
                        if($this->isShipmentCanceledOrRefunded($order, $item->getId())) {
                            $order1pCancelled = true;
                        }
                        $i++;
                    }
                }

                if ($mkt_i > 1) {
                    $mktOrderItemArray = $this->populateMissingShippingItemInfo($mktOrderItemArray, $mktShippingInfo);
                }

                $orderShipments = $this->getShipmentByOrders($order->getIncrementId(), $mktOrderItemArray);

                $orderDetailResponse[$order->getIncrementId()] = [
                    'order_id' => $order->getIncrementId(),
                    'order_date' => date('M j, Y', strtotime($order->getCreatedAt())),
                    'order_status' => !$order1pCancelled ? $order->getStatus() : \Magento\Sales\Model\Order::STATE_CANCELED,
                    'mkt_order_status' => $this->getMiraklOrderValue($order->getIncrementId()),
                    'orderItemsTotal' => count($orderItems),
                    'orderItems' => $orderItemArray,
                    'orderMktItems' => $mktOrderItemArray,
                    'shipment_type' => $this->getShipmentType($order),
                    'pickup_address' => $this->getPickupAddress($order),
                    'delivery_date' => $this->getExpectedDeliveryDate($order),
                    'tracking_number' => $this->getTrackingNumber($order),
                    'track_order_url' => $this->getTrackOrderUrl(),
                    'cancelled_date' => $this->getCanceledDate($order),
                    'isValid' => true,
                    'shipped_orders' => $orderShipments,
                ];
            }
        }

        foreach ($queryOrderArray as $order) {
            if (!in_array($order, $validOrders)) {
                $orderDetailResponse[$order] = [
                    'order_id' => $order,
                    'isValid' => false,
                ];
            }
        }
        return $orderDetailResponse;
    }

    /**
     * @param Item $orderItem
     * @return array
     */
    protected function getBundleChildrenItems($orderItem)
    {
        $childrenItems = [];
        foreach ($orderItem->getChildrenItems() as $childItem) {
            $productObject = $childItem->getProduct();
            $proPrice = $this->priceHelper->currency($childItem->getPrice(), true, false);
            $childrenItems[] = [
                'name'      => $childItem->getName(),
                'sku'       => $childItem->getSku(),
                'price'     => $proPrice,
                'imgurl'    => $this->getItemThumbnailUrl($productObject),
                'qty'       => (int) $childItem->getQtyOrdered()
            ];
        }
        return $childrenItems;
    }

    private function processMktItemData(array $mktOrderItemArray, $order, $item, $miraklShopId, $productObject): array
    {
        return $this->populateMktItemDataEnhancement($mktOrderItemArray, $order, $item, $miraklShopId, $productObject);
    }

    /**
     * @param OrderInterface $order
     * @param $orderItemId
     * @return bool
     */
    public function isShipmentCanceledOrRefunded(OrderInterface $order, $orderItemId): bool
    {
        if (!$order->hasShipments() || !$this->isOrderTracking1PCancellationMixedCartEnable()) {
            return false;
        }

        $shipments = $order->getShipmentsCollection();
        /** @var ShipmentInterface $shipment */
        foreach ($shipments as $shipment) {

            $shipmentItems = $shipment->getAllItems();
            foreach ($shipmentItems as $shipmentItem) {

                if ($shipmentItem->getOrderItemId() == $orderItemId) {
                    $shipmentStatus = $this->shipmentHelper->getShipmentStatus($shipment->getShipmentStatus(), true);
                    if ($shipmentStatus == strtolower(self::STATUS_CANCELED)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Populate 1p data.
     *
     * @param $mktOrderItemArray
     * @param $order
     * @param $item
     * @param $mkt_i
     * @param $productObject
     * @return array
     */
    public function populateMktItemData($mktOrderItemArray, $order, $item, $mkt_i, $productObject)
    {
        $shipment = $this->shipmentHelper->getShippingByOrderAndItemId($order, $item->getId());
        $trackingNumber =  ($shipment && $shipment->getTracks() && current($shipment->getTracks()))
            ? current($shipment->getTracks())->getData('track_number') : '';
        $proPrice = $this->priceHelper->currency($item->getprice(), true, false);
        $mktOrderItemArray[$mkt_i]['name'] = $item->getName();
        $mktOrderItemArray[$mkt_i]['sku'] = $item->getSku();
        $mktOrderItemArray[$mkt_i]['price'] = $proPrice;
        $mktOrderItemArray[$mkt_i]['imgurl'] = $this->getItemThumbnailUrl($productObject);
        $mktOrderItemArray[$mkt_i]['qty'] = (int) $item->getQtyOrdered();
        $mktOrderItemArray[$mkt_i]['status'] = $shipment ? $this->shipmentHelper->getShipmentStatusByValue($shipment->getShipmentStatus()) : "";
        $mktOrderItemArray[$mkt_i]['delivery_date'] = $this->shipmentHelper->getDeliveryDateFromMktItem($item);
        $mktOrderItemArray[$mkt_i]['shipment_type'] = $this->getShipmentTypeFromMktItem($item);
        $mktOrderItemArray[$mkt_i]['tracking_number'] = $trackingNumber;
        $mktOrderItemArray[$mkt_i]['track_order_url'] = $this->getTrackOrderUrl();
        $mktOrderItemArray[$mkt_i]['seller_id'] = $item->getSellerId();
        $mktOrderItemArray[$mkt_i]['mkt_shipping_method'] = $this->getShipmentMethodTitleMktItem($item);

        return $mktOrderItemArray;
    }

    /**
     * Populate mkt item data.
     *
     * @param $mktOrderItemArray
     * @param $order
     * @param $item
     * @param $miraklShopId
     * @param $productObject
     * @return mixed
     */
    public function populateMktItemDataEnhancement($mktOrderItemArray, $order, $item, $miraklShopId, $productObject)
    {
        if ($this->toggleCodeImprovementLogic()) {

            $shipment = $this->shipmentHelper->getShippingByOrderAndItemId($order, $item->getId());
            $tracks = $shipment ? $shipment->getTracks() : null;
            $trackingNumber = $tracks ? current($tracks)->getData('track_number') : '';
            $proPrice = $this->priceHelper->currency($item->getPrice(), true, false);

            $shop = $this->shopManagement->getShopByProduct($item->getProduct());

            if (!isset($mktOrderItemArray[$miraklShopId])) {
                $miraklShopName = $shop->getSellerAltName();
                $deliveryDate = $this->shipmentHelper->getDeliveryDateFromMktItem($item);
                $trackOrderUrl = $this->getTrackOrderUrl();

                $mktOrderItemArray[$miraklShopId] = [
                    'mirakl_shop_name' => $miraklShopName,
                    'delivery_date' => $deliveryDate,
                    'track_order_url' => $trackOrderUrl,
                    'tracking_number' => $trackingNumber,
                    'items' => []
                ];
            }

            $mktOrderItemArray[$miraklShopId]['items'][] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'price' => $proPrice,
                'imgurl' => $this->getItemThumbnailUrl($productObject),
                'qty' => (int)$item->getQtyOrdered(),
                'status' => $shipment ? $this->shipmentHelper->getShipmentStatusByValue($shipment->getShipmentStatus()) : '',
                'shipment_type' => $this->getShipmentTypeFromMktItem($item),
                'seller_id' => $miraklShopId,
                'mkt_shipping_method' => $this->getShipmentMethodTitleMktItem($item)
            ];

            return $mktOrderItemArray;
        } else {

            $shipment = $this->shipmentHelper->getShippingByOrderAndItemId($order, $item->getId());
            $trackingNumber =  ($shipment && $shipment->getTracks() && current($shipment->getTracks()))
                ? current($shipment->getTracks())->getData('track_number') : '';
            $proPrice = $this->priceHelper->currency($item->getprice(), true, false);

            $shop = $this->shopManagement->getShopByProduct($item->getProduct());

            if (!isset($mktOrderItemArray[$miraklShopId])) {
                $mktOrderItemArray[$miraklShopId] = [
                    'mirakl_shop_name' => $shop->getSellerAltName(),
                    'delivery_date' => $this->shipmentHelper->getDeliveryDateFromMktItem($item),
                    'track_order_url' => $this->getTrackOrderUrl(),
                    'tracking_number' => $trackingNumber,
                    'items' => []
                ];
            }

            $mktOrderItemArray[$miraklShopId]['items'][] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'price' => $proPrice,
                'imgurl' => $this->getItemThumbnailUrl($productObject),
                'qty' => (int) $item->getQtyOrdered(),
                'status' => $shipment ? $this->shipmentHelper->getShipmentStatusByValue($shipment->getShipmentStatus()) : "",
                'shipment_type' => $this->getShipmentTypeFromMktItem($item),
                'seller_id' => $miraklShopId,
                'mkt_shipping_method' => $this->getShipmentMethodTitleMktItem($item)
            ];

            return $mktOrderItemArray;
        }
    }

    /**
     * @param $mktOrderItemArray
     * @param $mktShippingInfo
     * @return array
     */
    protected function populateMissingShippingItemInfo($mktOrderItemArray, $mktShippingInfo): array
    {
        foreach ($mktOrderItemArray as $shopId => &$shopItems) {
            if(!isset($shopItems['items'])) {
                continue;
            }
            foreach ($shopItems['items'] as $key => &$mktItem) {
                $sellerId = $mktItem['seller_id'];
                if ($this->toggleCodeImprovementLogic()) {
                    if ((!isset($mktItem['delivery_date']) || !isset($mktItem['shipment_type'])) && isset($mktShippingInfo[$sellerId])) {
                        $mktItem['delivery_date'] = $mktItem['delivery_date'] ??
                            $mktShippingInfo[$sellerId]['delivery_date'];
                        $mktItem['shipment_type'] = $mktItem['shipment_type'] ??
                            $mktShippingInfo[$sellerId]['shipment_type'];
                        $mktItem['status'] = $mktItem['status'] ??
                            $mktShippingInfo[$sellerId]['status'];
                    }
                } else {
                    if ((!$mktItem['shipment_type']) && isset($mktShippingInfo[$sellerId])) {
                        $mktOrderItemArray['delivery_date'] = $mktItem['delivery_date'] ??
                            $mktShippingInfo[$sellerId]['delivery_date'];
                        $mktOrderItemArray['shipment_type'] = $mktItem['shipment_type'] ??
                            $mktShippingInfo[$sellerId]['shipment_type'];
                        $mktOrderItemArray['status'] = $mktItem['status'] ??
                            $mktShippingInfo[$sellerId]['status'];
                    }
                }
            }
        }

        return $mktOrderItemArray;
    }

    /**
     * Get ShipmentType from mkt item.
     *
     * @param \Magento\Sales\Model\Order\ItemRepository $item
     * @return array
     */
    public function getShipmentTypeFromMktItem($item)
    {
        $additionalData = $item->getAdditionalData();
        $jsonData = json_decode($additionalData, true);

        if (isset($jsonData['mirakl_shipping_data'])) {
            $shipmentTitle = $jsonData['mirakl_shipping_data']['title'];
            $shipmentValue = (self::DELIVERY_SHIPMENT);
            return [$shipmentTitle, $shipmentValue];
        }
    }

    /**
     * Get Shipment method from mkt item.
     *
     * @param \Magento\Sales\Model\Order\ItemRepository $item
     * @return string
     */
    public function getShipmentMethodTitleMktItem($item)
    {
        $additionalData = $item->getAdditionalData();

        $jsonData = json_decode($additionalData, true);

        if (isset($jsonData['mirakl_shipping_data'])) {
            return $jsonData['mirakl_shipping_data']['method_title'];
        }
        return '';
    }

    /**
     * Get mirakl order status
     *
     * @param string $commercialId
     * @param int $shopId
     * @param int $offerId
     * @param bool $reorder
     * @return string|array
     */
    public function getMiraklOrderValue(string $commercialId, int $shopId = null, int $offerId = null, bool $reorder = false)
    {
        $value = "";
        $orders = $this->miraklHelper->getOrders(
            [
                'commercial_ids' => $commercialId,
                'shop_ids' => $shopId,
                'offer_ids' => $offerId
            ]
        );

        if (!empty($orders)) {
            foreach ($orders as $order) {
                /** @var MiraklOrder $order */
                if ($reorder) {
                    $value = $order->getId();
                } elseif ($shopId && $this->isOrderTrackingDeliveryDateUpdateEnable()){
                    $value = $order->getData();
                } else {
                    $value = $order->getStatus()->getState();
                }
                break;
            }
        }

        return $value;
    }

    private function formatShippingDescription(array $parts): array
    {
        $formattedDescription = (isset($parts[0])) ? trim($parts[0], ', ') . "-" : '';
        $formattedDescription .= (isset($parts[1])) ? $parts[1] . " " : '';
        $formattedDescription .= (isset($parts[2])) ? $parts[2] . " " : '';
        $formattedDescription .= date('Y') . "-";
        $formattedDescription .= (isset($parts[3])) ? $parts[3] . " " : '';

        if (count($parts) >= 5) {
            $formattedDescription .= (isset($parts[4])) ? $parts[4] . " " : '';
            $formattedDescription .= (isset($parts[5])) ? $parts[5] . " " : '';
        }

        return explode('-', $formattedDescription);
    }

    /**
     * Toggle for enabling Code Improvement
     * @return boolean
     */
    public function toggleCodeImprovementLogic(): bool{
        return (bool) $this->toggleConfig->getToggleConfigValue(self::CODE_IMPROVEMENT_TOGGLE) ?? false;
    }

    /**
     * Toggle for E-433689 Order Tracking Delivery Date Update (3p only)
     * @return boolean
     */
    public function isOrderTrackingDeliveryDateUpdateEnable(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XPATH_ORDER_TRACKING_DELIVERY_DATE_UPDATE);
    }

    /**
     * Toggle for D-191698 Order tracking page not showing 1p cancellation status in mixed cart scenario
     * @return boolean
     */
    public function isOrderTracking1PCancellationMixedCartEnable(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XPATH_ORDER_TRACKING_1P_CANCELLATION_MIXED_CART);
    }

    /**
     * @param $miraklOrderData
     * @return array
     */
    public function getExtendedDeliveryDate($miraklOrderData)
    {
        foreach ($miraklOrderData["order_additional_fields"]->getItems() as $item) {
            if ($item->getData('code') == 'extd-del-date' && $item->getData('value')) {
                $timestamp = strtotime($item->getData('value'));
                $dayOfWeek = date('l', $timestamp);
                $dateFormatted = date('F j, Y', $timestamp);
                $timeFormatted = date('g:ia', $timestamp);
                $resultArray = [
                    'dayOfWeek'     => $dayOfWeek,
                    'dateFormatted' =>$dateFormatted,
                    'timeFormatted' => $timeFormatted,
                ];
                return $resultArray;
            }
        }

        return [];
    }

    public function getShipmentByOrders(string $incrementId, array $mktOrderItemArray): array
    {
        if (!$this->marketplaceCheckoutHelper->isEssendantToggleEnabled()) {
            return [];
        }

        $orderShipments = $itemQtyShipped = [];

        //Get Mirakl orders by Order Increment Id
        $miraklOrders = $this->miraklHelper->getOrders(
            [
                'commercial_ids' => $incrementId
            ]
        );

        foreach ($miraklOrders as $miraklOrder) {
            $miraklOrderArray = [];
            $shopId = $miraklOrder->getShopId();
            if ($miraklOrder->getId()) {
                // ST11 - Get Shipments by Mirakl Commercial Order ID
                $shipments = $this->shipmentApi->getShipments([$miraklOrder->getId()]);
                if ($shipments && count($shipments->getCollection())) {
                    foreach ($shipments->getCollection() as $shipment) {
                        $tracking = $shipment->getData('tracking');
                        $shipmentLines = $shipment->getData('shipment_lines');
                        foreach ($shipmentLines as $shipmentItem) {
                            // Create array of order items
                            $data = [
                                'id' => $shipment->getData('order_id'),
                                'order_item_id' => $shipmentItem->getData('order_line_id'),
                                'sku' => $shipmentItem->getData('offer_sku'),
                                'qty' => $shipmentItem->getData('quantity'),
                                'status' => $shipment->getData('status')
                            ];

                            if (array_key_exists($tracking->getData('tracking_number'), $miraklOrderArray)) {
                                array_push($miraklOrderArray[$tracking->getData('tracking_number')]['items'], $data);
                            } else {
                                $miraklOrderArray[$tracking->getData('tracking_number')] = [
                                    'status' => $shipment->getData('status'),
                                    'items' => [$data]
                                ];
                            }

                            // Create array of each item and qty shipped
                            $itemQtyShipped[] = [
                                'item_id' => $shipmentItem->getData('order_line_id'),
                                'qty' => $shipmentItem->getData('quantity')
                            ];
                        }
                    }
                    // If no Shipments created, populate iems from Mirakl Array for all items
                } else {
                    $data = $mktOrderItemArray[$shopId]['items'];
                    // Todo - Fill correct status i.e Ordered or Processing
                    $miraklOrderArray['pending'] = ['status' => $miraklOrder->getStatus()->getState(),
                        'items' => $data];
                }
            }

            // Final array structure based on API response
            $orderShipments[$shopId]['items'] = $miraklOrderArray;
        }

        // Create an array of total qty shipped grouped by order items
        $groupedOrderItemQty = $this->groupOrderItemsAndQty(
            $itemQtyShipped, 'item_id', 'qty');

        // Copy required field from original order array for template rendering
        foreach ($orderShipments as $sellerId => &$seller) {
            $sellerRow = $mktOrderItemArray[$sellerId];
            $seller['mirakl_shop_name'] = $sellerRow['mirakl_shop_name'];
            $seller['delivery_date'] = $sellerRow['delivery_date'];
            $seller['track_order_url'] = $sellerRow['track_order_url'];
            $seller['tracking_number'] = $sellerRow['tracking_number'];

            foreach ($seller['items'] as &$track) {
                foreach ($track['items'] as &$item) {
                    if (isset($item['order_item_id'])) {
                        $originalItems = $sellerRow['items'];
                        foreach ($originalItems as $originalItem) {
                            if ($originalItem['id'] == $item['order_item_id']) {
                                $item['name'] = $originalItem['name'];
                                $item['price'] = $originalItem['price'];
                                $item['imgurl'] = $originalItem['imgurl'];
                                $item['shipment_type'] = $originalItem['shipment_type'];
                                $item['shipment_type'] = $originalItem['shipment_type'];
                                $item['seller_id'] = $originalItem['seller_id'];
                                $item['mkt_shipping_method'] = $originalItem['mkt_shipping_method'];
                            }
                        }
                    }
                }
            }
        }

        // Adjust array for un-shipped order items
        if ($groupedOrderItemQty) {
            foreach ($mktOrderItemArray as $sellerId => &$seller) {
                $pendingArray = [];
                foreach ($seller['items'] as &$item) {
                    $totalItemShipped = $this->filterByKey($groupedOrderItemQty, $item['id']);
                    if (isset($totalItemShipped[$item['id']])) {
                        $qtyPending = $item['qty'] - $totalItemShipped[$item['id']];
                        $item['qty'] = $qtyPending;
                        if ($qtyPending > 0) {
                            $pendingArray[] = $item;
                        }
                    }
                }
                if (count($pendingArray) > 0) {
                    $items = &$orderShipments[$sellerId]['items'];
                    $items['pending'] = ['status' => self::STATUS_SHIPPING,
                        'items' => $pendingArray];
                }
            }
        }

        return $orderShipments;
    }

    // Utility function to group array by specific keys
    private function groupOrderItemsAndQty($array, $groupByField, $sumField) {
        $result = [];

        foreach ($array as $item) {
            // Check if the grouping field exists in the item
            if (isset($item[$groupByField])) {
                $groupKey = $item[$groupByField];

                // Check if the sum field exists and is numeric
                if (isset($item[$sumField]) && is_numeric($item[$sumField])) {
                    // If the group does not exist in the result array, initialize it
                    if (!isset($result[$groupKey])) {
                        $result[$groupKey] = 0;
                    }

                    // Sum the value of the sum field
                    $result[$groupKey] += $item[$sumField];
                }
            }
        }

        return $result;
    }

    // Utility function to filter array by key
    private function filterByKey(array $array, $key) {
        // Check if the key exists in the array
        if (array_key_exists($key, $array)) {
            return [$key => $array[$key]]; // Return the filtered key-value pair
        }

        return []; // Return an empty array if the key doesn't exist
    }

    /**
     * Checks if the due date of an order has been changed.
     *
     * @param int $orderId The ID of the order to check.
     * @return bool True if the order's due date has been changed, false otherwise.
     */
    public function isOrderDueDateChanged($orderId) {
        $collection = $this->dueDateLogCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId);
        return $collection->getSize() > 0;
    }

    /**
     * Retrieves the most recent updated time for a specific shipping ID and due date.
     *
     * @param int|string $shippingId Identifier of the shipping entity.
     * @param string $dueDate The due date to filter the records.
     * @return string|null The updated time of the first matching record or null if no records are found.
     */
    public function getRecentUpdatedTime($shippingId, $dueDate) {
        $collection = $this->dueDateLogCollectionFactory->create()
            ->addFieldToFilter('shipment_id', $shippingId)
            ->addFieldToFilter('new_due_date', $dueDate);
        return $collection->getLastItem()->getUpdatedAt();
    }

    /**
     * @param $orderData
     * @param $key
     * @return bool
     * @throws \Exception
     */
    public function isOrderDueDateDelayed($orderData, $key): bool {
        $order = $this->getOrderByIncrementId->execute($orderData[$key]['order_id']);
        if (!$order) {
            return false;
        }
        $dueDateItem = $this->dueDateLogRepository->getByOrderId((int)$order->getId());
        if ($dueDateItem) {
            $newDueDate = new \DateTimeImmutable($dueDateItem->getNewDueDate());
            $oldDueDate = new \DateTimeImmutable($dueDateItem->getOldDueDate());

            return $newDueDate > $oldDueDate;
        }
        return false;
    }

    /**
     * @param ?OrderInterface $order
     * @return string|bool
     */
    public function getShippingDueDate(?OrderInterface $order): string|bool {
        if (!$this->instoreConfig->isUpdateDueDateEnabled() || !$order?->getId()) {
            return false;
        }
        $dueDateItem = $this->dueDateLogRepository->getByOrderId((int)$order->getId());
        if ($dueDateItem) {
            $shippingUpdatedDueDate = new \DateTime($dueDateItem->getNewDueDate());
            return $shippingUpdatedDueDate->format('l, F j, g:ia');
        }
        return false;
    }
}
