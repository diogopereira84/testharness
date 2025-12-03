<?php
/**
 * @category Fedex
 * @package  Fedex_Company
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Company\Api\Data;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Return the Company Store Relation
     *
     * @return ?string
     **/
    public function getCompanyStoreRelation(): ?string;

    /**
     * D-173846 - Toggle for category name is not updating
     * @return bool|int|null
     */
    public function getCategoryEditD173846Toggle(): bool|int|null;

    /**
     * E-414712 - Hero Banner Carousel For Commercial
     * @return bool|int|null
     */
    public function getE414712HeroBannerCarouselForCommercial(): bool|int|null;

    public function isToggleEnableForD190859IssueFix();

    /**
     * Get toggle status for the fix of mapping issues in shared catalogs.
     * @return boolean
     */
    public function getSharedCatalogsMapIssueFixToggle();
}
