<?php
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Service\Product;

class ImageDataFilter
{
    private const KEYS_TO_UNSET = [
        'images',
        'mirakl_image_1', 'mirakl_image_2', 'mirakl_image_3', 'mirakl_image_4', 'mirakl_image_5',
        'mirakl_image_6', 'mirakl_image_7', 'mirakl_image_8', 'mirakl_image_9', 'mirakl_image_10'
    ];

    /**
     * @param array $products
     * @return array
     */
    public function filter(array $products): array
    {
        foreach ($products as $index => $productData) {
            foreach (self::KEYS_TO_UNSET as $key) {
                unset($productData[$key]);
            }
            $products[$index] = $productData;
        }

        return $products;
    }
}
