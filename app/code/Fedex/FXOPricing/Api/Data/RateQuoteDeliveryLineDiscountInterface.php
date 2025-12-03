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

interface RateQuoteDeliveryLineDiscountInterface extends DataObjectInterface
{
    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     * @return RateQuoteDeliveryLineDiscountInterface
     */
    public function setType(string $type): RateQuoteDeliveryLineDiscountInterface;

    /**
     * @return float
     */
    public function getAmount(): float;

    /**
     * @param float $amount
     * @return RateQuoteDeliveryLineDiscountInterface
     */
    public function setAmount(float $amount): RateQuoteDeliveryLineDiscountInterface;
}
