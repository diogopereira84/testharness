<?php
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Plugin\Mirakl\Mcm\Helper\Product;
use Mirakl\Mcm\Helper\Product\Export\Product as Subject;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceAdmin\Service\Product\ImageDataFilter;

class ProductPlugin
{
    public const TIGER_TEAM_D_226109_ESSENDANT_IMAGE_MIRAKL_SYNC ='tiger_team_d_226109_essendant_image_mirakl_sync';

    /**
     * @param ToggleConfig $toggleConfig
     * @param ImageDataFilter $imageDataFilter
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig,
        private readonly ImageDataFilter $imageDataFilter,
    ) {
    }

    /**
     * Prevent media gallery from being loaded.
     *
     * @param Subject $subject
     * @param array $result
     * @param array $productIds
     * @return array
     */
    public function afterGetProductsData(Subject $subject, array $result, array $productIds): array
    {
        if (!$this->toggleConfig->getToggleConfigValue(self::TIGER_TEAM_D_226109_ESSENDANT_IMAGE_MIRAKL_SYNC)) {
            return $result;
        }

        return $this->imageDataFilter->filter($result);
    }
}
