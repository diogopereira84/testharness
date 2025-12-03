<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Plugin\Checkout\Block;

use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Magento\Checkout\Block\Cart;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as AuthContext;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

/**
 * Get cart summary item class
 */
class CartItems
{
    /**
     * Initilizing constructor
     *
     * @param ExpiredItem $expiredItem
     * @param CheckoutSession $checkoutSession
     * @param HttpContext $httpContext
     * @param CustomerSession $customerSession
     * @param AuthHelper $authHelper
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param ToggleConfig $toggleConfig
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        protected ExpiredItem $expiredItem,
        protected CheckoutSession $checkoutSession,
        private HttpContext $httpContext,
        protected CustomerSession $customerSession,
        protected AuthHelper $authHelper,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        private ToggleConfig $toggleConfig,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    )
    {
    }

    /**
     * Send config data to cart
     *
     * @param  object $subject
     * @return array
     */
    public function afterGetItems(Cart $subject)
    {
        $isEssendantToggleEnabled = $this->marketplaceCheckoutHelper->isEssendantToggleEnabled();
        $expiredInstanceIds = $this->expiredItem->getExpiredInstanceIds();
        $expiredItems = [];
        $expyrySoonItems = [];
        $remainingItems = [];
        if($this->addToCartPerformanceOptimizationToggle->isActive()) {
            $expirySoonItems = [];
            if ($this->authHelper->isLoggedIn()) {
                if($isEssendantToggleEnabled){
                    $itemsInQuote = $subject->getQuote()->getAllVisibleItems();
                }else{
                    $itemsInQuote = $subject->getQuote()->getAllItems();
                }
                $hasExpiredInstanceIds = is_array($expiredInstanceIds);

                foreach ($itemsInQuote as $item) {
                    $itemId = $item->getId();

                    if ($hasExpiredInstanceIds && in_array($itemId, $expiredInstanceIds)) {
                        $expiredItems[] = $item;
                    } elseif ($this->expiredItem->isItemExpiringSoon($itemId)) {
                        $expirySoonItems[] = $item;
                    } else {
                        $remainingItems[] = $item;
                    }
                }
                return array_merge($expiredItems, $expirySoonItems, $remainingItems);
            }
            if($isEssendantToggleEnabled) {
                return $subject->getQuote()->getAllVisibleItems();
            }
            return $subject->getQuote()->getAllItems();
        }
        if ($this->authHelper->isLoggedIn()) {
            foreach ($subject->getQuote()->getAllItems() as $item) {
                $itemId = $item->getId();
                if (is_array($expiredInstanceIds) && in_array($itemId, $expiredInstanceIds)) {
                    $expiredItems[] = $item;
                } elseif ($this->expiredItem->isItemExpiringSoon($itemId)) {
                    $expyrySoonItems[] = $item;
                } else {
                    $remainingItems[] = $item;
                }
            }
            return array_merge($expiredItems, $expyrySoonItems, $remainingItems);
        }
        if($isEssendantToggleEnabled){
            return $subject->getQuote()->getAllVisibleItems();
        }
        return $subject->getQuote()->getAllItems();
    }
}
