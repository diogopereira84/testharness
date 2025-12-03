<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Controller\Cart;

use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Checkout\Model\Cart;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Controller\Cart\CouponPost as ParentCouponPost;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CouponPost extends ParentCouponPost
{
    private Session $checkoutSession;

    /**
     * Execute CouponPost Controller
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     * @param CouponFactory $couponFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param CartFactory $cartFactory
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        Cart $cart,
        CouponFactory $couponFactory,
        CartRepositoryInterface $quoteRepository,
        protected CartFactory $cartFactory,
        protected FXORateQuote $fxoRateQuote,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $couponFactory,
            $quoteRepository
        );
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Execute Method.
     */
    public function execute()
    {
        if (!$this->toggleConfig->getToggleConfigValue('explores_remove_adobe_commerce_override')) {
            $couponCode = $this->getRequest()->getParam('remove') == 1 ? '' :
                trim((string)$this->getRequest()->getParam('coupon_code'));
            $quote = $this->cartFactory->create()->getQuote();
            $quote->setCouponCode((string)$couponCode);
            if (!empty($couponCode) && $quote->getData('fedex_account_number')) {
                $this->checkoutSession->setAccountDiscountExist(true);
            } else {
                if ($this->checkoutSession->getCouponDiscountExist()) {
                    $this->checkoutSession->unsCouponDiscountExist();
                }
            }
            $this->fxoRateQuote->getFXORateQuote($quote);
        }
        return $this->_goBack();
    }
}
