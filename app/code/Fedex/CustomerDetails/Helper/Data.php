<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerDetails\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Helper\Context;

/**
 * Data helper for getting looged in customer details
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private SessionFactory $customerSession;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param SessionFactory $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        SessionFactory $customerSession,
        protected \Magento\Framework\App\Http\Context $httpContext,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
    }
    /**
     * Get if customer is logged in
     * @deprecated use \Fedex\Base\Helper\Auth::isLoggedIn() instead
     * @return boolean true|false
     */
    public function isLoggedIn()
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * Get Customer Company details
     *
     * @return string
     */
    public function getLoggedinCustomerDetails()
    {
        $customer = $this->customerSession->create();
        return "Hello " . $customer->getCompanyName() . ",";
    }

    /**
     * Get Toggle Config Valye for FCL feature
     *
     * @param string $key
     * @return string
     */
    public function getToggleConfig($key)
    {
        return $this->toggleConfig->getToggleConfigValue($key);
    }
}
