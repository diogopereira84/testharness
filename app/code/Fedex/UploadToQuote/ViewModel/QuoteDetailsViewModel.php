<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\UploadToQuote\ViewModel;

use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Model\QuoteFactory;
use Fedex\FXOPricing\Model\FXORateQuote;
use Psr\Log\LoggerInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\MarketplacePunchout\Model\ProductInfo;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Fedex\MarketplaceCheckout\Helper\Data as Config;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Magento\Framework\View\Asset\Repository;
use Fedex\FXOCMConfigurator\Helper\Data;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\SSO\ViewModel\SsoConfiguration;

class QuoteDetailsViewModel implements ArgumentInterface
{
    private $expiredItems = [];

    /**
     * @param AdminConfigHelper $adminConfigHelper
     * @param CustomerSession $customerSession
     * @param QuoteFactory $quoteFactory
     * @param Http $request
     * @param UrlInterface $urlInterface
     * @param ResponseFactory $responseFactory
     * @param SerializerInterface $serializer
     * @param FXORateQuote $fxoRateQuote
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param Repository $assetRepository
     * @param FuseBidViewModel $fuseBidViewModel
     * @param CheckoutSession $checkoutSession
     * @param TimezoneInterface $timezone
     * @param ProductInfo $productInfo
     * @param ExpiredItem $expiredItem
     * @param Config $config
     * @param ConfigProvider $configProvider
     * @param ShopManagement $shopManagement
     * @param Data $fxocmhelper
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        protected AdminConfigHelper $adminConfigHelper,
        protected CustomerSession $customerSession,
        protected QuoteFactory $quoteFactory,
        protected Http $request,
        protected UrlInterface $urlInterface,
        protected ResponseFactory $responseFactory,
        protected SerializerInterface $serializer,
        protected FXORateQuote $fxoRateQuote,
        protected LoggerInterface $logger,
        protected StoreManagerInterface $storeManager,
        protected QuoteEmailHelper $quoteEmailHelper,
        protected GraphqlApiHelper $graphqlApiHelper,
        protected Repository $assetRepository,
        protected FuseBidViewModel $fuseBidViewModel,
        protected CheckoutSession $checkoutSession,
        private TimezoneInterface $timezone,
        private ProductInfo $productInfo,
        private ExpiredItem $expiredItem,
        private Config $config,
        private ConfigProvider $configProvider,
        private ShopManagement $shopManagement,
        protected Data   $fxocmhelper,
        private SsoConfiguration $ssoConfiguration
    )
    {
    }

    /**
     * Get quote details
     *
     * @param bool $isCreatedByLoggedInUser
     * @return object|void
     */
    public function getQuote($isCreatedByLoggedInUser = false)
    {
        $quote = $this->getQuoteById($this->request->getParam('quote'));
        if (!$quote->getId()
            || ($isCreatedByLoggedInUser && $quote->getCustomerId() != $this->customerSession->getId())) {
            $this->responseFactory->create()->setRedirect($this->urlInterface->getBaseUrl())->sendResponse();
        }

        return $quote;
    }

    /**
     * Filter all quotes visible items to get first party items
     * @param $quote
     * @return array
     */
    public function getFirstPartyItems($quote)
    {
        return array_filter($quote->getAllVisibleItems(),function ($item){
            return !$item->getData('mirakl_offer_id');
        });
    }

    /**
     * Create a new third party items array organized by seller
     * @param $quote
     * @return array
     */
    public function getThirdPartySellers($quote)
    {
        return $this->shopManagement->getThirdPartySellers($quote);
    }

    /**
     * Get quote details by id
     *
     * @param int $quoteId
     * @return object
     */
    public function getQuoteById($quoteId)
    {
        if ($this->isToggleD206707Enabled()) {
            $quoteCollection = $this->quoteFactory->create()->getCollection();
            $quoteCollection->addFieldToFilter('entity_id', $quoteId);
            return $quoteCollection->getFirstItem();
        } else {
            return $this->quoteFactory->create()->load($quoteId);
        }
    }

    /**
     *  Get quote details by id
     *
     * @return object
     */
    public function getQuoteBySessionQuoteId()
    {
        return $this->quoteFactory->create()->load($this->customerSession->getUploadToQuoteId());
    }

    /**
     * Get formatted phone number
     *
     * @param string $telephone
     * @return string
     */
    public function getFormattedPhone($telephone)
    {
        if ($telephone) {
            $telephone = substr_replace($telephone, '(', 0, 0);
            $telephone = substr_replace($telephone, ')', 4, 0);
            $telephone = substr_replace($telephone, ' ', 5, 0);
            $telephone = substr_replace($telephone, '-', 9, 0);
        }

        return $telephone;
    }

    /**
     * Get expiry date
     *
     * @param int $quoteId
     * @param string $format
     * @return string
     */
    public function getExpiryDate($quoteId, $format)
    {
        return $this->adminConfigHelper->getExpiryDate($quoteId, $format);
    }

    /**
     * Converts price
     *
     * @param double $price
     * @return string
     */
    public function convertPrice($price)
    {
        return $this->adminConfigHelper->convertPrice($price);
    }

    /**
     * Get formatted date
     *
     * @param string $dateString
     * @param string $format
     * @return string
     */
    public function getFormattedDate($dateString, $format)
    {
        return $this->adminConfigHelper->getFormattedDate($dateString, $format);
    }

    /**
     * Get quote status label
     *
     * @param string $key
     * @return string
     */
    public function getStatusLabelByStatusKey($key)
    {
        return $this->adminConfigHelper->getQuoteStatusLabel($key);
    }

    /**
     * Get status label by quote id
     *
     * @param int $quoteId
     * @return string
     */
    public function getStatusLabelByQuoteId($quoteId)
    {
        return $this->adminConfigHelper->getNegotiableQuoteStatus($quoteId, true);
    }

    /**
     * Get quote status by quote id
     *
     * @param int $quoteId
     * @return string
     */
    public function getQuoteStatusKeyByQuoteId($quoteId, $quote = null)
    {
        return $this->adminConfigHelper->getQuoteStatusKeyByQuoteId($quoteId, $quote);
    }

    /**
     * Check If a Quote is Punchout Quote
     *
     * @param int $quoteId
     * @return boolean
     */
    public function checkIsPunchoutQuote($quoteId)
    {
        return $this->adminConfigHelper->checkIsPunchoutQuote($quoteId);
    }

    /**
     * Get quote declined modal title
     *
     * @return string
     */
    public function getQuoteDeclinedModalTitle()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('decline_modal_title');
    }

    /**
     * Get quote declined message
     *
     * @return string
     */
    public function getQuoteDeclinedMessage()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('decline_modal_message');
    }

    /**
     * Get quote declined reasons
     *
     * @return array
     */
    public function getQuoteDeclinedReason()
    {
        $reasons = $this->adminConfigHelper->getUploadToQuoteConfigValue('quote_decline_reason');
        $declinedReasons = $reasons ? $this->serializer->unserialize($reasons) : [];
        $numberField = array_column($declinedReasons, 'number_field');
        array_multisort($numberField, $declinedReasons);

        return $declinedReasons;
    }

    /**
     * Get quote request change message
     *
     * @return string
     */
    public function getRequestChangeMessage()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('request_change_modal_message');
    }

    /**
     * Get quote request change Cancel CTA Label
     *
     * @return string
     */
    public function getRequestChangeCancelCTALabel()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('request_change_cancel_cta_label');
    }

    /**
     * Get quote request change CTA Label
     *
     * @return string
     */
    public function getRequestChangeCTALabel()
    {
        return $this->adminConfigHelper->getUploadToQuoteConfigValue('request_change_cta_label');
    }

    /**
     * Get status data by quote id
     *
     * @param int $quoteId
     * @return array
     */
    public function getStatusDataByQuoteId($quoteId)
    {
        return $this->adminConfigHelper->getStatusDataByQuoteId($quoteId);
    }

    /**
     * Call RateQuote on Request Change
     *
     * @param array $formData
     * @return boolean
     */
    public function getRateQuoteResponse($formData)
    {
        $quoteId = $formData['quote_id'] ?? '';
        $itemId = $formData['quote_item_id'] ?? '';
        $si = $formData['print_instructions'] ?? '';
        $uploadToQuoteRequest = [
            'action' => 'changeRequested',
            'items' => [
                ['item_id' => $itemId, 'si' => $si]
            ]
        ];

        if ($quoteId && ($quote = $this->getQuoteById($quoteId))) {
            $rateResponse = $this->fxoRateQuote->getFXORateQuote($quote, null, false, $uploadToQuoteRequest);
            if (!empty($rateResponse['output']) && !empty($rateResponse['output']['alerts'])) {
                foreach ($rateResponse['output']['alerts'] as $alerts) {
                    if ($alerts['code'] == 'QCXS.SERVICE.ZERODOLLARSKU') {
                        $this->updateItemInfoBuyRequest($quote, $uploadToQuoteRequest);
                        $this->adminConfigHelper->updateQuoteStatusByKey(
                            $quoteId,
                            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER
                        );
                        $quoteData=[
                            'quote_id' => $quoteId,
                            'status' => AdminConfigHelper::CHANGE_REQUEST
                        ];
                        $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Update Quote Item InfoBuyRequest
     *
     * @param object $quote
     * @param array $siItems
     */
    public function updateItemInfoBuyRequest($quote, $siItems)
    {
        $items = $quote->getAllVisibleItems();
        $itemIdsArr = [];
        $itemSiArr = [];
        foreach ($siItems['items'] as $val) {
            $itemIdsArr[] = $val['item_id'];
            $itemSiArr[$val['item_id']] = $val['si'];
        }
        foreach ($items as $item) {
            if (in_array($item->getItemId(), $itemIdsArr)) {
                $additionalOption = $item->getOptionByCode('info_buyRequest');
                if (!empty($additionalOption->getOptionId())) {
                    $additionalOptions = $additionalOption->getValue();
                    $productData = (array)$this->serializer->unserialize($additionalOptions);
                    $productData['external_prod'][0]['priceable'] = false;
                    $properties = $productData['external_prod'][0]['properties'] ?? [];
                    foreach ($properties as $k => $prop) {
                        if ($prop['name'] == 'USER_SPECIAL_INSTRUCTIONS') {
                            $productData['external_prod'][0]['properties'][$k]['value']
                            = $itemSiArr[$item->getItemId()];
                        }
                    }
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__ . ' Product Json after Request Change : '.json_encode($productData)
                    );

                    $additionalOption->setValue($this->serializer->serialize($productData))->save();
                }
            }
        }
    }

    /**
     * Function to return progress bar percentage
     *
     * @param int $quoteId
     * @return array
     */
    public function getPercentBasedOnStatus($quoteId, $quote = null)
    {
        $result = [];
        $status = $this->adminConfigHelper->getQuoteStatusKeyByQuoteId($quoteId, $quote);
        $statusList = [
            NegotiableQuoteInterface::STATUS_CREATED => 40,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN => 40,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN => 82,
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER => 82,
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER => 62,
            NegotiableQuoteInterface::STATUS_ORDERED => 100,
            NegotiableQuoteInterface::STATUS_EXPIRED => 100,
            NegotiableQuoteInterface::STATUS_DECLINED => 100,
            NegotiableQuoteInterface::STATUS_CLOSED => 100,
        ];
        $result['percent'] = $statusList[$status];
        if ($status == NegotiableQuoteInterface::STATUS_ORDERED) {
            $result['class'] = 'quote-status-closed-and-approve';
            $result['showicon'] = true;
        }
        if ($status == NegotiableQuoteInterface::STATUS_EXPIRED ||
            $status == NegotiableQuoteInterface::STATUS_DECLINED ||
            $status == NegotiableQuoteInterface::STATUS_CLOSED) {
            $result['class'] = 'quote-status-expired-and-declined';
            $result['showicon'] = false;
        }
        if ($status == NegotiableQuoteInterface::STATUS_CREATED ||
            $status == NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN) {
            $result['class'] = 'quote-status-created';
            $result['showicon'] =  false;
        }
        if ($status == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER) {
            $result['class'] = 'quote-status-submitted-customer';
            $result['showicon'] =  false;
        }
        if ($status == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN) {
            $result['class'] = 'quote-status-submitted-admin';
            $result['showicon'] =  false;
        }

        return $result;
    }

    /**
     * Get non standard Image url
     *
     * @return string
     */
    public function getNonStandardImageUrl()
    {
        return $this->assetRepository->getUrl('images/upload-to-quote/nostandard-image.png');
    }

    /**
     * Get deleted item details
     *
     * @return array
     */
    public function getDeletedItems()
    {
        return $this->adminConfigHelper->getDeletedItems();
    }

    /**
     * Get price info for reaming
     *
     * @return array
     */
    public function getTotalPriceInfForRemainingItem()
    {
        return $this->adminConfigHelper->getTotalPriceInfForRemainingItem();
    }

    /**
     * Get the quote notes send by FedEX team member
     *
     * @param int $quoteId
     * @return array
     */
    public function getMostRecentQuoteNote($quoteId)
    {
        $notes = $this->graphqlApiHelper->getQuoteNotes($quoteId);
        if (empty($notes)) {
            return [];
        }
        usort($notes, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $notes[0];
    }

    /**
     * Check if the item is expired.
     *
     * @param $item
     * @return array|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getExpirationUploadToQuoteItem($item)
    {
        $expiredIds = [];
        if ($item->getMiraklOfferId() && $item->getAdditionalData()) {
            $additionalData = json_decode($item->getAdditionalData());
            $expiredData = false;
            if (isset($additionalData->expire)) {
                $date = $this->timezone->date($item->getCreatedAt());
                $date->modify("+".json_decode($item->getAdditionalData())->expire." day");
                $itemExpiryDate = $this->timezone->convertConfigTimeToUtc($date);
                $currentDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                $expiredData = $currentDate >= $itemExpiryDate;
            }

            if ($expiredData ||
                !$this->productInfo->execute($additionalData->supplierPartAuxiliaryID, $item->getSku())) {
                $expiredIds = $item->getId();
            }

            $isExpired3PItem = $expiredIds ? true : false;

            if ($isExpired3PItem) {
                return ['expired_soon' => false,'expired'=> true];
            }

            $isExpiredSoon3PItem = $this->expiredItem->isItemExpiringSoon($item->getId(), $item);

            if ($isExpiredSoon3PItem) {
                return ['expired_soon' => true,'expired'=> false];
            }
        } else {
            $isExpired1PItem = $this->expiredItem->isItemExpired($item);

            if ($isExpired1PItem) {
                return ['expired_soon' => false, 'expired'=> true];
            }

            $isExpiredSoon1PItem = $this->expiredItem->isItemExpiringSoon($item->getId(), $item);

            if ($isExpiredSoon1PItem) {
                return ['expired_soon' => true, 'expired'=> false];
            }
        }

        return ['expired_soon' => false, 'expired'=> false];
    }

    /**
     * check if any item is expired or expired soon to be able to show the toast message.
     *
     * @return array
     * @throws LocalizedException
     */
    public function isAnyUploadToQuoteItemExpiredOrExpiredSoon()
    {
        $quote = $this->getQuote();

        foreach ($quote->getAllVisibleItems() as $item) {
            $details = $this->getExpirationUploadToQuoteItem($item);
            if ($details['expired']) {
                $this->expiredItems['expired'] = true;
            }

            if ($details['expired_soon']) {
                $this->expiredItems['expiring_soon'] = true;
            }
        }

        return $this->expiredItems;
    }

    /**
     * Get product value
     *
     * @param object $item
     * @param int $quoteId
     *
     * @return array
     */
    public function getProductValue($item, $quoteId, $quote = null)
    {
        return $this->adminConfigHelper->getProductValue($item, $quoteId, $quote);
    }

    /**
     * Get product json
     *
     * @param object $item
     * @param int $quoteId
     *
     * @return array
     */
    public function getProductJson($item, $quoteId, $quote = null)
    {
        return $this->adminConfigHelper->getProductJson($item, $quoteId, $quote);
    }

    /**
     * Get upload to quote expiring title
     *
     * @return string
     */
    public function getUploadToQuoteExpiringTitle()
    {
        return $this->configProvider->getUploadToQuoteExpiringTitle();
    }

    /**
     * Get upload to quote expiring message
     *
     * @return string
     */
    public function getUploadToQuoteExpiringMessage()
    {
        return $this->configProvider->getUploadToQuoteExpiringMessage();
    }

    /**
     * Get upload to quote expired title
     *
     * @return string
     */
    public function getUploadToQuoteExpiredTitle()
    {
        return $this->configProvider->getUploadToQuoteExpiredTitle();
    }

    /**
     * Get upload to quote expired message
     *
     * @return string
     */
    public function getUploadToQuoteExpiredMessage()
    {
        return $this->configProvider->getUploadToQuoteExpiredMessage();
    }

    /**
     * Get upload to quote summary expiring title
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiringTitle()
    {
        return $this->configProvider->getUploadToQuoteSummaryExpiringTitle();
    }

    /**
     * Get upload to quote summary expiring message
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiringMessage()
    {
        return $this->configProvider->getUploadToQuoteSummaryExpiringMessage();
    }

    /**
     * Get upload to quote summary expired title
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiredTitle()
    {
        return $this->configProvider->getUploadToQuoteSummaryExpiredTitle();
    }

    /**
     * Get upload to quote summary expired message
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiredMessage()
    {
        return $this->configProvider->getUploadToQuoteExpiredMessage();
    }

    /**
     * Is upload to quote enable toggle
     *
     * @return bool
     */
    public function isUploadToQuoteEnabled()
    {
        return $this->config->isUploadToQuoteEnabled();
    }

    /**
     * Get X-MEN U2Q Quote Detail CUSTOMER_SI validate
     *
     * @return bool
     */
    public function isU2QCustomerSIEnabled()
    {
        return $this->adminConfigHelper->isU2QCustomerSIEnabled();
    }

    /**
     * Get product attributeset name
     *
     * @return string
     */
    public function getProductAttributeName($attributeSetId)
    {
        return $this->adminConfigHelper->getProductAttributeName($attributeSetId);
    }

    /**
     * Get product image url
     *
     * @param object $product
     * @param int $width
     * @param int $height
     * @return string
     */
    public function productImageUrl($product, $width, $height)
    {
        return $this->adminConfigHelper->productImageUrl($product, $width, $height);
    }

    /**
     * Is upload to quote enable toggle
     *
     * @return bool
     */
    public function isEproUploadToQuoteEnable()
    {
        return $this->config->isEproUploadToQuoteEnable();
    }

    /**
     * Get Return to Epro URL
     *
     * @return string|null
     */
    public function getReturnToEproUrl()
    {
        if (!empty($this->customerSession->getBackUrl())) {
            $returnUrl = $this->customerSession->getBackUrl();
        } else {
            $routeUrl = $this->urlInterface->getUrl('success');
            $returnUrl = rtrim($routeUrl, "/");
        }

        return $returnUrl;
    }

    /**
     * New Documents Api Image Preview Toggle
     *
     * @return boolean true|false
     */
    public function isNewDocumentsApiImageEnable()
    {
        return $this->fxocmhelper->getNewDocumentsApiImagePreviewToggle();
    }

    /**
     * Is D190723 Fix ToggleEnable
     *
     * @return bool
     */
    public function isD190723FixToggleEnable()
    {
        return $this->config->isD190723FixToggleEnable();
    }

    /**
     * Check if FuseBidding toggle is enable on store and global level.
     *
     * @return boolean
     */
    public function isFuseBidToggleEnabled()
    {
        return $this->fuseBidViewModel->isFuseBidToggleEnabled();
    }

    /**
     * Check if negotiable quote is present in cart
     *
     * @return boolean
     */
    public function isNonNegotiableQuotePresentInCart()
    {
        $quote = $this->checkoutSession->getQuote();
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($quote->getItems()) {
            if ($negotiableQuote->getId()) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if D-206707 toggle is enabled
     * @return bool
     */
    public function isToggleD206707Enabled()
    {
        return $this->adminConfigHelper->isToggleD206707Enabled();
    }

    /**
    * Toggle for upload to quote submit date
    *
    * @return boolean
    */
    public function toggleUploadToQuoteSubmitDate()
    {
       return $this->adminConfigHelper->toggleUploadToQuoteSubmitDate();
    }

    /**
     * Get submit date
     *
     * @param int $quoteId
     * @return string
     */
    public function getSubmitDate($quoteId)
    {
        return $this->adminConfigHelper->getSubmitDate($quoteId);
    }

    /**
     * @return bool
     */
    public function getIsFclCustomer(): bool
    {
        return $this->ssoConfiguration->isFclCustomer();
    }
}
