<?php

namespace Fedex\Customer\Plugin\Customer\Adminhtml;

use Magento\Customer\Controller\Adminhtml\Index\Edit;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CustomerEditAfterPlugin
{
   
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerExtensionFactory $customerExtensionFactory,
        private LoggerInterface $logger,
        protected ToggleConfig $toggleConfig
    ) {}

    /**
     * Before plugin for the `execute` method
     *
     * @param \Magento\Customer\Controller\Adminhtml\Index\Edit $subject
     * @param \Magento\Framework\App\RequestInterface $request
     * @return array
     */
    public function beforeExecute(\Magento\Customer\Controller\Adminhtml\Index\Edit $subject)
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator')) {
            $customerId = $subject->getRequest()->getParam('id');
            if ($customerId) {
                try {
                    $customer = $this->customerRepository->getById($customerId);
                    $this->customerRepository->save($customer);
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        }
        return [];
    }
}
