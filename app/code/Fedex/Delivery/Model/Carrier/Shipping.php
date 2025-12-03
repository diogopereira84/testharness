<?php
declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Model\Carrier;

use Magento\Checkout\Model\SessionFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Fedex\Delivery\Helper\Delivery;
use Fedex\Delivery\Helper\Data;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Punchout\Helper\Data as TokenHelper;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Framework\App\State;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Area;

/**
 * Shipping Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Shipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var ScopeConfigInterface
     */
    public const CONFIG_FLAG = 'configFlag';
    public const RESULT = 'result';
    public const ADDRESS_INFORMATION = 'addressInformation';
    public const SHIPPING_METHOD_CODE = 'shipping_method_code';
    public const PICKUP = 'PICKUP';
    public const TOKEN = 'token';
    public const SERVICE_TYPE = 'serviceType';
    public const PRODUCTION_LOCATION = 'productionLocation';
    public const NO_ITEMS_FOUND = 'No Items were Found';
    public const XML_PATH_D203990_TOGGLE = 'tiger_d203990';
    protected $_code = 'fedexshipping';
    public $param;

    /**
     * Shipping constructor.
     *
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param Delivery $deliveryHelper
     * @param TokenHelper $gateTokenHelper
     * @param MethodFactory $rateMethodFactory
     * @param Data $helper
     * @param ScopeConfigInterface $configInterface
     * @param State $state
     * @param Request $requestU
     * @param RequestInterface $requestObj
     * @param SessionFactory $checkoutSessionFactory
     * @param Session $checkoutSession
     * @param ToggleConfig $toggleConfig
     * @param CustomerSession $customerSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param SelfReg $selfregHelper
     * @param QuoteHelper $quoteHelper
     * @param array $data
     * @param array $param
     * @param array $mapping
     */
    public function __construct(
        ErrorFactory $rateErrorFactory,
        public LoggerInterface $logger,
        protected ResultFactory $rateResultFactory,
        public Delivery $deliveryHelper,
        protected TokenHelper $gateTokenHelper,
        protected MethodFactory $rateMethodFactory,
        public Data $helper,
        public ScopeConfigInterface $configInterface,
        public State $state,
        public Request $requestU,
        public RequestInterface $requestObj,
        protected SessionFactory $checkoutSessionFactory,
        protected Session $checkoutSession,
        protected ToggleConfig $toggleConfig,
        protected CustomerSession $customerSession,
        protected CompanyRepositoryInterface $companyRepository,
        protected SelfReg $selfregHelper,
        private QuoteHelper $quoteHelper,
        array $data = [],
        array $param = [],
        array $mapping = ['BUSINESSDAYS' => 'Business Day(s)']
    ) {
        $this->mapping = $mapping;
        $this->getToken();
        $this->getDeliveryApiUrl();
        parent::__construct($configInterface, $rateErrorFactory, $logger, $data);
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @param object RateRequest $request
     * @return bool|Result
     * @throws \Exception
     */
    public function collectRates(RateRequest $request)
    {
        if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_D203990_TOGGLE)) {
            $this->checkoutSession = $this->checkoutSessionFactory->create();
        }
        if ($this->toggleConfig->getToggleConfigValue('explorers_d196641_fix') && $this->state->getAreaCode() == Area::AREA_ADMINHTML) {
            return false;
        }
        /* we tried logger using dependencies but some how it is
        not working in this file thats why used Object Manager */
        /* B-1299551 toggle clean up start end */
        $isEproCustomer = $this->helper->isEproCustomer();
        $isSelfRegCustomer = $this->selfregHelper->isSelfRegCustomer();
        $result = $this->setQuoteRateData($isEproCustomer, $isSelfRegCustomer);
        $result = $this->checkGetConfigFlag($result);
        if ($result[self::RESULT] || !$result[self::CONFIG_FLAG]) {
            return $result[self::RESULT] ? $result[self::RESULT] : $result[self::CONFIG_FLAG];
        }
        $requestData = $this->requestObj->getContent();
        $requestData = json_decode((string)$requestData, true);
        // Save production location in quote table Epro
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $this->saveProductionLocationIdInQuote($isEproCustomer, $requestData);
        }

        // Check cart items and set shipping method and rate data

        if (isset($requestData['isPickup']) && $requestData['isPickup']) {
            return false;
        }

        if ($this->toggleConfig->getToggleConfigValue('explorer_D196640_add_to_cart_is_calling_delivery_options')) {
            // to stop delivery option call on cart page refresh
            if (isset($requestData['addressInformation']['address']) && empty($requestData['addressInformation']['address']) ) {
                return $result;
            }
        }

        $result = $this->isCartEmptySetShippingMethodAndRate($request);
        if ($result) {
            if ($result == self::NO_ITEMS_FOUND) {
                return false;
            }

            return $result;
        }

        $result = $this->isOrderCreatedProgramtically($request);
        if ($result) {
            if ($result == self::NO_ITEMS_FOUND) {
                return false;
            }

            return $result;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();
        $isDeliveryEnabled = $this->helper->getIsDelivery();
        $accessToken = $this->getAccessToken();
        if ($isDeliveryEnabled) {
            $this->param['street'] = $request->getDestStreet();
            $this->param['country_id'] = $request->getDestCountryId();
            $this->param['region_id'] = $request->getDestRegionId();
            $this->param['postcode'] = $request->getDestPostcode();
            $this->param['city'] = $request->getDestCity();
            $this->setParamSiteAndToken();
            $this->param['access_token'] = $accessToken;

            $items = $request->getAllItems();
            $firstItem = reset($items);
            $quote = null;
            if ($firstItem) {
                $quote = $firstItem->getQuote();
            } elseif ($request->getQuote()) {
                $quote = $request->getQuote();
            }

            if ($this->toggleConfig->getToggleConfigValue('explorers_epro_upload_to_quote') || $this->toggleConfig->getToggleConfigValue('explorers_site_level_quoting_stores')) {
                // If its full Mirakl Quote, we don't need to call Delivery API
                if (!$this->quoteHelper->isFullMiraklQuote($quote)
                 && !$this->deliveryHelper->isCheckoutQuotePriceableDisable($quote)) {

                    if ($this->toggleConfig->getToggleConfigValue('explorer_D196640_add_to_cart_is_calling_delivery_options')) {
                        if (isset($requestData['reRate'])) {
                            $deliveryOptions = $this->getDeliveryOptionsData();
                            $this->customerSession->setDeliveryOptionsResponse($deliveryOptions);
                        } else {
                            $deliveryOptions = $this->customerSession->getDeliveryOptionsResponse();
                        }
                    } else {
                        $deliveryOptions = $this->getDeliveryOptionsData();
                    }
                    if (empty($deliveryOptions)) {
                        $deliveryOptions = $this->getDeliveryOptionsData();
                    }
                } else {
                    return $result;
                }
            } else {
                // If its full Mirakl Quote, we don't need to call Delivery API
                if (!$this->quoteHelper->isFullMiraklQuote($quote)) {
                    $deliveryOptions = $this->getDeliveryOptionsData();
                } else {
                    return $result;
                }
            }

            if (isset($deliveryOptions['code']) && $deliveryOptions['code'] == "400") {
                return false;
            }

            $pickupRequestData = $this->requestObj->getPost('data');
            $pickupRequestData = json_decode((string)$pickupRequestData, true);
            $pickupData = $this->setPickupData($pickupRequestData);

            $phpSessionId = $this->gateTokenHelper->getPHPSessionId();
            if ($this->deliveryHelper->isItPickup($pickupData)) {
                if (!$this->quoteHelper->isMiraklQuote($quote)) {
                    $result = $this->setShippingRate($quote);
                    if ($result) {
                        if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_D203990_TOGGLE)) {
                            $this->setupPickupShippingRate($result, $pickupData);
                        }
                        return $result;
                    }
                }
                if ($result == null) {
                    $result = $this->rateResultFactory->create();
                    $this->logger->info($phpSessionId.' '.__METHOD__ . ':' . __LINE__ .
                    ' Redeclare Result Variable for the defect D-148529.');
                }
                $this->setupPickupShippingRate($result, $pickupData);
            } elseif (count($deliveryOptions)) {
                $result = $this->generateRateData($deliveryOptions, $requestData, $result);
            } else {
                $this->logger->critical(
                    $phpSessionId . ' '.__METHOD__ . ':' . __LINE__ .
                    ' No data being returned from delivery options api.' .
                    ' Request data: ' . json_encode($requestData ?? []) .
                    ' Trace details: ' . \Magento\Framework\Debug::backtrace(true, false, false)
                );
                if ($this->helper->isD175160ToggleEnabled() && $this->helper->isEproCustomer()) {
                    $exceptionMessage = $this->helper->getMessageError();
                    throw new \Exception(__($exceptionMessage));
                }
            }
        }

        return $result;
    }

    public function setupPickupShippingRate($result, $pickupData)
    {
        $rate = $this->rateMethodFactory->create();
        $rate->setCarrier($pickupData[self::ADDRESS_INFORMATION]['shipping_carrier_code']);
        $rate->setCarrierTitle('');
        $rate->setMethod($pickupData[self::ADDRESS_INFORMATION][self::SHIPPING_METHOD_CODE]);
        $rate->setMethodTitle($pickupData[self::ADDRESS_INFORMATION]['shipping_detail']['method_title']);
        $shippingCost = 0.00;
        $shippingCost = $this->getShippingCost($shippingCost);
        $rate->setPrice($shippingCost);
        $rate->setCost($shippingCost);
        $result->append($rate);
    }

    /**
     * Set Pickup Data
     */
    public function setPickupData($pickupRequestData)
    {
        $pickupData = [];
        if (isset($pickupRequestData['pickupData'])) {
            $pickupData = $pickupRequestData['pickupData'];
            $pickupData = is_array($pickupData) ?
                $pickupData :
                json_decode((string)$pickupData,true);
        }
        return $pickupData;
    }

    /**
     * Set Param Site And Token
     */
    public function setParamSiteAndToken()
    {
        if (!$this->helper->isCommercialCustomer()) {
            $this->param['site'] = null;
            $this->param[self::TOKEN] = $this->gateTokenHelper->getAuthGatewayToken();
        } else {
            $this->param['site'] = $this->helper->getCompanySite();
        }
    }

    /**
     * Check ConfigFlag
     */
    public function checkGetConfigFlag($result)
    {
        $configFlag = true;
        $quoteRateResult = false;
        if ($result) {
            $quoteRateResult =  $result;
        }

        if (!$this->getConfigFlag('active')) {
            $configFlag =  false;
        }

        return [
            self::RESULT => $quoteRateResult,
            self::CONFIG_FLAG => $configFlag
        ];
    }

    /**
     * Get Access Token
     */
    public function getAccessToken()
    {
        if (!$this->helper->isCommercialCustomer()) {
            $accessToken = $this->gateTokenHelper->getTazToken();
        } else {
            $accessToken = $this->helper->getApiToken();
            $accessToken = $accessToken[self::TOKEN];
        }

        return $accessToken;
    }

    /**
     * Get Delivery Options Data
     */
    public function getDeliveryOptionsData()
    {
        return $this->deliveryHelper->getDeliveryOptions($this->param);
    }

    /**
     * Generate Rate Data
     */
    public function generateRateData($deliveryOptions, $requestData, $result)
    {
        $locationIds = [];
        if ($this->helper->isCommercialCustomer()) {
            $i = 0;
            foreach ($deliveryOptions as $deliveryOption) {
                if (str_contains(strtolower((string)$deliveryOption[self::SERVICE_TYPE]), 'home') &&
                (isset($requestData['address']['company']) && $requestData['address']['company'] != "")) {
                    unset($deliveryOptions[$i]);
                }
                $i++;
            }
        }

        if ($this->helper->isOurSourced()) {
            $i = 0;
            foreach ($deliveryOptions as $deliveryOption) {
                if (str_contains($deliveryOption[self::SERVICE_TYPE], 'LOCAL_DELIVERY')) {
                    unset($deliveryOptions[$i]);
                }
                $i++;
            }
        }

        foreach ($deliveryOptions as $deliveryOption) {
            $expectedDate = $this->getExpectedDateTimeValue($deliveryOption);
            $rate = $this->rateMethodFactory->create();
            $rate->setCarrier($this->_code);
            $rate->setCarrierTitle($deliveryOption['serviceDescription']);
            if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
                $locationIds[$deliveryOption[self::SERVICE_TYPE]] = $deliveryOption['productionLocationId'];
            }
            $rate->setMethod($deliveryOption[self::SERVICE_TYPE]);
            $rate->setMethodTitle($expectedDate);
            $shippingCost = str_replace([",", "$"], "", $deliveryOption['estimatedShipmentRate']);
            $shippingCost = $this->getShippingCostWithServiceType($shippingCost, $deliveryOption);
            $rate->setCost($shippingCost);
            $rate->setPrice($shippingCost);
            $rate->setExpected($shippingCost);

            if (
                $this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') &&
                $this->helper->isCommercialCustomer()
            ) {
                $rate->setData('production_location', $deliveryOption['productionLocationId']);
            }
            $result->append($rate);
        }

        // D-192068 :: Commercial B2B Approval - Ship Orders don't route to customer selected production location
        $toggleD192068FixEnabled = $this->toggleConfig->getToggleConfigValue('explorers_D192068_fix');
        if ($toggleD192068FixEnabled && !$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $this->checkoutSession->setLocationIds($locationIds);
        }

        return $result;
    }

    /**
     * Get Expected Date Time Value
     *
     * @param array $deliveryOption
     * @return string
     */
    public function getExpectedDateTimeValue($deliveryOption)
    {
        return $this->deliveryHelper->getExpectedDateFormat($deliveryOption);
    }

    /**
     * Get decreypt token
     * @return string $token
     */
    public function getToken()
    {
        $this->param[self::TOKEN] = $this->gateTokenHelper->getAuthGatewayToken();
    }

    /**
     * Get API Config URL
     * @return string $url
     */
    public function getDeliveryApiUrl()
    {
        $this->param['delivery_api'] = $this->configInterface->getValue("fedex/general/delivery_api_url");
        $isDeliveryApiMockEnabled = $this->deliveryHelper->isDeliveryApiMockEnabled();
        if($isDeliveryApiMockEnabled) {
            $this->param['delivery_api'] = $this->deliveryHelper->getDeliveryMockApiUrl();
        }
    }

    /**
     * Set rate data
     *
     * @param bool $isEproCustomer
     * @param bool $isSelfRegCustomer
     *
     * @return object $result
     */
    public function setQuoteRateData($isEproCustomer, $isSelfRegCustomer)
    {
        if (!$isEproCustomer || $isSelfRegCustomer) {
            $shippingMethodCode = $this->checkoutSession->getCustomShippingMethodCode();
            $shippingCarrierCode = $this->checkoutSession->getCustomShippingCarrierCode();
            $shippingTitle = $this->checkoutSession->getCustomShippingTitle();
            $shippingPrice = $this->checkoutSession->getCustomShippingPrice();
            $shippingPrice = $this->getShippingCost($shippingPrice);
            if (!empty($shippingTitle) &&
            !empty($shippingMethodCode) &&
            !empty($shippingCarrierCode) &&
            $shippingMethodCode == self::PICKUP
            ) {
                $result = $this->rateResultFactory->create();
                $rate = $this->rateMethodFactory->create();
                $rate->setCarrier($shippingCarrierCode);
                $rate->setCarrierTitle('');
                $rate->setMethod($shippingMethodCode);
                $rate->setMethodTitle($shippingTitle);
                if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_D203990_TOGGLE)) {
                    $rate->setPrice(0);
                    $rate->setCost(0);
                    $rate->setExpected(0);
                } else {
                    $rate->setPrice($shippingPrice);
                    $rate->setCost($shippingPrice);
                    $rate->setExpected($shippingPrice);
                }
                $result->append($rate);

                return $result;
            }
        } else {
            /* B-1299551 toggle clean up start end */
            if ($isEproCustomer) {
                $shippingMethodCode = $this->checkoutSession->getCustomShippingMethodCode();
                $shippingCarrierCode = $this->checkoutSession->getCustomShippingCarrierCode();
                $shippingTitle = $this->checkoutSession->getCustomShippingTitle();
                $shippingPrice = $this->checkoutSession->getCustomShippingPrice();
                $shippingPrice = $this->getShippingCost($shippingPrice);

                if (!empty($shippingTitle)
                && !empty($shippingMethodCode)
                && !empty($shippingCarrierCode)
                && !empty($shippingPrice)
                ) {
                    $result = $this->rateResultFactory->create();
                    $rate = $this->rateMethodFactory->create();
                    $rate->setCarrier($shippingCarrierCode);
                    $rate->setCarrierTitle('');
                    $rate->setMethod($shippingMethodCode);
                    $rate->setMethodTitle($shippingTitle);

                    if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
                        $rate->setPrice(0);
                        $rate->setCost(0);
                    } else {
                        $rate->setPrice($shippingPrice);
                        $rate->setCost($shippingPrice);
                    }

                    $result->append($rate);

                    return $result;
                }
            }
        }
    }

    /**
     * Check Is Order Created Programatically
     *
     * @param  object  $request
     * @return object
     */
    public function isOrderCreatedProgramtically($request)
    {
        $str = $this->requestU->getRequestUri();
        $pattern = "/\bstatus\b/";


        if ($this->state->getAreaCode() == 'webapi_rest'
        && preg_match($pattern, (string)$str)){

            $items = $request->getAllItems();
            if (empty($items)) {
                return self::NO_ITEMS_FOUND;
            }

            /** @var \Magento\Quote\Model\Quote\Item $firstItem */
            $firstItem = reset($items);
            if (!$firstItem) {
                return self::NO_ITEMS_FOUND;
            }

            $quote = $firstItem->getQuote();
            $shippingMethod = $quote->getBillingAddress()->getShippingMethod();
            $shippingDescription = $quote->getBillingAddress()->getData('shipping_description');
            $result = $this->rateResultFactory->create();
            $shippingMethodModified = str_replace(['fedexshipping_', '_'], ["", " "], (string) $shippingMethod);
            $carrierTitle = "FedEx ". $shippingMethodModified;
            $shipMethod = str_replace(" ", "_", (string) $shippingMethodModified);

            $rate = $this->rateMethodFactory->create();
            $rate->setCarrier($this->_code);
            $rate->setMethod($shipMethod);

            if (strpos((string)$shippingMethod, self::PICKUP)) {
                $rate->setMethodTitle('');
                $rate->setCarrierTitle($shippingDescription);
            } else {
                $rate->setMethodTitle();
                $rate->setCarrierTitle($carrierTitle.' - '.$shippingDescription);
            }
            $shippingCost = $quote->getShippingCost() ?? 0;
            $rate->setPrice($shippingCost);
            $rate->setCost($shippingCost);
            $result->append($rate);

            return $result;
        }
    }

    /**
     * Check cart items and set shipping method and rate data
     *
     * @param object $request
     *
     * @return object $result
     */
    public function isCartEmptySetShippingMethodAndRate($request)
    {
        $str = $this->requestU->getRequestUri();
        $pattern = "/negotiable-carts/i";
        $patternSubmitToCustomer = "/negotiableQuote/i";

        if ($this->state->getAreaCode() == 'webapi_rest' &&
        ($str == '/index.php/rest/V1/fedex/eprocurement' ||
        preg_match($patternSubmitToCustomer, (string)$str) || preg_match($pattern, (string)$str))) {

            $items = $request->getAllItems();
            if (empty($items)) {
                return self::NO_ITEMS_FOUND;
            }

            /** @var \Magento\Quote\Model\Quote\Item $firstItem */
            $firstItem = reset($items);
            if (!$firstItem) {
                return self::NO_ITEMS_FOUND;
            }

            $quote = $firstItem->getQuote();
            $shippingMethod = $quote->getBillingAddress()->getShippingMethod();
            $shippingDescription = $quote->getBillingAddress()->getData('shipping_description');
            $result = $this->rateResultFactory->create();
            $shippingMethodModified = str_replace(['fedexshipping_', '_'], ["", " "], (string) $shippingMethod);
            $carrierTitle = "FedEx ". $shippingMethodModified;
            $shipMethod = str_replace(" ", "_", (string) $shippingMethodModified);
            $rate = $this->rateMethodFactory->create();
            $rate->setCarrier($this->_code);
            $rate->setMethod($shipMethod);
            if (strpos((string)$shippingMethod, self::PICKUP)) {
                $rate->setMethodTitle('');
                $rate->setCarrierTitle($shippingDescription);
            } else {
                $rate->setMethodTitle($shippingDescription);
                $rate->setCarrierTitle($carrierTitle);
            }

            $shippingCost = $quote->getShippingCost() ?? 0;
            if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
                $shippingDiscount = $quote->getShippingDiscount() ?? 0;
                $finalShippingCost = $shippingCost - $shippingDiscount;
                $rate->setPrice($finalShippingCost);
                $rate->setCost($finalShippingCost);
            } else {
                $rate->setPrice($shippingCost);
                $rate->setCost($shippingCost);
            }

            $result->append($rate);

            return $result;
        }
    }

    /**
     * Save production location in quote table Epro
     *
     * @param bool $isEproCustomer
     * @param array $requestData
     *
     * @return void
     */
    public function saveProductionLocationIdInQuote($isEproCustomer, $requestData)
    {
        //B-995245 - Sanchit Bhatia Save production location id in Quote Table.
        if ($isEproCustomer) {
            $companyId = $this->customerSession->getCustomerCompany();
            $customerRepo = $this->companyRepository->get((int) $companyId);
            if (
                $this->firstConditionToSetProductionLocationId($customerRepo) &&
                $this->secondConditionToSetProductionLocationId($requestData)
            ) {
                $this->checkoutSession->setProductionLocationId($requestData[self::PRODUCTION_LOCATION]);
            } else {
                $this->checkoutSession->unsProductionLocationId();
            }
        }
    }

    /**
     * Passing First Condition For Setting Production Location
     */
    public function firstConditionToSetProductionLocationId($customerRepo)
    {
        return $customerRepo->getAllowProductionLocation() == 1 &&
        $customerRepo->getProductionLocationOption() == 'recommended_location_all_locations';
    }

    /**
     * Passing Second Conditions For Setting Production Location
     */
    public function secondConditionToSetProductionLocationId($requestData)
    {
        return isset($requestData[self::PRODUCTION_LOCATION]) &&
        $requestData[self::PRODUCTION_LOCATION]!='' &&
        $requestData[self::PRODUCTION_LOCATION] != null;
    }

    /**
     * Get Shipping Cost
     */
    public function getShippingCost($shippingCost)
    {
        if (!empty($this->checkoutSession->getShippingCost())
        ) {
            $shippingCost = $this->checkoutSession->getShippingCost();
        }

        return $shippingCost;
    }

    /**
     * Get Shipping Cost
     */
    public function getShippingCostWithServiceType($shippingCost, $deliveryOption)
    {
        if (!empty($this->checkoutSession->getServiceType()) &&
        !empty($this->checkoutSession->getShippingCost()) &&
        $this->checkoutSession->getServiceType() == $deliveryOption[self::SERVICE_TYPE]
        ) {
            $shippingCost = $this->checkoutSession->getShippingCost();
        }

        return $shippingCost;
    }

    /**
     * Set shipping rate
     * @return null|Object
     */
    public function setShippingRate($quote)
    {
        $shippingMethodCode = $this->checkoutSession->getCustomShippingMethodCode();

        if (empty($shippingMethodCode)) {
            $extShipInfo = $quote->getExtShippingInfo();
            if (!empty($extShipInfo)) {
                $extShipInfoData = json_decode($extShipInfo, true);
                $shippingMethodCode = $extShipInfoData['shippingMethodCode'];
                if (empty($this->checkoutSession->getCustomShippingCarrierCode())) {
                    $this->checkoutSession->setCustomShippingCarrierCode($extShipInfoData['shippingCarrierCode']);
                }
                if (empty($this->checkoutSession->getCustomShippingTitle())) {
                    $this->checkoutSession->setCustomShippingTitle($extShipInfoData['shipMethodTitle']);
                }
            }
        }
        if ($shippingMethodCode !== 'PICKUP') {
            $shippingCarrierCode = $this->checkoutSession->getCustomShippingCarrierCode();
            $shippingTitle = $this->checkoutSession->getCustomShippingTitle();
            $shippingPrice = $this->checkoutSession->getCustomShippingPrice();
            $shippingPrice = $this->getShippingCost($shippingPrice);

            if (empty($shippingPrice)) {
                $shippingPrice = $quote->getShippingCost();
            }

            if (!empty($shippingTitle)
            && !empty($shippingMethodCode)
            && !empty($shippingCarrierCode)
            && !empty($shippingPrice)
            ) {
                $result = $this->rateResultFactory->create();
                $rate = $this->rateMethodFactory->create();
                $rate->setCarrier($shippingCarrierCode);
                $rate->setCarrierTitle('');
                $rate->setMethod($shippingMethodCode);
                $rate->setMethodTitle($shippingTitle);
                $rate->setPrice($shippingPrice);
                $rate->setCost($shippingPrice);
                $result->append($rate);

                return $result;
            }

            return null;
        }

        return null;
    }

}
