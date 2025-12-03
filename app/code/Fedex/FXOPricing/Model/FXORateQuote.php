<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use DateTime;
use Exception;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\FXOPricing\Model\CatalogMvp\ProductPriceHandler;
use Fedex\Header\Helper\Data;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfigInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseFactory;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\App\RequestInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder as InStoreRecipientsBuilder;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\SubmitOrderSidebar\Model\SubmitOrder as SubmitOrderModel;
use Fedex\ExpiredItems\Helper\ExpiredData as ExpiredDataHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\CoreApi\Model\LogHelperApi;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;

class FXORateQuote
{
    private const RAQ_TIMEOUT_ERROR_MSG = "Fedex API is unavailable at the moment. Please try after sometime.";

    private static $cache = [];

    public const GETQUOTEDETAILS_GRAPHQLAPI = "getQuoteDetails";

    /**
     * @var array
     */
    private array $mutationsList = [
        "addProductsToCart",
        "updateCartItems",
        "updateGuestCartContactInformation",
        "createOrUpdateOrder",
        "placeOrder"
    ];

    public const EXPLORERS_D178017_FIX = 'explorers_D178017_fix';

    public const Mazegeek_D213295_FIX = 'mazegeek_D213295_quote_expired_details_issue_fix';

    public const QUOTE_STATUS_EXPIRED = 'expired';

    /**
     * FxoRateQuote Helper
     *
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param CartFactory $cartFactory
     * @param CartDataHelper $cartDataHelper
     * @param FXORateQuoteDataArray $fxoRateQuoteDataArray
     * @param DataObjectFactory $dataObjectFactory
     * @param CheckoutSession $checkoutSession
     * @param SubmitOrderModel $submitOrderModel
     * @param UrlInterface $urlInterface
     * @param ResponseFactory $responseFactory
     * @param FXORequestBuilder $fXORequestBuilder
     * @param FXOModel $fxoModel
     * @param FXOProductDataModel $fXOProductDataModel
     * @param SdeHelper $sdeHelper
     * @param ToggleConfig $toggleConfig
     * @param RequestQueryValidator $requestQueryValidator
     * @param RequestInterface $request
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param InStoreRecipientsBuilder $inStoreRecipientsBuilder
     * @param SubmitOrderHelper $submitOrderHelper
     * @param ExpiredDataHelper $expiredDataHelper
     * @param CustomerSession $customerSession
     * @param InstoreConfig $instoreConfig
     * @param QuoteHelper $quoteHelper
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     * @param Product $product
     * @param CatalogMvp $catalogMvpHelper
     * @param Data $data
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param ProductPriceHandler $productPriceHandler
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param FuseBidViewModel $fuseBidViewModel
     * @param LogHelperApi $loggerHelperApi
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param LoggerHelper $loggerHelper
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected Curl $curl,
        protected CartFactory $cartFactory,
        protected CartDataHelper $cartDataHelper,
        private FXORateQuoteDataArray $fxoRateQuoteDataArray,
        private DataObjectFactory $dataObjectFactory,
        protected CheckoutSession $checkoutSession,
        protected SubmitOrderModel $submitOrderModel,
        protected UrlInterface $urlInterface,
        protected ResponseFactory $responseFactory,
        protected FXORequestBuilder $fXORequestBuilder,
        private FXOModel $fxoModel,
        private FXOProductDataModel $fXOProductDataModel,
        protected SdeHelper $sdeHelper,
        protected ToggleConfig $toggleConfig,
        private RequestQueryValidator $requestQueryValidator,
        private RequestInterface $request,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private InStoreRecipientsBuilder $inStoreRecipientsBuilder,
        private SubmitOrderHelper $submitOrderHelper,
        protected ExpiredDataHelper $expiredDataHelper,
        protected CustomerSession $customerSession,
        private InstoreConfig $instoreConfig,
        protected QuoteHelper $quoteHelper,
        protected SerializerInterface $serializer,
        protected StoreManagerInterface $storeManager,
        protected Product $product,
        protected CatalogMvp $catalogMvpHelper,
        protected Data $data,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        private ProductPriceHandler $productPriceHandler,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        protected MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        protected FuseBidViewModel $fuseBidViewModel,
        private readonly LogHelperApi $loggerHelperApi,
        private NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        protected LoggerHelper $loggerHelper,
        private readonly ProductBundleConfigInterface $productBundleConfigInterface
    )
    {
    }

    /**
     * Get Rates
     *
     * @param Object $quote
     * @param string $mixedFlag (account|coupon|reorder)
     * @param boolean $validateContent
     * @param array $uploadToQuoteRequest
     * @param array $fuseRequest
     * @throws GraphQlFujitsuResponseException
     */
    public function getFXORateQuote(
        $quote,
        $mixedFlag = null,
        $validateContent = false,
        $uploadToQuoteRequest = [],
        $fuseRequest = null,
        $fuseBiddingUpdateDiscount = [],
        $isNotesNull = false
    ) {
        // Check if result is cached
        if ($this->addToCartPerformanceOptimizationToggle->isActive()) {
            $cacheKey = 'fxo_rate_quote_' . $quote->getId();
            if (isset(self::$cache[$cacheKey])) {
                return self::$cache[$cacheKey];
            }
        }
        $quoteLocationId = null;
        $retailCustomerId = null;
        $dataString = '';
        $isGraphQlRequest = $this->requestQueryValidator->isGraphQlRequest($this->request);
        $isNegotiableQuoteGraphQlRequest = $this->requestQueryValidator
            ->isNegotiableQuoteGraphQlRequest($this->request, $isGraphQlRequest);
        if ($isGraphQlRequest && $quote->getId() && (!$isNegotiableQuoteGraphQlRequest || $quote->getIsBid())) {
            try {
                $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
                $quoteLocationId = $quoteIntegration->getLocationId();
                $retailCustomerId = $quoteIntegration->getRetailCustomerId();
            } catch (NoSuchEntityException $e) {
                if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
                    $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'quote_id' => $quote->getId(), 'exception' => $e->getMessage()];
                    $this->loggerHelper->error('Can\'t retrieve integration for quote', $context, false);
                } else {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . 'Can\'t retrieve integration with quote with ID #' .$quote->getId());
                }
            }
        }

        if ($this->fuseBidViewModel->isSendRetailLocationIdEnabled()
            && !$isGraphQlRequest && $quote->getIsBid()) {
            try {
                $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quote->getId());
                $quoteLocationId = $quoteIntegration->getLocationId();
            } catch (NoSuchEntityException $e) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ .
                    'Error in Fetching Quote Integration: ' . $e->getMessage()
                );
            }
        }

        try {
            //Get access gatway token
            $authenticationDetails = $this->fXORequestBuilder->getAuthenticationDetails(
                $quote,
                $this->cartDataHelper
            );
            $fedExAccountNumber = $authenticationDetails['fedexAccountNumber'];
            $promoCodeArray = [];
            $couponCode = '';
            $couponCode = $quote->getData("coupon_code");

            if (strlen((string)$couponCode)) {
                $promoCodeArray['code'] = $couponCode;
            }

            if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
            } else {
                $items = $quote->getAllVisibleItems();
            }

            $dbQuoteItemCount = $this->fxoModel->getDbItemsCount($quote);

            $quoteObjectItemsCount = count($items);
            if (empty($quoteObjectItemsCount)) {
                return null;
            }

            $isEssendantToggleEnabled = $this->marketplaceCheckoutHelper->isEssendantToggleEnabled();

            if($isEssendantToggleEnabled){
                $quoteObjectItemsCount = count($quote->getAllItems());
            }

            // E-383162-Upload to Quote delete item functionality (Exclude deleted item)
            if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable()
                && isset($uploadToQuoteRequest['action']) && $uploadToQuoteRequest['action'] == 'deleteItem') {
                $items = $this->uploadToQuoteViewModel
                    ->excludeDeletedItem($items, $uploadToQuoteRequest['item_id']);
            }

            // Get iterated item data
            $itemData = $this->fXOProductDataModel->iterateItems(
                $this->cartDataHelper,
                $items,
                $quoteObjectItemsCount,
                $dbQuoteItemCount,
                $isGraphQlRequest
            );

            if ($isGraphQlRequest) {
                /**
                 * TODO: move $requestedPickupLocalTime to PickupData when InStoreRecipientsBuilder removed
                 * @see \Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\PickupData
                 * @see \Fedex\FXOPricing\Model\RateQuoteApi\InStoreRecipientsBuilder
                 */
                $requestedPickupLocalTime = $quote->getData("requested_pickup_local_time") ?? null;
                $requestedDeliveryLocalTime = $quote->getData("requested_delivery_local_time") ?? null;
                $shippingEstimatedDeliveryLocalTime = $quote->getData("shipping_estimated_delivery_local_time") ?? null;
                $holdUntilDate = $quote->getData("hold_until_date") ?? null;

                if ((empty($requestedPickupLocalTime))
                    && (!empty($quoteIntegration))
                    && (!empty($quoteIntegration->getPickupLocationDate()))) {
                    $expectedDate = new DateTime($quoteIntegration->getPickupLocationDate());
                    $requestedPickupLocalTime = $expectedDate->format("Y-m-d").
                        "T".
                        $expectedDate->format("H:i:s");
                }
                $arrRecipientsData = $this->inStoreRecipientsBuilder->build(
                    $this->fXORequestBuilder->getShipmentId($quote),
                    $quote->getId(),
                    $itemData['productAssociations'],
                    $requestedPickupLocalTime,
                    $requestedDeliveryLocalTime,
                    $shippingEstimatedDeliveryLocalTime,
                    $holdUntilDate
                );
            } else {
                $arrRecipientsData = $this->get3pPickUpData($quote, $itemData);
            }

            if (!empty($itemData['rateApiProdRequestData'])) {
                if (!empty($arrRecipientsData['fedExAccountNumber'])) {
                    $fedExAccountNumber = $arrRecipientsData['fedExAccountNumber'];
                }

                if ($this->customerSession->getExpiredItemIds()) {
                    $productData = $this->expiredDataHelper
                        ->exludeExpiredProductFromRateQuoteRequest($itemData['rateApiProdRequestData']);
                } else {
                    $productData = $itemData['rateApiProdRequestData'];
                }

                // E-383162-Upload to Quote change request functionality
                if (($this->uploadToQuoteViewModel->isUploadToQuoteEnable() ||
                        ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $quote->getIsBid()))
                    && isset($uploadToQuoteRequest['action']) && $uploadToQuoteRequest['action'] == 'changeRequested') {
                    $productData = $this->uploadToQuoteViewModel
                        ->updateItemsSI($itemData['rateApiProdRequestData'], $uploadToQuoteRequest);
                }

                // Upload to Quote fuse API- update product json in rate request
                $quoteAction = '';
                if ($fuseRequest) {
                    if ($this->toggleConfig->getToggleConfigValue('mazegeek_u2q_quote_notes_save_fix')) {
                        if(isset($fuseRequest[0]['quote_items'])){
                            $productData = $this->uploadToQuoteViewModel
                                ->updateItemsForFuse($itemData['rateApiProdRequestData'], $fuseRequest);
                        }
                    } else {
                        $productData = $this->uploadToQuoteViewModel
                            ->updateItemsForFuse($itemData['rateApiProdRequestData'], $fuseRequest);
                    }
                    $quoteAction = $fuseRequest[0]['quote_action'] ?? '';
                }
                //Get gtn number
                $orderNumber = $this->getGTNNumber($quote);
                $webhookUrl = $this->submitOrderModel->getWebHookUrl($orderNumber);
                $rateQuoteDataObject = $this->dataObjectFactory->create();
                $rateQuoteDataObject->setQuoteObject($quote);
                $rateQuoteDataObject->setFedExAccountNumber($fedExAccountNumber);
                $rateQuoteDataObject->setProductsData($productData);
                $rateQuoteDataObject->setOrderNumber($orderNumber);
                $rateQuoteDataObject->setWebhookUrl($webhookUrl);
                $rateQuoteDataObject->setPromoCodeArray($promoCodeArray);
                $rateQuoteDataObject->setSite($authenticationDetails['site']);
                $rateQuoteDataObject->setSiteName($authenticationDetails['siteName']);
                $rateQuoteDataObject->setIsGraphQlRequest($isGraphQlRequest);
                $rateQuoteDataObject->setQuoteLocationId($quoteLocationId);
                $rateQuoteDataObject->setOrderNotes($this->fXORequestBuilder->getOrderNotes());
                $rateQuoteDataObject->setRetailCustomerId($retailCustomerId);
                $rateQuoteDataObject->setLteIdentifier($quote->getLteIdentifier());

                if ($isGraphQlRequest || ($quote->getIsFromPickup() || $quote->getIsFromShipping()) &&
                    is_array($arrRecipientsData['arrRecipients'])) {
                    /* isset check added to check if $arrRecipientsData['arrRecipients']
                     is set or not, without this it will giving error in graphql API's
                     */
                    if (isset($arrRecipientsData['arrRecipients'])) {
                        $rateQuoteDataObject->setRecipients($arrRecipientsData['arrRecipients']);
                    }
                }
                $validateContent = true;

                if ($this->toggleConfig->getToggleConfigValue(self::Mazegeek_D213295_FIX)) {
                    $quoteData = $this->negotiableQuoteRepository->getById($quote->getId());
                    if ($quoteData->getData('status') === self::QUOTE_STATUS_EXPIRED) {
                        $validateContent = false;
                    }
                }
                $rateQuoteDataObject->setValidateContent($validateContent);
                $this->customerSession->unsValidateContentApiExpired();
                if (!empty($this->customerSession->getExpiredItemIds())) {
                    $this->customerSession->setValidateContentApiExpired(true);
                }

                $rateQuoteApiData = $this->fxoRateQuoteDataArray->getRateQuoteRequest($rateQuoteDataObject, $this->getInstoreConfigNotesCheck($isNotesNull));
                if ($fuseBiddingUpdateDiscount) {
                    $rateQuoteApiData = $this->uploadToQuoteViewModel
                        ->updateRateRequestForFuseBiddingDiscount($rateQuoteApiData, $fuseBiddingUpdateDiscount);
                }
                $authHeaderVal = $this->data->getAuthHeaderValue();
                if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $isGraphQlRequest
                    && $quote->getIsBid() && $quoteAction == 'sent_to_customer') {
                    $rateQuoteApiData['rateQuoteRequest']['action'] = 'SAVE_COMMIT';
                }
                $dataString = json_encode($rateQuoteApiData);

                $headers = [
                    "Content-Type: application/json",
                    "Accept: application/json",
                    "Accept-Language: json",
                    "Content-Length: " . strlen($dataString),
                    $authHeaderVal . $authenticationDetails['gateWayToken']
                ];

                if (isset($authenticationDetails['accessToken']) && $authenticationDetails['accessToken']) {
                    $headers[] = "Cookie: Bearer=" . $authenticationDetails['accessToken'];
                } else {
                    $headers = $this->submitOrderHelper->getCustomerOnBehalfOf($headers);
                }

                $productRates = $this->callRateQuoteApi(
                    $quote,
                    $items,
                    $itemData,
                    $couponCode,
                    $dataString,
                    $headers,
                    $mixedFlag,
                    $uploadToQuoteRequest
                );
                if (!empty($productRates['output']['alerts'])) {
                    foreach ($productRates['output']['alerts'] as $alert) {
                        if ($alert['code']=== "RATEREQUEST.FEDEXACCOUNTNUMBER.INVALID") {
                            $quote->setData('fedex_account_number', '');
                        }
                    }
                }
            } else {
                if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
                    $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'quote_id' => $quote->getId(), 'payload' => $dataString];
                    $this->loggerHelper->info('FXO RateQuote Request', $context, false);
                    $this->loggerHelper->critical('System error, Please try again', $context, false);
                } else {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . 'FXO RateQuote Request' .$dataString);
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' System error, Please try again.');
                }
            }

            if ($this->addToCartPerformanceOptimizationToggle->isActive()) {
                self::$cache[$cacheKey] = $productRates ?? true;
            }
            return $productRates ?? true;
        } catch (GraphQlFujitsuResponseException $e) {
            if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                throw new GraphQlFujitsuResponseException(__($e->getMessage()));
            }
        } catch (Exception $error) {
            if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
                $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'quote_id' => $quote->getId(), 'payload' => $dataString];
                $this->loggerHelper->info('FXO RateQuote Request', $context, false);
                $this->loggerHelper->critical('System error, Please try again', $context, false);
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'FXO RateQuote Request' .$dataString);
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    ' System error, Please try again. ' . $error->getMessage());
            }

            return $this->exceptionResult();
        }
    }

    /**
     * Exception Result
     *
     * @return array|string
     */
    public function exceptionResult()
    {
        return ["errors" => "System error, Please try again."];
    }

    /**
     * Call RateQuote API
     *
     * @param object $quote
     * @param object $items
     * @param $itemData
     * @param string $couponCode
     * @param string $dataString
     * @param $headers
     * @param string $mixedFlag
     * @param array $uploadToQuoteRequest
     * @return array|false|string|true[]|void|null
     * @throws GraphQlFujitsuResponseException
     */
    public function callRateQuoteApi(
        $quote,
        $items,
        $itemData,
        $couponCode,
        $dataString,
        $headers,
        $mixedFlag,
        $uploadToQuoteRequest = []
    ) {
        $setupURL = $this->cartDataHelper->getRateQuoteApiUrl();

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $dataString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => ''
            ]
        );

        $originRequest = 'Frontend - ';
        $isGraphQlRequest = $this->requestQueryValidator->isGraphQlRequest($this->request);
        if ($isGraphQlRequest) {
            $body = $this->serializer->unserialize($this->request->getContent());
            $queryMutation = null;
            foreach ($this->mutationsList as $mutationName) {
                $pos = strpos($body['query'], $mutationName);
                if ($pos !== false) {
                    $queryMutation = $mutationName;
                }
            }

            $originRequest = 'GraphQl mutation: ' . $queryMutation . ' - ';
        }
        if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
            $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'origin' => $originRequest];
            $this->loggerHelper->info('RateQuote API request start', $context, false);
            $context['payload'] = $dataString;
            $this->loggerHelper->info('RateQuote API request payload', $context, false);
        } else {
            $this->loggerHelperApi->info(__METHOD__ . ':' . __LINE__ . ' Origin: ' . $originRequest . 'RateQuote API request start ' . __CLASS__);
            $this->loggerHelperApi->info(
                __METHOD__ . ':' . __LINE__ . ' Origin: ' . $originRequest .
                'RateQuote API request payload: ' . $dataString
            );
        }

        try {
            $start = microtime(true);
            $this->curl->post($setupURL, $dataString);
            $output = $this->curl->getBody();
            $elapsed = round((microtime(true) - $start) * 1000, 2); // ms
            if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
                $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'origin' => $originRequest];
                $this->loggerHelper->info('RateQuote API request end', $context, false);
            } else {
                $this->loggerHelperApi->info(__METHOD__ . ':' . __LINE__ . ' Origin: ' . $originRequest . 'RateQuote API request end ' . __CLASS__);
            }
            if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
                $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'origin' => $originRequest, 'response' => $output];
                $this->loggerHelper->info('RateQuote API response', $context, false);
            } else {
                $this->loggerHelperApi->info(
                    __METHOD__ . ':' . __LINE__ . ' Origin: ' . $originRequest .
                    'RateQuote API response payload: ' . $output
                );
            }
        } catch (\Exception $exception) {
            if ($isGraphQlRequest &&
                $this->instoreConfig->isHandleRAQTimeoutErrorEnabled()) {
                throw new GraphQlFujitsuResponseException(
                    __(self::RAQ_TIMEOUT_ERROR_MSG)
                );
            }
            throw new GraphQlFujitsuResponseException(__($exception->getMessage()));
        }

        /**
         * @todo Convert response from JSON to Data Object
         *
         * @see \Fedex\FXOPricing\Api\Data\RateQuoteInterface
         * @see \Fedex\FXOPricing\Api\RateQuoteBuilderInterface
         */
        $rateResultdata = [];
        $rateApiOutputdata = json_decode($output, true);
        $rateApiOutputdata= $this->updatedEstimatedVsActualFor3p($rateApiOutputdata, $quote, $dataString);
        // Return output when temporary delete item from quote
        if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable()
            && isset($uploadToQuoteRequest['action']) && $uploadToQuoteRequest['action'] == 'deleteItem') {
            return $rateApiOutputdata;
        }
        // added condition to check quote, when calling the function from product save
        if ($quote) {
            $this->expiredDataHelper->unSetExpiredItemids($rateApiOutputdata);

            if (!empty($rateApiOutputdata['errors']) || !isset($rateApiOutputdata['output'])) {
                if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
                    $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'payload' => $dataString];
                    $this->loggerHelper->info('RateQuote API request at FXORateQuote.', $context, false);
                    $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'duration' => $elapsed . 'ms', 'response' => $output];
                    $this->loggerHelper->info('RateQuote API response at FXORateQuote', $context, false);
                } else {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' RateQuote API request at FXORateQuote:');
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' RateQuote API response at FXORateQuote:');
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }

                if (($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests())
                    && (!isset($rateApiOutputdata['output']))
                    && (!isset($rateApiOutputdata['errors']))) {
                    throw new GraphQlFujitsuResponseException(
                        __(
                            "Rate Quote Response Error: " . $output
                        )
                    );
                }

                $isOrderExitError = $this->submitOrderModel->validateRateQuoteAPIErrors(
                    $rateApiOutputdata['errors'] ?? []
                );

                // @codeCoverageIgnoreStart
                if ($isOrderExitError) {
                    $this->submitOrderHelper->saveRetailTransactionId(null);
                    if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D178017_FIX)) {
                        $isQuoteOrderAvailable = $this->submitOrderHelper->isQuoteOrderAvailable($quote);
                    }

                    if ($quote->getIsAjaxRequest()) {
                        return ['is_timeout' => true];
                    }

                    if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D178017_FIX)) {
                        if ($isQuoteOrderAvailable) {
                            $cartPageRedirectionUrl = $this->urlInterface->getUrl('checkout/cart/index');
                            $responseFactoryObject = $this->responseFactory->create();
                            $responseFactoryObject->setRedirect($cartPageRedirectionUrl);
                            $responseFactoryObject->sendResponse();
                        } else {
                            $orderSuccessPageRedirectionUrl = $this->urlInterface->getUrl('submitorder/index/ordersuccess');
                            $responseFactoryObject = $this->responseFactory->create();
                            $responseFactoryObject->setRedirect($orderSuccessPageRedirectionUrl);
                            $responseFactoryObject->sendResponse();
                        }
                    } else {
                        $orderSuccessPageRedirectionUrl = $this->urlInterface->getUrl('submitorder/index/ordersuccess');
                        $responseFactoryObject = $this->responseFactory->create();
                        $responseFactoryObject->setRedirect($orderSuccessPageRedirectionUrl);
                        $responseFactoryObject->sendResponse();
                    }
                    exit();
                }
                // @codeCoverageIgnoreEnd

                $quote = $this->cartFactory->create()->getQuote();
                if ($mixedFlag == 'reorder') {
                    // Remove item from quote and return item name
                    $errorMessage = $this->fxoModel->removeReorderQuoteItem($quote);

                    $rateResultdata =  json_encode(["errors" => $errorMessage]);
                } else {
                    $this->fxoModel->removeQuoteItem($quote);
                    $rateResultdata =  $rateApiOutputdata;
                }

                if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                    $errorMessage = implode(",", array_column($rateApiOutputdata['errors'], 'message'));
                    throw new GraphQlFujitsuResponseException(__("Rate Quote Response Error: " . $errorMessage));
                }

                $this->fxoModel->checkErrorsAndRemoveFedexAccount($quote, $rateApiOutputdata);

                return $rateResultdata;
            }

            if (!empty($rateApiOutputdata['output']) && !empty($rateApiOutputdata['output']['alerts'])) {
                if ($this->toggleConfig->getToggleConfigValue('tiger_team_B_2568623')) {
                    $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'payload' => $dataString];
                    $this->loggerHelper->info('RateQuote API request at FXORateQuote.', $context, false);
                    $context = ['class' => __CLASS__, 'method' => __METHOD__, 'line' => __LINE__, 'duration' => $elapsed . 'ms', 'response' => $output];
                    $this->loggerHelper->info('RateQuote API response at FXORateQuote', $context, false);
                } else {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' RateQuote API request at FXORateQuote:');
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' RateQuote API response at FXORateQuote:');
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $output);
                }

                // Start Transaction timeout code with warning response
                $isOrderExitWarning = false;
                $isOrderExitWarning =  $this->submitOrderModel->validateRateQuoteAPIWarnings(
                    $rateApiOutputdata['output']['alerts']
                );

                // @codeCoverageIgnoreStart
                if ($isOrderExitWarning) {
                    if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D178017_FIX)) {
                        $isQuoteOrderAvailable = $this->submitOrderHelper->isQuoteOrderAvailable($quote);
                    }

                    if ($this->instoreConfig->isFixPlaceOrderRetry() &&
                        str_starts_with($quote->getData('gtn') ?? '', PunchoutHelper::INSTORE_GTN_PREFIX)) {
                        return $rateApiOutputdata;
                    }
                    $tId = null;
                    if (isset($rateApiOutputdata['output']['trasactionDetails'])) {
                        $output = $rateApiOutputdata['output']['trasactionDetails'];
                        $tId = $output['orderReferenceSearch']['orderReferences']['0']['txnDetails']['retailTransactionId'];
                    }
                    $this->submitOrderHelper->saveRetailTransactionId($tId);

                    if ($quote->getIsAjaxRequest()) {
                        return ['is_timeout' => true];
                    }

                    if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D178017_FIX)) {
                        if ($isQuoteOrderAvailable) {
                            $cartPageRedirectionUrl = $this->urlInterface->getUrl('checkout/cart/index');
                            $responseFactoryObject = $this->responseFactory->create();
                            $responseFactoryObject->setRedirect($cartPageRedirectionUrl);
                            $responseFactoryObject->sendResponse();
                        } else {
                            $orderSuccessPageRedirectionUrl = $this->urlInterface->getUrl('submitorder/index/ordersuccess');
                            $responseFactoryObject = $this->responseFactory->create();
                            $responseFactoryObject->setRedirect($orderSuccessPageRedirectionUrl);
                            $responseFactoryObject->sendResponse();
                        }
                    } else {
                        $orderSuccessPageRedirectionUrl = $this->urlInterface->getUrl('submitorder/index/ordersuccess');
                        $responseFactoryObject = $this->responseFactory->create();
                        $responseFactoryObject->setRedirect($orderSuccessPageRedirectionUrl);
                        $responseFactoryObject->sendResponse();
                    }
                    exit();
                }
                // @codeCoverageIgnoreEnd

                $quote = $this->cartFactory->create()->getQuote();
                // Manage coupon code reset
                if ($quote->getIsFromShipping() || $quote->getIsFromPickup()) {
                    $fjmpRateQuoteId = $this->cartDataHelper->getRateQuoteId($rateApiOutputdata);
                    $quote->setData('fjmp_quote_id', $fjmpRateQuoteId);
                    $this->manageOutputData($quote, $items, $itemData, $rateApiOutputdata, $couponCode);
                    $this->fxoModel->saveDiscountBreakdown($quote, $rateApiOutputdata);
                    $this->fxoModel->isVolumeDiscountAppliedonItem($quote, $rateApiOutputdata);
                    return $rateApiOutputdata;
                }

                $couponCode = $this->fxoModel->resetCartDiscounts($quote, $rateApiOutputdata, $this->cartDataHelper);
            }

            $this->fxoModel->checkErrorsAndRemoveDiscounts($quote, $rateApiOutputdata, $this->cartDataHelper);

            $fjmpRateQuoteId = $this->cartDataHelper->getRateQuoteId($rateApiOutputdata);
            $quote->setData('fjmp_quote_id', $fjmpRateQuoteId);
            $quoteDetailsNotFound = false;
            if($isGraphQlRequest){
                $quoteDetailsNotFound = strpos($body['query'], self::GETQUOTEDETAILS_GRAPHQLAPI);
            }
            if ($quoteDetailsNotFound === false) {
                // Manage output data for quote
                $this->manageOutputData($quote, $items, $itemData, $rateApiOutputdata, $couponCode);
                $this->fxoModel->saveDiscountBreakdown($quote, $rateApiOutputdata);
                $this->fxoModel->isVolumeDiscountAppliedonItem($quote, $rateApiOutputdata);
            }
            $this->manageOutputData($quote, $items, $itemData, $rateApiOutputdata, $couponCode);

            $this->fxoModel->saveDiscountBreakdown($quote, $rateApiOutputdata);
            $this->fxoModel->isVolumeDiscountAppliedonItem($quote, $rateApiOutputdata);

            if (!empty($quote) && $this->catalogMvpHelper->isMvpSharedCatalogEnable()) {
                $this->productPriceHandler->handle($quote, $rateApiOutputdata);
            }

            // for adding productLinedetails to show sku detail in quote details page.
            if (($this->uploadToQuoteViewModel->isUploadToQuoteEnable() ||
                    ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $quote->getIsBid())) ||
                $this->toggleConfig->getToggleConfigValue('xmen_upload_to_quote')) {
                if (!isset($uploadToQuoteRequest['action']) ||
                    (isset($uploadToQuoteRequest['action']) && $uploadToQuoteRequest['action'] != 'changeRequested')) {
                    $this->uploadToQuoteViewModel->updateLineItemsSkuDetails($rateApiOutputdata, $quote);
                }
            }
        }

        return $rateApiOutputdata;
    }

    /**
     * manageOutputData
     */
    public function manageOutputData($quote, $items, $itemData, $rateApiOutputdata, $couponCode)
    {
        $itemsUpdatedData = $itemData['itemsUpdatedData'];
        $quoteObjectItemsCount = $itemData['quoteObjectItemsCount'];
        $dbQuoteItemCount = $itemData['dbQuoteItemCount'];

        if ($quote->getIsFromAccountScreen() && ($quote->getIsFromShipping() || $quote->getIsFromPickup())) {
            $this->updateRateForAccountSdeDiscountFix($rateApiOutputdata, $quote);
        } else {
            if ($quote->getIsFromShipping() || $quote->getIsFromPickup()) {
                $this->updateQuoteInfo($quote, $rateApiOutputdata);
            } else {
                $this->updateCartItems(
                    $items,
                    $rateApiOutputdata,
                    $itemsUpdatedData,
                    $quoteObjectItemsCount,
                    $dbQuoteItemCount
                );
                $isGraphQlRequest = $this->requestQueryValidator->isGraphQlRequest($this->request);
                $isNegotiableQuoteGraphQlRequest = $this->requestQueryValidator->isNegotiableQuoteGraphQlRequest($this->request, $isGraphQlRequest);
                $this->fxoModel->updateQuoteDiscount(
                    $quote,
                    $rateApiOutputdata,
                    $couponCode,
                    $this->cartDataHelper,
                    $isGraphQlRequest,
                    $isNegotiableQuoteGraphQlRequest
                );
            }
        }
    }

    /**
     * Add Items to Cart
     *
     * @param object $items
     * @param array $productRates
     * @param array $itemsUpdatedData
     * @param int $quoteObjectItemsCount
     * @param int $dbQuoteItemCount
     */
    public function updateCartItems(
        $items,
        $productRates,
        $itemsUpdatedData,
        $quoteObjectItemsCount,
        $dbQuoteItemCount
    ) {
        $productLines = $productRates['output']['rateQuote']['rateQuoteDetails'][0]['productLines'] ?? [];
        if (!empty($productLines)) {
            $count = 0;
            foreach ($items as $key => $item) {
                if ($item->getProductType() === Product\Type::TYPE_BUNDLE) {
                    continue;
                }

                foreach ($productLines as $productLine) {
                    if ($quoteObjectItemsCount == $dbQuoteItemCount) {
                        $key = $item->getItemId();
                    }
                    if ($key == $productLine['instanceId']) {
                        if (!$productLine['priceable']) {
                            $productLine['productRetailPrice'] = 0;
                            $productLine['productLinePrice'] = 0;
                            $productLine['productDiscountAmount'] = 0;
                        }
                        $price = $productLine['productRetailPrice'];
                        $price = $this->cartDataHelper->formatPrice($price);

                        $unitQty = $productLine['unitQuantity'];

                        if ($this->requestQueryValidator->isGraphQl()) {
                            $totalPrice = $productLine['productRetailPrice'];
                            $unitPrice = $productLine['productRetailPrice'] / $productLine['unitQuantity'];
                            $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                            $additionalData['productLinePrice'] = $productLine['productLinePrice'];
                            $additionalData['productRetailPrice'] = $productLine['productRetailPrice'];

                            $item->setAdditionalData(json_encode($additionalData));
                        } else {
                            // Item total mismatch
                            $unitPrice = $price / $unitQty;
                            $totalPrice = $unitPrice * $unitQty;
                        }

                        $item->setBaseRowTotal($totalPrice);
                        $item->setRowTotal($totalPrice);

                        $fedexDiscount = $productLine['productDiscountAmount'];
                        $fedexDiscount = $this->cartDataHelper->formatPrice($fedexDiscount);
                        $item->setDiscount($fedexDiscount);
                        $item->setCustomPrice($unitPrice);
                        $item->setOriginalCustomPrice($unitPrice);

                        if ($this->toggleConfig->getToggleConfigValue('techtitans_205366_subtotal_fix')) {
                            $item->setPrice($unitPrice);
                            $item->setBasePrice($unitPrice);
                            $item->setPriceInclTax($unitPrice);
                            $item->setBasePriceInclTax($unitPrice);
                            $item->setRowTotalInclTax($unitPrice * $unitQty);
                            $item->setBaseRowTotalInclTax($unitPrice * $unitQty);
                        }

                        $item->setIsSuperMode(true);
                        // Manage Additional Items
                        $this->fXOProductDataModel->manageAdditionalItem($item, $itemsUpdatedData, $count);

                        break;
                    }
                }
                $count++;
            }
        }
    }

    /**
     * Update shipping or pickup information along with fedex account
     *
     * @param ProductRates $productRates
     * @param Quote $quote
     */
    public function updateRateForAccount($productRates, $quote)
    {
        $productDiscountAmount = $customPrice = [];
        if (isset($productRates['output']['rateQuote']['rateQuoteDetails'][0]['productLines'])) {
            foreach ($productRates['output']['rateQuote']['rateQuoteDetails'][0]['productLines'] as $val) {
                $k = $val['instanceId'];
                $customPrice[$k] = $val['productRetailPrice'];
                $productDiscountAmount[$k] = $val['productDiscountAmount'];
            }

            if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
            } else {
                $items = $quote->getAllVisibleItems();
            }

            foreach ($items as $item) {
                if ($item->getMiraklOfferId()) {
                    continue;
                }
                $discountAmt = $productDiscountAmount[$item->getItemId()];
                $item->setDiscountAmount($discountAmt);
                $item->setBaseDiscountAmount($discountAmt);
                $item->setDiscount($discountAmt);
                $item->getProduct()->setIsSuperMode(true);
                $itemPrice = $customPrice[$item->getItemId()];
                $item->setRowTotal($itemPrice);
                $item->save();
            }
        }
        /* Updating the quote table */
        if (!empty($productRates['output']['rateQuote']['rateQuoteDetails'])) {
            $this->updateQuoteInfo($quote, $productRates);
        }
    }

    /**
     * Update shipping or pickup information along with fedex account
     * D-144516 - SDE Unable to see discount value in order history page and print page
     *
     * @param ProductRates $productRates
     * @param Quote $quote
     */
    public function updateRateForAccountSdeDiscountFix($productRates, $quote)
    {
        $productDiscountAmount = $customPrice = [];
        if (isset($productRates['output']['rateQuote']['rateQuoteDetails'])) {
            $rateQuoteDetails = $productRates['output']['rateQuote']['rateQuoteDetails'];
            foreach ($rateQuoteDetails as $val) {
                if (isset($val['productLines'])) {
                    foreach ($val['productLines'] as $lineItem) {
                        $k = $lineItem['instanceId'];
                        $customPrice[$k] = $lineItem['productRetailPrice'];
                        $productDiscountAmount[$k] = $lineItem['productDiscountAmount'];
                    }
                }
            }

            if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
            } else {
                $items = $quote->getAllVisibleItems();
            }

            foreach ($items as $item) {
                if ($item->getMiraklOfferId() || $item->getProductType() === Product\Type::TYPE_BUNDLE) {
                    continue;
                }
                $discountAmt = $productDiscountAmount[$item->getItemId()];
                $item->setDiscountAmount($discountAmt);
                $item->setBaseDiscountAmount($discountAmt);
                $item->setDiscount($discountAmt);
                $item->getProduct()->setIsSuperMode(true);
                $itemPrice = $customPrice[$item->getItemId()];
                $item->setRowTotal($itemPrice);
                $item->save();
            }
        }
        /* Updating the quote table */
        if (!empty($productRates['output']['rateQuote']['rateQuoteDetails'])) {
            $this->updateQuoteInfo($quote, $productRates);
        }
    }

    /**
     * Update Quote Information
     */
    public function updateQuoteInfo($quote, $productRates)
    {
        $productLinesTotal = $discountTotal = $totalNetAmount = $totalTaxAmount = $deliveryRetailPrice = 0;
        $responseRateDetails = $productRates['output']['rateQuote']['rateQuoteDetails'];
        foreach ($responseRateDetails as $rateDetail) {
            if (array_key_exists('deliveryLines', $rateDetail)) {
                $deliveryRetailPrice += $this->getDeliveryRatePrice($rateDetail);
            }
            if (array_key_exists('productLines', $rateDetail)) {
                foreach ($rateDetail['productLines'] as $productLine) {
                    $productLinesTotal += $this->cartDataHelper->formatPrice($productLine['productRetailPrice']);
                }
            }
            if (array_key_exists('discounts', $rateDetail)) {
                foreach ($rateDetail['discounts'] as $discount) {
                    $discountTotal += $this->cartDataHelper->formatPrice($discount['amount']);
                }
            }
            if (array_key_exists('taxAmount', $rateDetail)) {
                $totalTaxAmount += $this->cartDataHelper->formatPrice($rateDetail['taxAmount']);
            }
            if (
                $this->toggleConfig->getToggleConfigValue('tech_titans_d_216028') &&
                array_key_exists('totalAmount', $rateDetail)
            ) {
                $totalNetAmount += $this->cartDataHelper->formatPrice($rateDetail['totalAmount']);
            } else {
                $totalNetAmount = $this->cartDataHelper->formatPrice($rateDetail['totalAmount']);
            }
        }
        $this->checkoutSession->setShippingCost($deliveryRetailPrice);
        $quote->setShippingCost($deliveryRetailPrice);
        $quote->setDiscount($discountTotal);
        $quote->setSubTotal($productLinesTotal);
        $quote->setBaseSubTotal($productLinesTotal);
        $quote->setGrandTotal($totalNetAmount);
        $quote->setBaseGrandTotal($totalNetAmount);
        $quote->setCustomTaxAmount($totalTaxAmount);
        $quote->save();
    }

    /**
     * Get Gtn Number
     */
    public function getGTNNumber($quote)
    {
        if (!$quote->hasGtn()) {
            $quote->setData('gtn', $this->submitOrderModel->getGTNNumber());
        }
        return $quote->getData('gtn');
    }

    /**
     * Function for getDeliveryRatePrice
     * @param Array $rateDetail
     *
     * @return float
     */
    public function getDeliveryRatePrice($rateDetail)
    {
        $deliveryRetailPrice = 0;
        $deliveryRetailPrice += $this->calculateDeliveryLinePrice($rateDetail);

        return $deliveryRetailPrice;
    }

    /**
     * Calculate DeliveryPrice
     * @param Array $rateDetail
     *
     * @return float
     */
    public function calculateDeliveryLinePrice($rateDetail)
    {
        $deliveryRetailPrice = 0;
        if (isset($rateDetail['estimatedVsActual']) &&
            isset($rateDetail['deliveryLines'])
        ) {
            foreach ($rateDetail['deliveryLines'] as $deliveryData) {
                if (isset($deliveryData['deliveryLineType']) &&
                    $deliveryData['deliveryLineType'] == 'SHIPPING'
                ) {
                    if ($rateDetail['estimatedVsActual'] == 'ESTIMATED') {
                        $deliveryRetailPrice += $this->cartDataHelper->formatPrice(
                            $deliveryData['deliveryLinePrice']
                        );
                    } else {
                        $deliveryRetailPrice += $this->cartDataHelper->formatPrice(
                            $deliveryData['deliveryRetailPrice']
                        );
                    }
                }
            }
        }

        return $deliveryRetailPrice;
    }

    /**
     * Manage Cart Warnings
     */
    public function manageCartWarnings($rateApiOutputdata)
    {
        $ignoreWarnings = [
            'ADDRESS_INVALID_VALUE',
            'ADDRESS_SERVICE_FAILURE',
            'ADDRESS_INVALID_URL',
            'ADDRESS_INVALID_TOKEN',
            'ADDRESS_SERVICE_TIMEOUT',
            'MAX.PRODUCT.COUNT',
            'INVALID.PRODUCT.CODE',
            'RCXS.SERVICE.RATE.5',
            'RCXS.SERVICE.RATE.46',
            'RCXS.SERVICE.RATE.108'
        ];
        if (!empty($rateApiOutputdata['output']['alerts']) &&
            !empty($rateApiOutputdata['output']['alerts'][0]['code'])
        ) {
            foreach ($rateApiOutputdata['output']['alerts'] as $key => $alertDetail) {
                if (in_array($alertDetail['code'], $ignoreWarnings)) {
                    unset($rateApiOutputdata['output']['alerts'][$key]);
                }
            }
            $rateApiOutputdata['output']['alerts'] = array_values($rateApiOutputdata['output']['alerts']);
        }

        return $rateApiOutputdata;
    }

    /**
     * @param $quote
     * @param $itemData
     * @return array
     */
    private function get3pPickUpData($quote, $itemData)
    {
        return $this->fXORequestBuilder->getPickShipDataUpdated($quote, $itemData);
    }

    /**
     * @param $rateApiOutputdata
     * @param $quote
     * @param $fedexAccountNumber
     * @return array|void
     */
    private function updatedEstimatedVsActualFor3p($rateApiOutputdata, $quote, $dataString)
    {
        $ratequoteRequestData = json_decode($dataString);
        $fedExAccountNumber = $ratequoteRequestData->rateQuoteRequest->retailPrintOrder->fedExAccountNumber??'';

        if ($this->toggleConfig->getToggleConfigValue('vendor_shipping_account_number')) {
            if ($this->quoteHelper->isFullMiraklQuote($quote) && $fedExAccountNumber != '') {
                $rateApiOutputdata['output']['rateQuote']['rateQuoteDetails'][0]['estimatedVsActual'] = 'ESTIMATED';
            }
        }
        return $rateApiOutputdata;
    }

    /**
     * @param $isNotesNull
     * @return bool
     */
    public function getInstoreConfigNotesCheck($isNotesNull): bool
    {
        return !$isNotesNull && $this->instoreConfig->isEnabledAddNotes();
    }
}
