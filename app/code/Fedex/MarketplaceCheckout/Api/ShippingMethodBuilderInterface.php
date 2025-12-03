<?php
/**
 * Interface ShippingMethodsInterface
 *
 * Defines methods for handling shipping methods and related operations in the Fedex MarketplaceCheckout module.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Fedex\MarketplaceCheckout\Model\DTO\ShippingMethodDTO;
interface ShippingMethodBuilderInterface
{
    /**
     * Creates shipping methods based on the provided data.
     *
     * @param ShippingMethodDTO $data
     * @return array
     */
    public function createShippingMethod(ShippingMethodDTO $data): array;
}