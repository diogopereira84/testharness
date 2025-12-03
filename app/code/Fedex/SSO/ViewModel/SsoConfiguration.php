<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\ViewModel;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnhancedProfile\Helper\Account;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Laminas\Uri\Http;
use Fedex\SSO\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Fedex\Delivery\Helper\Data as CompanyHelper;

/**
 * SsoConfiguration ViewModel class
 */
class SsoConfiguration implements ArgumentInterface
{
    public const FCL_MY_PROFILE_URL = 'sso/general/fcl_my_profile_url';

    public const CONTACT_INFORMATION_PROFILE_URL = 'sso/general/contact_information_profile_url';

    public const XML_PATH_FEDEX_SSO = 'sso/';
    public const RETAILSTORECODE = 'main_website_store';
    public const CANVA_DESIGN_ENABLED = 'fedex/my_account_menu_options/my_design_templates';

    public const XML_IMPROVING_PASSWORD_TOGGLE = 'environment_toggle_configuration/environment_toggle/sgc_improving_visibility_to_change_password';

    public const TIGER_D239988 = 'tiger_d239988';

    /**
     * Login Session Idel Timeout
     */
    public const XML_PATH_FEDEX_SSO_SESSION_IDLE_TIMEOUT = 'sso/login_session/login_session_idle_timeout';

    /**
     * SsoConfiguration constructor.
     *
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlInterface
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param CookieManagerInterface $cookieManagerInterface
     * @param Http $zendUri
     * @param LoggerInterface $logger
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param DeliveryHelper $deliveryHelper
     * @param StoreManagerInterface $storeManager
     * @param SdeHelper $sdeHelper
     * @param SelfReg $selfReg
     * @param Data $dataHelper
     * @param ToggleConfig $toggleConfig
     * @param RequestInterface $request
     * @param FuseBidHelper $fuseBidHelper
     * @return void
     */
    public function __construct(
        protected Session $customerSession,
        protected ScopeConfigInterface $scopeConfig,
        protected UrlInterface $urlInterface,
        protected CustomerRepositoryInterface $customerRepository,
        protected AccountManagementInterface $accountManagement,
        protected CookieManagerInterface $cookieManagerInterface,
        protected Http $zendUri,
        protected LoggerInterface $logger,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected DeliveryHelper $deliveryHelper,
        protected StoreManagerInterface $storeManager,
        protected SdeHelper $sdeHelper,
        protected SelfReg $selfReg,
        protected Account $accountHelper,
        protected Data $dataHelper,
        protected ToggleConfig $toggleConfig,
        protected RequestInterface $request,
        protected FuseBidHelper $fuseBidHelper,
        protected CompanyHelper $companyHelper
    )
    {
    }

    /**
     * Get type bid param.
     *
     * @return string
     */
    public function isBidQuoteParamSet()
    {
        return $this->request->getParam('bidquote');
    }

    /**
     * Check if FuseBidding toggle is enable.
     *
     * @return boolean
     */
    public function isFuseBidToggleEnabled()
    {
        return $this->fuseBidHelper->isFuseBidGloballyEnabled();
    }

    /**
     * Get Config Value
     *
     * @param string $field
     * @param int $storeId
     *
     * @return mixed Config Value
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get logic mock toggle
     *
     * @return boolean
     */
    public function getLoginMockToggle()
    {
        return $this->scopeConfig->getValue(
            'wiremock_service/selfreg_wiremock_group/fcl_login_api_wiremock_enable',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }

    /**
     * Get General Configuration Value
     *
     * @param string $code
     * @param int $storeId
     *
     * @return string
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FEDEX_SSO . 'general/' . $code, $storeId);
    }

    /**
     * Checks if the customer is retail or E-Pro
     *
     * @return boolean true|false
     */
    public function isFclCustomer()
    {
        return $this->customerSession->getCustomerId() && $this->accountHelper->isRetail();
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
     * Get if request from SelfReg with SSO Enabled
     *
     * @return bool
     */
    public function isSelfRegCustomerWithSSOEnabled()
    {
        return $this->selfReg->isSelfRegCustomerWithSSOEnabled();
    }

    /**
     * Get Customer Name
     *
     * @return boolean|string false
     */
    public function getFclCustomerName()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getFirstname();
        }
        return false;
    }

    /**
     * Get Web Cookie Configuration
     *
     * @param string $code
     * @param int $storeId
     *
     * @return int
     */
    public function getWebCookieConfig($code, $storeId = null)
    {
        return $this->getConfigValue('web/cookie/' . $code, $storeId);
    }

    /**
     * Get Login Popup Configuration
     *
     * @param string $code
     * @param int $storeId
     *
     * @return string
     */
    public function getLoginPopupConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FEDEX_SSO . 'login_popup/' . $code, $storeId);
    }

    /**
     * Get Checkout Continue As a Guest Login Modal Configuration
     *
     * @param string $code
     * @param int $storeId
     *
     * @return string
     */
    public function getCheckoutLoginPopupConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FEDEX_SSO . 'checkout_login_popup/' . $code, $storeId);
    }

    /**
     * Get current url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->urlInterface->getCurrentUrl();
    }

    /**
     * Get order success url
     *
     * @return string
     */
    public function getOrderSuccessUrl()
    {
        $orderSuccessUrl =  $this->urlInterface->getUrl('submitorder/index/ordersuccess');

        return rtrim($orderSuccessUrl, "/");
    }

    /**
     * Get home url
     *
     * @return string
     */
    public function getHomeUrl()
    {
        return $this->urlInterface->getBaseUrl();
    }

    /**
     * Get current url path
     *
     * @param string $currentUrl
     * @return string
     */
    public function getCurrentUrlPath($currentUrl)
    {
        try {
            $parseUrl = $this->zendUri->parse($currentUrl);

            $hostName = $this->zendUri->getScheme() . "://" . $parseUrl->getHost();
            $currentPath = str_replace($hostName, "", $currentUrl);
            return $currentPath;
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.':'.'Error in getting host value : '. $e->getMessage());
        }
    }

    /**
     * Get WLGN profile URL
     *
     * @return string
     */
    public function getFclMyProfileUrl()
    {
        return $this->scopeConfig->getValue(self::FCL_MY_PROFILE_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get WLGN profile URL
     *
     * @return string
     */
    public function getcontactinformationprofileurl()
    {
        return $this->scopeConfig->getValue(self::CONTACT_INFORMATION_PROFILE_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get customer billing address by id
     *
     * @param int $customerId
     *
     * @return array|string
     */
    public function getDefaultBillingAddressById($customerId)
    {

        if ($this->accountManagement->getDefaultBillingAddress($customerId)) {
            return $this->accountManagement->getDefaultBillingAddress($customerId)->__toArray();
        } else {
            return 'Customer has not set a default billing address.';
        }
    }

    /**
     * Get cookie value
     *
     * @param string $cookieName
     *
     * @return string
     */
    public function getCustomCookie($cookieName)
    {
        return $this->cookieManagerInterface->getCookie($cookieName);
    }

    /**
     * Delete cookie value
     *
     * @param string $cookieName
     *
     * @return string
     */
    public function deleteCustomCookie($cookieName)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDomain(".fedex.com")
            ->setPath("/");
        return $this->cookieManagerInterface->deleteCookie($cookieName, $metadata);
    }

    /**
     * Get default billing address
     *
     * @return array|string
     */
    public function getDefaultBillingAddress()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($this->accountManagement->getDefaultBillingAddress($customerId)) {
            return $this->accountManagement->getDefaultBillingAddress($customerId)->__toArray();
        } else {
            return __('You have not set a default billing address.');
        }
    }

    /**
     * Get customer default shipping address by id
     *
     * @param int $customerId
     *
     * @return array|string
     */
    public function getDefaultShippingAddressById($customerId)
    {

        if ($this->accountManagement->getDefaultShippingAddress($customerId)) {
            return $this->accountManagement->getDefaultShippingAddress($customerId)->__toArray();
        } else {
            return __('Customer has not set a default shipping address.');
        }
    }

    /**
     * Get default shipping address
     *
     * @return array|string
     */
    public function getDefaultShippingAddress()
    {
        $customerId = $this->customerSession->getCustomerId();
        $defaultShippingAddress = $this->accountManagement->getDefaultShippingAddress($customerId);
        if($this->toggleConfig->getToggleConfigValue('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers')){
            $companyData = $this->companyHelper->getAssignedCompany();
            if(is_object($companyData) && !empty($companyData->getSsoGroup())){
                if ($defaultShippingAddress) {
                    return $defaultShippingAddress->__toArray();
                } else {
                    return __('You have not set a default shipping address.');
                }
            }else{
                return __('');
            }
        }else{
            if ($defaultShippingAddress) {
                return $defaultShippingAddress->__toArray();
            } else {
                return __('You have not set a default shipping address.');
            }
        }
    }

    /**
     * Get Customer Info
     *
     * @return object|false
     */
    public function getFclCustomerInfo()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId) {
            return $this->customerRepository->getById($customerId);
        } else {
            return false;
        }
    }

    /**
     * Display customer login/signup icon for retail & fcl
     *
     * @return boolean true|false
     */
    public function fclCustomerAccountIcon()
    {
        if ($this->customerSession->getCustomerId()
            && !$this->customerSession->getCustomerCompany()
        ) {
            return true;
        } elseif (!$this->customerSession->getCustomerId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check LoginError
     */
    public function getloginErrorCode()
    {
        if ($this->customerSession->getLoginErrorCode()) {

            return $this->customerSession->getLoginErrorCode();
        }

        return false;
    }

    /**
     * Check customer is commercial or not
     *
     * @return boolean true|false
     */
    public function isCommercialCustomer()
    {
        return $this->deliveryHelper->isCommercialCustomer();
    }

    /**
     * Get current store code
     *
     * @return string
     */
    public function getCurrentStoreCode()
    {
        return $this->storeManager->getGroup()->getCode();
    }

    /**
     * Check if current store is SDE
     *
     * @return int
     */
    public function isSdeStore()
    {
        return (int) $this->sdeHelper->getIsSdeStore();
    }

    /**
     * Get SDE logout URL
     *
     * @return string
     */
    public function getSdeLogoutUrl()
    {
        return $this->sdeHelper->getLogoutUrl();
    }

    /**
     * Get SDE logout Idle Timeout
     *
     * @return string
     */
    public function getSdeLogoutIdleTimeOut()
    {
        return $this->getConfigValue(self::XML_PATH_FEDEX_SSO_SESSION_IDLE_TIMEOUT);
    }

    /**
     * To identify the retail store
     *
     * @return boolean
     */
    public function isRetail()
    {
        $isRetail = false;
        if ($this->getCurrentStoreCode() == self::RETAILSTORECODE) {
            $isRetail = true;
        }
        return $isRetail;
    }

    /**
     * @return mixed
     */
    public function getCanvaDesignEnabled(): mixed
    {
        return $this->getConfigValue(self::CANVA_DESIGN_ENABLED);
    }


    /**
     * Get FCL cookie name toggle value
     *
     * @return boolean
     */
    public function getFCLCookieNameToggle()
    {
        return $this->dataHelper->getFCLCookieNameToggle();
    }

    /**
     * Get fcl cookie name config value
     *
     * @return string
     */
    public function getFCLCookieConfigValue()
    {
        return $this->dataHelper->getFCLCookieConfigValue();
    }

    /**
     * Check if SSO login
     * @return boolean
     */
    public function isSSOlogin(): bool
    {
        return $this->dataHelper->isSSOlogin();
    }

    /**
     * Check if customer is an Epro Customer.
     *
     * @return bool
     */
    public function iseprocustomer()
    {
        return $this->deliveryHelper->isEproCustomer();
    }
/**
    * check E-449727 Improving Visibility to Change Password toggle enable or not
    */
    public function isimprovingpasswordtoggle()
    {
        return $this->toggleConfig->getToggleConfigValue('sgc_improving_visibility_to_change_password');
    }

    /**
     * Get register URL for login popup
     *
     * @return string
     */
    public function getRegisterUrl(): string
    {
        $registerAdminURL = $this->getGeneralConfig('register_url');
        $registerAdminQueryParam = $this->getGeneralConfig('register_url_param');

        return $registerAdminURL . $registerAdminQueryParam . '/oauth/index/index/rc/' . base64_encode($this->getCurrentUrl());
    }

    /**
     * Get login URL for popup
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        $wlgnLoginPageUrl = $this->getGeneralConfig('wlgn_login_page_url');
        $queryParameter = $this->getGeneralConfig('query_parameter');
        $redirectUrl = $this->getHomeUrl() . 'oauth/index/index/rc/' . base64_encode($this->getCurrentUrl());

        return $wlgnLoginPageUrl . '?' . $queryParameter . '=' . $redirectUrl;
    }

    /**
     * Return is Toggle D-239988 Enabled
     *
     * @return bool|int
     */
    public function isToggleD239988Enabled(): bool|int
    {
        return $this->toggleConfig->getToggleConfigValue(self::TIGER_D239988);
    }
}
