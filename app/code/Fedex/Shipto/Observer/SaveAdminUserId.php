<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class SaveAdminUserId implements ObserverInterface
{
     public function __construct(
         private \Magento\Backend\Model\Auth\Session $authSession,
         /**
         * @param \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig
         */
        protected \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig
     )
    {
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        if($this->authSession->isLoggedIn()){
            $_product = $observer->getProduct();
            $adminUserId = $this->getCurrentUser();

            $_product->setAdminUserId($adminUserId);
        }
        return $this;
    }

    public function getCurrentUser()
    {
        return $this->authSession->getUser()->getId();
    }
}
