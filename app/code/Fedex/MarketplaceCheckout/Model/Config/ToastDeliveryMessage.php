<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ToastDeliveryMessage
{
    public const XML_PATH_MARKETPLACE_TOAST_TITLE = 'fedex/marketplace_configuration_toast/title';
    public const XML_PATH_MARKETPLACE_SHIPPING_CONTENT = 'fedex/marketplace_configuration_toast/shipping_content';
    public const XML_PATH_MARKETPLACE_PICKUP_CONTENT = 'fedex/marketplace_configuration_toast/pickup_content';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Return Marketplace delivery title
     *
     * @return string|null
     */
    public function getMarketplaceToastTitle(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MARKETPLACE_TOAST_TITLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return Marketplace shipping content
     *
     * @return string|null
     */
    public function getMarketplaceToastShippingContent(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MARKETPLACE_SHIPPING_CONTENT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return Marketplace pickup content
     *
     * @return string|null
     */
    public function getMarketplaceToastPickupContent(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MARKETPLACE_PICKUP_CONTENT, ScopeInterface::SCOPE_STORE);
    }
}
