<?php
/**
 * @category Fedex
 * @package  Fedex_CacheErrorPages
 * @author   Iago Lima <iago.lima.osv@fedex.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License
 * @copyright Copyright (c) 2025 Fedex.
 **/
declare(strict_types=1);
namespace Fedex\CacheErrorPages\Model\Cache;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Page404CacheConfig
{
    public const PAGE_404_CACHE_STORE_KEY_PREFIX = 'PAGE_404_CACHE_STORE_';
    const PAGE_404_CACHE_ENABLED_CONFIG_PATH = 'performance/cache_error_pages/enable_page_404';
    const PAGE_404_CACHE_MAX_AGE_CONFIG_PATH = 'performance/cache_error_pages/lifetime_page_404';

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::PAGE_404_CACHE_ENABLED_CONFIG_PATH,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * @return int
     */
    public function getCacheLifetime(): int
    {
        return  (int)$this->scopeConfig->getValue(
            self::PAGE_404_CACHE_MAX_AGE_CONFIG_PATH,
            ScopeInterface::SCOPE_STORES
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCacheKey(): string
    {
        $storeId = $this->storeManager->getStore()->getId();
        return self::PAGE_404_CACHE_STORE_KEY_PREFIX . $storeId;
    }

}
