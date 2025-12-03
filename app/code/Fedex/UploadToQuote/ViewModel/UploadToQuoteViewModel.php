<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\UploadToQuote\ViewModel;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\UploadToQuote\Api\ConfigInterface as UploadToQuoteConfigInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper as UploadToQuoteAdminConfigHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssestRepository;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\ExternalProd;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Model\QuoteFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Company\Api\CompanyManagementInterface;
use Psr\Log\LoggerInterface;

/**
 * UploadToQuoteViewModel view model class
 */
class UploadToQuoteViewModel implements ArgumentInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param UrlInterface $url
     * @param UploadToQuoteAdminConfigHelper $uploadToQuoteAdminConfigHelper
     * @param AssestRepository $assestRepository
     * @param Curl $curl
     * @param CheckoutSession $checkoutSession
     * @param SerializerInterface $serializer
     * @param Repository $assetRepository
     * @param Data $helper
     * @param ExternalProd $externalProd
     * @param QuoteFactory $quoteFactory
     * @param FuseBidViewModel $fuseBidViewModel
     * @param CompanyManagementInterface $companyRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected CustomerSession $customerSession,
        protected UrlInterface $url,
        protected UploadToQuoteAdminConfigHelper $uploadToQuoteAdminConfigHelper,
        protected AssestRepository $assestRepository,
        protected Curl $curl,
        protected CheckoutSession $checkoutSession,
        protected SerializerInterface $serializer,
        protected Repository $assetRepository,
        private Data $helper,
        private ExternalProd $externalProd,
        protected QuoteFactory $quoteFactory,
        protected FuseBidViewModel $fuseBidViewModel,
        protected CompanyManagementInterface $companyRepository,
        protected LoggerInterface $logger,
        protected UploadToQuoteConfigInterface $uploadToQuoteConfig
    ) {
        $this->logger = $logger;
    }

    /**
     * Get Upload to quote toggle value
     *
     * @return boolean
     * @throws NoSuchEntityException
     */
    public function isUploadToQuoteEnable()
    {
        $companyData = $this->customerSession->getOndemandCompanyInfo();
        $companyId = (isset($companyData['company_id']) && !empty($companyData['company_id']))
        ? trim($companyData['company_id']) : null;

        return $this->uploadToQuoteAdminConfigHelper
        ->isUploadToQuoteEnable($this->storeManager->getStore()->getId(), $companyId);
    }

    /**
     * Get Upload To Quote Config Value
     *
     * @param string $key
     * @return boolean|string
     */
    public function getUploadToQuoteConfigValue($key)
    {
        return $this->uploadToQuoteAdminConfigHelper
        ->getUploadToQuoteConfigValue($key, $this->storeManager->getStore()->getId());
    }

    /**
     * Get Upload To Quote Success Url
     *
     * @return string
     */
    public function getUploadToQuoteSuccessUrl()
    {
        return $this->url->getUrl('uploadtoquote/index/quotesuccess');
    }

    /**
     * Update quote status
     *
     * @param int $quoteId
     * @param string $statusKey
     * @param boolean $isUpdateHistory
     * @return void
     */
    public function updateQuoteStatusByKey($quoteId, $statusKey, $isUpdateHistory = false)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);
        if ($this->isUploadToQuoteEnable()
        || ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $quote->getIsBid())) {
            if ($isUpdateHistory) {
                $this->uploadToQuoteAdminConfigHelper->updateQuoteStatusWithHisotyUpdate($quoteId, $statusKey);
            } else {
                $this->uploadToQuoteAdminConfigHelper->updateQuoteStatusByKey($quoteId, $statusKey);
            }
        }
    }

    /**
     * GetQuoteEditButton Msg for quote summary
     *
     * @return string
     */
    public function getQuoteEditButtonMsg()
    {
        return $this->uploadToQuoteAdminConfigHelper->getQuoteEditButtonMsg();
    }

    /**
     * Check quote is priceable or not
     *
     * @return boolean
     */
    public function checkoutQuotePriceisDashable()
    {
        $isQuotePriceIsDashable = 0;
        if ($this->isUploadToQuoteEnable()) {
            $isQuotePriceIsDashable = $this->uploadToQuoteAdminConfigHelper
            ->checkoutQuotePriceisDashable($this->checkoutSession->getQuote());
        }
        return $isQuotePriceIsDashable;
    }

    /**
     * If product has special instruction or not
     *
     * @param array $productJson
     * @param bool $specialInstruction
     * @return bool
     */
    public function isProductLineItems($productJson, $specialInstruction = false)
    {
        return $this->uploadToQuoteAdminConfigHelper->isProductLineItems($productJson, $specialInstruction);
    }

    /**
     * To check if SI product is editable or not
     *
     * @param array $productJson
     * @return bool
     */
    public function isSiItemNonEditable($productJson)
    {
        $isSiItemNonEditable = false;
        if ($this->isUploadToQuoteEnable()) {
            return $this->uploadToQuoteAdminConfigHelper->isSiItemNonEditable($productJson);
        }

        return $isSiItemNonEditable;
    }

    /**
     * To check if SI product is editable or not after quote approve.
     *
     * @param array $productJson
     * @return bool
     */
    public function isSiItemEditBtnDisable($productJson)
    {
        return $this->uploadToQuoteAdminConfigHelper->isSiItemEditBtnDisable($productJson);
    }

    /**
     * Check quote is pricable or not
     *
     * @param object $quote
     * @param boolean $fuseBidingQuote
     * @return boolean
     */
    public function isQuotePriceable($quote, $fuseBidingQuote = false)
    {
        if ($this->isUploadToQuoteEnable()
        && $this->uploadToQuoteAdminConfigHelper->checkoutQuotePriceisDashable($quote)) {
            return false;
        }
        if ($fuseBidingQuote && $this->uploadToQuoteAdminConfigHelper->checkoutQuotePriceisDashable($quote)) {
            return false;
        }

        return true;
    }

    /**
     * Check item is pricable or not
     *
     * @param json $productJson
     * @param boolean $fuseBidingQuote
     * @return boolean
     */
    public function isItemPriceable($productJson, $fuseBidingQuote = false)
    {
        $isItemPriceable = true;
        if ($this->isUploadToQuoteEnable()) {
            $isItemPriceable = $this->uploadToQuoteAdminConfigHelper->isItemPriceable($productJson);
        }
        if ($fuseBidingQuote) {
            $isItemPriceable = $this->uploadToQuoteAdminConfigHelper->isItemPriceable($productJson);
        }

        return $isItemPriceable;
    }

    /**
     * Get price dash
     *
     * @return string
     */
    public function getPriceDash()
    {
        return '$--.--';
    }

    /**
     * Check file is stardard or not
     *
     * @param json $productJson
     * @return boolean
     */
    public function isNonStandardFile($productJson)
    {
        if ($this->isUploadToQuoteEnable()) {
            return $this->uploadToQuoteAdminConfigHelper->isNonStandardFile($productJson);
        }

        return false;
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
     * Get Upload to quote toggle value globally
     *
     * @return bool|string
     */
    public function isUploadToQuoteGloballyEnabled()
    {
        return $this->uploadToQuoteAdminConfigHelper->isUploadToQuoteGloballyEnabled();
    }

    /**
     * Pass updated SI in product json of RateQuote Request to save/update in DB
     *
     * @param array $productData
     * @param array $items
     * @return array
     */
    public function updateItemsSI($productData, $items)
    {
        $itemIdsArr = [];
        $itemSiArr = [];
        foreach ($items['items'] as $val) {
            $itemIdsArr[] = $val['item_id'];
            $itemSiArr[$val['item_id']] = $val['si'];
        }
        foreach ($productData as $key => $itemData) {
            if (in_array($itemData['instanceId'], $itemIdsArr)) {
                $properties = $productData[$key]['properties'] ?? [];
                foreach ($properties as $k => $prop) {
                    if ($prop['name'] == 'USER_SPECIAL_INSTRUCTIONS') {
                        $productData[$key]['properties'][$k]['value'] = $itemSiArr[$itemData['instanceId']];

                    }
                }
            }
        }

        return $productData;
    }

    /**
     * Exclude delete item
     *
     * @param arra $items
     * @param int $excludeItemid
     * @return array
     */
    public function excludeDeletedItem($items, $excludeItemid)
    {
        $deletedItems = $this->uploadToQuoteAdminConfigHelper->getDeletedItems();
        $remainingItems = [];
        foreach ($items as $item) {
            if ($item->getId() != $excludeItemid && !in_array($item->getId(), $deletedItems)) {
                $remainingItems[] = $item;
            }
        }

        return $remainingItems;
    }

    /**
     * Check quote is negotiated or not
     *
     * @param int $quoteId
     * @return boolean
     */
    public function isQuoteNegotiated($quoteId)
    {
        return $this->uploadToQuoteAdminConfigHelper->isQuoteNegotiated($quoteId);
    }

    /**
     * Get item SI type
     *
     * @param json $productJson
     * @return string
     */
    public function getSiType($productJson)
    {
        $siType = '';
        if ($this->isUploadToQuoteEnable()) {
            $siType = $this->uploadToQuoteAdminConfigHelper->getSiType($productJson);
        }

        return $siType;
    }

    /**
     * Update sku details after rate quote response
     *
     * @param array $rateQuoteResponse
     * @param object $quote
     */
    public function updateLineItemsSkuDetails($rateQuoteResponse, $quote)
    {
        $items = $quote->getAllVisibleItems();
        $quoteItemsIds = [];
        foreach ($items as $item) {
            $quoteItemsIds[] = $item->getItemId();
        }
        $productLines = $rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'][0]['productLines'] ?? [];
        if (!empty($productLines)) {
            foreach ($productLines as $prodLineData) {
                if (in_array($prodLineData['instanceId'], $quoteItemsIds)) {
                    $item = $quote->getItemById($prodLineData['instanceId']);
                    if (!$item) {
                        continue;
                    }
                    $additionalOption = $item->getOptionByCode('info_buyRequest');
                    if (empty($additionalOption)) {
                        continue;
                    }
                    $additionalOptions = $additionalOption->getValue();
                    $productData = (array)$this->serializer->unserialize($additionalOptions);
                    $productData['productRateTotal'][0] = $prodLineData;
                    if ($item->getMiraklOfferId() !== null) {
                        $productData = $this->externalProd->createAdditionalData($item, $productData);
                    }
                    $encodedData = $this->serializer->serialize($productData);
                    $additionalOption->setValue($encodedData)->save();
                }
            }
        }
    }

    /**
     * Update items in rate quote request
     *
     * @param array $rateQuoteRequest
     * @param array $quoteItemArray
     * @return array
     */
    public function updateItemsForFuse($rateQuoteRequest, $quoteItemArray)
    {
        foreach ($quoteItemArray as $quoteItem) {
            switch ($quoteItem['item_action']) {
                case 'add':
                    $rateQuoteRequest = $this->addItem($rateQuoteRequest, $quoteItem['product']);
                    break;
                case 'update':
                    $data = json_decode($quoteItem['product'], true);

                    if (isset($data['is_marketplace']) && $data['is_marketplace'] === true) {
                        break;
                    }

                    $rateQuoteRequest = $this->updateItem($rateQuoteRequest, $quoteItem);
                    break;
                case 'delete':
                    $rateQuoteRequest = $this->deleteItem($rateQuoteRequest, $quoteItem['item_id']);
                    break;
            }
        }
        return $rateQuoteRequest;
    }

    /**
     * Update discont object in rate quote request
     *
     * @param array $rateQuoteRequest
     * @param array $discountIntent
     * @return array
     */
    public function updateRateRequestForFuseBiddingDiscount($rateQuoteRequest, $discountIntent)
    {
        $rateQuoteRequest["rateQuoteRequest"]["retailPrintOrder"]['discountIntentResource'] = $discountIntent;

        return $rateQuoteRequest;
    }

    /**
     * Add a new item to the rate quote request
     *
     * @param array $rateQuoteRequest
     * @param string $product
     * @return array
     */
    private function addItem($rateQuoteRequest, $product)
    {
        $newItemData = json_decode($product, true);
        $newItemData['instanceId'] = random_int(pow(10, (12 - 1)), pow(10, 12) - 1);
        $rateQuoteRequest[] = $newItemData;

        return $rateQuoteRequest;
    }

    /**
     * Update an existing item in the rate quote request
     *
     * @param array $rateQuoteRequest
     * @param array $quoteItem
     * @return array
     */
    private function updateItem($rateQuoteRequest, $quoteItem)
    {
        foreach ($rateQuoteRequest as &$itemData) {
            if ($itemData['instanceId'] == $quoteItem['item_id']) {
                $itemData = json_decode($quoteItem['product'], true);
                $itemData['instanceId'] = $quoteItem['item_id'];
            }
        }

        return $rateQuoteRequest;
    }

    /**
     * Delete an item from the rate quote request
     *
     * @param array $rateQuoteRequest
     * @param int $deleteItemId
     * @return array
     */
    private function deleteItem($rateQuoteRequest, $deleteItemId)
    {
        foreach ($rateQuoteRequest as $key => $itemData) {
            if ($itemData['instanceId'] == $deleteItemId) {
                unset($rateQuoteRequest[$key]);
                break;
            }
        }

        return array_values($rateQuoteRequest);
    }

    /**
     * Update quote log in negotiable quote hisory
     *
     * @param int $quoteId
     * @param string $quoteStatus
     * @return void
     */
    public function updateLogHistory($quoteId, $quoteStatus)
    {
        $this->uploadToQuoteAdminConfigHelper->updateLogHistory($quoteId, $quoteStatus);
    }

    /**
     * Update $info for reorder case
     *
     * @param array $info
     * @return array
     */
    public function resetCustomerSI($info)
    {
        if (isset($info['external_prod']) && is_array($info['external_prod'])) {
            foreach ($info['external_prod'] as &$prod) {
                if (isset($prod['properties']) && is_array($prod['properties'])) {
                    foreach ($prod['properties'] as &$property) {
                        if (isset($property['name']) && $property['name'] == 'CUSTOMER_SI') {
                            $property['value'] = '';
                            break;
                        }
                    }
                }
            }
        }

        return $info;
    }

    /**
     * Get toggle value for decline quote when all items are deleted
     *
     * @return boolean
     */
    public function isMarkAsDeclinedEnabled()
    {
        return $this->uploadToQuoteAdminConfigHelper->isMarkAsDeclinedEnabled();
    }

    /**
     * Update quote log in negotiable quote history
     *
     * @param int $quoteId
     * @param string $quoteStatus
     * @return void
     */
    public function updateEproQuoteStatusByKey($quoteId, $quoteStatus)
    {
        return $this->uploadToQuoteAdminConfigHelper->updateFinalQuoteStatus($quoteId, $quoteStatus);
    }

    /**
     * Update Epro Negotiable Quote when upload to quote submit
     *
     * @param object $quote
     * @return void
     */
    public function updateEproNegotiableQuote($quote)
    {
        return $this->uploadToQuoteAdminConfigHelper->updateEproNegotiableQuote($quote);
    }

    /**
     * Get company extention url
     *
     * @param object $quote
     * @return string
     */
    public function getCompanyExtentionUrl($quote)
    {
        $extentionUrl = '';
        if ($this->getMyQuoteMaitenanceFixToggle() && !$quote->getIsBid()) {
            try {
                $company = $this->companyRepository->getByCustomerId($quote->getCustomerId());
                if ($company) {
                    $extentionUrl = $company->getCompanyUrlExtention() . '/';
                }
            } catch (\Exception $e) {
                $this->logger->critical("UploadToQuote Error...".$e->getMessage());
            }

        }

        return $extentionUrl;
    }

    /**
     * Get toggle value for My Quotes Maintenace page fix
     *
     * @return boolean
     */
    public function getMyQuoteMaitenanceFixToggle()
    {
        return $this->uploadToQuoteAdminConfigHelper->getMyQuoteMaitenanceFixToggle();
    }

    /**
     * Get login modal heading
     *
     * @return string
     */
    public function getLoginModalHeading()
    {
        return $this->uploadToQuoteConfig->getLoginModalHeading();
    }

    /**
     * Get login modal copy
     *
     * @return string
     */
    public function getLoginModalCopy()
    {
        return $this->uploadToQuoteConfig->getLoginModalCopy();
    }
}
