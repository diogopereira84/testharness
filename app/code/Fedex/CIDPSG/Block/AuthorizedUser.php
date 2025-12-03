<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Block;

use Magento\Framework\View\Element\Template\Context;
use Fedex\CIDPSG\Helper\AdminConfigHelper;
use Magento\Framework\View\Element\Template;

/**
 * AuthorizedUser Block class
 */

class AuthorizedUser extends Template
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param AdminConfigHelper $adminConfigHelper

     */
    public function __construct(
        Context $context,
        protected AdminConfigHelper $adminConfigHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Get the list of regions present in the given Country
     *
     * @param string $countryCode
     * @return array
     */
    public function getAllStates($countryCode)
    {
        return $this->adminConfigHelper->getAllStates($countryCode);
    }

    /**
     * To get account request form terms and condition text.
     *
     * @return string
     */
    public function getConfirmationPopupMessage()
    {
        return $this->adminConfigHelper->getConfirmationPopupMessage();
    }
}
