<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * B-1250149 : Magento Admin UI changes to group all the Customer account details
 */
class PaymentOptions implements ArrayInterface
{
    /**
     * FedEx account number payment method identifier
     */
    const FEDEX_ACCOUNT_NUMBER = 'fedexaccountnumber';

    /**
     * Credit card payment method identifier
     */
    const CREDIT_CARD = 'creditcard';

    /**
     * List of available payment options
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::FEDEX_ACCOUNT_NUMBER, 'label' => __('Fedex Account Number')],
            ['value' => self::CREDIT_CARD, 'label' => __('Credit Card')],
        ];
    }
}
