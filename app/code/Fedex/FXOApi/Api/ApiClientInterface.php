<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOApi
 * @copyright   Copyright (c) 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\FXOApi\Api;

interface ApiClientInterface
{
    /**
     * Handle FXO Api Calls
     *
     * @param string $type
     * @param string $uri
     * @param string $gateway
     * @return mixed
     */
    public function fxoApiCall(string $type, string $uri, string $gateway): mixed;
}
