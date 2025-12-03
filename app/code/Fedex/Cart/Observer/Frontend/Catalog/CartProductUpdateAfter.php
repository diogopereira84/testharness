<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Cart\Observer\Frontend\Catalog;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;

class CartProductUpdateAfter implements ObserverInterface
{
    /**
     * Execute observer construct.
     *
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param ToggleConfig $toggleConfig
     * @param InstoreConfig $instoreConfig
     */
    public function __construct(
        protected FXORate $fxoRateHelper,
        protected FXORateQuote $fxoRateQuote,
        protected ToggleConfig $toggleConfig,
        private InstoreConfig $instoreConfig
    )
    {
    }

    /**
     * Execute Observer method
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $cart = $observer->getData('cart');
        $quote = $cart->getData('quote');
        
        if (!$this->fxoRateHelper->isEproCustomer()) {
            try {
                $this->fxoRateQuote->getFXORateQuote($quote);
            } catch (GraphQlFujitsuResponseException $e) {
                if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                    throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                }
            }
        } else {
            $this->fxoRateHelper->getFXORate($quote);
        }
    }
}
