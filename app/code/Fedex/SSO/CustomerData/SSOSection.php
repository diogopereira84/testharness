<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare (strict_types = 1);

namespace Fedex\SSO\CustomerData;

use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\Api\Data\ConfigInterface as ModuleConfig;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Cart\Helper\Data as CartHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\ViewModel\SsoConfiguration;

class SSOSection implements SectionSourceInterface
{

    /**
     * SSOSection constructor
     *
     * @param ModuleConfig $moduleConfig
     * @param SdeHelper $sdeHelper
     * @param CheckoutSession $checkoutSession
     * @param ToggleConfig $toggleConfig
     * @param CompanyHelper $companyHelper
     * @param CustomerRepository $customerRepository
     * @param CustomerSession $customerSession
     * @param CartHelper $cartHelper
     * @param SelfReg $selfReg
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        private ModuleConfig $moduleConfig,
        private SdeHelper $sdeHelper,
        protected CheckoutSession $checkoutSession,
        protected ToggleConfig $toggleConfig,
        protected CompanyHelper $companyHelper,
        protected CustomerRepository $customerRepository,
        protected CustomerSession $customerSession,
        protected CartHelper $cartHelper,
        protected SelfReg $selfReg,
        protected SsoConfiguration $ssoConfiguration
    )
    {
    }

    /**
     * Add custom data to customer section data
     *
     * @return array
     */
    public function getSectionData(): array
    {
        return [
            'login_page_url' => $this->moduleConfig->getLoginPageURL() ?? '',
            'login_page_query_parameter' => $this->moduleConfig->getQueryParameter() ?? '',
            // B-1149167 : RT-ECVS-SDE-SDK changes for File upload
            // B-1333928 : Implement Switch for POD 1.0 Integration
            'sensitive_data_workflow' => $this->sdeHelper->getIsSdeStore(),
            'fedex_account_number' => $this->getFXOAccountNumber()
        ];
    }

    /**
     * Get FXO account number
     *
     * @return string
     */
    public function getFXOAccountNumber()
    {
        $fxoAccountNumber = null;
        if ($this->customerSession->getCustomerId()) {
            $customerD = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $companyAttributes = $customerD->getExtensionAttributes()->getCompanyAttributes();
            $companyId = $companyAttributes ? $companyAttributes->getCompanyId() : '';
            $isSdeOrSelfReg = $this->sdeHelper->getIsSdeStore()
            || ($this->selfReg->isSelfRegCompany());
        
            $quote = $this->checkoutSession->getQuote();
            if ($isSdeOrSelfReg
            && (($quote->getData("fedex_account_number") && $this->companyHelper->getFedexAccountNumber($companyId))
            ||  ($quote->getData("fedex_account_number") && $this->checkoutSession->getAppliedFedexAccNumber()))
            ) {
                $fxoAccountNumber = $this->cartHelper->decryptData($quote->getData("fedex_account_number"));
            } elseif ($quote->getData("fedex_account_number") && !$isSdeOrSelfReg) {
                $fxoAccountNumber = $this->cartHelper->decryptData($quote->getData("fedex_account_number"));
            } else {
                $fxoAccountNumber = $this->getFXOAccountForLoggedIn($companyId);
            }
        } else {
            $quote = $this->checkoutSession->getQuote();
            $fxoAccountNumber = $this->cartHelper->decryptData($quote->getData("fedex_account_number"));
        }

        return $fxoAccountNumber;
    }

    /**
     * Get FXO account number for logged in user
     *
     * @return string
     */
    public function getFXOAccountForLoggedIn($companyId)
    {
        $fxoAccountNumber = null;
        if ($this->ssoConfiguration->isFclCustomer()
        && !$this->checkoutSession->getRemoveFedexAccountNumber()) {
            $fxoAccountNumber = $this->cartHelper->getDefaultFxoNumberForFCLUser();
        } elseif ($this->sdeHelper->getIsSdeStore()
        || ($this->selfReg->isSelfRegCompany())) {
            $fxoAccountNumber = $this->companyHelper->getFedexAccountNumber($companyId);
        }

        return $fxoAccountNumber;
    }
}
 
