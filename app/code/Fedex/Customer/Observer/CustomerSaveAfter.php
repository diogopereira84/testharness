<?php
namespace Fedex\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Fedex\Customer\Helper\Customer;
use Magento\Framework\App\Request\Http;

class CustomerSaveAfter implements ObserverInterface
{
    /**
     * @param Http $request
     * @param Customer $customerHelper
     */
    public function __construct(
        protected Http $request,
        protected Customer $customerHelper
    )
    {
    }
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
            $postValue = $this->request->getParams();
            if(isset($postValue['customer']['external_identifier'])) {
                $externalIdentifierValue = $postValue['customer']['external_identifier'];
                $customer = $observer->getCustomer();
                $this->customerHelper->updateExternalIdentifier($externalIdentifierValue, $customer->getId());
            }
        return $this;
    }
}