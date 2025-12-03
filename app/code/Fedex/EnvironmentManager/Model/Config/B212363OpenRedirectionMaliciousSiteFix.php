<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Sawai Singh Rajpurohit <sawai.rajpurohit.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

class B212363OpenRedirectionMaliciousSiteFix extends ToggleBase implements ToggleInterface
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'b_2123653_oauth_fix_redirection_to_malicious_site';

    /**
     * @inheritDoc
     */
    protected function getPath(): string
    {
        return self::PATH;
    }
}
