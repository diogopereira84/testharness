<?php

namespace Fedex\Cart\Model;

use Magento\Framework\App\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Directory\Model\CurrencyFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

class PriceCurrency extends \Magento\Directory\Model\PriceCurrency
{
    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        LoggerInterface $logger,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct(
            $storeManager,
            $currencyFactory,
            $logger
        );
    }

    /**
     * Override the price directory model to repserve till 6 decimal places
     *
     * @param \Magento\Directory\Model\PriceCurrency $price
     * @return float
     */
    public function round($price)
    {
        if (!$this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
            return round((float) $price, 6);
        } else {
            return $price;
        }
    }
}
