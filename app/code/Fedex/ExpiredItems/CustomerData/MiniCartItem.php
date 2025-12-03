<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\CustomerData;

use Magento\Checkout\CustomerData\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\CustomerData\ItemPoolInterface;
use Magento\Framework\View\LayoutInterface;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Framework\App\Http\Context;
use Magento\Customer\Model\Context as AuthContext;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth as AuthHelper;

/**
 * Get mini cart items class
 */
class MiniCartItem extends CustomerCart
{

    /**
     * Initilizing constructor
     *
     * @param Session $checkoutSession
     * @param Url $catalogUrl
     * @param Cart $checkoutCart
     * @param Data $checkoutHelper
     * @param ItemPoolInterface $itemPoolInterface
     * @param LayoutInterface $layout
     * @param ExpiredItem $expiredItem
     * @param Context $httpContext
     * @param CustomerSession $customerSession
     * @param AuthHelper $authHelper
     */
    public function __construct(
        Session $checkoutSession,
        Url $catalogUrl,
        Cart $checkoutCart,
        Data $checkoutHelper,
        ItemPoolInterface $itemPoolInterface,
        LayoutInterface $layout,
        protected ExpiredItem $expiredItem,
        private Context $httpContext,
        protected CustomerSession $customerSession,
        protected AuthHelper $authHelper
    ) {
        parent::__construct($checkoutSession, $catalogUrl, $checkoutCart, $checkoutHelper, $itemPoolInterface, $layout);
    }

    /**
     * Get array of last added items
     *
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getRecentItems()
    {
        $items = parent::getRecentItems();
        if ($this->authHelper->isLoggedIn()) {
            $expiredItems = [];
            $expyrySoonItems = [];
            $remainingItems = [];
            $expiredInstanceIds = $this->expiredItem->getExpiredInstanceIds();

            if (!empty($items)) {
                foreach ($items as $item) {
                    $itemId = $item['item_id'];
                    if (is_array($expiredInstanceIds) && in_array($itemId, $expiredInstanceIds)) {
                        $expiredItems[] = $item;
                    } elseif ($this->expiredItem->isItemExpiringSoon($itemId)) {
                        $expyrySoonItems[] = $item;
                    } else {
                        $remainingItems[] = $item;
                    }
                }
            }

            return array_merge(array_reverse($expiredItems), array_reverse($expyrySoonItems), $remainingItems);
        }

        return $items;
    }
}
