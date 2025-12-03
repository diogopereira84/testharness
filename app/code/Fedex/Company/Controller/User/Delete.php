<?php

/**
 * Copyright © FedEx  All rights reserved.
 * See COPYING.txt for license details.
 * @author Adithya Adithya <5174169@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Company\Controller\User;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Api\GroupRepositoryInterface;
use Fedex\CustomerGroup\Model\FolderPermission;

class Delete implements HttpPostActionInterface
{
    /**
     * Manage user groups Constructor
     *
     * @param JsonFactory $resultJsonFactory
     * @param UserGroupsRepositoryInterface $userGroupsRepository
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param CustomerFactory $customerFactory
     * @param Customer $customer
     * @param GroupRepositoryInterface $groupRepository
     * @param FolderPermission $folderPermission
     */
    public function __construct(
        private JsonFactory $resultJsonFactory,
        private UserGroupsRepositoryInterface $userGroupsRepository,
        private LoggerInterface $logger,
        private RequestInterface $request,
        private CustomerFactory $customerFactory,
        private Customer $customer,
        private GroupRepositoryInterface $groupRepository,
        private FolderPermission $folderPermission
    ) {
    }

    /**
     * Execute function
     *
     * @return Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $postData = $this->request->getParams() ?? [];
        try {
            if (isset($postData['groupId']) && !empty($postData['groupId'])) {
                $idParts = explode("-", $postData['groupId']);
                $groupType = $idParts[0] ?? "";
                $groupId = $idParts[1] ?? "";

                if ($groupType == 'user_groups') {
                    $this->userGroupsRepository->deleteById($groupId);
                } elseif ($groupType == 'customer_group') {
                    $parentGroupId = $this->folderPermission->getParentGroupId($groupId);
                    $customerCollection = $this->customerFactory->create()->getCollection()
                        ->addFieldToFilter('group_id', $groupId)->getItems();
        
                    foreach ($customerCollection as $customer) {
                        $customer->setGroupId($parentGroupId);
                        $this->customer->save($customer);
                    }
                    $this->groupRepository->deleteById($groupId);
                }
                $resultJson->setData([
                    'success' => true,
                    'message' => __('User group deleted successfully.')
                ]);
            } else {
                $resultJson->setData([
                    'success' => false,
                    'message' => __('Unable to retrieve user group.')
                ]);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while deleting the user group.')
            ]);
        }
        return $resultJson;
    }
}
