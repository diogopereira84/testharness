<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Helper;

use Fedex\Email\Helper\SendEmail;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement; 
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\Punchout\Helper\Data as PunchoutData;
use Fedex\Shipment\Helper\Data as ShipmentData;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Fedex\FujitsuReceipt\Model\FujitsuReceipt;
use Fedex\MarketplaceCheckout\Model\Email;
use Magento\Framework\View\LayoutInterface;
use Fedex\Shipment\Helper\OrderConfirmationTemplateProvider;
use Fedex\MarketplaceCheckout\Model\Config\Email as EmailConfig;
use Mirakl\Connector\Helper\Order as MiraklOrderHelper;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;
use Magento\Quote\Model\QuoteFactory;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface; 

/**
 * Class ShipmentEmail to send order status emails
 */
class ShipmentEmail extends AbstractHelper
{
    public const CONFIRMED_STATUS = 'confirmed';
    public const READY_FOR_PICKUP_STATUS = 'ready_for_pickup';
    public const SHIPPED_STATUS = 'shipped';
    public const DELIVERED_MULTIPLE_STATUS = 'delivered_multiple';
    public const CANCELLED_STATUS = 'cancelled';
    public const DELIVERY_DATE_UPDATED = 'delivery_date_updated';

    private const TEMPLATE_EMAIL_STATUS_XML_CONFIG_PATH = [
        'shipped' => 'fedex/transactional_email/order_shipment_delivery',
        'delivered_multiple' => 'fedex/transactional_email/order_shipment_multiple_delivery',
        'ready_for_pickup' => 'fedex/transactional_email/order_ready_for_pickup',
        'cancelled' => 'fedex/transactional_email/order_cancelled',
        'confirmed' => 'fedex/transactional_email/order_confirmed',
        'delivery_date_updated' => 'fedex/transactional_email/order_delivery_date_updated'
    ];

    public const SGC_ENABLE_EXPECTED_DELIVERY = 'sgc_enable_expected_delivery_date';
    public const TIGERS_D188299_TOGGLE = 'tigers_d194471_fix_order_emails_subtotal_to_match_front_end';
    public const FUSE_TEMPLATE_EMAIL_STATUS_XML_CONFIG_PATH = 'fedex/transactional_email/fuse_quote_order_confirmation_email_template';

    public $orderStatus = ["confirmed","cancelled","ready_for_pickup"];
    private CountryFactory $_countryFactory;

    private CONST EMAIL_TITTLE = 'FedEx Office Print On Demand';

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $configInterface
     * @param SendEmail $mail
     * @param PunchoutData $punchoutHelper
     * @param Data $helper
     * @param Session $customerSession
     * @param ProducingAddressFactory $producingAddressFactory
     * @param CountryFactory $countryFactory
     * @param TimezoneInterface $timezone
     * @param ToggleConfig $toggleConfig
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param FujitsuReceipt $fujitsuReceipt
     * @param Email $emailHelper
     * @param LayoutInterface $layout
     * @param MarketplaceRatesHelper $marketplaceRatesHelper
     * @param HeaderData $headerData
     * @param \Fedex\Shipment\Helper\OrderConfirmationTemplateProvider $templateHelper
     * @param CompanyManagementInterface $companyManager
     * @param EmailConfig $emailConfig
     * @param MiraklOrderHelper $miraklOrderHelper
     * @param HandleMktCheckout $handleMktCheckout
     * @param CheckoutHelper $checkoutHelper
     * @param QuoteFactory $quoteFactory
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param ConfigInterface $productBundleConfig
     * @param StoreManagerInterface $storeManager
     * @param OrderHistoryEnhacement $orderHistoryEnhacement
     */
    public function __construct(
        Context                                     $context,
        protected LoggerInterface                   $logger,
        protected ScopeConfigInterface              $configInterface,
        protected SendEmail                         $mail,
        protected PunchoutData                      $punchoutHelper,
        protected ShipmentData                      $helper,
        protected Session                           $customerSession,
        protected ProducingAddressFactory           $producingAddressFactory,
        CountryFactory                              $countryFactory,
        protected TimezoneInterface                 $timezone,
        protected ToggleConfig                      $toggleConfig,
        protected ShipmentRepositoryInterface       $shipmentRepository,
        private FujitsuReceipt                      $fujitsuReceipt,
        private Email                               $emailHelper,
        protected LayoutInterface                   $layout,
        private MarketplaceRatesHelper              $marketplaceRatesHelper,
        protected HeaderData                        $headerData,
        protected OrderConfirmationTemplateProvider $templateHelper,
        private CompanyManagementInterface          $companyManager,
        private readonly EmailConfig                $emailConfig,
        private MiraklOrderHelper                   $miraklOrderHelper,
        private readonly HandleMktCheckout          $handleMktCheckout,
        private CheckoutHelper                      $checkoutHelper,
        protected QuoteFactory                      $quoteFactory,
        private GraphqlApiHelper                    $graphqlApiHelper,
        protected ConfigInterface                   $productBundleConfig,
        private StoreManagerInterface               $storeManager,
        private OrderHistoryEnhacement              $orderHistoryEnhacement
    )
    {
        $this->_countryFactory = $countryFactory;
        parent::__construct($context);
    }

    /*
     * Use to send shipment status email using TAZ API
     *
     * @param string $shipmentStatus
     * @param int $orderId
     * @param int $shipmentId
     *
     * @return string
     */
    public function sendEmail($shipmentStatus, $orderId, $shipmentId = null, $track = null, $isBidding = false)
    {
        $altPickupEmail = $this->toggleConfig->getToggleConfigValue('explorers_order_email_alternate_pick_up_person');
        $order = $this->helper->getOrderById($orderId);
        $incrementId = "";
        if ($order->getId()) {
            $incrementId = $order->getIncrementId();
        }

        // D-113011 : SDE-receiving multiple order cancellation emails
        $saveShipment = false;

        if ($shipmentId) {
            $shipment = $this->getOrderShipmentByShipmentId($shipmentId);
            if ($shipment && !$this->ifStatusEmailCanBeSent($shipment, $shipmentStatus, $incrementId, $order)) {
                return true;
            }
        }

        $tokenData = $this->getTokenData();
        $authToken = $tokenData["auth_token"];
        $aouth = "Bearer=" . $tokenData["access_token"];

        if (!empty($tokenData["access_token"])) {
            $orderData = $this->getTemplateOrderData($orderId, $shipmentId, $track, $shipmentStatus);
            $isConfirmationEmailEnabled = $this->templateHelper
                ->getConfirmationstatus($shipmentStatus, $orderData["customer_id"]);
            if (!$isConfirmationEmailEnabled && $shipmentStatus == "confirmed") {
                return true;
            }
            $jdata = $this->getTemplateDataGenericTemplate($shipmentStatus, $orderData, false, $isBidding);
            if ($jdata != "") {
                $this->getCurlResponse(
                    $authToken,
                    $aouth,
                    $jdata,
                    $incrementId,
                    $orderData,
                    $shipmentStatus,
                    $order,
                    $saveShipment,
                    $shipment ?? null
                );
            }
            if ($altPickupEmail && $orderData['isPickup'] &&
                $orderData['has_alternate_contact'] && in_array($shipmentStatus, $this->orderStatus)
            ) {
                $jdata = $this->getTemplateDataGenericTemplate($shipmentStatus, $orderData, $orderData['isPickup'], $isBidding);
                if ($jdata != "") {
                    $this->getCurlResponse(
                        $authToken,
                        $aouth,
                        $jdata,
                        $incrementId,
                        $orderData,
                        $shipmentStatus,
                        $order,
                        $saveShipment,
                        $shipment ?? null
                    );
                }
            }
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Empty access token.');
        }
    }

    /**
     * Get order shipment using fxo shipment id
     * D-113011 : SDE-receiving multiple order cancellation emails
     *
     * @param int $shipmentId
     * @return ShipmentInterface|null
     */
    public function getOrderShipmentByShipmentId($shipmentId)
    {
        try {
            return $this->shipmentRepository->get($shipmentId);
        } catch (\Exception $e) {
            $this->logger->critical("Error while loading shipment " . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if shipment status email can be sent
     *
     * @param ShipmentInterface $shipment
     * @param string $shipmentStatus
     * @param string $incrementId
     * @param Order $order
     * @return bool
     */
    public function ifStatusEmailCanBeSent($shipment, $shipmentStatus, $incrementId, $order): bool
    {
        if ($this->miraklOrderHelper->isMiraklOrder($order)) {
            return true;
        }

        /**
         * For mixed order scenario cancellations,
         * only send cancellation email when both 1P and 3P are cancelled
         */
        if ($shipmentStatus == "cancelled"
            && $this->miraklOrderHelper->isMiraklOrder($order)
            && !$this->miraklOrderHelper->isFullMiraklOrder($order)) {
            return $this->helper->canMixedOrderCancelEmailsBeSent($order, $shipment);
        }

        if (($shipmentStatus == "cancelled" && (bool)$shipment->getIsCancellationEmailSent())
            || ($shipmentStatus == "delivered" && (bool)$shipment->getIsCompletionEmailSent())
            || ($shipmentStatus == "shipped" && (bool)$shipment->getIsCompletionEmailSent())) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Order ' .
                $shipmentStatus . ' email already sent for order: ' . $incrementId);

            return false;
        }

        return true;
    }

    /**
     * Get email template data based on shipment status
     *
     * @param string $shipmentStatus
     * @param array $orderData
     * @param boolean $isAlternatePickup
     * @return string
     * @throws NoSuchEntityException
     */
    public function getTemplateDataGenericTemplate(string $shipmentStatus, array $orderData, $isAlternatePickup = false, $isBidding = false): string
    {
        $emailSubjectOrderId = '';
        $emailData = null;
        $alternateContact3p = [];
        $newFijtsuToggle = $this->toggleConfig->getToggleConfigValue('new_fujitsu_receipt_approach');
        if ($shipmentStatus == static::CONFIRMED_STATUS && !$newFijtsuToggle && !$isAlternatePickup) {
            $this->fujitsuReceipt->sendFujitsuReceiptConfirmationEmail($orderData);
        }

        if ($isAlternatePickup) {
            $orderData['customer_first_name'] = $orderData['recipient_first_name'];
            $orderData['customer_last_name'] = $orderData['recipient_last_name'];
        }

        // To Get Mixed Cart AlternatePickup Contact Details
        if($isAlternatePickup && $orderData['isShipping'] && $orderData['alternate_contact']['alternatePickupEmail3p'] != null){
            $alternateContact = $orderData['alternate_contact'];
            $alternateContact3p = explode(' ',$alternateContact['name']);
            $orderData['customer_first_name'] = $alternateContact3p[0];
            $orderData['customer_last_name'] = $alternateContact3p[1];
            $orderData['recipient_first_name'] = $alternateContact3p[0];
            $orderData['recipient_last_name'] = $alternateContact3p[1];
            $orderData['recipient_email'] = $alternateContact['alternatePickupEmail3p'];
        }

        if (isset(self::TEMPLATE_EMAIL_STATUS_XML_CONFIG_PATH[$shipmentStatus])) {
            if (in_array($shipmentStatus, ['delivered', 'shipped']) &&
                isset($orderData['multiple_shipment']) && $orderData['multiple_shipment']) {
                $shipmentStatus = 'delivered_multiple';
            }
            $templateName = $this->emailConfig->getEmailTemplate($shipmentStatus, $this->getOrderId()) ?
                $this->emailConfig->getEmailTemplate($shipmentStatus, $this->getOrderId()) :
                self::TEMPLATE_EMAIL_STATUS_XML_CONFIG_PATH[$shipmentStatus];
            if ($this->emailConfig->getEmailEnabled($shipmentStatus, $this->getOrderId())) {
                $emailData = $this->emailHelper->getEmailHtml($templateName, $orderData);
            }
        }
        if($isBidding && $shipmentStatus == 'confirmed') {
            $templateName = self::FUSE_TEMPLATE_EMAIL_STATUS_XML_CONFIG_PATH;
            $emailData = $this->emailHelper->getEmailHtml($templateName, $orderData);
        }

        if ($shipmentStatus == 'confirmed' && !empty($orderData['customer_id']) &&
            !empty($this->templateHelper->getTemplateId($orderData['customer_id']))
        ) {
            $emailData = $this->templateHelper->getEmailTemplateById($orderData);
        }

        if ($this->toggleConfig->getToggleConfigValue('tigerteam_d165226_email_issues')) {
            if ($shipmentStatus == self::CONFIRMED_STATUS) {
                $emailSubjectOrderId = ' - Order Confirmation #'.$orderData['gtn'];
            } elseif ($shipmentStatus == self::READY_FOR_PICKUP_STATUS || $shipmentStatus == self::SHIPPED_STATUS || $shipmentStatus == self::DELIVERED_MULTIPLE_STATUS) {
                $emailSubjectOrderId = ' - Order Completion #'.$orderData['gtn'];
            } elseif ($shipmentStatus == self::CANCELLED_STATUS) {
                $emailSubjectOrderId = ' - Order Cancelled #'.$orderData['gtn'];
            } elseif ($shipmentStatus == self::DELIVERY_DATE_UPDATED) {
                $emailSubjectOrderId = ' - Order Pickup Date Updated #'.$orderData['gtn'];
            }
        }

        $subjectJson = $emailSubjectOrderId ? '"subject": "' . self::EMAIL_TITTLE . $emailSubjectOrderId . '",' : '';
        if ($isAlternatePickup) {
            return isset($emailData['template']) ?
                '{
            "email":{
                "from":{
                    "address":"' . $this->configInterface->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE) . '"
                },
                "to":[
                    {
                        "address":"' . $orderData['recipient_email'] . '",
                        "name":"' . $orderData['recipient_first_name'] . " " . $orderData['recipient_last_name'] . '"
                    }
                ],
                ' . $subjectJson . '
                "templateId":"generic_template",
                "templateData":"' . $this->emailHelper->convertBase64('{"messages":{"statement":"' . $this->emailHelper->minifyHtml($emailData["template"]) . '","url":"' . $this->emailHelper->getEmailLogoUrl() . '"},"order":{"contact":{"email":"' . $orderData['recipient_email'] . '"}}}') . '",
                "directSMTPFlag":"false"
            }
        }' : '';
        } else {
            return isset($emailData['template']) ?
                '{
                "email":{
                    "from":{
                        "address":"' . $this->configInterface->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE) . '"
                    },
                    "to":[
                        {
                            "address":"' . $orderData['customer_email'] . '",
                            "name":"' . $orderData['customer_first_name'] . " " . $orderData['customer_last_name'] . '"
                        }
                    ],
                    ' . $subjectJson . '
                    ' . $this->templateHelper
                ->getBccEmail($shipmentStatus, $orderData['customer_id']) . '
                    "templateId":"generic_template",
                    "templateData":"' . $this->emailHelper->convertBase64('{"messages":{"statement":"' . $this->emailHelper->minifyHtml($emailData["template"]) . '","url":"' . $this->emailHelper->getEmailLogoUrl() . '"},"order":{"contact":{"email":"' . $orderData['customer_email'] . '"}}}') . '",
                    "directSMTPFlag":"false"
                }
            }' : '';
        }
    }

    /**
     * Get dynamic order data to add in email template
     *
     * @param $orderId
     * @param $shipmentId
     * @return array|void
     */
    public function getTemplateOrderData($orderId, $shipmentId = null, $track = null, $shipmentStatus = null)
    {
        try {
            $orderData = $this->helper->getOrderById($orderId);
            $alternatePickupEmail3p = '';
            $altPickupEmailToggle = $this->toggleConfig->getToggleConfigValue('explorers_order_email_alternate_pick_up_person');
            if (!empty($orderData)) {
                $shippingAmount3p = $this->getMarketplaceShippingAmount($orderData);
                $shippingAmount3pPerItem = $this->getMarketplaceIndividualShippingAmount($orderData);
                $shipmentItems = $this->helper->getOrderItems($orderData, $shippingAmount3pPerItem);
                if (!empty($orderData->getShippingAddress())) {
                    $formattedAddressArray = $this->getFormattedShippingAddressArray($orderData);
                    $formattedShippingAddress = $formattedAddressArray['formattedShippingAddress'];
                    $recipientFirstName = $orderData->getShippingAddress()->getData("firstname");
                    $recipientLastName = $orderData->getShippingAddress()->getData("lastname");

                    $recipientEmail = $orderData->getShippingAddress()->getData("email");
                    $recipientPhone = $orderData->getShippingAddress()->getData("telephone");

                } else {
                    $formattedShippingAddress = "";
                    $recipientFirstName = "";
                    $recipientLastName = "";
                    $recipientEmail = '';
                    $recipientPhone = '';
                }

                $collection = $this->producingAddressFactory->create()->getCollection();

                $filterField = $shipmentId ? 'shipment_id' : 'order_id';
                $filterValue = $shipmentId ?: $orderId;

                $collection->addFieldToFilter($filterField, $filterValue);

                $producingAddress = $collection->getItems();

                $producingStoreDataArray = $this->getProducingStoreDataArray($producingAddress);
                $producingStoreAddress = $producingStoreDataArray['producingStoreAddress'];
                $producingStorePhoneNumber = $producingStoreDataArray['producingStorePhoneNumber'];
                $producingStoreEmailAddress = $producingStoreDataArray['producingStoreEmailAddress'];
                $shipment = $shipmentId ? $this->helper->getShipmentById($shipmentId) : null;
                if (!empty($shipment)) {
                    $fxoWorkOrderNumber = $shipment->getFxoWorkOrderNumber();
                    $orderCompletionDate = $shipment->getOrderCompletionDate();
                    $estimatedDeliveryDuration = $shipment->getEstimatedDeliveryDuration();
                    $shippingAccountNumber = $shipment->getShippingAccountNumber();
                    $shippingAccountNumberValue = substr((string)$shippingAccountNumber, -4);
                    $dateCompletion = $this->getDateCompletion($orderCompletionDate, $estimatedDeliveryDuration);
                } else {
                    $fxoWorkOrderNumber = "NA";
                    $orderCompletionDate = "";
                    $shippingAccountNumber = "";
                    $shippingAccountNumberValue = "";
                    $dateCompletion = "NA";
                }
                $orderTemplateData = [];

                $orderTemplateData["retail_transaction_id"] = $orderData->getPayment()
                        ->getRetailTransactionId();
                $orderTemplateData["isShipping"] = $shipment ? true : false;
                $orderTemplateData["gtn"] = $orderData->getIncrementId();
                $orderTemplateData["customer_id"] = (int)$orderData->getCustomerId();
                $orderTemplateData["jobnumber"] = $fxoWorkOrderNumber;
                $orderTemplateData["order_completion_date"] = $dateCompletion;
                $orderTemplateData["subtotal"] =
                    (float)$orderData->getSubtotal() - (float)$orderData->getDiscountAmount();
                $orderTemplateData["tax_amount"] = (float)$orderData->getCustomTaxAmount();
                $orderTemplateData["total"] = (float)$orderData->getGrandTotal();
                $orderTemplateData["shipping_amount"] = (float)$orderData->getShippingAmount();
                $orderTemplateData["shipping_tax_amount"] = (float)$orderData->getShippingTaxAmount();
                $orderTemplateData['payment_type'] = $this->getPaymentType($orderData, $orderTemplateData);
                $orderTemplateData["shipping_account_number"] = $shippingAccountNumberValue;

                $orderTemplateData["customer_first_name"] =
                    $this->escapeCharacter($orderData->getCustomerFirstName());
                $orderTemplateData["customer_last_name"] =
                    $this->escapeCharacter($orderData->getCustomerLastName());
                $orderTemplateData["recipient_first_name"] = $this->escapeCharacter($recipientFirstName);
                $orderTemplateData["recipient_last_name"] = $this->escapeCharacter($recipientLastName);
                $orderTemplateData["shipping_address"] = $this->escapeCharacter($formattedShippingAddress);
                $orderTemplateData["producing_store_address"] = $this->escapeCharacter($producingStoreAddress);

                $orderTemplateData["customer_email"] = $orderData->getCustomerEmail();
                $orderTemplateData["shipment_items"] = $shipmentItems;
                $orderTemplateData["delivery_method"] = ltrim((string)$orderData->getShippingDescription(), "FedEx");
                $orderTemplateData["producing_store_phonenumber"] = $producingStorePhoneNumber;
                $orderTemplateData["producing_store_email_address"] = $producingStoreEmailAddress;
                $orderTemplateData["B-2088132-toggle-po-number-email"] = (bool) $this->toggleConfig->getToggleConfigValue('b_2088132_toggle_po_number_email');
                if ($this->toggleConfig->getToggleConfigValue('b_2088132_toggle_po_number_email')) {
                    $orderTemplateData["po_number"] = $orderData->getPayment()->getPoNumber() ? $orderData->getPayment()->getPoNumber() : "";
                }
                $isMixedOrder = false;
                if ($this->miraklOrderHelper->isMiraklOrder($orderData) && !$this->miraklOrderHelper->isFullMiraklOrder($orderData)) {
                    $isMixedOrder = true;
                }

                /** Contact info updated with producing store phone number */
                if (!$this->miraklOrderHelper->isMiraklOrder($orderData) && !$isMixedOrder) {
                    $update_contact_info = $this->toggleConfig->getToggleConfigValue('update_contact_info');

                    if ($update_contact_info && (!$producingStorePhoneNumber || $producingStorePhoneNumber == 'NA')) {
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Production store phone number is not map with order '.$orderId);
                    }

                    if ($update_contact_info && $producingStorePhoneNumber && $producingStorePhoneNumber != 'NA') {
                        $orderTemplateData["producingstore_phonenumber"] = $producingStorePhoneNumber;
                    } else {
                        $orderTemplateData["producingstore_phonenumber"] = '';
                    }
                    if ($update_contact_info && $producingStoreEmailAddress && $producingStoreEmailAddress != 'NA') {
                        $orderTemplateData["producingstore_email_address"] = $producingStoreEmailAddress;
                    } else {
                        $orderTemplateData["producingstore_email_address"] = '';
                    }
                }

                $hasAlternateContact = $this->hasAlternateContact($orderData);
                $formattedAddressArray = $this->getFormattedAddressArray($orderData, $hasAlternateContact);
                $formattedAlternateContact = $formattedAddressArray['formattedAlternateContact'];
                $formattedShippingAddress = $formattedAddressArray['formattedShippingAddress'];
                $formattedPickupAddress = $formattedAddressArray['formattedPickupAddress'];
                $alternatePickupEmail3p = $formattedAddressArray['alternatePickupEmail3p'];
                if($altPickupEmailToggle && $alternatePickupEmail3p) {
                    $formattedAlternateContact['alternatePickupEmail3p'] = $alternatePickupEmail3p;
                }
                $isShipping = $formattedAddressArray['isShipping'];
                $isPickup = $formattedAddressArray['isPickup'];


                $orderTemplateData["alternate_contact"] = $formattedAlternateContact;
                $orderTemplateData["shipping_address"] = [];
                $orderTemplateData["shipping_address"]["address"] = isset($formattedShippingAddress["address"]) ?
                    $this->escapeCharacter($formattedShippingAddress["address"]) : null;
                $orderTemplateData["shipping_address"]["phone"] = isset($formattedShippingAddress["phone"]) ?
                    $this->escapeCharacter($formattedShippingAddress["phone"]) : null;
                $orderTemplateData["shipping_address"]["name"] = isset($formattedShippingAddress["name"]) ?
                    $this->escapeCharacter($formattedShippingAddress["name"]) : null;
                $orderTemplateData["shipping_address"]["company"] = isset($formattedShippingAddress["company"]) ?
                    $this->escapeCharacter($formattedShippingAddress["company"]) : null;
                $orderTemplateData["shipping_address"]["email"] = $formattedShippingAddress["email"] ?? null;

                $orderTemplateData["pickup_address"] = is_array($formattedPickupAddress) ?
                    $this->escapeCharacter($formattedPickupAddress["address"]) : null;
                $orderTemplateData["order_completion_date"] = $this->getUpdatedOrderCompletionDate($shipmentStatus, $orderData);
                $orderTemplateData["has_alternate_contact"] = $hasAlternateContact;
                $orderTemplateData["isPickup"] = $isPickup;
                $orderTemplateData["isShipping"] = $isShipping;
                $orderTemplateData["showCustomerInfo"] = !$isShipping || !$isPickup;
                $showTrackStatusCta = false;
                if (!empty($shipment)) {

                    if($this->marketplaceRatesHelper->isd216504toggleEnabled()){
                        $orderTemplateData['tracking_number'] = $this->checkoutHelper->isEssendantToggleEnabled()
                            ? ($track ?? null)
                            : $this->helper->getFirstTrackingNumber($shipmentId);
                    } else {
                        $orderTemplateData['tracking_number'] = $this->checkoutHelper->isEssendantToggleEnabled()
                            ? ($track['tracking_number'] ?? null)
                            : $this->helper->getFirstTrackingNumber($shipmentId);
                    }

                    $orderTemplateData['tracking_url'] = $this->getTrackOrderUrl();
                    $showTrackStatusCta = true;
                }
                $orderTemplateData['order_date'] = $this->emailHelper->getFormattedCstDate($orderData->getCreatedAt());
                $orderTemplateData['shipment_items_count'] = count($shipmentItems);
                $orderTemplateData['discount'] = (float)$orderData->getDiscountAmount();
                $orderTemplateData['cc_last_4'] = $orderData->getPayment()->getCcLast4();
                $orderTemplateData['account_number'] = $orderData->getPayment()->getFedexAccountNumber() ? substr($orderData->getPayment()->getFedexAccountNumber(), -4) : '';
                if (!empty($orderData->getBillingAddress())) {
                    $customerBillingPhone = $orderData->getBillingAddress()->getData('telephone');
                } else {
                    $customerBillingPhone = '';
                }
                $orderTemplateData['customer_phone'] = $customerBillingPhone;
                $orderTemplateData['recipient_email'] = $recipientEmail;
                $orderTemplateData['recipient_phone'] = $recipientPhone;
                $street = $orderData->getBillingAddress()->getData("street");
                $city = $orderData->getBillingAddress()->getData("city");
                $region = $orderData->getBillingAddress()->getData("region");
                $countryId = $orderData->getBillingAddress()->getData("country_id");
                $postcode = $orderData->getBillingAddress()->getData("postcode");
                if ($countryId) {
                    $country = $this->_countryFactory->create()->loadByCode($countryId);
                    $countryName = $country->getName();
                }
                $orderTemplateData["billing_address"] = $this->getFormattedBillingAddressArray(
                    $orderData,
                    $street,
                    $city,
                    $region,
                    $postcode,
                    $countryName
                );
                $orderTemplateData["billing_name"] = $orderData->getPayment()->getCcOwner() ??
                    $orderData->getBillingAddress()->getData("firstname") . ' ' .
                    $orderData->getBillingAddress()->getData("lastname");

                    $totalDetails['total'] = (float)$orderData->getGrandTotal();
                    if (!empty($shippingAccountNumber)) {
                        $totalDetails['total'] = (float)$orderData->getGrandTotal() - (float)$orderData->getShippingAmount();
                    }

                    $subtotal = (float) $orderData->getSubtotal();
                    $subTotalToApply = $subtotal;

                    if ($this->toggleConfig->getToggleConfigValue(self::TIGERS_D188299_TOGGLE)) {
                        $productTotal = array_reduce($shipmentItems, function ($carry, $item) {

                            if (!empty($item['is_child'])) {
                                return $carry;
                            }

                            return $carry + (float) ($item['row_total'] ?? 0);
                        }, 0.0);

                        if ($productTotal > 0) {
                            $subTotalToApply = $productTotal;
                        }
                    } else {
                        $subTotalToApply = $subtotal - (float) $orderData->getDiscountAmount();
                    }

                    $totalDetails['sub_total'] = $subTotalToApply;
                    $totalDetails['discount'] = (float)$orderData->getDiscountAmount();
                    if ($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                        $totalDetails['discounts'] = [
                            ['label' => 'Account Discount', 'price' => (float)$orderData->getAccountDiscount()],
                            ['label' => 'Bundle Discount', 'price' => (float)$orderData->getBundleDiscount()],
                            ['label' => 'Volume Discount', 'price' => (float)$orderData->getVolumeDiscount()],
                            ['label' => 'Promo Discount', 'price' => (float)$orderData->getPromoDiscount()],
                            ['label' => 'Shipping Discount', 'price' => (float)$orderData->getShippingDiscount()]
                        ];
                    } else {
                        $totalDetails['discounts'] = [
                            ['label' => 'Account Discount', 'price' => (float)$orderData->getAccountDiscount()],
                            ['label' => 'Volume Discount', 'price' => (float)$orderData->getVolumeDiscount()],
                            ['label' => 'Promo Discount', 'price' => (float)$orderData->getPromoDiscount()],
                            ['label' => 'Shipping Discount', 'price' => (float)$orderData->getShippingDiscount()]
                        ];
                    }
                    $totalDetails['tax'] = (float)$orderData->getCustomTaxAmount();

                    $totalDetails['shipping'] = 0;
                    $totalDetails['shipping_estimate'] = 0;
                    if (!empty($shippingAccountNumber)) {
                        $totalDetails['shipping_estimate'] = (float)$orderData->getShippingAmount();
                        if ($this->miraklOrderHelper->isMiraklOrder($orderData)) {
                            $totalDetails['shipping'] = $shippingAmount3p;
                        }
                    } else {
                        $totalDetails['shipping'] = (float)$orderData->getShippingAmount() + $shippingAmount3p;
                    }
                $orderTemplateData["item_html"] = $this->orderItemHtml($shipmentItems, $totalDetails);
                $orderTemplateData['multiple_shipment'] = $shipmentId && $this->helper->isMultipleShipment(
                    $orderData,
                    $shipmentId,
                    $this->miraklOrderHelper->isMiraklOrder($orderData)
                );
                $orderTemplateData['is_expected_delivery_enabled'] = (bool) $this->toggleConfig->getToggleConfigValue(
                    self::SGC_ENABLE_EXPECTED_DELIVERY
                );
                $orderTemplateData['show_track_status_cta'] = $showTrackStatusCta &&
                    $orderTemplateData['is_expected_delivery_enabled'];
                if(!$orderTemplateData['is_expected_delivery_enabled'] && $orderTemplateData["producingstore_phonenumber"]) {
                    $orderTemplateData["producingstore_phonenumber_display"] = true;
                } else {
                    $orderTemplateData["producingstore_phonenumber_display"] = false;
                }
                if (!$orderTemplateData['is_expected_delivery_enabled'] && !$orderTemplateData["producingstore_phonenumber"]) {
                    $orderTemplateData["producingstore_regular_number_display"] = true;
                } else {
                    $orderTemplateData["producingstore_regular_number_display"] = false;
                }
                $orderTemplateData['essendant_toggle'] = $this->checkoutHelper->isEssendantToggleEnabled();
                $orderTemplateData['only_non_customizable'] = $this->checkoutHelper->isEssendantToggleEnabled() &&
                    $this->checkoutHelper->checkIfItemsAreAllNonCustomizableProduct($orderData);
                $orderTemplateData['contact_support_url'] = $this->storeManager->getStore()->getBaseUrl() . 'contact-support';
                $orderTemplateData["order_details_html"] = $this->orderDetailsHtml($orderTemplateData);
                $orderTemplateData["is_full_1p_order"] =  !$this->miraklOrderHelper->isMiraklOrder($orderData);
                $orderTemplateData["footer_messages_html"] = $this->footerMessagesHtml($orderTemplateData, $shipmentStatus);
                $orderTemplateData["quote_id"] = "";
                $orderTemplateData["quote_is_bid"] = "";

                return $orderTemplateData;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Taz Email Url
     *
     * @return string
     */
    public function getTazEmailUrl()
    {
        return $this->configInterface->getValue("fedex/taz/taz_email_api_url");
    }

    /**
     * Get Configuration Track Order URL
     *
     * @return string
     */
    public function getTrackOrderUrl()
    {
        return $this->configInterface->getValue("fedex/general/track_order_url", ScopeInterface::SCOPE_STORE);
    }

    /*
     * Get token data to integrate email
     *
     * @return array
     */
    public function getTokenData()
    {
        try {
            $tokenData = [];
            $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
            $tokenData['auth_token'] = $gatewayToken;
            $tazToken = $this->punchoutHelper->getTazToken(false, true);
            $tokenData['access_token'] = $tazToken;

            return $tokenData;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Remove escape character from string
     *
     * @param string|null $value
     *
     * @return string
     */
    public function escapeCharacter($value = null)
    {
        $value = preg_replace("/[^a-zA-Z0-9,]+/", " ", trim($value));

        return $value;
    }

    /**
     * Get curl response
     */
    public function getCurlResponse(
        $authToken,
        $aouth,
        $jdata,
        $incrementId,
        $orderData,
        $shipmentStatus,
        $order,
        $saveShipment,
        $shipment
    )
    {
        $setupURL = $this->getTazEmailUrl();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            $authHeaderVal . $authToken,
            "Cookie: " . $aouth,
        ];
        $jdata = preg_replace("/\n/", "", $jdata);
        $this->logEmailData($jdata);
        $ch = curl_init($setupURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API before request for order: ' . $incrementId);
        try {
            $output = curl_exec($ch);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API after request for order: ' . $incrementId . ' output response: ' . json_encode($output, true));
            if ($output === false) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API after request false for order: ' . $incrementId);
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ':' . 'Taz Email API Error: ' .
                    $incrementId . ',' . $orderData['customer_email'] . ' : ' . curl_error($ch));
                return 'Curl error: ' . curl_error($ch);
            } else {
                $response = curl_getinfo($ch);
                curl_close($ch);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Order ID: ' . $incrementId);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Response Info: ' . $incrementId . " " . json_encode($response, true));
                if ($response['http_code'] == "200") {
                    if ($shipmentStatus == "confirmed") {
                        $order->setEmailSent(true);
                        $order->save();
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API Successfully shipment status confirmed for order: ' . $incrementId . ' output response: '. json_encode($response, true));
                    }
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API Successfully Response 200 for order: ' . $incrementId . ' output response: '. json_encode($response, true));
                    // D-113011 : SDE-receiving multiple order cancellation emails
                    if ($shipment) {
                        // Mixed order cancellations
                        $mixedShipments = [];
                        if ($this->isMixedOrderCancellations($shipmentStatus, $order)) {
                            $mixedShipments = $this->helper->getShipmentIds($order->getId());
                        }
                        $this->handlingSdeEmailIssue($shipment, $shipmentStatus, $saveShipment, $incrementId, $mixedShipments);
                    }
                } else {
                        $responseData = json_decode($output, true);
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API ERROR response != 200 for order: ' . $incrementId . ' output response: '. json_encode($output, true));
                        $errorMessage = '';
                        if ($responseData && isset($responseData['errors'])) {
                            $errorMessage = $responseData['errors'][0]['message'] ?? null;
                            $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API ERROR response != 200 for order: ' . $incrementId . ' output response: ' . $errorMessage);
                            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ':' .
                                'Taz Email API Error: ' . $incrementId . ',' . $orderData['customer_email'] .
                                ' : ' . $errorMessage);
                        } elseif (!$responseData) {
                            throw new \Exception($output);
                        }
                        return 'Taz Email API Error: ' . $errorMessage;
                }
            }
        } catch (\Exception $e) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . '  TAZ API ERROR EXCEPTION for order:' . $incrementId . ' output message: '. $e->getMessage());
            $this->logger->critical(
                "Taz Email API Error: " . $orderData['customer_email'] . ' : ' . $e->getMessage()
            );
        }
        return true;
    }

    /**
     * Handling the SDE Shipment email Issue
     */
    public function handlingSdeEmailIssue($shipment, $shipmentStatus, $saveShipment, $incrementId, $mixedShipments)
    {
        // Mixed Order
        if (!empty($mixedShipments)) {
            foreach ($mixedShipments as $shipmentId) {
                $shipment = $this->helper->getShipmentById($shipmentId);
                $shipment->setIsCancellationEmailSent(true);
                $this->shipmentRepository->save($shipment);
            }
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Email sent successfully for order: ' .
                $incrementId);
            return "sent";
        }

        // 1P or 3P only
        if ($shipment && $shipmentStatus == "cancelled") {
            $shipment->setIsCancellationEmailSent(true);
            $saveShipment = true;
        } elseif ($shipmentStatus == "shipped" || $shipmentStatus == "delivered") {
            $shipment->setIsCompletionEmailSent(true);
            $saveShipment = true;
        }
        if ($saveShipment) {
            $this->shipmentRepository->save($shipment);
        }

        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Email sent successfully for order: ' .
            $incrementId);
        return "sent";
    }

    /**
     * Get Shipment Itmes List
     */
    public function getShipmentItemsList($orderData, $shipmentItemsList, $i)
    {
        foreach ($orderData["shipment_items"] as $value) {
            if ($i == 1) {
                $shipmentItemsList = $shipmentItemsList .
                    '{\"name\": \"' . $value["name"] . '\",\"quantity\": \"' . (int)$value["qty"] . '\"}';
            } else {
                $shipmentItemsList = $shipmentItemsList . "," .
                    '{\"name\": \"' . $value["name"] . '\",\"quantity\": \"' . (int)$value["qty"] . '\"}';
            }
            if ($i == count($orderData["shipment_items"])) {
                $shipmentItemsList = $shipmentItemsList . "]";
            }
            $i = $i + 1;
        }

        return $shipmentItemsList;
    }

    /**
     * Get Formatted Shipping Address Array
     */
    public function getFormattedShippingAddressArray($orderData): array
    {
        $street = $orderData->getShippingAddress()->getData("street");
        $city = $orderData->getShippingAddress()->getData("city");
        $region = $orderData->getShippingAddress()->getData("region");
        $countryId = $orderData->getShippingAddress()->getData("country_id");
        $postcode = $orderData->getShippingAddress()->getData("postcode");

        if ($countryId) {
            $country = $this->_countryFactory->create()->loadByCode($countryId);
            $countryName = $country->getName();
        }

        $isShipping = $isPickup = false;
        $formattedShippingAddress = "NA";

        if ($orderData->getShippingMethod() != "fedexshipping_PICKUP") {
            $isShipping = true;
        } else {
            $isPickup = true;

            if ($orderData->getMiraklIsOfferInclTax() !== null) {
                foreach ($orderData->getItems() as $item) {
                    if ($item->getMiraklOfferId()) {
                        $isShipping = true;
                        break;
                    }
                }
            }
        }

        $formattedShippingAddress = $street . ", " . $city . ", " .
            $region . ", " . $countryName . ", " . $postcode;


        return [
            'formattedShippingAddress' => $formattedShippingAddress,
            'isShipping' => $isShipping,
            'isPickup' => $isPickup
        ];
    }

    /**
     * Get Formatted Addresses Array
     * @param $orderData
     * @param bool $hasAlternateContact
     * @return array
     */
    public function getFormattedAddressArray($orderData, bool $hasAlternateContact): array
    {
        $isShipping = $isPickup = $has3p = false;
        $formattedShippingAddress = $formattedPickupAddress = "NA";
        $formattedAlternateContact = null;
        $pickupAddressDetails = [];
        $alternatePickupEmail3p = '';
        $altPickupEmailToggle = $this->toggleConfig->getToggleConfigValue('explorers_order_email_alternate_pick_up_person');

        if ($orderData->getShippingMethod() == "fedexshipping_PICKUP") {
            $isPickup = true;
            $formattedPickupAddress = $this->getFormattedBillingShipping($orderData);
        } else {
            $isShipping = true;
            $formattedShippingAddress = $this->getFormattedBillingShipping($orderData);
        }

        foreach ($orderData->getItems() as $item) {
            if ($item->getMiraklOfferId()) {
                $has3p = true;
                break;
            }
        }

        if ($has3p && $orderData->getShippingMethod() == "fedexshipping_PICKUP") {
            $isShipping = true;
            $formattedShippingAddress = $hasAlternateContact ?
                $this->getFormattedBillingShipping($orderData, true) :
                $this->getFormattedPickup($orderData);
            $formattedAlternateContact = $hasAlternateContact ?
                $this->getFormattedBillingShipping($orderData) : null;
                if($altPickupEmailToggle){
                    $pickupAddressDetails = $this->getFormattedPickup($orderData);
                }
        }
        // To Get Mixed Cart AlternatePickup Contact Details
        if($altPickupEmailToggle && $has3p && isset($pickupAddressDetails['email'])) {
            $alternatePickupEmail3p= $pickupAddressDetails['email'];
        }
        return [
            'formattedAlternateContact' => $formattedAlternateContact,
            'formattedShippingAddress' => $formattedShippingAddress,
            'formattedPickupAddress' => $formattedPickupAddress,
            'isShipping' => $isShipping,
            'isPickup' => $isPickup,
            'alternatePickupEmail3p' => $alternatePickupEmail3p
        ];
    }

    /**
     * @param $orderData
     * @param bool $alternateContact
     * @return array
     */
    public function getFormattedBillingShipping($orderData, bool $alternateContact = false): array
    {
        $address = $alternateContact ? $orderData->getBillingAddress() : $orderData->getShippingAddress();

        $firstName = $address->getData("firstname");
        $lastName = $address->getData("lastname");
        $company = $address->getData("company");

        $street = $address->getData("street");
        $city = $address->getData("city");
        $region = $address->getData("region");
        $postcode = $address->getData("postcode");
        $countryId = $address->getData("country_id");

        if ($countryId) {
            $country = $this->_countryFactory->create()->loadByCode($countryId);
            $countryName = $country->getName();
        }

        return [
            'company' => $company,
            'name' => $firstName . " " . $lastName,
            'address' => $street . ", " . $city . ", " . $region . ", " . ($countryName ?? $countryId) . ", ". $postcode
        ];
    }

    /**
     * @param $orderData
     * @return array|string[]
     */
    public function getFormattedPickup($orderData): array
    {
        $miraklAddress = [];

        foreach ($orderData->getAllVisibleItems() as $orderItem) {
            $additionalDataAsObject = json_decode($orderItem->getAdditionalData() ?? '{}');
            if (!property_exists($additionalDataAsObject, 'mirakl_shipping_data')) {
                continue;
            }
            try {
                if (isset($additionalDataAsObject->mirakl_shipping_data) &&
                    isset($additionalDataAsObject->mirakl_shipping_data->address)) {
                    $miraklShippingAddress = $additionalDataAsObject->mirakl_shipping_data->address;
                    foreach ($miraklShippingAddress as $key => $value) {
                        $miraklAddress[$key] = $value;
                    }
                    break;
                }
            } catch (Exception) {
                continue;
            }
        }

        if (count($miraklAddress)) {
            $hasAlternateContact = isset($miraklAddress['altFirstName']) && $miraklAddress['altFirstName'] !== "";
            $miraklAddress['street'] = trim(join(',', $miraklAddress['street']),',');

            $firstName = $hasAlternateContact ? $miraklAddress['altFirstName'] : $miraklAddress['firstname'];
            $lastName = $hasAlternateContact ? $miraklAddress['altLastName'] : $miraklAddress['lastname'];
            $telephone = $hasAlternateContact ? $miraklAddress['altPhoneNumber'] : $miraklAddress['telephone'];
            $email = $miraklAddress['altEmail'] ?? '';

            if ($email === '') {
                foreach ($miraklAddress['customAttributes'] as $attribute) {
                    if ($attribute->attribute_code === 'email_id' && $attribute->value !== '') {
                        $email = $attribute->value;
                    }
                }
            }


            return [
                'company' => null,
                'address' => $miraklAddress['street'] . ", " . $miraklAddress['city'] . ", " .
                    $miraklAddress['region'] . ", " . $miraklAddress['postcode'] . " " . $miraklAddress['countryId'],
                'email' => $email,
                'name' => $firstName . ' ' . $lastName,
                'phone' => $telephone
            ];
        }

        return ['address' => 'NA'];
    }

    /**
     * Get Producing Store Data Array
     */
    public function getProducingStoreDataArray($producingAddress)
    {
        $producingStoreAddress = "NA";
        $producingStorePhoneNumber = "NA";
        $producingStoreEmailAddress = "NA";
        if (!empty($producingAddress)) {
            foreach ($producingAddress as $producingAddressData) {
                $producingStoreAddress = $producingAddressData->getAddress();
                $producingStorePhoneNumber = $producingAddressData->getPhoneNumber();
                $producingStoreEmailAddress = $producingAddressData->getEmailAddress();
            }
        }

        return [
            'producingStoreAddress' => $producingStoreAddress,
            'producingStorePhoneNumber' => $producingStorePhoneNumber,
            'producingStoreEmailAddress' => $producingStoreEmailAddress
        ];
    }

    /**
     * Get Date Completion
     */
    public function getDateCompletion($orderCompletionDate, $estimatedDeliveryDuration)
    {
        if ($orderCompletionDate) {
            $dateEmail = date('M j, Y', strtotime($orderCompletionDate));
            $timeEmail = date('g:i A', strtotime($orderCompletionDate));
            $dateCompletion = $dateEmail . " at " . $timeEmail;
        } elseif ($estimatedDeliveryDuration) {
            $dateCompletion = $estimatedDeliveryDuration;
        } else {
            $dateCompletion = "NA";
        }

        return $dateCompletion;
    }

    /**\
     * Get Payment Name
     */
    public function getPaymentName($orderData)
    {
        $paymentName = "Credit Card";
        if ($orderData->getPayment()->getMethod() == "fedexaccount") {
            $paymentName = "FedEx Office Print Account";
        }

        return $paymentName;
    }

    /**
     * Get Payment Type
     */
    public function getPaymentType($orderData)
    {
        $paymentName = "";
        if (!empty($orderData->getPayment())) {
            $paymentName = $this->getPaymentName($orderData);
        }

        return $paymentName;
    }

    /**
     * @param $shipmentItems
     * @param $totalDetails
     * @return mixed
     */
    public function orderItemHtml($shipmentItems, $totalDetails)
    {
        return $this->layout->createBlock(\Fedex\MarketplaceCheckout\Block\Order\Email\Items::class)
            ->setName('fedex_pickup_order_items')
            ->setArea('frontend')
            ->setData('shipment_items', $shipmentItems)
            ->setData('total_details', $totalDetails)
            ->toHtml();
    }

    /**
     * @param $orderDetailsData
     * @return mixed
     */
    public function orderDetailsHtml($orderDetailsData)
    {
        return $this->layout->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setArea('frontend')
            ->setTemplate('Fedex_Shipment::email/orderDetails.phtml')
            ->setData($orderDetailsData)
            ->toHtml();
    }

    /**
     * @param $orderDetailsData
     * @param $shipmentStatus
     * @return mixed
     */
    public function footerMessagesHtml($orderDetailsData, $shipmentStatus = null)
    {
        return $this->layout->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setArea('frontend')
            ->setTemplate('Fedex_Shipment::email/footerMessages.phtml')
            ->setData($orderDetailsData)
            ->setStatus($shipmentStatus)
            ->toHtml();
    }

    /**
     * Get Formatted Billing Address Array
     */
    public function getFormattedBillingAddressArray(
        $orderData,
        $street,
        $city,
        $region,
        $postcode,
        $countryName
    ): string
    {
        return rtrim($street, ',') . ", " . $city . ", " . $region . ", " . $countryName . ", " . $postcode;
    }

    /**
     * @param $orderData
     * @return bool
     */
    private function hasAlternateContact($orderData): bool
    {
        $shippingAddress = $orderData->getShippingAddress();
        $billingAddress = $orderData->getBillingAddress();

        return strtolower($billingAddress->getFirstName() ?? '') != strtolower($shippingAddress->getFirstName() ?? '') ||
            strtolower($billingAddress->getLastName() ?? '') != strtolower($shippingAddress->getLastName() ?? '') ||
            strtolower($billingAddress->getEmail() ?? '') != strtolower($shippingAddress->getEmail() ?? '') ||
            strtolower($billingAddress->getTelephone() ?? '') != strtolower($shippingAddress->getTelephone() ?? '');
    }

    /**
     * Function to get bcc email
     *
     * @param string $shipmentStatus
     * @param int $customerid
     * @return string
     */
    public function getBccEmail($shipmentStatus, $customerid)
    {
        $company = null;
        $bccEmail = '';
        if (!empty($customerid)) {
            $company = $this->companyManager->getByCustomerId($customerid);
        }
        if ($company != null && $shipmentStatus == 'confirmed') {
            $commaSeperatedEmail = $company->getBccCommaSeperatedEmail();
            if ($commaSeperatedEmail != '') {
                $bccEmailArrays = explode(',', $commaSeperatedEmail);
                $emailString = '';
                foreach ($bccEmailArrays as $bcc) {
                    $bcc = trim($bcc);
                    $emailJson =
                        '{
                        "address":"' . $bcc . '"
                    }';
                    if ($emailString == '') {
                        $emailString =
                            $emailString . $emailJson;
                    } else {
                        $emailString =
                            $emailString . ',
                        ' . $emailJson;
                    }
                }
                $bccEmail =
                    '"bcc":[
                    ' . $emailString . '
                ],';
            }
        }
        return $bccEmail;
    }

    /**
     * Check OrderConfirmation Enabled for Company
     *
     * @param string $shipmentStatus
     * @param int $customerid
     * @return bool
     */
    public function getConfirmationstatus($shipmentStatus, $customerid)
    {
        $company = null;
        $status = true;
        if ($customerid != null) {
            $company = $this->companyManager->getByCustomerId($customerid);
        }
        if ($company != null && $shipmentStatus == 'confirmed') {
            $status = $company->getIsSuccessEmailEnable();
        }
        return $status;
    }

    /**
     * @return mixed
     */
    private function getOrderId(): mixed
    {
        return $this->helper->order->getId();
    }

    /**
     * Gets marketplace shipping total for the order
     *
     * @param Order $order
     * @return float
     */
    private function getMarketplaceShippingAmount(Order $order): float
    {
        return $this->marketplaceRatesHelper->getMktShippingTotalAmount($order) ?? 0;
    }

    /**
     * Gets marketplace individual shipping total for each seller
     *
     * @param Order $order
     * @return array
     */
    private function getMarketplaceIndividualShippingAmount(Order $order): array
    {
        return $this->marketplaceRatesHelper->getMktShippingTotalAmountPerItem($order) ?? [];
    }

    /**
     * Check if mixed order cancellations
     *
     * @param string $shipmentStatus
     * @param Order $order
     * @return bool
     */
    private function isMixedOrderCancellations(string $shipmentStatus, Order $order): bool
    {
        return $shipmentStatus === "cancelled" &&
            $this->helper->isMixedOrder($order);
    }


    /**
     * Log Email Data
     * @param $jdata
     * @return void
     */
    private function logEmailData($jdata)
    {
        if(!$this->toggleConfig->getToggleConfigValue('tiger_d209119')) {
            return;
        }
        $jdataToLog = json_decode($jdata);
        $jdataToLog->templateData = '';
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Email Request: ' . json_encode($jdataToLog));
    }

    /**
     * Returns updated order completion date if shipment status is DELIVERY_DATE_UPDATED and new due date exists.
     *
     * @param string|null $shipmentStatus
     * @param mixed $orderData
     * @return mixed
     */
    private function getUpdatedOrderCompletionDate($shipmentStatus, $orderData): mixed
    {
        try {
            //Toggle check is inside getShippingDueDate method.
            if ($shipmentStatus && $shipmentStatus == self::DELIVERY_DATE_UPDATED
                && $orderNewDueDate = $this->orderHistoryEnhacement->getShippingDueDate($orderData)) {
                return $orderNewDueDate;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
        return $orderData->getEstimatedPickupTime();
    }
}
