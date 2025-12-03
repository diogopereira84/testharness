<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class FXOProductDataModel
{
    public const TOGGLE_FOR_DLT_FIX = 'explorers_d196313_fix';
    /**
     * Xpath enable FUSE Search - Upload to Quote - 3p product data is corrupted with 1p data
     */
    public const XPATH_ENABLE_3P_CORRUPTED_DATA ='tiger_D195777';

    /**
     * @param ProductModel $productModel
     * @param AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param SerializerInterface $serializer
     * @param CheckoutSession $checkoutSession
     * @param ToggleConfig $toggleConfig
     * @param QuoteHelper $quoteHelper
     * @param AdminConfigHelper $adminConfigHelper
     * @param ConfigInterface $config
     * @param ProductRepositoryInterface $productRepository
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        protected ProductModel $productModel,
        protected AttributeSetRepositoryInterface $attributeSetRepositoryInterface,
        protected SerializerInterface $serializer,
        protected CheckoutSession $checkoutSession,
        protected ToggleConfig $toggleConfig,
        private QuoteHelper $quoteHelper,
        protected AdminConfigHelper $adminConfigHelper,
        private readonly ConfigInterface $config,
        private readonly ProductRepositoryInterface $productRepository,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    )
    {
    }

    /**
     * Get authentication details
     *
     * @param Object $items
     * @param int $quoteObjectItemsCount
     * @param int $dbQuoteItemCount
     * @return array
     */
    public function iterateItems(
        $cartDataHelper,
        $items,
        $quoteObjectItemsCount,
        $dbQuoteItemCount,
        $isGraphQlRequest = false
    ) {
        $externalProdData = $rateApiProdRequestData = $itemsUpdatedData = $productAssociations = [];
        $index = 0;
        $rateQuoteToggle = $this->config->isRateQuoteProductAssociationEnabled();
        foreach ($items as $key => $item) {
            if ($item->getProductType() === ProductModel\Type::TYPE_BUNDLE) {
                continue;
            }
            if (!empty($item->getMiraklOfferId())) {
                $rateApiProdRequestData[] = $this->quoteHelper->getMarketplaceRateQuoteRequest($item);
                    $productAssociations[$item->getData('mirakl_shop_id')][] = $cartDataHelper->getProductAssociation(
                        $item,
                        $index,
                        $quoteObjectItemsCount,
                        $dbQuoteItemCount
                    );

                if ($this->toggleConfig->getToggleConfigValue(self::XPATH_ENABLE_3P_CORRUPTED_DATA)) {
                    $itemsUpdatedData[] = [];
                }
            } else {
                $pid = $item->getProduct()->getId();
                $qty = $item->getQty();
                if($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
                    $product =  $this->productRepository->getById($pid);
                }else{
                    $product = $this->productModel->load($pid);
                }
                $isAttribute = $product->getAttributeSetId();
                $attributeSetRepository = $this->attributeSetRepositoryInterface->get($isAttribute);
                $attributeSetName = $attributeSetRepository->getAttributeSetName();
                $isCustomize = $product->getCustomizable();
                $additionalOption = $item->getOptionByCode('info_buyRequest');
                $additionalOptions = $additionalOption->getValue();
                if (!empty($additionalOptions)) {
                    $decodedData = (array)$this->serializer->unserialize($additionalOptions);
                }
                if ($this->adminConfigHelper->isUploadToQuoteGloballyEnabled() &&
                    $isGraphQlRequest && empty($additionalOptions)) {
                    $decodedData = $this->adminConfigHelper->getProductValue($item, $item->getQuoteId());
                }
                $decodedData['external_prod'][0]['qty'] = $qty;
                $productAssociation = $cartDataHelper->getProductAssociation(
                    $item,
                    $index,
                    $quoteObjectItemsCount,
                    $dbQuoteItemCount
                );
                // Get product Association from Cart Data Helper
                if (!$isGraphQlRequest){
                    $productAssociations[0][] = $productAssociation;
                } else {
                    $productAssociations[] = $productAssociation;
                }

                if ($this->toggleConfig->getToggleConfigValue(static::TOGGLE_FOR_DLT_FIX) && $attributeSetName != 'FXOPrintProducts') {
                    // Fetch the dlt_threshold attribute value
                    $dltHours = $cartDataHelper->getDltThresholdHours($product, $qty);

                    if (!empty($dltHours)) {
                        $decodedData = $cartDataHelper->setDltThresholdHours($decodedData, $dltHours);
                    }
                }
                if ($attributeSetName != 'FXOPrintProducts' && !$isCustomize) {
                    if ($item->getItemId() == null) {
                        $instanceId = mt_rand(pow(10, (12 - 1)), pow(10, 12) - 1);
                    } else {
                        $instanceId = $item->getItemId();
                    }

                    if ($quoteObjectItemsCount == $dbQuoteItemCount) {
                        $decodedData['external_prod'][0]['instanceId'] = $instanceId;
                    } else {
                        $decodedData['external_prod'][0]['instanceId'] = $instanceId ?? "$key";
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
                        $externalProdData['external_prod'][0]['instanceId'] =
                        $item->getItemId() ? $item->getItemId() : "$key";
                    }
                    $externalProdData['external_prod'][0]['preview_url'] = null;
                    $externalProdData = $cartDataHelper->setFxoProductNull($decodedData, $externalProdData);

                    $rateApiProdRequestData[] = $externalProdData['external_prod'][0];
                }
            }
            $index++;
        }
        $associations = $productAssociations;

        if ($rateQuoteToggle && $isGraphQlRequest) {
            $associations = $this->setProductAssociations($productAssociations);
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
     * Manage Additional Item
     */
    public function manageAdditionalItem($item, $itemsUpdatedData, $count)
    {
        $additionalOption = $item->getOptionByCode('info_buyRequest');
        if (!empty($additionalOption->getOptionId())) {
            if (isset($itemsUpdatedData[$count]) && !empty($itemsUpdatedData[$count])) {
                $additionalOption->setValue($this->serializer->serialize($itemsUpdatedData[$count]))
                    ->save();
            }
        } else {
            $optionIds = $item->getOptionByCode('custom_option');
            if ($optionIds) {
                $item->removeOption('custom_option');
            }
        }
    }

    /**
     * @param $productAssociations
     * @return array
     */
    private function setProductAssociations($productAssociations): array
    {
        $associations = [];
        foreach ($productAssociations as $productAssociation) {
            $associations[] = $productAssociation;
        }
        return $associations;
    }
}
