<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Api\Data;

interface SharedCatalogSkipInterface
{
    /**
     * @return bool
     */
    public function checkIsSharedCatalogPage(): bool;

    /**
     * @return bool
     */
    public function checkCommercialStoreWithArea(): bool;

    /**
     * @return string|bool
     */
    public function getLivesearchProductListingEnable();
}
