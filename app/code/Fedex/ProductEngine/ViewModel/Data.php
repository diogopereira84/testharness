<?php

namespace Fedex\ProductEngine\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\SDE\Helper\SdeHelper;

/**
 * Data Helper
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Data implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    const XPATH_PRODUCT_ENGINE_URL = 'product_engine/general/url'; 

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param SdeHelper $sdeHelper
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected StoreManagerInterface $storeManager,
        protected SdeHelper $sdeHelper
    )
    {
    }

    /**
     * Get product engine url
     * @return string
     */
    public function getProductEngineUrl()
    {
        $currentStoreId = $this->storeManager->getStore()->getStoreId();
        return $this->scopeConfig->getValue(self::XPATH_PRODUCT_ENGINE_URL, ScopeInterface::SCOPE_STORE, $currentStoreId);
    }

    /**
     * Get sde store enabled
     * @return boolean
     */
    public function isSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }
}
