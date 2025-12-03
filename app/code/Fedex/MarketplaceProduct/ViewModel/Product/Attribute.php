<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\ViewModel\Product;

use Fedex\Catalog\Model\Config as CatalogConfig;
use Fedex\MarketplaceCheckout\Helper\Data as CheckoutHelper;
use Fedex\MarketplaceCheckout\Helper\Data as ToggleHelperData;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\MarketplaceProduct\Api\NonCustomizableProductInterface;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Fedex\MarketplaceProduct\Helper\Data;
use Fedex\MarketplaceProduct\Model\Config as ToggleConfig;
use Fedex\MarketplaceProduct\Model\Config\Backend\MarketplaceProduct;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Data as CataloHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Model\Configuration;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Mirakl\Connector\Model\Offer;

class Attribute implements ArgumentInterface
{
    const REQUEST_URL = 'marketplacepunchout/index/pkce';

    /**
     * @param Registry $registry
     * @param ShopManagementInterface $shopManagement
     * @param Data $helper
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param UrlInterface $urlBuilder
     * @param CatalogConfig $catalogConfig
     * @param NonCustomizableProductInterface $nonCustomizableProductModel
     * @param CheckoutHelper $checkoutHelper
     * @param CataloHelper $catalogHelper
     * @param CollectionFactory $category
     * @param SsoConfiguration $ssoConfiguration
     * @param MarketplaceProduct $marketplaceProduct
     * @param ToggleHelperData $toggleHelperData
     * @param Json $jsonSerializer
     */
    public function __construct(
        private Registry                   $registry,
        private ShopManagementInterface    $shopManagement,
        private Data                       $helper,
        private ProductRepositoryInterface $productRepository,
        protected PriceCurrencyInterface   $priceCurrency,
        private UrlInterface               $urlBuilder,
        private CatalogConfig              $catalogConfig,
        private NonCustomizableProductInterface $nonCustomizableProductModel,
        private CheckoutHelper             $checkoutHelper,
        private CataloHelper               $catalogHelper,
        private CollectionFactory          $category,
        private SsoConfiguration           $ssoConfiguration,
        private MarketplaceProduct         $marketplaceProduct,
        private ToggleHelperData           $toggleHelperData,
        private Json                       $jsonSerializer,
        private ToggleConfig    $toggleConfig,
        private Configuration   $configuration
    ) {
    }

    /**
     * Get Category attributes
     *
     * @param ProductInterface $product
     * @return array
     * @throws LocalizedException
     */
    public function getCategoryAttributes(ProductInterface $product): array
    {
        return $this->marketplaceProduct->getCategoryAttributes($product);
    }

    /**
     * Get Reference FromStore ToCategory Toggle Enabled.
     *
     * @return bool
     */
    public function isMoveReferenceFromStoreToCategoryToggleEnabled(): bool
    {
        return (bool)$this->toggleHelperData->isMoveReferenceFromStoreToCategoryToggleEnabled();
    }

    /**
     * Check if product has offers
     *
     * @return bool
     */
    public function hasAvailableOffersForProduct(): bool
    {
        $product = $this->getProduct();
        return $this->helper->hasAvailableOffersForProduct($product);
    }

    /**
     * Return message of a product that has no associated offers
     *
     * @return mixed
     */
    public function getOfferErrorRelationMessage()
    {
        return $this->helper->getOfferErrorRelationMessage();
    }

    /**
     * Get shop By product
     *
     * @param Product $product
     * @return ShopInterface
     */
    public function getShopByProduct(Product $product): ShopInterface
    {
        if ($this->isEssendantToggleEnabled() && $this->isConfigurableProduct()) {
            $childProduct = $product->getTypeInstance()->getUsedProducts($product)[0] ?? null;
            if ($childProduct) {
                $product = $childProduct;
            }
        }
        return $this->shopManagement->getShopByProduct($product);
    }

    /**
     * Get all offers from mirakl
     *
     * @return Offer[]
     */
    public function getAllOffers(): array
    {
        $product = $this->getProduct();
        return $this->helper->getAllOffers($product);
    }

    /**
     * Get best offer
     *
     * @return Offer|null
     */
    public function getBestOffer(): ?Offer
    {
        $product = $this->getProduct();
        return $this->helper->getBestOffer($product);
    }

    /**
     * Returns current product
     *
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->registry->registry('product');
    }

    /**
     * Get format currency
     *
     * @param $amount
     * @param $includeContainer
     * @param $precision
     *
     * @return string
     */
    public function formatCurrency(
        $amount,
        $includeContainer = true,
        $precision = PriceCurrencyInterface::DEFAULT_PRECISION
    ): string
    {
        return $this->priceCurrency->convertAndFormat($amount, $includeContainer, $precision);
    }

    /**
     * Return Marketplace url and new punchout flow flag
     *
     * @return mixed
     */
    public function getMarketplaceInfo(): array
    {
        $ajaxUrl = '';
        $punchoutFlowEnhancement = false;

        $offer = $this->getBestOffer();
        $additionalInfo = $offer->getAdditionalInfo();
        $categoryPunchout = $this->getProduct()->getCategoryPunchout();
        $skuInfo = [
            'sku' => $this->getProduct()->getSku(),
            'offer_id' => $offer->getId(),
            'seller_sku' => $categoryPunchout ? $additionalInfo['external_category_id'] : $additionalInfo['shop_sku']
        ];

        $url = (string)$this->urlBuilder->getUrl('marketplacepunchout/index/index', $skuInfo);

        if ($this->nonCustomizableProductModel->isMktCbbEnabled()) {
            $shopCustomAttributes = $this->helper->getCustomAttributes([$offer]);

            if (isset($shopCustomAttributes['punchout-flow-enhancement'])) {
                $punchoutFlowEnhancement = $shopCustomAttributes['punchout-flow-enhancement'] === 'true';
            }

            if ($punchoutFlowEnhancement) {
                $ajaxUrl = $this->ssoConfiguration->getHomeUrl() . SELF::REQUEST_URL;
            }
        }

        return [
            'url' => $url,
            'punchout_enable' => $punchoutFlowEnhancement,
            'punchout_url' => $ajaxUrl,
            'sku_info' => $skuInfo,
            'cbb_toggle' => $this->nonCustomizableProductModel->isMktCbbEnabled()
        ];
    }

    /**
     * Get minimum discount
     *
     * @return string
     */
    public function getDiscount(): string
    {
        $discountPrice = $this->getDiscountRanges() ? $this->getDiscountPrice() : $this->getOriginPrice();
        $originPrice = $this->getOriginPrice();
        return min($discountPrice, $originPrice);
    }

    /**
     * Get discount price
     *
     * @return string
     */
    public function getDiscountPrice(): string
    {
        return number_format(floatval($this->getBestOffer()->getDiscountPrice()), 2, ".");
    }

    /**
     * Get Original price
     *
     * @return string
     */
    public function getOriginPrice(): string
    {
        return number_format(floatval($this->getBestOffer()->getOriginPrice()), 2, ".");
    }

    /**
     * Get discount saved
     *
     * @return bool|string
     */
    public function getDiscountSaved()
    {
        if (!$this->getDiscountRanges()) {
            return false;
        }
        if ($this->getDiscountPrice() < $this->getOriginPrice()) {
            $discountComparison = $this->getOriginPrice() - $this->getDiscountPrice();
            $totalDiscount = number_format(floatval($discountComparison), 2, '.');
            return $this->formatCurrency($totalDiscount);
        }
        return false;
    }

    /**
     * Get Minimum origin price
     *
     * @return mixed
     */
    public function getMinOriginPrice(): mixed
    {
        return $this->getBestOffer()->getPriceRanges()[0]->getPrice();
    }

    /**
     * Get Maximum origin price
     *
     * @return mixed
     */
    public function getMaxOriginPrice(): mixed
    {
        return $this->getBestOffer()->getPriceRanges()[count($this->getBestOffer()->getPriceRanges()) - 1]->getPrice();
    }

    /**
     * Get Max origin quantities
     *
     * @return mixed
     */
    public function getMaxOriginQuantity(): mixed
    {
        return $this->getBestOffer()
            ->getPriceRanges()[count($this->getBestOffer()->getPriceRanges()) - 1]->getQuantityThreshold();
    }

    /**
     * Get discount ranges
     *
     * @return false|string[]
     */
    public function getDiscountRanges()
    {
        $discountRanges = $this->getBestOffer()->getDiscountRanges();
        if (!empty($discountRanges)) {
            return explode(",", $discountRanges);
        }
        return false;
    }

    /**
     * Get maximum discount
     *
     * @return string[]
     */
    public function getMaxDiscount(): array
    {
        return explode("|", $this->getDiscountRanges()[count($this->getDiscountRanges()) - 1]);
    }

    /**
     * Get minimum discount position
     *
     * @return string[]
     */
    public function getMinDiscount(): array
    {
        return explode("|", $this->getDiscountRanges()[0]);
    }

    /**
     * Get minimum discount price
     *
     * @return string
     */
    public function getMinDiscountPrice(): string
    {
        return $this->getMinDiscount()[1];
    }

    /**
     * Get Min discount quantity
     *
     * @return string
     */
    public function getMinDiscountQuantity(): string
    {
        return $this->getMinDiscount()[0];
    }

    /**
     * Get max discount price
     *
     * @return string
     */
    public function getMaxDiscountPrice(): string
    {
        return $this->getMaxDiscount()[1];
    }

    /**
     * Get max discount and quantity
     *
     * @return string
     */
    public function getMaxDiscountQuantity(): string
    {
        return $this->getMaxDiscount()[0];
    }

    /**
     * Has Discount over origin price
     *
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->getMinDiscountPrice() < $this->getMinOriginPrice();
    }

    /**
     * Has discount and price ranges.
     *
     * @return bool
     */
    public function hasDiscountAndPriceRanges(): bool
    {
        if ($this->getDiscountRanges() && $this->hasPriceRanges()) {
            return true;
        }
        return false;
    }

    /**
     * Has price ranges
     *
     * @return \Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection
     */
    public function hasPriceRanges(): \Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection
    {
        return $this->getBestOffer()->getPriceRanges();
    }

    /**
     * Has discount for more quantities
     *
     * @return bool
     */
    public function hasDiscountForMoreQuantities(): bool
    {
        return $this->getMaxDiscountQuantity() > $this->getMinDiscountQuantity();
    }

    /**
     * Has discount per quantities price
     *
     * @return bool
     */
    public function hasDiscountPerQtyPrice(): bool
    {
        return $this->getMaxDiscountPrice() < $this->getMaxOriginPrice();
    }

    /**
     * Check if Product ˜Has Canva Design˜ is enabled
     *
     * @return bool
     */
    public function productHasCanvaDesign(): bool
    {
        return (bool)$this->getProduct()->getData('has_canva_design');
    }

    /**
     * Check if Toggle for 3P Unit Cost is enabled
     *
     * @return bool
     */
    public function getTigerDisplayUnitCost3P1PProducts(): bool
    {
        return $this->catalogConfig->getTigerDisplayUnitCost3P1PProducts();
    }

    /**
     * Check if Toggle for 3P Unit Cost is enabled + If product has the mandatory field filled
     *
     * @return bool
     */
    public function isNewUnitCostAvailable(): bool
    {
        $product = $this->getProduct();
        return $this->catalogConfig->getTigerDisplayUnitCost3P1PProducts() && $product->getData('unit_cost');
    }

    /**
     * Return Unit Cost formatted with container
     *
     * @return string
     */
    public function getProductUnitCost(): string
    {
        $product = $this->getProduct();
        $unitCost = $product->getData('unit_cost');

        return $this->formatCurrency($unitCost);
    }

    /**
     * Check if Toggle for 3P Unit Cost is enabled + basePrice and BaseQuantity is available
     *
     * @return bool
     */
    public function isBasePriceAndBaseQuantityAvailable(): bool
    {
        $product = $this->getProduct();
        return $product->getData('base_quantity') && $product->getData('base_price');
    }

    /**
     * Return Base Quantity
     *
     * @return bool
     */
    public function getBaseQuantity(): string
    {
        $product = $this->getProduct();
        return $product->getData('base_quantity');
    }

    /**
     * Return Base Price formatted
     *
     * @return bool
     */
    public function getBasePrice(): string
    {
        $product = $this->getProduct();
        $basePrice = $product->getData('base_price');
        return $this->formatCurrency($basePrice);
    }

    /**
     * Return Base Price formatted
     *
     * @return string|null
     */
    public function getBasePriceWithoutFormat(): string|null
    {
        $product = $this->getProduct();
        return $product->getData('base_price');
    }

    /**
     * @return bool
     */
    public function isProductHasOffer()
    {
        if ($this->getBestOffer() !== null) {
            return true;
        }
        return false;
    }

    /**
     * Return mixQty, maxQt and punchoutEnabled to be used in the template
     * @param $offer
     * @return array
     */
    public function getMinMaxPunchoutInfo($offer, $product)
    {
        return $this->nonCustomizableProductModel->getMinMaxPunchoutInfo($offer, $product);
    }

    /**
     * @throws LocalizedException
     */
    public function getCategoryProductAdditionalInstructions(): string
    {
        $additionalInstructions = '';

        if ($this->isEssendantToggleEnabled()) {
            $product = $this->catalogHelper->getProduct();
            if ($product) {
                $data = $this->getCategoryAttributes($product);
                if (!empty($data['product_detail_page_additional_information'])) {
                    $additionalInstructions = $data['product_detail_page_additional_information'];
                }
            }
        }

        return $additionalInstructions;
    }

    /**
     * Get Marketplace Product Helper
     *
     * @return CatalogConfig
     */
    public function getCatalogConfig(): CatalogConfig
    {
        return $this->catalogConfig;
    }

    /**
     * @return bool
     */
    public function isConfigurableProduct(): bool
    {
        $product = $this->getProduct();
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isEssendantToggleEnabled(): bool
    {
        return $this->checkoutHelper->isEssendantToggleEnabled();
    }

    /**
     * Get offer data with variant details.
     *
     * @param Product[] $offers
     * @param Product $parentProduct
     * @return array
     * @throws NoSuchEntityException
     */
    public function getOfferDataWithVariantDetails(array $offers, Product $parentProduct): array
    {
        if (!$this->isEssendantToggleEnabled()) {
            return [];
        }

        $result = [];
        $wrongMinMaxQtyToggle = $this->toggleConfig->isConfigurableMinMaxWrongQtyToggleEnabled();

        foreach ($offers as $offer) {
            $sku = $offer->getProductSku();
            $result[$sku] = $this->buildOfferData($offer, $parentProduct, $wrongMinMaxQtyToggle);
        }

        return $result;
    }

    private function buildOfferData($offer, Product $parentProduct, bool $wrongMinMaxQtyToggle): array
    {
        $attributes = $this->getProductAttributes($offer, $parentProduct);
        $offerData = [
            'offer-id' => $offer->getId(),
            'final-price' => $offer->getFinalPrice(),
            'attributes' => $attributes,
        ];

        if ($wrongMinMaxQtyToggle) {
            $offerData['min-qty'] = $this->getMinSaleQty($offer);
            $offerData['max-qty'] = $this->getMaxSaleQty($offer);
        }

        return $offerData;
    }

    private function getMinSaleQty($offer): int
    {
        $minSaleQty = (int) $offer->getMinOrderQuantity();
        return ($minSaleQty == 0) ? (int) $this->configuration->getMinSaleQty() : $minSaleQty;
    }

    private function getMaxSaleQty($offer): int
    {
        $maxSaleQty = (int) $offer->getMaxOrderQuantity();
        return ($maxSaleQty == 0) ? (int)$this->configuration->getMaxSaleQty() : $maxSaleQty;
    }

    /**
     * Get product attributes for a given offer.
     *
     * @param $offer
     * @param Product $parentProduct
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductAttributes($offer, Product $parentProduct): array
    {
        $attributeOptionsForChild = [];
        $superAttributes = $this->getConfigurableProductAttributes($parentProduct);
        $product = $this->productRepository->get($offer->getProductSku());
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            if (in_array($attribute->getId(), $superAttributes)) {
                $attributeOptionsForChild[$attribute->getId()] = $product->getData($attribute->getAttributeCode());
            }
        }
        return $attributeOptionsForChild;
    }

    /**
     * Get configurable product attributes.
     *
     * @param Product $parentProduct
     * @return array
     */
    public function getConfigurableProductAttributes(Product $parentProduct): array
    {
        $superAttributes = [];
        $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);
        foreach ($configurableAttributes as $configurableAttribute) {
            $superAttributes[] = $configurableAttribute->getData('attribute_id');
        }
        return $superAttributes;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getProductSpecifications()
    {
        $product = $this->getProduct();
        $specifications = [];
        if ($this->isConfigurableProduct()) {
            $childProducts = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($childProducts as $childProduct) {
                $specificationValue = $childProduct->getData('product_specifications');
                if($specificationValue!==null){
                    $specifications[$childProduct->getSku()] = $specificationValue;
                }
            }
        } else {
            $specificationValue = $product->getData('product_specifications');
            if($specificationValue !== null){
                $specifications[$product->getSku()] = $specificationValue;
            }
        }
        return $specifications;
    }

    /**
     * @return bool
     */
    public function canMovePageTitleToNewLocation():bool {
        return $this->helper->canMovePageTitleToNewLocation();
    }

    /**
     * @param Product $product
     * @return \Magento\Catalog\Model\Category|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getDirectChildCategoryWithChildren(Product $product): ?\Magento\Catalog\Model\Category
    {
        $categoryIds = $product->getCategoryIds();
        if (empty($categoryIds)) {
            return null;
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
        $categoryCollection = $this->category->create()
            ->addAttributeToSelect(['name', 'parent_id', 'is_active'])
            ->addFieldToFilter('entity_id', ['in' => $categoryIds])
            ->addIsActiveFilter();

        $rootCategoryId = $product->getStore()->getRootCategoryId();

        foreach ($categoryCollection as $category) {
            if ((int)$category->getParentId() === (int)$rootCategoryId && $category->hasChildren()) {
                return $category;
            }
        }
        return null;
    }

    /**
     * @param $product
     * @return bool
     */
    public function isProductFxoNonCustomizableProduct($product = null)
    {
        $product = $product ?? $this->getProduct();
        return $this->nonCustomizableProductModel->checkIfNonCustomizableProduct($product);
    }

    public function isTigerTeamD232505ToggleEnabled(): bool
    {
        return $this->toggleHelperData->isTigerTeamD232505ToggleEnabled();
    }
}
