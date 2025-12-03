<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api;

use Fedex\FXOPricing\Api\Data\RateDetailInterface;

interface RateDetailBuilderInterface
{
    /**
     * Build the rate detail from array
     *
     * @return RateDetailInterface
     */
    public function build(): RateDetailInterface;
}
