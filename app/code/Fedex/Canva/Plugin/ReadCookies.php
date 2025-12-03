<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Plugin;

use Fedex\SSO\Model\Session as SSOSession;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Router\Base as RouterBase;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Fedex\SSO\Observer\ReadCookies as ReadCookiesObserver;

class ReadCookies
{
    private const CANVA_LAST_PRODUCT_URL = 'canva_last_product_url';
    private const CANVA_ERROR_PROFILE_POPUP = 'canva_error_profile_popup';

    public function __construct(
        Session $customerSession,
        private ResponseFactory $responseFactory,
        private CookieManagerInterface $cookieManager,
        private CookieMetadataFactory $cookieMetadataFactory,
        private SSOSession $session,
        private RouterBase $router,
        private LoggerInterface $logger
    )
    {
    }

    public function afterExecute(ReadCookiesObserver $subject, $result, $arguments)
    {
        try {
            $action = "";
            if ($arguments->getData('request')) {
                $action = $this->router->match($arguments->getData('request'));
            }
            if (($this->session->getProfileRetrieveError() || $this->session->getLoginError())
                    && is_a($action, \Fedex\Canva\Controller\Index\Index::class)) {
                $cookieMeta = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setDuration(60)
                    ->setPath('/')
                    ->setHttpOnly(false);
                $this->cookieManager->setPublicCookie(self::CANVA_ERROR_PROFILE_POPUP, true, $cookieMeta);
                $this->responseFactory->create()
                    ->setRedirect($this->cookieManager->getCookie(self::CANVA_LAST_PRODUCT_URL) ?? '/')
                    ->sendResponse();
                $this->session->setProfileRetrieveError(false);
                $this->session->setLoginError(false);
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Canva profile error.');
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Error while reading cookie: ' . $e->getMessage());
        }
    }
}
