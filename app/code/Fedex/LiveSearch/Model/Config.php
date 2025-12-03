<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Model;

use Fedex\LiveSearch\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    private const XML_PATH_SERVICE_URL = 'storefront_features/website_configuration/service_url';
    private const ENABLE_PRODUCTION_API_KEY = 'storefront_features/website_configuration/enable_production_api_key_for_saas_service';
    private const ENABLE_ELLIPSIS_CONTROL = 'storefront_features/product_name_ellipsis_control/enabled';
    private const ELLIPSIS_CONTROL_TOTAL_CHARACTERS = 'storefront_features/product_name_ellipsis_control/total_characters';
    private const ELLIPSIS_CONTROL_START_CHARACTERS = 'storefront_features/product_name_ellipsis_control/start_characters';
    private const ELLIPSIS_CONTROL_END_CHARACTERS = 'storefront_features/product_name_ellipsis_control/end_characters';
    private const SHARED_CATALOG_GUEST_USER = 'storefront_features/shared_catalog/guest_catalog_id';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getServiceUrl(): string
    {
        return (string) $this->scopeConfig->getValue(
            static::XML_PATH_SERVICE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * @inheritDoc
     */
    public function getToggleValueForLiveSearchProductionMode(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::ENABLE_PRODUCTION_API_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isEllipsisControlEnabled(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(
            static::ENABLE_ELLIPSIS_CONTROL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getEllipsisControlTotalCharacters(): int
    {
        return (int) $this->scopeConfig->getValue(
            static::ELLIPSIS_CONTROL_TOTAL_CHARACTERS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getEllipsisControlStartCharacters(): int
    {
        return (int) $this->scopeConfig->getValue(
            static::ELLIPSIS_CONTROL_START_CHARACTERS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getEllipsisControlEndCharacters(): int
    {
        return (int) $this->scopeConfig->getValue(
            static::ELLIPSIS_CONTROL_END_CHARACTERS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return int
     */
    public function getGuestUserSharedCatalogId(): int
    {
        return (int) $this->scopeConfig->getValue(
            static::SHARED_CATALOG_GUEST_USER,
            ScopeInterface::SCOPE_STORE
        );
    }
}
