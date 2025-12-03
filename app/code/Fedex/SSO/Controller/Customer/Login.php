<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Customer;

use Fedex\SSO\Model\Login as LoginModal;

/**
 * CustomerLoginInfo Block class
 */
class Login extends \Magento\Framework\App\Action\Action
{
    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param LoginModal $login
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        protected LoginModal $login
    ) {
        parent::__construct($context);
    }

    /**
     * Login post action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        return $this->login->isCustomerLoggedIn();
    }
}
