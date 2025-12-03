<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Plugin;

use Magento\User\Model\User;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;

class SkipPerformIdentityCheck
{
    /**
     * SkipPerformIdentityCheck constructor.
     * @param OktaHelper $oktaHelper
     */
    public function __construct(
        private OktaHelper $oktaHelper
    )
    {
    }

    /**
     *
     * @param User $subject
     * @param callable $proceed
     * @param $passwordString
     * @return User
     */
    public function aroundPerformIdentityCheck(
        User $subject,
        callable $proceed,
        $passwordString
    ) {
        if (!$this->oktaHelper->isEnabled()) {
            return $proceed($passwordString);
        }

        return $subject;
    }
}
