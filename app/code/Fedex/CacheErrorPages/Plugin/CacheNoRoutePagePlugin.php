<?php
/**
 * @category Fedex
 * @package  Fedex_CacheErrorPages
 * @author   Iago Lima <iago.lima.osv@fedex.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License
 * @copyright Copyright (c) 2025 Fedex.
 **/
namespace Fedex\CacheErrorPages\Plugin;

use Fedex\CacheErrorPages\Model\Cache\Page404CacheConfig;
use Magento\Cms\Controller\Noroute\Index as NoRouteIndex;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CacheNoRoutePagePlugin
{

    /**
     * @param CacheInterface $cache
     * @param Page404CacheConfig $page404CacheConfig
     * @param LoggerInterface $logger
     * @param State $state
     */
    public function __construct(
        protected CacheInterface $cache,
        private readonly Page404CacheConfig $page404CacheConfig,
        protected LoggerInterface $logger,
        private readonly State $state,
        protected ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @param NoRouteIndex $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NoSuchEntityException
     */
    public function aroundDispatch(NoRouteIndex $subject, callable $proceed, RequestInterface $request)
    {
        try {
            $areaCode = $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $areaCode = null;
        }

        if ($areaCode !== 'frontend') {
            return $proceed($request);
        }

        if (!$this->page404CacheConfig->isCacheEnabled()) {
            return $proceed($request);
        }

        $cacheKey = $this->page404CacheConfig->getCacheKey();
        $cachedHtml = $this->cache->load($cacheKey);

        if ($cachedHtml) {
            return $this->respondWithCache($subject, $cachedHtml);
        }

        return $proceed($request);
    }

    /**
     * @param NoRouteIndex $subject
     * @param string $cachedHtml
     * @return ResponseInterface
     */
    private function respondWithCache(NoRouteIndex $subject, string $cachedHtml): ResponseInterface
    {
        $subject->getResponse()->setBody($cachedHtml)->setHttpResponseCode(404);
        return $subject->getResponse();
    }

}
