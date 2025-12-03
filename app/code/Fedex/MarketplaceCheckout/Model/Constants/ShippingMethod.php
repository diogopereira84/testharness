<?php
/**
 * @category  Fedex
 * @package   Fedex_Customer
 * @author    Austin King <austin.king@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Constants;

enum ShippingMethod: string
{
    case TWO_DAY = 'FEDEX_2_DAY';
    case EXPRESS_SAVER = 'FEDEX_EXPRESS_SAVER';
    case GROUND_US = 'FEDEX_GROUND';
    case LOCAL_DELIVERY_AM = 'FEDEX_2_DAY_AM';
    case LOCAL_DELIVERY_PM = 'FEDEX_2_DAY_PM';

    /**
     * Accepts either the enum case name (e.g., "TWO_DAY") or the enum value
     * (e.g., "FEDEX_2_DAY") and returns the corresponding case.
     * Matching is case-insensitive and trims surrounding whitespace.
     * Returns null if the input does not map to a known method.
     */
    public static function fromString(string $method): ?self
    {
        $string = strtoupper(trim($method));
        foreach (self::cases() as $case) {
            if ($case->name === $string || $case->value === $string) {
                return $case;
            }
        }
        return null;
    }
}
