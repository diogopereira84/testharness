<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Punchout\Api;

interface CustomerInterface
{
    /**
     * get customer by unique id
     *
     * @api
     * @param $request
     * @return boolean|array
     */
    function getCustomer();

    /**
     * Initiate Punchout request and Order request
     *
     * @api
     * @param $request
     * @return boolean|array
     */
    function doPunchOut();

}
