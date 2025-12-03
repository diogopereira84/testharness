<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

 /* B-1060632 - RT-ECVS- View Order Details
Remove Reorder Button  */
namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Block\Order\Info;

use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;

class Buttons
{
    /**
     * @inheritDoc
     * @param OrderHistoryEnhacement $orderHistoryEnhacement
     */
    public function __construct(
        protected OrderHistoryEnhacement $orderHistoryEnhacement
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function beforeToHtml(\Magento\Sales\Block\Order\Info\Buttons $block)
    {
        if ($this->orderHistoryEnhacement->isModuleEnabled()) {
            $block->setTemplate('Fedex_Orderhistory::order/info/buttons.phtml')
            ->setData('order_enhancement_view_model', $this->orderHistoryEnhacement);
        }
        if ($this->orderHistoryEnhacement->isPrintReceiptRetail()) {
            $block->setTemplate('Fedex_Orderhistory::order/info/retail-buttons.phtml')
            ->setData('order_enhancement_view_model', $this->orderHistoryEnhacement);
        }
    }
}
