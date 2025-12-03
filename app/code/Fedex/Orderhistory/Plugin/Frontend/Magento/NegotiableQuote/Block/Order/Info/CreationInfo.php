<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

 /* B-1060632 - RT-ECVS- View Order Details
Remove Reorder Button  */
namespace Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Block\Order\Info;

use Fedex\Orderhistory\Helper\Data;

class CreationInfo
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeToHtml(\Magento\NegotiableQuote\Block\Order\Info\CreationInfo $block)
    {
        if ($this->helper->isPrintReceiptRetail()) {
            $block->setTemplate('');
        }
    }
}
