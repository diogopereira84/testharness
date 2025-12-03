<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api;

use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountInterface;

interface RateDeliveryLineDiscountBuilderInterface
{
    /**
     * Build the rate from array
     *
     * @return RateDeliveryLineDiscountInterface
     */
    public function build(): RateDeliveryLineDiscountInterface;
}
