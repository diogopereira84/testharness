<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FXOPricing\Helper;

use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Model\FXOModel;
use Fedex\Header\Helper\Data;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfigInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\RegionFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Psr\Log\LoggerInterface;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\ExpiredItems\Helper\ExpiredData as ExpiredDataHelper;
use Fedex\InStoreConfigurations\Api\ConfigInterface;

class FXORate extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const DISCOUNT_CUSTOMER = 'AR_CUSTOMERS';
    public const DISCOUNT_COUPON = 'COUPON';

    protected ConfigInterface $cartConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $configInterface
     * @param LoggerInterface $logger
     * @param DeliveryHelper $deliveryHelper
     * @param CompanyHelper $companyHelper
     * @param PunchoutHelper $punchoutHelper
     * @param ProductModel $productModel
     * @param AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param SerializerInterface $serializer
     * @param Curl $curl
     * @param Cart $cart
     * @param CartFactory $cartFactory
     * @param RequestInterface $request
     * @param RegionFactory $regionFactory
     * @param Session $customerSession
     * @param ToggleConfig $toggleConfig
     * @param ManagerInterface $messageManager
     * @param CollectionFactory $quoteItemCollectionFactory
     * @param CheckoutSession $checkoutSession
     * @param CartDataHelper $cartDataHelper
     * @param SdeHelper $sdeHelper
     * @param CartInterface $quoteRepository
     * @param ExpiredDataHelper $expiredDataHelper
     * @param Data $data
     * @param ConfigInterface $config
     * @param ProductBundleConfigInterface $productBundleConfigInterface
     * @param FXOModel $fxoModel
     */
    public function __construct(
        Context $context,
        protected ScopeConfigInterface $configInterface,
        protected LoggerInterface $logger,
        protected DeliveryHelper $deliveryHelper,
        protected CompanyHelper $companyHelper,
        protected PunchoutHelper $punchoutHelper,
        protected ProductModel $productModel,
        protected AttributeSetRepositoryInterface $attributeSetRepositoryInterface,
        protected SerializerInterface $serializer,
        protected Curl $curl,
        protected Cart $cart,
        protected CartFactory $cartFactory,
        private RequestInterface $request,
        protected RegionFactory $regionFactory,
        protected Session $customerSession,
        protected ToggleConfig $toggleConfig,
        protected ManagerInterface $messageManager,
        private CollectionFactory $quoteItemCollectionFactory,
        protected CheckoutSession $checkoutSession,
        protected CartDataHelper $cartDataHelper,
        protected SdeHelper $sdeHelper,
        protected CartInterface $quoteRepository,
        protected ExpiredDataHelper $expiredDataHelper,
        protected Data $data,
        private readonly ConfigInterface $config,
        private readonly ProductBundleConfigInterface $productBundleConfigInterface,
        private FXOModel $fxoModel,
    ) {
        parent::__construct($context);
    }

    /**
     * Get authentication details
     *
     * @param Object $quote
     * @return array
     */
    public function getAuthenticationDetails($quote)
    {
        $companySite = $fedExAccountNumber = null;
        if ($this->deliveryHelper->isCommercialCustomer()) {
            $companySite = $this->deliveryHelper->getCompanySite();
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
            $access = $this->deliveryHelper->getApiToken();
            $accessToken = $access['token'];
            // B-1250149 : Magento Admin UI changes to group all the Customer account details
            $fedExAccountNumber = $this->companyHelper->getFedexAccountNumber();
            //B-1275215: Autopopulate fedex account number in cart
            if ($fedExAccountNumber
                && !$this->checkoutSession->getRemoveFedexAccountNumber()
                && !$quote->getData('fedex_account_number')
            ) {
                $fedExAccountNumber = $this->cartDataHelper->encryptData($fedExAccountNumber);
                $quote->setData('fedex_account_number', $fedExAccountNumber);
            }
        } else {
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
            $accessToken = $this->punchoutHelper->getTazToken();
        }
        $fedExAccountNumber = $this->cartDataHelper
            ->decryptData($quote->getData("fedex_account_number")) ?? null;

        return [
            'gateWayToken' => $gateWayToken,
            'accessToken' => $accessToken,
            'fedexAccountNumber' => $fedExAccountNumber,
            'companySite' => $companySite
        ];
    }

    /**
     * Get Rates
     *
     * @param Object $quote
     * @param string $mixedFlag (account|coupon|reorder)
     */
    public function getFXORate($quote, $mixedFlag = null, $validateContent = false)
    {
        $arrRecipients = $quoteObjectItemsCount = $dbQuoteItemCount = null;
        try {
            //Get access gatway token
            $authenticationDetails = $this->getAuthenticationDetails($quote);
            $fedExAccountNumber = $authenticationDetails['fedexAccountNumber'];
            $promoCodeArray = [];
            $couponCode = '';
            $dataString = '';
            $couponCode = $quote->getData("coupon_code");
            if (strlen((string)$couponCode)) {
                $promoCodeArray['code'] = $couponCode;
            }
            if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
                $dbQuoteItemCount = $this->fxoModel->getDbItemsCount($quote);
            } else {
                $items = $quote->getAllVisibleItems();

                $quoteItemCollection = $this->quoteItemCollectionFactory->create();
                $dbQuoteItemCount = $quoteItemCollection->addFieldToSelect('*')
                    ->addFieldToFilter('quote_id', $quote->getId())
                    ->getSize();
            }
            $quoteObjectItemsCount = count($items);
            if (empty($quoteObjectItemsCount)) {
                return null;
            }

            // Get iterated item data
            $itemData = $this->iterateItems($items, $quoteObjectItemsCount, $dbQuoteItemCount);

            // D-106217 -Shipping screen -Continue to payment Button is not coming and seeing system error
            $referenceId = '';
            $siteName = null;
            $site = $authenticationDetails['companySite'];
            if ($this->sdeHelper->getIsSdeStore() && $this->companyHelper->getCustomerCompany()) {
                // B-1378749 - SDE Add SiteName in Rate API
                $siteName = $this->companyHelper->getCustomerCompany()->getCompanyName() ?? null;
                $site = null;
            }
            $referenceId = mt_rand(1000, 9999);

            $arrShippingAddress = $quote->getCustomerShippingAddress() ?? null;
            $arrPickupLocationdata = $quote->getCustomerPickupLocationData() ?? null;
            if ($quote->getIsFromPickup() && is_array($arrPickupLocationdata) && $arrPickupLocationdata['locationId']) {
                $arrRecipients = [
                    0 => [
                        'contact' => null,
                        'reference' => $referenceId,
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => [
                                'id' => $arrPickupLocationdata['locationId'],
                            ],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => $itemData['productAssociations'],
                    ],
                ];

                if (isset($arrPickupLocationdata['fedExAccountNumber'])) {
                    $fedExAccountNumber = $arrPickupLocationdata['fedExAccountNumber'];
                }
            } elseif ($quote->getIsFromShipping() && is_array($arrShippingAddress) &&
            $arrShippingAddress['shipMethod']) {
                $arrRecipients = [
                    0 => [
                        'contact' => null,
                        'reference' => $referenceId,
                        'attention' => null,
                        'shipmentDelivery' => [
                            'address' => [
                                'streetLines' => $arrShippingAddress['street'],
                                'city' => $arrShippingAddress['city'],
                                'stateOrProvinceCode' => $arrShippingAddress['regionData'],
                                'postalCode' => $arrShippingAddress['zipcode'],
                                'countryCode' => 'US',
                                'addressClassification' => $arrShippingAddress['addrClassification'],
                            ],
                            'holdUntilDate' => null,
                            'serviceType' => $arrShippingAddress['shipMethod'],
                            'productionLocationId' => $arrShippingAddress['productionLocationId'],
                            'fedExAccountNumber' => $arrShippingAddress['fedExShippingAccountNumber'],
                            'deliveryInstructions' => null,
                        ],
                        'productAssociations' => $itemData['productAssociations'],
                    ],
                ];
                $this->checkoutSession->setServiceType($arrShippingAddress['shipMethod']);
                if (isset($arrShippingAddress['fedExAccountNumber'])) {
                    $fedExAccountNumber = $arrShippingAddress['fedExAccountNumber'];
                }
                $shipmentSpecialServices = $this->deliveryHelper->getRateRequestShipmentSpecialServices();
                if (!empty($shipmentSpecialServices)) {
                    $arrRecipients[0]['shipmentDelivery']['specialServices'] = $shipmentSpecialServices;
                }

            }

            if (!empty($itemData['rateApiProdRequestData'])) {
                $coupons = !empty($promoCodeArray['code']) ? [$promoCodeArray] : null;
                if ($quote->getIsFromPickup() && $arrPickupLocationdata['fedExAccountNumber']) {
                    $fedExAccountNumber = $arrPickupLocationdata['fedExAccountNumber'];
                } elseif ($quote->getIsFromShipping() && $arrShippingAddress['fedExAccountNumber']) {
                    $fedExAccountNumber = $arrShippingAddress['fedExAccountNumber'];
                }
                $rateApiData = [
                    'rateRequest' => [
                        'fedExAccountNumber' => $fedExAccountNumber ? $fedExAccountNumber : null,
                        'profileAccountId' => null,
                        'site' => $site,
                        'siteName' => $siteName,
                        'products' => $itemData['rateApiProdRequestData'],
                        'recipients' => null,
                        'loyaltyCode' => null,
                        'specialInstructions' => null,
                        'coupons' => $coupons,
                    ],
                ];
                if (($quote->getIsFromPickup() || $quote->getIsFromShipping()) && is_array($arrRecipients)) {
                    $rateApiData['rateRequest']['recipients'] = $arrRecipients;
                }
                $rateApiData['rateRequest']['validateContent'] = true;
                $this->customerSession->unsValidateContentApiExpired();
                if (!empty($this->customerSession->getExpiredItemIds())) {
                    $this->customerSession->setValidateContentApiExpired(true);
                    $rateApiData = $this->expiredDataHelper->exludeExpiredProductFromRateRequest($rateApiData);
                }

                $authHeaderVal = $this->data->getAuthHeaderValue();

                $dataString = json_encode($rateApiData);
                $setupURL = $this->getRateApiUrl();
                $headers = [
                    "Content-Type: application/json",
                    "Accept: application/json",
                    "Accept-Language: json",
                    "Content-Length: " . strlen($dataString),
                    $authHeaderVal . $authenticationDetails['gateWayToken'],
                    "Cookie: Bearer=" . $authenticationDetails['accessToken'],
                ];

                $productRates = $this->callRateApi(
                    $quote,
                    $items,
                    $itemData['itemsUpdatedData'],
                    $couponCode,
                    $setupURL,
                    $headers,
                    $dataString,
                    $itemData['quoteObjectItemsCount'],
                    $itemData['dbQuoteItemCount'],
                    $mixedFlag
                );
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'FXO Rate Request' .$dataString);
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' System error, Please try again.');
            }
            return $productRates ?? true;

        } catch (\Exception $error) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . 'FXO Rate Request' .$dataString);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' System error, Please try again. ' . $error->getMessage());

            return json_encode(["errors" => "System error, Please try again."]);
        }
    }

    /**
     * Get authentication details
     *
     * @param Object $items
     * @param int $quoteObjectItemsCount
     * @param int $dbQuoteItemCount
     * @return array
     */
    public function iterateItems($items, $quoteObjectItemsCount, $dbQuoteItemCount)
    {
        $externalProdData = $rateApiProdRequestData = $itemsUpdatedData = $productAssociations = [];
        $index = 0;

        $useRateApiShippingValidation = $this->toggleConfig->getToggleConfigValue('armada_call_rate_api_shipping_validation');

        foreach ($items as $key => $item) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Product Instance Id: '. $item->getItemId(). '' . $key);
            if (($item->getMiraklOfferId() && $useRateApiShippingValidation)
                || $item->getProductType() === ProductModel\Type::TYPE_BUNDLE) {
                continue;
            }
            $decodedData = [];
            $pid = $item->getProduct()->getId();
            $qty = $item->getQty();
            $product = $this->productModel->load($pid);
            $isAttribute = $product->getAttributeSetId();
            $attributeSetRepository = $this->attributeSetRepositoryInterface->get($isAttribute);
            $attributeSetName = $attributeSetRepository->getAttributeSetName();
            $isCustomize = $product->getCustomizable();
            $additionalOption = $item->getOptionByCode('info_buyRequest');
            $additionalOptions = $additionalOption->getValue();
            $decodedData = (array)$this->serializer->unserialize($additionalOptions);
            $decodedData['external_prod'][0]['qty'] = $qty;
            $instanceIdFeatureToggle = $this->toggleConfig->getToggleConfigValue('tech_titans_D_174940_documents_cannot_be_added_to_the_cart');
            $externalId = $key;
            if ($instanceIdFeatureToggle) {
                $externalId = isset($decodedData['external_prod'][0]['id']) ? $decodedData['external_prod'][0]['id'] : $key;
            }

            // Fetch the dlt_threshold attribute value
            if ($this->toggleConfig->getToggleConfigValue('explorers_d196313_fix') && $attributeSetName != 'FXOPrintProducts') {
                $dltHours = $this->cartDataHelper->getDltThresholdHours($product, $qty);
                if (!empty($dltHours)) {
                    $decodedData = $this->cartDataHelper->setDltThresholdHours($decodedData, $dltHours);
                }
            }

            if ($quoteObjectItemsCount == $dbQuoteItemCount) {
                $productAssociations[] = ['id' => $item->getItemId(), 'quantity' => $item->getQty()];

            } else {
                $productAssociations[] = ['id' => $index, 'quantity' => $item->getQty()];
            }

            if ($attributeSetName != 'FXOPrintProducts' && !$isCustomize) {
                $tk4437408 = $this->toggleConfig->getToggleConfigValue('tiger_tk4437408');
                if ($quoteObjectItemsCount == $dbQuoteItemCount && (!$tk4437408 || $item->getItemId())) {
                    $decodedData['external_prod'][0]['instanceId'] = $item->getItemId();
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Product Instance Id: '. $item->getItemId(). '' . $key);
                } else {
                    if ($instanceIdFeatureToggle) {
                        $decodedData['external_prod'][0]['instanceId'] =  $item->getItemId() ? $item->getItemId() : $externalId . $key;
                    } else {
                        $decodedData['external_prod'][0]['instanceId'] = $item->getItemId() ? $item->getItemId() : "$key";
                    }

                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Product Instance Id: '. $item->getItemId(). '' . $key);
                }
                $rateApiProdRequestData[] = $decodedData['external_prod'][0];
                $itemsUpdatedData[] = $decodedData;
            } else {
                if (isset($decodedData['external_prod'][0]['fxo_product'])) {
                    $productData = $decodedData['external_prod'][0]['fxo_product'];
                    $qtyData = json_decode($productData, true);
                    $qtyData['fxoProductInstance']['productConfig']['product']['qty'] = $qty;
                    $qtyData['fxoProductInstance']['fileManagementState']['projects'][0]
                    ['productConfig']['product']['qty'] = $qty;
                }
                $externalProdData = $decodedData;
                $itemsUpdatedData[] = $decodedData;
                $externalProdData['external_prod'][0]['qty'] = $qty;

                if ($quoteObjectItemsCount == $dbQuoteItemCount) {
                    $externalProdData['external_prod'][0]['instanceId'] = $item->getItemId();
                } else {
                    if ($instanceIdFeatureToggle) {
                        $decodedData['external_prod'][0]['instanceId'] =  $item->getItemId() ? $item->getItemId() : $externalId . $key;
                        if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
                            $externalProdData['external_prod'][0]['instanceId'] = $item->getItemId() ? $item->getItemId() : $externalId . $key;
                        }
                    } else {
                        $externalProdData['external_prod'][0]['instanceId'] =
                        $item->getItemId() ? $item->getItemId() : "$key";
                    }

                }

                $externalProdData['external_prod'][0]['preview_url'] = null;
                if (isset($decodedData['external_prod'][0]['fxo_product'])) {
                    $externalProdData['external_prod'][0]['fxo_product'] = null;
                }
                if ($instanceIdFeatureToggle && !$externalProdData['external_prod'][0]['instanceId']) {
                    $externalProdData['external_prod'][0]['instanceId'] =  $item->getItemId() ? $item->getItemId() : $externalId . $key;
                }
                $rateApiProdRequestData[] = $externalProdData['external_prod'][0];

            }
            $index++;
        } // End foreach

        $associations = $productAssociations;
        if ($this->config->isRateQuoteProductAssociationEnabled()) {
            $associations = [];
            foreach ($productAssociations as $productAssociation) {
                array_push($associations, reset($productAssociation));
            }
        }

        return [
            'quoteObjectItemsCount' => $quoteObjectItemsCount,
            'rateApiProdRequestData' => $rateApiProdRequestData,
            'productAssociations' => $associations,
            'itemsUpdatedData' => $itemsUpdatedData,
            'dbQuoteItemCount' => $dbQuoteItemCount
        ];
    }

    /**
     * Update Quote discount in DB.
     *
     * @param CartFactory $quote
     * @param productRates $productRates
     * @param couponCode $couponCode
     */
    public function updateQuoteDiscount($quote, $productRates, $couponCode)
    {
        if (isset($productRates['output']['rate']['rateDetails'][0]['netAmount'])) {
            $netAmount = $productRates['output']['rate']['rateDetails'][0]['netAmount'];
            $netAmount = str_replace(['$', ',', '(', ')'], '', $netAmount);
            $totalDiscountAmount = $productRates['output']['rate']['rateDetails'][0]['totalDiscountAmount'];
            $totalDiscountAmount = str_replace(['$', ',', '(', ')'], '', $totalDiscountAmount);
            $grossAmount = $productRates['output']['rate']['rateDetails'][0]['grossAmount'];
            $grossAmount = str_replace(['$', ',', '(', ')'], '', $grossAmount);
            $quote->setDiscount($totalDiscountAmount);
            $quote->setCouponCode($couponCode);
            $quote->setSubtotal($grossAmount);
            $quote->setBaseSubtotal($grossAmount);

            if ($this->toggleConfig->getToggleConfigValue('techtitans_205366_subtotal_fix')) {
                $quote->setSubtotalWithDiscount($grossAmount);
                $quote->setBaseSubtotalWithDiscount($grossAmount);
                $quote->getShippingAddress()->setSubtotal($grossAmount);
                $quote->getShippingAddress()->setBaseSubtotal($grossAmount);
            }

            $quote->setGrandTotal($netAmount);
            $quote->setBaseGrandTotal($netAmount);
            $quote->save();
        }

        return true;
    }

    /**
     * Get Rate API URL
     */
    public function getRateApiUrl()
    {
        return $this->configInterface->getValue("fedex/general/rate_api_url");
    }

    /**
     * Call Rate API
     *
     * @param object $quote
     * @param object $items
     * @param array $itemsUpdatedData
     * @param string $couponCode
     * @param string $setupURL
     * @param string $headers
     * @param string $dataString
     * @param string $mixedFlag
     */
    public function callRateApi(
        $quote,
        $items,
        $itemsUpdatedData,
        $couponCode,
        $setupURL,
        $headers,
        $dataString,
        $quoteObjectItemsCount,
        $dbQuoteItemCount,
        $mixedFlag,
        $isFromMvp = false
    ) {
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
        $output = $this->curl->getBody();
        /**
         * @todo Convert response from JSON to Data Object
         *
         * @see \Fedex\FXOPricing\Api\Data\RateInterface
         * @see \Fedex\FXOPricing\Api\RateBuilderInterface
         */
        $rateApiOutputdata = [];
        if ($output) {
            $rateApiOutputdata = json_decode($output, true);
        } else {
            $rateApiOutputdata['output'] = [];
        }

        if($isFromMvp){
            return $rateApiOutputdata;
        }

        $this->expiredDataHelper->unSetExpiredItemids($rateApiOutputdata);

        //Check and remove MAX.PRODUCT.COUNT & Address Validation Alerts
        if (!empty($rateApiOutputdata['output']['alerts']) &&
            !empty($rateApiOutputdata['output']['alerts'][0]['code'])
        ) {
            $rateApiOutputdata = $this->manageCartWarnings($rateApiOutputdata);
        } elseif (!empty($rateApiOutputdata['output']['alerts']) &&
            !empty($rateApiOutputdata['output']['alerts'][0]['code']) &&
            $rateApiOutputdata['output']['alerts'][0]['code'] == 'MAX.PRODUCT.COUNT'
        ) {
            if (count($rateApiOutputdata['output']['alerts']) > 1) {
                unset($rateApiOutputdata['output']['alerts'][0]);
                $rateApiOutputdata['output']['alerts'] = array_values($rateApiOutputdata['output']['alerts']);
            } else {
                unset($rateApiOutputdata['output']['alerts'][0]);
            }
        }

        if (isset($rateApiOutputdata['errors']) || !isset($rateApiOutputdata['output'])) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Rate API request at FXORate:');
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Rate API response at FXORate:');
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $output);
            $quote = $this->cartFactory->create()->getQuote();
            if ($mixedFlag == 'reorder') {
                // Remove item from quote and return item name
                $errorMessage = $this->removeReorderQuoteItem($quote, $rateApiOutputdata['errors']);

                $rateResultdata =  json_encode(["errors" => $errorMessage]);
            } else {
                $this->removeQuoteItem($quote);
                $rateResultdata =  $rateApiOutputdata;
            }

            return $rateResultdata;
        }

        if (!empty($rateApiOutputdata['output']['alerts']) &&
            !empty($rateApiOutputdata['output']['alerts'][0]['code'])) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Rate API request at FXORate:');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $dataString);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Rate API response at FXORate:');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $output);
            $quote = $this->cartFactory->create()->getQuote();
            // Manage coupon code reset
            if ($quote->getIsFromShipping() || $quote->getIsFromPickup()) {
                return $rateApiOutputdata;
            } else {
                $couponCode = $this->resetCartDiscounts($quote, $rateApiOutputdata);
            }
        }
        if ($quote->getIsFromAccountScreen() && ($quote->getIsFromShipping() || $quote->getIsFromPickup())) {
            $this->updateRateForAccount($rateApiOutputdata, $quote);
        } else {
            if (($quote->getIsFromShipping() || $quote->getIsFromPickup())) {
                $this->updateShippingPickupDetail($quote, $rateApiOutputdata);
            } else {
                $this->updateCartItems(
                    $items,
                    $rateApiOutputdata,
                    $itemsUpdatedData,
                    $quoteObjectItemsCount,
                    $dbQuoteItemCount
                );

                $this->updateQuoteDiscount($quote, $rateApiOutputdata, $couponCode);
            }
        }
        $this->saveDiscountBreakdown($quote, $rateApiOutputdata);

        return $rateApiOutputdata;
    }

    /**
     * Manage Cart Discount
     *
     * @param object $quote
     * @param array $rateApiOutputdata
     * @return string|null
     */
    public function resetCartDiscounts($quote, $rateApiOutputdata)
    {
        $discountTypes = isset($rateApiOutputdata['output']['rate']['rateDetails']
                    [0]['discounts']) ? $rateApiOutputdata['output']['rate']['rateDetails']
                    [0]['discounts'] : [];
        $accountDiscount = null;
        $couponDiscount = null;
        foreach ($discountTypes as $discountType) {
            if ($discountType['type'] == static::DISCOUNT_CUSTOMER) {
                $accountDiscount = true;
            } elseif ($discountType['type'] == static::DISCOUNT_COUPON) {
                $couponDiscount = true;
            }
        }
        if (!$accountDiscount) {
            $this->resetFedexAccount($rateApiOutputdata['output'], $quote);
            $couponCode = $quote->getCouponCode();
        }
        if (!$couponDiscount) {
            $couponCode = $this->removePromoCode($rateApiOutputdata['output'], $quote);
        }

        return $couponCode ?? null;
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
    public function updateCartItems($items, $productRates, $itemsUpdatedData, $quoteObjectItemsCount, $dbQuoteItemCount)
    {
        $productLines = $productRates['output']['rate']['rateDetails'][0]['productLines'] ?? [];
        if (!empty($productLines)) {
            $count = 0;
            foreach ($items as $key => $item) {
                if ($item->getProductType() === ProductModel\Type::TYPE_BUNDLE) {
                    continue;
                }

                foreach ($productLines as $productLine) {
                    if ($quoteObjectItemsCount == $dbQuoteItemCount) {
                        $key = $item->getItemId();
                    }
                    if ($key == $productLine['instanceId']) {
                        $price = !empty($productLine['productRetailPrice']) ? $productLine['productRetailPrice'] : 0;
                        $price = str_replace('$', '', $price);
                        $price = str_replace(',', '', $price);
                        /**  D-83711 - Negotible quote getting blank **/
                        /* B-1299551 toggle clean up start end */
                        $itemTotalRoundoff = true;
                        // Item total mismatch
                        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
                        $itemTotalRoundoff = $itemTotalRoundoff && $isEproCustomer;
                        $unitQty = $productLine['unitQuantity'];
                        if ($itemTotalRoundoff) {
                            $unitPrice = $price / $unitQty;
                            $item->setBaseRowTotal($unitPrice * $unitQty);
                            $item->setRowTotal($unitPrice * $unitQty);

                        } else {
                            $unitPrice = $price / $productLine['unitQuantity'];
                            $item->setBaseRowTotal($price);
                            $item->setRowTotal($price);
                        }
                        $fedexDiscount = !empty($productLine['productDiscountAmount']) ? $productLine['productDiscountAmount'] : 0;
                        $fedexDiscount = ltrim($fedexDiscount, "($");
                        $fedexDiscount = rtrim($fedexDiscount, ")");
                        $fedexDiscount = str_replace(',', '', $fedexDiscount);
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
                        $additionalOption = $item->getOptionByCode('info_buyRequest');
                        if (!empty($additionalOption->getOptionId())) {
                            if (isset($itemsUpdatedData[$count])) {
                                $additionalOption->setValue($this->serializer->serialize($itemsUpdatedData[$count]))
                                ->save();
                            }
                        } else {
                            $optionIds = $item->getOptionByCode('custom_option');
                            if ($optionIds) {
                                $item->removeOption('custom_option');
                            }
                        }
                        break;
                    }
                }
                $count++;
            }
        }
    }

    /**
     * Update shipping or pickup information
     *
     * @param Quote $quote
     * @param ProductRates $productRates
     */
    public function updateShippingPickupDetail($quote, $productRates)
    {
        if (!empty($productRates['output']['rate'])) {
            $rateOutputData = $productRates['output']['rate'];
            $totalNetAmount = $totalTaxAmount = $discountTotal = $deliveryRetailPrice = 0;
            $responseRateDetails = $rateOutputData['rateDetails'];
            foreach ($responseRateDetails as $rateDetail) {
                if (array_key_exists('taxAmount', $rateDetail)) {
                    $totalTaxAmount += str_replace(["$", ",", "(", ")"], "", $rateDetail['taxAmount']);
                }
                if (array_key_exists('discounts', $rateDetail)) {
                    foreach ($rateDetail['discounts'] as $discount) {
                        $discountTotal += str_replace(["$", ",", "(", ")"], "", $discount['amount']);
                    }
                }
                $totalNetAmount += str_replace(["$", ",", "(", ")"], "", $rateDetail['totalAmount']);
                if (array_key_exists('deliveryLines', $rateDetail)) {
                    foreach ($rateDetail['deliveryLines'] as $deliveryLine) {
                        $deliveryRetailPrice += str_replace(
                            ["$", ",", "(", ")"],
                            "",
                            $deliveryLine['deliveryRetailPrice']
                        );
                    }
                }
            }
            $quote->setDiscount($discountTotal);
            $quote->setGrandTotal($totalNetAmount);
            $quote->setBaseGrandTotal($totalNetAmount);
            $quote->setCustomTaxAmount($totalTaxAmount);
            $this->checkoutSession->setShippingCost($deliveryRetailPrice);
            $quote->setShippingCost($deliveryRetailPrice);
            $quote->save();
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
        $qty = $productDiscountAmount = $basePrice = $customPrice = $price = [];
        if (isset($productRates['output']['rate']['rateDetails'][0]['productLines'])) {
            foreach ($productRates['output']['rate']['rateDetails'][0]['productLines'] as $val) {
                $k = $val['instanceId'];
                $qty[$k] = $val['unitQuantity'];
                $basePrice[$k] = $val['productRetailPrice'];
                $customPrice[$k] = $val['productRetailPrice'];
                $price[$k] = $val['productRetailPrice'];
                $productDiscountAmount[$k] = $val['productDiscountAmount'];
            }
            if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
                $items = $quote->getAllItems();
            } else {
                $items = $quote->getAllVisibleItems();
            }
            foreach ($items as $item) {
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
        if (!empty($productRates['output']['rate']['rateDetails'])) {
            $productLinesTotal = $discountTotal = $totalNetAmount = $totalTaxAmount = $deliveryRetailPrice = 0;
            $responseRateDetails = $productRates['output']['rate']['rateDetails'];
            foreach ($responseRateDetails as $rateDetail) {
                if (array_key_exists('productLines', $rateDetail)) {
                    foreach ($rateDetail['productLines'] as $productLine) {
                        $productLinesTotal += str_replace(["$", ",", "(", ")"], "", $productLine['productRetailPrice']);
                    }
                }
                if (array_key_exists('deliveryLines', $rateDetail)) {
                    foreach ($rateDetail['deliveryLines'] as $deliveryLine) {
                        $deliveryRetailPrice += str_replace(
                            ["$", ",", "(", ")"],
                            "",
                            $deliveryLine['deliveryRetailPrice']
                        );
                    }
                }
                if (array_key_exists('discounts', $rateDetail)) {
                    foreach ($rateDetail['discounts'] as $discount) {
                        $discountTotal += str_replace(["$", ",", "(", ")"], "", $discount['amount']);
                    }
                }
                if (array_key_exists('taxAmount', $rateDetail)) {
                    $totalTaxAmount += str_replace(["$", ",", "(", ")"], "", $rateDetail['taxAmount']);
                }
                $totalNetAmount += str_replace(["$", ",", "(", ")"], "", $rateDetail['totalAmount']);
            }
            $quote->setDiscount($discountTotal);
            $quote->setSubTotal($productLinesTotal);
            $quote->setBaseSubTotal($productLinesTotal);
            $quote->setGrandTotal($totalNetAmount);
            $quote->setBaseGrandTotal($totalNetAmount);
            $quote->setCustomTaxAmount($totalTaxAmount);
            $this->checkoutSession->setShippingCost($deliveryRetailPrice);
            $quote->setShippingCost($deliveryRetailPrice);
            $quote->save();
        }
    }

    /**
     * Refactor Coupon Code
     */
    public function removePromoCode($fxoRateResponse, $quote)
    {
        $message = null;
        $couponCode = $quote->getCouponCode();
        if ($couponCode) {
            $alertCode = $fxoRateResponse['alerts'][0]['code'];
            if ($alertCode == "COUPONS.CODE.INVALID") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid coupon code.');
                $message = 'Promo code invalid. Please try again.';
                $quote->setCouponCode();
            } elseif ($alertCode == "MINIMUM.PURCHASE.REQUIRED") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Minimum purchase amount not met.');
                $message = 'Minimum purchase amount not met.';
                $quote->setCouponCode();
            } elseif ($alertCode == "INVALID.PRODUCT.CODE") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Invalid product code.');
                $message = $fxoRateResponse['alerts'][0]['message'];
                $quote->setCouponCode();
            } elseif ($alertCode == "COUPONS.CODE.EXPIRED") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code expired.');
                $message = 'Promo code has expired. Please try again.';
                $quote->setCouponCode();
            } elseif ($alertCode == "COUPONS.CODE.REDEEMED") {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Promo code has already been redeemed.');
                $message = 'Promo code has already been redeemed.';
                $quote->setCouponCode();
            }
            if ($message && !$quote->getIsFromShipping() &&
            !$quote->getIsFromPickup() && !$quote->getIsFromAccountScreen()) {
                $this->customerSession->setPromoErrorMessage(__($message));
                return $quote->getCouponCode() ?? null;
            }
            return $message;
        }
    }

    /**
     * Manage account number reset in case of alert from Rate API.
     * @param string $fxoRateResponse
     * @param object $quote
     * @return string|null
     */
    public function resetFedexAccount($fxoRateResponse, $quote)
    {
        $accountNumber = $this->cartDataHelper->decryptData($quote->getData('fedex_account_number'));
        if ($accountNumber) {
            $this->customerSession->setFedexAccountWarning('The account number entered is invalid.');
            $quote->setData('fedex_account_number', '');
        }

        return $accountNumber ?? null;
    }

    /**
     * Remove item from cart
     *
     * @param object $quote
     */
    public function removeQuoteItem($quote)
    {
        if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
            $items = $quote->getAllItems();
        } else {
            $items = $quote->getAllVisibleItems();
        }
        foreach ($items as $item) {
            if (!$item->getCustomPrice()) {
                $quote->deleteItem($item);
            }
        }
        $this->quoteRepository->save($quote);
    }

    /**
     * Remove item from cart
     *
     * @param object $quote
     * @param array $rateApiResponse
     * @return string $errorMessage
     */
    public function removeReorderQuoteItem($quote, $rateApiResponse)
    {
        if ($this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
            $items = $quote->getAllItems();
        } else {
            $items = $quote->getAllVisibleItems();
        }
        $errorMessage = 'System error, Please try again.';

        foreach ($items as $item) {
            if (!$item->getCustomPrice()) {
                $quote->deleteItem($item);
            }
        }
        $this->cart->save();

        return $errorMessage;
    }

    /**
     * SaveDiscountBreakdown
     *
     * @param object $quote
     * @param array $rateApiOutputdata
     * @return boolean
     */
    public function saveDiscountBreakdown($quote, $rateApiOutputdata)
    {
        $discounts = $this->getDiscounts($rateApiOutputdata);
        $accountDiscount = [];
        $qtyDiscount = [];
        $promoDiscountarr =[];
        if (!empty($discounts)) {
            if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
                $shippingDiscount = $this->getShippingDiscount($rateApiOutputdata);
                foreach ($discounts as $key => $val) {
                    foreach ($val as $discount) {
                        $discount['amount'] = (string)$discount['amount'];
                        if ($discount['type'] == 'AR_CUSTOMERS' || $discount['type'] == 'CORPORATE') {
                            $accountDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                        } elseif ($discount['type'] == 'QUANTITY') {
                            $qtyDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                        } elseif ($discount['type'] == 'COUPON') {
                            $promoDiscountarr[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                        }
                    }
                }
            } else {
                $shippingDiscount = 0;
                foreach ($discounts as $discount) {
                    $discount['amount'] = (string)$discount['amount'];
                    if ($discount['type'] == 'AR_CUSTOMERS' || $discount['type'] == 'CORPORATE') {
                        $accountDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                    } elseif ($discount['type'] == 'QUANTITY') {
                        $qtyDiscount[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                    } elseif ($discount['type'] == 'COUPON') {
                        $promoDiscountarr[] = str_replace(',', '', rtrim(ltrim($discount['amount'], "($"), ")"));
                    }
                }
            }
            $promoDiscount = array_sum($promoDiscountarr);
            if ($shippingDiscount == 0.00) {
                $quote->setPromoDiscount($promoDiscount);
            } else {
                $quote->setPromoDiscount($promoDiscount-$shippingDiscount);
            }
            $quote->setAccountDiscount(array_sum($accountDiscount));
            $quote->setVolumeDiscount(array_sum($qtyDiscount));
            $quote->setShippingDiscount($shippingDiscount);
            $this->resetDiscount($quote, $discounts);
        } else {
            $quote->setVolumeDiscount(0);
            $quote->setAccountDiscount(0);
            $quote->setPromoDiscount(0);
        }
        $quote->save();

        return true;
    }

    /**
     * Get Discounts
     *
     * @param array $rateApiOutputdata
     * @return array
     */
    public function getDiscounts($rateApiOutputdata)
    {
        $discounts = [];
        $rateQuoteDetails = $rateApiOutputdata['output']['rate']['rateDetails'] ?? [];
        if (!empty($rateQuoteDetails)) {
            foreach ($rateQuoteDetails as $details) {
                if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
                    if (!empty($details['productLines'])) {
                        foreach ($details['productLines'] as $deldisc) {
                            if (!empty($deldisc['productLineDiscounts'])) {
                                $discounts[] = $deldisc['productLineDiscounts'];
                            }
                        }
                    }
                } else {
                    if (!empty($details['discounts'])) {
                        $discounts = $details['discounts'];
                    }
                }
            }
        }
        return $discounts;
    }

    /**
     * Get Discounts
     *
     * @param array $rateApiOutputdata
     * @return float
     */
    public function getShippingDiscount($rateApiOutputdata)
    {
        $discount = 0;
        $rateQuoteDetails = $rateApiOutputdata['output']['rate']['rateDetails'] ?? [];
        if (!empty($rateQuoteDetails)) {
            foreach ($rateQuoteDetails as $discdetails) {
                if (!empty($discdetails['deliveryLines'][0]['deliveryLineDiscounts'])) {
                    foreach ($discdetails['deliveryLines'][0]['deliveryLineDiscounts'] as $deldisc) {
                        if ($deldisc['type'] == 'COUPON' || $deldisc['type'] == 'CORPORATE') {
                            if (is_string($deldisc['amount'])) {
                                $discount = str_replace(',', '', rtrim(ltrim($deldisc['amount'], "($"), ")"));
                            } else {
                                $discount = $deldisc['amount'];
                            }
                        }
                    }
                }
            }
        }
        return $discount;
    }

    /**
     * ResetDiscount
     *
     * @param object $quote
     * @param array $discounts
     * @return boolean
     */
    public function resetDiscount($quote, $discounts)
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeek_B2352379_discount_breakdown')) {
            foreach ($discounts as $discountChild) {
                if (!in_array('QUANTITY', array_column($discountChild, 'type'))) {
                    $quote->setVolumeDiscount(0);
                }
                if (!in_array('AR_CUSTOMERS', array_column($discountChild, 'type'))
                    && !in_array('CORPORATE', array_column($discountChild, 'type'))) {
                    $quote->setAccountDiscount(0);
                }
                if (!in_array('COUPON', array_column($discountChild, 'type'))) {
                    $quote->setPromoDiscount(0);
                }
            }
        } else {
            if (!in_array('QUANTITY', array_column($discounts, 'type'))) {
                $quote->setVolumeDiscount(0);
            }
            if (!in_array('AR_CUSTOMERS', array_column($discounts, 'type'))
                && !in_array('CORPORATE', array_column($discounts, 'type'))) {
                $quote->setAccountDiscount(0);
            }
            if (!in_array('COUPON', array_column($discounts, 'type'))) {
                $quote->setPromoDiscount(0);
            }
        }

        return true;
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
     * Check if customer is Epro
     */
    public function isEproCustomer()
    {
        return $this->deliveryHelper->isEproCustomer();
    }
}
