<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * B-1250149 : Magento Admin UI changes to group all the Customer account details
 */
class FedExAccountOptions implements OptionSourceInterface
{
    /**
     * Legacy site provided FedEx account identifier
     */
    const LEGACY_FEDEX_ACCOUNT = 'legacyaccountnumber';

    /**
     * Custom FedEx account identifier
     */
    const CUSTOM_FEDEX_ACCOUNT = 'custom_fedex_account';

    /**
     * Get FedEx Account options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => 'Legacy Site Provided Account',
                'value' => self::LEGACY_FEDEX_ACCOUNT,
            ],
            [
                'label' => 'Custom Account',
                'value' => self::CUSTOM_FEDEX_ACCOUNT,
            ],
        ];
    }
}
