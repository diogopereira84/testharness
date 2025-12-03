<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Backend;

use Magento\Backend\Model\Auth\Session as UserSession;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Security\Model\AdminSessionInfo;
use Magento\Security\Model\AdminSessionsManager;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Fedex\OKTA\Model\Backend\Handler\Auth;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\EntityDataValidator;
use Fedex\OKTA\Model\Oauth\OktaUserInfoInterface;
use Psr\Log\LoggerInterface as LoggerInterface;

class LoginHandler
{
    /**
     * @var AdminSessionsManager
     */
    private $adminSessionsManager;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @var UserSession
     */
    private $userSession;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param EntityProvider $entityProvider
     * @param EntityDataValidator $entityDataValidator
     * @param OktaUserInfoInterface $oktaUserInfo
     * @param OktaHelper $oktaHelper
     * @param Auth $auth
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CookieManagerInterface $cookieManager,
        private EntityProvider $entityProvider,
        private EntityDataValidator $entityDataValidator,
        private OktaUserInfoInterface $oktaUserInfo,
        private OktaHelper $oktaHelper,
        private Auth $auth,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param string $accessToken
     * @throws AlreadyExistsException
     * @throws AuthenticationException
     * @throws FailureToSendException
     * @throws InputException
     * @throws LocalizedException
     * @param LoggerInterface $logger
     */
    public function loginByToken(string $accessToken)
    {
        $adminUserData = json_decode($this->oktaUserInfo->getUserInfo(trim($accessToken)), true);
        $this->entityDataValidator->validate($adminUserData);
        $adminUser = $this->entityProvider->getOrCreateEntity($adminUserData);
        $this->validateAdminUser($adminUser);
        $this->auth->login($adminUser);
        $this->removeOauthNonceCookie();
    }

    /**
     * @throws FailureToSendException
     * @throws InputException
     */
    public function removeOauthNonceCookie()
    {
        $this->cookieManager->deleteCookie($this->oktaHelper->getNonceCookieName());
    }

    /**
     * @param User $adminUser
     * @return bool
     * @throws AuthenticationException
     */
    private function validateAdminUser(User $adminUser): bool
    {
        if (!$adminUser->getIsActive()) {
            $this->logger->info(__METHOD__.':'.__LINE__.' User did not sign in correctly or your account is temporarily disabled.');
            throw new AuthenticationException(
                __('You did not sign in correctly or your account is temporarily disabled.')
            );
        }

        if (!$adminUser->hasAssigned2Role($adminUser->getId())) {
            $this->logger->info(__METHOD__.':'.__LINE__.' User does not have the required permissions to access the website.');
            throw new AuthenticationException(
                __('You don’t have the required permissions to access the website.')
            );
        }

        return true;
    }
}
