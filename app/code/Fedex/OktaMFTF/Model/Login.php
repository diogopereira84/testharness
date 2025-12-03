<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Model;

use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Security\Model\AdminSessionInfo;
use Magento\Security\Model\AdminSessionsManager;
use Magento\User\Model\User;

use Magento\Backend\Model\Auth\Session as UserSession;
use Magento\User\Model\ResourceModel\User as UserResource;


class Login
{
    public function __construct(
        private AdminSessionsManager $adminSessionsManager,
        private EventManager $eventManager,
        private UserResource $userResource,
        private UserSession $userSession,
        private GeneralConfig $generalConfig
    )
    {
    }

    public function authenticate(User $user)
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
