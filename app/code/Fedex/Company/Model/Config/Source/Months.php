<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Months implements OptionSourceInterface
{
    /**
     * Get list of months
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('0 - Month')],
            ['value' => '01', 'label' => __('1 - January')],
            ['value' => '02', 'label' => __('2 - February')],
            ['value' => '03', 'label' => __('3 - March')],
            ['value' => '04', 'label' => __('4 - April')],
            ['value' => '05', 'label' => __('5 - May')],
            ['value' => '06', 'label' => __('6 - June')],
            ['value' => '07', 'label' => __('7 - July')],
            ['value' => '08', 'label' => __('8 - August')],
            ['value' => '09', 'label' => __('9 - September')],
            ['value' => '10', 'label' => __('10 - October')],
            ['value' => '11', 'label' => __('11 - November')],
            ['value' => '12', 'label' => __('12 - December')],
        ];
    }
}
