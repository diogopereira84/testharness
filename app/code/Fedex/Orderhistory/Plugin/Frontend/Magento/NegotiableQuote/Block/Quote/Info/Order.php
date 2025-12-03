<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Block\Quote\Info;

use Fedex\Orderhistory\Helper\Data;

class Order
{

    /**
     * @inheritDoc
     */
    public function __construct(
        protected Data $helper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function beforeToHtml(\Magento\NegotiableQuote\Block\Quote\Info\Order $block)
    {
        if ($this->helper->isModuleEnabled()) {
            $block->setTemplate('Fedex_Orderhistory::negotiableQuote/quote/info/order.phtml');
        }
    }
}
