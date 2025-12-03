<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Model\Config;

class General extends Base
{
    private const XPATH_ENABLED = 'enabled';
    private const XPATH_LOG_ENABLED = 'log_enabled';
    private const XPATH_ADMIN_USER = 'admin_user';
    private const PREFIX_KEY = 'okta_mftf/general';

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->getScopeValue(self::XPATH_ENABLED);
    }

    /**
     * @return bool
     */
    public function isLogEnabled(): bool
    {
        return (bool) $this->getScopeValue(self::XPATH_LOG_ENABLED);
    }

    /**
     * @return string
     */
    public function getAdminUser(): string
    {
        return (string) $this->getScopeValue(self::XPATH_ADMIN_USER);
    }

    /**
     * @inheritDoc
     */
    protected function getConfigPrefix(): string
    {
        return self::PREFIX_KEY;
    }
}
