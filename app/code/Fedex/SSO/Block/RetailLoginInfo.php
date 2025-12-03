<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Block;

use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\View\Element\Template\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Cart\ViewModel\UnfinishedProjectNotification;

/**
 * RetailLoginInfo Block class
 */
class RetailLoginInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $template = 'Fedex_SSO::header/singin_signup.phtml';

    /**
     * Customer Login constructor
     *
     * @param Context $context
     * @param SsoConfiguration $ssoConfiguration
     * @param ToggleConfig $toggleConfig
     * @param UnfinishedProjectNotification $myProjectManager
     */
    public function __construct(
        Context $context,
        protected SsoConfiguration $ssoConfiguration,
        protected ToggleConfig $toggleConfig,
        protected UnfinishedProjectNotification $myProjectManager
    ) {
        parent::__construct($context);
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->ssoConfiguration->isFclCustomer()) {
            $this->template = 'Fedex_SSO::header/singin_signup.phtml';
        } else {
            $this->template = 'Fedex_SSO::header/login_info.phtml';
        }

        return $this->template;
    }

    /**
     * Get Customer Name
     *
     * @return boolean|string false
     */
    public function getFclCustomerName()
    {
        return $this->ssoConfiguration->getFclCustomerName();
    }

    /**
     * Get is Fcl Customer
     *
     * @return boolean|string false
     */
    public function isFclCustomer()
    {
        return $this->ssoConfiguration->isFclCustomer();
    }

    /**
     * Get is CommercialCustomer
     *
     * @return boolean|string false
     */
    public function isCommercialCustomer()
    {
        return $this->ssoConfiguration->isCommercialCustomer();
    }

    /**
     * Get General Configuration Value
     *
     * @param string $code
     *
     * @return string
     */
    public function getGeneralConfig($code)
    {
        return $this->ssoConfiguration->getGeneralConfig($code);
    }

    /**
     * Get default shipping address
     *
     * @return array|string
     */
    public function getDefaultShippingAddress()
    {
        return $this->ssoConfiguration->getDefaultShippingAddress();
    }

    /**
     * Get Customer Info
     *
     * @return object|false
     */
    public function getFclCustomerInfo()
    {
        return $this->ssoConfiguration->getFclCustomerInfo();
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
        return $this->ssoConfiguration->getConfigValue('web/cookie/' . $code, $storeId);
    }

    /**
     * @return mixed
     */
    public function getCanvaDesignEnabled()
    {
        return $this->ssoConfiguration->getCanvaDesignEnabled();
    }

    /**
     * Get Batch Upload Toggle
     */
    public function isMyProjectsEnable(): bool|int
    {
        return $this->myProjectManager->isCartPageUnfinisedPopupEnable();
    }

    /**
     * Check if view my project link to be enable for logged in users
     * @return bool
     */
    public function isMyProjectAvailable(): bool
    {
        return $this->myProjectManager->isProjectAvailable();
    }

    public function getTigerMyAccountUiNavigationConsistency(): bool
    {
        return !$this->isCommercialCustomer();
    }

    /**
     * Personal Address Book Toggle
     *
     * @return bool
     */
    public function isPersonalAddressBookToggleEnable()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book');
    }

    /**
     * Toggle Tiger Team - B-2260777 Access to Workspace
     *
     * @return bool
     */
    public function isAccessToWorkspaceToggleEnable(): bool
    {
        return $this->myProjectManager->isAccessToWorkspaceToggleEnable();
    }

    /**
     * Get Workspace Url
     *
     * @return string
     */
    public function getWorkspaceUrl(){
        return $this->myProjectManager->getWorkspaceUrl();
    }
}
