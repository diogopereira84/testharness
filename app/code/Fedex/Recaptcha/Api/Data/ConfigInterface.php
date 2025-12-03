<?php
/**
 * @category Fedex
 * @package  Fedex_Recaptcha
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Recaptcha\Api\Data;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Return Configured Site Key
     *
     * @return string
     */
    public function getPublicKey(): string;
}
