<?php
/**
 * @category Fedex
 * @package  Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Api;

use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;

/**
 * Interface ConfigInterface
 */
interface SalesForceInterface
{
    /**
     * Call SalesForce API for setting up Marketing OptIn
     *
     * @param SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriber
     * @return SalesForceResponseInterface
     */
    public function subscribe(SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriber): SalesForceResponseInterface;
}
