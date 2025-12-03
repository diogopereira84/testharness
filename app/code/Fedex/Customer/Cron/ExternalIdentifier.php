<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Customer\Cron;

use Fedex\Customer\Helper\Customer as CustomerHelper;
use Magento\Customer\Model\Customer;

class ExternalIdentifier
{
    /**
     * Constructor
     * @param Customer $customer,
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        protected Customer $customer,
        protected CustomerHelper $customerHelper
    )
    {
    }

    /**
     * @return mixed
     */
    public function execute()
    {
            $customerCollection = $this->customer->getCollection()
                ->addFieldToFilter('group_id', ['nin' => ['0', '1', '2', '3']]);
            $customerCollection->getSelect()->where("external_id is null");
            $customerCollection
                ->setPageSize(1000)
                ->setCurPage(1);
            foreach ($customerCollection as $customer) {
                $externalIdentifer = $customer->getData('external_identifier');
                $this->customerHelper->updateExternalIdentifier($externalIdentifer,  $customer->getId());
            }
    }
}