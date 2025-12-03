<?php
/**
 * B-1058846 - Print Quote Receipt
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Block\Quote;

/**
 * Block for preparing quote view data.
 *
 * @api
 * @since 100.0.0
 */
class View extends \Magento\NegotiableQuote\Block\Quote\View
{
    /**
     * Set page title.
     * @codeCoverageIgnore
     */
    protected function _prepareLayout()
    {
        $negotiableQuote = $this->getNegotiableQuote();
        if ($negotiableQuote && $negotiableQuote->getQuoteName()) {
            $quoteName = $negotiableQuote->getQuoteName();
        }
        $this->pageConfig->getTitle()->set(__('%1', $quoteName));
    }
}
