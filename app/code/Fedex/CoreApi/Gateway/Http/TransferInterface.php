<?php
/**
 * @category    Fedex
 * @package     Fedex_CoreApi
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
namespace Fedex\CoreApi\Gateway\Http;

interface TransferInterface
{
    const PARAMS = 'params';
    const METHOD = 'method';
    const HEADERS = 'headers';
    const BODY = 'body';
    const URI = 'uri';

    /**
     * Returns gateway client configuration
     *
     * @return array
     */
    public function getParams(): array;

    /**
     * Returns method used to place request
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Returns headers
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Returns request body
     *
     * @return array
     */
    public function getBody(): array;

    /**
     * Returns URI
     *
     * @return string
     */
    public function getUri(): string;
}
