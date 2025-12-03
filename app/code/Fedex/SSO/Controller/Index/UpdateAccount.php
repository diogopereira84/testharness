<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Index;

use Fedex\SSO\Helper\Data;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * UpdateAccount Controller class
 */
class UpdateAccount implements ActionInterface
{
    public const CUSTOMER_ACCOUNT_URL = "customer/account";

    /**
     * Customer update account constructor
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param Data $ssoHelper
     * @param LoggerInterface $logger
     * @param CookieManagerInterface $cookieManager
     * @param SsoConfiguration $ssoConfiguration
     * @param Session $customerSession
     */
    public function __construct(
        private RedirectFactory $resultRedirectFactory,
        protected Data $ssoHelper,
        protected LoggerInterface $logger,
        protected CookieManagerInterface $cookieManager,
        protected SsoConfiguration $ssoConfiguration,
        protected Session $customerSession
    )
    {
    }

    /**
     * Update Fcl Customer Account Action
     *
     * @return $this
     */
    public function execute()
    {
        try {
            $this->customerSession->unsFclFdxLogin();
            $endUrl = $this->ssoConfiguration->getConfigValue("sso/general/profile_api_url");
            if ($this->ssoHelper->getFCLCookieNameToggle()) {
                $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
                $fdxLogin = $this->cookieManager->getCookie($cookieName);
            } else {
                $fdxLogin = $this->cookieManager->getCookie('fdx_login');
            }
            if (!empty($fdxLogin)) {
                $getFCLProfile = $this->ssoHelper->getCustomerProfile($endUrl, $fdxLogin);
                return $this->resultRedirectFactory->create()->setPath(self::CUSTOMER_ACCOUNT_URL);
            }
        } catch (\Exception $e) {
            $this->customerSession->unsFclFdxLogin();
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ':' . $e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath(self::CUSTOMER_ACCOUNT_URL);
    }
}
