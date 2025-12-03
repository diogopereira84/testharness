<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\UploadToQuote\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\CIDPSG\Helper\Email;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Company\Api\CompanyManagementInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Punchout\Helper\Data;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\UploadToQuote\Block\QuoteDetails;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Backend\Model\Session\AdminConfig;
class QuoteEmailHelper extends AbstractHelper
{
    public const FEDEX_OFFICE_PPRINT_ON_DEMAND = 'FedEx Office® Print On Demand';
    public const FEDEX_OFFICE_FUSE_SUBJECT = 'FedEx Office®';
    public const TIGER_E_469378_U2Q_PICKUP = 'tiger_team_E_469378_u2q_pickup';
    public const MAZEGEEKS_D239974 = 'mazegeeks_d239974_store_phone_number_and_email_address_missing';
    public const MAZEGEEKS_D242332_UPDATE_LINK = 'mazegeeks_d242332_update_for_quote_confirmation_link';
    public const DEFAULT_STORE_ID = 1;
    public $status;
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param AdminConfigHelper $adminConfigHelper
     * @param Email $email
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $quoteRepository
     * @param TimezoneInterface $timezoneInterface
     * @param ProductInfoHandler $productInfoHandler
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param ShopManagement $shopManagement
     * @param CompanyManagementInterface $companyRepository
     * @param FuseBidViewModel $fuseBidViewModel
     * @param Curl $curl
     * @param Data $gateTokenHelper
     * @param HeaderData $headerData
     * @param QuoteDetails $quoteDetails
     * @param SelfReg $selfReg
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        protected LoggerInterface $logger,
        protected AdminConfigHelper $adminConfigHelper,
        protected Email $email,
        protected StoreManagerInterface $storeManager,
        protected CartRepositoryInterface $quoteRepository,
        private TimezoneInterface $timezoneInterface,
        protected ProductInfoHandler $productInfoHandler,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected ShopManagement $shopManagement,
        protected CompanyManagementInterface $companyRepository,
        protected FuseBidViewModel $fuseBidViewModel,
        protected Curl $curl,
        protected Data $gateTokenHelper,
        protected HeaderData $headerData,
        private QuoteDetails $quoteDetails,
        private SelfReg $selfReg,
        private ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }
    /**
     * Send quote email
     *
     * @param array $quoteData
     * @return mixed
     */
    public function sendQuoteGenericEmail($quoteData)
    {
        $quote = $this->quoteRepository->get($quoteData['quote_id']);
        
        $isBid = $quote->getIsBid() ?? false;
        $nbc = $quote->getNbcRequired() ?? false;
        if ($this->checkEmailEnable($quoteData['status'],$isBid,$nbc)) {
            $genericEmailData = $this->prepareGenericEmailRequest($quoteData);
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . ' Email data for quote Id:' .
                $quoteData['quote_id'] . " Quote status: " . $quoteData['status'] .
                    " genericEMailData: " . $genericEmailData
            );
            return $this->email->callGenericEmailApi($genericEmailData, true);
        }
        return false;
    }
    /**
     * Check email enable
     *
     * @param string $status
     * @return mixed
     */
    public function checkEmailEnable($status,$isBid = false, $nbc = false)
    {
        $storeId = $this->storeManager->getStore()->getId();
        switch ($status) {
            case AdminConfigHelper::DECLINED:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_decline_customer_email_enable', $storeId);
            case AdminConfigHelper::CHANGE_REQUEST:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_change_request_email_enable', $storeId);
            case AdminConfigHelper::READY_FOR_REVIEW:
                if($this->adminConfigHelper->isToggleB2564807Enabled()){
                    if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $isBid) {
                        return $this->adminConfigHelper
                            ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_email_enable', $storeId);
                    } else {
                        return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('quote_ready_for_review_email_enable', $storeId);
                    }
                }else{
                    if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $isBid) {
                        if ($nbc) {
                            return $this->adminConfigHelper
                            ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_nbc_engagement_email_enable', $storeId);
                        } else {
                            return $this->adminConfigHelper
                            ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_email_enable', $storeId);
                        }
                    } else {
                        return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('quote_ready_for_review_email_enable', $storeId);
                    }
                }
            case AdminConfigHelper::NBC_SUPPORT:
                if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $isBid) {
                    return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_nbc_engagement_email_enable', $storeId);
                }
            case AdminConfigHelper::NBC_PRICED:
                if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $isBid) {
                    return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('fuse_quote_nbc_priced_email_enable', $storeId);
                }
            case AdminConfigHelper::CONFIRMED:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_confirmation_email_enable', $storeId);
            case AdminConfigHelper::EXPIRED:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_expired_email_enable', $storeId);
            case AdminConfigHelper::EXPIRATION:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_expiration_email_enable', $storeId);
            case AdminConfigHelper::DECLINED_BY_TEAM:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_decline_by_team_email_enable', $storeId);
            default:
                return false;
        }
    }
    /**
     * Check if BCC toggle enabled
     *
     * @return bool
     */
    private function isQuoteBccToggleEnabled(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(
            'environment_toggle_configuration/environment_toggle/sales_rep_ctc_bcc_quote_confirmation',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Prepare generic email request
     *
     * @param array $quoteData
     * @return mixed
     */
    public function prepareGenericEmailRequest($quoteData)
    {
        $quote = $this->quoteRepository->get($quoteData['quote_id']);
        $items = $this->formatQuoteItems($quote, $quoteData['status']);
        $emailQuoteData = $this->formatQuoteData($quote);
        $emailData = $this->buildEmailData($emailQuoteData, $items);
        $fromEmail = 'no-reply@fedex.com';
        $toEmail = $quote->getCustomerEmail();
        $storeId = $this->storeManager->getStore()->getId();
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $isBid = $quote->getIsBid() ?? false;
            $nbc = $quote->getNbcRequired() ?? false;
            $templateId = $this->getTemplateId($quoteData['status'], $isBid, $nbc);
            $subject = $this->getQuoteEmailSubject($quoteData['status'], $isBid, $nbc).' #'.$quoteData['quote_id'];
            if($quoteData['status'] == AdminConfigHelper::NBC_PRICED){
                if(isset($emailData['store_location_email'])){
                    $toEmail = $emailData['store_location_email'];
                }
                $subject = $this->getQuoteEmailSubject($quoteData['status'], $isBid, $nbc).' #'.$quoteData['quote_id'];
            }
        } else {
            $templateId = $this->getTemplateId($quoteData['status']);
            $subject = $this->getQuoteEmailSubject($quoteData['status']).' #'.$quoteData['quote_id'];
        }

        $bccEmails = [];
        if ($this->isQuoteBccToggleEnabled()) {
            if ($quoteData['status'] == AdminConfigHelper::CONFIRMED) {
                $company = $this->companyRepository->getByCustomerId($quote->getCustomerId());
                if ($company && $company->getData('bcc_comma_seperated_email')) {
                    $bccEmails = array_map('trim', explode(',', $company->getData('bcc_comma_seperated_email')));
                }
            }
        }
        $emailTemplateContent = $this->email->loadEmailTemplate($templateId, $storeId, $emailData);
        return json_encode($this->buildEmailPayload($emailTemplateContent, $subject, $toEmail, $fromEmail, $bccEmails));
    }
    /**
     * Get html of email template
     *
     * @param array $quoteData
     * @return mixed
     */
    public function getEmailTemplate($quoteData)
    {
        $quote = $this->quoteRepository->get($quoteData['quote_id']);
        $items = $this->formatQuoteItems($quote, $quoteData['status']);
        $emailQuoteData = $this->formatQuoteData($quote);
        $emailData = $this->buildEmailData($emailQuoteData, $items);
        $storeId = $this->storeManager->getStore()->getId();
        $isBid = $quoteData['is_bid'] ?? false;
        $nbc = $quoteData['nbc'] ?? false;
        $templateId = $this->getTemplateId($quoteData['status'], $isBid, $nbc);
        return $this->email->loadEmailTemplate($templateId, $storeId, $emailData);
    }
    /**
     * Get quote email subject
     *
     * @param string $status
     * @param bool $isBid
     * @param bool $nbc
     * @return string
     */
    public function getQuoteEmailSubject($status, $isBid = false, $nbc = false)
    {
        $fuseBiddingToggle = $this->fuseBidViewModel->isFuseBidToggleEnabled();
        switch ($status) {
            case AdminConfigHelper::DECLINED:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — Quote Declined';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — Quote Declined';
                }
            case AdminConfigHelper::CHANGE_REQUEST:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — Quote Change Request Confirmation';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — Quote Change Request Confirmation';
                }
            case AdminConfigHelper::READY_FOR_REVIEW:
                if ($this->adminConfigHelper->isToggleB2564807Enabled()){
                    if ($fuseBiddingToggle && $isBid) {
                        return self::FEDEX_OFFICE_FUSE_SUBJECT.' — Quote Ready For Your Review';
                    } else {
                        return self::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — Quote Ready For Your Review';
                    }
                }else{
                    if ($fuseBiddingToggle && $isBid) {
                        if ($nbc) {
                            return self::FEDEX_OFFICE_FUSE_SUBJECT.' — NBC Working On Quote';
                        } else {
                            return self::FEDEX_OFFICE_FUSE_SUBJECT.' — Quote Ready For Your Review';
                        }
                    } else {
                        return self::FEDEX_OFFICE_PPRINT_ON_DEMAND.' — Quote Ready For Your Review';
                    }
                }
            case AdminConfigHelper::CONFIRMED:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — Quote Confirmation';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — Quote Confirmation';
                }
            case AdminConfigHelper::EXPIRED:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — Quote Expired';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — Quote Expired';
                }
            case AdminConfigHelper::EXPIRATION:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — Quote Expiring Soon';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — Quote Expiring Soon';
                }
            case AdminConfigHelper::DECLINED_BY_TEAM:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — Quote Unsupported';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — Quote Unsupported';
                }
            case AdminConfigHelper::NBC_SUPPORT:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — NBC Working On Quote';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — NBC Working On Quote';
                }
            case AdminConfigHelper::NBC_PRICED:
                if ($fuseBiddingToggle && $isBid) {
                    return self::FEDEX_OFFICE_FUSE_SUBJECT . ' — NBC Supported - Quote is Ready';
                } else {
                    return self::FEDEX_OFFICE_PPRINT_ON_DEMAND . ' — NBC Supported - Quote is Ready';
                }
        }
    }
    /**
     * Format Quote Items
     *
     * @param object $quote
     * @param string $status
     * @return array
     */
    private function formatQuoteItems($quote, $status)
    {
        $quoteItems=$quote->getAllVisibleItems();
        $formattedItems = [];
        $thirdPartySellers = [];
        $firstPartyItemsCounter = 0;
        $thirdPartyItemsCounter = 0;
        /** @var Item $item */
        foreach ($quoteItems as $item) {
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
            $infoBuyRequestValue = $infoBuyRequest->getValue();
            $productJson = $this->productInfoHandler->getItemExternalProd($item);
            $userProductName = $productJson['userProductName'] ?? '';
            $productJson = $this->productInfoHandler->getItemExternalProd($item);
            $specialInstruction = $this->uploadToQuoteViewModel->isProductLineItems($productJson, true);
            $thirdPartySellerName = '';
            if (!empty($item->getMiraklShopId())) {
                $sellerShopProduct = $this->shopManagement->getShopByProduct($item->getProduct());
                $thirdPartySellerName = $sellerShopProduct?->getSellerAltName();
                $thirdPartyItemsCounter++;
                if (!in_array($thirdPartySellerName, $thirdPartySellers)) {
                    $thirdPartySellers[$thirdPartySellerName] = 1;
                } else {
                    $thirdPartySellers[$thirdPartySellerName] += 1;
                }
            } else {
                $firstPartyItemsCounter++;
            }
            $isPriceable = $status == 'declined_by_team'
            ? $this->adminConfigHelper->isItemPriceable($infoBuyRequestValue)
            : $this->uploadToQuoteViewModel->isItemPriceable($infoBuyRequestValue);
            $price = $isPriceable
            ? $this->adminConfigHelper->convertPrice($item->getBaseRowTotal(), true)
            : $this->uploadToQuoteViewModel->getPriceDash();
            $childrenData = [];
            if ($item->getProductType() === Type::TYPE_BUNDLE && $item->getChildren()) {
                $userProductName = $item->getName();
                $childrenData = $this->getChildrenData($item);
            }
            $formattedItems[] = [
                'item' => $userProductName,
                'qty' => $item->getQty(),
                'price' => $price,
                'AdditionalPrintInstruction' => $specialInstruction,
                'itemName' =>  $item->getName(),
                'thirdPartySellerName' =>  $thirdPartySellerName,
                'miraklShopId' => $item->getMiraklShopId() ?? '',
                'is_parent' => $item->getChildren() ? true : false,
                'childrenData' => $childrenData,
            ];
        }
        $quotePriceable = $status == 'declined_by_team'
        ? !$this->adminConfigHelper->checkoutQuotePriceisDashable($quote)
        : $this->uploadToQuoteViewModel->isQuotePriceable($quote);
        if ($quotePriceable) {
            $subtotalValue = $quote->getSubtotal();
            $discount = $quote->getDiscount();
            $discountEmailValue = $this->adminConfigHelper->convertPrice($discount);
            $total = $this->adminConfigHelper->convertPrice($subtotalValue - $discount);
            $subtotal = $this->adminConfigHelper->convertPrice($subtotalValue);
        } else {
            $discountEmailValue = $this->uploadToQuoteViewModel->getPriceDash();
            $subtotal = $this->uploadToQuoteViewModel->getPriceDash();
            $total = $this->uploadToQuoteViewModel->getPriceDash();
        }
        $formattedItems['count'] = $quote->getItemsCount();
        $formattedItems['firstPartyItemsCounter'] = $firstPartyItemsCounter;
        $formattedItems['thirdPartyItemsCounter'] = $thirdPartyItemsCounter;
        $formattedItems['thirdPartySellers'] = $thirdPartySellers;
        $formattedItems['subTotal'] = $subtotal;
        $formattedItems['total'] = $total;
        $formattedItems['totalDiscount'] = $discountEmailValue;
        return $formattedItems;
    }
    /**
     * Get children data
     *
     * @param Item $item
     * @return array
     */
    protected function getChildrenData($item)
    {
        $childrenData = [];
        foreach ($item->getChildren() as $child) {
            $childProductJson = $this->productInfoHandler->getItemExternalProd($child);
            $childPrice = $this->adminConfigHelper->convertPrice($child->getBaseRowTotal());
            $childrenData[] = [
                'item' => $childProductJson['userProductName'] ?? '',
                'qty' => $child->getQty(),
                'price' => $childPrice,
                'itemName' =>  $child->getName(),
                'miraklShopId' => $child->getMiraklShopId() ?? '',
            ];
        }
        return $childrenData;
    }
     /**
      * Format Quote Data
      *
      * @param object $quote
      * @return array
      */
    private function formatQuoteData($quote)
    {
        $customer = $quote->getCustomer();
        $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        $isSelfRegCustomer = $companyAttributes && $this->selfReg->checkSelfRegEnable($companyAttributes->getCompanyId());
        $isRetailCustomer = (!$companyAttributes || !$companyAttributes->getCompanyId())
            && $customer->getStoreId() == self::DEFAULT_STORE_ID;
        $isEproCustomer = (bool)($this->isEproCustomer() ?: $quote->getIsEproQuote());

        if (($this->fuseBidViewModel->isFuseBidToggleEnabled() && $quote->getIsBid())
            || ($this->isTigerE469378ToggleEnabled() && $isRetailCustomer)) {
            $companyExtensionUrl = 'default';
        } else {
            $companyExtensionUrl = 'ondemand/' . $this->getCompanyExtensionUrl($quote->getCustomerId());
        }

        if($this->adminConfigHelper->toggleUploadToQuoteSubmitDate()) {
            $createdAt = $this->adminConfigHelper->getSubmitDate($quote->getId());
        } else {
            $createdAt = $quote->getCreatedAt();
        }
        if ($this->adminConfigHelper->isToggleD226511Enabled()) {
            $quoteCreationDate = $this->timezoneInterface->date($createdAt)->format('M d, Y \a\t h:i A');
        } else {
            $quoteCreationDate = $this->formatDate($createdAt);
        }
        $quoteCreatedDate= $this->adminConfigHelper->getFormattedDate($createdAt, 'F j, Y');
        $quoteExpiryDate = $this->adminConfigHelper->getExpiryDate($quote->getId(), 'F j, Y');
        $quoteExpiryDateRaw = $this->adminConfigHelper->getExpiryDate($quote->getId(), 'm/d/Y');
        $currentDateTime =  new \DateTime();
        $expiryDateTime = new \DateTime($quoteExpiryDate);
        $interval = $currentDateTime->diff($expiryDateTime);
        $daysUntilExpiration = $interval->format('%a');
        $storeLocationCode = $quote->getQuoteMgntLocationCode();
        $storeLocationDetail = $this->getStoreLocationDetail($quote->getQuoteMgntLocationCode());
        $storeLocationAddressLine1 = $storeLocationDetail['address']['address1'];
        $storeLocationAddressLine2 = $storeLocationDetail['address']['address2'];
        $storeLocationCityStatePincode = $storeLocationDetail['address']['city'].", ".$storeLocationDetail['address']['stateOrProvinceCode'].", ".$storeLocationDetail['address']['postalCode'];
        $storeLocationPhone = $storeLocationDetail['phone'];
        $storeLocationEmail = $storeLocationDetail['email'];
        $isBidCheckoutEnable = false;
        if ($quote->getIsBid() && $this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $isBidCheckoutEnable = true;
        }
        $shouldShowQuoteLink = false;
        if ($this->adminConfigHelper->isToggleD235696Enabled()) {
            if (!$isEproCustomer) {
                if ($isSelfRegCustomer || ($this->isTigerE469378ToggleEnabled() && $isRetailCustomer)) {
                    $shouldShowQuoteLink = true;
                }
            }
        } else {
            if (!$isEproCustomer) {
                if ($isSelfRegCustomer || $isBidCheckoutEnable
                    || ($this->isTigerE469378ToggleEnabled() && $isRetailCustomer)) {
                    $shouldShowQuoteLink = true;
                }
            }
        }
        $showStoreInfo = $this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_D239974) && ($quote->getIsBid() == 0);
        $updateQuoteLinkToggle = $this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_D242332_UPDATE_LINK);
        if($updateQuoteLinkToggle){
            $quoteLink = 'ondemand/uploadtoquote/index/view/quote/' . $quote->getId();
        } else {
            $quoteLink = 'ondemand/' . $this->getCompanyExtensionUrl($quote->getCustomerId()). '/uploadtoquote/index/view/quote/' . $quote->getId();
        }
        return  [
                'user_name' => $quote->getCustomerFirstname(),
                'company_url_extension' => $companyExtensionUrl,
                'quote_id' => $quote->getId(),
                'action_days' => $daysUntilExpiration,
                'quote_placed_date' => $quoteCreationDate,
                'quote_creation_date' => $quoteCreatedDate,
                'quote_expiration_date' => $quoteExpiryDate,
                'quote_expiration_date_raw' => $quoteExpiryDateRaw,
                'is_epro_customer'=> $isEproCustomer,
                'store_location_address_line_1' => $storeLocationAddressLine1,
                'store_location_address_line_2' => $storeLocationAddressLine2,
                'store_location_address_city_state_pincode' => $storeLocationCityStatePincode,
                'store_location_phone' => $this->fuseBidViewModel->isFuseBidToggleEnabled() ? $this->getFormattedPhone($storeLocationPhone) : $storeLocationPhone,
                'store_location_email' => $storeLocationEmail,
                'is_bid' => $quote->getIsBid() ? "true" : "",
                'is_bid_checkout_enable' => $isBidCheckoutEnable,
                'should_show_quote_link' => $shouldShowQuoteLink,
                'location_code' => $storeLocationCode,
                'show_store_info' => $showStoreInfo,
                'quoteLink' => $quoteLink
            ];
    }
    /**
     * Build Email Payload
     *
     * @param string $templateContent
     * @param string $subject
     * @param string $toEmail
     * @param string $fromEmail
     * @return array
     */
    private function buildEmailPayload($templateContent, $subject, $toEmail, $fromEmail, $bccEmails = [])
    {
        $payload = [
            'templateData' => $templateContent,
            'templateSubject' => $subject,
            'toEmailId' => $toEmail,
            'fromEmailId' => $fromEmail,
            'retryCount' => 0,
            'errorSupportEmailId' => '',
            'attachment' => ''
        ];
        if ($this->isQuoteBccToggleEnabled() && !empty($bccEmails)) {
            $payload['bccEmailIds'] = $bccEmails;
        }

        return $payload;
    }
    /**
     * Format date
     *
     * @param string $dateString
     * @return mixed
     */
    private function formatDate($dateString)
    {
        return $this->timezoneInterface->date($dateString)->setTimezone(new \DateTimeZone('CST'))
        ->format('M d, Y \a\t h:i A \C\S\T');
    }
    /**
     * Get Template Id
     *
     * @param string $status
     * @param bool $isBid
     * @param bool $nbc
     * @return string
     */
    public function getTemplateId($status, $isBid = false, $nbc = false)
    {
        $storeId = $this->storeManager->getStore()->getId();
        switch ($status) {
            case AdminConfigHelper::DECLINED:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_decline_customer_email_template', $storeId);
            case AdminConfigHelper::CHANGE_REQUEST:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_change_request_email_template', $storeId);
            case AdminConfigHelper::READY_FOR_REVIEW:
                if ($this->adminConfigHelper->isToggleB2564807Enabled()){
                    if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $isBid) {
                        return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_email_template', $storeId);
                    } else {
                        return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('quote_ready_for_review_email_template', $storeId);
                    }
                }else{
                    if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $isBid) {
                        if ($nbc) {
                            return $this->adminConfigHelper
                            ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_nbc_engagement_email_template', $storeId);
                        } else {
                            return $this->adminConfigHelper
                            ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_email_template', $storeId);
                        }
                    } else {
                        return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('quote_ready_for_review_email_template', $storeId);
                    }
                }
            case AdminConfigHelper::NBC_SUPPORT:
                return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('fuse_quote_ready_for_review_nbc_engagement_email_template', $storeId);
            case AdminConfigHelper::NBC_PRICED:
                return $this->adminConfigHelper
                        ->getUploadToQuoteEmailConfigValue('fuse_quote_nbc_priced_email_template', $storeId);
            case AdminConfigHelper::CONFIRMED:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_confirmation_email_template', $storeId);
            case AdminConfigHelper::EXPIRED:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_expired_email_template', $storeId);
            case AdminConfigHelper::EXPIRATION:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_expiration_email_template', $storeId);
            case AdminConfigHelper::DECLINED_BY_TEAM:
                return $this->adminConfigHelper
                    ->getUploadToQuoteEmailConfigValue('quote_decline_by_team_email_template', $storeId);
        }
    }
    /**
     * Build email data
     *
     * @param array $quoteData
     * @param array $items
     * @return array
     */
    private function buildEmailData($quoteData, $items)
    {
        return [
            'user_name'             => $quoteData['user_name'],
            'quote_id'              => $quoteData['quote_id'],
            'company_url_extension' => $quoteData['company_url_extension'],
            'action_days'           => $quoteData['action_days'],
            'quote_placed_date'     => $quoteData['quote_placed_date'],
            'items'                 => $items,
            'quote_creation_date'   => $quoteData['quote_creation_date'],
            'quote_expiration_date' => $quoteData['quote_expiration_date'],
            'quote_expiration_date_raw' => $quoteData['quote_expiration_date_raw'],
            'is_epro_customer' => $quoteData['is_epro_customer'],
            'store_location_address_line_1' => $quoteData['store_location_address_line_1'],
            'store_location_address_line_2' => $quoteData['store_location_address_line_2'],
            'store_location_address_city_state_pincode' => $quoteData['store_location_address_city_state_pincode'],
            'store_location_phone' => $quoteData['store_location_phone'],
            'store_location_email' => $quoteData['store_location_email'],
            'is_bid' => $quoteData['is_bid'],
            'is_bid_checkout_enable' => $quoteData['is_bid_checkout_enable'],
            'should_show_quote_link' => $quoteData['should_show_quote_link'],
            'location_code' => $quoteData['location_code'],
            'show_store_info' => $quoteData['show_store_info'],
            'quoteLink' => $quoteData['quoteLink']
        ];
    }
    /**
     * Get company url extension
     *
     * @param int $customerId
     * @return string
     */
    private function getCompanyExtensionUrl($customerId)
    {
        return !empty($this->companyRepository->getByCustomerId($customerId)) ?
        $this->companyRepository->getByCustomerId($customerId)->getCompanyUrlExtention() : '';
    }
    /**
     * Check if customer is Epro Customer or not
     *
     * @return bool
     */
    public function isEproCustomer()
    {
        return $this->quoteDetails->isEproCustomer();
    }
    /** Get store location detail
     *
     * @param int $locationId
     * @return string
     */
    private function getStoreLocationDetail($locationId)
    {
        $setupURL = $this->scopeConfig->getValue("fedex/general/location_details_api_url") . '/' .
            $locationId.'?startDate='.date("m-d-Y").'&views=30';
        $gateWayToken = $this->gateTokenHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            $authHeaderVal . $gateWayToken
        ];
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => ''
            ]
        );
        $this->curl->get($setupURL);
        $output = $this->curl->getBody();
        $arrayData = json_decode($output, true);
        if (isset($arrayData['errors']) || !isset($arrayData['output'])) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Center API Request');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $locationId . date('m-d-y'));
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Center API Response');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $output);
        }
        $arrayName = json_decode($output, true);
        if (!empty($arrayName)) {
            if (!array_key_exists('errors', $arrayName)) {
                $arraySortedCenters = $arrayName['output']['location']??$arrayName['output'];
                return $arraySortedCenters;
            } else {
                return $arrayName;
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' No data being returned from center details api.');
            return "Error found no data";
        }
    }
    /** Get formatted phone number
     *
     * @param string $telephone
     * @return string
     */
    public function getFormattedPhone($telephone)
    {
        $telephone = substr_replace($telephone, '(', 0, 0);
        $telephone = substr_replace($telephone, ')', 4, 0);
        $telephone = substr_replace($telephone, ' ', 5, 0);
        $telephone = substr_replace($telephone, '-', 9, 0);
        return $telephone;
    }

    /**
     * @return bool|int|string|null
     */
    private function isTigerE469378ToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(self::TIGER_E_469378_U2Q_PICKUP);
    }
}
