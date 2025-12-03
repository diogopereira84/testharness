<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Quote\Plugin\Rewrite\Quote\Model;

use Magento\Customer\Model\Session as CustomerSession;

/**
 * Plugin quote class for merging cart when customer login
 */
class Quote
{
    /**
     * Initializing Constructor
     *
     * @param QuoteModel $quoteModel
     */
    public function __construct(
        protected CustomerSession $customerSession
    )
    {
    }

    /**
     * Set session before cart item merge
     *
     * @param object $subject
     * @param object $result
     * @return void
     */
    public function beforeMerge($subject, $result)
    {
        $this->customerSession->setCompareItem(true);
    }

    /**
     * Unset session after cart item merged
     *
     * @param object $subject
     * @param object $result
     * @return object
     */
    public function afterMerge($subject, $result)
    {
        $this->customerSession->unsCompareItem();

        return $result;
    }
}
