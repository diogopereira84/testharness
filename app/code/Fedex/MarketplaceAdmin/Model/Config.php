<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceAdmin
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Config
{
    const XPATH_ENABLE_MKT_SELFREG_SITE = 'environment_toggle_configuration/environment_toggle/tiger_tk_410245';
    const XPATH_ENABLE_D226848 = 'environment_toggle_configuration/environment_toggle/tiger_d226848';
    const ORIGIN_MARKETPLACE = 'marketplace';
    const ORIGIN_MIXED       = 'mixed';
    const OPERATOR_CLASS     = 'magento';
    const OPERATOR_LABEL     = 'Operator';
    const MARKETPLACE_CLASS  = 'marketplace';
    const MARKETPLACE_LABEL  = 'Marketplace';
    const MIXED_CLASS        = 'magento marketplace';
    const MIXED_LABEL        = 'Mixed';

    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Checks if marketplace master toggle is enabled
     * @return bool
     */
    public function isMktSelfregEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_MKT_SELFREG_SITE);
    }

    /**
     * Checks if D226848 toggle is enabled
     * @return bool
     */
    public function isD226848Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_D226848);
    }
}
