<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Constants;

final class RequestConstants
{
    public const RATE_SORT_ORDER = 'SERVICENAMETRADITIONAL';
    public const PICK_UP_TYPE = 'DROPOFF_AT_FEDEX_LOCATION';
    public const ACCOUNT = 'ACCOUNT';
    public const LIST = 'LIST';
    public const POST_REQUEST = 'POST';
    public const SUCCESS_RESPONSE_CODE = 200;
}