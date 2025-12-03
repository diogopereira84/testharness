<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api;

use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;

interface RateAlertBuilderInterface
{
    /**
     * Build the rate alert from array
     *
     * @param array $alerts
     * @return AlertCollectionInterface
     */
    public function build(array $alerts): AlertCollectionInterface;
}
