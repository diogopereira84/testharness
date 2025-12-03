<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Observer\Frontend\Controller;

use Exception;
use Fedex\Canva\Model\CanvaCredentials;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Backend\Model\Session;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\Login\Helper\Login;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\ActionFlag;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Base\Helper\Auth as AuthHelper;

class ActionPredispatchEmailVerification implements ObserverInterface
{
    /**
     * Redirect on home page for visitor Constructor
     *
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param Login $login
     * @param ToggleConfig $toggleConfig
     * @param ActionFlag $actionFlag
     * @param SessionFactory $sessionFactory
     * @param UrlInterface $urlInterface
     * @param CookieManagerInterface $cookieManager
     * @param EventManager $eventManager
     * @param DataPersistorInterface $dataPersistor
     * @param Session $sessionS
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param SsoConfiguration $ssoConfiguration
     * @param SSOHelper $ssoHelper
     * @param CanvaCredentials $canvaCredentials
     * @param AuthHelper $authHelper
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected StoreManagerInterface $storeManager,
        protected Login $login,
        protected ToggleConfig $toggleConfig,
        protected ActionFlag $actionFlag,
        protected SessionFactory $sessionFactory,
        protected UrlInterface $urlInterface,
        private CookieManagerInterface $cookieManager,
        private EventManager $eventManager,
        private DataPersistorInterface $dataPersistor,
        private Session $sessionS,
        private CookieMetadataFactory $cookieMetadataFactory,
        private \Magento\Customer\Model\Session $customerSession,
        private SsoConfiguration $ssoConfiguration,
        private SSOHelper $ssoHelper,
        private CanvaCredentials $canvaCredentials,
        protected AuthHelper $authHelper
    )
    {
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return ActionPredispatch
     */
    public function execute(Observer $observer)
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_user_email_verification_redirect')) {
            $controllerAction = $observer->getControllerAction();
            $isValidAction = $controllerAction && method_exists($controllerAction, 'getResponse');

            if (!$isValidAction) {
                return $this;
            }
        }

        if ($this->authHelper->isLoggedIn()) {
            try {
                $metadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setDomain(".fedex.com")
                    ->setPath("/")
                    ->setHttpOnly(false)
                    ->setSecure(true)
                    ->setSameSite("None");

                $sdeCookieMetadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setPath("/")
                    ->setHttpOnly(false);

                $customerId = $this->customerSession->getId();
                $this->cookieManager->deleteCookie(SSOHelper::SDE_COOKIE_NAME, $metadata);
                $this->cookieManager->deleteCookie(SSOHelper::FORGE_ROCK_COOKIE_NAME, $metadata);
                $this->cookieManager
                    ->deleteCookie(SdeHelper::CUSTOMER_ACTIVE_SESSION_COOKIE_NAME, $sdeCookieMetadata);
                $this->customerSession->unsFclFdxLogin();
                if ($this->ssoHelper->getFCLCookieNameToggle()) {
                    $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
                    $this->cookieManager->deleteCookie($cookieName, $metadata);
                } else {
                    $this->cookieManager->deleteCookie("fdx_login", $metadata);
                }
                $this->cookieManager->deleteCookie("fcl_customer_login_success", $metadata);
                $this->cookieManager->deleteCookie("fcl_customer_login", $metadata);
                $this->canvaCredentials->fetch();

                    //L6 Cookie for Logout
                $this->cookieManager->deleteCookie('b2ef1b160e192c2', $metadata);

                //PROD Cookie for Logout
                $this->cookieManager->deleteCookie('ab45335bc623e59', $metadata);
                $this->ssoHelper->callFclLogoutApi();
                $this->customerSession->logout()->setLastCustomerId($customerId);
                $this->eventManager->dispatch('user_logout_success', []);
                $currentUrl = $this->urlInterface->getCurrentUrl();
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $currentUrl);
                $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $observer->getControllerAction()->getResponse()->setRedirect($currentUrl);
            } catch (Exception $e) {
                $this->customerSession->unsFclFdxLogin();
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ':Unable to do logout for ' . $this->customerSession->getCustomerId() . ' with error: ' . $e->getMessage());
            }
        }

        return $this;
    }
}
