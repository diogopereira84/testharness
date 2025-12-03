<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Commercial\ViewModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\UrlInterface;
use Fedex\Commercial\Helper\CommercialHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Search\Helper\Data;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session;
use Fedex\CatalogMvp\ViewModel\MvpHelper as MvpHelperViewModel;

/**
 * SdeSsoConfiguration ViewModel class
 */
class CommercialSsoConfiguration implements ArgumentInterface
{
    public const CANVA_DESIGN_ENABLED = 'fedex/my_account_menu_options/my_design_templates';
    public const TIGER_D_161115_ADD_TO_CART_MESSAGE_WHEN_RATE_QUOTE_FAILS = 'tiger_d161115_add_to_cart_message_when_rate_quote_fails';
    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';


    /**
     * SsoConfiguration constructor.
     *
     * @param SessionFactory $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param Http $request
     * @param UrlInterface $urlInterface
     * @param CommercialHelper $commercialHelper
     * @param SdeHelper $sdeHelper
     * @param Data $searchHelper $searchHelper
     * @param SelfReg $selfReg
     * @param ScopeConfigInterface $scopeConfig
     * @param SsoConfiguration $ssoConfig
     * @param ToggleConfig $toggleConfig
     * @param Session $session
     * @param MvpHelperViewModel $mvpHelperViewModel
     */
    public function __construct(
        protected SessionFactory $customerSession,
        protected CustomerRepositoryInterface $customerRepository,
        protected Http $request,
        protected UrlInterface $urlInterface,
        protected CommercialHelper $commercialHelper,
        protected SdeHelper $sdeHelper,
        protected Data $searchHelper,
        protected SelfReg $selfReg,
        protected ScopeConfigInterface $scopeConfig,
        protected SsoConfiguration $ssoConfig,
        protected ToggleConfig $toggleConfig,
        private Session $session,
        protected MvpHelperViewModel $mvpHelperViewModel
    ) {
    }

    /**
     * Get name of getCommercialCustomerName customer
     *
     * @return string
     */
    public function getCommercialCustomerName()
    {
        if ($customerId = $this->commercialCustomerSession()->getId()) {
            $customer = $this->customerRepository->getById($customerId);

            return $customer->getFirstname();
        }

        return '';
    }

    /**
     * Checks if the customer is a SDE customer
     *
     * @return boolean true|false
     */
    public function isSdeCustomer()
    {
        $customerSession = $this->commercialCustomerSession();
        if ($customerSession->getId() && !$customerSession->getCustomerCompany()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get customer session
     *
     * @return commercialCustomerSession
     */
    public function commercialCustomerSession()
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
           return $this->getOrCreateCustomerSession();
        } else {
            return $this->customerSession->create();
        }
    }

    /**
     * Get current customer visited URL
     *
     * @return commercialCustomerSession
     */
    public function getCommercialCurrentUrl()
    {
        return $this->urlInterface->getCurrentUrl();
    }

    /**
     * Get if request from SDE
     *
     * @return bool
     */
    public function getIsRequestFromSdeStore()
    {
        return $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Get if request from SDE with FCL On
     *
     * @return bool
     */
    public function getIsRequestFromSdeStoreFclLogin()
    {
        return $this->sdeHelper->getIsRequestFromSdeStoreFclLogin();
    }

    /**
     * Get if request from SelfReg with FCL Enabled
     *
     * @return bool
     */
    public function isSelfRegCustomerWithFclEnabled()
    {
        return $this->selfReg->isSelfRegCustomerWithFclEnabled();
    }

    /**
     * Get current customer visited URL
     *
     * @return isGlobalCommercialCustomerRequest
     */
    public function isGlobalCommercialCustomerRequest()
    {
        return $this->commercialHelper->isGlobalCommercialCustomer();
    }

    /**
     * VM indicate the calling function from view model
     *
     * @return getResultUrl
     */
    public function getVmResultUrl()
    {
        return $this->searchHelper->getResultUrl();
    }

    /**
     * VM indicate the calling function from view model
     *
     * @return getSuggestUrl
     */
    public function getVmSuggestUrl()
    {
        return $this->searchHelper->getSuggestUrl();
    }

    /**
     * VM indicate the calling function from view model
     *
     * @return getMinQueryLength
     */
    public function getVmMinQueryLength()
    {
        return $this->searchHelper->getMinQueryLength();
    }

    /**
     * VM indicate the calling function from view model
     *
     * @return getQueryParamName
     */
    public function getVmQueryParamName()
    {
        return $this->searchHelper->getQueryParamName();
    }

    /**
     * VM indicate the calling function from view model
     *
     * @return getEscapedQueryText
     */
    public function getVmEscapedQueryText()
    {
        return $this->searchHelper->getEscapedQueryText();
    }

    /**
     * VM indicate the calling function from view model
     *
     * @return getMaxQueryLength
     */
    public function getVmMaxQueryLength()
    {
        return $this->searchHelper->getMaxQueryLength();
    }

    /**
     * Identify user request platform
     *
     * @return identifyUserRequest
     */
    public function identifyUserRequest()
    {
        $responseDevice = '';
        $deviceName = "/(android|avantgo|blackberry|bolt|boost|cricket|docomo
|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i";
        $isMobile = preg_match($deviceName, $_SERVER["HTTP_USER_AGENT"]);

        if ($isMobile) {
            $responseDevice = '-mobile';
        }
        return $responseDevice;
    }

    /**
     * Get SDE Logout Url
     *
     * @return string
     */
    public function getSdeLogoutUrl()
    {
        return $this->sdeHelper->getLogoutUrl();
    }

    public function getSelfRegLogoutUrl()
    {

        $redirectUrl = $this->urlInterface->getUrl('selfreg/logout/index');
        $wlgnLogoutPageUrl = $this->ssoConfig->getGeneralConfig('fcl_logout_url');
        $queryParameter = $this->ssoConfig->getGeneralConfig('fcl_logout_query_param');
        $wlgnLogoutUrl = $wlgnLogoutPageUrl.'?'.$queryParameter.'='.$redirectUrl;

        return $wlgnLogoutUrl;
    }

    /**
     * Check if SelfReg Admin
     *
     * @return string
     */
    public function isSelfRegAdmin()
    {
        return $this->selfReg->isSelfRegCustomerAdmin();
    }

    /**
     * Check if SelfReg Company
     *
     * @return string
     */
    public function isSelfRegCompany()
    {
        return $this->selfReg->isSelfRegCompany();
    }

    /**
     * @return mixed
     */
    public function getCanvaDesignEnabled()
    {
        return $this->scopeConfig->getValue(
            self::CANVA_DESIGN_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Logout URL details (SSO/FCL) in commercial
     */
    public function getCommercialLogoutInfo()
    {
        $compInfo = $this->commercialHelper->getCompanyInfo();
        if (!empty($compInfo)) {
            if ($compInfo['login_method'] === 'commercial_store_wlgn') {
                $redirectUrl = $this->urlInterface->getCurrentUrl();
                $wlgnLogoutPageUrl = $this->ssoConfig->getGeneralConfig('fcl_logout_url');
                $queryParameter = $this->ssoConfig->getGeneralConfig('fcl_logout_query_param');
                $wlgnLogoutUrl = $wlgnLogoutPageUrl.'?'.$queryParameter.'='.$redirectUrl;
                $compInfo['logoutUrl'] = $wlgnLogoutUrl;
            }

            return $compInfo;
        }

        return false;
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->session->isLoggedIn()){
            $this->session = $this->customerSession->create();
        }
        return $this->session;
    }

    /**
     * Checks if the customer is SelfReg Admin
     *
     * @return bool
     */
    public function isSharedCatalogPermissionEnabled()
    {
        return $this->mvpHelperViewModel->isSharedCatalogPermissionEnabled();
    }

    /**
     * Check E443304_stop_redirect_mvp_addtocart toggle enable or not
     */
    public function isEnableStopRedirectMvpAddToCart()
    {
        return $this->mvpHelperViewModel->isEnableStopRedirectMvpAddToCart();
    }

    /**
    * check E-449727 Improving Visibility to Change Password toggle enable or not
    */
    public function isimprovingpasswordtoggle()
    {
        return $this->toggleConfig->getToggleConfigValue('sgc_improving_visibility_to_change_password');
    }

}
