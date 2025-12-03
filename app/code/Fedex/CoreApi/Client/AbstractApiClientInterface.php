<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CoreApi\Client;

interface AbstractApiClientInterface
{
    /**
     * Execute request
     *
     * @param string $urlKey
     * @param string $method
     * @param $requestBody
     * @param array $params
     * @return mixed
     */
    public function execute(string $urlKey, string $method, $requestBody, array $params);
}
