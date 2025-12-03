<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

interface AlertCollectionInterface
{
    /**
     * Checks if the collection has alerts
     *
     * @return bool
     */
    public function hasAlerts(): bool;

    /**
     * Get the alert by code
     *
     * @param string $type
     * @return AlertInterface
     */
    public function getItemByCode(string $type): AlertInterface;

    /**
     * Return the alert for invalid coupon code
     *
     * @return AlertInterface
     */
    public function getCouponCodeInvalid(): AlertInterface;

    /**
     * Checks if the collection has invalid coupon code alert
     *
     * @return bool
     */
    public function hasInvalidCouponCode(): bool;
}
