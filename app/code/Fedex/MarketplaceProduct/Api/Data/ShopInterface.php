<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Api\Data;

interface ShopInterface
{
    /**
     * Return the shop id
     *
     * @return string|null
     */
    public function getId(): string|null;

    /**
     * Set the shop id
     *
     * @param string $shopId
     * @return ShopInterface
     */
    public function setId($shopId);

}
