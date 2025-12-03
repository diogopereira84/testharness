<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
namespace Fedex\MarketplacePunchout\ViewModel;

use Fedex\MarketplacePunchout\Model\Config\Marketplace;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Notification implements ArgumentInterface
{

    /**
     * Construct
     *
     * @param Marketplace $marketplaceConfig
     */
    public function __construct(
        private Marketplace $marketplaceConfig
    )
    {
    }

    /**
     *
     * @return string
     */
    public function getMarketplaceNotificationTitle()
    {
        return $this->marketplaceConfig->getMarketplaceDowntimeTitle();
    }

    /**
     *
     * @return string
     */
    public function getMarketplaceNotificationMsg()
    {
        return $this->marketplaceConfig->getMarketplaceDowntimeMsg();
    }

    public function getMarketplaceMessage() {
        $message = [
            'category' => 'marketplace_pdp',
            'type'     => 'warning',
            'title'    => $this->getMarketplaceNotificationTitle(),
            'text'     => $this->getMarketplaceNotificationMsg()
        ];

        return htmlspecialchars(json_encode($message));
    }
}
