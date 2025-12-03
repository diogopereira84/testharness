<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\ViewModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * SdeSsoConfiguration ViewModel class
 */
class SdeSsoConfiguration implements ArgumentInterface
{
    /**
     * @var SessionFactory $_customerSession
     */
    protected $_customerSession;

    /**
     * @var CustomerRepositoryInterface $customerRepository
     */
    protected $CustomerRepositoryInterface;

    /**
     * SsoConfiguration constructor.
     *
     * @param SessionFactory $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param Http $request
     * @return void
     */
    public function __construct(
        SessionFactory $customerSession,
        private CustomerRepositoryInterface $customerRepository,
        protected Http $request
    ) {
        $this->_customerSession = $customerSession;
    }

    /**
     * Get name of SDE customer
     *
     * @return string
     */
    public function getSdeCustomerName()
    {
        if ($customerId = $this->customerSession()->getId()) {
            $customer = $this->customerRepository->getById($customerId);

            return $customer->getFirstname();
        }

        return '';
    }

    /**
     * Checks if the customer is a SDE customer
     *
     * @return boolean true|false
     */
    public function isSdeCustomer()
    {
        $customerSession = $this->customerSession();
        if ($customerSession->getId() && !$customerSession->getCustomerCompany()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get customer session
     *
     * @return CustomerSession
     */
    public function customerSession()
    {
        return $this->_customerSession->create();
    }
}
