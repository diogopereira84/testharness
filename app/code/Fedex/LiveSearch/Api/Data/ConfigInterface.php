<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Api\Data;

interface ConfigInterface
{
    /**
     * @return string
     */
    public function getServiceUrl(): string;

    /**
     * @return bool
     */
    public function getToggleValueForLiveSearchProductionMode(): bool;

    /**
     * @return bool
     */
    public function isEllipsisControlEnabled(): bool;

    /**
     * @return int
     */
    public function getEllipsisControlTotalCharacters(): int;

    /**
     * @return int
     */
    public function getEllipsisControlStartCharacters(): int;

    /**
     * @return int
     */
    public function getEllipsisControlEndCharacters(): int;

    /**
     * @return int
     */
    public function getGuestUserSharedCatalogId():int;
}
