<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;

/**
 * Controller for deleting a customer from the frontend.
 */
class Delete extends \Magento\Company\Controller\Customer\Delete implements HttpPostActionInterface
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structureManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Company\Model\Company\Structure $structureManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Company\Model\Company\Structure $structureManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context, $companyContext, $logger,$structureManager,$customerRepository);
        $this->structureManager = $structureManager;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Delete team action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();
        $customerId = $request->getParam('customer_id');
        try {
            if ($customerId == $this->companyContext->getCustomerId()) {
                $response = $this->jsonError(__('You cannot delete yourself.'));
            } else {
                $customer = $this->customerRepository->getById($customerId);
                /** @var CompanyCustomerInterface $companyAttributes */
                $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
                $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_INACTIVE);
                $customer->setCustomAttribute('customer_status', CompanyCustomerInterface::STATUS_INACTIVE);
                $this->customerRepository->save($customer);
                $response = $this->handleJsonSuccess(
                    __(
                        "%1's account has been set to Inactive.",
                        $customer->getFirstname() . ' ' . $customer->getLastname()
                    )
                );

            }
            return $response;
            
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->handleJsonError();
        }
    }
}
