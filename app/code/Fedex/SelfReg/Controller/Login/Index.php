<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Login;

use Fedex\SSO\Helper\Data as SSOHelper;

class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * Constructor
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Fedex\SSO\ViewModel\SsoConfiguration $ssoConfig
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Fedex\SelfReg\Helper\SelfReg $selfRegHelper
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Customer\Model\SessionFactory $sessionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private \Fedex\SSO\ViewModel\SsoConfiguration $ssoConfig,
        private \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        private \Fedex\SelfReg\Helper\SelfReg $selfRegHelper,
        private \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        private \Magento\Customer\Model\SessionFactory $sessionFactory,
        private \Psr\Log\LoggerInterface $logger,
        protected SSOHelper $ssoHelper
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     * B-1320022 - WLGN integration for selfReg customer
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->ssoHelper->getFCLCookieNameToggle()) {
            $cookieName = $this->ssoHelper->getFCLCookieConfigValue();
            $fdxLogin = $this->cookieManager->getCookie($cookieName);
        } else {
            $fdxLogin = $this->cookieManager->getCookie('fdx_login');
        }
        if (!empty($fdxLogin)) {
            $endUrl = $this->ssoConfig->getConfigValue("sso/general/profile_api_url");

            $loginResponse = $this->selfRegHelper->selfRegWlgnLogin($endUrl, $fdxLogin);
            if (isset($loginResponse['error']) && $loginResponse['error']) {
                // logout first from WLGN and then show error page at Magento end
                $redirectUrl = $this->url->getUrl('selfreg/login/fail');
                $wlgnLogoutPageUrl = $this->ssoConfig->getGeneralConfig('fcl_logout_url');
                $queryParameter = $this->ssoConfig->getGeneralConfig('fcl_logout_query_param');
                $wlgnLogoutUrl = $wlgnLogoutPageUrl . '?' . $queryParameter . '=' . $redirectUrl;

                // logout first from WLGN and then show error page at Magento end
                $url = $wlgnLogoutUrl;
            } else {
                // redirect to Home page
                $url = $loginResponse['redirectUrl'];
            }
        } else {
            $this->logger->info("SelfReg WLGN Error: Not logged-in at WLGN end. Cookie not created.");
            $this->sessionFactory->create()->setSelfRegLoginError(
                'Not logged-in at WLGN end. Please try to registration/login again.'
            );
            $url = $this->url->getUrl('selfreg/login/fail');
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}
