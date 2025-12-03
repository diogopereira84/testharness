<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Service\Address;

use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;

class RegionNameResolver
{
    /**
     * @var array
     */
    private array $cache = [];

    public function __construct(
        private readonly RegionCollectionFactory $collectionFactory
    ) {}

    /**
     * Preloads region names for the given ids (single query with WHERE IN).
     */
    public function preload(array $regionIds): void
    {
        $regionIds = array_values(array_unique(array_map('intval', $regionIds)));
        $regionIds = array_diff($regionIds, array_keys($this->cache));
        if (!$regionIds) {
            return;
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('main_table.region_id', ['in' => $regionIds]);

        foreach ($collection as $region) {
            $this->cache[(int)$region->getId()] = (string)$region->getName();
        }
    }

    /**
     * Returns the region name for a given id if preloaded, or null.
     */
    public function nameById(int $regionId): ?string
    {
        return $this->cache[$regionId] ?? null;
    }
}
