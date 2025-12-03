<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ComputerRental\Api;

interface CRDataInterface
{
    /**
     * Check if isReatil Customer
     * @return bool
     */
    public function isRetailCustomer();

    /**
     * get store code from session.
     *
     * @return string
     */
    public function getStoreCodeFromSession();

    /**
     * Save store code in session.
     *
     * @param string $storeCode
     * @return void
     */
    public function saveStoreCodeInSession($storeCode);
    /**
     * Save Location code in session.
     *
     * @param string $locationCode
     * @return void
     */
    public function saveLocationCode($locationCode);

    /**
     * get Location code from session.
     *
     * @return string
     */
    public function getLocationCode();
}
