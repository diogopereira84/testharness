<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Model;

class Order
{
    /**
     * @inheritDoc
     */
    public function __construct(
        private \Fedex\Orderhistory\Helper\Data $helper,
        private \Fedex\SelfReg\Helper\SelfReg $selfRegHelper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function afterGetRealOrderId(
        \Magento\Sales\Model\Order $subject,
        $result
    ) {
        if ($this->helper->isModuleEnabled() == true &&
                !$this->selfRegHelper->isSelfRegCustomer()) { //D-128365 : show incrementId in selfReg
            $result = ($subject->getExtOrderId() != '') ? $subject->getExtOrderId() : $subject->getIncrementId();
        }
        return $result;
    }
}
