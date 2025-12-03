<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Iago Ferreira Lima <iago.lima.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Helper\Output;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_CATALOG_FIRST_PARTY_GALLERY_SETTINGS = 'fedex/one_p_product/gallery';
    public const XML_CATALOG_IN_STORE_PRODUCT_GALLERY_SETTINGS = 'fedex/in_store_product/gallery';
    const WYSIWYG_ATTRIBUTE_LIST_CONFIG = 'fedex/wysiwyg_editor/attribute_list';
    const WYSIWYG_EDITOR_TOOLBAR_CONFIG = 'fedex/wysiwyg_editor/toolbar_config';
    const WYSIWYG_EDITOR_VALID_ELEMENTS = 'fedex/wysiwyg_editor/valid_elements';
    const WYSIWYG_EDITOR_EXTENDED_VALID_ELEMENTS = 'fedex/wysiwyg_editor/extended_valid_elements';
    const WYSIWYG_EDITOR_VALID_STYLES = 'fedex/wysiwyg_editor/valid_styles';
    const TIGER_DISPLAY_UNIT_COST_3P_1P_PRODUCTS = 'tiger_e422224dispaly_unit_cost_for_3p_and_1p_products';
    const TIGER_B2315919 = 'tiger_b_2315919';

    /** @var string */
    private const XML_PATH_PRICE_PERCEPTION_TOGGLE = 'price_perception_toggle_E455387';

    /** @var string */
    private const XML_PATH_PRICE_PERCEPTION_DISCLAIMER_TEXT_1P
        = 'fedex/one_p_product/price_perception_disclaimer_text_1p';

    /** @var string */
    private const XML_PATH_PRICE_PERCEPTION_DISCLAIMER_TEXT_3P
        = 'fedex/three_p_product/price_perception_disclaimer_text_3p';

    public function __construct(
        private Output $outputHelper,
        protected ScopeConfigInterface $scopeConfig,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @param Product $product
     * @param string $attributeCode
     * @return string
     * @throws LocalizedException
     */
    public function formatAttribute(Product $product, string $attributeCode): string
    {
        return $this->outputHelper->productAttribute(
            $product,
            $product->getData($attributeCode),
            $attributeCode
        ) ?? '';
    }

    public function getPdpGallerySettings(?int $store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_CATALOG_FIRST_PARTY_GALLERY_SETTINGS,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    public function getPdpGalleryInStoreProductSettings(?int $store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_CATALOG_IN_STORE_PRODUCT_GALLERY_SETTINGS,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    /**
     * @return array
     */
    public function wysiwygAttributeList(): array
    {
        return ($this->scopeConfig->getValue(self::WYSIWYG_ATTRIBUTE_LIST_CONFIG))?
            explode(',', $this->scopeConfig->getValue(self::WYSIWYG_ATTRIBUTE_LIST_CONFIG)) : [];
    }

    /**
     * @return string
     */
    public function wysiwygToolbarConfig(): string
    {
        return ($this->scopeConfig->getValue(self::WYSIWYG_EDITOR_TOOLBAR_CONFIG))?
            $this->scopeConfig->getValue(self::WYSIWYG_EDITOR_TOOLBAR_CONFIG) : '';
    }

    /**
     * @return string
     */
    public function getWysiwygValidElements():string
    {
        return ($this->scopeConfig->getValue(self::WYSIWYG_EDITOR_VALID_ELEMENTS))?
            $this->scopeConfig->getValue(self::WYSIWYG_EDITOR_VALID_ELEMENTS) : '';
    }

    /**
     * @return string
     */
    public function getWysiwygExtendedValidElements():string
    {
        return ($this->scopeConfig->getValue(self::WYSIWYG_EDITOR_EXTENDED_VALID_ELEMENTS))?
            $this->scopeConfig->getValue(self::WYSIWYG_EDITOR_EXTENDED_VALID_ELEMENTS) : '';
    }

    /**
     * @return string
     */
    public function getWysiwygValidStyles():string
    {
        return ($this->scopeConfig->getValue(self::WYSIWYG_EDITOR_VALID_STYLES))?
            $this->scopeConfig->getValue(self::WYSIWYG_EDITOR_VALID_STYLES) : '';
    }

    /**
     * Get toggle value for Display Unit Cost 3P 1P Products
     *
     * @return bool
     */
    public function getTigerDisplayUnitCost3P1PProducts()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_DISPLAY_UNIT_COST_3P_1P_PRODUCTS);
    }

    /**
     * Get toggle value for B-2315919 RT-ECVS-Unpublish products from folders or without folders should not be visible in search dropdown and search page for shared catalog
     *
     * @return bool
     */
    public function getTigerB2315919Toggle()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_B2315919);
    }


    /**
     * @return bool
     */
    public function isPricePerceptionToggleEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(
            self::XML_PATH_PRICE_PERCEPTION_TOGGLE
        );
    }

    /**
     * @return string
     */
    public function getPricePerceptionDisclaimerText1p(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_PERCEPTION_DISCLAIMER_TEXT_1P,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * @return string
     */
    public function getPricePerceptionDisclaimerText3p(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_PERCEPTION_DISCLAIMER_TEXT_3P,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }
}
