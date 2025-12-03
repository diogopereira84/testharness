<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Request\Builder;

use Fedex\OktaMFTF\Model\Config\Credentials;

class BaseUrl
{
    /**
     * @param Credentials $credentials
     */
    public function __construct(
        private Credentials $credentials
    )
    {
    }

    /**
     * Build base url request
     *
     * @return string
     */
    public function build(): string
    {
        return "https://{$this->credentials->getDomain()}/oauth2/{$this->credentials->getAuthorizationServerId()}";
    }
}
