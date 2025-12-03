<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\OptimizeProductinstance\Model;

use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;
use Fedex\OptimizeProductinstance\Model\QuoteCompressionFactory;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\Delivery\Helper\Data as ProductDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CleanUpdateQuoteItemInstanceSubscriber implements OptimizeInstanceSubscriberInterface
{
    public const EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS = "explorers_optimize_quotes_and_orders_within_14_months";

    public const QUOTE_INSTANCE_NOT_UPDATED = 2;

    /**
     * OptimizeInstanceSubscriber constructor.
     *
     * @param QuoteFactory $quoteFactory
     * @param LoggerInterface $logger
     * @param QuoteCompressionFactory $quoteCompressionFactory
     * @param NegotiableQuoteFactory $negotiableQuoteFactory
     * @param ProductDataHelper $productDataHelper
     * @param ToggleConfig $toggleConfig
     */

    public function __construct(
        protected QuoteFactory $quoteFactory,
        protected LoggerInterface $logger,
        protected QuoteCompressionFactory $quoteCompressionFactory,
        protected NegotiableQuoteFactory $negotiableQuoteFactory,
        protected ProductDataHelper $productDataHelper,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function processMessage(OptimizeInstanceMessageInterface $message)
    {
        try {
            if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS)) {
                $tempTableQuoteId = (int) $message->getMessage();
                $quoteCompressionData = $this->quoteCompressionFactory->create()->load($tempTableQuoteId);
                $quoteId = $quoteCompressionData->getQuoteId();
                $isApproved = $this->checkNegotiableQuoteApprovedOrNot($quoteId);
                $quote = $this->quoteFactory->create()->load($quoteId);
                $items = $quote->getAllItems();
                $tempQuoteStatus = 0;
                $finalQuoteStatus = 0;
                foreach ($items as $item) {
                    if ($item->getMiraklShopId()) {
                        continue;
                    }
                    
                    $tempQuoteStatus = $this->cleanUpdateFxoPrintProducts($item, $isApproved, $quote);
                    if ($tempQuoteStatus) {
                        if ($finalQuoteStatus < $tempQuoteStatus) {
                            $finalQuoteStatus = $tempQuoteStatus;
                        }
                    }
                }
                $status = $finalQuoteStatus ?? $tempQuoteStatus;
                $quoteCompressionData->setStatus($status);
                $quoteCompressionData->save();
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in processing clean and update product Instance for quote ID: " .$quoteId." ". var_export($e->getMessage(), true));
        }
    }

    /**
     * Clean and Update FxoPrintProducts Instance
     *
     * @param object $item
     * @param bool $isApproved
     * @param object $quote
     * @return int $tempQuoteStatus
     */
    public function cleanUpdateFxoPrintProducts($item, $isApproved, $quote)
    {
        $tempQuoteStatus = 0;
        $productAttributeSetId = $item->getProduct()->getAttributeSetId();
        $productAttributeSetName = $this->productDataHelper->getProductAttributeName($productAttributeSetId);
        if ($productAttributeSetName == "FXOPrintProducts") {
            $item->setIsSuperMode(true);
            $additionalOption = $item->getOptionByCode('info_buyRequest');
            // clean and update item option
            $tempQuoteStatus = 1;
            if (!empty($additionalOption->getOptionId()) && $isApproved && $quote->getIsActive() != 1) {
                    $additionalOption->setValue(null)->save();
            } elseif (($quote->getIsActive() == 1 || $isApproved != true) && empty($quote->getReservedOrderId())) {
                $isUpdatedInstanceFormat = $this->updateQuoteItemOptionData($additionalOption->getValue());
                if ($isUpdatedInstanceFormat == self::QUOTE_INSTANCE_NOT_UPDATED) {
                    return self::QUOTE_INSTANCE_NOT_UPDATED;
                } else if ($isUpdatedInstanceFormat != false) {
                    $additionalOption->setValue($isUpdatedInstanceFormat)->save();
                }
            }
        }

        return $tempQuoteStatus;
    }

    /**
     * Create new Item Product json instance
     *
     * @param string $additionalOptionData
     * @return string|bool|Int
     */
    public function updateQuoteItemOptionData($additionalOptionData)
    {
        $exstingProductInstaceData = json_decode($additionalOptionData, true);

        $newFormatInstanceData = [];

        if (null != $exstingProductInstaceData && !array_key_exists('fxoMenuId', $exstingProductInstaceData)) {
            $fxoProductDatas = json_decode($exstingProductInstaceData['external_prod'][0]["fxo_product"], true);
            $fxoProductData = $fxoProductDatas ?? '';
            if(!$fxoProductData) {
                $fxoProduct = str_replace('\\\"', '', $exstingProductInstaceData['external_prod'][0]["fxo_product"]);
                $fxoProductDatas = json_decode($fxoProduct, true);
                $fxoProductData = $fxoProductDatas ?? '';
                if (!$fxoProductData) {
                    return self::QUOTE_INSTANCE_NOT_UPDATED;
                }
            }
            $externalProduct = $exstingProductInstaceData['external_prod'][0];
            $newFormatInstanceData['external_prod'][0]['productionContentAssociations'] = $externalProduct['productionContentAssociations'] ? $externalProduct['productionContentAssociations'] : [];
            $newFormatInstanceData['external_prod'][0]['userProductName'] = $externalProduct['userProductName'] ?? '';
            $newFormatInstanceData['external_prod'][0]['id'] = $externalProduct['id'] ?? '';
            $newFormatInstanceData['external_prod'][0]['version'] = $externalProduct['version'] ?? '';
            $newFormatInstanceData['external_prod'][0]['name'] = $externalProduct['name'] ?? '';
            $newFormatInstanceData['external_prod'][0]['qty'] = $externalProduct['qty'] ?? '';
            $newFormatInstanceData['external_prod'][0]['priceable'] = $externalProduct['priceable'] ?? '';
            $newFormatInstanceData['external_prod'][0]['instanceId'] = $externalProduct['instanceId'] ?? '';
            $newFormatInstanceData['external_prod'][0]['proofRequired'] = $externalProduct['proofRequired'] ?? '';
            $newFormatInstanceData['external_prod'][0]['isOutSourced'] = $externalProduct['isOutSourced'] ?? '';
            $newFormatInstanceData['external_prod'][0]['features'] = $externalProduct['features'] ?? [];
            $newFormatInstanceData['external_prod'][0]['pageExceptions'] = $externalProduct['pageExceptions'] ?? [];
            $newFormatInstanceData['external_prod'][0]['contentAssociations'] = $externalProduct['contentAssociations'] ?? [];
            $newFormatInstanceData['external_prod'][0]['properties'] = $externalProduct['properties'] ?? [];
            $newFormatInstanceData['external_prod'][0]['preview_url'] = $externalProduct['preview_url'] ?? '';
            $newFormatInstanceData['external_prod'][0]['isEditable'] = $externalProduct['isEditable'] ?? false;
            $newFormatInstanceData['external_prod'][0]['isEdited'] = $externalProduct['isEdited'] ?? false;
            $newFormatInstanceData['external_prod'][0]['fxoMenuId'] = $fxoProductData['fxoMenuId'] ?? '';
            $newFormatInstanceData['productConfig'] = isset($fxoProductData['fxoProductInstance']['productConfig']) ?
                                                             $fxoProductData['fxoProductInstance']['productConfig'] : [];

            if (
                empty($newFormatInstanceData['productConfig']) ||
                empty($newFormatInstanceData['productConfig']['productPresetId']) ||
                empty($newFormatInstanceData['productConfig']['fileCreated'])
                ) {
                return self::QUOTE_INSTANCE_NOT_UPDATED;
            }
            if ($fxoProductData) {
                unset($newFormatInstanceData['productConfig']['product']);
            }
            $newFormatInstanceData['productRateTotal'] = $fxoProductData ? $fxoProductData['fxoProductInstance']['productRateTotal'] : [];
            $newFormatInstanceData['quantityChoices'] = $fxoProductData ? $fxoProductData['fxoProductInstance']['quantityChoices'] : [];
            $newFormatInstanceData['fileManagementState'] = $fxoProductData ? $fxoProductData['fxoProductInstance']['fileManagementState'] : [];
            $newFormatInstanceData['fxoMenuId'] = $fxoProductData ? $fxoProductData['fxoMenuId'] : '';

            return json_encode($newFormatInstanceData);
        } else {
            return false;
        }
    }

    /**
     * Check it is negotiable quote and is approved or not
     *
     * @param int $quoteId
     * @return bool true|false
     */
    public function checkNegotiableQuoteApprovedOrNot($quoteId)
    {
        $negotiableQuoteCollection = $this->negotiableQuoteFactory->create()->load($quoteId);
        if (null != $negotiableQuoteCollection->getStatus() &&
        ($negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_CREATED ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_DECLINED ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_EXPIRED ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_CLOSED ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_DRAFT_BY_ADMIN ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN ||
        $negotiableQuoteCollection->getStatus() == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER)) {
            return false;
        }

        return true;
    }
}
