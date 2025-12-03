<?php

declare(strict_types=1);

namespace Fedex\Company\Controller\User;

use Exception;
use Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;
use Fedex\SelfReg\Api\UserGroupsPermissionRepositoryInterface;
use Fedex\SelfReg\Model\UserGroupsFactory;
use Fedex\SelfReg\Model\UserGroupsPermissionFactory;
use Fedex\Company\Model\CustomerGroupSaveModel;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Helper\Data;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Fedex\CustomerGroup\Model\FolderPermission;

class Save implements HttpPostActionInterface
{
    /**
     * Save class constructor
     *
     * @param Session $session
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param CompanyManagementInterface $companyManagement
     * @param UserGroupsFactory $usergroupFactory
     * @param UserGroupsPermissionFactory $userGroupsPermissionFactory
     * @param CustomerRepository $customerRepository
     * @param CustomerFactory $customerFactory
     * @param UserGroupsRepositoryInterface $userGroupsRepository
     * @param UserGroupsPermissionRepositoryInterface $userGroupsPermissionRepository
     * @param CustomerGroupSaveModel $customerGroupSaveModel
     * @param LoggerInterface $logger
     * @param Data $data
     * @param Customer $customer
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FolderPermission $folderPermission
     */
    public function __construct(
        private Session $session,
        private JsonFactory $resultJsonFactory,
        private RequestInterface $request,
        private CompanyManagementInterface $companyManagement,
        private UserGroupsFactory $usergroupFactory,
        private UserGroupsPermissionFactory $userGroupsPermissionFactory,
        private CustomerRepository $customerRepository,
        private CustomerFactory $customerFactory,
        private UserGroupsRepositoryInterface $userGroupsRepository,
        private UserGroupsPermissionRepositoryInterface $userGroupsPermissionRepository,
        private CustomerGroupSaveModel $customerGroupSaveModel,
        private LoggerInterface $logger,
        private Data $data,
        private Customer $customer,
        private FilterBuilder $filterBuilder,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private FolderPermission $folderPermission
    ) {
    }

    /**
     * Execute function
     *
     * @return string Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $postData = $this->request->getParams() ?? [];
        $customerId = $this->session->getCustomer()?->getId();

        $companyData = null;
        if ($customerId) {
            $companyData = $this->companyManagement->getByCustomerId($customerId);
        }

        if ($companyData) {
            try {
                $companyId = (int) $companyData->getId();
                $companyUrlExt = (isset($postData['groupId']) && !empty($postData['groupId'])) ?
                    $companyUrlExt = $postData['siteUrl'] : '<' . $companyData->getCompanyUrlExtention() . '>';
                $parentGroupId = (int) $companyData->getCustomerGroupId();

                if ($companyId
                    && isset($postData['groupType'])
                    && $postData['groupType'] == UserGroupsPermissionInterface::ORDER_APPROVAL) {

                    $groupType = UserGroupsPermissionInterface::ORDER_APPROVAL;

                } elseif ($companyId
                        && isset($postData['groupType'])
                        && $postData['groupType'] == UserGroupsPermissionInterface::FOLDER_PERMISSIONS) {
                    
                    $groupType = UserGroupsPermissionInterface::FOLDER_PERMISSIONS;
                }

                return $this->save($postData, $companyId, $groupType, $companyUrlExt, $parentGroupId);

            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $resultJson->setData([
                    'success' => false,
                    'message' => __('An error occurred while saving the user group.')
                ]);
            }
        } else {
            $resultJson->setData([
                'success' => false,
                'message' => __('Unable to find company data.')
            ]);
        }

        return $resultJson;
    }

    /**
     * Save function
     *
     * @param array $postData
     * @param int $companyId
     * @param int $groupType
     * @param string $companyUrlExt
     * @param int $parentGroupId
     *
     * @return string Json
     */
    public function save(array $postData, int $companyId, string $groupType, string $companyUrlExt, int $parentGroupId)
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJsonData = [];
        try {
            if (empty($postData['userIds'])) {
                $userIdsArray = [];
            } else {
                $userIdsArray = explode(',', ($postData['userIds'] ?? ''));
                $userIdsArray = array_unique($userIdsArray);
            }

            if ($groupType == UserGroupsPermissionInterface::FOLDER_PERMISSIONS) {
                $baseGroupName = isset($postData['groupName']) ? $postData['groupName'] : '';
                $groupName = $companyUrlExt ? $companyUrlExt . ' ' . $baseGroupName : $baseGroupName;
                $formGroupId = (isset($postData['groupId']) && !empty($postData['groupId'])) ?
                    (int) $postData['groupId'] : null;

                $isEditedGroup = false;
                if ($formGroupId) {
                    $isEditedGroup = true;
                    $customerGroupId = $this->customerGroupSaveModel
                        ->editCustomerGroup($baseGroupName, $groupName, $parentGroupId, $formGroupId);
                    if ($customerGroupId) {
                        $this->removeCustomersFromGroup($userIdsArray, $customerGroupId, $parentGroupId);
                    }
                } else {
                    $customerGroupId = $this->customerGroupSaveModel
                        ->saveInCustomerGroup($baseGroupName, $groupName, $parentGroupId);
                }

                if ($customerGroupId && $parentGroupId) {
                    $this->saveCustomerEntityGroupId($userIdsArray, $customerGroupId);

                    $this->folderPermission->mapCategoriesCustomerGroup([], $parentGroupId, $customerGroupId, $isEditedGroup);
                    $unAssignedCategories = $this->folderPermission->getUnAssignedCategories($customerGroupId, []);
                    if (!empty($unAssignedCategories)) {
                        $this->folderPermission->unAssignCustomerGroupId($unAssignedCategories, $customerGroupId);
                    }
                    $this->folderPermission->assignCustomerGroupId([], $customerGroupId);

                    $resultJsonData = [
                        'success' => true,
                        'message' => __('User group saved successfully.')
                    ];
                } else {
                    $resultJsonData = [
                        'success' => false,
                        'existingErrorMessage' => __('The user group name already exists.')
                    ];
                }
            } elseif ($groupType == UserGroupsPermissionInterface::ORDER_APPROVAL) {
                $isEditedGroup = false;
                if (isset($postData['groupId']) && !empty($postData['groupId'])) {
                    $usergroup = $this->userGroupsRepository->get((int) $postData['groupId']);
                    $isEditedGroup = true;
                } else {
                    $usergroup = $this->usergroupFactory->create();
                }

                if ($usergroup) {
                    $baseGroupName = isset($postData['groupName']) ? $postData['groupName'] :
                        ($usergroup->getGroupName() ?? '');
                    $groupName = $companyUrlExt ? $companyUrlExt . ' ' . $baseGroupName : $baseGroupName;

                    if ($groupName !== $usergroup->getGroupName()) {
                        if (!$this->isUserGroupUnique($groupName)) {
                            $resultJsonData = [
                                'success' => false,
                                'existingErrorMessage' => __('The user group name already exists.')
                            ];
                            $resultJson->setData($resultJsonData);

                            return $resultJson;
                        } else {
                            if (!$isEditedGroup) {
                                $usergroup->setGroupType($groupType);
                            }
                            $usergroup->setGroupName($groupName);
                            $this->userGroupsRepository->save($usergroup);
                        }
                    }

                    $groupId = $usergroup->getId();

                    if ($groupId) {
                        if ($isEditedGroup) {
                            $permissions = $this->userGroupsPermissionRepository
                                ->getByGroupId((int) $postData['groupId']);

                            $userGroupsPermissionCollection = $this->userGroupsPermissionFactory
                                ->create()
                                ->getCollection()
                                ->addFieldToFilter("group_id", $groupId)
                                ->getFirstItem();

                            $orderApproversArrayExisting =
                                explode(',', ($userGroupsPermissionCollection->getOrderApproval() ?? ''));
                            $orderApproversArrayExisting = array_unique($orderApproversArrayExisting);

                            if ($permissions) {
                                foreach ($permissions as $permission) {
                                    $this->userGroupsPermissionRepository->delete($permission);
                                }
                            }
                        }

                        // Remove user record if associated with different group
                        if ($userIdsArray) {
                            $this->userGroupsPermissionRepository
                                ->deleteByUserGroupInfo(
                                    $companyId,
                                    (int) $groupId,
                                    $groupType,
                                    $userIdsArray
                                );
                        }

                        // Remove duplicate order approver ids
                        $orderApprovers = $postData['orderApprovers'] ?? '';
                        $orderApproversArray = explode(',', ($orderApprovers ?? ''));
                        $orderApproversArray = array_unique($orderApproversArray);
                        $orderApprovers = implode(',', $orderApproversArray);

                        if ($userIdsArray) {
                            foreach ($userIdsArray as $userId) {
                                $usergroupPermission = $this->userGroupsPermissionFactory->create();
                                $usergroupPermission->setGroupId($groupId);
                                $usergroupPermission->setUserId((int)$userId);
                                $usergroupPermission->setCompanyId($companyId);
                                $usergroupPermission->setOrderApproval($orderApprovers);

                                $this->userGroupsPermissionRepository->save($usergroupPermission);
                            }
                        } else {
                            $usergroupPermission = $this->userGroupsPermissionFactory->create();
                            $usergroupPermission->setGroupId($groupId);
                            $usergroupPermission->setUserId(null);
                            $usergroupPermission->setCompanyId($companyId);
                            $usergroupPermission->setOrderApproval($orderApprovers);

                            $this->userGroupsPermissionRepository->save($usergroupPermission);
                        }

                        if ($isEditedGroup) {
                            // Get difference order appprover ids
                            $orderApproverdiff = array_diff($orderApproversArrayExisting, $orderApproversArray);
                            $orderApproverdiff = array_values($orderApproverdiff);

                            if (is_array($orderApproverdiff) && count($orderApproverdiff) > 0) {
                                foreach ($orderApproverdiff as $eachOrderApproverdififf) {
                                    $orderApproverCount = $this->data
                                        ->checkIfCustomerIsOrderApprovar($eachOrderApproverdififf);

                                    // Check order approver not assigned to other user guoups
                                    // If not assign to any group then delete from order review role table
                                    if (!$this->data->checkIfCustomerIsOrderApprovar($eachOrderApproverdififf)) {
                                        // Order approver id Delete from order review permission
                                        $this->data
                                            ->deletePermission($eachOrderApproverdififf, $companyId);
                                    }
                                }
                            }
                        }

                        // Save value in enhace user role permission
                        $this->saveValueInEnhanceUserRole($orderApproversArray, $companyId);

                        $resultJsonData = [
                            'success' => true,
                            'message' => __('User group saved successfully.')
                        ];
                    } else {
                        $resultJsonData = [
                            'success' => false,
                            'message' => __('An error occurred while saving the user group.')
                        ];
                    }
                } else {
                    $resultJsonData = [
                        'success' => false,
                        'message' => __('Unable to create or retrieve user group.')
                    ];
                }
            }

            $resultJson->setData($resultJsonData);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while saving the user group.')
            ]);
        }
        return $resultJson;
    }

    /**
     * Save Data in enhance user role table
     *
     * @param array $orderApproversArray
     * @param int $companyId
     */
    public function saveValueInEnhanceUserRole($orderApproversArray, $companyId)
    {
        foreach ($orderApproversArray as $orderApprover) {
            $this->data->setPermissions($orderApprover, $companyId);
        }
    }

    /**
     * Save group id in customer entity table
     *
     * @param array $userIdsArray
     * @param int $customerGroupId
     *
     * @return void
     */
    public function saveCustomerEntityGroupId(array $userIdsArray, int $customerGroupId): void
    {
        foreach ($userIdsArray as $userId) {
            if ($userId) {
                $customer = $this->customerFactory->create()->load($userId);
                $oldGroupId = $customer->getGroupId();

                if ($oldGroupId != $customerGroupId) {
                    $customer->setGroupId($customerGroupId);
                    $this->customer->save($customer);
                }
            }
        }
    }

    /**
     * Remove customers from group and set to parent group
     *
     * @param array $userIdsArray
     * @param ?int $customerGroupId
     * @param int $parentGroupId
     *
     * @return void
     */
    public function removeCustomersFromGroup(array $userIdsArray, ?int $customerGroupId, int $parentGroupId): void
    {
        if ($customerGroupId) {
            $customerCollection = array_keys($this->customerFactory->create()->getCollection()
                ->addFieldToFilter('group_id', $customerGroupId)->getItems());
            $removedCustomerIds = array_diff($customerCollection, $userIdsArray);

            foreach ($removedCustomerIds as $customerId) {
                $customer = $this->customerFactory->create()->load($customerId);
                $customer->setGroupId($parentGroupId);
                $this->customer->save($customer);
            }
        }
    }

    /**
     * Check if there are duplicate user group names in user groups table
     *
     * @param string $groupName
     *
     * @return bool
     */
    public function isUserGroupUnique(string $groupName): bool
    {
        $isUnique = true;

        $userGroupsFilter = $this->filterBuilder->setField('group_name')
            ->setConditionType('eq')
            ->setValue($groupName)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$userGroupsFilter])
            ->create();
        
        $groupList = $this->userGroupsRepository->getList($searchCriteria);
        if ($groupList->getTotalCount() > 0) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' User group name already exists.');
            $isUnique = false;
        }

        return $isUnique;
    }
}
