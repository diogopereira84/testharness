<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class MarketplaceConfigProvider
{
    public const XML_PATH_MARKETPLACE_PROMO_CODE_MESSAGE = 'fedex/marketplace_configuration/promo_code_message';
    public const XML_PATH_MARKETPLACE_CART_QUANTITY_TOOLTIP = 'fedex/marketplace_configuration/cart_quantity_tooltip';
    public const XML_PATH_PROMO_CODE_MESSAGE_ENABLED_TOGGLE = 'fedex/marketplace_configuration/promo_code_message_enabled_toggle';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @return null|string
     */
    public function getPromoCodeMessage(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MARKETPLACE_PROMO_CODE_MESSAGE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return null|string
     */
    public function getCartQuantityTooltip(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MARKETPLACE_CART_QUANTITY_TOOLTIP, ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get toggle for Promo Code Message Enabled configuration
     *
     * @return bool
     */
    public function getPromoCodeMessageEnabledToggle(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PROMO_CODE_MESSAGE_ENABLED_TOGGLE, ScopeInterface::SCOPE_STORE
        );
    }
}
