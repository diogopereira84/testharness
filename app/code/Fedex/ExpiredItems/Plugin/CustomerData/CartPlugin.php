<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Plugin\CustomerData;

use Fedex\ExpiredItems\Model\ConfigProvider;
use Magento\Checkout\CustomerData\Cart;
use Magento\Framework\App\Http\Context;
use Magento\Customer\Model\Context as AuthContext;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth as AuthHelper;

/**
 * Plugin Class CartPlugin
 */
class CartPlugin
{

    /**
     * Initiliazing constructor
     *
     * @param Context $httpContext
     * @param ExpiredItem $expiredItem
     * @param CustomerSession $customerSession
     * @param AuthHelper $authHelper
     * @param ConfigProvider $expiredConfig
     */
    public function __construct(
        private Context $httpContext,
        private ExpiredItem $expiredItem,
        protected CustomerSession $customerSession,
        protected AuthHelper $authHelper,
        protected ConfigProvider $expiredConfig,
        protected \Fedex\ProductBundle\Api\ConfigInterface $bundleConfigInterface
    ) {
    }

    /**
     * Send config data to minicart
     *
     * @param  object $subject
     * @param  array $result
     * @return array
     */
    public function afterGetSectionData(Cart $subject, $result)
    {
        $isLoggedIn = $this->authHelper->isLoggedIn();
        if ($isLoggedIn || ($this->expiredItem->getExpiredInstanceIds())) {
            $expiredInstanceIds = $this->expiredItem->getExpiredInstanceIds();
            if ($result['items']) {
                foreach ($result['items'] as &$item) {
                    if (is_array($expiredInstanceIds) && in_array($item['item_id'], $expiredInstanceIds)) {
                        $item['is_expired'] = true;
                        $result['is_expired'] = true;
                        $result['expired_msg'] = $this->customerSession->getExpiredMessage();
                    } elseif (is_array($expiredInstanceIds) && !empty($item['childrenItemsIds'])
                        && $this->isExpiredBundleChildrenInside($item['childrenItemsIds'], $expiredInstanceIds)) {
                        $item['is_expired'] = true;
                        $result['is_expired'] = true;
                        $result['expired_msg'] = $this->customerSession->getExpiredMessage();
                    } elseif ($this->expiredItem->isItemExpiringSoon($item['item_id'])) {
                        $item['is_expiry'] = true;
                        $result['is_expiry'] = true;
                        $result['expiry_msg'] = $this->customerSession->getExpiryMessage();
                    }
                }
            }

            $result['product_engine_expired'] = $this->customerSession->getExpiredMessage();
        }

        return $result;
    }

    private function isExpiredBundleChildrenInside(array $childrenItemsIds, array $expiredInstanceIds): bool
    {
        if($this->bundleConfigInterface->isTigerE468338ToggleEnabled()) {
            foreach ($childrenItemsIds as $childItemId) {
                if (in_array($childItemId, $expiredInstanceIds)) {
                    return true;
                }
            }
        }
        return false;
    }
}
