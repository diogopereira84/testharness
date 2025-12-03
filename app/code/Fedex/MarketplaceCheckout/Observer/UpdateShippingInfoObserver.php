<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Observer;

use Magento\Framework\Event\Observer;
use Mirakl\FrontendDemo\Observer\AbstractObserver;

class UpdateShippingInfoObserver extends AbstractObserver
{
    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        if ($this->apiConfig->isEnabled()) {
            /** @var \Magento\Framework\App\Request\Http $request */
            $request = $observer->getEvent()->getData('request');
            if ($request && $request->isGet()) {
                $this->quoteUpdater->synchronize($this->quoteHelper->getQuote());
            }
        }
    }
}

