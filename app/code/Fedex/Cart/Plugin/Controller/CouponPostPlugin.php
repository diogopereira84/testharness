<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Plugin\Controller;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Service\BundlePriceCalculator;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Controller\Cart\CouponPost as Subject;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class CouponPostPlugin
{
    /**
     * CouponPostPlugin constructor
     *
     * @param CartFactory $cartFactory
     * @param FXORateQuote $fxoRateQuote
     * @param ToggleConfig $toggleConfig
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CartFactory $cartFactory,
        protected FXORateQuote $fxoRateQuote,
        protected ToggleConfig $toggleConfig,
        protected Session $checkoutSession,
        protected LoggerInterface $logger,
        protected BundlePriceCalculator $bundlePriceCalculator,
        protected ConfigInterface $productBundleConfig
    )
    {
    }

    /**
     * After Execute
     *
     * @param Subject $subject
     * @param Redirect $result
     * @throws LocalizedException
     */
    public function afterExecute(Subject $subject, Redirect $result)
    {
        if ($this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
            $couponCode = $subject->getRequest()->getParam('remove') == 1 ? '' :
                trim((string)$subject->getRequest()->getParam('coupon_code'));
            $quote = $this->cartFactory->create()->getQuote();
            $quote->setCouponCode((string)$couponCode);
            if (!empty($couponCode) && $quote->getData('fedex_account_number')) {
                $this->checkoutSession->setAccountDiscountExist(true);
            } else {
                if ($this->checkoutSession->getCouponDiscountExist()) {
                    $this->checkoutSession->unsCouponDiscountExist();
                }
            }
            $fxoResponse = $this->fxoRateQuote->getFXORateQuote($quote);
            if ($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                $this->applyBundlePriceCalculations($quote, $fxoResponse);
            }
        }
        return $result;
    }

    /**
     * Calculate bundle prices for quote items.
     *
     * @param Quote $quote
     * @param mixed $fxoResponse Custom data required for price calculation
     * @return void
     * @throws LocalizedException
     */
    public function applyBundlePriceCalculations(Quote $quote, $fxoResponse)
    {
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getProductType() === Type::TYPE_BUNDLE) {
                $this->bundlePriceCalculator->calculateBundlePrice($fxoResponse, $quoteItem);
            }
        }
    }

}
