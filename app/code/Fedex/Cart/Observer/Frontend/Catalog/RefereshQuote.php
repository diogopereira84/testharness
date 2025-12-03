<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Cart\Observer\Frontend\Catalog;

use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\EnvironmentManager\Model\Config\RateQuoteOptimizationToggle;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\FXOPricing\Model\FXORateQuoteApi;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Service\BundlePriceCalculator;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RefereshQuote implements ObserverInterface
{
    /** @var string */
    public const XML_PATH_TIGER_D207139_TOGGLE = 'tiger_d207139';

    /**
     * Execute observer construct.
     *
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param CartFactory $cartFactory
     * @param Session $checkoutSession
     * @param CartDataHelper $cartDataHelper
     * @param ToggleConfig $toggleConfig
     * @param AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle
     * @param RateQuoteOptimizationToggle $rateQuoteOptimizationToggle
     * @param FXORateQuoteApi $fXORateQuoteApi
     * @param BundlePriceCalculator $bundlePriceCalculator
     */
    public function __construct(
        protected FXORate $fxoRateHelper,
        protected FXORateQuote $fxoRateQuote,
        protected CartFactory $cartFactory,
        protected Session $checkoutSession,
        protected CartDataHelper $cartDataHelper,
        protected ToggleConfig $toggleConfig,
        readonly AddToCartPerformanceOptimizationToggle $addToCartPerformanceOptimizationToggle,
        readonly RateQuoteOptimizationToggle $rateQuoteOptimizationToggle,
        private FXORateQuoteApi $fXORateQuoteApi,
        private BundlePriceCalculator $bundlePriceCalculator,
        private ConfigInterface $productBundleConfig
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

        if($this->addToCartPerformanceOptimizationToggle->isActive()) {
           if($this->checkoutSession->getQuote() ){
                $quote = $this->checkoutSession->getQuote();
            } else {
                $quote = $this->cartFactory->create()->getQuote();
            }

            if (!$quote) {
                return;
            }
            $method = $quote->getShippingAddress()->getShippingMethod();
        } else {
            $cart = $this->cartFactory->create();
            $quote =$cart->getQuote();
            $method = $quote->getShippingAddress()->getShippingMethod();
        }

        $customTaxAmount = $quote->getCustomTaxAmount();
        if ($customTaxAmount > 0) {
            $quote->setGrandTotal($quote->getGrandTotal() - $customTaxAmount);
            $quote->setBaseGrandTotal($quote->getGrandTotal() - $customTaxAmount);
            $quote->setCustomTaxAmount(0);
        }

        if (!empty($method) && $method != "_") {
            $quote->getShippingAddress()->setShippingMethod(null);
        }
        //B-1004486 -Sanchit Bhatia Send production location Id in get Rate API
        if (!empty($this->checkoutSession->getProductionLocationId())) {
            $this->checkoutSession->unsProductionLocationId();
        }

        if($this->addToCartPerformanceOptimizationToggle->isActive()) {
            $removeFedexAccountNumber = $this->checkoutSession->getRemoveFedexAccountNumber();
            $appliedFedexAccNumber = $this->checkoutSession->getAppliedFedexAccNumber();
            $fedexAccountNumber = $quote->getData("fedex_account_number");
            if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_TIGER_D207139_TOGGLE)) {
                if ((!$removeFedexAccountNumber && !$appliedFedexAccNumber) || (!$fedexAccountNumber && !$removeFedexAccountNumber)) {
                    $defaultFedexAccountNumber = $this->cartDataHelper->getDefaultFedexAccountNumber();
                    $quote->setData('fedex_account_number', $defaultFedexAccountNumber);
                }
            } else {
                if (($removeFedexAccountNumber && !$appliedFedexAccNumber) || (!$fedexAccountNumber && !$removeFedexAccountNumber)) {
                    $defaultFedexAccountNumber = $this->cartDataHelper->getDefaultFedexAccountNumber();
                    $quote->setData('fedex_account_number', $defaultFedexAccountNumber);
                }
            }
        } else {
            //B-1475094 - Start Here
            $quoteConditionWithToggle = (!$quote->getData("fedex_account_number")
                && !$this->checkoutSession->getRemoveFedexAccountNumber());

            if ((!$this->checkoutSession->getRemoveFedexAccountNumber()
                    && !$this->checkoutSession->getAppliedFedexAccNumber())
                || $quoteConditionWithToggle) {
                $defaultFedexAccountNumber = $this->cartDataHelper->getDefaultFedexAccountNumber();
                $quote->setData('fedex_account_number', $defaultFedexAccountNumber);
            }
            //B-1475094 - End Here
        }

        $rateQuote = [];
        if (!$this->fxoRateHelper->isEproCustomer()) {
            if ($this->rateQuoteOptimizationToggle->isActive()) {
                $rateQuote = $this->fXORateQuoteApi->getFXORateQuote($quote);
            } else {
                $rateQuote = $this->fxoRateQuote->getFXORateQuote($quote);
            }
        } else {
            if (!$this->checkoutSession->getRemoveFedexAccountNumberWithSi()) {
                $rateQuote = $this->fxoRateHelper->getFXORate($quote);
            }
        }
        if ($this->productBundleConfig->isTigerE468338ToggleEnabled() && !empty($rateQuote)) {
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                if ($quoteItem->getProductType() === Type::TYPE_BUNDLE) {
                    $this->bundlePriceCalculator->calculateBundlePrice($rateQuote, $quoteItem);
                }
            }
        }
    }
}
