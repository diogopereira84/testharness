<?php
declare(strict_types=1);
namespace Fedex\AccountValidation\Api;

interface AccountValidationInterface
{
    /**
     * Check if the toggle for E-456656 is enabled.
     *
     * @return bool
     */
    public function isToggleE456656Enabled();

    /**
     * Get the URL for account validation.
     *
     * @return string
     */
    public function getAccountValidationUrl();
}
