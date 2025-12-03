<?php

/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Plugin\Model\Email;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;


class Sender
{
    /**
     * Data construct
     *
     * @param ToggleConfig $toggleConfig
     * @param SelfReg $selfReg
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private SelfReg $selfReg
    )
    {
    }

    /**
     *
     * @param \Magento\Company\Model\Email\Sender $subject
     * @param callable $proceed
     */

    public function aroundSendUserStatusChangeNotificationEmail($subject, callable $proceed, $customer, $status)
    {
        if ($this->selfReg->isSelfRegCustomer()) {
            return $subject;
        }
        return $proceed($customer, $status);
    }
}
