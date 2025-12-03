<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Api\Data;

/**
 * Interface LoginConfigInterface
 */
interface LoginConfigInterface
{
    /**
     * Return the Canva login title.
     *
     * @return ?string
     **/
    public function getTitle(): ?string;

    /**
     * Return the Canva login description.
     *
     * @return ?string
     **/
    public function getDescription(): ?string;

    /**
     * Return the Canva login register button label.
     *
     * @return ?string
     **/
    public function getRegisterButtonLabel(): ?string;

    /**
     * Return the Canva login login button label.
     *
     * @return ?string
     **/
    public function getLoginButtonLabel(): ?string;

    /**
     * Return the Canva login continue button label.
     *
     * @return ?string
     **/
    public function getContinueButtonLabel(): ?string;
}
