<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Pricing\Render\PriceBox;

class PriceBoxTagsPlugin
{
    public function __construct(
        private ToggleConfig $toggleConfig
    ) // @codingStandardsIgnoreLine
    {
    }

    /**
     * This will force not use the cache for the price box block
     *
     * @param PriceBox $subject
     * @param callable $proceed
     * @return string
     * @see PriceBox::getCacheKey
     */
    public function aroundGetCacheKey(
        PriceBox $subject,
        callable $proceed
    ) : string {
        if ($this->isFixForcedPriceCacheToggleEnabled()) {
            return '' . rand();
        }
        return $proceed();
    }

    /**
     * Returns /environment_toggle_configuration/environment_toggle/techtitans_D190199_fix
     *
     * @return bool
     */
    public function isFixForcedPriceCacheToggleEnabled(): bool
    {
        return (bool) $this->toggleConfig
            ->getToggleConfigValue('techtitans_D190199_fix');
    }
}
