<?php
/**
 * @category Fedex
 * @package  Fedex_SSO
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Model;

use Fedex\SSO\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    /**
     * is_enable xml path
     */
    public const XML_PATH_FEDEX_SSO_IS_ENABLED = 'sso/general/is_enable';

    /**
     * Login page URL xml path
     */
    public const XML_PATH_FEDEX_SSO_LOGIN_PAGE_URL = 'sso/general/wlgn_login_page_url';

    /**
     * Query parameter xml path
     */
    public const XML_PATH_FEDEX_SSO_QUERY_PARAMETER = 'sso/general/query_parameter';

    /**
     * Register URL xml path
     */
    public const XML_PATH_FEDEX_SSO_REGISTER_URL = 'sso/general/register_url';

    /**
     * Register URL parameter xml path
     */
    public const XML_PATH_FEDEX_SSO_REGISTER_URL_PARAM = 'sso/general/register_url_param';

    /**
     * Profile api url xml path
     */
    public const XML_PATH_FEDEX_SSO_PROFILE_API_URL = 'sso/general/profile_api_url';

    /**
     * FCL my profile xml path
     */
    public const XML_PATH_FEDEX_SSO_FCL_MY_PROFILE_URL = 'sso/general/fcl_my_profile_url';


    /**
     * Contact information xml path
     */
    public const XML_PATH_FEDEX_SSO_CONTACT_INFORMATION_PROFILE_URL = 'sso/general/contact_information_profile_url';


    /**
     * FCL logout url xml path
     */
    public const XML_PATH_FEDEX_SSO_FCL_LOGOUT_URL = 'sso/general/fcl_logout_url';

    /**
     * FCL logout query param xml path
     */
    public const XML_PATH_FEDEX_SSO_FCL_LOGOUT_QUERY_PARAM = 'sso/general/fcl_logout_query_param';

    /**
     * Profile mockup json xml path
     */
    public const XML_PATH_FEDEX_SSO_PROFILE_MOCKUP_JSON = 'sso/general/profile_mockup_json';
    /**
     * FCL New logout url xml path
     */
    public const XML_PATH_FEDEX_SSO_FCL_LOGOUT_API_URL = 'sso/general/fcl_logout_api';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }
    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FEDEX_SSO_IS_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getLoginPageURL(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_LOGIN_PAGE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getQueryParameter(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_QUERY_PARAMETER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getRegisterUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_REGISTER_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getRegisterUrlParameter(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_REGISTER_URL_PARAM,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getProfileApiUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_PROFILE_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getFclMyProfileUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_FCL_MY_PROFILE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getcontactinformationprofileurl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_CONTACT_INFORMATION_PROFILE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * @inheritDoc
     */
    public function getFclLogoutUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_FCL_LOGOUT_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getFclLogoutQueryParam(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_FCL_LOGOUT_QUERY_PARAM,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getProfileMockupJson(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_PROFILE_MOCKUP_JSON,
            ScopeInterface::SCOPE_STORE
        );
    }
            /**
     * @inheritDoc
     */
    public function getFclLogoutApiUrl(): string
    {
         return $this->scopeConfig->getValue(
            self::XML_PATH_FEDEX_SSO_FCL_LOGOUT_API_URL,
            ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * @inheritDoc
     */
    public function isWireMockLoginEnable(): bool
    {
         return (bool) $this->scopeConfig->getValue(
            'wiremock_service/selfreg_wiremock_group/fcl_login_api_wiremock_enable',
            ScopeInterface::SCOPE_STORE
        );
    }

     /**
     * @inheritDoc
     */
    public function getWireMockProfileUrl()
    {
         return (string) $this->scopeConfig->getValue(
            'wiremock_service/selfreg_wiremock_group/fcl_login_api_wiremock_url',
            ScopeInterface::SCOPE_STORE
        );
    }
}
