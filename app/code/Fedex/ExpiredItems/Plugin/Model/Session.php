<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\ExpiredItems\Plugin\Model;

use Fedex\ExpiredItems\Helper\ExpiredItem;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

/**
 * Call Rate API to get expired item ids
 */
class Session
{

    /**
     * Initiliazing constructor
     *
     * @param ExpiredItem $expiredItemHelper
     */
    public function __construct(
        private ExpiredItem $expiredItemHelper,
        private CartDataHelper $cartDataHelper,
        protected FuseBidViewModel $fuseBidViewModel
    )
    {
    }

    /**
     * Call rate API to get expired product ids
     *
     * @param object $subject
     * @param object $result
     * @return array
     */
    public function afterLoadCustomerQuote(
        \Magento\Checkout\Model\Session $subject,
        $result
    ) {
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $this->fuseBidViewModel->deactivateQuote();
        }
        $quote =  $result->getQuote();
        $this->cartDataHelper->applyFedxExAccountInCheckout($quote);
        $itemCount =  count($quote->getAllItems());
        if ($itemCount) {
            $this->expiredItemHelper->callRateApiGetExpiredInstanceIds($quote);
        }
        $this->expiredItemHelper->setExpiredItemMessageCustomerSession();
       
        return $result;
    }
}
