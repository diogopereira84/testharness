<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Model;

use Fedex\SSO\Api\Data\ConfigInterface;
use Fedex\SSO\Api\ProfileManagementInterface;
use Fedex\SSO\Helper\Data;
use Magento\Framework\Stdlib\CookieManagerInterface;

class ProfileManagement implements ProfileManagementInterface
{
    public const LOGIN_COOKIE_NAME = 'fdx_login';

    public function __construct(
        private ConfigInterface $config,
        private CookieManagerInterface $cookieManager,
        private Data $helper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function isCustomerLoggedIn(): bool
    {
        if ($this->helper->getFCLCookieNameToggle()) {
            $cookieName = $this->helper->getFCLCookieConfigValue();
        } else {
            $cookieName = self::LOGIN_COOKIE_NAME;
        }
        return (bool)$this->helper->getCustomerProfile(
            $this->config->getProfileApiUrl(),
            $this->cookieManager->getCookie($cookieName)
        );
    }
}
