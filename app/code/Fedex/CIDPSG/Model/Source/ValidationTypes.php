<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ValidationTypes implements OptionSourceInterface
{
    /**
     * Get list of Validation Types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'text', 'label' => __('Text')],
            ['value' => 'email', 'label' => __('Email')],
            ['value' => 'telephone', 'label' => __('Telephone')],
            ['value' => 'fax', 'label' => __('Fax')],
            ['value' => 'fedex_account', 'label' => __('FedEx Account')],
            ['value' => 'zipcode', 'label' => __('Zipcode')],
            ['value' => 'fedex_shipping_account', 'label' => __('FedEx Shipping Account')]
        ];
    }
}
