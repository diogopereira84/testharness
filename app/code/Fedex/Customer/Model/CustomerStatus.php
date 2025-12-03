<?php
/**
 * @category  Fedex
 * @package   Fedex_Customer
 * @author    Austin King <austin.king@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Customer\Model;

enum CustomerStatus: string
{
    case INACTIVE = 'Inactive';
    case ACTIVE = 'Active';
    case PENDING_FOR_APPROVAL = 'Pending For Approval';
    case EMAIL_VERIFICATION_PENDING = 'Email Verification Pending';
}
