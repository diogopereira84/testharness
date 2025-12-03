<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\TransactionApi;

use Exception;
use Fedex\CartGraphQl\Model\PlaceOrder\SaveQuoteIntegrationRetryData;
use Fedex\EnhancedProfile\Helper\Account;
use Laminas\Http\Client\Adapter\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\SubmitOrderSidebar\Helper\SubmitOrderOptimizedHelper;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Fedex\SubmitOrderSidebar\Model\BillingAddressBuilder;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\CoreApi\Model\LogHelperApi;

/**
 * RateQuoteAndTransactionApiHandler Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class RateQuoteAndTransactionApiHandler
{
    public const DEFAULT_TIMEZONE = 'America/Chicago';
    /**
     * constant for rate post api
     */
    public const RATE_POST_API_URL = "fedex/general/rate_post_api_url";
    /**
     * constant for rate quote post api
     */
    public const RATE_QUOTE_POST_API_URL = "fedex/rateQuote/rate_post_api_url";
    /**
     * constant for rate post api
     */
    public const GENERAL_TRANSACTION_POST_API_URL = "fedex/general/transaction_post_api_url";
    /**
     * constant for transaction api timeout value
     */
    public const TRANSACTION_POST_API_TIMEOUT_VALUE = "fedex/general/transaction_post_api_timeout_value";
    /**
     * constant for rate quote post api
     */
    public const TRANSACTION_POST_API_URL = "fedex/transaction/transaction_post_api_url";
    /**
     * constant for transaction search api
     */
    public const TRANSACTION_SEARCH_POST_API_URL = "fedex/general/transaction_search_post_api_url";
    /**
     * constant for get transaction api
     */
    public const GET_TRANSACTION_API_URL = "fedex/general/get_transaction_api_url";
    /**
     * constant for address classification home
     */
    public const HOME = "HOME";
    /**
     * constant for address classification business
     */
    public const BUSINESS = "BUSINESS";
    /**
     * constant for failure
     */
    private const FAILURE = "Failure";

    /**
     * constant for epro order placement fix
     */
    private const LOCAL_DELIVERY_EPRO_ORDER_FIX_ENABLED = "mazegeeks_D209388_ePro_order_fix";

    public const API_DEBUG_STATUS_CODE_TXT = 'Transaction API Status code = ';
    public const MESSAGE_TXT = ' - Message: ';
    public const QUOTE_ID = ' Quote ID:';
    public const GTN_NUMBER_TEXT = ' GTN Number => ';
    public const TOKEN = 'token';
    public const OUTPUT = 'output';
    public const ERRORS = 'errors';
    public const ERROR = 'error';
    public const RESPONSE = 'response';
    public const IS_CARD_AUTHORIZE = 'iscardAuthorize';
    public const MSG = 'msg';
    public const MESSAGE = 'message';
    public const STATE = 'state';
    public const REGION_CODE = 'regionCode';
    public const CC_TOKEN = 'ccToken';
    public const NAME_ON_CARD = 'nameOnCard';

    /**
     * RateQuoteAndTransactionApiHandler constructor
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param ScopeConfigInterface $configInterface
     * @param DeliveryHelper $deliveryHelper
     * @param PunchoutHelper $punchoutHelper
     * @param CompanyHelper $companyHelper
     * @param SdeHelper $sdeHelper
     * @param SubmitOrderHelper $submitOrderHelper
     * @param SubmitOrderOptimizedHelper $submitOrderOptimizedHelper
     * @param LoggerInterface $logger
     * @param RegionFactory $regionFactory
     * @param Curl $curl
     * @param TimezoneInterface $timezoneInterface
     * @param EnhancedProfile $enhancedProfile
     * @param DataObjectFactory $dataObjectFactory
     * @param SubmitOrderDataArray $submitOrderDataArray
     * @param ToggleConfig $toggleConfig
     * @param LaminasClientFactory $httpClientFactory
     * @param BillingAddressBuilder $billingAddressBuilder
     * @param InStoreRequestBuilder $inStoreRequestBuilder
     * @param InstoreConfig $instoreConfig
     * @param Account $accountHelper
     * @param HeaderData $headerData
     * @param SaveQuoteIntegrationRetryData $saveQuoteIntegrationRetryData
     * @param LogHelperApi $logHelperApi
     */
    public function __construct(
        protected CartRepositoryInterface $quoteRepository,
        protected ScopeConfigInterface $configInterface,
        protected DeliveryHelper $deliveryHelper,
        private PunchoutHelper $punchoutHelper,
        protected CompanyHelper $companyHelper,
        protected SdeHelper $sdeHelper,
        private SubmitOrderHelper $submitOrderHelper,
        protected SubmitOrderOptimizedHelper $submitOrderOptimizedHelper,
        protected LoggerInterface $logger,
        protected RegionFactory $regionFactory,
        protected Curl $curl,
        private TimezoneInterface $timezoneInterface,
        protected EnhancedProfile $enhancedProfile,
        private DataObjectFactory $dataObjectFactory,
        private SubmitOrderDataArray $submitOrderDataArray,
        protected ToggleConfig $toggleConfig,
        private LaminasClientFactory $httpClientFactory,
        protected BillingAddressBuilder $billingAddressBuilder,
        protected InStoreRequestBuilder $inStoreRequestBuilder,
        private InstoreConfig $instoreConfig,
        private Account $accountHelper,
        protected HeaderData $headerData,
        protected SaveQuoteIntegrationRetryData $saveQuoteIntegrationRetryData,
        private readonly LogHelperApi $logHelperApi
    )
    {
    }

    /**
     * Get config value
     *
     * @param string $config
     * @return mixed
     */
    public function getConfigValue($config)
    {
        return $this->configInterface->getValue($config);
    }

    /**
     * Get Commerical customer
     *
     * @return bool
     */
    public function getCommercialCustomer():bool
    {
        return $this->deliveryHelper->isCommercialCustomer();
    }

    /**
     * Get Commerical customer
     */
    public function getCustomer()
    {
        return $this->deliveryHelper->getCustomer();
    }

    /**
     * Get Headers for curl request
     *
     * @param null|array $tokenStr
     * @return string[]
     */
    public function getHeaders(?array $tokenStr)
    {
        $commercialCustomer = $this->getCommercialCustomer();
        if (!$commercialCustomer) {
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        } else {
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        }
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'json',
            $authHeaderVal . $gateWayToken
        ];

        if (isset($tokenStr[self::TOKEN]) && $tokenStr[self::TOKEN]) {
            $headers['Cookie'] = "Bearer=" . $tokenStr[self::TOKEN];
        } else {
            $headers = $this->submitOrderHelper->getCustomerOnBehalfOf($headers);
        }

        return $headers;
    }

    /**
     * Get Checkout Response Data
     *
     * @param object $dataObjectForFujistu
     * @param array $rateQuoteResponse
     * @return array
     */
    public function getCheckoutResponseData($dataObjectForFujistu, $rateQuoteResponse)
    {
        $paymentData = $dataObjectForFujistu->getPaymentData();
        $estimatePickupTime = $dataObjectForFujistu->getEstimatePickupTime();
        $quoteId = $dataObjectForFujistu->getQuoteId();
        $orderNumber = $dataObjectForFujistu->getOrderNumber();

        if (empty($rateQuoteResponse[self::ERRORS]) && isset($rateQuoteResponse[self::OUTPUT])) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':' . " Fujitsu Rate Quote API success for the ".self::QUOTE_ID
                . $quoteId . self::GTN_NUMBER_TEXT . $orderNumber
            );
            $fjmpRateQuoteId = $this->submitOrderHelper->getRateQuoteId($rateQuoteResponse);

            $quote = $this->quoteRepository->getActive($quoteId);
            $quote->setData('fjmp_quote_id', $fjmpRateQuoteId);
            $quote->setData('estimated_pickup_time', $estimatePickupTime);

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':' . " Fujitsu Rate ". self::QUOTE_ID .
                $fjmpRateQuoteId . " for the ". self::QUOTE_ID . $quoteId . self::GTN_NUMBER_TEXT . $orderNumber
            );

            if ($paymentData->paymentMethod == 'instore') {
                $checkoutResponse = $this->handleInstoreTransactionAPI(
                    $fjmpRateQuoteId,
                    $quoteId,
                    $rateQuoteResponse,
                    $dataObjectForFujistu
                );
            } else {
                $checkoutResponse = $this->constructTransactionAPI(
                    $quote,
                    $fjmpRateQuoteId,
                    $rateQuoteResponse,
                    $dataObjectForFujistu
                );
            }

            return $this->validateCheckoutResponse(
                $quote,
                $checkoutResponse,
                $dataObjectForFujistu,
                $rateQuoteResponse
            );
        } else {
            $this->submitOrderOptimizedHelper->unsetOrderInProgress();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':' . " Fujitsu Rate Quote API failed for the ". self::QUOTE_ID. $quoteId
            );

            return [self::ERROR => 1, self::MSG => self::FAILURE, self::RESPONSE => $rateQuoteResponse];
        }
    }

    /**
     * Validate Checkout Response
     *
     * @param object $quote
     * @param array $checkoutResponse
     * @param object $dataObjectForFujistu
     * @param array $rateQuoteResponse
     * @return array
     */
    public function validateCheckoutResponse(
        $quote,
        $checkoutResponse,
        $dataObjectForFujistu,
        $rateQuoteResponse
    ) {
        $quoteId = $dataObjectForFujistu->getQuoteId();
        $orderNumber = $dataObjectForFujistu->getOrderNumber();

        if (!empty($checkoutResponse)) {
            $boolCardAuthorizationStatus = true;
            $retailTransactionId = null;
            $productLineDetailsAttributes = null;

            if (isset($checkoutResponse[self::RESPONSE])) {
                $transactionResponse = json_decode((string)$checkoutResponse[self::RESPONSE]);

                //D-97873 added !empty condition (failed tests in UT)
                if (!empty($transactionResponse->errors) || !empty($transactionResponse->output->alerts)) {
                    $boolCardAuthorizationStatus = false;

                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__ .':' . self::QUOTE_ID .
                        $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' return => ' .
                        json_encode([
                            self::ERROR => 1,
                            self::MSG => 'Failure',
                            self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                            self::RESPONSE => $checkoutResponse,
                        ])
                    );
                    return [
                        self::ERROR => 1,
                        self::MSG => self::FAILURE,
                        self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                        self::RESPONSE => $checkoutResponse,
                    ];
                }

                $transactionConfig = $this->getTransactionIdAndProductLinesAttributes($transactionResponse);
                $retailTransactionId = $transactionConfig['retailTransactionId'];
                $productLineDetailsAttributes = $transactionConfig['productLineDetailsAttributes'];
            }

            return $this->placeOrderProcessing(
                $quote,
                $checkoutResponse,
                $boolCardAuthorizationStatus,
                $retailTransactionId,
                $productLineDetailsAttributes,
                $dataObjectForFujistu,
                $rateQuoteResponse
            );
        } else {
            $this->submitOrderOptimizedHelper->unsetOrderInProgress();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':'
                . self::QUOTE_ID . $quoteId . ' $checkoutResponse => Error found no data'
            );

            return [self::ERROR => 1, self::MSG => 'Error found no data', self::RESPONSE => ''];
        }
    }

    /**
     * Place Order Processing
     *
     * @param object $quote
     * @param array $checkoutResponse
     * @param bool $boolCardAuthorizationStatus
     * @param int|string $retailTransactionId
     * @param string|null $productLineDetailsAttributes
     * @param object $dataObjectForFujistu
     * @param array $rateQuoteResponse
     * @return array
     */
    public function placeOrderProcessing(
        $quote,
        $checkoutResponse,
        $boolCardAuthorizationStatus,
        $retailTransactionId,
        $productLineDetailsAttributes,
        $dataObjectForFujistu,
        $rateQuoteResponse
    ) {
        $quoteId = $dataObjectForFujistu->getQuoteId();

        if ((isset($checkoutResponse[self::ERROR]) && $checkoutResponse[self::ERROR] == 0)
        && isset($checkoutResponse[self::RESPONSE]) && $boolCardAuthorizationStatus) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':' . " Retail Transaction Id received :" .
                $retailTransactionId . " for the quote id " . $quoteId
            );

            return $this->finalizeCheckoutResponse(
                $quote,
                $checkoutResponse,
                $retailTransactionId,
                $productLineDetailsAttributes,
                $dataObjectForFujistu,
                $rateQuoteResponse
            );
        } else {
            $this->submitOrderOptimizedHelper->unsetOrderInProgress();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .': '.self::QUOTE_ID . $quoteId .' Transaction CXS API failed'
            );

            return [
                self::ERROR => 1,
                self::MSG => self::FAILURE,
                self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                self::RESPONSE => $checkoutResponse,
            ];
        }
    }

    /**
     * Finally Call Place Order
     *
     * @param object $quote
     * @param array $checkoutResponse
     * @param int|string $retailTransactionId
     * @param string|null $productLineDetailsAttributes
     * @param object $dataObjectForFujistu
     * @param array $rateQuoteResponse
     * @return array
     */
    public function finalizeCheckoutResponse(
        $quote,
        $checkoutResponse,
        $retailTransactionId,
        $productLineDetailsAttributes,
        $dataObjectForFujistu,
        $rateQuoteResponse
    ) {
        $quoteId = $dataObjectForFujistu->getQuoteId();
        $orderNumber = $dataObjectForFujistu->getOrderNumber();
        $shipmentId = $dataObjectForFujistu->getShipmentId();
        $paymentData = $dataObjectForFujistu->getPaymentData();

        $isSetOrderId = $this->submitOrderHelper->isSetOrderId($quote, $orderNumber);
        if (!$isSetOrderId) {

            return [
                self::ERROR => true,
                self::MESSAGE => 'Set order id is not updated for quote Id:' . $quoteId
            ];
        }

        try {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':'. ' Before Placing the Order '. self::QUOTE_ID . $quoteId
            );

            $orderId = $this->submitOrderHelper->placeOrder(
                $quote,
                $quoteId,
                $shipmentId,
                $retailTransactionId,
                $productLineDetailsAttributes,
                $paymentData
            );
        } catch (Exception $e) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .':' .
                ' Before dumping quote data '. self::QUOTE_ID . $quote->getId());
            $this->logger->info(__METHOD__ . ':' . __LINE__ .':' . self::QUOTE_ID . $quote->getId() .
                ' Customer ID: ' . $quote->getCustomerId() .
                ' Customer Email: ' . $quote->getCustomerEmail() .
                ' Payment: ' . json_encode($quote->getPayment()->getData()) .
                ' Billing Address: ' . json_encode($quote->getBillingAddress()->getData()) .
                ' Shipping Address: ' . json_encode($quote->getShippingAddress()->getData()) .
                ' Stack trace: ' . $e->getTraceAsString());
            $this->logger->info(__METHOD__ . ':' . __LINE__ .':' .
                ' After dumping quote data'. self::QUOTE_ID . $quote->getId());

            $this->logger->error(__METHOD__ . ':' . __LINE__ .':' . self::QUOTE_ID .
                $quoteId . ' Message => ' . $e->getMessage() . ' ' . ' $shipmentId => ' .
                $shipmentId . ' $retailTransactionId => ' .
                $retailTransactionId . ' $productLineDetailsAttributes => ' .
                $productLineDetailsAttributes);

            return [self::ERROR => true, self::MESSAGE => $e->getMessage()];
        }

        $this->logger->info(
            __METHOD__ . ':' . __LINE__ .':'. ' Before prepare producing address '. self::QUOTE_ID . $quoteId
        );
        $this->submitOrderHelper->prepareOrderProducingAddress($checkoutResponse[self::RESPONSE], $orderId);
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ .':'. ' After prepare producing address '. self::QUOTE_ID . $quoteId
        );

        $this->submitOrderHelper->setCookie('quoteId', $quoteId);
        // If Order id is generated then call reorderable Instance API to preserve the instance.
        $this->submitOrderHelper->reorderInstanceSave($orderId);

        // Push quote id in queue to clean item instance from quote
        $this->submitOrderHelper->cleanProductItemInstance($quoteId);

        // Clear quote and generate new one
        $this->submitOrderHelper->clearQuoteCheckoutSessionAndStorage($quoteId, $orderId);

        //B-1275188 include rate quote response in the return inorder to show order totals
        return [
            $checkoutResponse[self::RESPONSE],
            'rateQuoteResponse' => $rateQuoteResponse,
        ];
    }

    /**
     * Call Transaction CXS API
     *
     * @param array $dataa
     * @param int $quoteId
     * @return array
     */
    public function callTransactionAPI($dataa, $quoteId)
    {
        $dataString = json_encode($dataa, JSON_UNESCAPED_SLASHES);
        $this->logHelperApi->info(__METHOD__ . ':' . __LINE__ .': Quote Id => ' . $quoteId .
            ' Before Transaction CXS API $dataString => ' . $dataString);

        if ($this->toggleConfig->getToggleConfigValue('explorers_D179263_fix')) {
            $output = $this->callTransactionApiClientRequest($dataString);
        } else {
            $output = $this->callCurlPost($dataString, 'transaction');
        }

        $this->logHelperApi->info(__METHOD__ . ':' . __LINE__ .':: Quote Id => ' . $quoteId .
            ' After Transaction CXS API $output =>' . $output);

        $transactionResponseData = json_decode((string)$output, true);

        if (!empty($transactionResponseData)) {
            if (empty($transactionResponseData[self::ERRORS]) && isset($transactionResponseData[self::OUTPUT])) {
                return [self::ERROR => 0, self::MSG => 'Success', self::RESPONSE => $output];
            } else {
                if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                    $errorMessage = implode(",", array_column($transactionResponseData[self::ERRORS] ?? [], 'message'));
                    return [self::ERROR => 1, self::MSG => $errorMessage, self::RESPONSE => ''];
                }

                return $this->isTransactionTimeout($transactionResponseData, $output, $quoteId);
            }
        } else {
            $this->submitOrderOptimizedHelper->unsetOrderInProgress();
            $this->logHelperApi->info(__METHOD__ . ':' . __LINE__ .':' . ' Error found no data'.$quoteId);

            return [self::ERROR => 1, self::MSG => 'Error found no data', self::RESPONSE => ''];
        }
    }

    /**
     * Validate Checkout Response If Transaction Timeout
     *
     * @param array $transactionResponseData
     * @param string $output
     * @param int|string $quoteId
     * @return array
     */
    public function isTransactionTimeout($transactionResponseData, $output, $quoteId)
    {
        if ($transactionResponseData[self::ERRORS][0]['code'] == "TIMEOUT") {
            $this->submitOrderOptimizedHelper->unsetOrderInProgress();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':' . 'Transaction API Internal Server Timeout For Quote Id => '.$quoteId
            );

            return [self::ERROR => 1, self::MSG => "timeout", self::RESPONSE => $output];
        } else {
            $this->submitOrderOptimizedHelper->unsetOrderInProgress();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':' . 'Transaction CXS API Failed For Quote Id => '.$quoteId
            );

            return [self::ERROR => 1, self::MSG => self::FAILURE, self::RESPONSE => $output];
        }
    }

    /**
     * Zend client post request for transaction api
     *
     * @param string $dataString
     * @return false|string
     */
    public function callTransactionApiClientRequest(string $dataString)
    {
        $setupURL = $this->configInterface->getValue(self::GENERAL_TRANSACTION_POST_API_URL);
        $accessToken = $this->deliveryHelper->getApiToken();
        $headers = $this->getHeaders($accessToken);

        $client = $this->httpClientFactory->create();
        $client->setOptions(['adapter' => \Laminas\Http\Client\Adapter\Curl::class]);
        $client->setHeaders($headers);
        $client->setUri($setupURL);
        $client->setMethod("POST");
        if (isset($accessToken) === true) {
            $client->setParameterPost($accessToken);
        }

        /* Do not remove this code this is only for dev
        testing of transaction API timeout scenario in local env */
        if ($this->toggleConfig->getToggleConfigValue('test_transaction_timeout_toggle')) {
            $timeOut = $this->configInterface->getValue(self::TRANSACTION_POST_API_TIMEOUT_VALUE);
            if (!empty($timeOut)) {
                $client->setOptions(['timeout' => $timeOut]);
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ .': Validate the transaction api timeout scenario for dev testing only'
                );
            }
        }

        try {
            $client->setRawBody($dataString);
            $response = $client->send();
            $responseStatus = $response->getStatusCode();

            $this->logHelperApi->logResponseStatus($responseStatus,
                __METHOD__ . ':' . __LINE__ .':' . self::API_DEBUG_STATUS_CODE_TXT
                . $responseStatus . self::MESSAGE_TXT . $response->getReasonPhrase()
            );

            if ($responseStatus !== 200 && $this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                throw new GraphQlFujitsuResponseException(__(self::API_DEBUG_STATUS_CODE_TXT . $responseStatus .
                    self::MESSAGE_TXT . $response->getMessage()));
            }
        } catch (RuntimeException|\Exception $e) {
            $this->logHelperApi->error(__METHOD__ . ':' . __LINE__ .':' . $e->getMessage());

            return json_encode(
                [
                    "errors" => [
                        [
                            "code" => "TIMEOUT",
                            "message" => "Internal Server Timeout"
                        ]
                    ]
                ]
            );
        }

        return $response->getBody();
    }

    /**
     * Curl Post Data
     *
     * @param string $dataString
     * @param string $apiName
     */
    public function callCurlPost(string $dataString, $apiName)
    {
        $accessToken = $this->deliveryHelper->getApiToken();

        $isOptimize = $this->toggleConfig->getToggleConfigValue('is_optimize_configuration');
        if ($apiName == 'rate') {
            if ($isOptimize) {
                $setupURL = $this->configInterface->getValue(self::RATE_POST_API_URL);
            } else {
                $setupURL = $this->configInterface->getValue(self::RATE_QUOTE_POST_API_URL);
            }
        } elseif ($apiName == 'transaction') {
            if ($isOptimize) {
                $setupURL = $this->configInterface->getValue(self::GENERAL_TRANSACTION_POST_API_URL);
            } else {
                $setupURL = $this->configInterface->getValue(self::TRANSACTION_POST_API_URL);
            }
        } else {
            $setupURL = $this->configInterface->getValue(self::TRANSACTION_SEARCH_POST_API_URL);
        }

        $headers = $this->getHeaders($accessToken);

        $client = $this->httpClientFactory->create();
        $client->setHeaders($headers);
        $client->setOptions(['adapter' => \Laminas\Http\Client\Adapter\Curl::class]);
        $client->setUri($setupURL);
        $client->setMethod("POST");
        $client->setRawBody($dataString);
        $response = $client->send();

        return $response->getBody();
    }

    /**
     *  Method to create Transaction CXS request.
     *
     * @param object $quote
     * @param int|string $fjmpRateQuoteId
     * @param array $rateQuoteResponse
     * @param object $dataObjectForFujistu
     * @return array
     */
    public function constructTransactionAPI(
        $quote,
        $fjmpRateQuoteId,
        $rateQuoteResponse,
        $dataObjectForFujistu
    ) {
        $expirationMonth = $expirationYear =  $nameOnCard = '';
        $requestedAmount = $numTotal = $numDiscountPrice = null;

        $data = $dataObjectForFujistu->getOrderData();
        $paymentData = $dataObjectForFujistu->getPaymentData();
        $encCCData = $dataObjectForFujistu->getEncCCData();
        $isPickup = $dataObjectForFujistu->getIsPickup();
        $useSiteCreditCard = $dataObjectForFujistu->getUseSiteCreditCard();
        $eproOrder = $dataObjectForFujistu->getEproOrder();
        $companyId = $dataObjectForFujistu->getCompanyId();
        $shippingAddress = $quote->getShippingAddress();
        $shipMethod = $shippingAddress->getShippingMethod();

        if (!$isPickup && $this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix')) {
            $addressClassification = self::BUSINESS;
            $isResidenceShipping = $shippingAddress->getData('is_residence_shipping');
            if ($isResidenceShipping) {
                $addressClassification = self::HOME;
            }
        } else {
            $addressClassification = self::HOME;
            $company = $shippingAddress->getData('company');
            if ($company != null && $company != "") {
                $addressClassification = self::BUSINESS;
            }
        }

        $paymentMethod = $paymentData->paymentMethod;
        $addressData = $this->getCustomerAddressInformation($isPickup, $paymentMethod, $paymentData, $quote);

        if ($paymentMethod == "cc") {
            $nameOnCard = $paymentData->nameOnCard ?? '';
            $expirationYear = $paymentData->year ?? '';
            $expirationMonth = $paymentData->expire ?? '';
        }
        $poReferenceId = $paymentData->poReferenceId ?? null;
        $accNo = $paymentData->fedexAccountNumber;
        $shipperRegion = null;
        $stateCode = $addressData[self::STATE];
        if (isset($addressData[self::REGION_CODE]) && empty($addressData[self::STATE])) {
            $shipperRegion = $this->regionFactory->create()->load($addressData[self::REGION_CODE]);
        }

        $customerInfo = $this->billingAddressBuilder->getCustomerDetails($data);
        $numTotal = $this->submitOrderHelper->getOrderTotalFromRateQuoteResponse($rateQuoteResponse);
        $shippingAccountData = $data['rateQuoteRequest']['retailPrintOrder']['recipients'];
        $shippingAccount = null;
        $numDiscountPrice = null;
        $requestedAmount = null;

        if (!$isPickup) {
            $discountLinePrice = $this->submitOrderHelper->getDeliveryLinePrice($rateQuoteResponse);
            $numDiscountPrice = $discountLinePrice;
            $shippingAccount = isset($shippingAccountData[0]['shipmentDelivery']['fedExAccountNumber']) ? $shippingAccountData[0]['shipmentDelivery']['fedExAccountNumber'] : '' ;
            //B-1275188 Identifying old rate quote response
            $requestedAmount = $this->getRequestedAmounts(
                $shippingAccount,
                $rateQuoteResponse,
                $numTotal,
                $numDiscountPrice
            );
        }

        date_default_timezone_set(self::DEFAULT_TIMEZONE);
        $date = $this->timezoneInterface->date()->format('Y-m-d H:i:s');

        $transactionDataObject = $this->dataObjectFactory->create();
        $transactionDataObject->setDate($date);
        $transactionDataObject->setFjmpRateQuoteId($fjmpRateQuoteId);
        $transactionDataObject->setFname($customerInfo['fName']);
        $transactionDataObject->setLname($customerInfo['lName']);
        $transactionDataObject->setCompanyName($customerInfo['companyName']);
        $transactionDataObject->setEmail($customerInfo['email']);
        $transactionDataObject->setPhNumber($customerInfo['phNumber']);
        $transactionDataObject->setExtension($customerInfo['extension']);
        if ($this->toggleConfig->getToggleConfigValue('explorers_toas_mapping_redesign')) {
            $transactionDataObject->setOrderNumber($dataObjectForFujistu->getOrderNumber());
        }

        $dataa = $this->submitOrderDataArray->getTransactionOrderDetails($transactionDataObject);

        $paymentInfo = $this->getPaymentDetails(
            $paymentMethod,
            $useSiteCreditCard,
            $encCCData,
            $nameOnCard,
            $paymentData,
            $shippingAccount,
            $isPickup,
            $shipMethod,
            $companyId,
            $eproOrder
        );

        //set all variable in data object
        $dataObject = $this->dataObjectFactory->create();
        $dataObject->setNumDiscountPrice($numDiscountPrice);
        $dataObject->setShippingAccount($shippingAccount);
        $dataObject->setRequestedAmount($requestedAmount);
        $dataObject->setEncCCData($paymentInfo['encCCData']);
        $dataObject->setCcToken($paymentInfo[self::CC_TOKEN]);
        $dataObject->setNameOnCard($paymentInfo[self::NAME_ON_CARD]);
        $dataObject->setStreetAddress($addressData['streetAddress']);
        $dataObject->setCity($addressData['city']);
        $dataObject->setShipperRegion($shipperRegion);
        $dataObject->setStateCode($stateCode);
        $dataObject->setZipCode($addressData['zipcode']);
        $dataObject->setAddressClassification($addressClassification);
        $dataObject->setExpirationMonth($expirationMonth);
        $dataObject->setExpirationYear($expirationYear);
        $dataObject->setPoReferenceId($poReferenceId);
        $dataObject->setNumTotal($numTotal);
        $dataObject->setState($addressData[self::STATE]);
        $dataObject->setAccNo($accNo);
        $dataObject->setCondition($paymentInfo['condition']);
        $dataObject->setPaymentMethod($paymentMethod);
        $dataObject->setIsB2bApproval($dataObjectForFujistu->getIsB2bApproval());
        $quoteId = $quote->getId();
        $dataa['checkoutRequest']['tenders'] = $this->submitOrderDataArray
        ->getCheckoutRequestTenderData($dataObject, $quote);

        return $this->callTransactionAPI($dataa, $quoteId);
    }

    /**
     * Handle Transaction CXS request for in store.
     *
     * @param $fjmpRateQuoteId
     * @param $quoteId
     * @param $rateQuoteResponse
     * @param $dataObjectForFujistu
     * @return array
     * @throws GraphQlFujitsuResponseException
     */
    public function handleInstoreTransactionAPI($fjmpRateQuoteId, $quoteId, $rateQuoteResponse, $dataObjectForFujistu): array
    {
        $data = $this->inStoreRequestBuilder->build($fjmpRateQuoteId);

        if( ! empty($data) && $this->toggleConfig->getToggleConfigValue('explorers_toas_mapping_redesign')){
            $data['checkoutRequest']['transactionHeader']['orderReferences'] = [
                [
                    'name' => "FUSE",
                    'value' => $dataObjectForFujistu->getOrderNumber()
                ]
            ];
        }

        $transactionResponse = $this->callTransactionAPI($data, $quoteId);

        if ($transactionResponse[self::ERROR] && $this->instoreConfig->isCheckoutRetryImprovementEnabled()) {
            $transactionId = $rateQuoteResponse["transactionId"] ?? null;
            if (isset($transactionId)) {
                $this->saveQuoteIntegrationRetryData->execute($quoteId, $transactionId);
            }
        }

        if ($transactionResponse[self::ERROR] && $this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
            throw new GraphQlFujitsuResponseException(
                __("Transaction CXS API Response Error: " . $transactionResponse[self::MSG])
            );
        }

        return $transactionResponse;
    }

    /**
     * Get Customer Address Information
     *
     * @param bool $isPickup
     * @param string $paymentMethod
     * @param string $paymentData
     * @param object $quote
     * @return array
     */
    public function getCustomerAddressInformation($isPickup, $paymentMethod, $paymentData, $quote)
    {
        $state = $regionCode = $city = $zipcode = '';
        $streetAddress = null;
        $streetAddress2 = null;

        if ($isPickup && $paymentMethod == "cc") {
            $streetAddress = $paymentData->billingAddress->address;
            $streetAddress2 = $paymentData->billingAddress->addressTwo;
            $city = $paymentData->billingAddress->city;
            $state = $paymentData->billingAddress->state;
            $zipcode = $paymentData->billingAddress->zip;
        } elseif (!$isPickup && $paymentMethod == "fedex") {
            $shippingAddress = $quote->getShippingAddress();
            $streetAddress = $shippingAddress->getData('street');
            $city = $shippingAddress->getData('city');
            $regionCode = $shippingAddress->getData('region_id');
            $zipcode = $shippingAddress->getData('postcode');
        } elseif (!$isPickup && $paymentMethod == "cc") {
            $isBillingAddress = $paymentData->isBillingAddress;
            if ($isBillingAddress) {
                $streetAddress = $paymentData->billingAddress->address;
                $streetAddress2 = $paymentData->billingAddress->addressTwo;
                $city = $paymentData->billingAddress->city;
                $state = $paymentData->billingAddress->state;
                $zipcode = $paymentData->billingAddress->zip;
            } else {
                $shippingAddress = $quote->getShippingAddress();
                $streetAddress = $shippingAddress->getData('street');
                $city = $shippingAddress->getData('city');
                $regionCode = $shippingAddress->getData('region_id');
                $zipcode = $shippingAddress->getData('postcode');
            }
        }
        if (!empty($streetAddress)) {
            if ($streetAddress2 != null) {
                $streetAddress = [$streetAddress, $streetAddress2];
            } else {
                $streetAddress = explode(PHP_EOL, $streetAddress);
            }
        }

        return [
            'streetAddress' => $streetAddress,
            'streetAddress2' => $streetAddress2,
            'city' => $city,
            self::STATE => $state,
            self::REGION_CODE => $regionCode,
            'zipcode' => $zipcode
        ];
    }

    /**
     * Get Requested Amounts
     *
     * @param int|float|string $shippingAccount
     * @param array $rateQuoteResponse
     * @param int|float|string $numTotal
     * @param int|float|string $numDiscountPrice
     * @return int|float|string|null
     */
    public function getRequestedAmounts($shippingAccount, $rateQuoteResponse, $numTotal, $numDiscountPrice)
    {
        if ($shippingAccount != null
            && $shippingAccount != ''
            && count($rateQuoteResponse[self::OUTPUT]['rateQuote']['rateQuoteDetails']) == 1
        ) {
            $requestedAmount = $numTotal - $numDiscountPrice;
        } else {
            $requestedAmount = $numTotal;
        }

        return $requestedAmount;
    }

    /**
     * Get Payment Information
     *
     * @param string $paymentMethod
     * @param bool $useSiteCreditCard
     * @param string $encCCData
     * @param string $nameOnCard
     * @param string $paymentData
     * @param int|string $shippingAccount
     * @param bool $isPickup
     * @param int|bool $companyId
     * @param bool $eproOrder
     * @return array
     */
    public function getPaymentDetails(
        $paymentMethod,
        $useSiteCreditCard,
        $encCCData,
        $nameOnCard,
        $paymentData,
        $shippingAccount,
        $isPickup,
        $shipMethod,
        $companyId = false,
        $eproOrder = false
    )
    {
        $ccToken = null;
        if ($paymentMethod == "cc") {
            // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
            if (($this->sdeHelper->getIsSdeStore() || $this->accountHelper->getIsSelfRegStore()) && $useSiteCreditCard) {
                $siteCreditCardData = $this->companyHelper->getCompanyCreditCardData();
                $ccToken = $siteCreditCardData[self::TOKEN] ?? null;
                $encCCData = null;
            } elseif ($eproOrder && $companyId && $useSiteCreditCard) {
                $siteCreditCardData = $this->companyHelper->getCompanyCreditCardData($companyId);
                $ccToken = $siteCreditCardData[self::TOKEN] ?? null;
                $encCCData = null;
            }

            /** Retail payment via credit card token */
            $profileCreditCardId = '';
            if (isset($paymentData->profileCreditCardId)) {
                $profileCreditCardId = $paymentData->profileCreditCardId;
                $response = $this->enhancedProfile->updateCreditCard([], $profileCreditCardId, 'GET');
                $responseData = $this->billingAddressBuilder->getUpdatedCreditCardDetail(
                    $response,
                    $ccToken,
                    $nameOnCard
                );
                $ccToken = $responseData[self::CC_TOKEN];
                $nameOnCard = $responseData[self::NAME_ON_CARD];
            }
            //Fix for D-209388: Epro: Unable to approve the shipping and pickup flow orders
            if ((bool)$this->toggleConfig->getToggleConfigValue(self::LOCAL_DELIVERY_EPRO_ORDER_FIX_ENABLED)) {
                $condition = (($shippingAccount != null || $shippingAccount != "") && (!$isPickup) && (!str_contains($shipMethod, "fedexshipping_LOCAL_DELIVERY")));
            } else {
                $condition = (($shippingAccount != null || $shippingAccount != "") && (!$isPickup));
            }
        } elseif ($paymentMethod == "fedex") {
            if ((bool)$this->toggleConfig->getToggleConfigValue(self::LOCAL_DELIVERY_EPRO_ORDER_FIX_ENABLED)) {
                $condition = (($shippingAccount != null || $shippingAccount != "") && (!$isPickup) && (!str_contains($shipMethod, "fedexshipping_LOCAL_DELIVERY")));
            } else {
                $condition = (($shippingAccount != null || $shippingAccount != "") && (!$isPickup));
            }
        }

        return [
            self::CC_TOKEN => $ccToken,
            self::NAME_ON_CARD => $nameOnCard,
            'encCCData' => $encCCData,
            'condition' => $condition
        ];
    }

    /**
     * Get retail transaction id and product lines attributes details
     *
     * @param string|array|object $checkoutResponseData
     * @return array
     */
    public function getTransactionIdAndProductLinesAttributes($checkoutResponseData)
    {
        $retailTransactionId = null;
        $productLineDetailsAttributes = null;

        if (isset($checkoutResponseData->output)) {
            $retailTracId = $checkoutResponseData->output->checkout->transactionHeader;
            if (isset($retailTracId->retailTransactionId)) {
                $retailTransactionId = $retailTracId->retailTransactionId;
            }
            $productLineDetailsResponse =
            $checkoutResponseData->output->checkout->lineItems[0]->retailPrintOrderDetails[0];
            if (isset($productLineDetailsResponse->productLines)) {
                $productLineDetailsAttributes = json_encode($productLineDetailsResponse->productLines);
            }
        }

        return [
            'retailTransactionId' => $retailTransactionId,
            'productLineDetailsAttributes' => $productLineDetailsAttributes
        ];
    }

    /**
     * Get Transaction Response By Retail transaction id
     *
     * @param object $quote
     * @param int|string $retailTransactionId
     * @return array
     */
    public function getTransactionResponse($quote, $retailTransactionId)
    {
        $accessToken = $this->deliveryHelper->getApiToken();
        $getTransactionAPIURL = $this->configInterface->getValue(self::GET_TRANSACTION_API_URL);
        $setupURL = $getTransactionAPIURL.'/'.$retailTransactionId;
        $headers = $this->getHeaders($accessToken);

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => ''
            ]
        );

        $this->curl->get($setupURL);
        $result = $this->curl->getBody();
        $this->logger->info(__METHOD__ . ':' . __LINE__ .':' . ' Get Transaction API Response =>' . $result);
        $transactionResponseData = json_decode((string)$result, true);

        if (!empty($transactionResponseData)) {
            $transactionResponse = $this->inStoreRequestBuilder->prepareGetTransactionResponse(
                $quote,
                $transactionResponseData
            );

            $rateQuoteResponse = [
                self::OUTPUT => [
                    'rateQuote' => [
                        'rateQuoteDetails' => [
                            [
                                'estimatedVsActual' => $quote->getData('estimated_vs_actual')
                            ]
                        ]
                    ]
                ]
            ];

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .':'
                . ' Prepared transaction API response same as checkout response =>' . json_encode($transactionResponse)
            );

            return [
                self::ERROR => 0,
                self::MSG => 'Success',
                self::RESPONSE => $transactionResponse,
                'rateQuoteResponse' => $rateQuoteResponse
            ];
        } else {
            $this->submitOrderOptimizedHelper->unsetOrderInProgress();

            return [self::ERROR => 1, self::MSG => 'Error no data found', self::RESPONSE => ''];
        }
    }
}
