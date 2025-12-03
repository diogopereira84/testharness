<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Plugin;

use Magento\Directory\Model\PriceCurrency;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

class PriceCurrencyPlugin
{
    /**
     * Initializing Constructor
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger
    )
    {
    }
    
    /**
     * Override the price directory model to repserve till 6 decimal places
     *
     * @param PriceCurrency $subject
     * @param float $result
     * @param float $price
     * @return float
     */
    public function afterRound(PriceCurrency $subject, $result, $price)
    {
        if ($this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
            $result = round((float) $price, 6);
        }
        return $result;
    }
}
