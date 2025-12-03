<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Cart\CustomerData;

use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Catalog\Model\Product\Type;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Fedex\ProductEngine\Model\Config\Backend as PeBackendConfig;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\JsonValidator;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\ProductUnavailabilityMessage\ViewModel\CheckProductAvailability;
use Magento\Framework\UrlInterface;
use Fedex\CustomerCanvas\ViewModel\CanvasParams;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfigInterface;

/**
 * Default cart item
 * @codeCoverageIgnore
 */

class DefaultItem extends \Magento\Checkout\CustomerData\DefaultItem
{
    const REQUEST_URL = 'marketplacepunchout/index/pkce';

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @param Image $imageHelper
     * @param \Fedex\Delivery\Helper\Data $dataHelper
     * @param \Magento\Msrp\Helper\Data $msrpHelper
     * @param UrlInterface $urlBuilder
     * @param ConfigurationPool $configurationPool
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param Escaper $escaper
     * @param ItemResolverInterface $itemResolver
     * @param AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param JsonValidator $jsonValidator
     * @param PeBackendConfig $peBackendConfig
     * @param ProductInfoHandler $productInfoHandler
     * @param SdeHelper $sdeHelper
     * @param ShopManagement $shopManagement
     * @param HandleMktCheckout $handleMktCheckout
     * @param ToggleConfig $toggleConfig
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param Data $marketplaceData
     * @param SsoConfiguration $ssoConfiguration
     * @param CheckProductAvailability $checkProductAvailability
     * @param CanvasParams $canvasParams
     * @param ProductBundleConfigInterface $productBundleConfigInterface
     */
    public function __construct(
        Image $imageHelper,
        public \Fedex\Delivery\Helper\Data $dataHelper,
        \Magento\Msrp\Helper\Data $msrpHelper,
        UrlInterface $urlBuilder,
        ConfigurationPool $configurationPool,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        Escaper $escaper,
        ItemResolverInterface $itemResolver,
        private AttributeSetRepositoryInterface $attributeSetRepositoryInterface,
        private JsonValidator $jsonValidator,
        protected PeBackendConfig $peBackendConfig,
        private ProductInfoHandler $productInfoHandler,
        protected SdeHelper $sdeHelper,
        private ShopManagement $shopManagement,
        private HandleMktCheckout $handleMktCheckout,
        protected ToggleConfig $toggleConfig,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected Data $marketplaceData,
        private SsoConfiguration $ssoConfiguration,
        private readonly CheckProductAvailability $checkProductAvailability,
        private readonly CanvasParams $canvasParams,
        private readonly ProductBundleConfigInterface $productBundleConfigInterface
    ) {
        $this->imageHelper = $imageHelper;
        $this->msrpHelper = $msrpHelper;
        $this->urlBuilder = $urlBuilder;
        $this->configurationPool = $configurationPool;
        $this->checkoutHelper = $checkoutHelper;
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(Escaper::class);
        $this->itemResolver = $itemResolver ?: ObjectManager::getInstance()->get(ItemResolverInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function doGetItemData()
    {
        $productName = null;
        $imgSrc = null;
        $instanceId = null;
        $url = null;
        $imageObj = $this->imageHelper->init($this->getProductForThumbnail(), 'mini_cart_product_thumbnail');
        $attributeSetId = $this->item->getProduct()->getAttributeSetId();

        // Get External data product json;
        $productJson = (array)$this->productInfoHandler->getInfoBuyRequest($this->item);
        $externalProd = (array)$this->productInfoHandler->getItemExternalProd($this->item);
        $id = isset($externalProd['id']) ? $externalProd['id'] : '';
        $version = isset($externalProd['version']) ? $externalProd['version'] : '';

        // Get attribute set name;
        $attributeSetName = $this->getProductAttributeName($attributeSetId);

        $product = $this->item->getProduct();
        $isCustomize = $this->dataHelper->getProductCustomAttributeValue($product->getId(), 'customizable');
        $externalProductInstance = '';
        $childrenExternalProductInstancies = [];
        $childrenItemsIds = [];
        $qtydata = '';


        $isThirdPartyProduct = !empty($this->item->getMiraklOfferId()) ? true : false;

        $shopAltName = null;
        $sku = null;
        $additionalData = null;
        $marketplaceProductUnitPrice = null;
        $marketplaceProductTotal = null;
        $canEditReorder = false;
        $punchoutEnabled = false;
        $punchoutUrl = '';
        $isBundleProductSetupCompleted = true;

        if ($isThirdPartyProduct) {
            $sellerShopProduct = $this->shopManagement->getShopByProduct($this->item->getProduct());
            foreach ($sellerShopProduct->getAdditionalInfo()['additional_field_values'] as $additionalInfo) {
                if ($additionalInfo['code'] === 'allow-edit-reorder') {
                    $canEditReorder = $additionalInfo['value'] === 'true';
                }
                if ($additionalInfo['code'] === 'punchout-flow-enhancement') {
                    $punchoutEnabled = $additionalInfo['value'] === 'true';
                    $punchoutUrl = $this->ssoConfiguration->getHomeUrl() . SELF::REQUEST_URL;
                }
            }
            $shopAltName = $sellerShopProduct->getSellerAltName();
            $sku = $this->item->getSku();
            $additionalData = $this->item->getAdditionalData();
            $additionalDataArray = (array) json_decode($additionalData);
            $marketplaceProductUnitPrice = $additionalDataArray['unit_price'];
            $marketplaceProductTotal = $additionalDataArray['total'];
            if (!$this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
                $volumeDiscountAmount = $this->item->getVolumeDiscount();
                $volumeDiscountAmount = ((float) $volumeDiscountAmount == 0) ? $this->item->getDiscount() : 0;
                $itemDiscount = str_replace(',', '', (string)$volumeDiscountAmount);
            } else {
                $volumeDiscountAmount = $this->item->getVolumeDiscount();
                $bundleDiscountAmount = $this->item->getBundleDiscount();
                $discountAmount = $bundleDiscountAmount > 0 ? $bundleDiscountAmount : $volumeDiscountAmount;
                $itemDiscount = str_replace(',', '', (string)$discountAmount);
            }
            $marketplaceProductTotal = floatval($marketplaceProductTotal - ($itemDiscount == "" ? 0 : $itemDiscount));

        } elseif ($attributeSetName == 'FXOPrintProducts' && $product->getTypeId() == Type::TYPE_BUNDLE) {
            $productName = $product->getName();
            $imgSrc = $imageObj->getUrl();
            $childrenProducts = $this->item->getChildren();
            foreach ($childrenProducts as $childrenProduct) {
                $childExternalProd = (array)$this->productInfoHandler->getItemExternalProd($childrenProduct);

                $childProductJson = (array)$this->productInfoHandler->getInfoBuyRequest($childrenProduct);
                $childExternalProductInstance = $this->getExternalProductInstance($childExternalProd);
                $childExternalProductInstance = $this->getExternalProductInstanceData(
                    $childExternalProd,
                    $childExternalProductInstance,
                    $childProductJson,
                    $isCustomize
                );

                $childrenExternalProductInstancies[$childrenProduct->getId()] = $childExternalProductInstance;
                $childrenItemsIds[] = $childrenProduct->getId();

                if(!isset($childExternalProd['contentAssociations']) || empty($childExternalProd['contentAssociations'])) {
                    $isBundleProductSetupCompleted = false;
                }
            }
        } elseif ($attributeSetName == 'FXOPrintProducts' || $isCustomize) {
            $externalProductInstance = $this->getExternalProductInstance($externalProd);
            $productName = $externalProd['userProductName'] ?? '';
            $imgSrc = $externalProd['preview_url'] ?? '';

            if (is_array($externalProductInstance)
                || (is_string($externalProductInstance) && $this->jsonValidator->isValid($externalProductInstance))) {
                $fxoProduct = is_string($externalProductInstance) ? json_decode($externalProductInstance, true)
                    : $externalProductInstance;
                $instanceId = $fxoProduct['instanceId']??'';
                $path = 'configurator/index/index?edit='. $instanceId;
                $qtydata = $this->getQtyData($productJson, $fxoProduct);
                $url = $this->getControllerUrlPath($path, $fxoProduct);
            }
        } elseif ($isCustomize) {
            $productName = $this->getProductName($externalProd, $product);
            $imgSrc = $externalProd['preview_url'];
        } else {
            $productName = $this->escaper->escapeHtml($this->item->getProduct()->getName());
            $imgSrc = $imageObj->getUrl();
            if ($attributeSetName == 'PrintOnDemand'
                && $this->toggleConfig->getToggleConfigValue('xmen_order_confirmation_fix')) {
                $externalProductInstance = $this->getExternalProductInstance($externalProd);
            }
        }

        $externalProductInstance = $this->getExternalProductInstanceData(
            $externalProd,
            $externalProductInstance,
            $productJson,
            $isCustomize
        );
        $finalLineTotal = 0;
        $isDiscountApplied = false;


        $rowTotal = str_replace(',', '', $this->item->getRowTotal());

        if (!$this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
            $volumeDiscountAmount = $this->item->getVolumeDiscount();
           $volumeDiscountAmount = ((float) $volumeDiscountAmount == 0) ? $this->item->getDiscount() : 0;
           $itemDiscount = str_replace(',', '', (string)$volumeDiscountAmount);
           $isDiscountApplied = ($volumeDiscountAmount > 0 && $volumeDiscountAmount !== null) ? true: false;
        } else {
            $volumeDiscountAmount = $this->item->getVolumeDiscount();
            $bundleDiscountAmount = $this->item->getBundleDiscount();
            $discountAmount = $bundleDiscountAmount > 0 ? $bundleDiscountAmount : $volumeDiscountAmount;
            $itemDiscount = str_replace(',', '', (string)$discountAmount);
            $isDiscountApplied = ($discountAmount > 0 && $discountAmount !== null) ? true: false;
        }

        $finalLineTotal = floatval($rowTotal - ($itemDiscount == "" ? 0 : $itemDiscount));
        $isItemPriceable = $this->uploadToQuoteViewModel->isItemPriceable(json_encode($productJson));

        // Non Standard Catalog Quantity Issue Fix
        $isPrintonDemandProduct = ($attributeSetName == 'PrintOnDemand') ? true : false;
        $isSiItemNonEditable = $this->uploadToQuoteViewModel->isSiItemEditBtnDisable($externalProd) && !$isPrintonDemandProduct;

        //Code for preview image call
        $product = $this->item->getProduct();
        $isCustomize = $this->dataHelper->getProductCustomAttributeValue($product->getId(), 'customizable');
        $newDocumentImage = 0;
        $newDocumentImageToggle = $this->toggleConfig->getToggleConfigValue('new_documents_api_image_preview_toggle');
        if (($product->getData('pod2_0_editable') && ($isCustomize)) || $newDocumentImageToggle){
            $newDocumentImage = 1;
        }

        // Code added to fetch allowed quantity product
        if ($product->getData('pod2_0_editable')
            && $this->toggleConfig
                ->getToggleConfigValue('fxo_cm_fixed_qty_handle_for_catalog_mvp')
            && isset($externalProd['quantityChoices'])) {

            $qtydata = $externalProd['quantityChoices'];
        }
        $isUnavailable = false;
        if($this->checkProductAvailability->isE441563ToggleEnabled()){
            $isUnavailable = $this->item->getProduct()->getData('is_unavailable')=="1";
        }

        $surcharge = 0;
        if (isset($additionalDataArray['mirakl_shipping_data']) &&
            isset($additionalDataArray['mirakl_shipping_data']->surcharge_amount)) {
            $surcharge = (float) $additionalDataArray['mirakl_shipping_data']->surcharge_amount;
        }

        $productPrice = $this->item->getRowTotal();

        if (!$this->productBundleConfigInterface->isTigerE468338ToggleEnabled()) {
            $volumeDiscount = $this->item->getData('volume_discount');
            $finalDiscountPrice = $volumeDiscount > 0 ? $productPrice - $volumeDiscount : 0;
            $isFinalDiscountPrice = $volumeDiscount > 0 ? 1 : 0;
        } else {
            $bundleDiscount = $this->item->getData('bundle_discount');
            $volumeDiscount = $this->item->getData('volume_discount');
            $appliedDiscount = $bundleDiscount > 0 ? $bundleDiscount : $volumeDiscount;
            $finalDiscountPrice = $appliedDiscount > 0 ? $productPrice - $appliedDiscount : 0;
            $isFinalDiscountPrice = $appliedDiscount > 0 ? 1 : 0;
        }

        //Code for preview image call
        return [
            "is_unavailable"=>$isUnavailable,
            'options' => $this->getOptionList(),
            'qty' => $this->item->getQty() * 1,
            'qtyData' => $qtydata,
            'item_id' => $this->item->getId(),
            'configure_url' => $url,
            'is_visible_in_site_visibility' => $this->item->getProduct()->isVisibleInSiteVisibility(),
            'product_attributesetname'=> $this->getProductAttributeName($attributeSetId),
            'product_id' => $this->item->getProduct()->getId(),
            'id' => $id,
            'version' => $version,
            'product_engine_url' => $this->peBackendConfig->getProductEngineUrl(),
            'product_name' => $productName,
            'is_customize' => $isCustomize,
            'product_sku' => $this->item->getProduct()->getSku(),
            'product_url' => $this->getProductUrl(),
            'product_has_url' => $this->hasProductUrl(),
            'product_price' => $this->checkoutHelper->formatPrice($this->item->getCalculationPrice()),
            'product_price_value' => $this->item->getCalculationPrice(),
            'price_including_qty_calculation' => $this->checkoutHelper->formatPrice($this->item->getRowTotal()),
            'final_discount_price' => $this->checkoutHelper->formatPrice($finalDiscountPrice),
            'is_final_discount_price' => $isFinalDiscountPrice,
            'product_image' => [
                'src' => $imgSrc,
                'alt' => $imageObj->getLabel(),
                'width' => $imageObj->getWidth(),
                'height' => $imageObj->getHeight(),
            ],
            'canApplyMsrp' => $this->msrpHelper->isShowBeforeOrderConfirm($this->item->getProduct())
                && $this->msrpHelper->isMinimalPriceLessMsrp($this->item->getProduct()),
            'message' => $this->item->getMessage(),
            'instance_id' => $this->item->getInstanceId(),
            'externalProductInstance' => $externalProductInstance,
            'childrenExternalProductInstance' => $childrenExternalProductInstancies,
            'childrenItemsIds' => $childrenItemsIds,
            'subtotal' => $isItemPriceable ? $this->checkoutHelper->convertPrice($finalLineTotal, true) : '$--.--',
            'is_discount_applied' => $isDiscountApplied,
            'is_third_party_product' => $isThirdPartyProduct,
            'seller_item_shop_id' => $this->item->getMiraklShopId(),
            'seller_item_alt_name' => $shopAltName,
            'can_edit_reorder' => $canEditReorder,
            'sku' => $sku,
            'additional_data' => $additionalData,
            'marketplace_product_price' => $this->checkoutHelper->formatPrice($marketplaceProductUnitPrice),
            'marketplace_product_subtotal' => $this->checkoutHelper->convertPrice($marketplaceProductTotal, true),
            'isItemPriceable' => $isItemPriceable,
            'isNonStandardFile' => $this->uploadToQuoteViewModel->isNonStandardFile(json_encode($productJson)),
            'nonStandardImageUrl' => $this->uploadToQuoteViewModel->getNonStandardImageUrl(),
            'isSiItemNonEditable' => $isSiItemNonEditable,
            'newDocumentImage;'=> $newDocumentImage,
            'punchout_enable' => $punchoutEnabled,
            'punchout_url' => $punchoutUrl,
            'surcharge' => $surcharge > 0 ? $this->checkoutHelper->formatPrice($surcharge) : '',
            'productContentAssociation' => $this->toggleConfig->getToggleConfigValue('techtitans_B2353473_remove_legacy_doc_api_call_on_cart')
            ? (isset($productJson['external_prod'][0]) ? $productJson['external_prod'][0] : '')
            : '',
            'isDyeSubExpired'=>$this->canvasParams->isExpired($this->item),
            'isDyeSubEditDisable'=>$this->canvasParams->isDyeSubEditEnabled(),
            'isDyeSubProduct'=>(bool) $this->item->getProduct()->getData('is_customer_canvas'),
            'isDyeSubEnable'=>$this->canvasParams->isDyeSubEnabled()
                ? (isset($productJson['external_prod'][0]) ? $productJson['external_prod'][0] : '')
                : '',
            'product_type' => $product->getTypeId(),
            'isBundleProductSetupCompleted' => $isBundleProductSetupCompleted
        ];
    }

    protected function getProductAttributeName($attributeSetId)
    {
        $attributeSetRepository = $this->attributeSetRepositoryInterface->get($attributeSetId);

        return $attributeSetRepository->getAttributeSetName();
    }

    /**
     * Get list of all options for product
     *
     * @return array
     */
    protected function getOptionList()
    {
        return $this->configurationPool->getByProductType($this->item->getProductType())->getOptions($this->item);
    }

    /**
     * Returns product for thumbnail.
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function getProductForThumbnail()
    {
        return $this->itemResolver->getFinalProduct($this->item);
    }

    /**
     * Returns product.
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function getProduct()
    {
        return $this->item->getProduct();
    }

    /**
     * Get item configure url
     *
     * @return string
     */
    protected function getConfigureUrl()
    {
        return $this->urlBuilder->getUrl('configurator/index/index');
    }

    /**
     * Check Product has URL
     *
     * @return bool
     */
    protected function hasProductUrl()
    {
        if ($this->item->getRedirectUrl()) {
            return true;
        }

        $product = $this->item->getProduct();
        $option = $this->item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $this->isVisibleProduct($product);
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    protected function getProductUrl()
    {
        if ($this->item->getRedirectUrl()) {
            return $this->item->getRedirectUrl();
        }

        $product = $this->item->getProduct();
        $option = $this->item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * Get item configure url
     *
     * @return bool
     */
    protected function isVisibleProduct($product)
    {
        $status = false;
        if ($product->isVisibleInSiteVisibility()) {
            $status = true;
        } else {
            if ($product->hasUrlDataObject()) {
                $data = $product->getUrlDataObject();
                if (in_array($data->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                    $status = true;
                }
            }
        }

        return $status;
    }

    /**
     * Get item Qty data
     */
    protected function getQtyData($productJson, $fxoProduct)
    {
        if (isset($productJson['quantityChoices'])) {
            return $productJson['quantityChoices'];
        } else {
            return $fxoProduct['fxoProductInstance']['quantityChoices']??'';
        }
    }

    /**
     * Get controller redirect url
     */
    protected function getControllerUrlPath($path, $fxoProduct)
    {
        $url = $this->urlBuilder->getUrl($path);

        if (isset($fxoProduct['fxoProductInstance']) && isset(['productConfig']['designProduct']['designId'])) {
            $designProductId = $fxoProduct['fxoProductInstance']['productConfig']['designProduct']['designId'];
            $url = $this->urlBuilder->getUrl("canva/index/index") . "?designId=$designProductId";
        } elseif (isset($fxoProduct['design']) && !empty($fxoProduct['design'])) {
            $designProductId = $fxoProduct['designProduct']['designId'];
            $url = $this->urlBuilder->getUrl("canva/index/index") . "?designId=$designProductId";
        }

        return $url;
    }

    /**
     * Get External Product Instance
     */
    protected function getExternalProductInstance($externalProd)
    {
        $externalProductInstance = '';
        if (isset($externalProd['fxo_product'])) {
            $externalProductInstance = $externalProd['fxo_product'] ?? null;
        } elseif (is_array($externalProd)) {
            $externalProductInstance = $externalProd ?? null;
        }

        return $externalProductInstance;
    }

    /**
     * Get external product instance data
     */
    protected function getExternalProductInstanceData($externalProd, $externalProductInstance, $productJson, $isCustomize = false)
    {
        if (!isset($externalProd['fxo_product']) && !empty($externalProductInstance)) {
            $externalProductInstance['fileManagementState'] = $productJson['fileManagementState'] ?? [];
            $externalProductInstance['productRateTotal'] = $productJson['productRateTotal'] ?? [];
            $externalProductInstance['productConfig'] = $productJson['productConfig'] ?? [];
            if (!empty($externalProductInstance['productConfig'])) {
                $externalProductInstance['productConfig']->product = $externalProd ?? [];
            }
            $externalProductInstance['quantityChoices'] = $productJson['quantityChoices'] ?? [];
        }
        if ($isCustomize) {
            $externalProductInstance['expressCheckout'] = $productJson['expressCheckout'] ?? false;
            $externalProductInstance['isEditable'] = $productJson['isEditable'] ?? false;
            $externalProductInstance['catalogDocumentMetadata'] = $productJson['catalogDocumentMetadata'] ?? [];
            $externalProductInstance['isEdited'] = $productJson['isEdited'] ?? false;
            $externalProductInstance['customDocState'] = $productJson['customDocState'] ?? [];
        }
        return $externalProductInstance;
    }

    /**
     * Get product name
     */
    protected function getProductName($externalProd, $product)
    {
        $productName = null;
        if (isset($externalProd['fxo_product'])) {
            $fxoProduct = json_decode($externalProd['fxo_product'] ?? "{}")->fxoProductInstance ?? [];
            $productName = $fxoProduct ? $fxoProduct->name : $product->getName();
        } else {
            $productName = $externalProd['userProductName'] ?? $product->getName();
        }

        return $productName;
    }
}
