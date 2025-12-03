<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Block;

use Fedex\SDE\ViewModel\SdeSsoConfiguration;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\View\Element\Template\Context;

/**
 * LoginInfo Block class
 */
class LoginInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * Customer Login constructor
     *
     * @param Context $context
     * @param SdeSsoConfiguration $sdeSsoConfiguration
     * @param SdeHelper $sdeHelper
     */
    public function __construct(
        Context $context,
        protected SdeSsoConfiguration $sdeSsoConfiguration,
        protected SdeHelper $sdeHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Get Customer Name
     *
     * @return boolean|string false
     */
    public function getSdeCustomerName()
    {
        return $this->sdeSsoConfiguration->getSdeCustomerName();
    }

    /**
     * Get is Sde Customer
     *
     * @return boolean|string false
     */
    public function isSdeCustomer()
    {
        return $this->sdeSsoConfiguration->isSdeCustomer();
    }
}
