<?php
/**
 * @category Fedex
 * @package  Fedex_SSO
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SessionTimeoutMessaging
{
    public const SESSION_WARNING_TIME = 'session_warning_time';

    public const SESSION_WARNING_PRIMARY_MESSAGE = 'session_warning_primary_message';

    public const SESSION_WARNING_SECONDARY_MESSAGE = 'session_warning_secondary_message';

    public const SESSION_EXPIRED_PRIMARY_MESSAGE = 'session_expired_primary_message';

    public const SESSION_EXPIRED_SECONDARY_MESSAGE= 'session_expired_secondary_message';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }
    /**
     * Get session warning time
     *
     * @return mixed
     */
    public function getSessionWarningTime()
    {
        return $this->getConfig(self::SESSION_WARNING_TIME);
    }

    /**
     * Get session warning primary message
     *
     * @return mixed
     */
    public function getSessionWarningPMessage()
    {
        return $this->getConfig(self::SESSION_WARNING_PRIMARY_MESSAGE);
    }

    /**
     * Get session warning secondary message
     *
     * @return mixed
     */
    public function getSessionWarningSMessage()
    {
        return $this->getConfig(self::SESSION_WARNING_SECONDARY_MESSAGE);
    }

    /**
     * Get session expired primary message
     *
     * @return mixed
     */
    public function getSessionExpiredPMessage()
    {
        return $this->getConfig(self::SESSION_EXPIRED_PRIMARY_MESSAGE);
    }

    /**
     * Get session expired secondary message
     *
     * @return mixed
     */
    public function getSessionExpiredSMessage()
    {
        return $this->getConfig(self::SESSION_EXPIRED_SECONDARY_MESSAGE);
    }

    /**
     * Get config value
     *
     * @param string $path
     * @return mixed
     */
    private function getConfig(string $path)
    {
        return $this->scopeConfig->getValue(
            'sso/session_general/'.$path,
            ScopeInterface::SCOPE_STORE
        );
    }
}
