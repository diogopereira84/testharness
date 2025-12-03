<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class PoliticalDisclosureConfig
{
    private const PATH = 'fedex/political_disclosure_config/states_enabled';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {}

    /**
     * @return array
     */
    public function getEnabledStates(): array
    {
        $raw = $this->scopeConfig->getValue(self::PATH, ScopeInterface::SCOPE_STORE);
        $list = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
        return array_values(array_unique(array_map('strtoupper', $list)));
    }
}
