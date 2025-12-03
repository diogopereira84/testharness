<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\Customer;

enum CustomerType: string
{
    case GUEST = 'GUEST';
    case LOGGED_IN = 'LOGGED_IN';
}
