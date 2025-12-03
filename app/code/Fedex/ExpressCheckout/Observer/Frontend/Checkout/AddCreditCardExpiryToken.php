<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

namespace Fedex\ExpressCheckout\Observer\Frontend\Checkout;

use Fedex\EnhancedProfile\Helper\Account;
use Fedex\ExpressCheckout\ViewModel\ExpressCheckout;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AddCreditCardExpiryToken
 *
 * This class is responsible for Adding Credit Card Expiry Token in Json
 */
class AddCreditCardExpiryToken implements ObserverInterface
{
    /**
     * @var ExpressCheckout
     */
    protected $expressCheckout;

    /**
     * @var Account
     */
    protected $accountHelper;

    /**
     * Constructor function
     *
     * @param ExpressCheckout $expressCheckout
     * @param Account $accountHelper
     */
    public function __construct(
        ExpressCheckout $expressCheckout,
        Account $accountHelper
    ) {
        $this->expressCheckout = $expressCheckout;
        $this->accountHelper = $accountHelper;
    }

    /**
     * Execute Method
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->expressCheckout->getIsFclCustomer()
            || ($this->accountHelper->getIsSelfRegStore() && $this->accountHelper->getCompanyLoginType() == 'FCL')) {
            $this->expressCheckout->getCustomerProfileSessionWithExpiryToken();
        }
    }
}
