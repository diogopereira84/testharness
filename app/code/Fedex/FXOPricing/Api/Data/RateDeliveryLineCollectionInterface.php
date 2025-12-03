<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

interface RateDeliveryLineCollectionInterface
{
    /**
     * Get item by type
     *
     * @param string $type
     *
     * @return RateDeliveryLineInterface
     */
    public function getItemByType(string $type): RateDeliveryLineInterface;

    /**
     * Get shipping delivery line
     *
     * @return RateDeliveryLineInterface
     */
    public function getShippingDeliveryLine(): RateDeliveryLineInterface;

    /**
     * Get packing and handling delivery line
     *
     * @return RateDeliveryLineInterface
     */
    public function getPackingAndHandlingDeliveryLine(): RateDeliveryLineInterface;

    /**
     * Check for shipping delivery line discount
     *
     * @return bool
     */
    public function hasShippingDeliveryLineDiscounts(): bool;
}
