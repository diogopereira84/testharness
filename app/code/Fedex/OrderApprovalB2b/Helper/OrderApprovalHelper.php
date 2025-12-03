<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * OrderApprovalB2b OrderApprovalHelper class
 */
class OrderApprovalHelper extends AbstractHelper
{
    public const OUTPUT = 'output';
    public const CHECKOUT = 'checkout';
    public const RATE_QUOTE_DETAILS = 'rateQuoteDetails';
    public const RATE_QUOTE = 'rateQuote';
    public const RATE_QUOTE_REQUEST = 'rateQuoteRequest';
    public const RATE_QUOTE_RESPONSE = 'rateQuoteResponse';
    public const TRANSACTION_HEADER = 'transactionHeader';
    public const DELIVERY_LINES = 'deliveryLines';
    public const PRODUCT_LINES = 'productLines';
    public const LINE_ITEMS = 'lineItems';
    public const CONTACT = 'contact';
    public const SUPPORT_CONTACT = 'supportContact';
    public const RECIPIENT_CONTACT = 'recipientContact';
    public const RETAIL_PRINT_ORDER_DETAILS = 'retailPrintOrderDetails';
    public const RETAIL_PRINT_ORDER = 'retailPrintOrder';
    public const ORDER_REFERENCES = 'orderReferences';
    public const ORIGIN = 'origin';
    public const ORDER_CLIENT = 'orderClient';
    public const TENDERS = 'tenders';
    public const TRANSACTION_TOTALS = 'transactionTotals';
    public const MARKETPLACE = 'marketPlace';
    public const IS_ORDER_APPROVAL_ENABLED = 'isOrderApprovalEnabled';
    public const TRANSACTION_ID = 'transactionId';

    /*Ratequote Error Codes*/
    public const SHIPMENTDELIVERY_ADDRESS_INVALID = "SHIPMENTDELIVERY.ADDRESS.INVALID";
    public const SHIPMENTDELIVERY_HOLDUNTILDATE_INVALID = "SHIPMENTDELIVERY.HOLDUNTILDATE.INVALID";
    public const SHIPMENTDELIVERY_SERVICETYPE_INVALID = "SHIPMENTDELIVERY.SERVICETYPE.INVALID";
    public const SHIPMENTDELIVERY_WEIGHT_EXCEEDED = "SHIPMENTDELIVERY.WEIGHT.EXCEEDED";
    public const SHIPMENTDELIVERY_FEDEXACCOUNTNUMBER_INVALID = "SHIPMENTDELIVERY.FEDEXACCOUNTNUMBER.INVALID";
    public const SHIPMENTDELIVERY_NOT_AVAILABLE = "SHIPMENTDELIVERY.NOT.AVAILABLE";
    public const SHIPMENTDELIVERY_INAVLID_ADDRESSCLASSIFICATION = "SHIPMENTDELIVERY.INAVLID.ADDRESSCLASSIFICATION";
    public const INVALID_PICKUP_LOCATION = "INVALID.PICKUP.LOCATION";

    /**
     * OrderApprovalB2b Constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        Context $context,
        protected StoreManagerInterface $storeManager,
        protected LoggerInterface $logger,
        protected TimezoneInterface $timezoneInterface
    ) {
        parent::__construct($context);
    }

    /**
     * To build response like transaction without the transaction for pending order flow.
     *
     * @param object $dataObjectForFujistu
     * @param object $paymentData
     * @param object $quote
     * @return array
     */
    public function buildOrderSuccessResponse($dataObjectForFujistu, $paymentData, $quote)
    {
        try {
            $response = $rateQuoteDetails = [];
            $isShipAccount = false;
            $rateQuoteResponse = $dataObjectForFujistu->getRateQuoteResponse();
            $response[0][self::TRANSACTION_ID] = $rateQuoteResponse[self::TRANSACTION_ID] ?? '';
            if (!empty($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS]) &&
            count($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS]) < 2) {
                $rateQuoteDetails =
                $rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS][0] ?? [];
            } elseif (!empty($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS])) {
                $rateQuoteDetails =
                $rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS] ?? [];
                foreach ($rateQuoteDetails as $data) {
                    if ($data['estimatedVsActual'] == "ESTIMATED") {
                        $isShipAccount = true;
                    }
                }
            }
            $response[0][self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0] =
            $this->prepareLineItems($dataObjectForFujistu, $rateQuoteDetails, $paymentData, $quote, $isShipAccount);
            $response[0][self::OUTPUT][self::CHECKOUT][self::CONTACT] = $this->prepareContactDetails($quote);
            $response[0][self::OUTPUT][self::CHECKOUT][self::TENDERS] = [];

            $response[0][self::OUTPUT][self::CHECKOUT][self::TRANSACTION_TOTALS] =
            $this->getTransactionTotals($rateQuoteResponse, $isShipAccount);

            $response[0][self::OUTPUT][self::CHECKOUT][self::MARKETPLACE] = $this->getMarketplaceData();
            $response[0][self::OUTPUT][self::CHECKOUT][self::IS_ORDER_APPROVAL_ENABLED] = true;
            $response[0] = json_encode($response[0]);
            $response[self::RATE_QUOTE_RESPONSE] = $rateQuoteResponse;

            return $response;

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.': '.var_export($e->getMessage(), true));
            $this->logger->info(
                __METHOD__.':'.__LINE__.
                ': Some Error occued while returning response on success for pending order flow: ' .
                var_export($response, true)
            );
        }
    }

    /**
     * To preapare contact details response array.
     *
     * @param object $quote
     * @return array
     */
    public function prepareContactDetails($quote)
    {
        $prepareContact = [];
        $prepareContact['personName']['firstName'] = $quote->getData('customer_firstname') ?? '';
        $prepareContact['personName']['lastName'] = $quote->getData('customer_lastname') ?? '';
        $prepareContact['company']['name'] = 'FXO';
        $prepareContact['emailDetail']['emailAddress'] = $quote->getData('customer_email') ?? '';
        $prepareContact['phoneNumberDetails'][0]['phoneNumber']['number'] =
        $quote->getData('customer_telephone') ?? '';
        $prepareContact['phoneNumberDetails'][0]['usage'] = 'PRIMARY';

        return $prepareContact;
    }

    /**
     * To preapare Responsible Contact Center Details response array.
     *
     * @param array $rateQuoteDetails
     * @return array
     */
    public function getResponsibleCenterDetail($rateQuoteDetails)
    {
        $contactData['locationId'] = $rateQuoteDetails['responsibleLocationId'] ?? '';
        $contactData['address'] = $rateQuoteDetails[self::SUPPORT_CONTACT]['address'] ?? [];
        $contactData['emailDetail']['emailAddress'] = $rateQuoteDetails[self::SUPPORT_CONTACT]['email'] ?? '';
        $contactData['phoneNumberDetails'][0] = $rateQuoteDetails[self::SUPPORT_CONTACT]['phoneNumberDetails'] ?? [];

        return $contactData;
    }

    /**
     * To preapare product lines details response array.
     *
     * @param array $rateQuoteDetails
     * @return array
     */
    public function getProductLines($rateQuoteDetails)
    {
        $productLines = [];
        if (!empty($rateQuoteDetails[self::PRODUCT_LINES])) {
            foreach ($rateQuoteDetails[self::PRODUCT_LINES] as $data) {
                $data['productUnitPrice'] = (string) $data['productLinePrice'] ?? '';
                $data['productLineDetails'][0]['detailRetailPrice'] =
                (string) $data['productLineDetails'][0]['detailPrice'] ?? '';
                $productLines[] = $data;
            }
        }

        return $productLines;
    }

    /**
     * To preapare delivery lines details response array.
     *
     * @param array $rateQuoteDetails
     * @param object $quote
     * @param array $rateDetailProdAssoc
     * @return array
     */
    public function getDeliveryLines($rateQuoteDetails, $quote, $rateDetailProdAssoc)
    {
        if (isset($rateQuoteDetails[self::DELIVERY_LINES][0]) &&
        !empty($rateQuoteDetails[self::DELIVERY_LINES][0]['pickupDetails'])) {
            return $this->preparePickupDeliveryLines($rateQuoteDetails, $quote);
        } else {
            return $this->prepareShippingDeliveryLines($rateQuoteDetails, $quote, $rateDetailProdAssoc);
        }
    }

    /**
     * To preapare shipping delivery lines details response array.
     *
     * @param array $rateQuoteDetails
     * @param object $quote
     * @param array $rateDetailProdAssoc
     * @return array
     */
    public function prepareShippingDeliveryLines($rateQuoteDetails, $quote, $rateDetailProdAssoc)
    {
        $deliveryData = [];
        if (!empty($rateQuoteDetails[self::DELIVERY_LINES])) {
            foreach ($rateQuoteDetails[self::DELIVERY_LINES] as $data) {
                if (!empty($data['deliveryLineType']) && $data['deliveryLineType'] == "SHIPPING") {
                    $deliveryData['deliveryLineId'] = $data['recipientReference'] ?? '';
                    $deliveryData['recipientReference'] = $data['recipientReference'] ?? '';
                    $deliveryData['estimatedDeliveryLocalTime'] = $data['estimatedDeliveryLocalTime'] ?? '';
                    $deliveryData['estimatedShipDate'] = $data['estimatedShipDate'] ?? '';
                    $deliveryData['deliveryLinePrice'] = (string) $data['deliveryLinePrice'] ?? '';
                    $deliveryData['deliveryRetailPrice'] = (string) $data['deliveryRetailPrice'] ?? '';
                    $deliveryData['deliveryLineType'] = $data['deliveryLineType'] ?? '';
                    $deliveryData['deliveryDiscountAmount'] = (string) $data['deliveryDiscountAmount'] ?? '';
                    $deliveryData['recipientContact'] = $this->prepareContactDetails($quote);
                    $deliveryData['shipmentDetails'] = $data['shipmentDetails'] ?? [];
                    if (!empty($rateDetailProdAssoc)) {
                        $deliveryData['productAssociation'] =
                        $this->prepareProductAssociations($rateDetailProdAssoc);
                    } else {
                        $deliveryData['productAssociation'] = $this->prepareProductAssociations($rateQuoteDetails);
                    }
                    $deliveryData['productTotals'] = [];
                    $deliveryData['deliveryLineDetails'] = [];
                }
            }
        }

        return $deliveryData;
    }

    /**
     * To preapare pickup delivery lines details response array.
     *
     * @param array $rateQuoteDetails
     * @param object $quote
     * @return array
     */
    public function preparePickupDeliveryLines($rateQuoteDetails, $quote)
    {
        $deliveryData = [];
        $deliveryData['deliveryLineId'] = $rateQuoteDetails[self::DELIVERY_LINES][0]['deliveryLineId'] ?? '';
        $deliveryData['recipientReference'] = $rateQuoteDetails[self::DELIVERY_LINES][0]['deliveryLineId'] ?? '';
        $deliveryData['estimatedDeliveryLocalTime'] =
        $rateQuoteDetails[self::DELIVERY_LINES][0]['estimatedDeliveryLocalTime'] ?? '';
        $deliveryData['deliveryLineType'] = 'PICKUP';
        $deliveryData['recipientContact'] = $this->prepareContactDetails($quote);
        $deliveryData['pickupDetails']['address'] = $rateQuoteDetails[self::SUPPORT_CONTACT]['address'] ?? [];
        $deliveryData['pickupDetails']['requestedPickupLocalTime'] = $deliveryData['estimatedDeliveryLocalTime'] ?? '';
        $deliveryData['productAssociation'] = $this->prepareProductAssociations($rateQuoteDetails);

        return $deliveryData;
    }

    /**
     * To preapare product associations response array.
     *
     * @param array $rateQuoteDetails
     * @return array
     */
    public function prepareProductAssociations($rateQuoteDetails)
    {
        $productAssData = [];
        if (!empty($rateQuoteDetails[self::PRODUCT_LINES])) {
            foreach ($rateQuoteDetails[self::PRODUCT_LINES] as $data) {
                $element['productRef'] = (string) $data['instanceId'] ?? '';
                $element['quantity'] = (string) $data['unitQuantity'] ?? '';
                $productAssData[] = $element;
            }
        }

        return $productAssData;
    }

    /**
     * To preapare marketplace details response array.
     *
     * @return array
     */
    public function getMarketplaceData()
    {
        $mpData = [];
        $mpData['enabled'] = true;
        $mpData['shipping_method'] = "";
        $mpData['shipping_price'] = "";
        $mpData['lineItems'] = [];
        $mpData['shipping_address'] = [];

        return $mpData;
    }

    /**
     * To preapare transaction totals response array.
     *
     * @param array $rateQuoteResponse
     * @param bool $isShipAccount
     * @return array
     */
    public function getTransactionTotals($rateQuoteResponse, $isShipAccount)
    {
        $transData = [];
        if (!empty($rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS])) {
            $rateQuoteDetails = $rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS][0] ?? [];
            if ($isShipAccount) {
                $rateQuoteDetails =
                $rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS][1] ?? [];
            }
        }
        $transData['currency'] = (string) $rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE]['currency'] ?? '';
        $transData['grossAmount'] = (string) $rateQuoteDetails['grossAmount'] ?? '';
        $transData['totalDiscountAmount'] = (string) $rateQuoteDetails['totalDiscountAmount'] ?? '';
        $transData['netAmount'] = (string) $rateQuoteDetails['netAmount'] ?? '';
        $transData['taxAmount'] = (string) $rateQuoteDetails['taxAmount'] ?? '';
        $transData['totalAmount'] = (string) $rateQuoteDetails['totalAmount'] ?? '';

        return $transData;
    }

    /**
     * To preapare lineItems response array.
     *
     * @param object $dataObjectForFujistu
     * @param array $rateQuoteDetails
     * @param object $paymentData
     * @param object $quote
     * @param bool $isShipAccount
     * @return array
     */
    public function prepareLineItems($dataObjectForFujistu, $rateQuoteDetails, $paymentData, $quote, $isShipAccount)
    {
        $lineData = [];
        $rateDetailProdAssoc = [];
        $rateQuoteDeliveryLines = $rateQuoteDetails;
        if ($isShipAccount) {
            foreach ($rateQuoteDetails as $data) {
                if ($data['estimatedVsActual'] == "ESTIMATED") {
                    $rateQuoteDeliveryLines = $data;
                } elseif ($data['estimatedVsActual'] == "ACTUAL") {
                    $rateQuoteDetails = $data;
                    $rateDetailProdAssoc = $rateQuoteDetails;
                }
            }
        }
        $orderNumber = $dataObjectForFujistu->getOrderNumber();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $webhookUrl = "{$baseUrl}rest/V1/fedexoffice/orders/{$orderNumber}/status";
        $responseLineItems['type'] = 'PRINT_PRODUCT';
        $lineData['fedExAccountNumber'] = $this->maskFedexAccountNoLast4($paymentData->fedexAccountNumber);
        $lineData['customerNotificationEnabled'] = false;
        $lineData['orderContact']['contact'] = $this->prepareContactDetails($quote);
        $lineData['responsibleCenterDetail'][0] = $this->getResponsibleCenterDetail($rateQuoteDetails);
        $lineData[self::PRODUCT_LINES] = $this->getProductLines($rateQuoteDetails);
        $lineData[self::DELIVERY_LINES][0] =
        $this->getDeliveryLines($rateQuoteDeliveryLines, $quote, $rateDetailProdAssoc);
        $lineData['orderTotalDiscountAmount'] = (string) $rateQuoteDetails['totalDiscountAmount'] ?? '';
        $lineData['orderGrossAmount'] = (string) $rateQuoteDetails['grossAmount'] ?? '';
        $lineData['orderNetAmount'] = (string) $rateQuoteDetails['netAmount'] ?? '';
        $lineData['orderTaxableAmount'] = (string) $rateQuoteDetails['taxableAmount'] ?? '';
        $lineData['orderTaxAmount'] = (string) $rateQuoteDetails['taxAmount'] ?? '';
        $lineData['orderTotalAmount'] = (string) $rateQuoteDetails['totalAmount'] ?? '';
        $lineData['notificationRegistration']['webhook']['url'] = $webhookUrl;
        $lineData['origin']['orderNumber'] = $orderNumber;
        $lineData['origin']['orderClient'] =
        $dataObjectForFujistu->getOrderData()['rateQuoteRequest']['retailPrintOrder']['origin']['orderClient'] ?? '';

        $lineData['origin']['orderReferences'][0]['name'] =
        $dataObjectForFujistu->getOrderData()['rateQuoteRequest']['retailPrintOrder']['origin']['orderClient'] ?? '';
    
        $lineData['origin']['siteName'] =
        $dataObjectForFujistu->getOrderData()['rateQuoteRequest']['retailPrintOrder']['origin']['siteName'] ?? '';
        $responseLineItems[self::RETAIL_PRINT_ORDER_DETAILS][0] = $lineData;

        return $responseLineItems;
    }

    /**
     * Mask Fedex Account Number last 4 characters.
     *
     * @param string $accNumber
     * @return string
     */
    public function maskFedexAccountNoLast4($accNumber)
    {
        if (!empty($accNumber) && strlen($accNumber = trim($accNumber)) >= 4) {
            $accNumber = str_repeat('*', strlen($accNumber) - 4) .
            substr((string) $accNumber, -4);
        }

        return $accNumber;
    }

    /**
     * Prepare Order Shipping Request Data.
     *
     * @param string $fedexAccountNumber
     * @param object $order
     * @return json
     */
    public function prepareOrderShippingRequest($fedexAccountNumber, $order)
    {
        $paymentData = [
            'paymentMethod' => 'fedex',
            'fedexAccountNumber' => $fedexAccountNumber,
            'poReferenceId' => null
        ];
        $shippingReqData['paymentData'] = $paymentData;
        $shippingReqData['encCCData'] = null;
        $shippingReqData['pickupData'] = null;
        $shippingReqData['useSiteCreditCard'] = false;
        $shippingReqData['billingFields'] = $this->getCustomerBillingFields($order);

        return json_encode($shippingReqData);
    }

    /**
     * Prepare Order In-Store Pickup Request Data.
     *
     * @param object $order
     * @param object $quote
     * @return json
     */
    public function prepareOrderPickupRequest($order, $quote)
    {
        $paymentObj = $order->getPayment();
        $paymentData = [
            'paymentMethod' => 'fedex',
            'fedexAccountNumber' => $paymentObj->getFedexAccountNumber(),
            'poReferenceId' => null
        ];
        $quoteShippingAddress = $quote->getShippingAddress();
        $pickupAddressData = $quoteShippingAddress->getPickupAddress();
        $pickupAdd = !empty($pickupAddressData) ? json_decode($pickupAddressData, true) : [];
        $shippingAddress = $order->getShippingAddress();
        $isAlternatePerson = $quote->getIsAlternatePickup() ? true : false;

        $contactInfo = [
            "contact_fname" => $order->getCustomerFirstname(),
            "contact_lname" => $order->getCustomerLastname(),
            "contact_email" => $order->getCustomerEmail(),
            "contact_number" => $quote->getCustomerTelephone() ?? '',
            "contact_number_pickup" => $quote->getCustomerTelephone() ?? '',
            "contact_ext" => "",
            "alternate_fname" => $isAlternatePerson ? $shippingAddress->getFirstname() : '',
            "alternate_lname" => $isAlternatePerson ? $shippingAddress->getLastname() : '',
            "alternate_email" => $isAlternatePerson ? $shippingAddress->getEmail() : '',
            "alternate_number" => $isAlternatePerson ? $shippingAddress->getTelephone() : '',
            "alternate_ext" => "",
            "isAlternatePerson" => $isAlternatePerson
        ];
        
        $addressInfo = [
            "pickup_location_name" => $pickupAdd['name'] ?? '',
            "pickup_location_street" => $shippingAddress->getStreet()[0] ?? '',
            "pickup_location_city" => $shippingAddress->getCity() ?? '',
            "pickup_location_state" => $pickupAdd['address']['stateOrProvinceCode'] ?? '',
            "pickup_location_zipcode" => $shippingAddress->getPostcode() ?? '',
            "pickup_location_country" => $shippingAddress->getCountryId() ?? '',
            "pickup_location_date" => $this->timezoneInterface
                ->date($quote->getEstimatedPickupTime())
                ->format("Y-m-d\TH:i:s\Z"),
            "pickup" => true,
            "shipping_address" => '',
            "billing_address" => '',
            "shipping_method_code" => 'PICKUP',
            "shipping_carrier_code" => 'fedexshipping',
            "estimate_pickup_time" => $quote->getEstimatedPickupTime(),
            "estimate_pickup_time_for_api" => null
        ];

        $addressInfo['shipping_detail'] = [
            "carrier_code" => "fedexshipping",
            "method_code" => "PICKUP",
            "carrier_title" => "Fedex Store Pickup",
            "method_title" => $order->getShippingDescription(),
            "amount" => 0,
            "base_amount" => 0,
            "available" => true,
            "error_message" => "",
            "price_excl_tax" => 0,
            "price_incl_tax" => 0
        ];

        $shippingReqData['paymentData'] = $paymentData;
        $shippingReqData['encCCData'] = null;
        $shippingReqData['pickupData']['contactInformation'] = $contactInfo;
        $shippingReqData['pickupData']['addressInformation'] = $addressInfo;
        $shippingReqData['billingFields'] = $this->getCustomerBillingFields($order);
        
        return json_encode($shippingReqData);
    }

    /**
     * To send error response msgs.
     *
     * @param string $errorCode
     * @param boolean isRetrunBool
     * @return string
     */
    public function getErrorResponseMsgs($errorCode, $isRetrunBool = false)
    {
        switch ($errorCode) {
            case self::SHIPMENTDELIVERY_ADDRESS_INVALID:
                return $isRetrunBool ?? "The address in shipmentDelivery is invalid";
            case self::SHIPMENTDELIVERY_HOLDUNTILDATE_INVALID:
                return $isRetrunBool ?? "The holdUntilDate in shipmentDelivery is invalid";
            case self::SHIPMENTDELIVERY_SERVICETYPE_INVALID:
                return $isRetrunBool ?? "The serviceType in shipmentDelivery is invalid";
            case self::SHIPMENTDELIVERY_WEIGHT_EXCEEDED:
                return $isRetrunBool ?? "Shipment weight is greater than upper bound of shippable weight";
            case self::SHIPMENTDELIVERY_FEDEXACCOUNTNUMBER_INVALID:
                return $isRetrunBool ?? "The fedExAccountNumber in shipmentDelivery is invalid";
            case self::SHIPMENTDELIVERY_INAVLID_ADDRESSCLASSIFICATION:
                return $isRetrunBool ?? "Service type Ground Home Delivery must be designated as residential delivery";
            case self::INVALID_PICKUP_LOCATION:
                return $isRetrunBool ?? "Fetching Dlt details failed: The locationId in pickUpDelivery is invalid";
            default:
                return false;
        }
    }

    /**
     * get Custom billing fields data
     *
     * @param object $order
     * @return json|null
     */
    public function getCustomerBillingFields($order)
    {
        return $order->getBillingFields() ?? null;
    }
}
