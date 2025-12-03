<?php

namespace Fedex\Header\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class PromoBanner implements ArgumentInterface
{
    protected const PROMO_BANNER_URL = 'header_promo_banner/promobanner_group/promo_banner_url';
    protected const PROMO_BANNER_IS_NEW_TAB = 'header_promo_banner/promobanner_group/promo_banner_is_new_tab';

    /**
     * Data Constructor
     *
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfigInterface,
        protected StoreManagerInterface $storeManagerInterface
    )
    {
    }

    /**
     * Get current store id
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManagerInterface->getStore()->getStoreId();
    }

    /**
     * Get Promo Banner configuration
     *
     * @param string $path
     * @param null|int|string $storeId
     * @return mixed
     */
    public function gePromoBannerConfig($path, $storeId = null)
    {
        return $this->scopeConfigInterface->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get Promo Banner URL from system configuration
     *
     * @return string
     */
    public function getPromoBannerUrl()
    {
        $storeId = $this->getCurrentStoreId();
        
        return $this->gePromoBannerConfig(self::PROMO_BANNER_URL, $storeId);
    }

    /**
     * Get Promo Banner Is New Tab data from system configuration
     *
     * @return int
     */
    public function getPromoBannerIsNewTab()
    {
        $storeId = $this->getCurrentStoreId();

        return $this->gePromoBannerConfig(self::PROMO_BANNER_IS_NEW_TAB, $storeId);
    }
}
