<?php
/**
 * @category Fedex
 * @package  Fedex_Recaptcha
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Recaptcha\Model;

use Fedex\Recaptcha\Api\Data\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to Recaptcha Module database configuration.
 */
class Config implements ConfigInterface
{

    /**
     * Recaptcha Site Key Frontend
     */
    private const XML_PATH_PUBLIC_KEY = 'recaptcha_frontend/type_recaptcha_v3/public_key';

    /**
     * @param ToggleConfig $toggleConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_PUBLIC_KEY, ScopeInterface::SCOPE_WEBSITE));
    }
}
