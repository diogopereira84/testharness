<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Block\Cart;

use Fedex\SubmitOrderSidebar\Model\SubmitOrderSidebarConfigProvider;
use Magento\Checkout\Block\Cart\AbstractCart;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Block with apply-coupon form.
 *
 * @api
 * @since 100.0.2
 */
class Account extends AbstractCart
{
    const TOGGLE_CART_FXO_ACCOUNT_NUMBER_FIX = 'tiger_team_d_204604_fxo_account_number_fix';
    /**
     * Account constructor
     *
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param CartDataHelper $cartDataHelper
     * @param CompanyHelper $companyHelper
     * @param ToggleConfig $toggleConfig
     * @param \Fedex\EnhancedProfile\Helper\Account $helperAccount
     * @param SubmitOrderSidebarConfigProvider $submitOrderSidebarConfigProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        protected CartDataHelper $cartDataHelper,
        protected CompanyHelper $companyHelper,
        protected ToggleConfig $toggleConfig,
        protected \Fedex\EnhancedProfile\Helper\Account $helperAccount,
        protected SubmitOrderSidebarConfigProvider $submitOrderSidebarConfigProvider,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    /**
     * Applied Account no.
     *
     * @return string
     */
    public function getFedexAccountLast4()
    {
        $fedExAccountNumber = $this->getQuote()->getData('fedex_account_number');

        if ($this->toggleConfig->getToggleConfigValue(self::TOGGLE_CART_FXO_ACCOUNT_NUMBER_FIX)) {
            if (!empty($fedExAccountNumber)) {
                $decryptedFedexAccount = substr((string)$this->cartDataHelper->decryptData($fedExAccountNumber), -4);
                return !empty($decryptedFedexAccount) ? 'ending in *' . $decryptedFedexAccount : '';
            }
            return $fedExAccountNumber;
        } else {
            if (!empty($fedExAccountNumber)) {
                $fedExAccountNumber = substr((string)$this->cartDataHelper->decryptData($fedExAccountNumber), -4);
                return "ending in *" . $fedExAccountNumber;
            } else {
                return $fedExAccountNumber;
            }
        }
    }

    /**
     * B-1294484: Can Show Fedex Account Remove Button
     * User should not allow to remove site configured account number
     *
     * @return bool
     */
    public function canShowFedexAccountRemoveBtn()
    {
        $fedExAccountNumber = $this->getQuote()->getData('fedex_account_number');
        if ($fedExAccountNumber) {
            $fedExAccountNumber = $this->cartDataHelper->decryptData($fedExAccountNumber);
            try {
                $company = $this->companyHelper->getCustomerCompany();
                if ($company) {
                    $paymentAccountNumber = !empty($company->getFedexAccountNumber())
                    ? trim($company->getFedexAccountNumber()):null;
                    $discountAccountNumber = !empty($company->getDiscountAccountNumber())
                    ? trim((string)$company->getDiscountAccountNumber()) :null;
                    if ($fedExAccountNumber == $paymentAccountNumber) {
                        return $company->getFxoAccountNumberEditable() ? true : false;
                    } elseif ($fedExAccountNumber == $discountAccountNumber) {
                        return $company->getDiscountAccountNumberEditable() ? true : false;
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Applied fedex account number
     *
     * @return string
     */
    public function getAppliedFedexAccount()
    {
        $fedExAccountNumber = $this->getQuote()->getData('fedex_account_number');
        if (!empty($fedExAccountNumber)) {
            $fedExAccountNumber = (string)$this->cartDataHelper->decryptData($fedExAccountNumber);
        }

        if ($fedExAccountNumber && $this->helperAccount->getCompanyLoginType() == 'FCL') {
            $accountType = $this->helperAccount->getAccountNumberType($fedExAccountNumber);
            if ($accountType && $accountType == "DISCOUNT") {
                $fedExAccountNumber = null;
            }
        }

        return $fedExAccountNumber;
    }
}
