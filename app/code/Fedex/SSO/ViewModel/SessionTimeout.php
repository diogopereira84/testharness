<?php
/**
 * @category Fedex
 * @package  Fedex_SSO
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\ViewModel;

use Fedex\SSO\Model\SessionTimeoutMessaging;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\SSO\Block\LoginInfo;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Fedex\Delivery\Helper\Data;

class SessionTimeout implements ArgumentInterface
{
    public const COOKIE_LIFETIME= 'cookie_lifetime';

    /**
     * @param SessionTimeoutMessaging $config
     * @param ToggleConfig $toggleConfig
     * @param LoginInfo $loginInfo
     * @param Data $helper
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     */
    public function __construct(
        private SessionTimeoutMessaging $config,
        private ToggleConfig $toggleConfig,
        private LoginInfo $loginInfo,
        private Data $helper,
        private GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
    )
    {
    }

    /**
     * Get session warning time
     *
     * @return mixed|string
     */
    public function getSessionWarningTime()
    {
        return $this->config->getSessionWarningTime()??'';
    }

    /**
     * Get session warning primary message
     *
     * @return mixed
     */
    public function getSessionWarningPMessage()
    {
        return $this->config->getSessionWarningPMessage()??'';
    }

    /**
     * Get session warning secondary message
     *
     * @return mixed
     */
    public function getSessionWarningSMessage()
    {
        return $this->config->getSessionWarningSMessage()??'';
    }

    /**
     * Get session expired primary message
     *
     * @return mixed
     */
    public function getSessionExpiredPMessage()
    {
        return $this->config->getSessionExpiredPMessage()??'';
    }

    /**
     * Get session expired secondary message
     *
     * @return mixed
     */
    public function getSessionExpiredSMessage()
    {
        return $this->config->getSessionExpiredSMessage()??'';
    }

    /**
     * Get cookie lifetime
     *
     * @return mixed
     */
    public function getWebConfig()
    {
        return $this->loginInfo->getWebCookieConfig(self::COOKIE_LIFETIME);
    }

    /**
     * Get IsEpro toggle value
     *
     * @return bool
     */
    public function getIsEproUser()
    {
        return ($this->helper->isEproCustomer()==false)?0:1;
    }

    /**
     * Get impersonator timeout toggle value
     *
     * @return bool
     */
    public function getImpersonatorToggle()
    {
        return $this->toggleConfig
        ->getToggleConfigValue('mazegeeks_ctc_admin_impersonator');
    }

    /**
     * Get Impersonator Admin Id
     *
     */
    public function getAdminId()
    {
        return $this->getLoggedAsCustomerAdminId->execute()??'';
    }
}
