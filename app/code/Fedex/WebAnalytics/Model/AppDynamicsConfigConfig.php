<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model;

use Fedex\WebAnalytics\Api\Data\AppDynamicsConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to AppDynamics database configuration.
 */
class AppDynamicsConfigConfig implements AppDynamicsConfigInterface
{
    const XML_PATH_ACTIVE_APP_DYNAMICS = 'web/app_dynamics/active';
    const XML_PATH_APP_DYNAMICS_SCRIPT = 'web/app_dynamics/head_script';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ACTIVE_APP_DYNAMICS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getScriptCode(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_APP_DYNAMICS_SCRIPT,
            ScopeInterface::SCOPE_STORE
        );
    }
}
