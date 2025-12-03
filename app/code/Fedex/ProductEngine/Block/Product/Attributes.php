<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Block\Product;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ProductCustomAtrribute\Model\Config\Backend as CanvaBackendConfig;
use Fedex\ProductEngine\Model\Config\Backend as PeBackendConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\View as BaseView;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;

class Attributes extends BaseView
{
    protected const ATTRIBUTE_CODE_HAS_CANVA_DESIGN = 'has_canva_design';
    protected const MAX_ATTRIBUTES_AVAILABLE = 6;
    protected const QUANTITY_ATTRIBUTE = 'quantity';
    protected const TEXTBOX_ATTRIBUTE_OPTION = 'Textbox';
    protected const LABEL = 'label';

    /**
     * @param Context $context
     * @param EncoderInterface $urlEncoder
     * @param JsonEncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param Attribute $attribute
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param PeBackendConfig $peBackendConfig
     * @param CanvaBackendConfig $canvaBakendConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $urlEncoder,
        JsonEncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        //NOSONAR
        protected Attribute $attribute,
        protected ProductAttributeRepositoryInterface $attributeRepository,
        protected PeBackendConfig $peBackendConfig,
        protected CanvaBackendConfig $canvaBakendConfig,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected SortOrderBuilder $sortOrderBuilder,
        protected ToggleConfig $toggleConfig,
        array $data = []
    ) {
        parent::__construct($context, $urlEncoder, $jsonEncoder, $string, $productHelper, $productTypeConfig, $localeFormat, $customerSession, $productRepository, $priceCurrency, $data);//NOSONAR
    }

    public function getVisibleAttributes()
    {
        $product = $this->getProduct();
        $attributeList = [];

        $visibleAttributes = $product->getData('visible_attributes');
        if (!$visibleAttributes) {
            return $attributeList;
        }

        $visibleAttributes = array_slice(explode(',', $visibleAttributes), 0, self::MAX_ATTRIBUTES_AVAILABLE);
        $visibleAttributesOrdered = $this->getAttributes($visibleAttributes);
        foreach ($visibleAttributesOrdered->getItems() as $attribute) {

            $attributeOptionsId = $product->getData($attribute->getAttributeCode());
            if (!$attributeOptionsId) {
                continue;
            }

            $attributeSource = $attribute->getSource();
            if (!($attributeSource instanceof Table)) {
                continue;
            }

            $attributeOptions = $attributeSource->getSpecificOptions($attributeOptionsId, false);

            $matchedDefault = '';
            preg_match('/default-\d+/', $attributeOptionsId, $matchedDefault);

            if ($attributeOptions) {
                $this->setDefaultOption($attributeOptions, $matchedDefault, $attribute->getDefaultValue());
            }

            $attributeList[] = [
                'attribute' => $attribute,
                'options'   => $attributeOptions
            ];
        }

        return $attributeList;
    }

    public function hasCanvaLink(): bool
    {
        if ($attribute =  $this->getProduct()->getCustomAttribute(self::ATTRIBUTE_CODE_HAS_CANVA_DESIGN)) {
            return $attribute->getValue() == '1';
        }
        return false;
    }

    public function getDefaultCanvaLink(): string
    {
        return (string)$this->canvaBakendConfig->getCanvaLink();
    }

    public function getProductEngineUrl(): string
    {
        return (string)$this->peBackendConfig->getProductEngineUrl();
    }

    public function getMinSaleQty()
    {
        return $this->getMinimalQty($this->getProduct());
    }

    public function getMaxSaleQty()
    {
        $product = $this->getProduct();
        $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());

        return $stockItem->getMaxSaleQty();
    }

    protected function getAttributes($attributeCodeList)
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('position')
            ->setAscendingDirection()
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('attribute_code', $attributeCodeList, 'in')
            ->addFilter('frontend_input', 'multiselect')
            ->addSortOrder($sortOrder)
            ->create();

        return $this->attributeRepository->getList($searchCriteria);
    }

    protected function setDefaultOption(&$attributeOptionsLoaded, $productDefault, $attributeDefault)
    {
        $default = !empty($productDefault) ? str_replace('default-', '', $productDefault[0]) : $attributeDefault;
        $defaultKey = array_search($default, array_column($attributeOptionsLoaded, 'value'));
        if ($defaultKey !== false) {
            $attributeOptionsLoaded[$defaultKey]['selected'] = true;
        } else {
            $attributeOptionsLoaded[0]['selected'] = true;
        }
    }

    /**
     * Get Quantity attribute code
     * @return string
     */
    public function getQuantityAttributeCode(): string
    {
        return self::QUANTITY_ATTRIBUTE;
    }

    /**
     * Get Textbox attribute option label
     * @return string
     */
    public function getTextboxOptionLabel(): string
    {
        return self::TEXTBOX_ATTRIBUTE_OPTION;
    }

    /**
     * @return string
     */
    public function getLabelText(): string
    {
        return self::LABEL;
    }

    public function getOptionHtml($attributeCode, $quantityAttribute, $option, $labelText, $textboxLabel): string
    {
        $optionHtml = '';
        $selected = isset($option['selected']) ? 'selected' : '';
        if ($attributeCode === $quantityAttribute) {
            if ($option[$labelText] !== $textboxLabel) {
                $optionHtml = "<li class='option " . $selected . "' data-label='" . htmlspecialchars($option[$labelText]) . "' data-value='" . $option['choice_id'] . "'>" . $option[$labelText] . "</li>";
            }
        } else {
            $selected = '';
            if (isset($option['selected'])) {
                $selected = 'selected';
            }
            $optionHtml = "<li class='option " . $selected . "' data-label='" . htmlspecialchars($option[$labelText]) . "' data-value='" . $option['choice_id'] . "'>" . $option[$labelText] . "</li>";
        }
        return $optionHtml;
    }

    /**
     * @return ProductInterface
     */
    public function getPeProductId()
    {
        return $this->getProduct()->getData('pe_product_id');

    }

}
