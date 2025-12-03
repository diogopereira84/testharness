<?php
/**
 * Copyright © FedEx. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAcceptance implements ArrayInterface
{
    /**
     * FedEx account numbers payment option
     */
    const FEDEX_ACCOUNT_NUMBERS = 'accountnumbers';

    /**
     * Legecy FedEx account number payment option
     */
    const LEGECY_FEDEX_ACCOUNT_NUMBER = 'legacyaccountnumber';

    /**
     * Purchase order payment option
     */
    const PURCHASE_ORDER = 'purchaseorder';

    /**
     * Legacy site credit card payment option
     */
    const LEGECY_SITE_CREDIT_CARD = 'sitecreditcard';

    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Select payment Type')],
            ['value' => self::LEGECY_FEDEX_ACCOUNT_NUMBER, 'label' => __('Legacy Site Provided FedEx Account Number')],
            ['value' => self::LEGECY_SITE_CREDIT_CARD, 'label' => __('Legacy Site Provided Credit Card')],
            ['value' => self::PURCHASE_ORDER, 'label' => __('Purchase Order')],
            ['value' => self::FEDEX_ACCOUNT_NUMBERS, 'label' => __('Account Numbers')],
        ];
    }
}
