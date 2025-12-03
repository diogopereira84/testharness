<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Block\Product\View\AboutUs;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\UrlInterface;
use Fedex\Catalog\Model\Config;

class Info extends Template
{
    /**
     * Info constructor.
     * @param Template\Context $context
     * @param StoreManagerInterface $store
     * @param CatalogHelper $catalogHelper
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private StoreManagerInterface $store,
        private CatalogHelper $catalogHelper,
        private Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return mixed[]
     */
    public function getProductInfo(): array
    {
        $product = $this->catalogHelper->getProduct();
        if ($product) {
            return
                [
                    'content-left' => $this->config->formatAttribute($product, 'product_info'),
                    'content-right' => $this->getInfoImage($product),
                    'content-right-mobile' => $this->getInfoImageMobile($product)
                ];
        }

        return [];
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getInfoImage(Product $product): string
    {
        $previewImage = $product->getData('product_info_image') ?? 'no_selection';

        return $previewImage !== 'no_selection'
            ? $this->store->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $previewImage
            : '';
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getInfoImageMobile(Product $product): string
    {
        $previewImage = $product->getData('product_info_image_mobile') ?? 'no_selection';

        return $previewImage !== 'no_selection'
            ? $this->store->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $previewImage
            : '';
    }
}
