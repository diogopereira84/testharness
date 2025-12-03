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
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session as CustomerSession;

class AccountPost extends ParentCouponPost
{
    /**
     * Execute AccountPost Controller
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
     * @param CartDataHelper $cartDataHelper
     * @param ToggleConfig $toggleConfig
     * @param CustomerSession $customerSession
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
        protected FXORate $fxoRateHelper,
        protected FXORateQuote $fxoRateQuote,
        protected CartDataHelper $cartDataHelper,
        protected ToggleConfig $toggleConfig,
        protected CustomerSession $customerSession
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
    }

    /**
     * Execute Method.
     */
    public function execute()
    {
        $accountNo = $this->getRequest()->getParam('remove') == 1 ? '' :
        trim((string)$this->getRequest()->getParam('fedex_account_no'));
        $quote = $this->cartFactory->create()->getQuote();
        if (!empty($accountNo)) {
            $this->_checkoutSession->unsAccountDiscountWarningFlag();
            $accountNo = $this->cartDataHelper->encryptData($accountNo);
            $quote->setData('fedex_account_number', $accountNo);
            $this->_checkoutSession->setAppliedFedexAccNumber($accountNo);
            $this->_checkoutSession->setAccountDiscountExist(true);

            if ($quote->getData("coupon_code")) {
                $this->_checkoutSession->setCouponDiscountExist(true);
            }
        } else {
            $quote->setData('fedex_account_number', '');
            if ($this->_checkoutSession->getAccountDiscountExist()) {
                $this->_checkoutSession->unsAccountDiscountExist();
            }
            //B-1275215: Set flag to identify customer manually removed fedex account number
            $this->_checkoutSession->setRemoveFedexAccountNumber(true);
        }
        $rateResponse = $this->fxoRateQuote->getFXORateQuote($quote);

        if (!empty($rateResponse['output']) && !empty($rateResponse['output']['alerts'])) {
            foreach ($rateResponse['output']['alerts'] as $alerts) {
                if ($alerts['code'] == 'RATEREQUEST.FEDEXACCOUNTNUMBER.INVALID') {
                    $message = 'The account number entered is invalid.';
                    $this->customerSession->setFedexAccountWarning($message);
                    $this->_checkoutSession->setAccountDiscountWarningFlag(true);
                }
            }
        }

        return $this->_goBack();
    }
}
