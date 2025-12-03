<?php
/**
 * Interface FilterSalableChildrenInterface
 *
 * Defines methods for filtering salable children in a catalog.
 *
 * @category     Fedex
 * @package      Fedex_Catalog
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Catalog\Api;

interface FilterSalableChildrenInterface
{
    /**
     * @param array $children
     * @return array
     */
    public function filter(array $children): array;
}