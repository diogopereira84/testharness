<?php
/**
 * @category  Fedex
 * @package   Fedex_Customer
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Customer\Api\Data;

interface DataSourceInterface
{
    /**
     * Returns the data to be used in the data layer
     *
     * @param SalesForceResponseInterface $salesForceResponseInterface
     * @param array $subscriberData
     *
     * @return void
     */
    public function map(
        SalesForceResponseInterface $salesForceResponseInterface,
        array $subscriberData = []
    ): void;
}
