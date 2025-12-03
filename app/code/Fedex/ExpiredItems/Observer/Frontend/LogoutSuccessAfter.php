<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\ExpiredItems\Observer\Frontend;

use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class LogoutSuccessAfter Observer
 */
class LogoutSuccessAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * init constructor
     *
     * @param ExpiredItem $expiredItemHelper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        protected ExpiredItem $expiredItemHelper,
        protected CustomerSession $customerSession
    )
    {
    }

    /**
     * Execute Method
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->expiredItemHelper->clearExpiredModalCookie();
        $this->customerSession->unsExpiredItemIds();
        $this->customerSession->unsExpiredMessage();
        $this->customerSession->unsExpiryMessage();
    }
}
