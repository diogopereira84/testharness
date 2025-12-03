<?php

namespace Fedex\SelfReg\Block;

use Fedex\Delivery\Helper\Data;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\Login\Model\Config as FedexConfig;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class Landing extends \Magento\Framework\View\Element\Template
{

    public $_logo;
    /**
     * @var SelfReg
     */
    protected $SelfReg;

    /**
     * @var Logo
     */
    protected $logo;

    /**
     * @param Context $context
     * @param SelfReg $selfRegHelper
     * @param SsoConfiguration $ssoConfiguration
     * @param UrlInterface $url
     * @param Logo $logo
     * @param Data $helperData
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        public SelfReg $selfRegHelper,
        protected SsoConfiguration $ssoConfiguration,
        protected UrlInterface $url,
        Logo $logo,
        protected StoreManagerInterface $storeManagerInterface,
        public Http $httpRequest,
        public SessionFactory $sessionFactory,
        protected Data $helperData,
        protected ScopeConfigInterface $scopeConfig,
        protected FedexConfig $fedexConfig
    ) {
        $this->_logo = $logo;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     *
     */
    public function getLogoSrc()
    {
        $logoSrc = "";
        $companyLogo = $this->helperData->getCompanyLogo();

        if ($companyLogo) {
            $logoSrc = $companyLogo;
        } else {
            $configValue = $this->scopeConfig->getValue('design/header/logo_src',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeManagerInterface->getStore()->getStoreId());
            if ($configValue) {
                $logoSrc = $this->storeManagerInterface->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .
                    "logo/".$configValue;
            } else {
                $logoSrc = $this->_logo->getLogoSrc();
            }
        }
        return $logoSrc;
    }

    /**
     * Get logo text
     *
     * @return string
     */
    public function getLogoAlt()
    {
        return $this->_logo->getLogoAlt() ?? 'Store Logo';
    }

    /**
     * Get logo width
     *
     * @return int
     */
    public function getLogoWidth()
    {
        return $this->_logo->getLogoWidth();
    }

    /**
     * Get logo height
     *
     * @return int
     */
    public function getLogoHeight()
    {
        return $this->_logo->getLogoHeight();
    }
    /**
     * @return $this
     * @codeCoverageIgnore
     */
    protected function _prepareLayout()
    {
        $fullName = $this->httpRequest->getFullActionName();
        /** B-1805640 */
        if ($fullName == 'oauth_fail_index') {
            $this->pageConfig->getTitle()->set(__('Login Authentication Error'));
        }
        /** B-1805640 */
        else if ($fullName == 'selfreg_login_fail') {
            $this->pageConfig->getTitle()->set(__('Access Denied'));
        } else {
            $this->pageConfig->getTitle()->set(__('Customer Login'));
        }
        return parent::_prepareLayout();
    }

    /**
     * @inheritDoc
     *
     */
    public function getTitle()
    {
        return $this->ssoConfiguration->getLoginPopupConfig('login_popup_message_heading');
    }

    /**
     * @inheritDoc
     *
     */
    public function getDescription()
    {
        return $this->ssoConfiguration->getLoginPopupConfig('login_popup_message');
    }

    /**
     * @inheritDoc
     *
     */
    public function getRegistrationBtnLabel()
    {
        return $this->ssoConfiguration->getLoginPopupConfig('create_user_button_text');
    }

    /**
     * @inheritDoc
     *
     */
    public function getLoginBtnLabel()
    {
        return $this->ssoConfiguration->getLoginPopupConfig('login_button_text');
    }

    /**
     * @inheritDoc
     *
     */
    public function getLoginUrl()
    {
        /** B-1735489 start */
        $loginMockToggle = $this->scopeConfig->getValue('wiremock_service/selfreg_wiremock_group/fcl_login_api_wiremock_enable',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeManagerInterface->getStore()->getStoreId());

        if ($loginMockToggle) {
            return $this->url->getUrl('selfreg/landing/mock');
        } else {
            $redirectUrl = $this->url->getUrl('oauth');
        }

        /** B-1735489 end */
        $wlgnLoginPageUrl = $this->ssoConfiguration->getGeneralConfig('wlgn_login_page_url');
        $queryParameter = $this->ssoConfiguration->getGeneralConfig('query_parameter');

        return $wlgnLoginPageUrl . '?' . $queryParameter . '=' . $redirectUrl;
    }

    /**
     * @inheritDoc
     *
     */
    public function getBaseUrl()
    {
        return $this->url->getBaseUrl();
    }

    /**
     * @inheritDoc
     *
     */
    public function getRegistrationUrl()
    {
        $storeObj = $this->storeManagerInterface->getStore();
        $storeCode = $storeObj->getCode();
        /** B-1735489 start */
        $redirectUrl = $storeCode . '/oauth';
        /** B-1735489 end */
        $wlgnRegisterPageUrl = $this->ssoConfiguration->getGeneralConfig('register_url');
        $queryParameter = $this->ssoConfiguration->getGeneralConfig('register_url_param');

        return $wlgnRegisterPageUrl . $queryParameter . '/' . $redirectUrl;
    }

    /**
     * @inheritDoc
     * B-1326232 - RT-ECVS-Self-reg login error page
     */
    public function getLoginErrorMsg()
    {
        return $this->sessionFactory->create()->getSelfRegLoginError();
    }

    /**
     * getEmailVerificationErrorMsg
     * 
     */
    public function getEmailVerificationErrorMsg()
    {
        return $this->sessionFactory->create()->getEmailVerificationErrorMessage();
    }

    public function getInactiveUserLandingPageLink(): string
    {
        return $this->scopeConfig->getValue($path,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeManagerInterface->getStore()->getStoreId());
    }

    public function getInactiveUserErrorMessage(): string
    {
        return $this->fedexConfig->getInactiveUserErrorMessage();
    }

    public function getAlternateInactiveUserLandingPageLink(): string   
    {
        return $this->fedexConfig->getInactiveUserLandingPageLink(); 
    }

    /**
     * Check if the user is inactive
     *
     * @return bool
     */
    public function isInactiveUser()
    {
        return (bool) $this->sessionFactory->create()->getInactiveUserStatus();
    }

    public function getStoreConfigData($path)
    {
    return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManagerInterface->getStore()->getStoreId());
    }
}

