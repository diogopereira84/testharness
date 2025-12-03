<?php
namespace Fedex\Delivery\Helper;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutData;
use Fedex\Purchaseorder\Helper\Notification;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\CartFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\CustomerCanvas\Model\ConfigProvider;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const FXO_PRODUCT = 'fxo_product';
    public const IS_OUTSOURCED = 'isOutSourced';
    public const RATE_DETAILS = 'rateDetails';
    public const RATE_QUOTE_DETAILS = 'rateQuoteDetails';
    public const PRODUCT = 'product';
    public const CHECKOUT_SHIPPING_PROMO_ACTIVE = 'notification_promocode/notification_promocode_group/active';
    public const CHECKOUT_SHIPPING_PROMO_METHOD_CODE = 'notification_promocode/notification_promocode_group/ground_shipping_method_code';
    public const CHECKOUT_SHIPPING_PROMO_SUB_TOTAL = 'notification_promocode/notification_promocode_group/sub_total_value';
    public const CHECKOUT_SHIPPING_PROMO_DISCOUNT_VALUE = 'notification_promocode/notification_promocode_group/promo_discount_value';
    public const CHECKOUT_SHIPPING_PROMO_MESSAGE_TEXT = 'notification_promocode/notification_promocode_group/notificaiton_promocode_editor';
    public const FORM_FIELD_NAME_ERROR_MESSAGE = 'fedex/marketplace_configuration/firstlastname_validation_error_message';
    public const TECH_TITANS_D_175160 = 'tech_titans_D_179675_ePro_getting_system_error_when_single_delivery';
    public const TECH_TITANS_D_175160_MESSAGE = 'checkout/checkout_messages/tech_titans_D_179675_ePro_message_dependent';

    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';
    private $isOutSourcedCheck;
    public const MILLIONAIRES_EPRO_MIGRATED_CUSTOM_DOC = 'b2184326_epro_migrated_custom_doc';

    protected Session $customerSession;

    protected StoreManagerInterface $storeManager;
    public AdapterFactory $_imageFactory;
    public Filesystem $_filesystem;
    public AttributeSetRepositoryInterface $_attributeSetRepositoryInterface;
    public StoreManagerInterface $_storeManager;
    public SessionFactory $_customerSessionFactory;
    public Session $_customerSession;

    /**
     * @var AttributeSetInterface[]
     */
    private array $loadedAttributeSets = [];

    public function __construct(
        Context                               $context,
        Session                               $customerSession,
        SessionFactory                        $customerSessionFactory,
        StoreManagerInterface                 $storeManager,
        AttributeSetRepositoryInterface       $attributeSetRepositoryInterface,
        Filesystem                      $filesystem,
        AdapterFactory                  $imageFactory,
        protected CustomerRepositoryInterface $customerRepository,
        protected Notification                $notificationHelper,
        protected CartFactory                 $cartFactory,
        protected TimezoneInterface           $timezone,
        protected DateTime                    $date,
        protected PunchoutData                $punchoutHelper,
        protected CompanyRepositoryInterface  $companyRepository,
        public LoggerInterface                $logger,
        protected Product                     $product,
        protected UrlInterface                $url,
        protected Curl                        $curl,
        protected ToggleConfig                $toggleConfig,
        protected JsonValidator               $jsonValidator,
        protected Json                        $json,
        protected SdeHelper                   $sdeHelper,
        protected SelfReg                     $selfregHelper,
        protected QuoteDataHelper             $quoteDataHelper,
        protected AuthHelper                                  $authHelper,
        private readonly PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig,
        private readonly ProductRepositoryInterface $productRepository,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private ConfigProvider $dyeSubConfigProvider
    ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->_customerSessionFactory = $customerSessionFactory;
        $this->_storeManager = $storeManager;
        $this->_attributeSetRepositoryInterface = $attributeSetRepositoryInterface;
        $this->_filesystem = $filesystem;
        $this->_imageFactory = $imageFactory;
    }

    /**
     * Get Current Customer
     *
     * @return $customer
     */
    public function getCustomer()
    {
        static $return = null;
        if ($return !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }

        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customer = $this->getOrCreateCustomerSession();
        } else {
            $customer = $this->_customerSessionFactory->create();
        }

        $id = $customer->getCustomer()->getId();
        if ($id) {
            $return = $this->customerRepository->getById($id);
            return $return;
        } else {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Cannot get current customer id. ');
            return false;
        }
    }

    /**
     * Get Assigned Company
     *
     * @return $company
     */
    public function getAssignedCompany($customer = null)
    {
        static $return = null;
        if ($return !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $return;
        }
        $companyId = $this->_customerSession->getCustomerCompany();

        if ($companyId) {
            $return = $this->companyRepository->get((int) $companyId);
            return $return;
        } else {
            $companyData = $this->_customerSession->getOndemandCompanyInfo();
            if ($companyData && isset($companyData['company_id'])) {
                $companyId = $companyData['company_id'];
                $return = $this->companyRepository->get((int) $companyId);
                return $return;
            }
        }

        return false;
    }

    /**
     * Get Is Delivery Options Enabled
     *
     * @return boolean
     */
    public function getIsDelivery()
    {
        if (!$this->isCommercialCustomer()) {
            return true;
        } else {
            $customer = $this->getCustomer();
            if (!empty($customer) && $customer->getId()) {
                $company = $this->getAssignedCompany($customer);
                return $company->getIsDelivery();
            } else {
                return false;
            }
        }
    }

    /**
     * Get Is Pickup Enabled
     *
     * @return boolean
     */
    public function getIsPickup()
    {
        if (!$this->isCommercialCustomer()) {
            return true;
        } else {
            $customer = $this->getCustomer();
            if (!empty($customer) && $customer->getId()) {
                $company = $this->getAssignedCompany($customer);
                return $company->getIsPickup();
            } else {
                return false;
            }
        }
    }

    /**
     * Get Back Url
     *
     * @return $url
     */
    public function getRedirectUrl()
    {
        if (!empty($this->_customerSession->getBackUrl())) {
            $returnUrl = $this->_customerSession->getBackUrl();
        } else {
            $routeUrl = $this->url->getUrl('success');
            $returnUrl = rtrim($routeUrl, "/");
        }
        return $returnUrl;
    }

    /**
     * Get Company Extenal Website Name Setting
     *
     * @return $sitename
     */
    public function getCompanySite()
    {
        if ($this->isCommercialCustomer()) {
            $company = $this->getAssignedCompany();
            if ($company->getSiteName() != '') {
                return $company->getSiteName();
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Get Is Pickup Enabled
     *
     * @param string|null $operationAllowed
     * @param string|null $quoteStatus
     *
     * @return boolean
     */
    public function sendNotification($operationAllowed = 'create', $quoteStatus = 'final')
    {
        $companyId = $this->_customerSession->getCustomerCompany();
        $quote = $this->cartFactory->create()->getQuote();
        $company = $this->companyRepository->get((int) $companyId);
        $timestamp = $this->timezone->date($this->date->gmtDate())->format('Y-m-d H:i:sP');
        $payload = uniqid() . '.' . $this->punchoutHelper->uniqidReal();
        $apiUrl = $this->_customerSession->getCommunicationUrl();
        //Fix for Defect D-68981
        $netAmount = $quote->getGrandTotal() - $quote->getCustomTaxAmount();
        $cxml = '<?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE cXML SYSTEM "http://xml.cXML.org/schemas/cXML/1.2.021/cXML.dtd">
        <cXML timestamp="' . $timestamp . '" xml:lang="en-US" payloadID="' . $payload .
            '@c0021757.prod.cloud.fedex.com"><Header><From><Credential domain="' .
            $this->punchoutHelper->getHeaderToDomain() . '">
        <Identity>' . $this->punchoutHelper->getHeaderToIdentity() .
            '</Identity></Credential></From><To><Credential domain="' . $company->getDomainName() . '"><Identity>' .
            $company->getNetworkId() . '</Identity></Credential></To><Sender><Credential domain="' .
            $this->punchoutHelper->getSenderCredential() . '"><Identity>' . $this->punchoutHelper->getSenderIdentity() .
            '</Identity><SharedSecret>' . $this->punchoutHelper->getSenderSecret() .
            '</SharedSecret></Credential><UserAgent>' . $this->punchoutHelper->getSenderUserAgent() .
            '</UserAgent></Sender></Header><Message deploymentMode="production"><PunchOutOrderMessage><BuyerCookie>' .
            $this->_customerSession->getCommunicationCookie() .
            '</BuyerCookie><PunchOutOrderMessageHeader operationAllowed="' .$operationAllowed.'"
        quoteStatus="' .$quoteStatus. '"><Total><Money currency="USD">' . $netAmount .
            '</Money></Total><SupplierOrderInfo orderID="' . $quote->getId() . '" /></PunchOutOrderMessageHeader>';

        $items = $quote->getAllItems();
        $i = 1;
        foreach ($items as $item) {
            if ($item->getProductType() === Product\Type::TYPE_BUNDLE) {
                continue;
            }
            $additionalOption = $item->getOptionByCode('info_buyRequest');
            $additionalOptions = $additionalOption->getValue();
            $productJson = (array) json_decode($additionalOptions)->external_prod[0];
            $prodItemName = array_key_exists('userProductName', $productJson) ?
                $productJson['userProductName'] : $item->getName();
            if (is_null($prodItemName)) {
                $prodItemName = $item->getName();
            }

            /**
             * Special charaters in product name were causing issue in quote creation on OpenText,
             * so added code to remove it.
             *
             * @Author: Atul Kumar 01 SEP 2021
             **/
            $prodItemName = preg_replace('/[^A-Za-z0-9\- ]/', '', $prodItemName);

            $cxml .= '<ItemIn quantity="'. $item->getQty() . '" lineNumber="' . $i . '">
            <ItemID><SupplierPartID>' . $item->getProductId() . '</SupplierPartID>
            <SupplierPartAuxiliaryID>' . $item->getId() . '</SupplierPartAuxiliaryID>
            </ItemID><ItemDetail><UnitPrice><Money currency="USD">' .
                number_format((float) ($item->getPrice() - ($item->getDiscount() / $item->getQty())), 2, '.', '') .
                '</Money></UnitPrice><Description xml:lang="en_US">' . $prodItemName .
                '</Description><UnitOfMeasure>EA</UnitOfMeasure><Classification domain="UNSPSC">82121503
            </Classification><Extrinsic name="ItemExtendedPrice"><Money currency="' .
                $this->_storeManager->getStore()->getCurrentCurrencyCode() . '">' .
                number_format((float) (($item->getQty() * $item->getPrice()) - $item->getDiscount()), 2, '.', '') .
                '</Money></Extrinsic></ItemDetail></ItemIn>';
            $i++;
        }
        $cxml .= '</PunchOutOrderMessage></Message></cXML>';
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Quote Creation cXml:');
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $cxml);
        return $this->notificationHelper->sendXmlNotification($cxml, $apiUrl);
    }

    /**
     * Get Taz Token And Token Type
     * Set taz token and token type to customer session (B-1163726)
     *
     * @return ?array
     */
    public function getApiToken(): ?array
    {
        if ($this->_customerSession->getOnBehalfOf()) {
            return null;
        }

        $accessToken = '';
        $tokenType = '';
        if (!$this->_customerSession->getApiAccessToken() && !$this->_customerSession->getApiAccessType()) {
            $tazToken = $this->punchoutHelper->getTazToken();
            if ($tazToken) {
                $this->_customerSession->setApiAccessToken($tazToken);
                $this->_customerSession->setApiAccessType('Bearer');
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error getting Taz Token.');
            }
        }
        if ($this->_customerSession->getApiAccessToken() && $this->_customerSession->getApiAccessType()) {
            $accessToken = $this->_customerSession->getApiAccessToken();
            $tokenType = $this->_customerSession->getApiAccessType();
        }
        return ['token' => $accessToken, 'type' => $tokenType];
    }

    /**
     * Get Gateway Token For API Communication
     * Generate token and set it to the customer session itself (B-1163726)
     *
     * @return $token
     */
    public function getGateToken()
    {
        return $this->punchoutHelper->getAuthGatewayToken();
    }

    /**
     * Get product attribute name
     *
     * @param  $attributeSetId
     * @return string
     */
    public function getProductAttributeName($attributeSetId)
    {
        $isPerformanceImprovementProductLoadToggle = $this->toggleConfig
            ->getToggleConfigValue('hawks_d_227849_performance_improvement_checkout_product_load');
        if ($isPerformanceImprovementProductLoadToggle) {
            if (!isset($this->loadedAttributeSets[$attributeSetId])) {
                try {
                    $this->loadedAttributeSets[$attributeSetId] = $this->_attributeSetRepositoryInterface->get($attributeSetId);
                } catch (NoSuchEntityException $e) {
                    $this->logger->error(
                        __METHOD__ . ':' . __LINE__ .
                        ' Attribute set not found for ID: ' . $attributeSetId . '. Error: ' . $e->getMessage()
                    );
                    return '';
                }
            }

            return $this->loadedAttributeSets[$attributeSetId]->getAttributeSetName();
        }
        $attributeSetRepository = $this->_attributeSetRepositoryInterface->get($attributeSetId);

        return $attributeSetRepository->getAttributeSetName();
    }

    /**
     * Checks if the customer is reatil or commercial customer
     */
    public function isCommercialCustomer()
    {
        static $result = null;
        if ($result !== null
            && $this->performanceImprovementPhaseTwoConfig->isActive()
        ) {
            return $result;
        }
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $customer = $this->getOrCreateCustomerSession();
        } else {
            $customer = $this->_customerSessionFactory->create();
        }
        $result = ($customer->getCustomer()->getId() && $this->getAssignedCompany());
        return $result;
    }

    /**
     * Get customer login information
     *
     * @return array
     */
    public function getFCLCustomerLoggedInInfo()
    {
        try {
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customer = $this->getOrCreateCustomerSession();
            } else {
                $customer = $this->_customerSessionFactory->create();
            }

            $loginCustomerDetail = [];

            /* B-1242868 */
            if (($this->authHelper->isLoggedIn())
                || (!$this->isCommercialCustomer() && $customer->getCustomer()->getId())
            ) {

                if ($customer->getCustomer()->getSecondaryEmail()) {
                    $emailValue = $customer->getCustomer()->getSecondaryEmail();
                } else {
                    $emailValue = $customer->getCustomer()->getEmail();
                }
                $loginCustomerDetail["first_name"] = $customer->getCustomer()->getFirstname();
                $loginCustomerDetail["last_name"] = $customer->getCustomer()->getLastname();
                $loginCustomerDetail["contact_number"] = $customer->getCustomer()->getContactNumber();
                $loginCustomerDetail["contact_ext"] = $customer->getCustomer()->getContactExt();
                $loginCustomerDetail["email_address"] = $emailValue;
            }

            return $loginCustomerDetail;
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .
                ' Not able to get customer login details. ' . $e->getMessage()
            );
        }
    }

    /**
     * Get product attribute value
     *
     * @param productId
     * @param productAttributeCode
     * @return productCustomAttributeValue
     * @throws NoSuchEntityException
     */
    public function getProductCustomAttributeValue($productId, $atttibuteCode)
    {
        if($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
            $productObj = $this->productRepository->getById($productId);
        }else{
            $productObj = $this->product->load($productId);
        }

        return $productObj->getData($atttibuteCode);
    }

    /**
     * Get IsOutSourced
     *
     * @return bool
     */
    public function isOurSourced()
    {
        $this->isOutSourcedCheck = false;
        try {
            $items = $this->cartFactory->create()->getQuote()->getAllItems();
            foreach ($items as $item) {
                $additionalOption = $item->getOptionByCode('info_buyRequest');
                $additionalOptions = $additionalOption->getValue();
                if ($this->jsonValidator->isValid($additionalOptions)) {
                    $additionalOptions = $this->json->unserialize($additionalOptions);
                    if (isset($additionalOptions['external_prod'][0])) {
                        $productJson = (array) $additionalOptions['external_prod'][0];
                        $this->setIsOutSourceCheck($productJson);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . $e->getMessage());
        }

        return $this->isOutSourcedCheck;
    }

    /**
     * @param array $productJson
     * @param bool  $isOutsourceFlag
     * Set Is Out Source Flag
     */
    public function setIsOutSourceCheck($productJson)
    {
        if (isset($productJson[self::FXO_PRODUCT])) {
            if ($this->jsonValidator->isValid($productJson[self::FXO_PRODUCT])) {
                $fxoProduct = (array) $this->json->unserialize($productJson[self::FXO_PRODUCT]);
                if (isset($fxoProduct['fxoProductInstance']                    ['productConfig'][self::PRODUCT][self::IS_OUTSOURCED])
                ) {
                    $isOutSourced =
                        $fxoProduct['fxoProductInstance']['productConfig'][self::PRODUCT][self::IS_OUTSOURCED];
                    if ($isOutSourced == 1) {
                        $this->isOutSourcedCheck = true;
                    }
                }
            }
        } elseif (isset($productJson[self::IS_OUTSOURCED]) && $productJson[self::IS_OUTSOURCED] == 1) {
            $this->isOutSourcedCheck = true;
        }
    }
    /**
     * Check if customer is Epro Customer or not
     * Only epro customer will have the company site name (B-1163715)
     *
     * @return bool
     */
    public function isEproCustomer()
    {
        if ($this->getCompanySite()
            || ($this->authHelper->getCompanyAuthenticationMethod() == AuthHelper::AUTH_PUNCH_OUT)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get Is Delivery Options Enabled
     *
     * @return array
     */
    public function getAllowedDeliveryOptions()
    {
        $allowStore = $this->isCommercialCustomer();
        if ($allowStore) {
            $customer = $this->getCustomer();
            if (!empty($customer) && $customer->getId()) {
                $company = $this->getAssignedCompany($customer);
                if ($company && $company->getAllowedDeliveryOptions() != '') {
                    $allowedShippingOption = $this->json->unserialize($company->getAllowedDeliveryOptions());
                    if (is_array($allowedShippingOption)) {
                        return array_flip($allowedShippingOption);
                    }
                }
            }
        }
        return [];
    }

    /* All special services params can be included in this method
     * 1. We need to include direct signature options for rate request in sde
     *
     * @return array
     */
    public function getRateRequestShipmentSpecialServices()
    {
        $specialServices = [];
        $directSignatureOptions = $this->getDirectSignatureOptionsParams();
        if (!empty($directSignatureOptions)) {
            $specialServices[] = $directSignatureOptions;
        }

        return $specialServices;
    }

    /**
     * Get Direct Signature Options Params for rate and delivery options Api
     * Direct Signature Options should be enabled for delivery methods other than Local Delivery
     *
     * @return array
     */
    public function getDirectSignatureOptionsParams()
    {
        $signatureOptions = [];
        if ($this->isSdeCustomer()) {
            $signatureOptions = [
                'specialServiceType' => 'SIGNATURE_OPTION',
                'specialServiceSubType' => 'DIRECT',
                'displayText' => 'Direct Signature Required',
                'description' => 'Direct Signature Required',
            ];
        }

        return $signatureOptions;
    }

    /**
     * Update price for line items in DB.
     *
     * B-1126844 | update cart items price
     *
     * @param array $items
     * @param array $arraySortedPickup
     */
    public function updateCartItemPrice($items, $arraySortedPickup)
    {
        $rateAPIKey = self::RATE_DETAILS;
        if (!$this->isEproCustomer()) {
            $rateAPIKey = self::RATE_QUOTE_DETAILS;
            $productLines = $this->getProductLinesDetails($arraySortedPickup[$rateAPIKey]);
        } else {
            $productLines = $arraySortedPickup[$rateAPIKey][0]['productLines'];
        }

        if (isset($productLines)) {
            foreach ($items as $item) {
                foreach ($productLines as $productLine) {
                    if ($item->getItemId() == $productLine['instanceId']) {
                        $price = $productLine['productRetailPrice'];
                        $price = str_replace('$', '', $price);
                        $price = str_replace(',', '', $price);
                        $fedexDiscount = $productLine['productDiscountAmount'];
                        $fedexDiscount = ltrim($fedexDiscount, "($");
                        $fedexDiscount = rtrim($fedexDiscount, ")");
                        $fedexDiscount = str_replace(',', '', $fedexDiscount);

                        $unitQty = $productLine['unitQuantity'];
                        $unitPrice = $price / $unitQty;
                        // Setting the value of Unit Price for EPRO
                        $unitPrice = $this->setUnitPriceForEPRO($unitPrice);

                        if (!$item->getMiraklOfferId()) {
                            $item->setBaseRowTotal($unitPrice * $unitQty);
                            $item->setRowTotal($unitPrice * $unitQty);
                            $item->setDiscount($fedexDiscount);
                            $item->setBaseRowTotalInclTax($unitPrice * $unitQty);
                            $item->setRowTotalInclTax($unitPrice * $unitQty);
                            $item->setPrice($unitPrice);
                            $item->setPriceInclTax($unitPrice);
                            $item->setBasePrice($unitPrice);
                            $item->setBasePriceInclTax($unitPrice);
                            $item->setCustomPrice($unitPrice);
                            $item->setOriginalCustomPrice($unitPrice);
                            $item->setIsSuperMode(true);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get product lines details
     *
     * @param  array $arraySortedPickup
     * @return array
     */
    public function getProductLinesDetails($arraySortedPickup)
    {
        foreach ($arraySortedPickup as $productLineArray) {
            if (isset($productLineArray['productLines'])) {
                return $productLineArray['productLines'];
            }
        }
        return [];
    }

    /**
     * Set Unit price for EPRO
     */
    public function setUnitPriceForEPRO($unitPrice)
    {
        if ($this->isEproCustomer()) {
            $unitPrice = round($unitPrice, 2);
        }

        return $unitPrice;
    }

    /**
     * Update Quote discount in DB.
     *
     * B-1126844 | update cart items price
     *
     * @param Object $quote
     * @param array  $productRates
     * @param String $couponCode
     */
    public function updateQuotePrice($quote, $productRates, $couponCode)
    {
        $rateAPIKey = self::RATE_DETAILS;
        if (!$this->isEproCustomer()) {
            $rateAPIKey = self::RATE_QUOTE_DETAILS;
        }
        if (isset($productRates[$rateAPIKey][0]['totalAmount'])) {
            $netAmount = $productRates[$rateAPIKey][0]['totalAmount'];
            $netAmount = str_replace(['$', ',', '(', ')'], '', $netAmount);

            $taxAmount = $productRates[$rateAPIKey][0]['taxAmount'];
            $taxAmount = str_replace(['$', ',', '(', ')'], '', $taxAmount);

            $totalDiscountAmount = $productRates[$rateAPIKey][0]['totalDiscountAmount'];
            $totalDiscountAmount = str_replace(['$', ',', '(', ')'], '', $totalDiscountAmount);
            $grossAmount = $productRates[$rateAPIKey][0]['grossAmount'];
            $grossAmount = str_replace(['$', ',', '(', ')'], '', $grossAmount);

            $quote->setCustomTaxAmount($taxAmount);
            $quote->setDiscount($totalDiscountAmount);
            $quote->setCouponCode($couponCode);
            $quote->setSubtotal($grossAmount);
            $quote->setBaseSubtotal($grossAmount);
            $quote->setSubtotalWithDiscount($grossAmount);
            $quote->setBaseSubtotalWithDiscount($grossAmount);
            $quote->setGrandTotal($netAmount);
            $quote->setBaseGrandTotal($netAmount);
            $quote->save();
        }
    }

    /**
     * Check if customer is SDE
     */
    public function isSdeCustomer()
    {
        if ($this->sdeHelper->getIsSdeStore()) {
            return true;
        }

        return false;
    }

    /**
     * Resize image with the width and height
     */
    public function resize($image, $width = null, $height = null)
    {
        $absolutePath = $this->_filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath('/').$image;
        if (!file_exists($absolutePath)) {
            return false;
        }
        $imageResized = $this->_filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath('resized/'.$width.'/').$image;
        if (!file_exists($imageResized)) {
            // Only resize image if not already exists.
            //create image factory...
            $imageResize = $this->_imageFactory->create();
            $imageResize->open($absolutePath);
            $imageResize->keepTransparency(true);
            $imageResize->keepFrame(true);
            $imageResize->keepAspectRatio(true);
            $imageResize->backgroundColor([255,255,255]);
            $imageResize->resize($width, $height);
            //destination folder
            $destination = $imageResized;
            //save image
            $imageResize->save($destination);
        }
        $resizedURL = $this->_storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA).'resized/'.$width.'/'.$image;
        return $resizedURL;
    }

    /**
     * get Company Logo
     * B-1473176
     */
    public function getCompanyLogo()
    {
        $companyObj = $this->getAssignedCompany();
        $companyLogo = null;
        if ($companyObj && $companyObj->getCompanyLogo()) {
            $companyLogoInfo = $this->json->unserialize($companyObj->getCompanyLogo());
            if (is_array($companyLogoInfo) && !empty($companyLogoInfo['url'])) {
                $mediaUrl = $this->_storeManager->getStore()
                    ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $baseUrl = str_replace('/media/', '', $mediaUrl);
                $companyLogo = $baseUrl . $companyLogoInfo['url'];
                $companyLogoMainImage = str_replace('/media/', '', $companyLogoInfo['url']);
                $companyLogo = $this->resize($companyLogoMainImage, 142, 40);

            }
        }
        return $companyLogo;
    }

    /**
     * Get Company Level Logo
     *
     * @return string|null
     */
    public function getCompanyLevelLogo()
    {
        $companyLogo = null;
        $companyObject = $this->getAssignedCompany();
        if ($companyObject && $companyObject->getCompanyLogo()) {
            $companyLogoInfo = $this->json->unserialize(
                $companyObject->getCompanyLogo()
            );

            if (is_array($companyLogoInfo) && !empty($companyLogoInfo['url'])) {
                $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(
                    UrlInterface::URL_TYPE_MEDIA
                );
                $baseUrl = str_replace('/media/', '', $mediaUrl);
                $companyLogo = $baseUrl . $companyLogoInfo['url'];
            }
        }

        return $companyLogo;
    }

    /**
     * get Media URL
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function isPromiseTimeWarningToggleEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'environment_toggle_configuration/environment_toggle/sgc_promise_time_warning_modal',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * B-1572402: POD  2.0 Pickup Date/Time Format - Checkout Page
     *
     * @param  int|string $dateTimeValue
     * @return string
     */
    public function updateDateTimeFormat($dateTimeValue)
    {
        if (empty($dateTimeValue)) {
            return '';
        }
        try {
            $date = new \DateTime($dateTimeValue);
        } catch (\Exception $e) {
            return '';
        }
        if (!$this->isPromiseTimeWarningToggleEnabled()) {
            return sprintf('%s, %s', $date->format('l'), strtolower($date->format('F j, g:ia')));
        }
        $today = new \DateTime();
        $tomorrow = (clone $today)->modify('+1 day');
        $dateOnly = $date->format('Y-m-d');
        $prefix = match ($dateOnly) {
            $today->format('Y-m-d') => 'Today',
            $tomorrow->format('Y-m-d') => 'Tomorrow',
            default => $date->format('l')
        };

        $timeString = strtolower($date->format('g:ia')) === '11:59pm' ? 'End of Day' : strtolower($date->format('g:ia'));

        return sprintf('%s, %s by %s', $prefix, $date->format('F j'), $timeString);
    }


    /**
     * Is Non-Combinable Ground Promo Messaging Active
     *
     * @return bool
     */
    public function isGroundShippingPromoMessagingActive(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::CHECKOUT_SHIPPING_PROMO_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Shipping Promo Message Text
     *
     * @return string
     */
    public function getPromoMessageText()
    {
        return $this->scopeConfig->getValue(self::CHECKOUT_SHIPPING_PROMO_MESSAGE_TEXT, ScopeInterface::SCOPE_STORE);
    }

    public function getConfiguratorUrl()
    {
        return $this->scopeConfig->getValue(
            'fedex/general/configurator_url'
        );
    }

    /**
     * Get Configuration value
     *
     * @param  string $path
     * @return string
     */
    public function getConfigurationValue($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get toggle value
     *
     * @param  string $path
     * @return string
     */
    public function getToggleConfigurationValue($path)
    {
        return $this->toggleConfig->getToggleConfigValue($path);
    }

    /**
     * Get OnDemand Company Info
     */
    public function getOnDemandCompInfo()
    {
        $companyInfo = [];
        $compObject = $this->getAssignedCompany();
        if (is_object($compObject)) {
            $companyInfo['company_id'] = $compObject->getId();
            $companyInfo['login_method'] = $compObject->getStorefrontLoginMethodOption();
            $companyInfo['is_sensitive_data_enabled'] = $compObject->getIsSensitiveDataEnabled();
            $companyInfo['logoutUrl'] = $compObject->getSsoLogoutUrl();
            $companyInfo['company_url_extension'] = $compObject->getCompanyUrlExtention() ?? '';
            $companyInfo['adobe_analytics'] = (bool)$compObject->getAdobeAnalytics() ?? '';
        }

        return $companyInfo;
    }

    /**
     * Check if is Self Reg Customer Admin User
     *
     * @return boolean
     */
    public function isSelfRegCustomerAdminUser()
    {
        $companyInfo = $this->getOnDemandCompInfo();
        if (!empty($companyInfo)) {
            $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
            $companyId = $companyInfo['company_id'];
            if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                $customer = $this->getOrCreateCustomerSession();
            } else {
                $customer = $this->_customerSessionFactory->create();
            }
            $profileEmail = $customer->getCustomer()->getEmail();
            $loginMethodName = $companyInfo['login_method'];
            $isSensitiveDataEnabled = $companyInfo['is_sensitive_data_enabled'];
            $isCompanyAdmin = $this->selfregHelper->checkCustomerIsCompanyAdmin($websiteId, $profileEmail, $companyId);
            if (!$isSensitiveDataEnabled && $isCompanyAdmin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if is Company Admin User
     *
     * @return boolean
     */
    public function isCompanyAdminUser()
    {
        $companyInfo = $this->getOnDemandCompInfo();
        if (!empty($companyInfo)) {
            $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
            $companyId = $companyInfo['company_id'];
            $profileEmail = $this->_customerSession->getCustomer()
                ? $this->_customerSession->getCustomer()->getEmail() : false;
            $isCompanyAdmin = $this->selfregHelper->checkCustomerIsCompanyAdmin($websiteId, $profileEmail, $companyId);
            if  ($isCompanyAdmin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if Customer is Epro Admin
     *
     * @return boolean
     */
    public function isCustomerEproAdminUser()
    {
        $companyInfo = $this->getOnDemandCompInfo();
        if (!empty($companyInfo)) {
            $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
            $companyId = $companyInfo['company_id'];
            $profileEmail = $this->_customerSession->getCustomer()
                ? $this->_customerSession->getCustomer()->getEmail() : false;
            $loginMethodName = $companyInfo['login_method'];
            $isCompanyAdmin = $this->selfregHelper->checkCustomerIsCompanyAdmin($websiteId, $profileEmail, $companyId);

            if ($isCompanyAdmin && $loginMethodName == 'commercial_store_epro') {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Check if is Customer Company Admin User
     *
     * @return boolean
     */
    public function isCustomerAdminUser()
    {
        $companyInfo = $this->getOnDemandCompInfo();
        if (!empty($companyInfo)) {
            $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
            $companyId = $companyInfo['company_id'];
            $profileEmail = $this->_customerSession->getCustomer()
                ? $this->_customerSession->getCustomer()->getEmail() : false;
            $loginMethodName = $companyInfo['login_method'];
            $isCompanyAdmin = $this->selfregHelper->checkCustomerIsCompanyAdmin($websiteId, $profileEmail, $companyId);
            if ($isCompanyAdmin) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getFormFieldNameErrorMessage(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::FORM_FIELD_NAME_ERROR_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     *  check permission of current user
     *
     * @param  string
     * @return boolean
     */
    public function checkPermission($permission)
    {
        $permissionsData=$this->_customerSession->getUserPermissionData();
        if(!empty($permissionsData)) {
            foreach($permissionsData as $key=>$value){
                if(str_contains($key, $permission)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get Company Name
     */
    public function getCompanyName()
    {
        if ($this->isCommercialCustomer()) {
            $company = $this->getAssignedCompany();
            if ($company->getCompanyName() != '') {
                return $company->getCompanyName();
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Check TigerTeam D-174931 Business Review: Unable to proceed in checkout with In-Branch Documents for a mixed cart order toggle
     *
     * @return boolean
     */
    public function isD175160ToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_D_175160);
    }

    /**
     * Check TigerTeam D-174931 Business Review: Unable to proceed in checkout with In-Branch Documents for a mixed cart order toggle
     *
     * @return string
     */
    public function getMessageError(): string
    {
        return $this->scopeConfig->getValue(self::TECH_TITANS_D_175160_MESSAGE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Toggle to enable or disable, icons on left navigation
     *
     * @return bool
     */
    public function toggleEnableIcons(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue("tech_titans_D_177877");
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     *
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     *
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if($this->_customerSession->isLoggedIn()) {
            if($this->_customerSession->getCustomer()->getId() != null) {
                return $this->_customerSession;
            }
            $this->_customerSession = $this->_customerSessionFactory->create();
            return $this->_customerSession;
        }
        return $this->_customerSessionFactory->create();
    }

    /**
     * Get Toggle Value epro Custom doc for migrated Document Toggle
     *
     * @return boolean
     */
    public function getEproMigratedCustomDocToggle()
    {
        return $this->toggleConfig->getToggleConfigValue(static::MILLIONAIRES_EPRO_MIGRATED_CUSTOM_DOC);
    }

    /**
     * Get Toggle Value Auto Cart Transmission to ERP Enabled Toggle
     *
     * @return boolean
     */
    public function isAutoCartTransmissiontoERPToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_auto_cart_transmission_to_erp');
    }
    /**
     * @return bool
     */
    public function isDyeSubEnabled(): bool
    {
        return (bool) $this->dyeSubConfigProvider->isDyeSubEnabled();
    }

}
