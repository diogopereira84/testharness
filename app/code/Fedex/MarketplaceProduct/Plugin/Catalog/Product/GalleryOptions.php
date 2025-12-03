<?php
declare (strict_types = 1);

namespace Fedex\MarketplaceProduct\Plugin\Catalog\Product;

use Magento\Catalog\Block\Product\View\GalleryOptions as Options;
use Magento\Framework\View\Result\Page as PageResult;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Fedex\MarketplaceProduct\Model\Config\Backend\MarketplaceProduct as MarketplaceProductConfig;


class GalleryOptions
{
    /**
     * Thrid party layout 
     */
    private const THIRD_PARTY_LAYOUT = 'third-party-product-full-width';

    /**
     * @param PageResult $pageResult
     * @param CatalogHelper $catalogHelper
     * @param Json $jsonSerializer
     * @param MarketplaceProductConfig $marketplaceProductConfig
     */
    public function __construct(
        /**
         * Current page result.
         */
        protected PageResult $pageResult,
        /**
         * CatalogHelper
         */
        protected CatalogHelper $catalogHelper,
        /**
         * Json
         */
        protected Json $jsonSerializer,
        /**
         * MarketplaceProduct
         */
        protected MarketplaceProductConfig $marketplaceProductConfig
    )
    {
    }

    /**
     * After get options json serialize.
     *
     * @param Options $subject
     * @param string $data
     * @return bool|string
     */
    public function afterGetOptionsJson(Options $subject, string $data)
    {
        $currentLayout = $this->pageResult->getConfig()->getPageLayout();
        $options = $data;

        if ($currentLayout == self::THIRD_PARTY_LAYOUT) 
        {
            $options = $this->jsonSerializer->unserialize($options);
            $options['navdir'] = "horizontal";

            if ( $this->catalogHelper->getProduct() ) 
            {
                $galleryJson = json_decode($this->marketplaceProductConfig->get3pPdpGallerySettings(), true);

                if (is_array($galleryJson)) {
                    foreach ($galleryJson as $option) {
                        $options[$option['key']] = $option['value'];
                    }
                }
            }
            
            $options = $this->jsonSerializer->serialize($options);
        }

        return $options;
    }
}
