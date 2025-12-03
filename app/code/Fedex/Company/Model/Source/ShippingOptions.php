<?php

namespace Fedex\Company\Model\Source;

class ShippingOptions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get delivery options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
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
