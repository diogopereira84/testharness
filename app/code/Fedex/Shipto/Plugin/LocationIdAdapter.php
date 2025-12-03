<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Shipto\Plugin;

use Fedex\Shipto\Helper\Data;

class LocationIdAdapter
{
    /** @var string[]  */
    const PARAMETERS_TO_ADAPT = [
        "NULL",
        "null"
    ];

    /** @var null  */
    const ADAPTED_PARAMETER = null;

    /**
     * @param Data $subject
     * @param mixed $locationId
     * @param mixed $hoursOfOperation
     * @return array
     */
    public function beforeGetAddressByLocationId(
        Data $subject,
        mixed $locationId,
        mixed $hoursOfOperation = false
    ): array
    {
        if (in_array($locationId, self::PARAMETERS_TO_ADAPT)) {
            $locationId = self::ADAPTED_PARAMETER;
        }
        return [$locationId, $hoursOfOperation];
    }
}
