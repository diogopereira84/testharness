<?php
/**
 * B-1058846 - Print Quote Receipt
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Block\Quote;


class View
{
    /**
     * @inheritDoc
     */
    public function __construct(
        protected \Fedex\Orderhistory\Helper\Data $helper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function beforeToHtml(\Magento\NegotiableQuote\Block\Quote\View $block)
    {
        if ($this->helper->isModuleEnabled()) {
            $block->setTemplate('Fedex_Orderhistory::quote/print.phtml');
        }
    }
}
