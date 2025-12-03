<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

interface RateQuoteDeliveryLineCollectionInterface
{
    /**
     * Get item by type
     *
     * @param string $type
     *
     * @return RateQuoteDeliveryLineInterface
     */
    public function getItemByType(string $type): RateQuoteDeliveryLineInterface;

    /**
     * Get shipping delivery line
     *
     * @return RateQuoteDeliveryLineInterface
     */
    public function getShippingDeliveryLine(): RateQuoteDeliveryLineInterface;

    /**
     * Get packing and handling delivery line
     *
     * @return RateQuoteDeliveryLineInterface
     */
    public function getPackingAndHandlingDeliveryLine(): RateQuoteDeliveryLineInterface;

    /**
     * Check for shipping delivery line discount
     *
     * @return bool
     */
    public function hasShippingDeliveryLineDiscounts(): bool;
}
