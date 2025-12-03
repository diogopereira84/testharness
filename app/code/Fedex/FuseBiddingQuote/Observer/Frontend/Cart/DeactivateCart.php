<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FuseBiddingQuote\Observer\Frontend\Cart;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Request\Http;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

/**
 * Class DeactivateCart Observer
 */
class DeactivateCart implements ObserverInterface
{
    /**
     * DeactivateCart Constructor
     *
     * @param Http $request
     * @param FuseBidViewModel $fuseBidViewModel
     */
    public function __construct(
        protected Http $request,
        protected FuseBidViewModel $fuseBidViewModel
    )
    {
    }

    /**
     * Execute Method
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $moduleName = $this->request->getModuleName();
        $cotrollerName = $this->request->getControllerName();
        $isAjax = $this->request->isXmlHttpRequest();
        $arrModules = ['checkout', 'fxocm'];
        
        if (
            ($this->fuseBidViewModel->isFuseBidToggleEnabled() && !$isAjax) &&
            (($moduleName == 'checkout' && $cotrollerName == 'cart') || !in_array($moduleName, $arrModules))
        ) {
            $this->fuseBidViewModel->deactivateQuote();
        }
    }
}
