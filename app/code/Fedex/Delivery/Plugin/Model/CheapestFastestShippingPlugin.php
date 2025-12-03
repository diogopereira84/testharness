<?php

/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 * @copyright 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\Delivery\Plugin\Model;

use Fedex\Delivery\Model\GetCheapestFastestShippingMethod;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\ShippingMethodManagement;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class CheapestFastestShippingPlugin
{
    /**
     * Xpath for shipping methods display toggle
     */
    private const XPATH_DISPLAY_SHIPPING_METHODS_TOGGLE
        = 'environment_toggle_configuration/environment_toggle/tiger_e_427646_shipping_methods_display';

    private ?bool $isDisplayToggleEnabled = null;

    /**
     * @param GetCheapestFastestShippingMethod $getCheapestFastestShippingMethod
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly GetCheapestFastestShippingMethod $getCheapestFastestShippingMethod,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ShippingMethodManagement $subject
     * @param array $result
     * @return array
     */
    public function afterEstimateByExtendedAddress(
        ShippingMethodManagement $subject,
        array $result
    ): array {
        return $this->applyCheapestAndFastestShipping($result);
    }

    /**
     * @param ShippingMethodManagement $subject
     * @param array $result
     * @return array
     */
    public function afterEstimateByAddressId(
        ShippingMethodManagement $subject,
        array $result
    ): array {
        return $this->applyCheapestAndFastestShipping($result);
    }

    /**
     * @return bool
     */
    private function isDisplayToggleEnabled(): bool
    {
        if ($this->isDisplayToggleEnabled === null) {
            $this->isDisplayToggleEnabled = $this->scopeConfig->isSetFlag(
                self::XPATH_DISPLAY_SHIPPING_METHODS_TOGGLE,
                ScopeInterface::SCOPE_STORE
            );
        }
        return $this->isDisplayToggleEnabled;
    }

    /**
     * @param array $result
     * @return array
     */
    private function applyCheapestAndFastestShipping(array $result): array
    {
        try {
            if (!$this->isDisplayToggleEnabled()) {
                return $result;
            }

            return $this->getCheapestFastestShippingMethod->execute($result);
        } catch (\Exception $e) {
            $this->logger->critical('Fastest and Cheapest Plugin Error: ' . $e->getMessage());
            return $result;
        }
    }
}
