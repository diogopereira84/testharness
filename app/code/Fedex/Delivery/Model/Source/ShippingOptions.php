<?php
/**
 * Copyright © Fedex Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class ShippingOptions implements ArrayInterface
{
    /**
     * Get delivery options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'FEDEX_HOME_DELIVERY', 'label' => __('FedEx Home Delivery®')],
            ['value' => 'GROUND_US', 'label' => __('Ground US')],
            ['value' => 'LOCAL_DELIVERY_AM', 'label' => __('FedEx Local Delivery AM')],
            ['value' => 'LOCAL_DELIVERY_PM', 'label' => __('FedEx Local Delivery PM')],
            ['value' => 'EXPRESS_SAVER', 'label' => __('Express Saver')],
            ['value' => 'TWO_DAY', 'label' => __('2 Day')],
            ['value' => 'STANDARD_OVERNIGHT', 'label' => __('Standard Overnight')],
            ['value' => 'PRIORITY_OVERNIGHT', 'label' => __('Priority Overnight')],
            ['value' => 'FIRST_OVERNIGHT', 'label' => __('First Overnight')],
        ];
    }
}
