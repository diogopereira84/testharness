<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Api;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;

interface ShopRepositoryInterface
{
    /**
     * Retrieve shop by shop id
     *
     * @param int $shopId
     * @return ShopInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $shopId): ShopInterface;
}
