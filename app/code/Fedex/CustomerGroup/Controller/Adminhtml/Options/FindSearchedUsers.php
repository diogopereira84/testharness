<?php
/**
 * Copyright ©FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CustomerGroup\Controller\Adminhtml\Options;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Exception;

class FindSearchedUsers extends \Magento\Backend\App\Action
{
    public const RESPONSE_STATUS = 'status';
    public const RESPONSE_MESSAGE = 'message';

    /**
     * FindSearchedUsers constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Http $request
     * @param LoggerInterface $logger
     * @param CustomerRepository $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        private Http $request,
        private LoggerInterface $logger,
        private CustomerRepository $customerRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private CustomerFactory $customerFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Find selected users based on user input
     */
    public function execute(): Json
    {
        $resultJsonData = $this->resultJsonFactory->create();
        $requestData = $this->request->getParams();
        
        try {
            if (empty($requestData)) {
                return $this->createErrorResponse(
                    $resultJsonData,
                    'Error finding searched users for Assign Permissions Modal.'
                );
            }

            $filterValue = $requestData['filterValue'] ?? null;
            $excludeUserIds = $requestData['excludedUserIds'] ?? null;
            if (!$filterValue) {
                return $this->createErrorResponse(
                    $resultJsonData,
                    'Error finding searched users for Assign Permissions Modal.'
                );
            }
            $customerCollection = $this->customerFactory->create()->getCollection();
            if ($excludeUserIds) {
                $customerCollection->addFieldToFilter('entity_id', ['nin' => $excludeUserIds]);
            }

            $customerCollection->addAttributeToFilter([
                ['attribute' => 'firstname', 'like' => '%' . $filterValue . '%'],
                ['attribute' => 'lastname', 'like' => '%' . $filterValue . '%']
            ]);
            $filteredCustomers = $customerCollection->getItems();
            $html = '';
            if ($filteredCustomers) {
                $html = "<ul>";
                foreach ($filteredCustomers as $customer) {
                    $html .= '<li data-index-id="' . $customer->getId() . '">' . $customer->getName() . '</li>';
                }
                $html .= "</ul>";
            }
            $response = $resultJsonData->setData(['status' => 'success', 'html' => $html]);
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Error finding searched users for Assign Permissions Modal: ' .
                $e->getMessage()
            );
            return $this->createErrorResponse($resultJsonData, $e->getMessage());
        }

        return $response;
    }

    /**
     * Create an error response
     *
     * @param Json $resultJsonData
     * @param string $message
     *
     * @return Json
     */
    private function createErrorResponse(Json $resultJsonData, string $message): Json
    {
        return $resultJsonData->setData([
            self::RESPONSE_STATUS => 'error',
            self::RESPONSE_MESSAGE => $message,
        ]);
    }
}
