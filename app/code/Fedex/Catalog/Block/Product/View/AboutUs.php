<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Block\Product\View;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\UrlInterface;
use Fedex\Catalog\Model\Config;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class AboutUs extends Template
{
    /**
     * Ideas constructor.
     * @param Template\Context $context
     * @param StoreManagerInterface $store
     * @param CatalogHelper $catalogHelper
     * @param Config $config
     * @param ToggleConfig $toggleConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private StoreManagerInterface $store,
        private CatalogHelper $catalogHelper,
        private Config $config,
        protected ToggleConfig $toggleConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return mixed[]
     */
    public function getProductIdeas(): array
    {
        $product = $this->catalogHelper->getProduct();
        if ($product) {
            return [
                'content-left' => $this->config->formatAttribute($product, 'product_ideas') ?? '',
                'content-right' => $this->getIdeasImage($product),
                'content-right-mobile' => $this->getIdeasImageMobile($product)
            ];
        }

        return [];
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getIdeasImage(Product $product): string
    {
        $previewImage = $product->getData('product_ideas_image') ?? 'no_selection';

        return $previewImage !== 'no_selection'
            ? $this->store->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $previewImage
            : '';
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getIdeasImageMobile(Product $product): string
    {
        $previewImage = $product->getData('product_ideas_image_mobile') ?? 'no_selection';

        return $previewImage !== 'no_selection'
            ? $this->store->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $previewImage
            : '';
    }

    /**
     * @return mixed[]
     */
    public function getProductSpecifications()
    {
        $product = $this->catalogHelper->getProduct();
        if ($product) {
            return $this->config->formatAttribute($product, 'product_specifications') ?? '';
        }

        return [];
    }
}
