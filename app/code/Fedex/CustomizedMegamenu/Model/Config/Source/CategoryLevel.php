<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomizedMegamenu\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CategoryLevel implements OptionSourceInterface
{
    /**
     * Get list of category level values
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '2', 'label' => __('Second Level')],
            ['value' => '3', 'label' => __('Third Level')],
        ];
    }
}
