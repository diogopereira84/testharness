<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\ExpiredItems\ViewModel;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Magento\Framework\App\Http\Context;
use Magento\Customer\Model\Context as AuthContext;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;

class ExpiryMessages implements ArgumentInterface
{
    /**
     * @var array $expiredItems
     */
    public $expiredItems = [];

    /**
     * @var string
     */
    private $expiredItemsTransactionID = '';

    /**
     * Initializing constructor
     *
     * @param ConfigProvider $configProvider
     * @param Context $httpContext
     * @param ExpiredItem $expiredItem
     * @param Http $request
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private ConfigProvider $configProvider,
        private Context $httpContext,
        private ExpiredItem $expiredItem,
        protected Http $request,
        protected CheckoutSession $checkoutSession,
        protected CustomerSession $customerSession,
        protected StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * To get config provider class instance
     *
     * @return ConfigProvider
     */
    public function getConfig()
    {
        return $this->configProvider;
    }

    /**
     * To check customer login
     *
     * @return boolean
     */
    public function isCustomerLoggedIn()
    {
        return true;
    }

    /**
     * To check if cart has expired items
     *
     * @return array
     */
    public function getExpiredItems()
    {
        if (empty($this->expiredItems)) {
            $this->expiredItems = $this->expiredItem->getExpiredInstanceIds();
        }

        return $this->expiredItems;
    }

    /**
     * check if cart has transaction ID
     *
     * @return string
     */
    public function getExpiredItemsTransactionID()
    {
        if (empty($this->expiredItemsTransactionID)) {
            $this->expiredItemsTransactionID = $this->expiredItem->getExpiredInstanceIdsTransactionID();
        }

        return $this->expiredItemsTransactionID;
    }

    /**
     * To check if item in cart is expired
     *
     * @param Object $item
     * @return boolean
     */
    public function isItemExpired($item)
    {
        if (is_array($this->getExpiredItems()) &&
        in_array($item->getId(), $this->getExpiredItems())) {
            return true;
        }
        return $this->expiredItem->isItemExpired($item);
    }

    /**
     * To check if bundle item in cart is expired
     *
     * @param Object $item
     * @return boolean
     */
    public function isBundleItemExpired($item)
    {
        if($item && $item->getProductType() == Type::TYPE_BUNDLE && $item->getChildren()) {
            foreach ($item->getChildren() as $child) {
                if ($this->isItemExpired($child)) {
                    return true;
                }
            }
        }

        return false;
    }

   /**
    * To check canva path
    *
    * @return boolean
    */
    public function isCanvaPage()
    {
        return ($this->request->getFullActionName() == 'canva_index_index') ? true : false ;
    }

    /**
     * To check if item in cart is expiring soon
     *
     * @param int $itemId
     * @return boolean
     */
    public function isItemExpiringSoon($itemId)
    {
        return $this->expiredItem->isItemExpiringSoon($itemId);
    }

    /**
     * To check if bundle item in cart is expiring soon
     *
     * @param $quoteItem
     * @return boolean
     */
    public function isBundleItemExpiringSoon($quoteItem)
    {
        return $this->expiredItem->isBundleItemExpiringSoon($quoteItem);
    }

    /**
     * To check if any expiring soon item exist in cart
     *
     * @return boolean
     */
    public function isAnyItemExpiringSoon()
    {
        $allVisibleItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
        foreach ($allVisibleItems as $item) {
            if($item->getProductType() == Type::TYPE_BUNDLE && $item->getChildren()) {
                if ($this->isBundleItemExpiringSoon($item)) {
                    return true;
                }
                continue;
            }

            if ($this->expiredItem->isItemExpiringSoon($item->getItemId())
            && (empty($this->expiredItems) || (is_array($this->expiredItems)
            && !in_array($item->getItemId(), $this->expiredItems)))) {
                return true;
            }
        }

        return false;
    }
}
