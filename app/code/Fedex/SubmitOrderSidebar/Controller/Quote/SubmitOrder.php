<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SubmitOrderSidebar\Controller\Quote;

use Exception;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\ComputerRental\Model\CRdataModel;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Delivery\Model\ConfigProvider;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\ReorderInstance\Helper\ReorderInstanceHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\SubmitOrderSidebar\Model\BillingAddressBuilder;
use Fedex\SubmitOrderSidebar\Model\TransactionApi\InStoreRequestBuilder;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\DataSourceComposite;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper as GraphqlApiHelper;

/**
 * SubmitOrder Controller
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SubmitOrder extends Action
{

    public $requestQueryValidator;
    /**
     * Unified Data Layer key
     */
    private const UNIFIED_DATA_LAYER = 'unified_data_layer';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var PunchoutHelper
     */
    protected $gateTokenHelper;

    /**
     * SubmitOrder constructor
     *
     * @param Context $context
     * @param CartRepositoryInterface $quoteRepository
     * @param ScopeConfigInterface $configInterface
     * @param CartFactory $cartFactory
     * @param Session $checkoutSession
     * @param DeliveryHelper $helper
     * @param PunchoutHelper $punchoutHelper
     * @param SubmitOrderHelper $submitOrderHelper
     * @param LoggerInterface $logger
     * @param RegionFactory $regionFactory
     * @param Curl $curl
     * @param JsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     * @param BillingAddressBuilder $billingAddressBuilder
     * @param ToggleConfig $toggleConfig
     * @param QuoteFactory $quote
     * @param Cart $cart
     * @param ReorderInstanceHelper $reorderInstanceHelper
     * @param OptimizeItemInstanceHelper $optimizeItemInstanceHelper
     * @param CustomerSession $customerSession
     * @param CompanyHelper $companyHelper
     * @param SdeHelper $sdeHelper
     * @param EnhancedProfile $enhancedProfile
     * @param SelfReg $selfregHelper
     * @param InStoreRequestBuilder $inStoreRequestBuilder
     * @param DataSourceComposite $dataSourceComposite
     * @param HeaderData $headerData
     * @param AuthHelper $authHelper
     * @param GraphqlApiHelper $graphqlApiHelper
     */
    public function __construct(
        Context $context,
        protected CartRepositoryInterface $quoteRepository,
        protected ScopeConfigInterface $configInterface,
        protected CartFactory $cartFactory,
        protected Session $checkoutSession,
        protected DeliveryHelper $helper,
        private PunchoutHelper $punchoutHelper,
        private SubmitOrderHelper $submitOrderHelper,
        protected LoggerInterface $logger,
        protected RegionFactory $regionFactory,
        protected Curl $curl,
        protected JsonFactory $resultJsonFactory,
        private StoreManagerInterface $storeManager,
        protected BillingAddressBuilder $billingAddressBuilder,
        protected ToggleConfig $toggleConfig,
        protected QuoteFactory $quote,
        protected Cart $cart,
        protected ReorderInstanceHelper $reorderInstanceHelper,
        protected OptimizeItemInstanceHelper $optimizeItemInstanceHelper,
        protected CustomerSession $customerSession,
        protected CompanyHelper $companyHelper,
        protected SdeHelper $sdeHelper,
        protected EnhancedProfile $enhancedProfile,
        protected SelfReg $selfregHelper,
        protected InStoreRequestBuilder $inStoreRequestBuilder,
        private DataSourceComposite $dataSourceComposite,
        protected HeaderData $headerData,
        protected AuthHelper $authHelper,
        protected GraphqlApiHelper $graphqlApiHelper,
        private CRdataModel $crData
    ) {
        parent::__construct($context);
    }

    /**
     * Function to create Fujitsu Rate Quote request for delivery flow.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $quote = $this->cartFactory->create()->getQuote();

        $shippingAddress = $quote->getShippingAddress();
        $company = $shippingAddress->getData('company');
        $addressClassification = "HOME";

        if ($company != null && $company != "") {
            $addressClassification = "BUSINESS";
        }

        $quoteId = $quote->getId();
        $this->logger->info(__METHOD__.':'.__LINE__.': Starting Submit Order Delivery with Quote ID: ' . $quoteId);

        $requestData = $this->getRequest()->getPost('data');
        $this->logger->info(__METHOD__.':'.__LINE__.': Passing via payment fetch Quote ID: ' . $quoteId);
        $requestData = json_decode((string)$requestData);
        $paymentData = $requestData->paymentData;
        $paymentData = json_decode((string)$paymentData);
        $encCCData = $requestData->encCCData;
        // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
        $useSiteCreditCard = false;
        if (isset($requestData->useSiteCreditCard) && $requestData->useSiteCreditCard == 'true') {
            $useSiteCreditCard = true;
            //B-1326759: Set site configured payment is used in quote
            $quote->setSiteConfiguredPaymentUsed(1);
        }

        $orderNumber = null;
        $orderNumber = $this->punchoutHelper->getGTNNumber();
        if (empty($orderNumber)) {
            $this->logger->error(__METHOD__.':'.__LINE__.': Error while generating GTN.'. ' quote Id:' . $quoteId);

            return $resultJson->setData([['error' => 1]]);
        }

        if (property_exists($paymentData, "billingAddress") && is_a($paymentData, 'stdClass')) {
            $quote->setBillingAddress($this->billingAddressBuilder->build($paymentData, $quote));
            $quote->save();
        }

        $isPickup = false;
        $shipmentId = (string) random_int(1000, 9999);
        $estimatePickupTime = '';
        $customerOrderInfo = $this->getCustomerShippingAddressInfo($quote, $shippingAddress, $paymentData);
        $isAlternate = $this->checkoutSession->getAlternateContact() ?? null;
        $recipientExt = null;
        if ($isAlternate) {
            $recipientExt = $shippingAddress->getData('ext_no') ?? $customerOrderInfo['extension'];
        }
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $webhookUrl = "{$baseUrl}rest/V1/fedexoffice/orders/{$orderNumber}/status";

        $items = $quote->getAllItems();
        $id = 0;
        $product = $productAssociations = [];
        foreach ($items as $item) {
            $additionalOption = $item->getOptionByCode('info_buyRequest');
            $additionalOptions = $additionalOption->getValue();
            $productJson = $this->submitOrderHelper->getProductJsonData($additionalOptions);
            $productJson['instanceId'] = $id;
            $productJson['qty'] = $item->getQty();
            $product[] = $productJson;
            $productAssociations[] = ['id' => $productJson['instanceId'], 'quantity' => $item->getQty()];
            $id++;
        }

        $shipperRegion = null;
        if (isset($customerOrderInfo['regionCode'])) {
            $shipperRegion = $this->regionFactory->create()->load($customerOrderInfo['regionCode']);
        }

        $userReferences = [];
        $getUuid = $this->submitOrderHelper->getUuid();

        if (!empty($getUuid)) {
            $userReferences = [
                [
                    'reference' => $getUuid,
                    'source' => 'FCL',
                ],
            ];
        } else {
            $userReferences = null;
        }

        $siteName = null;
        $site = null;
        $siteInfo = $this->getCompanySiteInfo($site, $siteName);

        $data = $this->getRateQuoteRequestData(
            $orderNumber,
            $shipmentId,
            $customerOrderInfo,
            $siteInfo,
            $userReferences,
            $shipperRegion,
            $isAlternate,
            $quote,
            $webhookUrl,
            $product,
            $recipientExt,
            $addressClassification,
            $productAssociations
        );

        $shipmentSpecialServices = $this->helper->getRateRequestShipmentSpecialServices();
        if (!empty($shipmentSpecialServices)) {
            $data['rateQuoteRequest']['retailPrintOrder']['recipients'][0]['shipmentDelivery']['specialServices']
            = $shipmentSpecialServices;
        }
        $response = null;
        try {
            $response = $this->callFujitsuRateQuoteApi(
                $quote,
                $paymentData,
                $encCCData,
                $isPickup,
                $shipmentId,
                $estimatePickupTime,
                $useSiteCreditCard,
                $data,
                $quoteId,
                $orderNumber
            );
        } catch (Exception $exception) {
            $this->checkoutSession->unsOrderInProgress();
            $this->logger->error(
                __METHOD__.':'.__LINE__. ' Problem when calling Fujitsu Quote ID:'
                . $quoteId . ' Shipment Id => ' . $shipmentId . ' GTN-Number => ' . $orderNumber
                . ' Exception => ' . $exception->getMessage()
            );
        }

        if (is_array($response)) {
            return $resultJson->setData([
                $response,
                self::UNIFIED_DATA_LAYER => $this->dataSourceComposite->compose($response),
            ]);
        }

        return $resultJson->setData([$response]);
    }

    /**
     * Get Customer Shipping Address Information
     *
     * @param object $quote
     * @param object $shippingAddress
     * @param string $paymentData
     * @return array
     */
    public function getCustomerShippingAddressInfo($quote, $shippingAddress, $paymentData)
    {
        $streetAddress = (array) $shippingAddress->getData('street');
        if (isset($streetAddress[0])) {
            $streetAddress = explode(PHP_EOL, $streetAddress[0]);
        }
        $city = $shippingAddress->getData('city');
        $regionCode = $shippingAddress->getData('region_id');
        $zipcode = $shippingAddress->getData('postcode');
        $shipMethod = $shippingAddress->getData('shipping_method');
        $array = explode('_', $shipMethod, 2);
        $shipMethod = "";
        if (!empty($array[1])) {
            $shipMethod = $array[1];
        }

        $couponCode = $quote->getData("coupon_code");
        $promoCodeArray = [];
        if (strlen((string)$couponCode)) {
            $promoCodeArray['code'] = $couponCode;
        }

        $poReferenceId = $paymentData->poReferenceId ?? null;

        $fedExAccountNumber = !empty($paymentData->fedexAccountNumber) ? $paymentData->fedexAccountNumber : null;
        $fedexShipAccountNumber = $quote->getData("fedex_ship_account_number");
        $fName = $shippingAddress->getData('firstname');
        $lName = $shippingAddress->getData('lastname');
        $email = $shippingAddress->getData('email');
        $telephone = $shippingAddress->getData('telephone');
        $extension = $quote->getData('ext_no');

        return [
            'streetAddress' => $streetAddress,
            'city' => $city,
            'regionCode' => $regionCode,
            'zipcode' => $zipcode,
            'shipMethod' => $shipMethod,
            'couponCode' => $couponCode,
            'promoCodeArray' => $promoCodeArray,
            'poReferenceId' => $poReferenceId,
            'fedExAccountNumber' => $fedExAccountNumber,
            'fedexShipAccountNumber' => $fedexShipAccountNumber,
            'fName' => $fName,
            'lName' => $lName,
            'email' => $email,
            'telephone' => $telephone,
            'extension' => $extension
        ];
    }

    /**
     * Get Company Site Info
     *
     * @param string|null $site
     * @param string|null $siteName
     * @return array
     */
    public function getCompanySiteInfo($site, $siteName)
    {
        // B-1378749 - SDE Add SiteName in Rate API
        if ($this->sdeHelper->getIsSdeStore() ||
        $this->selfregHelper->isSelfRegCustomer()
        ) {
            $siteName = $this->companyHelper->getCustomerCompany()->getCompanyName() ?? null;
            $site = null;
        }

        return ['site' => $site, 'siteName' => $siteName];
    }

    /**
     * Get Rate Quote Request Data Handler
     *
     * @param string|null $orderNumber
     * @param int|string $shipmentId
     * @param array $customerOrderInfo
     * @param array $siteInfo
     * @param array|null $userReferences
     * @param object|null $shipperRegion
     * @param bool $isAlternate
     * @param object $quote
     * @param string $webhookUrl
     * @param array $product
     * @param int|null $recipientExt
     * @param string $addressClassification
     * @param array $productAssociations
     * @return array
     */
    public function getRateQuoteRequestData(
        $orderNumber,
        $shipmentId,
        $customerOrderInfo,
        $siteInfo,
        $userReferences,
        $shipperRegion,
        $isAlternate,
        $quote,
        $webhookUrl,
        $product,
        $recipientExt,
        $addressClassification,
        $productAssociations
    ) {
        $fedexLocationId = $this->getFedexLocationId();
        // Fix for D-223412 discount intent not being passed for bid quotes
        $discountIntent="";
        $isBidQuote = $this->toggleConfig->getToggleConfigValue('is_bid_quote');
        if ($isBidQuote && $quote->isBid()){
            $discountIntent =  $this->graphqlApiHelper->getDiscountIntentForQuote($quote);
        }
        return [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => null,
                'previousQuoteId' => null,
                'action' => 'SAVE_COMMIT',
                'retailPrintOrder' => [
                    'fedExAccountNumber' => $customerOrderInfo['fedExAccountNumber'],
                    'origin' => [
                        'orderNumber' => $orderNumber,
                        'orderClient' => 'MAGENTO',
                        'site' => $siteInfo['site'],
                        'siteName' => $siteInfo['siteName'],
                        'userReferences' => $userReferences,
                        'fedExLocationId'=> $fedexLocationId
                    ],
                    'orderContact' => [
                        'contact' => [
                            'contactId' => null,
                            'personName' => [
                                'firstName' => ($isAlternate) ?
                                    $quote->getData('customer_firstname') : $customerOrderInfo['fName'],
                                'lastName' => ($isAlternate) ?
                                    $quote->getData('customer_lastname') : $customerOrderInfo['lName'],
                            ],
                            'company' => [
                                'name' => 'FXO',
                            ],
                            'emailDetail' => [
                                'emailAddress' => ($isAlternate) ?
                                    $quote->getData('customer_email') : $customerOrderInfo['email'],
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => ($isAlternate) ?
                                            trim((string)$quote->getData('customer_telephone')) :
                                            trim((string)$customerOrderInfo['telephone']),
                                        'extension' => $customerOrderInfo['extension'],
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => $webhookUrl,
                            'auth' => null,
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => $product,
                    'recipients' => [
                        0 => [
                            'reference' => $shipmentId,
                            'contact' => [
                                'contactId' => null,
                                'personName' => [
                                    'firstName' => $customerOrderInfo['fName'],
                                    'lastName' => $customerOrderInfo['lName'],
                                ],
                                'company' => [
                                    'name' => 'FXO',
                                ],
                                'emailDetail' => [
                                    'emailAddress' => $customerOrderInfo['email'],
                                ],
                                'phoneNumberDetails' => [
                                    0 => [
                                        'phoneNumber' => [
                                            'number' => trim((string)$customerOrderInfo['telephone']),
                                            'extension' => $recipientExt,
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => $customerOrderInfo['streetAddress'],
                                    'city' => $customerOrderInfo['city'],
                                    'stateOrProvinceCode' => isset($shipperRegion) ? $shipperRegion->getCode() : null,
                                    'postalCode' => $customerOrderInfo['zipcode'],
                                    'countryCode' => 'US',
                                    'addressClassification' => $addressClassification,
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => $customerOrderInfo['shipMethod'],
                                'fedExAccountNumber' => $customerOrderInfo['fedexShipAccountNumber'],
                                'deliveryInstructions' => null,
                                'poNumber' => $customerOrderInfo['poReferenceId'],
                            ],
                            'productAssociations' => $productAssociations,
                        ],
                    ],
                    'discountIntentResource' => $discountIntent,                    
                ],
                'coupons' =>
                !empty($customerOrderInfo['promoCodeArray']['code']) ? [$customerOrderInfo['promoCodeArray']] : null,
                'teamMemberId' => null,
            ],
        ];
    }

    /**
     * Call Fujitsu Rate Quote API
     *
     * @param object $quote
     * @param string $paymentData
     * @param string $encCCData
     * @param bool $isPickup
     * @param int|string $shipmentId
     * @param string $estimatePickupTime
     * @param bool $useSiteCreditCard
     * @param array $data
     * @param int $quoteId
     * @param int|string $orderNumber
     * @return array
     */
    public function callFujitsuRateQuoteApi(
        $quote,
        $paymentData,
        $encCCData,
        $isPickup,
        $shipmentId,
        $estimatePickupTime,
        $useSiteCreditCard,
        $data = [],
        $quoteId = '',
        $orderNumber = ''
    ) {
        /* Code to for duplicate order number */
        $duplicateOrder = $this->submitOrderHelper->isDuplicateOrder($quoteId);
        if ($duplicateOrder) {
            return ['error' => 2, 'msg' => 'Duplicate Order Number', 'response' => ''];
        }
        /* End */

        $dataString = json_encode($data);

        $output = $this->getResponseFromFujitsuAPI($dataString, $orderNumber, $quoteId);
        $rateQuoteResponse = json_decode((string)$output, true);

        if (!empty($rateQuoteResponse)) {

            return $this->getCheckoutResponseData(
                $rateQuoteResponse,
                $quoteId,
                $orderNumber,
                $shipmentId,
                $estimatePickupTime,
                $paymentData,
                $data,
                $encCCData,
                $isPickup,
                $useSiteCreditCard
            );
        } else {
            $this->checkoutSession->unsOrderInProgress();
            $this->logger->error(__METHOD__.':'.__LINE__. ' Fujitsu rate quote API Failed.');

            return ['error' => 1, 'msg' => 'Failure', 'response' => ''];
        }
    }

    /**
     * Call Fujitsu Rate Quote API
     *
     * @param string $dataString
     * @param int|string $orderNumber
     * @param int $quoteId
     * @return string
     */
    public function getResponseFromFujitsuAPI($dataString, $orderNumber, $quoteId)
    {
        $isOptimize = $this->toggleConfig->getToggleConfigValue('is_optimize_configuration');
        $setupURL = $this->configInterface->getValue("fedex/rateQuote/rate_post_api_url");
        if ($isOptimize) {
            $setupURL = $this->configInterface->getValue("fedex/general/rate_post_api_url");
        }

        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        if (!$this->helper->isCommercialCustomer()) {
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        }
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            $authHeaderVal . $gateWayToken,
        ];

        if ($this->customerSession->getOnBehalfOf()) {
            array_push($headers, "X-On-Behalf-Of: " . $this->customerSession->getOnBehalfOf());
        }

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $dataString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );

        $this->curl->post($setupURL, $dataString);
        $this->logger->info(
            __METHOD__.':'.__LINE__. ' Before Fujitsu API call Quote ID:'
            . $quoteId . ' GTN Num => ' . $orderNumber . ' $dataString => ' . $dataString
        );
        $output = $this->curl->getBody();
        $this->logger->info(
            __METHOD__.':'.__LINE__. ' After Fujitsu API call Quote ID:' . $quoteId
            . ' Gtn Number => ' . $orderNumber . ' $output => ' . $output
        );

        return $output;
    }

    /**
     * Method to create Transaction CXS request.
     *
     * @param object $quote
     * @param array $data
     * @param string $paymentData
     * @param string $fjmpRateQuoteId
     * @param string $encCCData
     * @param array $rateQuoteResponse
     * @param bool $isPickup
     * @param bool $useSiteCreditCard
     * @return array
     */
    public function constructTransactionAPI(
        $quote,
        $data,
        $paymentData,
        $fjmpRateQuoteId,
        $encCCData,
        $rateQuoteResponse,
        $isPickup,
        $useSiteCreditCard
    ) {
        $shippingAddress = $quote->getShippingAddress();
        $company = $shippingAddress->getData('company');
        $addressClassification = "HOME";
        if ($company != null && $company != "") {
            $addressClassification = "BUSINESS";
        }
        $quoteId = $quote->getId();
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
        $stateCode = $addressData['state'];
        if (isset($addressData['regionCode']) && empty($addressData['state'])) {
            $shipperRegion = $this->regionFactory->create()->load($addressData['regionCode']);
        }

        $customerInfo = $this->billingAddressBuilder->getCustomerDetails($data);
        $totalAmt = $this->submitOrderHelper->getOrderTotalFromRateQuoteResponse($rateQuoteResponse);
        $shippingAccountData = $data['rateQuoteRequest']['retailPrintOrder']['recipients'];
        $numTotal = $totalAmt;
        $shippingAccount = null;
        $numDiscountPrice = null;
        $requestedAmount = null;

        if (!$isPickup) {
            $discountLinePrice = $this->submitOrderHelper->getDeliveryLinePrice($rateQuoteResponse);
            $numDiscountPrice = $discountLinePrice;
            $shippingAccount = $shippingAccountData[0]['shipmentDelivery']['fedExAccountNumber'];
            //B-1275188 Identifying old rate quote response
            $requestedAmount = $this->getRequestedAmount(
                $shippingAccount,
                $rateQuoteResponse,
                $numTotal,
                $numDiscountPrice
            );
        }

        date_default_timezone_set('Asia/Kolkata');
        $date = $date = date('Y-m-d H:i:s');
        $dataa = $this->getCheckoutRequestData($date, $fjmpRateQuoteId, $customerInfo);

        if ($paymentMethod == "cc") {
            // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
            $ccToken = null;
            if ($this->sdeHelper->getIsSdeStore()
                && $useSiteCreditCard) {
                $siteCreditCardData = $this->companyHelper->getCompanyCreditCardData();
                $ccToken = $siteCreditCardData['token'] ?? null;
                $encCCData = null;
            }

            /** Retal payment via credit card token */
            $profileCreditCardId = '';
            if (isset($paymentData->profileCreditCardId)
            && $this->toggleConfig->getToggleConfigValue(ConfigProvider::XMEN_EXPRESS_CHECKOUT)) {
                $profileCreditCardId = $paymentData->profileCreditCardId;
                $ccToken = null;
                $nameOnCard = null;
                $encCCData = null;
                $response = $this->enhancedProfile->updateCreditCard([], $profileCreditCardId, 'GET');
                $cartDetails = $this->submitOrderHelper->getCreditCartDetails($response, $ccToken, $nameOnCard);
                $ccToken = $cartDetails['ccToken'];
                $nameOnCard = $cartDetails['nameOnCard'];
            }

            $dataa = $this->checkoutRequestWithTenders(
                $dataa,
                $shippingAccount,
                $isPickup,
                $numDiscountPrice,
                $requestedAmount,
                $poReferenceId,
                $accNo,
                $numTotal,
                $shipperRegion,
                $encCCData,
                $ccToken,
                $nameOnCard,
                $addressData,
                $stateCode,
                $addressClassification,
                $expirationMonth,
                $expirationYear
            );
        } elseif ($paymentMethod == "fedex") {
            $dataa = $this->getCheckoutRequestTendersData(
                $dataa,
                $shippingAccount,
                $isPickup,
                $numDiscountPrice,
                $requestedAmount,
                $poReferenceId,
                $accNo,
                $numTotal
            );
        }

        return $this->callTransactionAPI($dataa, $quoteId);
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
            'state' => $state,
            'regionCode' => $regionCode,
            'zipcode' => $zipcode
        ];
    }

    /**
     * Get Requested Amount
     *
     * @param int|float|string $shippingAccount
     * @param array $rateQuoteResponse
     * @param int|float|string $numTotal
     * @param int|float|string $numDiscountPrice
     * @return int|float|string
     */
    public function getRequestedAmount($shippingAccount, $rateQuoteResponse, $numTotal, $numDiscountPrice)
    {
        if ($shippingAccount != null
            && $shippingAccount != ''
            && count($rateQuoteResponse['output']['rateQuote']['rateQuoteDetails']) == 1
        ) {
            $requestedAmount = $numTotal - $numDiscountPrice;
        } else {
            $requestedAmount = $numTotal;
        }

        return $requestedAmount;
    }

    /**
     * Get Checkout Request Data
     *
     * @param date $date
     * @param string $fjmpRateQuoteId
     * @param array $customerInfo
     * @return array
     */
    public function getCheckoutRequestData($date, $fjmpRateQuoteId, $customerInfo)
    {
        $newFijtsuToggle = $this->toggleConfig->getToggleConfigValue('new_fujitsu_receipt_approach');
        $reciptType = 'NONE';
        $receiptFormat = 'STANDARD';
        if ($newFijtsuToggle) {
            $reciptType = 'EMAIL';
            $receiptFormat = 'INVOICE_EIGHT_BY_ELEVEN';
        }

        return [
            'checkoutRequest' => [
                'transactionHeader' => [
                    'requestDateTime' => $date,
                    'rateQuoteId' => $fjmpRateQuoteId,
                    'type' => "SALE",
                ],
                'transactionReceiptDetails' => [
                    'receiptType' => $reciptType,
                    'receiptFormat' => $receiptFormat,
                ],
                'contact' => [
                    'contactId' => null,
                    'personName' => [
                        'firstName' => $customerInfo['fName'],
                        'lastName' => $customerInfo['lName'],
                    ],
                    'company' => [
                        'name' => $customerInfo['companyName'],
                    ],
                    'emailDetail' => [
                        'emailAddress' => $customerInfo['email'],
                    ],
                    'phoneNumberDetails' => [
                        0 => [
                            'phoneNumber' => [
                                'number' => $customerInfo['phNumber'],
                                'extension' => $customerInfo['extension'],
                            ],
                            'usage' => 'PRIMARY',
                        ],
                    ],
                ],
                'tenders' => [],
            ],
        ];
    }

    /**
     * Checkout Request With Tenders Data Handler
     *
     * @param array $dataa
     * @param int|string $shippingAccount
     * @param bool $isPickup
     * @param int|float $numDiscountPrice
     * @param int|float|string $requestedAmount
     * @param int|float|string $poReferenceId
     * @param int|float|string $accNo
     * @param int|float|string $numTotal
     * @param object|null $shipperRegion
     * @param int|string $encCCData
     * @param int|string $ccToken
     * @param string|null $nameOnCard
     * @param array $addressData
     * @param int|string $stateCode
     * @param string $addressClassification
     * @param int|string $expirationMonth
     * @param int|string $expirationYear
     * @return array
     */
    public function checkoutRequestWithTenders(
        $dataa,
        $shippingAccount,
        $isPickup,
        $numDiscountPrice,
        $requestedAmount,
        $poReferenceId,
        $accNo,
        $numTotal,
        $shipperRegion,
        $encCCData,
        $ccToken,
        $nameOnCard,
        $addressData,
        $stateCode,
        $addressClassification,
        $expirationMonth,
        $expirationYear
    ) {
        if (($shippingAccount != null || $shippingAccount != "") && (!$isPickup)) {
            $dataa['checkoutRequest']['tenders'] = [
                0 => [
                    'id' => "1",
                    'currency' => "USD",
                    'paymentType' => "ACCOUNT",
                    'requestedAmount' => $numDiscountPrice,
                    'account' => [
                        'accountNumber' => $shippingAccount,
                        'responsibleParty' => "SENDER",
                    ],
                ],
                1 => [
                    'id' => "2",
                    'currency' => "USD",
                    'paymentType' => "CREDIT_CARD",
                    'requestedAmount' => $requestedAmount,
                    'creditCard' => [
                        'encryptedCreditCard' => $encCCData,
                        'token' => $ccToken,
                        'cardHolderName' => $nameOnCard,
                        'billingAddress' => [
                            'streetLines' => $addressData['streetAddress'],
                            'city' => $addressData['city'],
                            'stateOrProvinceCode' => isset($shipperRegion) ? $shipperRegion->getCode() : $stateCode,
                            'postalCode' => $addressData['zipcode'],
                            'countryCode' => 'US',
                            'addressClassification' => $addressClassification,
                        ],
                        'expirationMonth' => $expirationMonth,
                        'expirationYear' => $expirationYear,
                    ],
                    'poNumber' => $poReferenceId,
                ],
            ];
        } else {
            $dataa['checkoutRequest']['tenders'] = [
                0 => [
                    'id' => "1",
                    'currency' => "USD",
                    'paymentType' => "CREDIT_CARD",
                    'requestedAmount' => $numTotal,
                    'creditCard' => [
                        'encryptedCreditCard' => $encCCData,
                        'token' => $ccToken,
                        'cardHolderName' => $nameOnCard,
                        'billingAddress' => [
                            'streetLines' => $addressData['streetAddress'],
                            'city' => $addressData['city'],
                            'stateOrProvinceCode' =>
                            isset($shipperRegion) ? $shipperRegion->getCode() : $addressData['state'],
                            'postalCode' => $addressData['zipcode'],
                            'countryCode' => 'US',
                            'addressClassification' => $addressClassification,
                        ],
                        'expirationMonth' => $expirationMonth,
                        'expirationYear' => $expirationYear,
                    ],
                    'poNumber' => $poReferenceId,
                ],
            ];
        }

        return $dataa;
    }

    /**
     * Get Checkout Request Tenders Data
     *
     * @param array $dataa
     * @param int|string $shippingAccount
     * @param bool $isPickup
     * @param int|float $numDiscountPrice
     * @param int|float|string $requestedAmount
     * @param int|float|string $poReferenceId
     * @param int|float|string $accNo
     * @param int|float|string $numTotal
     * @return array
     */
    public function getCheckoutRequestTendersData(
        $dataa,
        $shippingAccount,
        $isPickup,
        $numDiscountPrice,
        $requestedAmount,
        $poReferenceId,
        $accNo,
        $numTotal
    ) {
        if (($shippingAccount != null || $shippingAccount != "") && (!$isPickup)) {
            $dataa['checkoutRequest']['tenders'] = [
                0 => [
                    'id' => "1",
                    'currency' => "USD",
                    'paymentType' => "ACCOUNT",
                    'requestedAmount' => $numDiscountPrice,
                    'account' => [
                        'accountNumber' => $shippingAccount,
                        'responsibleParty' => "SENDER",
                    ],
                ],
                1 => [
                    'id' => "2",
                    'currency' => "USD",
                    'paymentType' => "ACCOUNT",
                    'requestedAmount' => $requestedAmount,
                    'poNumber' => $poReferenceId,
                    "account" => [
                        'accountNumber' => $accNo,
                    ],
                ],
            ];
        } else {
            $dataa['checkoutRequest']['tenders'] = [
                0 => [
                    'id' => "1",
                    'currency' => "USD",
                    'paymentType' => "ACCOUNT",
                    'requestedAmount' => $numTotal,
                    'poNumber' => $poReferenceId,
                    "account" => [
                        'accountNumber' => $accNo,
                    ],
                ],
            ];
        }

        return $dataa;
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
        $output = $this->getResponseFromTransactionCXSAPI($dataString);
        $this->logger->info(__METHOD__.':'.__LINE__. ' After Transaction CXS API $output =>' . $output);
        $checkoutResponse = json_decode((string)$output, true);

        if (!empty($checkoutResponse)) {
            if (empty($checkoutResponse['errors']) && isset($checkoutResponse['output'])) {

                return ['error' => 0, 'msg' => 'Success', 'response' => $output];
            } else {
                $this->checkoutSession->unsOrderInProgress();
                $this->logger->error(__METHOD__.':'.__LINE__ . 'Transaction CXS API Failed' .$quoteId);

                return ['error' => 1, 'msg' => 'Failure', 'response' => $output];
            }
        } else {
            $this->checkoutSession->unsOrderInProgress();
            $this->logger->error(__METHOD__.':'.__LINE__. ' Error found no data'.$quoteId);

            return ['error' => 1, 'msg' => 'Error found no data', 'response' => ''];
        }
    }

    /**
     * Get Response From Transaction CXS API
     *
     * @param string $dataString
     * @return string
     */
    protected function getResponseFromTransactionCXSAPI($dataString)
    {
        /**  B-1109907: Optimize Configurations  */
        $isOptimize = $this->toggleConfig->getToggleConfigValue('is_optimize_configuration');
        $setupURL = $this->configInterface->getValue("fedex/transaction/transaction_post_api_url");
        if ($isOptimize) {
            $setupURL = $this->configInterface->getValue("fedex/general/transaction_post_api_url");
        }

        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        if (!$this->helper->isCommercialCustomer()) {
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        }
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            $authHeaderVal . $gateWayToken,
        ];

        if ($this->customerSession->getOnBehalfOf()) {
            array_push($headers, "X-On-Behalf-Of: " . $this->customerSession->getOnBehalfOf());
        }

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $dataString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );

        $this->curl->post($setupURL, $dataString);
        $this->logger->info(__METHOD__.':'.__LINE__.': Before Transaction CXS API $dataString => ' . $dataString);

        return $this->curl->getBody();
    }

    /**
     * Validate Checkout Response
     *
     * @param array $checkoutResponse
     * @param object $quote
     * @param int $quoteId
     * @param int|string $orderNumber
     * @param int|string $shipmentId
     * @param string $paymentData
     * @param array $rateQuoteResponse
     * @return array
     */
    public function validateCheckoutResponse(
        $checkoutResponse,
        $quote,
        $quoteId,
        $orderNumber,
        $shipmentId,
        $paymentData,
        $rateQuoteResponse
    ) {
        if (!empty($checkoutResponse)) {
            $boolCardAuthorizationStatus = true;
            $retailTransectionId = null;
            $productLineDetailsAttributes = null;

            if (isset($checkoutResponse['response'])) {
                $cardDecline = json_decode((string)$checkoutResponse['response']);
                //D-97873 added !empty condition (failed tests in UT)
                if ((isset($cardDecline->errors) && !empty($cardDecline->errors))
                || (isset($cardDecline->output->alerts) && is_array($cardDecline->output->alerts)
                && !empty($cardDecline->output->alerts))
                ) {
                    $boolCardAuthorizationStatus = false;

                    $this->logger->error(
                        __METHOD__.':'.__LINE__. ' Quote Id:' . $quoteId . ' GTN Number => ' . $orderNumber
                        . ' return => ' . json_encode(
                            [
                                'error' => 1,
                                'msg' => 'Failure',
                                'iscardAuthorize' => $boolCardAuthorizationStatus,
                                'response' => $checkoutResponse
                            ]
                        )
                    );

                    return [
                        'error' => 1,
                        'msg' => 'Failure',
                        'iscardAuthorize' => $boolCardAuthorizationStatus,
                        'response' => $checkoutResponse,
                    ];
                }

                $productDataConfig = $this->getRetailTransectionIdAndProductLineDetailsAttributes($cardDecline);
                $retailTransectionId = $productDataConfig['retailTransectionId'];
                $productLineDetailsAttributes = $productDataConfig['productLineDetailsAttributes'];
            }

            return $this->callHelperForPlaceOrder(
                $checkoutResponse,
                $boolCardAuthorizationStatus,
                $retailTransectionId,
                $quoteId,
                $quote,
                $orderNumber,
                $shipmentId,
                $productLineDetailsAttributes,
                $paymentData,
                $rateQuoteResponse
            );
        } else {
            $this->checkoutSession->unsOrderInProgress();
            $this->logger->error(
                __METHOD__.':'.__LINE__. ' Quote ID:' . $quoteId . ' $checkoutResponse => Error found no data'
            );

            return ['error' => 1, 'msg' => 'Error found no data', 'response' => ''];
        }
    }

    /**
     * Get Checkout Response Data
     *
     * @param array $rateQuoteResponse
     * @param int $quoteId
     * @param int|string $orderNumber
     * @param int|string $shipmentId
     * @param string $estimatePickupTime
     * @param array $checkoutResponse
     * @param string $paymentData
     * @param array $data
     * @param string $encCCData
     * @param bool $isPickup
     * @param bool $useSiteCreditCard
     * @return array
     */
    public function getCheckoutResponseData(
        $rateQuoteResponse,
        $quoteId,
        $orderNumber,
        $shipmentId,
        $estimatePickupTime,
        $paymentData,
        $data,
        $encCCData,
        $isPickup,
        $useSiteCreditCard
    ) {
        if (empty($rateQuoteResponse['errors']) && isset($rateQuoteResponse['output'])) {
            $this->logger->info(
                __METHOD__.':'.__LINE__. " Fujitsu Rate Quote API success for the Quote Id: " . $quoteId
                . ' Gtn-Num => ' . $orderNumber
            );

            $fjmpRateQuoteId = $this->submitOrderHelper->getRateQuoteId($rateQuoteResponse);

            $quoteObject = $this->quoteRepository->getActive($quoteId);
            $quoteObject->setData('fjmp_quote_id', $fjmpRateQuoteId);
            $quoteObject->setData('estimated_pickup_time', $estimatePickupTime);

            $this->logger->info(
                __METHOD__.':'.__LINE__. " Fujitsu Rate Quote Id " . $fjmpRateQuoteId . " for the Quote Id "
                . $quoteId . ' GTN Number => ' . $orderNumber
            );

            if ($paymentData->paymentMethod == 'instore') {
                $checkoutResponse = $this->callTransactionAPI(
                    $this->inStoreRequestBuilder->build($fjmpRateQuoteId),
                    $quoteObject->getId()
                );
            } else {
                $checkoutResponse = $this->constructTransactionAPI(
                    $quoteObject,
                    $data,
                    $paymentData,
                    $fjmpRateQuoteId,
                    $encCCData,
                    $rateQuoteResponse,
                    $isPickup,
                    $useSiteCreditCard
                );
            }

            return $this->validateCheckoutResponse(
                $checkoutResponse,
                $quoteObject,
                $quoteId,
                $orderNumber,
                $shipmentId,
                $paymentData,
                $rateQuoteResponse
            );
        } else {
            $this->checkoutSession->unsOrderInProgress();
            $this->logger->error(
                __METHOD__.':'.__LINE__.' Fujitsu Rate Quote API failed for the Quote Id: ' . $quoteId
            );

            return ['error' => 1, 'msg' => 'Failure', 'response' => $rateQuoteResponse];
        }
    }

    /**
     * Get Retail Transection Id and Product Line Details Attributes
     *
     * @param string $cardDecline
     * @return array
     */
    public function getRetailTransectionIdAndProductLineDetailsAttributes($cardDecline)
    {
        if (isset($cardDecline->output)) {
            $retailTracId = $cardDecline->output->checkout->transactionHeader;
            if (isset($retailTracId->retailTransactionId)) {
                $retailTransectionId = $retailTracId->retailTransactionId;
            }
            $productLineDetailsResponse =
            $cardDecline->output->checkout->lineItems[0]->retailPrintOrderDetails[0];
            if (isset($productLineDetailsResponse->productLines)) {
                $productLineDetailsAttributes = json_encode($productLineDetailsResponse->productLines);
            }
        }

        return [
            'retailTransectionId' => $retailTransectionId,
            'productLineDetailsAttributes' => $productLineDetailsAttributes
        ];
    }

    /**
     * Call Helper For Place Order
     *
     * @param array $checkoutResponse
     * @param bool $boolCardAuthorizationStatus
     * @param int|string $retailTransectionId
     * @param int $quoteId
     * @param object $quote
     * @param int|string $orderNumber
     * @param int|string $shipmentId
     * @param string|null $productLineDetailsAttributes
     * @param string $paymentData
     * @param array $rateQuoteResponse
     * @return array
     */
    public function callHelperForPlaceOrder(
        $checkoutResponse,
        $boolCardAuthorizationStatus,
        $retailTransectionId,
        $quoteId,
        $quote,
        $orderNumber,
        $shipmentId,
        $productLineDetailsAttributes,
        $paymentData,
        $rateQuoteResponse
    ) {
        if (($checkoutResponse['error'] == 0)
        && isset($checkoutResponse['response'])
        && $boolCardAuthorizationStatus) {
            $this->logger->info(
                __METHOD__.':'.__LINE__. " Retail Transaction Id received :"
                . $retailTransectionId . " for the quote id " . $quoteId
            );

            $isSetOrderId = $this->submitOrderHelper->isSetOrderId($quote, $orderNumber);

            return $this->placeOrderProcessing(
                $checkoutResponse,
                $quote,
                $quoteId,
                $isSetOrderId,
                $shipmentId,
                $retailTransectionId,
                $productLineDetailsAttributes,
                $paymentData,
                $rateQuoteResponse
            );
        } else {
            $this->checkoutSession->unsOrderInProgress();
            $this->logger->critical(
                __METHOD__.':'.__LINE__. ' Quote ID:' . $quoteId . ' Transaction CXS API failed'
            );

            return [
                'error' => 1,
                'msg' => 'Failure',
                'iscardAuthorize' => $boolCardAuthorizationStatus,
                'response' => $checkoutResponse,
            ];
        }
    }

    /**
     * Place Order Processing
     *
     * @param array $checkoutResponse
     * @param object $quote
     * @param int $quoteId
     * @param bool $isSetOrderId
     * @param int|string $shipmentId
     * @param int|string $retailTransectionId
     * @param string|null $productLineDetailsAttributes
     * @param string $paymentData
     * @param array $rateQuoteResponse
     * @return array
     */
    public function placeOrderProcessing(
        $checkoutResponse,
        $quote,
        $quoteId,
        $isSetOrderId,
        $shipmentId,
        $retailTransectionId,
        $productLineDetailsAttributes,
        $paymentData,
        $rateQuoteResponse
    ) {
        $logHeader = 'File: ' . static::class . ' Method: ' . __METHOD__;
        if (!$isSetOrderId) {

            return [
                'error' => true,
                'message' => 'Set order id is not updated for quote Id:' . $quoteId
            ];
        }

        $this->logger->info(
            __METHOD__.':'.__LINE__. ' Quote Id:' . $quoteId . ' $shipmentId => '
            . $shipmentId . ' $retailTransectionId => ' . $retailTransectionId
            . ' $productLineDetailsAttributes => ' . $productLineDetailsAttributes
        );

        try {
            $this->logger->info(
                __METHOD__.':'.__LINE__.': Before Placing the Order Quote ID:' . $quoteId
            );
            $orderId = $this->submitOrderHelper->placeOrder(
                $quote,
                $quoteId,
                $shipmentId,
                $retailTransectionId,
                $productLineDetailsAttributes,
                $paymentData
            );
        } catch (Exception $e) {
            // TODO: Remove quote logging, after the root cause found
            $this->logger->info(
                __METHOD__. ':' . __LINE__ . ' Before dumping quote data - Quote ID:'
                . $quote->getId()
            );
            $this->logger->info($logHeader .
                ' Line:' . __LINE__ .
                ' Quote-Id:' . $quote->getId() .
                ' Customer ID: ' . $quote->getCustomerId() .
                ' Customer Email: ' . $quote->getCustomerEmail() .
                ' Payment: ' . json_encode($quote->getPayment()->getData()) .
                ' Billing Address: ' . json_encode($quote->getBillingAddress()->getData()) .
                ' Shipping Address: ' . json_encode($quote->getShippingAddress()->getData()) .
                ' Stack trace: ' . $e->getTraceAsString()
            );
            $this->logger->info(
                __METHOD__.':' . __LINE__ . ' After dumping quote data -- Quote ID:'
                . $quote->getId()
            );

            $this->logger->error(
                __METHOD__.':' .  __LINE__ . ' Quote id:' . $quoteId
                . ' Message => ' . $e->getMessage() . ' ' . ' $shipmentId => '
                . $shipmentId . ' $retailTransectionId => ' . $retailTransectionId
                . ' $productLineDetailsAttributes => ' . $productLineDetailsAttributes
            );
            $this->checkoutSession->unsOrderInProgress();
            return ['error' => true, 'message' => $e->getMessage()];
        }

        $this->logger->info(
            __METHOD__.':'.__LINE__.': Before prepare producing address Quote ID:' . $quoteId
        );
        $this->submitOrderHelper->prepareOrderProducingAddress(
            $checkoutResponse['response'],
            $orderId
        );

        $this->logger->info(
            __METHOD__.':'.__LINE__. ' After prepare producing address Quote ID:' . $quoteId
        );

        //D-97873 Set quote id cookie to show additional details in order confirmation
        $this->submitOrderHelper->setCookie('quoteId', $quoteId);
        // If Order id is generated then call reorderable Instance API to preserve the instance.
        if ($this->authHelper->isLoggedIn()) {
            $this->reorderInstanceHelper->pushOrderIdInQueue($orderId);
        }
        // Push quote id in queue to clean item instance from quote
            $this->optimizeItemInstanceHelper->pushQuoteIdQueue($quoteId);

        $this->logger->info(
            __METHOD__.':'.__LINE__.': Order Creation Successful Quote ID:'
            . $quoteId . ' Order Id: ' . $orderId
        );

        $this->checkoutSession->clearQuote();

        /* Generate new quote id after place order successfully and clear cart and checkout session */

        $quoteId = null;
        $this->checkoutSession->unsAll();
        $this->checkoutSession->clearStorage();
        $newQuoteGenerate = $this->quote->create();
        $newQuoteGenerate->save();
        $this->logger->info(
            __METHOD__.':'.__LINE__."New generated Quote Id : " . $newQuoteGenerate->getId()
        );
        $this->logger->info(
            __METHOD__.':'.__LINE__. ' New generated Quote ID:'
            . $newQuoteGenerate->getId() . ' Old quote ID' . $quoteId . ' Order ID: ' . $orderId
        );
        $this->cart->truncate();

        /* End here to generate new quote id and clear checkout session */

        $this->logger->info(
            __METHOD__.':'.__LINE__. ' Checkout process done New generated Quote ID:'
            . $newQuoteGenerate->getId() . ' Old quote ID' . $quoteId . ' Order ID: ' . $orderId
        );

        //B-1275188 include rate quote response in the return inorder to show order totals
        return [$checkoutResponse['response'], 'rateQuoteResponse' => $rateQuoteResponse];
    }

    private function getFedexLocationId() {
        $fedexLocationId = null;
        if($this->crData->isRetailCustomer() && (!$this->requestQueryValidator->isGraphQl())){
            $fedexLocationId = $this->crData->getLocationCode();
        }

        return $fedexLocationId;
    }
}
