<?php
/**
 * @category  Fedex
 * @package   Fedex_Login
 * @author    Austin King <austin.king@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Login\Model;

enum LoginType: string
{
    case FCL = 'fcl';
    case SSO = 'sso';
    case SSO_FCL = 'sso_fcl';
}
