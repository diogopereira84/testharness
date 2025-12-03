<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Plugin;

use Fedex\OktaMFTF\Gateway\Okta;
use Fedex\OktaMFTF\Model\Config\Credentials as Config;
use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\User\Model\User;

class TokenGeneration
{
    /**
     * @param TokenModelFactory $tokenModelFactory
     * @param GeneralConfig $generalConfig
     * @param RequestInterface $request
     * @param User $user
     * @param Okta $oktaGateway
     */
    public function __construct(
        private TokenModelFactory $tokenModelFactory,
        private GeneralConfig $generalConfig,
        private RequestInterface $request,
        private User $user,
        private Okta $oktaGateway
    )
    {
    }

    public function aroundCreateAdminAccessToken(
        \Magento\Integration\Model\AdminTokenService $subject,
        \Closure $proceed,
        $username,
        $password
    ) {
        if ($this->generalConfig->isEnabled()) {
            $this->request->setParams([
                'client_id' => $username,
                'client_secret' => $password
            ]);

            $token = $this->oktaGateway->token();
            $introspect = $this->oktaGateway->introspect($token->getAccessToken());

            if ($introspect->isActive()) {
                $user = $this->user->load($this->generalConfig->getAdminUser());
                return $this->tokenModelFactory->create()->createAdminToken($user->getId())->getToken();
            }
        }

        return $proceed($username, $password);
    }
}
