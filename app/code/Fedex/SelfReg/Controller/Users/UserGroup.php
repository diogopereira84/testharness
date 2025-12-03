<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Controller\Users;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\SelfReg\Model\UserGroupsFactory;
use Fedex\SelfReg\Model\UserGroupsPermissionFactory;
use Magento\Framework\App\ActionInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;

class UserGroup implements ActionInterface
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param UserGroupsPermissionFactory $userGroupsPermissionFactory
     * @param UserGroupsFactory $usergroupFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param CustomerFactory $customerFactory
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly UserGroupsPermissionFactory $userGroupsPermissionFactory,
        private readonly UserGroupsFactory $usergroupFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly LoggerInterface $logger,
        private GroupRepositoryInterface $groupRepositoryInterface,
        private CustomerFactory $customerFactory,
        private FilterBuilder $filterBuilder,
        private SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->context = $context;
    }

    /**
     * Execute function
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $id = $this->context->getRequest()->getParam("id");

            if ($id) {
                $idParts = explode("-", (string) $id);
                $groupType = $idParts[0] ?? "";
                $groupId = $idParts[1] ?? "";

                if ($groupType == 'user_groups') {
                    $data = $this->editUserGroup($groupId);
                } elseif ($groupType == 'customer_group') {
                    $data = $this->editCustomerGroup($groupId);
                } else {
                    $this->logger->error(
                        __METHOD__ . ':' . __LINE__ . ' Error in user group controller: Invalid group type.'
                    );
                    $data = [];
                }
                
                return $resultJson->setData(["output" => $data]);
            } else {
                return $resultJson->setData(["output" => []]);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in user group controller: ' . $e->getMessage());
        }
    }

    /**
     * Get Customer Object From Id
     *
     * @param int $customerId
     */
    public function checkCustomerIsExists($customerId)
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in Get customer from Id: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of Customer Objects From Ids
     *
     * @param array $customerIds
     */
    public function checkCustomerIsExistsFromIds($customerIds)
    {
        try {
            $filter = $this->filterBuilder
                ->setField('entity_id')
                ->setValue($customerIds)
                ->setConditionType('in')
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilters([$filter])
                ->create();
            return $this->customerRepository->getList($searchCriteria)->getItems();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in Get customer from Id: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Edit user group
     *
     * @param string $groupId
     *
     * @return array
     */
    public function editUserGroup(string $groupId): array
    {
        $data = [];
        $usergroupFactoryModel = $this->usergroupFactory->create()->load($groupId, "id");
        $data["id"] = $usergroupFactoryModel->getId();
        $data["group_name"] = preg_match('/<[^>]+>\s*(.+)/', $usergroupFactoryModel->getGroupName(), $groupName)
            ? $groupName[1] : $usergroupFactoryModel->getGroupName();
        $data["group_type"] = $usergroupFactoryModel->getGroupType();
        $userGroupsPermissionCollection = $this->userGroupsPermissionFactory->create()->getCollection()
            ->addFieldToFilter("group_id", $usergroupFactoryModel->getId());
        $data["users_list"] = [];
        $data["users"] = [];
        $customerIds = [];
        foreach ($userGroupsPermissionCollection as $item){
            $customerIds[] = $item->getUserId();
        }
        $customerIds = array_unique($customerIds);
        $customers = $this->checkCustomerIsExistsFromIds($customerIds);
        $customersNew = [];
        foreach ($customers as $customer){
            $customersNew[$customer->getId()] = $customer;
        }

        foreach($userGroupsPermissionCollection as $eachuserGroupsPermissionCollection){
            $customerUserId = $eachuserGroupsPermissionCollection->getUserId();
            if ($customerUserId) {
                $customer = $customersNew[$customerUserId];
                $name = $customer->getFirstname() . " " . $customer->getLastname();
                $data["users"][] = $customerUserId;
                $data['users_list'][] = [
                    'id' => $customerUserId,
                    'name' => $name
                ];
            }
        }
        $data["users"] = implode(",", $data['users']);
        $userGroupsPermission = $userGroupsPermissionCollection->getFirstItem();
        $data["order_approval_list"] = [];
        $data["order_approval"] = [];
        if (!empty($userGroupsPermission->getOrderApproval())) {
            $data['order_approval'] = $userGroupsPermission->getOrderApproval();
            $orderApprovalIds = explode(',', $userGroupsPermission->getOrderApproval());
            $orderApprovalIds = array_unique($orderApprovalIds);
            $orderApprovalUsers = $this->checkCustomerIsExistsFromIds($orderApprovalIds);
            $orderApprovalUsersNew = [];
            foreach ($orderApprovalUsers as $orderApprovalUser) {
                $orderApprovalUsersNew[$orderApprovalUser->getId()] = $orderApprovalUser;
            }

            foreach ($orderApprovalUsers as $orderApprovalUser) {
                $orderApprovalUserId = $orderApprovalUser->getId();
                if ($orderApprovalUserId) {
                    $orderApprover = $orderApprovalUsersNew[$orderApprovalUserId];
                    $name = $orderApprover->getFirstname() . " " . $orderApprover->getLastname();
                    $data['order_approval_list'][] = [
                        'id' => $orderApprovalUserId,
                        'name' => $name
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Edit customer group
     *
     * @param string $groupId
     *
     * @return array
     */
    public function editCustomerGroup(string $groupId): array
    {
        $data = [];
        $data["id"] = $groupId;
        $customerGroup = $this->groupRepositoryInterface->getById($groupId);
        $data["group_name"] = preg_match('/<[^>]+>\s*(.+)/', $customerGroup->getCode(), $groupName)
            ? $groupName[1] : $customerGroup->getCode();
        $data["group_type"] = "folder_permissions";
        $data["users_list"] = [];
        $data["users"] = [];
        $customerCollection = $this->customerFactory->create()->getCollection()
            ->addFieldToFilter('group_id', $customerGroup->getId());
        foreach ($customerCollection as $customer) {
            $name = $customer->getFirstname() . " " . $customer->getLastname();
            $data["users"][] = $customer->getId();
            $data['users_list'][] = [
                'id' => $customer->getId(),
                'name' => $name
            ];
        }
        $data["users"] = implode(",", $data['users']);
        $data["order_approval_list"] = [];
        $data["order_approval"] = [];

        return $data;
    }
}
