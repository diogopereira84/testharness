<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Plugin;

use Fedex\PoliticalDisclosure\Model\Config\PoliticalDisclosureConfig;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Model\ConfigProvider as DeliveryConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;

class DeliveryConfigProviderPlugin
{
    private const CFG_PD_TOOLTIP = 'fedex/political_disclosure_config/tooltip';

    /**
     * @param PoliticalDisclosureConfig $pdConfig
     * @param ToggleConfig $toggleConfig
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        private PoliticalDisclosureConfig $pdConfig,
        private ToggleConfig $toggleConfig,
        private ScopeConfigInterface $scopeConfigInterface,
    ) {}

    /**
     * @param DeliveryConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetConfig(DeliveryConfigProvider $subject, array $result): array
    {
        $result['political_disclosure'] = [
            'enabledStates' => $this->pdConfig->getEnabledStates(),
            'tooltip'       => (string) ($this->scopeConfigInterface->getValue(self::CFG_PD_TOOLTIP) ?? '')
        ];

        return $result;
    }
}
