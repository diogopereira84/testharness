<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2025.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Api\Data;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface ConfigInterface
 */
interface NuanceInterface
{
    public const XML_PATH_FEDEX_NUANCE_ACTIVE = 'web/nuance/nuance_active';
    public const XML_PATH_FEDEX_NUANCE_SCRIPT_CODE = 'web/nuance/script_code';

    /**
     *  Feature toggle for Nuance.
     *  Check if should show or not the script code.
     *
     * @param $storeId
     * @return bool
     */
    public function isActive($storeId = null): bool;

    /**
     * Return the Nuance source code to be displayed.
     *
     * @param $storeId
     * @return ?string
     **/
    public function getScriptCode($storeId = null): ?string;

    /**
     * Check if Nuance configuration enable or disable
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isEnabledNuanceForCompany();

    /**
     * Get Nuance Script with Nonce
     *
     * @return false|string
     * @throws NoSuchEntityException
     */
    public function getScriptCodeWithNonce();

    /**
     * Get current store id
     *
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId();
}
