<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceAdmin
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare (strict_types=1);

namespace Fedex\MarketplaceAdmin\ViewModel;
use Magento\Framework\View\Element\Block\ArgumentInterface;

use Fedex\MarketplaceCheckout\Helper\Data as Helper;

class Data implements ArgumentInterface
{
    public function __construct(
        private Helper $helper
    ) {
    }

    /**
     * Gets status of enable essendant toggle.
     * @return bool
     */
    public function isEssendantToggleEnabled(): bool
    {
        return $this->helper->isEssendantToggleEnabled();
    }
}
