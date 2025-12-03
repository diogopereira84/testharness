<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ActionInterface;



class GetCustomer implements ActionInterface
{

    /**
     * @param ResultFactory $resultFactory
     * @param JsonFactory $resultJsonFactory
     * @param Customer $customer
     * @param ResourceConnection $resource
     */
    public function __construct(
        protected JsonFactory $resultJsonFactory,
        protected Customer $customer,
        protected ResourceConnection $resource,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Find Company user by name.
     */
    public function execute()
    {
        $postData = $this->request->getPost();
        $resultJsonData = $this->resultJsonFactory->create();
        $response = [];

        if (isset($postData['site_id']) && $postData['site_id']) {
            $companyId = $postData['site_id'];
            $connection = $this->resource->getConnection();
            $companyAdvancedTable = $connection->getTableName('company_advanced_customer_entity');
            $customerCollection = $this->customer->getCollection();
            $customerCollection->addAttributeToSelect("*");
            $customerCollection->getSelect()->join($companyAdvancedTable . ' as ad_customer', 'e.entity_id = ad_customer.customer_id AND ad_customer.company_id = ' . $companyId, array('*'));
            foreach ($customerCollection as $customer) {
                $response[] = [
                    'value'   => $customer->getId(),
                    'label' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                ];
            }
        }
        return $resultJsonData->setData(['users' => $response]);
    }
}
