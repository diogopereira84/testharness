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

class RemoveQuoteItem implements ObserverInterface
{
    const MAZEGEEKS_D238399 = "mazegeeks_D238399";
    /**
     * Execute observer construct.
     *
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected FXORate $fxoRateHelper,
        protected FXORateQuote $fxoRateQuote,
        protected ToggleConfig $toggleConfig
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
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $quote = $quoteItem->getQuote();
        if (!$this->fxoRateHelper->isEproCustomer()) {
            if ($this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_D238399)) {
                $uploadToQuoteRequest = [];
                $discountIntent= $this->getDiscountIntentForQuote($quote);
                $this->fxoRateQuote->getFXORateQuote(
                    $quote,
                    null,
                    false,
                    $uploadToQuoteRequest,
                    $quoteItem->getData(),
                    $discountIntent
                );                              
            } else {
                $this->fxoRateQuote->getFXORateQuote($quote);
            }
        } else {
            $this->fxoRateHelper->getFXORate($quote);
        }
    }

    /**
     * Get discount Intent for quote
     *
     * @param obj $quote
     */
    public function getDiscountIntentForQuote($quote)
    {
        $discountIntent = null;
        $quoteItem = $quote->getAllVisibleItems();
        foreach ($quoteItem as $item) {
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
            if ($infoBuyRequest->getValue()) {
                $decodedProductData = json_decode($infoBuyRequest->getValue(), true);
                if (isset($decodedProductData['discountIntent'])) {
                    $discountIntent = $decodedProductData['discountIntent'];
                }
            }
        }
        return $discountIntent;
    }
}
