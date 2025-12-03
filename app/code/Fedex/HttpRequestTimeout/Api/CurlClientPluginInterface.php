<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Api;

use Magento\Framework\HTTP\ClientInterface;

interface CurlClientPluginInterface
{
    /**
     * @param $subject
     * @param $uri
     * @param $params
     * @return array
     */
    public function beforePost($subject, $uri, $params): array;

    /**
     * @param $subject
     * @param $uri
     * @return array
     */
    public function beforeGet($subject, $uri): array;
}



