<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Api;

/**
 * Interface for managing customers profile.
 * @api
 * @since 100.0.2
 */
interface ProfileManagementInterface
{

    /**
     * Check if the customer is logged-in and has a profile.
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool;
}
