<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Customer;

use Exception;
use Fedex\Canva\Model\CanvaCredentials;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SSO\Model\Config;

/**
 * Logout Controller class
 */
class Logout implements ActionInterface
{
    /** @var string */
    private const USER_LOGOUT_SUCCESS = 'user_logout_success';

    /**
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * Logout constructor
     *
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param EventManager $eventManager
     * @param Session $customerSession
     * @param LoggerInterface $logger
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CanvaCredentials $canvaCredentials
     * @param SdeHelper $sdeHelper
     * @param ToggleConfig $toggleConfig
     * @param SSOHelper $ssoHelper
     * @param Config $ssoConfiguration
     */
    public function __construct(
        private RequestInterface $request,
        private ResultFactory $resultFactory,
        private EventManager $eventManager,
        protected Session $customerSession,
        protected LoggerInterface $logger,
        private CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        private CanvaCredentials $canvaCredentials,
        protected SdeHelper $sdeHelper,
        protected ToggleConfig $toggleConfig,
        private SSOHelper $ssoHelper,
        private Config $ssoConfiguration
    ) {
        $this->ssoHelper = $ssoHelper;
        $this->ssoConfiguration = $ssoConfiguration;
    }

    /**
     * Customer logout from application
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $isLoggedout = 0;
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
            if ($customerId) {
                $params = $this->request->getParams();
                if (isset($params['login_method']) && $params['login_method'] == 'commercial_store_sso') {
                    $this->cookieManager->deleteCookie(SSOHelper::SDE_COOKIE_NAME, $metadata);
                    $this->cookieManager->deleteCookie(SSOHelper::FORGE_ROCK_COOKIE_NAME, $metadata);
                    $this->cookieManager
                        ->deleteCookie(SdeHelper::CUSTOMER_ACTIVE_SESSION_COOKIE_NAME, $sdeCookieMetadata);
                } else {
                    $this->customerSession->unsFclFdxLogin();
                    if ($this->ssoHelper->getFCLCookieNameToggle()) {
                        $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
                        $this->cookieManager->deleteCookie($cookieName, $metadata);
                    }
                    $this->cookieManager->deleteCookie("fdx_login", $metadata);
                    $this->cookieManager->deleteCookie("fcl_customer_login_success", $metadata);
                    $this->cookieManager->deleteCookie("fcl_customer_login", $metadata);
                    $this->canvaCredentials->fetch();
                }

                //L6 Cookie for Logout
                $this->cookieManager->deleteCookie('b2ef1b160e192c2', $metadata);

                //PROD Cookie for Logout
                $this->cookieManager->deleteCookie('ab45335bc623e59', $metadata);

                $this->ssoHelper->callFclLogoutApi();

                $this->customerSession->logout()->setLastCustomerId($customerId);

                $this->eventManager->dispatch(self::USER_LOGOUT_SUCCESS, []);
                $isLoggedout = 1;
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ':User logged out due to inactive session.');
            }
        } catch (Exception $e) {
            $this->customerSession->unsFclFdxLogin();
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ':Unable to do logout for ' . $this->customerSession->getCustomerId() . ' with error: ' . $e->getMessage());
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setContents($isLoggedout);

        return $response;
    }
}
