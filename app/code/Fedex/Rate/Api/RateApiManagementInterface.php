<?php

declare(strict_types=1);

namespace Fedex\Rate\Api;

interface RateApiManagementInterface
{
    /**
     * @api
     * @return string
     */
    public function rateProduct();
}
