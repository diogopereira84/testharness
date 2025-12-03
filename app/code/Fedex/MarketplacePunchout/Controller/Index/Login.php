<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2024 FedEx
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Controller\Index;

use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Model\FclLogin;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Psr\Log\LoggerInterface;

class Login extends Action implements HttpPostActionInterface
{const AUTH_URL = 'oauth/index/index/rc/';

    const REDIRECT_URL = 'marketplacepunchout/index/ajax';

    const LOGIN_ACTION = 'login';

    const REGISTER_ACTION = 'register';

    /**
     * @param Context $context
     * @param NonCustomizableProduct $nonCustomizableProductModel
     * @param LoggerInterface $logger
     * @param Session $customerSession
     * @param SsoConfiguration $ssoConfiguration
     * @param FclLogin $fclLogin
     */
    public function __construct(
        private Context          $context,
        private NonCustomizableProduct $nonCustomizableProductModel,
        private LoggerInterface $logger,
        private Session          $customerSession,
        private SsoConfiguration $ssoConfiguration,
        private FclLogin $fclLogin
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->context->getResultRedirectFactory()->create();
        $resultRedirect->setUrl($this->ssoConfiguration->getHomeUrl());

        try {
            if ($this->nonCustomizableProductModel->isMktCbbEnabled()) {
                $request = $this->getRequest();
                if ($request->isPost() && $this->validateRequest($request->getPost())) {
                    if (!$this->customerSession->isLoggedIn()) {
                        $this->customerSession->setSellerConfigurationData($request->getPostValue('configuration_data'));
                        $this->customerSession->setProductConfigData($request->getPostValue('product_config_data'));

                        $actionType = $request->getPostValue('action_type');

                        $fclPageUrl = $actionType === SELF::LOGIN_ACTION ?
                            $this->ssoConfiguration->getGeneralConfig('wlgn_login_page_url') :
                            $this->ssoConfiguration->getGeneralConfig('register_url');

                        $queryParameter = $actionType === SELF::LOGIN_ACTION ?
                            $this->ssoConfiguration->getGeneralConfig('query_parameter') :
                            $this->ssoConfiguration->getGeneralConfig('register_url_param');

                        $currentUrl = $this->ssoConfiguration->getHomeUrl() . SELF::REDIRECT_URL . '?t=' . $this->fclLogin->getTimeStamp();

                        $registerUrl = $fclPageUrl . $queryParameter . '/' . SELF::AUTH_URL . base64_encode($currentUrl);
                        $loginUrl = $fclPageUrl . '?' . $queryParameter . '=' . $this->ssoConfiguration->getHomeUrl() . SELF::AUTH_URL . base64_encode($currentUrl);

                        $resultRedirect->setUrl($actionType === SELF::LOGIN_ACTION ? $loginUrl : $registerUrl);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Stack trace: ' . $e->getTraceAsString() .
                ' Exception during Login/Register process from Marketplace Configurator' .
                $e->getMessage()
            );
        }

        return $resultRedirect;
    }

    /**
     * @param mixed $post
     * @return bool
     */
    private function validateRequest(mixed $post): bool
    {
        try {
            if ((isset($post['configuration_data']) && !empty($post['configuration_data']))
                && (isset($post['product_config_data']) && !empty($post['product_config_data']))
                && (isset($post['form_key']) && !empty($post['form_key']))
                && (isset($post['action_type']) && !empty($post['action_type'])
                    && in_array($post['action_type'], [SELF::LOGIN_ACTION, SELF::REGISTER_ACTION]))) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                ' Stack trace: ' . $e->getTraceAsString() .
                ' Invalid request during Login/Register process from Marketplace Configurator' .
                $e->getMessage()
            );
        }

        return false;
    }
}
