<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api\Data;

interface DataSourceInterface
{
    /**
     * Returns the data to be used in the data layer
     *
     * @param UnifiedDataLayerInterface $unifiedDataLayer
     * @param array $checkoutData
     *
     * @return void
     */
    public function map(
        UnifiedDataLayerInterface $unifiedDataLayer,
        array $checkoutData = []
    ): void;
}
