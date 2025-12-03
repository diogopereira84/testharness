<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Block\Product\Attributes;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ProductCustomAtrribute\Model\Config\Backend as CanvaBackendConfig;
use Fedex\ProductEngine\Block\Product\Attributes;
use Fedex\ProductEngine\Model\Catalog\Bundle\Products as ProductsBundle;
use Fedex\ProductEngine\Model\Config\Backend as PeBackendConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;

/**
 * Bundle Block for Attributes
 */
class Bundle extends Attributes
{
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
     * @param ProductsBundle $productsBundle
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
        Attribute $attribute,
        ProductAttributeRepositoryInterface $attributeRepository,
        PeBackendConfig $peBackendConfig,
        CanvaBackendConfig $canvaBakendConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        ToggleConfig $toggleConfig,
        private readonly ProductsBundle $productsBundle,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $attribute,
            $attributeRepository,
            $peBackendConfig,
            $canvaBakendConfig,
            $searchCriteriaBuilder,
            $sortOrderBuilder,
            $toggleConfig,
            $data
        );
    }

    /**
     * Get Ids listed form product engine
     *
     * @return array
     */
    public function getVisibleAttributes(): array
    {
        $product = $this->getProduct();
        $attributeList = [];

        if ($product->getTypeId() !== Type::TYPE_BUNDLE) {
            return [];
        }

        $childProducts = $this->productsBundle->getBundleChildProducts($product);
        foreach ($childProducts as $childProduct) {
            if($childProduct->getStatus() != Status::STATUS_ENABLED) {
                continue;
            }

            $attributeList[$childProduct->getPeProductId()] = [];

            $visibleAttributes = $childProduct->getData('visible_attributes') ?? '';

            $visibleAttributes = array_slice(explode(',', $visibleAttributes), 0, self::MAX_ATTRIBUTES_AVAILABLE);
            $visibleAttributesOrdered = $this->getAttributes($visibleAttributes);
            foreach ($visibleAttributesOrdered->getItems() as $attribute) {

                $attributeOptionsId = $childProduct->getData($attribute->getAttributeCode());
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

                foreach ($attributeOptions as $attributeOption) {
                    $choiceId = $attributeOption['choice_id'] ?? '';
                    $attributeList[$childProduct->getPeProductId()][] = $choiceId;
                }
            }
        }

        return $attributeList;
    }

    /**
     * Get Ids and skus formated
     *
     * @return array
     */
    public function getIdsSkus(): array
    {
        $product = $this->getProduct();
        $childProducts = $this->productsBundle->getBundleChildProducts($product);
        $skus = [];
        foreach ($childProducts as $childProduct) {
            if($childProduct->getStatus() != Status::STATUS_ENABLED) {
                continue;
            }
            $skus[$childProduct->getPeProductId()] = $childProduct->getSku();
        }

        return $skus;
    }
}
