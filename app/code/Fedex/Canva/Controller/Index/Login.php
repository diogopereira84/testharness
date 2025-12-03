<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Canva\Controller\Index;

use Exception;
use Psr\Log\LoggerInterface;
use Fedex\SSO\Model\Login as LoginModal;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * CustomerLoginInfo Block class
 */
class Login implements ActionInterface
{
    private const CANVA_LAST_PRODUCT_URL = 'canva_last_product_url';
    private const CANVA_ERROR_PROFILE_POPUP = 'canva_error_profile_popup';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param LoginModal $login
     */
    public function __construct(
        private Context $context,
        private Session                               $session,
        private ResponseFactory                       $responseFactory,
        private CookieManagerInterface                $cookieManager,
        private CookieMetadataFactory                 $cookieMetadataFactory,
        private LoggerInterface                       $logger,
        protected LoginModal                            $login
    )
    {
    }

    /**
     * Login post action
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        try {
            $redirect = $this->context->getResultFactory()->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl('/canva');
            $this->login->isCustomerLoggedIn();
            if ($this->session->getProfileRetrieveError() || $this->session->getLoginError()) {
                $cookieMeta = $this->cookieMetadataFactory->createPublicCookieMetadata();
                $cookieMeta->setDuration(60);
                $cookieMeta->setPath('/');
                $cookieMeta->setHttpOnly(false);
                $this->cookieManager->setPublicCookie(self::CANVA_ERROR_PROFILE_POPUP, true, $cookieMeta);
                $this->session->setProfileRetrieveError(false);
                $this->session->setLoginError(false);
                $redirect->setUrl($this->cookieManager->getCookie(self::CANVA_LAST_PRODUCT_URL) ?? '/');
            }
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while reading cookie: ' . $e->getMessage());
        }

        return $redirect;
    }
}
