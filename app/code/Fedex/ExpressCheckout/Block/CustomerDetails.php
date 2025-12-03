<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ExpressCheckout\Block;

use Magento\Framework\View\Element\Template\Context;
use Fedex\ExpressCheckout\ViewModel\ExpressCheckout;

/**
 * CustomerDetails Block class
 */
class CustomerDetails extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ExpressCheckout $expressCheckout
     */
    protected $expressCheckout;

    /**
     * Initialize dependencies
     *
     * @param Context $context
     * @param ExpressCheckout $expressCheckout
     */
    public function __construct(
        Context $context,
        ExpressCheckout $expressCheckout
    ) {
        $this->expressCheckout = $expressCheckout;
        parent::__construct($context);
    }

    /**
     * Get Loggedin profile Info
     *
     * @return array
     */
    public function getCustomerProfileSession()
    {
        $profileInfo = $this->expressCheckout->getCustomerProfileSession();
        if (!isset($profileInfo->output->profile->accounts[0]->accountValid)) {
            $profileInfo = $this->expressCheckout->getCustomerProfileSessionWithExpiryToken();
        }

        return $profileInfo;
    }
}
