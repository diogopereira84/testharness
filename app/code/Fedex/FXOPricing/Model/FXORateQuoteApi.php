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
use Fedex\FXOPricing\Model\CatalogMvp\ProductPriceHandler;
use Fedex\Header\Helper\Data;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
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
use Magento\Checkout\Model\CartFactory;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class FXORateQuoteApi
{

    protected $currentQuoteId = null;

    protected $getExpiredItemIds = null;

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

    /**
     * FxoRateQuote Helper
     *
     * @param LoggerInterface $logger
     * @param Curl $curl
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
     * @param CartFactory $cartFactory
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param ConfigInterface $productBundleConfig
     */
    public function __construct(
        private LoggerInterface                    $logger,
        private Curl                               $curl,
        private CartDataHelper                     $cartDataHelper,
        private FXORateQuoteDataArray              $fxoRateQuoteDataArray,
        private DataObjectFactory                  $dataObjectFactory,
        private CheckoutSession                    $checkoutSession,
        private SubmitOrderModel                   $submitOrderModel,
        private UrlInterface                       $urlInterface,
        private ResponseFactory                    $responseFactory,
        private FXORequestBuilder                  $fXORequestBuilder,
        private FXOModel                           $fxoModel,
        private FXOProductDataModel                $fXOProductDataModel,
        private SdeHelper                          $sdeHelper,
        private ToggleConfig                       $toggleConfig,
        private RequestQueryValidator              $requestQueryValidator,
        private RequestInterface                   $request,
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private InStoreRecipientsBuilder           $inStoreRecipientsBuilder,
        private SubmitOrderHelper                  $submitOrderHelper,
        private ExpiredDataHelper                  $expiredDataHelper,
        private CustomerSession                    $customerSession,
        private InstoreConfig                      $instoreConfig,
        private QuoteHelper                        $quoteHelper,
        private SerializerInterface                $serializer,
        private StoreManagerInterface              $storeManager,
        private Product                            $product,
        private CatalogMvp                         $catalogMvpHelper,
        private Data                               $data,
        private UploadToQuoteViewModel             $uploadToQuoteViewModel,
        private ProductPriceHandler                $productPriceHandler,
        private CartFactory                        $cartFactory,
        protected MarketplaceCheckoutHelper        $marketplaceCheckoutHelper,
        private readonly LoggerHelper $loggerHelper,
        public NewRelicHeaders $newRelicHeaders,
        private readonly ConfigInterface $productBundleConfig
    ) {
    }

    /**
     * Get Rates
     *
     * @param Object  $quote
     * @param string  $mixedFlag (account|coupon|reorder)
     * @param boolean $validateContent
     * @param array   $uploadToQuoteRequest
     * @param array   $fuseRequest
     */
    public function getFXORateQuote(
        $quote,
        $mixedFlag = null,
        $validateContent = false,
        $uploadToQuoteRequest = [],
        $fuseRequest = null,
        $fuseBiddingUpdateDiscount = []
    ) {

        $quoteLocationId = null;
        $retailCustomerId = null;
        $promoCodeArray = [];

        $this->currentQuoteId = $quote->getId();
        $this->getExpiredItemIds = $this->customerSession->getExpiredItemIds();
        $isGraphQlRequest = $this->requestQueryValidator->isGraphQlRequest($this->request);
        $isNegotiableQuoteGraphQlRequest = $this->requestQueryValidator->isNegotiableQuoteGraphQlRequest($this->request, $isGraphQlRequest);

        if ($isGraphQlRequest && $this->currentQuoteId && !$isNegotiableQuoteGraphQlRequest) {
            try {
                $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($this->currentQuoteId);
                $quoteLocationId = $quoteIntegration->getLocationId();
                $retailCustomerId = $quoteIntegration->getRetailCustomerId();
            } catch (NoSuchEntityException $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . 'Can\'t retrieve integration with quote with ID #' .$this->currentQuoteId);
            }
        }

        try {
            //Get access gatway token
            $authenticationDetails = $this->fXORequestBuilder->getAuthenticationDetails(
                $quote,
                $this->cartDataHelper
            );

            $fedExAccountNumber = $authenticationDetails['fedexAccountNumber'] ?? null;
            $couponCode = $quote->getData("coupon_code") ?? '';
            if (strlen((string)$couponCode)) {
                $promoCodeArray['code'] = $couponCode;
            }

            if ($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
                $quoteObjectItemsCount = count($items);
            } else {
                $items = $quote->getAllVisibleItems();
                $quoteObjectItemsCount = count($items);
            }

            if (empty($quoteObjectItemsCount)) {
                return null;
            }

            $dbQuoteItemCount = $this->fxoModel->getDbItemsCount($quote);

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
                $arrRecipientsData = $this->isRequestFromGraphql($quote, $itemData, $quoteIntegration);
            } else {
                $arrRecipientsData = $this->get3pPickUpData($quote, $itemData);
            }

            if (!empty($itemData['rateApiProdRequestData'])) {
                if (!empty($arrRecipientsData['fedExAccountNumber'])) {
                    $fedExAccountNumber = $arrRecipientsData['fedExAccountNumber'];
                }

                if ($this->getExpiredItemIds) {
                    $productData = $this->expiredDataHelper
                        ->exludeExpiredProductFromRateQuoteRequest($itemData['rateApiProdRequestData']);
                } else {
                    $productData = $itemData['rateApiProdRequestData'];
                }

                // E-383162-Upload to Quote change request functionality
                if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable()
                    && isset($uploadToQuoteRequest['action']) && $uploadToQuoteRequest['action'] == 'changeRequested') {
                    $productData = $this->uploadToQuoteViewModel
                        ->updateItemsSI($itemData['rateApiProdRequestData'], $uploadToQuoteRequest);
                }

                // Upload to Quote fuse API- update product json in rate request
                if ($fuseRequest) {
                    if ($this->toggleConfig->getToggleConfigValue('mazegeek_u2q_quote_notes_save_fix')) {
                        if(isset($fuseRequest[0]['quote_items'])){
                            $productData = $this->uploadToQuoteViewModel
                                ->updateItemsForFuse($itemData['rateApiProdRequestData'], $fuseRequest);
                        }
                    }else{
                            $productData = $this->uploadToQuoteViewModel
                                ->updateItemsForFuse($itemData['rateApiProdRequestData'], $fuseRequest);
                    }
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

                if ($isGraphQlRequest || ($quote->getIsFromPickup() || $quote->getIsFromShipping()) && is_array($arrRecipientsData['arrRecipients'] ?? null)) {
                    $rateQuoteDataObject->setRecipients($arrRecipientsData['arrRecipients']);
                }
                $rateQuoteDataObject->setValidateContent(true);
                $this->customerSession->unsValidateContentApiExpired();
                if (!empty($this->getExpiredItemIds)) {
                    $this->customerSession->setValidateContentApiExpired(true);
                }

                $rateQuoteApiData = $this->fxoRateQuoteDataArray->getRateQuoteRequest($rateQuoteDataObject);
                if ($fuseBiddingUpdateDiscount) {
                    $rateQuoteApiData = $this->uploadToQuoteViewModel
                        ->updateRateRequestForFuseBiddingDiscount($rateQuoteApiData, $fuseBiddingUpdateDiscount);
                }
                $authHeaderVal = $this->data->getAuthHeaderValue();
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
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' System error, Please try again.');
            }
            return $productRates ?? true;
        } catch (GraphQlFujitsuResponseException $e) {
            if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                throw new GraphQlFujitsuResponseException(__($e->getMessage()));
            }
        } catch (Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' System error, Please try again. ' . $error->getMessage());
            return $this->exceptionResult();
        }
    }

    /**
     * @param $quote
     * @param $itemData
     * @param $quoteIntegration
     * @return array|null
     * @throws Exception
     * @codeCoverageIgnore
     */
    private function isRequestFromGraphql($quote, $itemData, $quoteIntegration)
    {
        $requestedPickupLocalTime = $quote->getData("requested_pickup_local_time");
        if (empty($requestedPickupLocalTime) && $quoteIntegration && $quoteIntegration->getPickupLocationDate()) {
            $expectedDate = new \DateTime($quoteIntegration->getPickupLocationDate());
            $requestedPickupLocalTime = $expectedDate->format("Y-m-d\TH:i:s");
        }

        return $this->inStoreRecipientsBuilder->build(
            $this->fXORequestBuilder->getShipmentId($quote),
            $this->currentQuoteId,
            $itemData['productAssociations'],
            $requestedPickupLocalTime
        );
    }
    /**
     * Exception Result
     *
     * @return array|string
     */
    private function exceptionResult()
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
     * @return false|mixed|string|true[]|null
     * @throws GraphQlFujitsuResponseException
     */
    private function callRateQuoteApi(
        $quote,
        $items,
        $itemData,
        $couponCode,
        $dataString,
        $headers,
        $mixedFlag,
        $uploadToQuoteRequest = []
    ) {

        // Set up API URL and cURL options
        $setupURL = $this->cartDataHelper->getRateQuoteApiUrl();
        $this->curl->setOptions([
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $dataString,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
        ]);

        // Determine the request origin for logging
        $originRequest = 'Frontend - ';
        if ($this->requestQueryValidator->isGraphQlRequest($this->request)) {
            $body = $this->serializer->unserialize($this->request->getContent());
            foreach ($this->mutationsList as $mutationName) {
                if (strpos($body['query'], $mutationName) !== false) {
                    $originRequest = 'GraphQl mutation: ' . $mutationName . ' - ';
                    break;
                }
            }
        }

        // Log API request start
        $headerArray = $this->newRelicHeaders->getHeaders();
        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Origin: ' . $originRequest . 'RateQuote API request start ' . __CLASS__, $headerArray);

        // Make the API call and decode the response
        $this->curl->post($setupURL, $dataString);
        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Origin: ' . $originRequest . 'RateQuote API request end ' . __CLASS__, $headerArray);
        $output = $this->curl->getBody();
        $rateApiOutputData = json_decode($output, true);

        // Early return for upload to quote delete action
        if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable()
            && isset($uploadToQuoteRequest['action']) && $uploadToQuoteRequest['action'] == 'deleteItem') {
            return $rateApiOutputData;
        }

        // Handle expired item IDs and error conditions
        $this->expiredDataHelper->unSetExpiredItemIds($rateApiOutputData);

        if (!empty($rateApiOutputData['errors']) || !isset($rateApiOutputData['output'])) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' RateQuote API request at FXORateQuote: ' . $dataString);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' RateQuote API response at FXORateQuote:' . $output);

            // Handle GraphQL exception if applicable
            if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests() &&
                empty($rateApiOutputData['output']) && empty($rateApiOutputData['errors'])) {
                throw new GraphQlFujitsuResponseException(__("Rate Quote Response Error: " . $output));
            }

            // Check for order existence errors
            if ($this->submitOrderModel->validateRateQuoteAPIErrors($rateApiOutputData['errors'] ?? [])) {
                $this->submitOrderHelper->saveRetailTransactionId(null);
                return $quote->getIsAjaxRequest() ? ['is_timeout' => true] : $this->redirectIfOrderExist($quote);
            }

            // Handle reorder logic
            if ($mixedFlag === 'reorder') {
                $errorMessage = $this->fxoModel->removeReorderQuoteItem($quote);
                return json_encode(["errors" => $errorMessage]);
            } else {
                $this->fxoModel->removeQuoteItem($quote);
                return $rateApiOutputData;
            }
        }

        // Process warnings and alerts
        if (!empty($rateApiOutputData['output']['alerts'])) {

            $this->logger->info(__METHOD__ . ' RateQuote API request: ' . $dataString);
            $this->logger->info(__METHOD__ . ' RateQuote API response: ' . $output);

            if ($this->submitOrderModel->validateRateQuoteAPIWarnings($rateApiOutputData['output']['alerts'])) {
                $transactionId = $rateApiOutputData['output']['trasactionDetails']['orderReferenceSearch']['orderReferences'][0]['txnDetails']['retailTransactionId'] ?? null;
                $this->submitOrderHelper->saveRetailTransactionId($transactionId);
                return $quote->getIsAjaxRequest() ? ['is_timeout' => true] : $this->redirectIfOrderExist($quote);
            }
            // Handle data updates
            $fjmpRateQuoteId = $this->cartDataHelper->getRateQuoteId($rateApiOutputData);
            $quote->setData('fjmp_quote_id', $fjmpRateQuoteId);
            $this->manageOutputData($quote, $items, $itemData, $rateApiOutputData, $couponCode);
            $this->fxoModel->saveDiscountBreakdown($quote, $rateApiOutputData);
            $this->fxoModel->isVolumeDiscountAppliedonItem($quote, $rateApiOutputData);
            return $rateApiOutputData;
        }

        // Handle discount logic and MVP shared catalog
        $this->fxoModel->checkErrorsAndRemoveDiscounts($quote, $rateApiOutputData, $this->cartDataHelper);
        $fjmpRateQuoteId = $this->cartDataHelper->getRateQuoteId($rateApiOutputData);
        $quote->setData('fjmp_quote_id', $fjmpRateQuoteId);
        $this->manageOutputData($quote, $items, $itemData, $rateApiOutputData, $couponCode);
        $this->fxoModel->saveDiscountBreakdown($quote, $rateApiOutputData);
        $this->fxoModel->isVolumeDiscountAppliedonItem($quote, $rateApiOutputData);

        // Process MVP product price if applicable
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable()) {
            $this->productPriceHandler->handle($quote, $rateApiOutputData);
        }

        // Update SKU details if Upload to Quote is enabled
        if ($this->uploadToQuoteViewModel->isUploadToQuoteEnable() &&
            (!isset($uploadToQuoteRequest['action']) || $uploadToQuoteRequest['action'] !== 'changeRequested')) {
            $this->uploadToQuoteViewModel->updateLineItemsSkuDetails($rateApiOutputData, $quote);
        }

        return $rateApiOutputData;
    }

    /**
     * redirectIfOrderExist
     */
    private function redirectIfOrderExist($isQuoteOrderAvailable)
    {
        if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_D178017_FIX) && $isQuoteOrderAvailable) {
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
    }

    /**
     * manageOutputData
     */
    private function manageOutputData($quote, $items, $itemData, $rateApiOutputdata, $couponCode)
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
    private function updateCartItems(
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

            $items = $quote->getAllVisibleItems();

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
    private function updateRateForAccountSdeDiscountFix($productRates, $quote)
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
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                if ($item->getMiraklOfferId()) {
                    continue;
                }
                $discountAmt = $productDiscountAmount[$item->getItemId()] ?? 0;
                $item->setDiscountAmount($discountAmt);
                $item->setBaseDiscountAmount($discountAmt);
                $item->setDiscount($discountAmt);
                $item->getProduct()->setIsSuperMode(true);
                $itemPrice = $customPrice[$item->getItemId()] ?? 0;
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
    private function updateQuoteInfo($quote, $productRates)
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
            $totalNetAmount = $this->cartDataHelper->formatPrice($rateDetail['totalAmount']);
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
    private function getGTNNumber($quote)
    {
        if (!empty($quote->getData('gtn'))) {
            $orderNumber = $quote->getData('gtn');
        } else {
            $orderNumber = $this->submitOrderModel->getGTNNumber();
            $quote->setData('gtn', $orderNumber);
        }

        return $orderNumber;
    }

    /**
     * Function for getDeliveryRatePrice
     * @param Array $rateDetail
     *
     * @return float
     */
    private function getDeliveryRatePrice($rateDetail)
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
    private function calculateDeliveryLinePrice($rateDetail)
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
    private function manageCartWarnings($rateApiOutputdata)
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
     * @codeCoverageIgnore
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
}
