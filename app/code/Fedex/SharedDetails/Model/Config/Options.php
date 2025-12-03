<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedDetails\Model\Config;
 
use Magento\Framework\Option\ArrayInterface;
 
class Options implements ArrayInterface
{
    /**
     * Get month options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        for ($x = 1; $x <= 12; $x++) {
            if ($x == 1) {
                $options[] = [
                    'value' => $x,
                    'label' => $x.' Month'
                ];
            } else {
                $options[] = [
                    'value' => $x,
                    'label' => $x.' Months'
                ];
            }
        }

        return $options;
    }
}
