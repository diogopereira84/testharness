<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Service\Address;

use Fedex\MarketplaceAdmin\Service\Address\RegionNameResolver;

class MiraklShippingAddressGridFormatter
{
    /**
     * @param \Fedex\MarketplaceAdmin\Service\Address\RegionNameResolver $regionResolver
     */
    public function __construct(
        private readonly RegionNameResolver $regionResolver,
    ) {}

    /**
     * Formats a Mirakl address into a single inline string for the Orders grid.
     */
    public function formatInline(array $data): string
    {
        $street   = $this->buildStreetLine($data['street'] ?? []);
        $city     = $this->trimOrEmpty($data['city'] ?? null);
        $region   = $this->resolveRegionName($data);
        $postcode = $this->trimOrEmpty($data['postcode'] ?? null);

        $parts = array_filter(
            [$street ?: null, $city ?: null, $region ?: null, $postcode ?: null],
            static fn($v) => $v !== null && $v !== ''
        );

        return implode(',', $parts);
    }

    /**
     * Joins non-empty street lines using ", " preserving the original casing.
     */
    private function buildStreetLine(array $streetLines): string
    {
        $clean = [];
        foreach ($streetLines as $line) {
            $val = $this->trimOrEmpty($line);
            if ($val !== '') {
                $clean[] = $val;
            }
        }
        return $clean ? implode(', ', $clean) : '';
    }

    /**
     * Resolves the human-readable region name prioritizing regionId over regionCode.
     */
    private function resolveRegionName(array $data): string
    {
        $regionId = isset($data['regionId']) ? (int)$data['regionId'] : 0;
        if ($regionId > 0) {
            $cachedRegion = $this->regionResolver->nameById($regionId);
            if ($cachedRegion !== null) {
                return $cachedRegion;
            }
        }

        return $this->trimOrEmpty($data['region'] ?? null);
    }

    /**
     * Safe trim utility that converts null to an empty string.
     */
    private function trimOrEmpty($value): string
    {
        return trim((string)($value ?? ''));
    }
}
