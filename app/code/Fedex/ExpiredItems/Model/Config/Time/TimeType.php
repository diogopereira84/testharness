<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Model\Config\Time;

/**
 * Use to add system configuration Time Type class
 */
class TimeType
{
    /**
     * Return option value
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
          ['value' => 'days', 'label' => __('Days')],
          ['value' => 'hours', 'label' => __('Hours')],
          ['value' => 'minutes', 'label' => __('Minutes')]
        ];
    }
}
