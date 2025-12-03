<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Users;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Company\Model\Company\Structure;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\RequestInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class FindUsers extends Action
{
    public const TECH_TITANS_TK_4517938 = 'tech_titans_TK_4517938';

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param Structure $structure
     * @param Session $session
     * @param ResultFactory $resultFactory
     * @param JsonFactory $resultJsonFactory
     * @param Customer $customer
     * @param CompanyRepositoryInterface $companyRepositoryInterface
     * @param ResourceConnection $resource
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        protected Structure $structure,
        protected Session $session,
        ResultFactory $resultFactory,
        protected JsonFactory $resultJsonFactory,
        protected Customer $customer,
        protected ResourceConnection $resource,
        private CompanyRepositoryInterface $companyRepositoryInterface,
        protected RequestInterface $request,
        protected ToggleConfig $toggleConfig
    ) {
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
    }

    /**
     * Find COmpany user by name.
     */
    public function execute()
    {
        $companyData = $this->session->getOndemandCompanyInfo();
        $companyId = "";
        $resultJsonData = $this->resultJsonFactory->create();
        $postData = $this->request->getPost();
        if (isset($companyData['company_id']) && $companyData['company_id'] > 0 && isset($postData['filter']) && isset($postData['exclude_user'])) {
            $company = $this->companyRepositoryInterface->get($companyData['company_id']);
            $companySuperUserId = $company->getSuperUserId();
            $filter = $postData['filter'];
            $excludedIds = $postData['exclude_user'];
            $excludedIds = explode(",", $excludedIds);
            $companyId = $companyData['company_id'];
            $connection = $this->resource->getConnection();
            $companyAdvancedTable = $connection->getTableName('company_advanced_customer_entity');
            $customerCollection = $this->customer->getCollection();
            $customerCollection->addAttributeToSelect("*");
            if (!empty($excludedIds)) {
                $customerCollection->addFieldToFilter('entity_id', ['nin' => $excludedIds]);
            }
            if ($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_TK_4517938)) {
                // Create a virtual attribute 'fullname'
                $customerCollection->addExpressionAttributeToSelect(
                    'fullname',
                    "CONCAT({{firstname}}, ' ', {{lastname}})",
                    ['firstname', 'lastname']
                );
                // Apply filter on fullname
               $customerCollection->addAttributeToFilter(
                    'fullname',
                    ['like' => '%' . $filter . '%']
                );
            } else {
            $customerCollection->addAttributeToFilter([
                ['attribute' => 'firstname', 'like' => '%' . $filter . '%'],
                ['attribute' => 'lastname', 'like' => '%' . $filter . '%']
            ]);
        }
            $customerCollection->getSelect()->join($companyAdvancedTable . ' as ad_customer', 'e.entity_id = ad_customer.customer_id AND ad_customer.company_id = ' . $companyId, array('*'));
            $html = "<ul>";
            $counter = 0;
            foreach ($customerCollection as $customer) {
                if ($postData['includeCustomerAdmin']) {
                    $html .= '<li data-index-id="' . $customer->getId() . '">' . $customer->getName() . '</li>';
                    $counter++;
                } else {
                    if($customer->getId() != $companySuperUserId) {
                        $html .= '<li data-index-id="' . $customer->getId() . '">' . $customer->getName() . '</li>';
                        $counter++;
                    }
                }
            }
            $html .= "</ul>";
            $response = $resultJsonData->setData(['status' => 'success', 'html' => $html, 'counter' => $counter]);
            return $response;
        }
        $response = $resultJsonData->setData(['status' => 'error', 'html' => '']);
        return $response;
    }
}
