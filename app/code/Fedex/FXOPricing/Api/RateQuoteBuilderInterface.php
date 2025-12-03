<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api;

use Fedex\FXOPricing\Api\Data\RateQuoteInterface;

interface RateQuoteBuilderInterface
{
    /**
     * Build the rate from array
     *
     * @param array $data
     * @return RateQuoteInterface
     */
    public function build(array $data = []): RateQuoteInterface;
}
