<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Checkout\Block\Cart\Item;

use Fedex\Orderhistory\Helper\Data;

class Renderer
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
    public function afterHasProductUrl(\Magento\Checkout\Block\Cart\Item\Renderer $block, $result)
    {
        if ($this->helper->isModuleEnabled()) {
            return false;
        }
        return $result;
    }
}
