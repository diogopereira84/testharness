<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Api\Data;

interface OfferInterface
{
    /**
     * Return the offer id
     *
     * @return string|null
     */
    public function getId(): string|null;

    /**
     * Set the offer id
     *
     * @param string $offerId
     * @return OfferInterface
     */
    public function setId($offerId);
}
