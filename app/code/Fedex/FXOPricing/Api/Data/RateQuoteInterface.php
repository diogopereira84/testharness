<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

use Fedex\Base\Api\Data\DataObjectInterface;

interface RateQuoteInterface extends DataObjectInterface
{
    /**
     * @return string
     */
    public function getCurrency(): string;

    /**
     * @param string $currency
     * @return RateQuoteInterface
     */
    public function setCurrency(string $currency): RateQuoteInterface;

    /**
     * @return RateQuoteDetailCollectionInterface
     */
    public function getDetails(): RateQuoteDetailCollectionInterface;

    /**
     * @param RateQuoteDetailCollectionInterface $details
     * @return RateQuoteInterface
     */
    public function setDetails(RateQuoteDetailCollectionInterface $details): RateQuoteInterface;

    /**
     * Check if it has a shipping delivery lines
     *
     * @return bool
     */
    public function hasDetailShippingDeliveryLine(): bool;

    /**
     * Check if the has a shipping delivery line with discounts
     *
     * @return bool
     */
    public function hasDetailShippingDeliveryDiscount(): bool;

    /**
     * Check if details has discounts
     *
     * @return bool
     */
    public function hasDetailCouponDiscounts(): bool;

    /**
     * Check if details has multiple promotions
     *
     * @return bool
     */
    public function hasMultiplePromotion(): bool;

    /**
     * Check if details has single promotion
     *
     * @return bool
     */
    public function hasSinglePromotion(): bool;
}
