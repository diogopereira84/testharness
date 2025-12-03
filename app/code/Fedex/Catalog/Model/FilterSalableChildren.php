<?php
/**
 * @category    Fedex
 * @package     Fedex_Catalog
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Catalog\Model;

use Fedex\Catalog\Api\FilterSalableChildrenInterface;

class FilterSalableChildren implements FilterSalableChildrenInterface
{
    /**
     * @param array $children
     * @return array
     */
    public function filter(array $children): array
    {
        return array_filter($children, fn($child) => $child->isSalable());
    }
}