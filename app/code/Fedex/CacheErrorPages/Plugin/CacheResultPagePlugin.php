<?php
/**
 * @category Fedex
 * @package  Fedex_CacheErrorPages
 * @author   Iago Lima <iago.lima.osv@fedex.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License
 * @copyright Copyright (c) 2025 Fedex.
 **/
namespace Fedex\CacheErrorPages\Plugin;

use Fedex\CacheErrorPages\Model\Cache\ErrorPageCache;
use Fedex\CacheErrorPages\Model\Cache\Page404CacheConfig;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CacheResultPagePlugin
{

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param Page404CacheConfig $page404CacheConfig
     * @param State $state
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected ScopeConfigInterface $scopeConfig,
        protected CacheInterface $cache,
        protected LoggerInterface $logger,
        protected readonly Page404CacheConfig $page404CacheConfig,
        private readonly State $state
    ) {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function afterRenderResult(ResultPage $subject, $result, HttpResponse $httpResponse)
    {
        try {
            $areaCode = $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $areaCode = null;
        }

        if ($areaCode !== 'frontend') {
            return $result;
        }

        if ($httpResponse->getHttpResponseCode() === 404) {

            if ($this->page404CacheConfig->isCacheEnabled()) {

                $html = $httpResponse->getBody();
                $cacheKey = $this->page404CacheConfig->getCacheKey();
                $maxAge = $this->page404CacheConfig->getCacheLifetime();

                $this->cache->save($html, $cacheKey, [ErrorPageCache::CACHE_TAG], $maxAge);
                $this->logger->info('404 page saved into the cache.');
            }
        }

        return $result;
    }
}
