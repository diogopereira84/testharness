<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\ExpiredItems\Plugin\Controller\Quote;

use Fedex\Delivery\Controller\Quote\Create;
use Magento\Framework\App\Http\Context;
use Fedex\ExpiredItems\Helper\ExpiredItem;

/**
 * Plugin Class QuoteCreateAfter
 */
class QuoteCreateAfter
{

    /**
     * Initiliazing constructor
     *
     * @param ExpiredItem $expiredItemHelper
     */
    public function __construct(
        private ExpiredItem $expiredItemHelper
    )
    {
    }

    /**
     * To clear the cookie after submitting quote
     *
     * @param  Create $subject
     * @param  bool $result
     * @return bool
     */
    public function afterCheckoutSaveAddressAndClearSession(Create $subject, $result)
    {
        $this->expiredItemHelper->clearExpiredModalCookie();
        
        return $result;
    }
}
