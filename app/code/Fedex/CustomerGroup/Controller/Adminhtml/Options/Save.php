<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Controller\Adminhtml\Options;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Json\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var Context
     */
    protected $context;
    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param CustomerRepository $customerRepository
     * @param CustomerFactory $customerFactory
     * @param Customer $customer
     * @param Data $jsonHelper
     * @param LoggerInterface $logger
     * @param CompanyManagementInterface $companyRepository
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        protected CustomerRepository $customerRepository,
        protected CustomerFactory $customerFactory,
        protected Customer $customer,
        protected Data $jsonHelper,
        protected LoggerInterface $logger,
        protected CompanyManagementInterface $companyRepository
    ) {
        $this->resultFactory = $resultFactory;
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
    }

    /**
     * Execute Method
     *
     * @return Http
     *
     */
    public function execute()
    {
        try {
            $customersUpdated = 0;
            $customerGroupId = $this->getRequest()->getParam('group');
            $customerIds = $this->getRequest()->getParam('selectedIds');
            foreach ($customerIds as $customerId) {
                $this->updateCustomerAttribute($customerId, $customerGroupId);
                $customersUpdated++;
            }
            if ($customersUpdated) {
                $this->messageManager->addSuccessMessage(__('A total of %1 record(s) updated.', $customersUpdated));
            }
            $result = ['success' => true, 'redirect' => $this->getUrl('customer/index/index')];
            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * UpdateCustomerAttribute Method
     *
     * @param int $customerId
     * @param int $customerGroupId
     *
     * @return void
     */
    public function updateCustomerAttribute($customerId, $customerGroupId)
    {
            $customerData = $this->customerRepository->getById($customerId);
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($customerData->getWebsiteId())->loadByEmail($customerData->getEmail());
            $customer->setGroupId($customerGroupId);
            try {
                $this->customer->save($customer);
            } catch (\Exception $e) {
                $message = ' Error while saving Customer group for the customer id: ';
                $this->logger->critical(__METHOD__ . ':' . __LINE__.$message.$customerId.' is: ' . $e->getMessage());
            }
    }
    /**
     * Compile JSON response
     *
     * @param array $data
     * @return Http
     */
    protected function jsonResponse($data)
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($data)
        );
    }
}
