<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Exception;
use Fedex\Cart\Model\IntegrationNoteBuilder;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\SubmitOrderSidebar\Helper\Data;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\DataObjectFactory;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Fedex\SubmitOrderSidebar\Model\SubmitOrder as SubmitOrderModel;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\CartGraphQl\Model\Note\Command\SaveInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper as GraphqlApiHelper;

class SubmitOrderBuilder
{
    public const DEFAULT_TIMEZONE = 'America/Chicago';
    public const LINE_NUMBER = ' Line:';
    public const ORDER_CLIENT_MAGENTO = "MAGENTO";
    public const ORDER_CLIENT_FUSE = "FUSE";

    /**
     * constant for address classification home
     */
    public const HOME = "HOME";

    /**
     * constant for address classification business
     */
    public const BUSINESS = "BUSINESS";

    /**
     * SubmitOrderBuilder constructor
     *
     * @param LoggerInterface $logger
     * @param SubmitOrderModel $submitOrderModel
     * @param SubmitOrderModelAPI $submitOrderModelAPI
     * @param SubmitOrderDataArray $submitOrderDataArray
     * @param ToggleConfig $toggleConfig
     * @param DataObjectFactory $dataObjectFactory
     * @param SelfReg $selfregHelper
     * @param CompanyHelper $companyHelper
     * @param RequestQueryValidator $requestQueryValidator
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param IntegrationNoteBuilder $integrationNoteBuilder
     * @param Data $submitOrderHelper
     * @param MarketPlaceHelper $marketPlaceHelper
     * @param CheckoutHelper $checkoutHelper
     * @param FXORateQuote $fxoRateQuote
     * @param SaveInterface $commandOrderNoteSave
     * @param InstoreConfig $instoreConfig
     * @param QuoteHelper $quoteHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param SerializerInterface $serializer
     * @param SubmitOrderSidebarConfigProvider $configProvider
     * @param DeliveryDataHelper $deliveryHelper
     * @param FuseBidViewModel $fuseBidViewModel
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param MarketplaceRatesHelper $marketplaceRatesHelper
     * @param CustomerSession $customerSession
     * @param GraphqlApiHelper $graphqlApiHelper
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected SubmitOrderModel $submitOrderModel,
        private SubmitOrderModelAPI $submitOrderModelAPI,
        private SubmitOrderDataArray $submitOrderDataArray,
        private ToggleConfig $toggleConfig,
        private DataObjectFactory $dataObjectFactory,
        protected SelfReg $selfregHelper,
        private CompanyHelper $companyHelper,
        private RequestQueryValidator $requestQueryValidator,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private IntegrationNoteBuilder $integrationNoteBuilder,
        private Data $submitOrderHelper,
        private MarketPlaceHelper $marketPlaceHelper,
        private CheckoutHelper $checkoutHelper,
        private FXORateQuote $fxoRateQuote,
        private SaveInterface $commandOrderNoteSave,
        private InstoreConfig $instoreConfig,
        protected QuoteHelper $quoteHelper,
        private readonly CartRepositoryInterface  $quoteRepository,
        private readonly SerializerInterface  $serializer,
        private readonly SubmitOrderSidebarConfigProvider $configProvider,
        private DeliveryDataHelper $deliveryHelper,
        private FuseBidViewModel $fuseBidViewModel,
        protected OrderApprovalViewModel $orderApprovalViewModel,
        private MarketplaceRatesHelper $marketplaceRatesHelper,
        private CustomerSession $customerSession,
        protected GraphqlApiHelper $graphqlApiHelper,
        private readonly ConfigInterface $productBundleConfig,
    )
    {
    }

    /**
     * @param $requestData
     * @param bool $pickupStore
     * @return array|int[]|null
     * @throws Exception
     */
    public function build(
        $requestData,
        $pickupStore = true,
        $isOrderApproval = false,
        $quoteObj = null
    ): ?array {
        $logHeader = 'File: ' . self::class . ' Method: ' . __METHOD__ . self::LINE_NUMBER . __LINE__;

        $isB2bEnabled = $this->orderApprovalViewModel->isOrderApprovalB2bEnabled();
        $isOrderApproval = $isB2bEnabled && $isOrderApproval ? true : false;

        if ($isOrderApproval) {
            $quote = $quoteObj;
            $this->logger->info(
                __METHOD__ . ':' . __LINE__.
                ' Before Activating B2b Order Approval Quote with quote id : '. $quote->getId()
            );
            $this->submitOrderModelAPI->updateQuoteStatusAndTimeoutFlag($quote, true, 0);
            $this->logger->info(
                __METHOD__ . ':' . __LINE__.
                ' After Activating B2b Order Approval Quote with quote id : '. $quote->getId()
            );
        } elseif ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $quoteObj) {
            $quote = $quoteObj;
        } else {
            $quote = $this->submitOrderModel->getQuote();
        }

        $isAlternate = $quote->getIsAlternate();
        $isAlternatePickup = $quote->getIsAlternatePickup();
        if ($isAlternate || $isAlternatePickup) {
            $this->submitOrderModelAPI->manageAlternateFlags($isAlternate, $isAlternatePickup);
        }

        $quoteId = $quote->getId();
        $quote = $this->fixQuoteShippingAddressEmailWithAlternateContactInfo($quote, $requestData);
        $shippingAddress = $quote->getShippingAddress();
        $orderNumber = $this->getGTNNumber($quote);

        date_default_timezone_set(self::DEFAULT_TIMEZONE);
        $useSiteCreditCard = false;

        $paymentData = $requestData->paymentData;
        $paymentData = is_object($paymentData) ? $paymentData : json_decode((string)$paymentData);
        $encCCData = $requestData->encCCData;

        // To save custom billing fileds in order approval b2b.
        if ($isB2bEnabled && !$isOrderApproval && isset($requestData->billingFields) &&
        !empty($requestData->billingFields)) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__.
                ' Setting Custom Billing Fields in B2b Order for quote '. $quoteId.' '.
                $requestData->billingFields
            );
            $quote->setBillingFields($requestData->billingFields);
        }

        $techTitansLocationFixToggle = $this->toggleConfig->getToggleConfigValue('techtitans_205447_wrong_location_fix');
        $techTitans211480FixToggle = $this->toggleConfig->getToggleConfigValue('tech_titans_d_211480');
        if ($techTitansLocationFixToggle && !$isOrderApproval) {
            $locationId = $this->submitOrderModelAPI->getProductionLocationId();
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Location Id from checkout session:' . $locationId);
            if ($techTitans211480FixToggle && empty($locationId)) {
                $locationId = $quote->getProductionLocationId();
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Location Id from Quote:' . $locationId);
            }
            $productionLocationId = !empty($requestData->selectedProductionId) ? $requestData->selectedProductionId: $locationId;
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Production Location Id was found:' . $productionLocationId);
            $quote->setProductionLocationId($productionLocationId);
        }

        if (property_exists($paymentData, "billingAddress") && is_a($paymentData, 'stdClass')) {
            $quote->setBillingAddress($this->submitOrderModel->getBillingAddress($paymentData, $quote));
            $quote->save();
        }

        if (empty($orderNumber)) {
            $this->logger->error($logHeader . ' Error while generating GTN number for quote id:' . $quoteId);

            return ['error' => 1];
        }

        $shipmentId = $quote->getData('fxo_shipment_id');


        $customerOrderInfo = $this->getCustomerPickupAndShippingAddress(
            $isAlternate,
            $pickupStore,
            $requestData,
            $quote,
            $shippingAddress,
            $paymentData
        );

        // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
        if (isset($requestData->useSiteCreditCard) && $requestData->useSiteCreditCard == 'true') {
            $useSiteCreditCard = true;
            //B-1326759: Set site configured payment is used in quote
            $quote->setSiteConfiguredPaymentUsed(1);
        }

        $recipientInfo = $this->getRecipientInformation(
            $isAlternate,
            $isAlternatePickup,
            $shippingAddress
        );

        try {
            $this->setOrderNotes($quote, $requestData);
        } catch (GraphQlFujitsuResponseException $e) {
            if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                throw new GraphQlFujitsuResponseException(__($e->getMessage()));
            }
        }

        $dataObject = $this->prepareDataObject(
            $quote,
            $pickupStore,
            $orderNumber,
            $shipmentId,
            $customerOrderInfo,
            $recipientInfo
        );

        $data = $this->submitOrderDataArray->getOrderDetails(
            $dataObject,
            $quote,
            $isB2bEnabled,
            $isOrderApproval
        );
        if (!$pickupStore) {
            $shipmentSpecialServices = $this->submitOrderModel->getRateRequestShipmentSpecialServices();
            if (!empty($shipmentSpecialServices)) {
                $data['rateQuoteRequest']['retailPrintOrder']['recipients'][0]['shipmentDelivery']['specialServices']
                    = $shipmentSpecialServices;
            }
        }

        // Fix for D-223412 discount intent not being passed for bid quotes
        $discountIntent =  $this->graphqlApiHelper->getDiscountIntentForQuote($quote);
        $data['rateQuoteRequest']['retailPrintOrder']['discountIntentResource'] = $discountIntent;

        $response = null;
        $dataObjectForFujistu = $this->dataObjectFactory->create();
        $dataObjectForFujistu->setQuoteData($quote);
        $dataObjectForFujistu->setPaymentData($paymentData);
        $dataObjectForFujistu->setEncCCData($encCCData);
        $dataObjectForFujistu->setIsPickup($customerOrderInfo['isPickup']);
        $dataObjectForFujistu->setShipmentId($shipmentId);
        $dataObjectForFujistu->setEstimatePickupTime($customerOrderInfo['estimatePickupTime']);
        $dataObjectForFujistu->setUseSiteCreditCard($useSiteCreditCard);
        $dataObjectForFujistu->setOrderData($data);
        $dataObjectForFujistu->setQuoteId($quoteId);
        $dataObjectForFujistu->setOrderNumber($orderNumber);
        $this->logger->info(__METHOD__ . ':' . __LINE__. ': Before Verifying Quote Integrity:' . $quote->getId());
        $this->submitOrderHelper->verifyQuoteIntegrity($quote);
        $this->logger->info(__METHOD__ . ':' . __LINE__. ': After Verifying Quote Integrity:' . $quote->getId());

        try {
            $response = $this->callRateQuoteApiWithCommitAction(
                $logHeader,
                $dataObjectForFujistu,
                $paymentData,
                false,
                $isOrderApproval
            );
        } catch (Exception $exception) {
            $this->submitOrderModel->unsetOrderInProgress();
            $this->logger->info(
                $logHeader .
                ' Problem when calling fujitsu rate quote API for Quote Id:' . $quoteId . ' $shipmentId => ' .
                $shipmentId . ' GTN Number => ' . $orderNumber . ' Exception => ' . $exception
            );
            if ($isOrderApproval) {
                $this->submitOrderModelAPI->updateQuoteStatusAndTimeoutFlag($quote, false, 0);
            }
        }

        return $response;
    }

    /**
     * @param $order
     * @param $quote
     * @param $requestData
     * @return array
     * @throws GraphQlFujitsuResponseException
     */
    public function instoreBuildRetryTransaction($order, $quote, $requestData): array
    {
        $logHeader = 'File: ' . self::class . ' Method: ' . __METHOD__ . self::LINE_NUMBER . __LINE__;
        $orderNumber = $order->getIncrementId();
        try {
            $dataObjectForFujistu = $this->dataObjectFactory->create();
            $dataObjectForFujistu->setQuoteData($quote);
            $dataObjectForFujistu->setOrderNumber($orderNumber);
            $dataObjectForFujistu->setQuoteId($quote->getId());
            $dataObjectForFujistu->setPaymentData(json_decode($requestData->paymentData));
            $checkoutResponse = $this->submitOrderModelAPI->updateOrderAfterPayment($dataObjectForFujistu, $order);
        } catch (GraphQlFujitsuResponseException $e) {
            throw new GraphQlFujitsuResponseException(__($e->getMessage()));
        } catch (Exception $exception) {
            $this->submitOrderModel->unsetOrderInProgress();
            $this->logger->info(
                $logHeader .
                ' Problem on instore retry proccess when calling fujitsu rate quote API for Quote Id:' .
                $quote->getId() . ' $shipmentId => ' . $quote->getData('fxo_shipment_id') .
                ' GTN Number => ' . $orderNumber . ' Exception => ' . $exception
            );
        }

        return $checkoutResponse ?? [];
    }

    /**
     * Check if transaction warning or error returned
     * @param object $response
     * @return bool
     */
    public function isTransactionExist($response)
    {
        $transactionExist = false;
        if (!empty($response['error'])
            && !empty($response['response'])
            && !empty($response['response']['errors'])
            ) {
            $transactionExist =  $this->submitOrderModel
                    ->validateRateQuoteAPIErrors($response['response']['errors']);
        }

        if (!empty($response['response'])
        && !empty($response['response']['output'])
        && !empty($response['response']['output']['alerts'])
        ) {
            $transactionExist = $this->submitOrderModel->validateRateQuoteAPIWarnings(
                $response['response']['output']['alerts']
            );
        }

        return $transactionExist;
    }

    /**
     * Validate if rate quote response is valid
     *
     * @param array $rateQuoteResponse
     * @return boolean
     */
    private function isValidRateQuoteResponse(array $rateQuoteResponse): bool
    {
        $validResponse = false;
        if ((isset($rateQuoteResponse['output']))
            && (!empty($rateQuoteResponse['output']))
            && (empty($rateQuoteResponse['errors']))) {
            $validResponse = true;
        }

        return $validResponse;
    }

    /**
     * Get GTN Number
     *
     * @param object $quote
     * @return int|string
     */
    public function getGTNNumber($quote)
    {
        return $quote->getData('gtn');
    }

    /**
     * Get Customer Shipping and Pickup Address Information
     *
     * @param bool|null $isAlternate
     * @param bool $pickupStore
     * @param string|object $requestData
     * @param object $quote
     * @param object $shippingAddress
     * @param string|object $paymentData
     * @return array
     */
    public function getCustomerPickupAndShippingAddress(
        $isAlternate,
        $pickupStore,
        $requestData,
        $quote,
        $shippingAddress,
        $paymentData
    ): array {
        $addressClassification = $streetAddress = $city = $regionCode = $shipperRegion = $zipcode = $shipMethod = '';
        $poReferenceId = $fedExAccountNumber = $fedexShipAccountNumber = $estimatePickupTime = '';
        $locationId = $requestedPickupLocalTime = $fName = $lName = $email = $telephone = '';
        $altContactInfo = [];
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        $company = null;
        if (!$pickupStore) {
            $isPickup = false;

            if ($this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix')) {
                $addressClassification = SubmitOrderModelAPI::BUSINESS;
                $isResidenceShipping = $shippingAddress->getData('is_residence_shipping');
                if ($isResidenceShipping) {
                    $addressClassification = SubmitOrderModelAPI::HOME;
                }
            } else {
                $addressClassification = SubmitOrderModelAPI::HOME;
                $company = $shippingAddress->getData('company');
                if ($company != null && $company != "") {
                    $addressClassification = SubmitOrderModelAPI::BUSINESS;
                }
            }

            $streetAddress = (array)$shippingAddress->getData('street');
            if (isset($streetAddress[0])) {
                $streetAddress = explode(PHP_EOL, $streetAddress[0]);
            }
            $city = $shippingAddress->getData('city');
            $regionCode = $shippingAddress->getData('region_id');
            if (isset($regionCode)) {
                $shipperRegion = $this->submitOrderModel->getRegionByRegionCode($regionCode);
            }
            $zipcode = $shippingAddress->getData('postcode');
            $shipMethod = $shippingAddress->getData('shipping_method');
            $array = explode('_', $shipMethod, 2);
            $shipMethod = "";
            if (!empty($array[1])) {
                $shipMethod = $array[1];
            }
            $poReferenceId = $paymentData->poReferenceId ?? null;
            $fedExAccountNumber = !empty($paymentData->fedexAccountNumber) ? $paymentData->fedexAccountNumber : null;
            $fedexShipAccountNumber = $quote->getData("fedex_ship_account_number");
            $altContactInfo = $requestData->altContactInfo ?? '[]';
            $altContactInfo = is_object($altContactInfo) ? $altContactInfo : json_decode($altContactInfo);
            $explorersD193256FixToggle = $this->toggleConfig->getToggleConfigValue('explorers_d_193256_fix');

            if (
                $explorersD193256FixToggle && $isAlternate &&
                !empty($altContactInfo) && !empty($altContactInfo->alternate_email) && !empty($altContactInfo->alternate_fname && !empty($altContactInfo->alternate_lname) && !empty($altContactInfo->alternate_number))
            ) {
                $quote->setData('customer_email', $altContactInfo->alternate_email);
                $quote->setData('customer_firstname', $altContactInfo->alternate_fname);
                $quote->setData('customer_lastname', $altContactInfo->alternate_lname);
                $quote->setData('customer_telephone', $altContactInfo->alternate_number);
            }
            $shippingInfo = $this->getCustomerShippingInfo(
                $isAlternate,
                $quote,
                $shippingAddress
            );

            $fName = $shippingInfo['fName'];
            $lName = $shippingInfo['lName'];
            $email = $shippingInfo['email'];
            $telephone = $shippingInfo['telephone'];
            $company = $isCompanyNameToggleEnabled ? $shippingInfo['company'] : null;

            // D-188299 :: Send Location Id
            if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
                $techTitansLocationFixToggle = $this->toggleConfig->getToggleConfigValue('techtitans_205447_wrong_location_fix');
                $productionLocationFixToggle = $this->toggleConfig
                    ->getToggleConfigValue('explorers_d188299_production_location_fix');
                if ($productionLocationFixToggle || $techTitansLocationFixToggle) {
                    $locationId = $this->submitOrderModelAPI->getProductionLocationId();
                    $techTitans211480FixToggle = $this->toggleConfig->getToggleConfigValue('tech_titans_d_211480');
                    if ($techTitans211480FixToggle && empty($locationId)) {
                        $locationId = $quote->getProductionLocationId();
                    }
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Production Location Id was found:' . $locationId);
                }

                if ($techTitansLocationFixToggle) {
                    $locationId = !empty($requestData->selectedProductionId) ? $requestData->selectedProductionId : $locationId;
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Production Location Id was found:' . $locationId);
                }
            }
        } else {
            $isPickup = true;
            $pickupData = $requestData->pickupData ?? '[]';
            $pickupData = is_object($pickupData) ?
                $pickupData :
                json_decode($pickupData);

            if (!empty($pickupData->addressInformation)
                && !empty($pickupData->addressInformation->shipping_detail)
                && !empty($pickupData->addressInformation->shipping_detail->method_title)
            ) {
                $locationId = $pickupData->addressInformation->shipping_detail->method_title;
            } else {
                $locationId = $shippingAddress->getData('shipping_description');
            }

            $fedExAccountNumber = !empty($paymentData->fedexAccountNumber) ? $paymentData->fedexAccountNumber : null;
            $pickupInfo = $this->getCustomerPickupInfo($quote, $pickupData);

            $requestedPickupLocalTime = $this->getRequestedPickupLocalTime($requestedPickupLocalTime, $pickupData);

            $estimatePickupTime = $pickupInfo['estimatePickupTime'];
            $fName = $pickupInfo['fName'];
            $lName = $pickupInfo['lName'];
            $email = $pickupInfo['email'];
            $telephone = $pickupInfo['telephone'];
        }

        return [
            'isPickup' => $isPickup,
            'addressClassification' => $addressClassification,
            'streetAddress' => $streetAddress,
            'city' => $city,
            'regionCode' => $regionCode,
            'shipperRegion' => $shipperRegion,
            'zipcode' => $zipcode,
            'shipMethod' => $shipMethod,
            'poReferenceId' => $poReferenceId,
            'fedExAccountNumber' => $fedExAccountNumber,
            'fedexShipAccountNumber' => $fedexShipAccountNumber,
            'estimatePickupTime' => $estimatePickupTime,
            'locationId' => $locationId,
            'requestedPickupLocalTime' => $requestedPickupLocalTime,
            'fName' => $fName,
            'lName' => $lName,
            'email' => $email,
            'telephone' => $telephone,
            'company' => $company
        ];
    }

    /**
     * Get requested pickup local time
     *
     * @param int|string $requestedPickupLocalTime
     * @param string $pickupData
     * @return int|string
     */
    public function getRequestedPickupLocalTime($requestedPickupLocalTime, $pickupData)
    {
        if (!empty($pickupData->addressInformation)
            && !empty($pickupData->addressInformation->estimate_pickup_time_for_api)
        ) {
            $requestedPickupLocalTime = $pickupData->addressInformation->estimate_pickup_time_for_api;
        }

        return $requestedPickupLocalTime;
    }

    /**
     * Get Customer Shipping Information
     *
     * @param bool|null $isAlternate
     * @param object $quote
     * @param object $shippingAddress
     * @return array
     */
    public function getCustomerShippingInfo($isAlternate, $quote, $shippingAddress)
    {
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        $company = $isCompanyNameToggleEnabled ? $shippingAddress->getData('company') : null;
        if ($isAlternate) {
            $fName = $quote->getData('customer_firstname');
            $lName = $quote->getData('customer_lastname');
            $email = $quote->getData('customer_email');
            $telephone = $quote->getData('customer_telephone');
        } else {
            $fName = $shippingAddress->getData('firstname');
            $lName = $shippingAddress->getData('lastname');
            $email = $shippingAddress->getData('email');
            $telephone = $shippingAddress->getData('telephone');
        }

        return [
            'fName' => $fName,
            'lName' => $lName,
            'email' => $email,
            'telephone' => $telephone,
            'company' => $company
        ];
    }

    /**
     * Get Customer Pickup Information
     *
     * @param object $quote
     * @param string $pickupData
     * @return array
     */
    public function getCustomerPickupInfo($quote, $pickupData)
    {
        $estimatePickupTime = '';
        if (!empty($pickupData->addressInformation)
            && !empty($pickupData->addressInformation->estimate_pickup_time)
        ) {
            $estimatePickupTime = $pickupData->addressInformation->estimate_pickup_time;
        }
        $this->logger->info(__METHOD__. ':' . __LINE__. json_encode($quote->getData()));
        $this->logger->info(__METHOD__. ':' . __LINE__. json_encode($pickupData->contactInformation ?? []));
        $fName = $quote->getData('customer_firstname') ?? $pickupData->contactInformation->contact_fname ?? null;
        $lName = $quote->getData('customer_lastname') ?? $pickupData->contactInformation->contact_lname ?? null;
        if ($this->submitOrderModel->isFclCustomer()) {
            $email = $pickupData->contactInformation->contact_email ?? null;
        } else {
            $email = $quote->getData('customer_email');
        }
        $telephone = $quote->getData('customer_telephone') ?? $pickupData->contactInformation->contact_number ?? null;

        return [
            'estimatePickupTime' => $estimatePickupTime,
            'fName' => $fName,
            'lName' => $lName,
            'email' => $email,
            'telephone' => $telephone
        ];
    }

    /**
     * Get Recipient Information
     *
     * @param bool|null $isAlternate
     * @param bool|null $isAlternatePickup
     * @param object $shippingAddress
     * @return array
     */
    public function getRecipientInformation(
        $isAlternate,
        $isAlternatePickup,
        $shippingAddress
    ) {
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        $recipientFname = $recipientLname = $recipientEmail = $recipientTelephone = $recipientExt = $recipientCompany = null;
        if ($isAlternate || $isAlternatePickup) {
            $recipientFname = $shippingAddress->getData('firstname');
            $recipientLname = $shippingAddress->getData('lastname');
            $recipientEmail = $shippingAddress->getData('email');
            $recipientCompany = $isCompanyNameToggleEnabled ? $shippingAddress->getData('company') : null;
            $recipientTelephone = $shippingAddress->getData('telephone');
            $recipientExt = !empty($shippingAddress->getData('ext_no')) ? $shippingAddress->getData('ext_no') : null;
        }

        return [
            "recipientFname" => $recipientFname,
            "recipientLname" => $recipientLname,
            "recipientEmail" => $recipientEmail,
            "recipientTelephone" => $recipientTelephone,
            "recipientExt" => $recipientExt,
            "recipientCompany" => $recipientCompany
        ];
    }

    /**
     * Prepared data object
     *
     * @param object $quote
     * @param bool $pickupStore
     * @param int|string $orderNumber
     * @param int|string|null $shipmentId
     * @param array $customerOrderInfo
     * @param array $recipientInfo
     * @param bool $isEproOrder
     * @param $companyId
     * @return object
     * @throws NoSuchEntityException
     */
    public function prepareDataObject(
        $quote,
        $pickupStore,
        $orderNumber,
        $shipmentId,
        $customerOrderInfo,
        $recipientInfo,
        $isEproOrder = false,
        $companyId = null
    ) {
        $companySite = null;
        $siteName = null;
        $negotiableQuote = $this->getNegotiableQuote($quote);
        if (($this->selfregHelper->isSelfRegCustomer() ||
                $this->submitOrderHelper->isSdeStore())
        ) {
            $companySite = $this->companyHelper->getCustomerCompany($companyId)->getCompanyName() ?? null;
            $siteName = null;
        } elseif ($isEproOrder && $quote->getCustomerId() && $negotiableQuote->getQuoteId()) {
            $company = $this->companyHelper->getCustomerCompany($companyId);
            $companySite =  $company->getSiteName() ? null : $company->getCompanyName();
            $siteName = $company->getSiteName() ?? null;
        }
        $webhookUrl = $this->submitOrderModel->getWebHookUrl($orderNumber);
        if($this->marketPlaceHelper->isEssendantToggleEnabled()){
            if($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
            } else {
                $items = $quote->getAllVisibleItems();
            }
        }else{
            $items = $quote->getAllItems();
        }
        $isFullMiraklQuote = $this->quoteHelper->isFullMiraklQuote($quote);
        $this->logger->info(
            __METHOD__. ':' . __LINE__.
            ' Before product associations call with quote id '. $quote->getId()
        );
        $result = $this->submitOrderModel->getProductAndProductAssociations($items, $isFullMiraklQuote);
        $product = $result['product'] ?? [];
        $productAssociations = $result['productAssociations'] ?? [];
        $getUuid = $this->submitOrderModel->getUuid();
        $couponCode = $quote->getData("coupon_code");
        $promoCodeArray = [];
        $isGraphQlRequest = $this->requestQueryValidator->isGraphQl();
        if (strlen((string)$couponCode)) {
            $promoCodeArray['code'] = $couponCode;
        }

        $userReferences = null;
        if (!empty($getUuid)) {
            $userReferences = [
                [
                    'reference' => $getUuid,
                    'source' => 'FCL',
                ],
            ];
        }
        $extension = !empty($quote->getData('ext_no')) ? $quote->getData('ext_no') : null;
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');

        $dataObject = $this->dataObjectFactory->create();
        $dataObject->setPickStore($pickupStore);
        $dataObject->setFedExAccountNumber($customerOrderInfo['fedExAccountNumber']);
        $dataObject->setLteIdentifier($quote->getData('lte_identifier') ?? null);

        $dataObject->setOrderNumber($orderNumber);
        $dataObject->setSiteName($siteName);
        $dataObject->setCompanySite($companySite);
        $dataObject->setUserReferences($userReferences);
        $dataObject->setFname($customerOrderInfo['fName'] ?? $quote->getCustomerFirstname());
        $dataObject->setLname($customerOrderInfo['lName'] ?? $quote->getCustomerLastname());
        $dataObject->setEmail($customerOrderInfo['email'] ?? $quote->getCustomerEmail());
        $dataObject->setTelephone($customerOrderInfo['telephone'] ?? $quote->getCustomerTelephone());
        $dataObject->setExtension($extension);
        $dataObject->setRecipientFname($recipientInfo['recipientFname']);
        $dataObject->setRecipientLname($recipientInfo['recipientLname']);
        $dataObject->setRecipientEmail($recipientInfo['recipientEmail']);
        $dataObject->setRecipientTelephone($recipientInfo['recipientTelephone']);
        $dataObject->setRecipientExtension($recipientInfo['recipientExt']);
        if($isCompanyNameToggleEnabled) {
            $dataObject->setCompany($customerOrderInfo['company']);
        }
        $dataObject->setWebhookUrl($webhookUrl);
        $dataObject->setProductData($product);
        $dataObject->setShipmentId($shipmentId);
        $dataObject->setProductAssociations($productAssociations);
        $dataObject->setPromoCodeArray($promoCodeArray);
        $dataObject->setPoReferenceId($customerOrderInfo['poReferenceId']);
        $dataObject->setStreetAddress($customerOrderInfo['streetAddress']);
        $dataObject->setCity($customerOrderInfo['city']);
        $dataObject->setShipperRegion($customerOrderInfo['shipperRegion']);
        $dataObject->setZipCode($customerOrderInfo['zipcode']);
        $dataObject->setAddressClassification($customerOrderInfo['addressClassification']);
        $dataObject->setShipMethod($customerOrderInfo['shipMethod']);
        $dataObject->setFedexShipAccountNumber($customerOrderInfo['fedexShipAccountNumber']);
        $dataObject->setOrderClient(static::ORDER_CLIENT_MAGENTO);

        $contactId = null;
        if ($isGraphQlRequest) {
            try {
                $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
                $pickupStoreId = $quoteIntegration->getLocationId();
                $quoteLocationId = $quoteIntegration->getPickupLocationId();
                $dataObject->setOrderClient(static::ORDER_CLIENT_FUSE);
                $notes = $this->integrationNoteBuilder->build((int) $quote->getId());
                $contactId = $quoteIntegration->getRetailCustomerId();
            } catch (NoSuchEntityException $e) {
                $this->logger->info('No Such Entity Exception => ' . $e);
            }
        }

        if ($this->fuseBidViewModel->isSendRetailLocationIdEnabled()
        && !$isGraphQlRequest && $quote->getIsBid()) {
            try {
                $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
                $pickupStoreId = $quoteIntegration->getLocationId();
                $contactId = $quoteIntegration->getRetailCustomerId();
            } catch (NoSuchEntityException $e) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ .
                    'Error in Fetching Quote Integration: ' . $e->getMessage()
                );
            }
        }

        $orderNote = $this->companyHelper->getCompanyLevelConfig()['order_notes'] ?? null;
        if ($orderNote) {
            $notes = [['text' => $orderNote]];
        }
        $dataObject->setNotes($notes ?? []);
        $dataObject->setSourceRetailLocationId($pickupStoreId ?? null);
        $dataObject->setLocationId($quoteLocationId ?? $customerOrderInfo['locationId']);
        $dataObject->setRequestedPickupLocalTime($customerOrderInfo['requestedPickupLocalTime']);
        $dataObject->setRateQuoteId($quote->getData('fjmp_quote_id'));
        $dataObject->setContactId($contactId);

        $fedexShipReferenceId = $quote->getData('fedex_ship_reference_id') ?? null;
        $isCommercial = $this->deliveryHelper->isCommercialCustomer() ?? null;

        if ($fedexShipReferenceId !== null
            && $isCommercial !== null) {
            $dataObject->setPoReferenceId($fedexShipReferenceId);
        }

        return $dataObject;
    }

    /**
     * Finally call rateQuote Api with commit action
     *
     * @param string $logHeader
     * @param object $dataObjectForFujistu
     * @param object $paymentData
     * @param bool $eproOrderAlreadyExists
     * @param bool $isOrderApproval
     * @return array
     */
    public function callRateQuoteApiWithCommitAction(
        $logHeader,
        $dataObjectForFujistu,
        $paymentData,
        $eproOrderAlreadyExists = false,
        $isOrderApproval = false
    ) {
        $dataObjectForFujistu->setIsB2bApproval($isOrderApproval);
        $quote = $dataObjectForFujistu->getQuoteData();
        $orderNumber = $dataObjectForFujistu->getOrderNumber();
        $response = $this->submitOrderModelAPI->callRateQuoteApi($dataObjectForFujistu);

        $isTransactionCreated = $this->isTransactionExist($response);

        if (empty($response['error'])
        && empty($response['response']['errors'])
        && isset($response['response']['output'])
        && isset($response['response']['output']['rateQuote'])) {
            if (!$eproOrderAlreadyExists) {
                // delete pending order if order exit with same GTN number
                $this->submitOrderModelAPI->deleteOrderWithPendingStatus($quote->getGtn());
            }
            $dataObjectForFujistu->setRateQuoteResponse($response['response']);
            $updatedRateQuoteId = $this->submitOrderModelAPI->getRateQuoteId($response['response']);
                $isPromiseTimeWarningtoggleEnabled = $this->deliveryHelper->isPromiseTimeWarningtoggleEnabled();
                    if ($isPromiseTimeWarningtoggleEnabled) {
                    // Extract delivery line details
                    $deliveryLines = $response['response']['output']['rateQuote']['rateQuoteDetails'][0]['deliveryLines'][1] ?? [];
                        if (empty($deliveryLines)) {
                            $deliveryLines = $response['response']['output']['rateQuote']['rateQuoteDetails'][0]['deliveryLines'][0] ?? [];
                        }
                    $estimatedDeliveryLocalTime = $deliveryLines['estimatedDeliveryLocalTime'] ?? '';
                    if (!empty($estimatedDeliveryLocalTime)) {
                        $estimatedDeliveryLocalTime = $this->deliveryHelper->updateDateTimeFormat($estimatedDeliveryLocalTime);
                    }
                    // Compare estimated delivery time with original
                    if (!empty($estimatedDeliveryLocalTime) && !empty($quote->getData('estimated_pickup_time'))) {
                        $normalizedEstimatedTime = trim((string)$estimatedDeliveryLocalTime);
                        $normalizedOriginalTime = trim((string)$quote->getData('estimated_pickup_time'));
                        if ($normalizedEstimatedTime !== $normalizedOriginalTime) {
                        $isPickup = (bool)$dataObjectForFujistu->getIsPickup();
                        $response = [
                            'error' => true,
                            'code' => 'estimateTimeMismatch',
                            'estimateTimeMismatch' => true,
                            'estimatedDeliveryLocalTime' => $normalizedEstimatedTime,
                            'message' => $isPickup
                                ? __('Pickup time has changed. Please review the updated time.')
                                : __('Delivery time has changed. Please review the updated time.'),
                        ];

                        $response[$isPickup ? 'isPick' : 'isShip'] = true;
                        return $response;
                        }
                    }
                    if (!empty($estimatedDeliveryLocalTime)) {
                        $quote->setData('estimated_pickup_time', $estimatedDeliveryLocalTime);
                    }
                }
            $this->logger->info(
                $logHeader . ' Updated rate quote id with commit action => ' . $updatedRateQuoteId
            );
            $quote->setData('fjmp_quote_id', $updatedRateQuoteId);

            if (empty($quote->getData('reserved_order_id')) && ! empty($quote->getGtn())) {
                $quote->setData('reserved_order_id', $quote->getGtn());
            }

            $dataObjectForFujistu->setQuoteData($quote);

            if ($eproOrderAlreadyExists) {
                $order = $eproOrderAlreadyExists;
                if ($this->toggleConfig->getToggleConfigValue('explorers_d_205387_fix')) {
                    $productLines = $this->submitOrderModelAPI
                        ->getProductLinesDetails($response['response']);
                    $order->getPayment()
                        ->setProductLineDetails(json_encode($productLines));
                }
            } elseif (!$isOrderApproval) {
                $order = $this->submitOrderModelAPI->createOrderBeforePayment(
                    $paymentData,
                    $dataObjectForFujistu
                );
            } else {
                $order = $this->orderApprovalViewModel->getOrder($orderNumber);
            }
            if ($this->marketPlaceHelper->isEssendantToggleEnabled()) {
                $this->setLastRealOrderInfo($quote, $order);
            }

            if ($this->orderApprovalViewModel->isOrderApprovalB2bEnabled() && !$isOrderApproval) {
                $this->logger->info(
                    $logHeader . ' Order Created With Pending Approval Status For GTN Number => ' . $orderNumber
                );
                $checkoutResponse = $this->orderApprovalViewModel
                ->getOrderPendingApproval($dataObjectForFujistu, $paymentData, $quote);

                $this->logger->info(
                    $logHeader . ' Pending Order Approval checkout response => ' .
                    json_encode($checkoutResponse)
                );
                $this->submitOrderModel->unsetOrderInProgress();
                $this->submitOrderModelAPI->updateQuoteStatusAndTimeoutFlag($quote, false, 0);
                $this->submitOrderHelper->clearQuoteCheckoutSessionAndStorage($order->getQuoteId(), $order->getId());
                if (!empty($checkoutResponse)) {
                    $orderData = [
                        'status' => OrderApprovalViewModel::CONFIRMED,
                        'order_id' => $order->getId(),
                    ];
                    $this->orderApprovalViewModel->b2bOrderSendEmail($orderData);
                }
            } else {
                if (!$isOrderApproval) {
                    $this->logger->info(
                        $logHeader . ' Order Created With Pending Status For GTN Number => ' . $orderNumber
                    );
                }

                $checkoutResponse = $this->submitOrderModelAPI->updateOrderAfterPayment($dataObjectForFujistu, $order);

                if (empty($checkoutResponse['error'])) {
                    $checkoutResponseData = json_decode((string)$checkoutResponse[0]);
                    $items = $order->getItemsCollection();
                    $miraklItems =  $additionalDataArray = [];
                    foreach ($items as $item) {
                        if ($item->getMiraklOfferId()) {
                            $additionalData = $item->getAdditionalData();
                            $additionalDataArray = (array)json_decode($additionalData);
                            $miraklItems[] = [
                                'name' => ($additionalDataArray['marketplace_name'] ?? $additionalDataArray['navitor_name']),
                                'preview_url' => $additionalDataArray['image'],
                                'price' => $this->checkoutHelper->formatPrice($additionalDataArray['unit_price']),
                                'subtotal' => $this->checkoutHelper->convertPrice($additionalDataArray['total'], true),
                                'features' => $additionalDataArray['features'] ?? [],
                                'quoteItemId' => $item->getQuoteItemId(),
                                'additional_data' => $additionalData,
                                'qty' => $additionalDataArray['quantity']
                            ];
                        }
                    }
                    $marketPlaceInfo = [
                        'shipping_method' => $additionalDataArray['mirakl_shipping_data']->method_title ?? '',
                        'shipping_price' => $additionalDataArray['mirakl_shipping_data']->amount ?? '',
                        'lineItems' => $miraklItems,
                        'shipping_address' => $additionalDataArray['mirakl_shipping_data']->address ?? []
                    ];

                    // Add Freight Shipping Info for Marketplace - To be used for Order Confirmation Page
                    if ($this->marketplaceRatesHelper->isFreightShippingEnabled()) {
                        $marketPlaceInfo['marketplace_freight_shipping_enabled'] = $this->marketplaceRatesHelper->isFreightShippingEnabled();
                        $marketPlaceInfo['marketplace_freight_surcharge_text'] = $this->marketplaceRatesHelper->getFreightShippingSurchargeText();
                    }

                    $checkoutResponseData->output->checkout->marketPlace = $marketPlaceInfo;
                    $checkoutResponse[0] = json_encode($checkoutResponseData);
                }
                if ($isOrderApproval &&
                    $this->toggleConfig->getToggleConfigValue('xmen_D190450_fix')
                ) {
                    $this->orderApprovalViewModel->saveEstimatedPickupTime($response, $order);
                }
            }

            return $checkoutResponse;
        } elseif ($isTransactionCreated) {
            /* in case of transaction api timedout and user again click onto submit
             order button then call again transaction api to update magento order with
             new status */
            $quote = $this->submitOrderModelAPI->setTimeoutFlag($quote);
            $transId = null;
            if (isset($response['response']['output']) && $response['response']['output']['trasactionDetails']) {
                $output = $response['response']['output']['trasactionDetails'];
                $transId = $output['orderReferenceSearch']['orderReferences']['0']['txnDetails']['retailTransactionId'];
            }
            $orderConfirmationPage = $this->instoreConfig->isFixPlaceOrderRetry() &&
                str_starts_with($orderNumber, \Fedex\Punchout\Helper\Data::INSTORE_GTN_PREFIX) ? false : true;
            if ($isOrderApproval &&
                $this->toggleConfig->getToggleConfigValue('xmen_D190450_fix')
            ) {
                $order = $this->orderApprovalViewModel->getOrder($orderNumber);
                $this->orderApprovalViewModel->saveEstimatedPickupTime($response, $order);
            }

            return $this->submitOrderModelAPI->getTransactionAPIResponse($quote, $transId, $orderConfirmationPage);
        } elseif (!empty($response['error']) && !empty($response['response']['errors']) && $isOrderApproval) {
            $this->submitOrderModelAPI->updateQuoteStatusAndTimeoutFlag($quote, false, 0);
        }

        return $response;
    }

    /**
     * @param $quote
     * @return false|NegotiableQuoteInterface
     */
    private function getNegotiableQuote($quote)
    {
        if ($quote->getExtensionAttributes()) {
            return $quote->getExtensionAttributes()->getNegotiableQuote();
        }

        return false;
    }

    /**
     * @param CartInterface $quote
     * @param $requestData
     * @return void
     * @throws GraphQlFujitsuResponseException
     */
    private function setOrderNotes(CartInterface $quote, $requestData): void
    {
        $orderNotes = !empty($requestData->notes) ? $requestData->notes : null;
        if (!empty($orderNotes)) {
            $quote->setData('order_notes', json_encode($orderNotes));
            $rateQuoteResponse = $this->fxoRateQuote->getFXORateQuote($quote);
            if ($this->isValidRateQuoteResponse($rateQuoteResponse)) {
                $this->commandOrderNoteSave->execute($quote, json_encode($orderNotes));
            }
        }
    }

    /**
     * @param $quote
     * @param $requestData
     * @return mixed
     */
    public function fixQuoteShippingAddressEmailWithAlternateContactInfo($quote, $requestData)
    {
        $D209119Toggle = $this->toggleConfig->getToggleConfigValue('tiger_d209119') ?? true;
        $isAlternate = $quote->getIsAlternate() || $quote->getIsAlternatePickup();
        if ($D209119Toggle && $isAlternate) {

            $pickupData = $requestData->pickupData ?? '[]';
            $pickupData = is_object($pickupData) ? $pickupData : json_decode($pickupData);
            $shippingAddress = $quote->getShippingAddress();
            if ($this->isAlternateDifferentFromShipping($pickupData, $shippingAddress)) {

                $contactInformation = $pickupData->contactInformation;
                $shippingAddress->setEmail($contactInformation->alternate_email);
            }

            $quote->setShippingAddress($shippingAddress);
        }
        return $quote;
    }

    /**
     * Check if alternate email is different from shipping email
     *
     * @param $pickupData
     * @param $shippingAddress
     * @return bool
     */
    private function isAlternateDifferentFromShipping($pickupData, $shippingAddress)
    {
        if($pickupData && !empty($pickupData->contactInformation)
            && !empty($pickupData->contactInformation->isAlternatePerson)
            && !empty($pickupData->contactInformation->alternate_email)
            && ($pickupData->contactInformation->alternate_email != $shippingAddress->getEmail())) {
            return true;
        }

        return false;
    }

    /**
     * Set last real order information in customer session
     *
     * @param $quote
     * @param $order
     * @return void
     */
    private function setLastRealOrderInfo($quote, $order)
    {
        $this->customerSession->setLastQuoteId($quote->getId());
        $this->customerSession->setLastSuccessQuoteId($quote->getId());
        $this->customerSession->setLastOrderId($order->getId());
        $this->customerSession->setLastRealOrderId($order->getIncrementId());
        $this->customerSession->setLastOrderStatus($order->getStatus());
    }
}
