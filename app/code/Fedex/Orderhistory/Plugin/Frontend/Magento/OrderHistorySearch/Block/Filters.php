<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\OrderHistorySearch\Block;

use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;

class Filters
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
    public function beforeToHtml(\Magento\OrderHistorySearch\Block\Filters $block)
    {
        //D-97122 Show epro filters for sde
        if ($this->orderHistoryEnhacement->isModuleEnabled() || $this->orderHistoryEnhacement->isSdeStore()) {
            $block->setTemplate('Fedex_Orderhistory::order/filters.phtml')
            ->setData('order_enhancement_view_model', $this->orderHistoryEnhacement);
        }
        if ($this->orderHistoryEnhacement->isRetailOrderHistoryEnabled()) {
            $block->setTemplate('Fedex_Orderhistory::order/retail-filters.phtml')
            ->setData('order_enhancement_view_model', $this->orderHistoryEnhacement);
        }
    }
}
