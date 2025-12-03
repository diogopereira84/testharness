<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Controller\Users;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Framework\Controller\ResultFactory;



/**
 * Class BulkDelete
 * Handle the bulk delete product and category of the CatalogMvp
 */
class BulkDelete extends Action
{
     /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';
    private \Magento\Framework\Message\ManagerInterface $_messageManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Company\Model\Company\Structure $structureManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        private \Magento\Company\Model\CompanyContext $companyContext,
        private LoggerInterface $logger,
        private \Magento\Company\Model\Company\Structure $structureManager,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private JsonFactory $resultJsonFactory,
        private Registry $registry,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_messageManager = $messageManager;
        parent::__construct($context);

    }

    /**
     * Delete team action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();
        $params=$request->getParams();
        $customerids=explode(',',$params['postdata']);
        $this->registry->register('isSecureArea', true);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultJsonData = $this->resultJsonFactory->create();
        foreach($customerids as $customerId){
        if ($customerId != $this->companyContext->getCustomerId()) {
            try {
                $this->customerRepository->deleteById($customerId);
            }   catch (\Exception $e) {
                $this->logger->critical($e);

                $response = $resultJsonData->setData(['status' => 'ok', 'message' => "You can not delete yourself."]);
                return $response;
            }
        }
        else
        {
            $response = $resultJsonData->setData(['status' => 'ok', 'type'=>'error','message' => "You can not delete yourself."]);
            return $response;
        }
    }
    $response = $resultJsonData->setData(['status' => 'ok', 'type'=>'success','message' => (__("%1 customers were successfully deleted.",count($customerids)))]);
            return $response;

    }
}

