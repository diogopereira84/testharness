<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AccountTypes implements OptionSourceInterface
{
    /**
     * Get list of Account Types values
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Please select account type')],
            ['value' => '0', 'label' => __('Discount Account')],
            ['value' => '1', 'label' => __('Invoice Account')],
            ['value' => '2', 'label' => __('Both')]
        ];
    }
}
