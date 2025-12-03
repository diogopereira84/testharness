<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;

class LockAttribute implements ObserverInterface
{
     
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $product = $observer->getProduct();
        $product->lockAttribute('admin_user_id');
    }
}
