<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnvironmentManager\ViewModel;

use Fedex\EnvironmentManager\Model\Cache\Type\CacheType;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class ToggleConfig implements ArgumentInterface
{
    /**
     * The tag name that limits the cache cleaning scope within a particular tag
     */
    public const CACHE_TAG = 'TOGGLE_CONFIG';

    private const ENV_TOGGLE_PATH = 'environment_toggle_configuration/environment_toggle/';

    private array $cacheValueArray = [];

    private const B2321195_TOGGLE = 'environment_toggle_configuration/environment_toggle/tiger_b2321195';

    /**
     * Promo Code feature toggle key (environment_toggle_configuration/environment_toggle/<key>)
     * NOTE: If a different key is desired, update this constant and corresponding config value.
     */
    public const PAGE_BUILDER_PROMO_CODE_ENABLED = 'pagebuilderpromobanner_promo_code_enabled';

    /**
     * Data Constructor
     *
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfigInterface,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
        private readonly TypeListInterface $cacheTypeList,
        private readonly Pool $cacheFrontendPool
    ) {
    }

    /**
     * Get Toggle configuration
     *
     * @param string $path
     * @return mixed
     */
    public function getToggleConfig(string $path): mixed
    {
        return $this->scopeConfigInterface->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get feature toggle value from cache
     *
     * @param string $key
     * @return string|int|boolean IFRAME|1|0|false|null
     */
    public function getToggleConfigValue(string $key): string|bool|int|null
    {
        if ($this->getToggleConfig(self::B2321195_TOGGLE)) {

            if (empty($this->cacheValueArray)) {
                $cacheValue = $this->cache->load(CacheType::TYPE_IDENTIFIER);
                try {
                    $this->cacheValueArray = $this->serializer->unserialize($cacheValue);
                } catch (\Exception $e) {}
            }

            if (!isset($this->cacheValueArray[$key])) {
                $this->cacheValueArray[$key] = $this->getToggleConfig(
                    self::ENV_TOGGLE_PATH . $key
                ) ?? 0;
            }

            return $this->cacheValueArray[$key];
        }

        $cacheKey  = CacheType::TYPE_IDENTIFIER;
        $cacheValueArray = [];
        $cacheValue = $this->cache->load($cacheKey);
        if ($cacheValue!="") {
            $cacheValueArray = $this->serializer->unserialize($cacheValue);
        }

        if (!empty($cacheValueArray) && isset($cacheValueArray[$key])) {
            return $cacheValueArray[$key];
        }

        $path = "environment_toggle_configuration/environment_toggle/".$key;
        return $this->getToggleConfig($path);
    }

    /**
     * Get Cache data
     *
     * @param string $key
     * @return array
     */
    public function getCacheData(string $key): array
    {
        $cacheValue = $this->cache->load($key);
        if ($cacheValue != "") {
            return json_decode($cacheValue, true);
        } else {
            return [];
        }
    }

    /**
     * Save toggle configuration in cache
     *
     * @return void
     */
    public function saveToggleConfigCache(): void
    {
        $cacheKey  = CacheType::TYPE_IDENTIFIER;
        $cacheTag  = CacheType::CACHE_TAG;
        $cacheValue = $this->cache->load($cacheKey);
        if ($cacheValue == "") {
            $cacheData = $this->getToggleConfig("environment_toggle_configuration/environment_toggle");
            $this->cache->save($this->serializer->serialize($cacheData), $cacheKey, [$cacheTag]);
        }
    }

    /**
     * Disable Enable module
     *
     * @param string $moduleName
     * @param boolean $flag
     * @return ToggleConfig
     */
    public function disableEnableModule(string $moduleName, bool $flag): static
    {
        return $this;
    }

    /**
     * Flush Cache
     *
     * @return void
     */
    public function flushCache(): void
    {
        $types = [
            'block_html'
        ];

        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Wrapper method queried by PageBuilder Promo Banner observer.
     * Returns true only if the promo code feature toggle is explicitly enabled (1/true).
     * Swallows all internal exceptions to avoid breaking request flow.
     *
     * @return bool
     */
    public function isPromoCodeEnabled(): bool
    {
        try {
            $value = $this->getToggleConfigValue(self::PAGE_BUILDER_PROMO_CODE_ENABLED);
            return ($value === true) || ((string)$value === '1');
        } catch (\Throwable $e) {
            // Intentionally suppress; default disabled state keeps behavior safe.
            return false;
        }
    }
}
