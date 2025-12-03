<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api;

interface DataSourceCompositeInterface
{
    /**
     * Compose the data to be used in the data layer from multiple data sources
     *
     * @param array $checkoutData
     *
     * @return array
     */
    public function compose(array $checkoutData = []): array;
}
