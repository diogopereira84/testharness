<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model;

use Fedex\Canva\Api\Data\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to Canva Design database configuration.
 */
class Config implements ConfigInterface
{
    /**
     * Canva design base url xml path
     */
    public const XML_PATH_FEDEX_CANVA_DESIGN_BASE_URL = 'fedex/canva_design/base_url';

    /**
     * Canva design base path xml path
     */
    public const XML_PATH_FEDEX_CANVA_DESIGN_PATH = 'fedex/canva_design/path';

    /**
     * Path to Canva logo selected from admin panel
     */
    public const CANVA_LOGO_CONFIG_PATH = 'fedex/canva_design/canva_logo';

    /**
     * Path to Canva partner id from admin panel
     */
    public const XML_PATH_FEDEX_CANVA_DESIGN_PARTNER_ID = 'fedex/canva_design/partner_id';

    /**
     * Path to Canva partnership sdk url from admin panel
     */
    public const XML_PATH_FEDEX_CANVA_DESIGN_PARTNERSHIP_SDK_URL = 'fedex/canva_design/partnership_sdk_url';

    /**
     * Path to Canva user token api url from admin panel
     */
    public const XML_PATH_FEDEX_CANVA_DESIGN_USER_TOKEN_API_URL = 'fedex/canva_design/user_token_api_url';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getBaseUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_DESIGN_BASE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getPath(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_DESIGN_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getCanvaLogoPath(): ?string
    {
        return $this->scopeConfig->getValue(
            self::CANVA_LOGO_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getPartnerId(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_DESIGN_PARTNER_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getPartnershipSdkUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_DESIGN_PARTNERSHIP_SDK_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getUserTokenApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_CANVA_DESIGN_USER_TOKEN_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }
}
