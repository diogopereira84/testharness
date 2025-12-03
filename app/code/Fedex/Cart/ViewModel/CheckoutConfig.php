<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Cart\ViewModel;

use Fedex\Shipment\ViewModel\ShipmentConfig;
use Fedex\CustomizedMegamenu\Helper\Data as CustomizedMegamenuDataHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\CartFactory;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Company\Helper\Data as CompanyHelper;

/*
 * Checkout config viewmodel calss
*/
class CheckoutConfig implements ArgumentInterface
{
    /**  B-1109907: Optimize Configurations  */
    public const GENERAL_DOCUMENT_OFFICE_API_URL_ID = 'fedex/general/dunc_office_api_url';

    public const PROMO_DISCOUNT = 'promo_discount';
    public const ACCOUNT_DISCOUNT = 'account_discount';
    public const REORDER = 'reorder';
    public const TNC = 'terms_and_conditions';
    public const GENERAL_DOCUMENT_PREVIEW_IMAGE_URL = 'fedex/catalogmvp/preview_api_url';

    /**
     * Checkout Config Constructor
     *
     * @param ShipmentConfig $shipmentConfig
     * @param CustomizedMegamenuDataHelper $customizedMegamenuDataHelper
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param CartFactory $cartFactory
     * @param CartDataHelper $cartDataHelper
     * @param SelfReg $selfregHelper
     * @param ToggleConfig $toggleConfig
     * @param CompanyHelper $companyHelper
     */
    public function __construct(
        protected ShipmentConfig $shipmentConfig,
        protected CustomizedMegamenuDataHelper $customizedMegamenuDataHelper,
        protected Session $customerSession,
        protected CheckoutSession $checkoutSession,
        protected CartFactory $cartFactory,
        protected CartDataHelper $cartDataHelper,
        protected SelfReg $selfregHelper,
        protected ToggleConfig $toggleConfig,
        protected CompanyHelper $companyHelper
    )
    {
    }

    /**
     * Get document office api url
     *
     * @return string
     */
    public function getDocumentOfficeApiUrl()
    {
        $storeId = $this->customizedMegamenuDataHelper->getStoreId();

        return $this->shipmentConfig->getConfigValue(self::GENERAL_DOCUMENT_OFFICE_API_URL_ID, $storeId);
    }

    /**
     * Get document image url
     *
     * @return string
     */
    public function getDocumentImagePreviewUrl()
    {
        $storeId = $this->customizedMegamenuDataHelper->getStoreId();
 
        return $this->shipmentConfig->getConfigValue(self::GENERAL_DOCUMENT_PREVIEW_IMAGE_URL, $storeId);
    }

    /**
     * Getting the customerSession message for Promo warnings
     */
    public function getPromoWarnings()
    {
        return $this->customerSession->getPromoErrorMessage();
    }

    /**
     * Unset customer Session
     */
    public function unSetPromoWarnings()
    {
        return $this->customerSession->unsPromoErrorMessage();
    }

    /**
     * Getting the customerSession message for Account Warnings
     */
    public function getAccountWarnings()
    {
        return $this->customerSession->getFedexAccountWarning();
    }

    /**
     * Unset customer Session for Account
     */
    public function unSetAccountWarnings()
    {
        return $this->customerSession->unsFedexAccountWarning();
    }

    /**
     * Getting the checkoutSession message for Account & PromoCode Warning
     */
    public function getWarningMessage()
    {
        return $this->checkoutSession->getWarningMessageFlag();
    }

    /**
     * Unsetting the checkoutSession message for Account & PromoCode Warning
     */
    public function unSetWarningMessage()
    {
        return $this->checkoutSession->unsWarningMessageFlag();
    }

    /**
     * Get applied fedex account number
     *
     * @return string
     */
    public function getAppliedFedexAccountNumber()
    {
        $quote = $this->cartFactory->create()->getQuote();

        return $this->cartDataHelper->decryptData($quote->getFedexAccountNumber());
    }

    /**
     * To get fedex account discount warning flag
     *
     * @return string
     */
    public function getAccountDiscountWarningFlag()
    {
        return $this->checkoutSession->getAccountDiscountWarningFlag();
    }

     /**
      * is SelfReg Customer
      *
      * @return boolean
      */
    public function isSelfRegCustomer()
    {
        return $this->selfregHelper->isSelfRegCustomer();
    }

    /**
     * Get Active Quote
     * @return object
     */
    public function getCurrentActiveQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Is promo code discount enabled for company or store level
     *
     * @return bool
     */
    public function isPromoDiscountEnabled()
    {
        $storeId = $this->customizedMegamenuDataHelper->getStoreId();
        $promoToggleConfig = $this->shipmentConfig
            ->getConfigValue('web/store_discount_configuration/store_level_promo_display', $storeId);

        return $this->getToggleFinalStatus($promoToggleConfig, static::PROMO_DISCOUNT, $storeId);
    }

    /**
     * Is account discount enabled for company or store level
     *
     * @return bool
     */
    public function isAccountDiscountEnabled()
    {
        $storeId = $this->customizedMegamenuDataHelper->getStoreId();
        $accountToggleConfig = $this->shipmentConfig
            ->getConfigValue('web/store_discount_configuration/store_level_account_display', $storeId);

        return $this->getToggleFinalStatus($accountToggleConfig, static::ACCOUNT_DISCOUNT, $storeId);
    }

    /**
     * Is Reorder enabled for company or store level
     *
     * @return bool
     */
    public function isReorderEnabled()
    {
        $storeId = $this->customizedMegamenuDataHelper->getStoreId();
        $reorderToggleConfig = $this->shipmentConfig
            ->getConfigValue('web/store_commercial_toggle_configuration/store_level_reorder_display', $storeId);
        
        return $this->getToggleFinalStatus($reorderToggleConfig, static::REORDER, $storeId);
    }

    /**
     * Is Terms and Conditions enabled for company or store level
     *
     * @return bool
     */
    public function isTermsAndConditionsEnabled()
    {
        $storeId = $this->customizedMegamenuDataHelper->getStoreId();
        $tncToggleConfig = $this->shipmentConfig
            ->getConfigValue('web/store_commercial_toggle_configuration/store_level_tc_display', $storeId);
        
        return $this->getToggleFinalStatus($tncToggleConfig, static::TNC, $storeId);
    }

    /**
     * Get Toggle Final Status
     *
     * @param boolean $storeLevelToggleStatus
     * @param string $toggleType
     * @return boolean
     */
    public function getToggleFinalStatus($storeLevelToggleStatus = null, $toggleType = null, $storeId = null)
    {
        $storeCode = $this->customizedMegamenuDataHelper->getStoreCode();
            
        if ($storeCode == 'default' && $storeLevelToggleStatus) {
            return true;
        } elseif ($storeCode == 'default' && !$storeLevelToggleStatus) {
            return false;
        } else {
            $companyLevelToggle = $this->companyHelper->getCompanyLevelConfig()[$toggleType] ?? false;

            if ($storeLevelToggleStatus && $companyLevelToggle) {
                $toggleStatus = true;
            } elseif (!$storeLevelToggleStatus && $companyLevelToggle) {
                $toggleStatus = true;
            } elseif ($storeLevelToggleStatus && !$companyLevelToggle) {
                $toggleStatus = false;
            } else {
                $toggleStatus = false;
            }

            return $toggleStatus;
        }
    }
}
