<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\OKTA\Plugin;

use Magento\Backend\Model\Auth\Session;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Magento\User\Model\User;
use Magento\Framework\Acl\Builder;

class SkipAdminPasswordReset
{
    /**
     * SkipPerformIdentityCheck constructor.
     * @param OktaHelper $oktaHelper
     * @param Builder $aclBuilder
     */
    public function __construct(
        private OktaHelper $oktaHelper,
        private Builder $aclBuilder
    )
    {
    }

    /**
     * @param Session $subject
     * @param callable $proceed
     * @param User|null $user
     * @return $this
     * @throws \Exception
     */
    public function aroundRefreshAcl(
        Session $subject,
        callable $proceed,
        User $user = null
    ) {
        if (!$this->oktaHelper->isEnabled()) {
            return $proceed($user);
        }
        if ($user === null) {
            $user = $subject->getUser();
        }
        if (!$user) {
            return $this;
        }
        if (!$subject->getAcl() || $user->getReloadAclFlag()) {
            $subject->setAcl($this->aclBuilder->getAcl());
        }
        /**
         * Skip reset password to avoid password validation when redirecting
         */
        if ($user->getReloadAclFlag()) {
            $user->setReloadAclFlag(0)->save();
        }
        return $this;
    }
}
