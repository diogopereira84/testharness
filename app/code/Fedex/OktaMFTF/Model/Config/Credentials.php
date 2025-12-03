<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Model\Config;

class Credentials extends Base
{
    private const XPATH_DOMAIN = 'domain';
    private const XPATH_AUTHORIZATION_SERVER_ID = 'authorization_server_id';
    private const XPATH_GRANT_TYPE = 'grant_type';
    private const XPATH_SCOPE = 'scope';
    private const PREFIX_KEY = 'okta_mftf/credentials';

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return (string) $this->getScopeValue(self::XPATH_DOMAIN);
    }

    /**
     * @return string
     */
    public function getAuthorizationServerId(): string
    {
        return (string) $this->getScopeValue(self::XPATH_AUTHORIZATION_SERVER_ID);
    }

    /**
     * @return string
     */
    public function getGrantType(): string
    {
        return (string) $this->getScopeValue(self::XPATH_GRANT_TYPE);
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return (string) $this->getScopeValue(self::XPATH_SCOPE);
    }

    /**
     * @inheritDoc
     */
    protected function getConfigPrefix(): string
    {
        return self::PREFIX_KEY;
    }
}
