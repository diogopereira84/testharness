<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth;

interface OktaUserInfoInterface
{
    /**
     * @param string $accessToken
     * @return mixed
     */
    public function getUserInfo(string $accessToken);

    /**
     * @param array $response
     * @return bool
     */
    public function validate(array $response): bool;
}
