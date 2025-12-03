<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Block;

use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\View\Element\Template\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * LoginInfo Block class
 */
class LoginInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * Customer Login constructor
     *
     * @param Context $context
     * @param SsoConfiguration $ssoConfiguration
     */
    public function __construct(
        Context $context,
        protected SsoConfiguration $ssoConfiguration,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
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
     * Get Tiger My Account UI Navigation Consistency
     *
     * @return bool
     */
    public function getTigerMyAccountUiNavigationConsistency()
    {
        return !$this->isCommercialCustomer();
    }
}
