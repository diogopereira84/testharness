<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
namespace Fedex\HttpRequestTimeout\Model;

use Fedex\HttpRequestTimeout\Api\ConfigManagementInterface;

class TimeoutValidator
{
    /**
     * @param array $urlsWithTimeout
     * @param string $uri
     * @return bool
     */
    public function isSuitableForDefinedTimeout(array $urlsWithTimeout, string $uri): bool
    {
        return isset($urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER])
            && is_numeric($urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER])
            && $urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER] > 0;
    }

    /**
     * @param array $urlsWithTimeout
     * @param string $uri
     * @param bool $defaultTimeoutEnabled
     * @return bool
     */
    public function isSuitableForDefaultTimeout(array $urlsWithTimeout, string $uri, bool $defaultTimeoutEnabled): bool
    {
        return !isset($urlsWithTimeout[$uri]) && $defaultTimeoutEnabled;
    }
}
