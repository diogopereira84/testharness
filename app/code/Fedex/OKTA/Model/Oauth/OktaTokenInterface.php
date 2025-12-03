<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model\Oauth;

interface OktaTokenInterface
{
    /**
     * @param string $code
     * @return mixed
     */
    public function getToken(string $code);

    /**
     * @param array $response
     * @return bool
     */
    public function validate(array $response): bool;
}
