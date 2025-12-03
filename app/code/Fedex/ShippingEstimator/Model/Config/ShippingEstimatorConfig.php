<?php

declare(strict_types=1);
namespace Fedex\ShippingEstimator\Model\Config;

use \Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ShippingEstimatorConfig
{
    public const XPATH_US_STATE_FILTER   = 'fedex_shipping_estimator/state_filter/us_state_filter';

    /**
     * ShippingEstimatorConfig constructor.
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected LoggerInterface $logger,
        private ScopeConfigInterface $scopeConfig,
        private StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * @return array
     */
    public function getExcludedStates(): array
    {
        try {
            $excludedStates = $this->scopeConfig->getValue(
                self::XPATH_US_STATE_FILTER,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
            return (!empty($excludedStates) ? explode(',', $excludedStates) : []);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return [];
        }
    }
}
