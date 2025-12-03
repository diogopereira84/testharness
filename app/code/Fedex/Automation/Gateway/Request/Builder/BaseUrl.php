<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway\Request\Builder;

use Fedex\OktaMFTF\Model\Config\Credentials;

class BaseUrl
{
    /**
     * @var Credentials
     */
    private Credentials $credentials;

    /**
     * @param Credentials $credentials
     */
    public function __construct(
        Credentials $credentials
    ) {
        $this->credentials = $credentials;
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
