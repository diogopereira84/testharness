<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PageBuilderPromoBanner\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\CartFactory;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Request\Http;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session as CustomerSession;

class CheckCouponStatus implements ObserverInterface
{
    public $excludeUrlForPromoCodeApply = ['checkout','sales','couponcode'];

    /**
     * Initialize dependencies.
     *
     * @param Session $checkoutSession
     * @param CartFactory $cartFactory
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param Escaper $escaper
     * @param ManagerInterface $messageManager
     * @param Http $request
     * @param ToggleConfig $toggleConfig
     * @param Session $customerSession
     */
    public function __construct(
        protected Session $checkoutSession,
        protected CartFactory $cartFactory,
        protected FXORate $fxoRateHelper,
        protected FXORateQuote $fxoRateQuote,
        protected Escaper $escaper,
        protected ManagerInterface $messageManager,
        protected Http $request,
        protected ToggleConfig $toggleConfig,
        protected CustomerSession $customerSession
    )
    {
    }

    /**
     * Identified, is the coupon code is applied by the promotion banner.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (
            $this->toggleConfig->isPromoCodeEnabled() &&
            !in_array($this->request->getRouteName(), $this->excludeUrlForPromoCodeApply)
        ) {
            $httpReferer = $this->request->getServer('HTTP_REFERER');
            if ($httpReferer && str_contains($httpReferer, "code=")) {
                $urlParams = parse_url($httpReferer);
                parse_str($urlParams['query'], $params);
                if (!empty($params['code']) &&
                    $marketChannelPromoCode = $params['code']) {
                    $quote = $this->cartFactory->create()->getQuote();
                    $quote->setCouponCode($marketChannelPromoCode)->save();
                    $this->checkoutSession->setIsApplyCoupon(true);
                }
            }
        }

        $actionFullName = $this->request->getFullActionName() ? trim($this->request->getFullActionName()) : '';
        if ($actionFullName !== 'checkout_cart_index') {
            $this->applyPromoCode();
        }
        $this->checkoutSession->setIsApplyCoupon(false);

        return $this;
    }

    /**
     * Apply coupon code
     *
     * @return $void
     */
    public function applyCouponCode()
    {
        $isApplyCoupon = $this->checkoutSession->getIsApplyCoupon();
        $quote = $this->cartFactory->create()->getQuote();
        if (($isApplyCoupon) && ($quote->getItemsCount() >0)) {
            $couponCode = $quote->getCouponCode();

            $fxoRateResponse = $this->fxoRateQuote->getFXORateQuote($quote);

            if (isset($fxoRateResponse['alerts'])) {
                $alertCode = $fxoRateResponse['alerts'][0]['code'];
                if ($alertCode == "COUPONS.CODE.INVALID") {
                    $this->messageManager->addErrorMessage(
                        __('The coupon code "%1" is not valid.', $this->escaper->escapeHtml($couponCode))
                    );
                } elseif ($alertCode == "MINIMUM.PURCHASE.REQUIRED") {
                    $this->messageManager->addErrorMessage(
                        __(
                            'Minimum purchase required to redeem the coupon code',
                            $this->escaper->escapeHtml($couponCode)
                        )
                    );
                }
                $quote->setCouponCode('');
            } else {
                if ($couponCode && $isApplyCoupon) {
                    $this->messageManager->addSuccessMessage(
                        __('You used coupon code "%1".', $this->escaper->escapeHtml($couponCode))
                    );
                }
            }
        }
    }

    /**
     * Apply promo code
     *
     * @return $void
     */
    public function applyPromoCode()
    {
        $isApplyCoupon = $this->checkoutSession->getIsApplyCoupon();
        $quote = $this->cartFactory->create()->getQuote();
        if (($isApplyCoupon) && ($quote->getItemsCount() > 0)) {
            $couponCode = $quote->getCouponCode();
            $fxoRateResponse = $this->fxoRateQuote->getFXORateQuote($quote);
            if (isset($fxoRateResponse['output']['alerts'])) {
                $alertCode = $fxoRateResponse['output']['alerts'][0]['code'] ?? '';
                if ($alertCode == "COUPONS.CODE.INVALID") {
                    $this->messageManager->addErrorMessage(
                        __('Promo code invalid. Please try again.')
                    );
                } elseif ($alertCode == "MINIMUM.PURCHASE.REQUIRED") {
                    $this->messageManager->addErrorMessage(
                        __(
                            'Minimum purchase required to redeem the coupon code',
                            $this->escaper->escapeHtml($couponCode)
                        )
                    );
                }
                $this->customerSession->unsPromoErrorMessage();
            }
        }
    }
}
