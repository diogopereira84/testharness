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
class CredtiCardOptions implements OptionSourceInterface
{
    /**
     * Legacy site provided credit card identifier
     */
    const LEGACY_SITE_CREDIT_CARD = 'sitecreditcard';

    /**
     * New credit card identifier
     */
    const NEW_CREDIT_CARD = 'new_credit_card';

    /**
     * Get creditcard options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => 'Legacy Site Provided Credit Card',
                'value' => self::LEGACY_SITE_CREDIT_CARD,
            ],
            [
                'label' => 'New Credit Card',
                'value' => self::NEW_CREDIT_CARD,
            ],
        ];
    }
}
