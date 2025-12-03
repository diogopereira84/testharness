<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Backend\Handler;

use Magento\Backend\Model\Auth\Session as UserSession;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Security\Model\AdminSessionInfo;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\Security\Model\AdminSessionsManager;
use Magento\User\Model\User;

class Auth
{
    /**
     * @param AdminSessionsManager $adminSessionsManager
     * @param EventManager $eventManager
     * @param UserResource $userResource
     * @param UserSession $userSession
     */
    public function __construct(
        private AdminSessionsManager $adminSessionsManager,
        private EventManager $eventManager,
        private UserResource $userResource,
        private UserSession $userSession
    )
    {
    }

    /**
     * Login User
     *
     * @param User $user
     * @return void
     */
    public function login(User $user): void
    {
        $this->userResource->unlock($user->getId());
        $this->userSession->setUser($user);

        $this->adminSessionsManager->getCurrentSession()->load($this->userSession->getSessionId());

        $sessionInfo = $this->adminSessionsManager->getCurrentSession();
        $sessionInfo->setData('updated_at', time());
        $sessionInfo->setData('status', AdminSessionInfo::LOGGED_IN);

        $this->adminSessionsManager->processLogin();
        $this->eventManager->dispatch(
            'backend_auth_user_login_success',
            ['user' => $user]
        );
    }
}
