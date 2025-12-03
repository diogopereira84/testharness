<?php
/**
 * @category  Fedex
 * @package   Fedex_CatalogSyncAdmin
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CatalogSyncAdmin\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\CatalogSyncAdmin\Cron\ForceResyncCatalog as OriginalForceResyncCatalog;
use Magento\SaaSCommon\Model\ResyncManagerPool;
use Psr\Log\LoggerInterface;

class ForceResyncCatalog extends OriginalForceResyncCatalog
{
    /**
     * Xpath for ALS Resync Remove ProductOverrides Feed Toggle
     */
    private const XPATH_ALS_RESYNC_REMOVE_PRODUCTOVERRIDES_FEED = 'als_resync_remove_productoverrides_feed';

    /**
     * Resync Manager Pools
     */
    private const PRODUCT_ATTRIBUTES_RESYNC_POOL = 'productattributes';
    private const PRODUCT_RESYNC_POOL = 'products';
    private const PRODUCT_OVERRIDES_RESYNC_POOL = 'productoverrides';
    private const PRICES_RESYNC_POOL = 'prices';
    private const SCOPE_CUSTOMER_GROUPS_RESYNC_POOL = 'scopesCustomerGroup';
    private const SCOPE_WEBSITE_RESYNC_POOL = 'scopesWebsite';

    /**
     * @param ResyncManagerPool $resyncManagerPool
     * @param LoggerInterface $logger
     * @param ToggleConfig $config
     */
    public function __construct(
        private readonly ResyncManagerPool $resyncManagerPool,
        private readonly LoggerInterface $logger,
        private readonly ToggleConfig $config
    ) {
        parent::__construct($resyncManagerPool, $logger);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $this->logger->info("Initiating custom FedEx full catalog data re-sync");
            $this->resyncFeed(self::PRODUCT_ATTRIBUTES_RESYNC_POOL);
            $this->resyncFeed(self::PRODUCT_RESYNC_POOL);
            $this->resyncFeed(self::SCOPE_CUSTOMER_GROUPS_RESYNC_POOL);
            $this->resyncFeed(self::SCOPE_WEBSITE_RESYNC_POOL);
            $this->resyncFeed(self::PRICES_RESYNC_POOL);
            if (!$this->isRemoveProductOverridesFeedToggleEnabled()) {
                $this->resyncFeed(self::PRODUCT_OVERRIDES_RESYNC_POOL);
            }
            $this->logger->info('Catalog data re-sync successfully finished');
        } catch (\Exception $ex) {
            $this->logger->error(sprintf('An error occurred during catalog data re-sync: %s', $ex->getMessage()));
        }
    }

    /**
     * Resync feed
     *
     * @param string $feedName
     * @throws \Exception
     */
    private function resyncFeed(string $feedName): void
    {
        $this->logger->info(sprintf('Re-syncing feed: %s', $feedName));
        $productResync = $this->resyncManagerPool->getResyncManager($feedName);
        $productResync->executeFullResync();
    }

    /**
     * Check if the ALS Resync Remove ProductOverrides Feed Toggle is enabled
     *
     * @return bool
     */
    private function isRemoveProductOverridesFeedToggleEnabled(): bool
    {
        return (bool) $this->config->getToggleConfigValue(self::XPATH_ALS_RESYNC_REMOVE_PRODUCTOVERRIDES_FEED);
    }
}
