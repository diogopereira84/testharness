<?php
/**
 * @category Fedex
 * @package Fedex_Rate
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Rate\Api\Data;

interface ConfigInterface
{
    /**
     * @api
     * @return string
     **/
    public function getRateApiUrl(): string;
}
