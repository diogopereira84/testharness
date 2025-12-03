<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Helper;

use Exception;
use Fedex\Company\Model\CompanyData;
use Fedex\EnhancedProfile\Helper\Account;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\GroupFactory;
use Fedex\Base\Helper\Auth as AuthHelper;

class SdeHelper extends AbstractHelper
{
    /**
     * SDE store code
     */
    public const SDE_STORE_CODE = 'sde_store';

    /**
     * set SDE cookie period
     */
    public const XML_PATH_FEDEX_SSO_ACTIVE_SESSION_TIMEOUT = 'sso/login_session/active_session_timeout';

    /**
     * set SDE cookie name
     */
    public const CUSTOMER_ACTIVE_SESSION_COOKIE_NAME = 'customerActiveSessionCookie';

    /**
     * set SDE cookie value
     */
    public const CUSTOMER_ACTIVE_SESSION_COOKIE_VALUE = 1;

    /**
     * config path for authentication login SSO
     */
    public const AUTH_ENABLED = 'sso/general/is_enable_auth';

    /**
     * config path for Login method type
     */
    public const LOGIN_METHOD = 'sso/general/is_enable';

    /**
     * config path for SSO Login url
     */
    public const LOGIN_URL = 'sso/general/sso_login_url';

    /**
     * config path for SSO Logout url
     */
    public const LOGOUT_URL = 'sso/general/sso_logout_url';

    /**
     * config key for SDE facing message enable
     */
    public const FACE_MSG_ENABLE = 'sde/general/is_enable';

    /**
     * config key for SDE facing message title
     */
    public const FACE_MSG_TITLE = 'sde/general/sde_msg_title';

    /**
     * config key for SDE facing message text
     */
    public const FACE_MSG_CONTENT = 'sde/general/sde_msg_content';

    /**
     * config key for SDE facing message image
     */
    public const FACE_MSG_IMAGE = 'sde/general/secure_img';

    /**
     * config key for SDE mask image enable
     */
    public const PRODUCT_MASK_IMAGE_ENABLED = 'sde/sde_mask/is_enable';

    /**
     * config key for SDE mask image
     */
    public const PRODUCT_MASK_IMAGE_PATH = 'sde/sde_mask/sde_mask_img';

    /**
     * Configuration path for sde direct signature method
     */
    public const XML_PATH_SDE_DIRECT_SIGNATURE_MESSAGE = 'sde/general/sde_checkout_signature_message';


    /**
     * @var Account
     */
    protected $accountHelper;


    /**
     * Data Class Constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param CustomerSession $customerSession
     * @param CategoryManagementInterface $categoryManagement
     * @param Http $request
     * @param CompanyData $companyData
     * @param GroupFactory $groupFactory
     * @param AuthHelper $authHelper
     */
    public function __construct(
        Context $context,
        protected StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        protected CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        protected CustomerSession $customerSession,
        protected CategoryManagementInterface $categoryManagement,
        protected Http $request,
        protected CompanyData $companyData,
        protected GroupFactory $groupFactory,
        protected AuthHelper $authHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Check if current store is SDE store
     *
     * @return Boolean
     */
    public function getIsSdeStore()
    {
        // B-1515570
            $companyData = $this->customerSession->getOndemandCompanyInfo();
            if ($companyData && is_array($companyData) &&
                !empty($companyData['url_extension']) &&
                !empty($companyData['company_type']) &&
                $companyData['company_type'] == 'sde'
            ) {
                return true;
            }

        if ($this->getStoreGroupCode() === self::SDE_STORE_CODE) {
            return true;
        }

        return false;
    }

    /**
     * Check if current store is SDE store and has FCL on
     * @param int $customerId
     * @return bool
     */
    public function getIsRequestFromSdeStoreFclLogin(int $customerId = 0): bool
    {
        $customerId = $this->customerSession->getId() ?: $customerId;
        if($this->getIsSdeStore() && $customerId) {
            if ($this->authHelper->isLoggedIn()) {
                return $this->authHelper->getCompanyAuthenticationMethod() == AuthHelper::AUTH_FCL;
            }else{
                $companyData = $this->companyData->getByCustomerId($customerId);
                if ($companyData) {
                    $loginMethod = $companyData->getData('storefront_login_method_option');
                    if ($loginMethod === 'commercial_store_wlgn') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if customer SSO method is enabled
     *
     * @return Boolean
     */
    public function isCustomerSsoMethodEnabled()
    {
        if (!$this->isSdeSsoModuleActive()) {
            return false;
        }

        if ($this->toggleConfig->getToggleConfig(self::LOGIN_METHOD) == '2') {
            return true;
        }

        return false;
    }

    /**
     * Get SSO login URL
     *
     * @return string | false
     */
    public function getSsoLoginUrl()
    {
        if (!$this->isSdeSsoModuleActive()) {
            return false;
        }

        return $this->toggleConfig->getToggleConfig(self::LOGIN_URL);
    }

    /**
     * Check if SSO authentication is enabled
     *
     * @return Boolean
     */
    public function isSsoAuthEnabled()
    {
        if ($this->toggleConfig->getToggleConfig(self::AUTH_ENABLED) == '1') {
            return true;
        }

        return false;
    }

    /**
     * Check if SSO module is active
     *
     * @return Boolean
     */
    public function isSdeSsoModuleActive()
    {
        if (!$this->isSsoAuthEnabled() || !$this->getIsSdeStore()) {
            return false;
        }

        return true;
    }

    /**
     * Check if current customer is a SDE customer and is not in SDE store
     *
     * @return boolean
     */
    public function isSdeUserInNoneSdeStore()
    {
        if ($this->authHelper->isLoggedIn() &&
            $this->getCustomerStoreGroupCode() == self::SDE_STORE_CODE &&
            $this->getStoreGroupCode() != self::SDE_STORE_CODE) {
            return true;
        }

        return false;
    }

    /**
     * Get SDE secure image path
     *
     * @return string|boolean
     */
    public function getSdeSecureImagePath()
    {
        $image = $this->toggleConfig->getToggleConfig(self::FACE_MSG_IMAGE);
        $media_url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return $image ? ($media_url . 'sde/' . $image) : '';
    }

    /**
     * Get SDE notice message
     *
     * @return boolean
     */
    public function isFacingMsgEnable()
    {
        if ($this->toggleConfig->getToggleConfig(self::FACE_MSG_ENABLE) == '1') {
            return true;
        }

        return false;
    }

    /**
     * Get SDE notice message title
     *
     * @return string
     */
    public function getSdeSecureTitle()
    {
        return $this->toggleConfig->getToggleConfig(self::FACE_MSG_TITLE);
    }

    /**
     * Get SDE notice message content
     *
     * @return string
     */
    public function getSdeSecureContent()
    {
        return $this->toggleConfig->getToggleConfig(self::FACE_MSG_CONTENT);
    }

    /**
     * Check if SDE product image masking is enabled or not
     *
     * @return boolean
     */
    public function isProductSdeMaskEnable()
    {
        if ($this->getIsSdeStore() === false) {
            return false;
        }

        if ($this->toggleConfig->getToggleConfig(self::PRODUCT_MASK_IMAGE_ENABLED) == '1') {
            return true;
        }

        return false;
    }

    /**
     * Get image path for masking SDE product image
     *
     * @return string|boolean
     */
    public function getSdeMaskSecureImagePath()
    {
        if ($this->isProductSdeMaskEnable() == false) {
            return false;
        }
        $image = $this->toggleConfig->getToggleConfig(self::PRODUCT_MASK_IMAGE_PATH);
        $media_url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return $image ? ($media_url . 'sde/' . $image) : '';
    }

    /**
     * Get SDE category URL
     *
     * @return string|Url
     */
    public function getSdeCategoryUrl()
    {
        $url = $this->storeManager->getStore()->getBaseUrl();
        $storeId = $this->storeManager->getStore()->getStoreId();
        $rootId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $getSubCategory = $this->getCategoryData($rootId);
        foreach ($getSubCategory->getChildrenData() as $category) {
            if(strpos($category->getName(), "Print") !== false){
                $url = $category->getUrl();
            }
        }

        return $url;
    }

    /**
     * Get category data
     *
     * @param int $categoryId
     * @return CategoryTreeInterface|null
     */
    public function getCategoryData(int $categoryId): ?CategoryTreeInterface
    {
        $getSubCategory = null;
        try {
            $getSubCategory = $this->categoryManagement->getTree($categoryId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Category not found ' . $e->getMessage());
        }

        return $getSubCategory;
    }

    /**
     * Check if current page is SDE commercial checkout
     *
     * @return Boolean
     */
    public function sdeCommercialCheckout()
    {
        if ($this->getIsSdeStore()) {
            $module = $this->request->getModuleName();
            $controllerName = $this->request->getControllerName();
            $action = $this->request->getActionName();
            if ($module == 'checkout' && $controllerName == 'index' && $action == 'index') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get direct signature method from configuration
     *
     * @return string
     */
    public function getDirectSignatureMessage()
    {
        return $this->toggleConfig->getToggleConfig(self::XML_PATH_SDE_DIRECT_SIGNATURE_MESSAGE);
    }

    /**
     * Get store group code
     *
     * @param int|null $groupId
     * @return string
     */
    public function getStoreGroupCode($groupId = null)
    {
        return (string) trim(strtolower($this->storeManager->getGroup($groupId)->getCode()));
    }

    /**
     * Get customer store group code
     *
     * @return string
     */
    public function getCustomerStoreGroupCode()
    {
        try {
            $storeId = $this->customerSession->getCustomer()->getStoreId();
            $groupId = $this->storeManager->getStore($storeId)->getGroupId();

            return $this->getStoreGroupCode($groupId);
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Get SDE logout url
     *
     * @return string
     */
    public function getLogoutUrl()
    {
        $logoutUrl = trim((string)$this->toggleConfig->getToggleConfig(self::LOGOUT_URL));
        $storeObj = $this->storeManager->getStore();
        $storeCode = $storeObj->getCode();
        $this->getIsSdeStore();
        if ($this->getIsSdeStore() && $storeCode == 'ondemand') {
            $sdeStoreCode = 'sde_store';
            $sdeGroupObj = $this->groupFactory->create()->load($sdeStoreCode, 'code');
            $sdeStoreIds = $sdeGroupObj->getStoreIds();
            $sdeStoreId = reset($sdeStoreIds);
            $logoutUrl = trim((string)$this->getConfigValue(self::LOGOUT_URL, $sdeStoreId));
        }
        return $logoutUrl;
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
     * Set Custom Cookie
     *
     * @return Cookie
     */
    public function setCustomerActiveSessionCookie()
    {
        $customCookieTime = $this->getConfigValue(self::XML_PATH_FEDEX_SSO_ACTIVE_SESSION_TIMEOUT);
        $sdeCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $sdeCookieMetadata->setDuration($customCookieTime);
        $sdeCookieMetadata->setPath('/');
        $sdeCookieMetadata->setHttpOnly(false);

        return $this->cookieManager->setPublicCookie(
            self::CUSTOMER_ACTIVE_SESSION_COOKIE_NAME,
            self::CUSTOMER_ACTIVE_SESSION_COOKIE_VALUE,
            $sdeCookieMetadata
        );
    }

    /**
     * Get Customer Store url
     *
     * @param int $customerGroupId
     * @return string
     */
    public function getCustomerStoreUrl($customerGroupId)
    {
        $storeId = $this->getCustomerStoreIdByCustomerGroup($customerGroupId);
        $customerStore = $this->storeManager->getStore($storeId);

        return $customerStore->getBaseUrl();
    }

    /**
     * Get customer store view id using customer group
     *
     * @param int $customerGroupId
     * @return void
     */
    public function getCustomerStoreIdByCustomerGroup($customerGroupId)
    {
        $storeId = $this->companyData->getStoreViewIdByCustomerGroup($customerGroupId);
        if ($storeId == null || $storeId == 0) {
            $storeId = $this->customerSession->getCustomer()->getStoreId();
        }
        return $storeId;
    }

    /**
     * Checks request is marketplace product
     *
     * @return bool
     */
    public function isMarketplaceProduct()
    {
        if ($this->request->getParam('isMarketplaceProduct') == null) {
            return false;
        }
        return (bool) $this->request->getParam('isMarketplaceProduct');
    }

    /**
     * Get Base Url
     *
     * @return String
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }
}
