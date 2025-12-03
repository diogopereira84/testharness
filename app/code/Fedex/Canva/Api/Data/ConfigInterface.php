<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Api\Data;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Return the Canva Design base url.
     *
     * @return ?string
     **/
    public function getBaseUrl(): ?string;

    /**
     * Return the Canva Design path.
     *
     * @return ?string
     **/
    public function getPath(): ?string;

    /**
     * Returns the Canva logo image path.
     *
     * @return ?string
     **/
    public function getCanvaLogoPath(): ?string;

    /**
     * Returns the Canva partner id.
     *
     * @return ?string
     **/
    public function getPartnerId(): ?string;

    /**
     * Returns the Canva partnership sdk url.
     *
     * @return ?string
     **/
    public function getPartnershipSdkUrl(): ?string;

    /**
     * Returns the Canva user token api url.
     *
     * @return ?string
     **/
    public function getUserTokenApiUrl(): ?string;
}
