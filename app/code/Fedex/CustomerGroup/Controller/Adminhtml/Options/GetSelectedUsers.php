<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CustomerGroup\Controller\Adminhtml\Options;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Psr\Log\LoggerInterface;
use Exception;

class GetSelectedUsers extends \Magento\Backend\App\Action
{
    /**
     * GetSelectedUsers constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Http $request
     * @param LoggerInterface $logger
     * @param CustomerRepository $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        private Http $request,
        private LoggerInterface $logger,
        private CustomerRepository $customerRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
    }

    /**
     * Get selected users from user ids
     */
    public function execute()
    {
        $resultJsonData = $this->resultJsonFactory->create();
        $requestData = $this->request->getParams();
        try {
            if ($requestData) {
                $selectedUserIds  = $requestData['selectedIds'] ?? null;
                if ($selectedUserIds) {
                    $selectedCustomers = $this->getSelectedCustomersNames($selectedUserIds);
                    $selectedUsersHtml = '';
                    $addUsersHtml = '';
                    foreach ($selectedCustomers as $customerId => $customerName) {
                        $selectedUsersHtml .=
                            '<span class="admin__action-multiselect-crumb selected-user-assign-permissions"><span>' .
                            $customerName . '</span><button class="action-close remove-selected-user" type="button"
                            data-index-id="' . $customerId . '"><span class="action-close-text">Close</span></button>
                            </span>';
                    }
                    $addUsersHtml .=
                        '<div class="assign-permissions-search"><input type="text" id="ap_search_area" name="tag"
                            class="ap-search-area" placeholder="Add User"></div>
                            <div class="ap-search-dropdown-wrapper" style="display: none;">
                            <div class="ap-search-dropdown"></div></div>';
                    $response = $resultJsonData->setData(
                        [
                            'status' => 'success',
                            'selectedUsersHtml' => $selectedUsersHtml,
                            'addUsersHtml' => $addUsersHtml
                        ]
                    );
                } else {
                    $response =$resultJsonData->setData(
                        [
                            'status' => 'error',
                            'message' => 'Error getting selected user ids for Assign Permissions Modal.'
                        ]
                    );
                }
            } else {
                $response = $resultJsonData->setData(
                    [
                        'status' => 'error',
                        'message' => 'Error getting selected user ids for Assign Permissions Modal.'
                    ]
                );
            }
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Error getting selected user ids for Assign Permissions Modal: ' .
                    $e->getMessage()
            );
            $response = $resultJsonData->setData(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return $response;
    }

    /**
     * Get selected user names from user ids
     *
     * @param array $selectedUserIds
     *
     * @return array
     */
    public function getSelectedCustomersNames(array $selectedUserIds): array
    {
        $selectedCustomers = [];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $selectedUserIds, 'in')
            ->create();
        $selectedCustomersList = $this->customerRepository->getList($searchCriteria)->getItems();
        foreach ($selectedCustomersList as $selectedCustomer) {
            $selectedCustomers[$selectedCustomer->getId()] =
                $selectedCustomer->getFirstname() . ' ' . $selectedCustomer->getLastname();
        }

        return $selectedCustomers;
    }
}
