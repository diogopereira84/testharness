<?php

/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Plugin\Quote;

use \Magento\Framework\Serialize\Serializer\Json;
use Magento\Customer\Model\Session as CustomerSession;

class Item
{
    /**
     * @param Json $serializer
     * @param CustomerSession $customerSession
     */
    public function __construct(
        private Json $serializer,
        protected CustomerSession $customerSession
    )
    {
    }

    /**
     * Plugin function
     *
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param callable $proceed
     * @return DataObject
     */
    public function aroundGetBuyRequest(
        $subject,
        callable $proceed
    ) {
        try {
            $option = $subject->getOptionByCode('info_buyRequest');
            $data = $option ? $this->serializer->unserialize($option->getValue()) : [];
        } catch (\Exception $e) {

            $data = [];
            $buyRequest = new \Magento\Framework\DataObject($data);
            // Overwrite standard buy request qty, because item qty could have changed since adding to quote
            $buyRequest->setOriginalQty($buyRequest->getQty())->setQty($subject->getQty() * 1);
            return $buyRequest;
        }
        return $proceed();
    }

    /**
     * Item compare
     *
     * @param object $subject
     * @param boolean $result
     * @return boolean
     */
    public function afterCompare($subject, $result)
    {
        if ($this->customerSession->getCompareItem()) {
            $result = false;
        }

        return $result;
    }
}
