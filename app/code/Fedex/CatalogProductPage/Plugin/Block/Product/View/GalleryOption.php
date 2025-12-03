<?php

namespace Fedex\CatalogProductPage\Plugin\Block\Product\View;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Fedex\Catalog\Model\Config;

class GalleryOption
{
    const FIRST_PARTY_NEW_PRODUCT_TEMPLATE_NEW = 'first-party-product-full-width';
    const IN_STORE_PRODUCT_TEMPLATE_NEW = 'in-store-product-full-width';
    const COMMERCIAL_PRODUCT_TEMPLATE = 'commercial-product-full-width';

    /**
     * GalleryOption constructor.
     * @param Json $jsonSerializer
     * @param CatalogHelper $catalogHelper
     * @param Config $config
     */
    public function __construct(
        private Json $jsonSerializer,
        private CatalogHelper $catalogHelper,
        private Config $config
    )
    {
    }

    /**
     * Retrieve gallery options in JSON format
     *
     * @return string
     *
     */
    public function afterGetOptionsJson(
        \Magento\Catalog\Block\Product\View\GalleryOptions $subject,
        $result
    ) {
        $optionItems = $this->jsonSerializer->unserialize($result);

        $optionItems['maxheight'] = $subject->getVar("gallery/maxheight");
        $optionItems['maxwidth'] = $subject->getVar("gallery/maxwidth");
        $optionItems['thumbborderwidth'] = $subject->getVar("gallery/thumbborderwidth");

        $product = $this->catalogHelper->getProduct();
        $productLayoutCustom = [
            self::FIRST_PARTY_NEW_PRODUCT_TEMPLATE_NEW,
            self::COMMERCIAL_PRODUCT_TEMPLATE
        ];
        if ($product && in_array($product->getData('page_layout'), $productLayoutCustom)) {
            $galleryJson = json_decode($this->config->getPdpGallerySettings(), true);
            if (is_array($galleryJson)) {
                foreach ($galleryJson as $options) {
                    $optionItems[$options['key']] = $options['value'];
                }
            }
        } elseif ($product && $product->getData('page_layout') === self::IN_STORE_PRODUCT_TEMPLATE_NEW) {
            $galleryJson = json_decode($this->config->getPdpGalleryInStoreProductSettings(), true);
            if (is_array($galleryJson)) {
                foreach ($galleryJson as $options) {
                    $optionItems[$options['key']] = $options['value'];
                }
            }
        }

        return $this->jsonSerializer->serialize($optionItems);
    }
}
