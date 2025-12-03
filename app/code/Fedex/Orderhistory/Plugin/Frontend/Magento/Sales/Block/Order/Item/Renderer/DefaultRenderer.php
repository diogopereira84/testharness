<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

/** B-1149275 - View Order Receipt - Delivery */

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Block\Order\Item\Renderer;

use Fedex\Cart\ViewModel\ProductInfoHandler;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;

class DefaultRenderer
{
    /**
     * @inheritDoc
     * @param OrderHistoryEnhacement $orderHistoryEnhacement
     */
    public function __construct(
        protected OrderHistoryEnhacement $orderHistoryEnhacement,
        private ProductInfoHandler     $productInfoHandler
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function beforeToHtml(\Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer $block)
    {
        if ($this->orderHistoryEnhacement->isModuleEnabled()
            && $this->orderHistoryEnhacement->isEnhancementEnabeled()) {
            $block->setTemplate('Fedex_Orderhistory::order/items/renderer/default.phtml')
                ->setData('order_enhancement_view_model', $this->orderHistoryEnhacement);
        }

        if ($this->orderHistoryEnhacement->isMarketplaceCommercialToggleEnabled()) {
            $block->setData('productinfo_handler_view_model', $this->productInfoHandler);
        }
    }
}
