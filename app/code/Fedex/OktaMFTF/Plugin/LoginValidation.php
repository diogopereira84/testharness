<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Plugin;

use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Fedex\OktaMFTF\Gateway\Okta;
use Fedex\OktaMFTF\Model\Login;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\User\Model\User;
use Psr\Log\LoggerInterface;

class LoginValidation
{
    public function __construct(
        protected ManagerInterface $messageManager,
        protected UrlInterface $urlInterface,
        private RequestInterface $request,
        private GeneralConfig $generalConfig,
        private Login $login,
        private User $user,
        private Okta $oktaGateway,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function aroundExecute(
        \Fedex\OKTA\Controller\Rewrite\Adminhtml\Backend\Auth\Login $subject,
        \Closure $proceed
    ) {
        if (!$this->generalConfig->isEnabled() || (!$this->request->getParam('client_id') || !$this->request->getParam('client_secret'))) {
            return $proceed();
        }

        $token = $this->oktaGateway->token();
        $introspect = $this->oktaGateway->introspect($token->getAccessToken());

        if ($introspect->isActive()) {
            $user = $this->user->load($this->generalConfig->getAdminUser());
            $this->login->authenticate($user);
            $url = $this->urlInterface->getUrl('admin');
            return $subject->getResponse()->setRedirect($url);
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' User is unable to login.');
            throw new \Exception("Unable to login");
        }
    }
}
