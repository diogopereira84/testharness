<?php

namespace Fedex\Delivery\ViewModel;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * CartPickup ViewModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CartPickup implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    public const PICKUP_SEARCH_ERROR_MESSAGE = 'checkout/pickup_search_settings/pickup_search_error_message';

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        private ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * Get Media URL
     *
     * @param string $path
     * @return string
     */
    public function getMediaUrl($path)
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . $path;
    }

    /**
     * Get Pickup Search Error Message
     */
    public function getPickupSearchErrorMessage()
    {
        return $this->scopeConfig->getValue(static::PICKUP_SEARCH_ERROR_MESSAGE, ScopeInterface::SCOPE_STORE);
    }
}
