<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model\Middleware;

use Magento\Framework\App\CacheInterface;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;

class MiraklWebhookDuplicateBlocker
{
    private const CACHE_PREFIX = 'webhook_replay_';

    /**
     * @param CacheInterface $cache
     * @param HandleMktCheckout $handleMktCheckout
     */
    public function __construct(
        private CacheInterface    $cache,
        private HandleMktCheckout $handleMktCheckout
    ) {

    }

    /**
     *
     *
     * @param string $payload
     * @return bool
     */
    public function isDuplicate(string $payload): bool
    {
        $hash = md5($payload);
        $key = self::CACHE_PREFIX . $hash;

        if ($this->cache->load($key)) {
            return true;
        }
        $ttl = $this->handleMktCheckout->getTtlBlockWebhookInSeconds();
        $this->cache->save('1', $key, [], $ttl);
        return false;
    }
}
