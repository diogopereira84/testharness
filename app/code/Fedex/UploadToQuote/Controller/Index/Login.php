<?php
/**
 * @category    Fedex
 * @package     Fedex_UploadToQuote
 * @copyright   Copyright (c) 2025 FedEx
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\UploadToQuote\Controller\Index;

use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Psr\Log\LoggerInterface;

class Login extends Action implements HttpPostActionInterface
{
    const AUTH_URL = 'oauth/index/index/rc/';

    const REDIRECT_URL = 'checkout';

    const LOGIN_ACTION = 'login';

    const REGISTER_ACTION = 'register';

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Session $customerSession
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        private Context          $context,
        private LoggerInterface  $logger,
        private Session          $customerSession,
        private SsoConfiguration $ssoConfiguration
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->context->getResultRedirectFactory()->create();

        try {
            $request = $this->getRequest();
            if (!$request->isPost() || !$this->validateRequest($request->getPost())) {
                return $this->redirectToHome($resultRedirect);
            }

            if ($this->customerSession->isLoggedIn()) {
                return $this->redirectToHome($resultRedirect);
            }

            $actionType = $request->getPostValue('action_type');
            $resultRedirect->setUrl($this->generateRedirectUrl($actionType));
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                '%s:%d Exception during Login/Register process: %s',
                __METHOD__,
                __LINE__,
                $e->getMessage()
            ));
        }

        return $resultRedirect;
    }

    private function redirectToHome($resultRedirect)
    {
        $resultRedirect->setUrl($this->ssoConfiguration->getHomeUrl());
        return $resultRedirect;
    }

    private function generateRedirectUrl(string $actionType): string
    {
        $fclPageUrl = $actionType === self::LOGIN_ACTION
            ? $this->ssoConfiguration->getGeneralConfig('wlgn_login_page_url')
            : $this->ssoConfiguration->getGeneralConfig('register_url');

        $queryParameter = $actionType === self::LOGIN_ACTION
            ? $this->ssoConfiguration->getGeneralConfig('query_parameter')
            : $this->ssoConfiguration->getGeneralConfig('register_url_param');

        $currentUrl = $this->ssoConfiguration->getHomeUrl() . self::REDIRECT_URL;

        return $actionType === self::LOGIN_ACTION
            ? $fclPageUrl . '?' . $queryParameter . '=' . $this->ssoConfiguration->getHomeUrl() . self::AUTH_URL . base64_encode($currentUrl)
            : $fclPageUrl . $queryParameter . '/' . self::AUTH_URL . base64_encode($currentUrl);
    }

    private function validateRequest(mixed $post): bool
    {
        try {
            $formKeyValid = isset($post['form_key']) && !empty($post['form_key']);
            $actionTypeValid = isset($post['action_type']) && !empty($post['action_type'])
                && in_array($post['action_type'], [self::LOGIN_ACTION, self::REGISTER_ACTION]);

            return $formKeyValid && $actionTypeValid;
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                '%s:%d Stack trace: %s Invalid request during Login/Register process from UploadToQuote for Retail %s',
                __METHOD__,
                __LINE__,
                $e->getTraceAsString(),
                $e->getMessage()
            ));
        }

        return false;
    }
}
