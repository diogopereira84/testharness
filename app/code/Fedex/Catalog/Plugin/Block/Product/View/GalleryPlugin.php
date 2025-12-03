<?php

namespace Fedex\Catalog\Plugin\Block\Product\View;

use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Block\Product\View\Gallery;

class GalleryPlugin
{
    const FIRST_PARTY_NEW_PRODUCT_TEMPLATE_NEW = 'first-party-product-full-width';
    const IN_STORE_PRODUCT_TEMPLATE_NEW = 'in-store-product-full-width';
    const COMMERCIAL_PRODUCT_TEMPLATE = 'commercial-product-full-width';
    const OLD_PDP_IMAGE_ID = 'product_page_image_medium';
    const NEW_PDP_IMAGE_ID = 'product_page_image_medium_one_p';

    private UrlBuilder $imageUrlBuilder;

    /**
     * GalleryPlugin constructor.
     * @param UrlBuilder|null $urlBuilder
     */
    public function __construct(
        UrlBuilder $urlBuilder = null
    ) {
        $this->imageUrlBuilder = $urlBuilder ?? ObjectManager::getInstance()->get(UrlBuilder::class);
    }

    public function afterGetGalleryImages(Gallery $subject, $result)
    {
//NOSONAR
        $product = $subject->getProduct();
        $images = $product->getMediaGalleryImages();
        if (!$images instanceof \Magento\Framework\Data\Collection) {
            return $images;
        }

        foreach ($images as $image) {
            $galleryImagesConfig = $subject->getGalleryImagesConfig()->getItems();
            foreach ($galleryImagesConfig as $imageConfig) {

                /**
                 * Logic added to change image dimensions for new 1P PDP
                 */
                $imageId = $imageConfig['image_id'];
                $productLayout = $product->getData('page_layout');
                $productLayoutCustom = [
                    self::FIRST_PARTY_NEW_PRODUCT_TEMPLATE_NEW,
                    self::IN_STORE_PRODUCT_TEMPLATE_NEW,
                    self::COMMERCIAL_PRODUCT_TEMPLATE
                ];
                if (in_array($productLayout, $productLayoutCustom)
                    && $imageId === self::OLD_PDP_IMAGE_ID) {
                    $imageId = self::NEW_PDP_IMAGE_ID;
                } elseif (!in_array($productLayout, $productLayoutCustom)
                    && $imageId === self::NEW_PDP_IMAGE_ID) {
                    $imageId = self::OLD_PDP_IMAGE_ID;
                }

                $image->setData(
                    $imageConfig->getData('data_object_key'),
                    $this->imageUrlBuilder->getUrl($image->getFile(), $imageId)
                );
            }
        }

        return $images;
    }
}
