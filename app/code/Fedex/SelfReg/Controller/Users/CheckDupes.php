<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Controller\Users;

use Fedex\SelfReg\Api\UserGroupsPermissionRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;

class CheckDupes implements HttpPostActionInterface
{
    /**
     * CheckDupes class constructor function
     *
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param Session $session
     * @param UserGroupsPermissionRepositoryInterface $userGroupsPermissionRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepository $customerRepository
     * @param UserGroupsRepositoryInterface $userGroupsInterface
     */
    public function __construct(
        private RequestInterface $request,
        private JsonFactory $resultJsonFactory,
        private Session $session,
        private UserGroupsPermissionRepositoryInterface $userGroupsPermissionRepository,
        private FilterBuilder $filterBuilder,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private CustomerRepository $customerRepository,
        private UserGroupsRepositoryInterface $userGroupsInterface
    ) {
    }

    /**
     * Execute function
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $groupId = $this->request->getParam('groupId') ?? null;
        $userIds = $this->request->getParam('userIds') ?? null;
        $groupType = $this->request->getParam('groupType') ?? null;
        $companyData = $this->session?->getOndemandCompanyInfo();
        $companyId = $companyData['company_id'] ?? null;
        $isFolderPermissionGroup = filter_var(
            $this->request->getParam('isFolderPermissionGroup'),
            FILTER_VALIDATE_BOOLEAN
        ) ?? null;
        $companyDataObject = $companyData['company_data'] ?? null;
        $customerGroupId = $companyDataObject ? $companyDataObject->getCustomerGroupId() : null;
        if ($companyId) {
            if($isFolderPermissionGroup){
                $duplicates = $this->checkDuplicateCustomerGroups(
                    (string) $userIds,
                    (int) $groupId,
                    (string) $customerGroupId
                );
            }else{
                $duplicatedData =$this->userGroupsPermissionRepository->checkDuplicateUsersInGroup(
                    (int) $companyId,
                    (int) $groupId,
                    (string) $groupType,
                    (string) $userIds
                );

                if(isset($duplicatedData['group_id']) && !empty($duplicatedData['group_id'])){
                    $group_id = (int)$duplicatedData['group_id'];
                    if(!empty($group_id)){
                        $groupName = $this->userGroupsInterface->get($group_id)->getGroupName() ?? '';
                    }
                }

                $duplicates = $duplicatedData['duplicate_count'] ?? 0;
            }
            return $resultJson->setData([
                'success' => true,
                'duplicate' => $duplicates > 0 ? true : false,
                'duplicate_count' => $duplicates ?? 0,
                'group_name' => $groupName ?? '',
                'folder_permission_group' => $isFolderPermissionGroup ?? false,
                'message' => $duplicates > 0
                            ? __('Duplicate users found in the group.')
                            : __('No duplicate users found in the group.')
            ]);
        } else {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid parameters.')
            ]);
        }
    }
    /**
     * Check duplicate customer groups
     *
     * @param string $userIds
     * @param int $groupId
     * @param string $customerGroupId
     *
     * @return int
     */
    public function checkDuplicateCustomerGroups(string $userIds, int $groupId, string $customerGroupId): int
    {
        $customerIdFilter = $this->filterBuilder->setField('entity_id')
            ->setConditionType('in')
            ->setValue($userIds)
            ->create();

        $defaultGroupIdFilter = $this->filterBuilder->setField('group_id')
            ->setConditionType('eq')
            ->setValue($customerGroupId)
            ->create();

        $checkCriteria = $this->searchCriteriaBuilder
            ->addFilters([$customerIdFilter])
            ->addFilters([$defaultGroupIdFilter])
            ->create();

        $matchedList = $this->customerRepository->getList($checkCriteria);
        $matchedCount = $matchedList->getTotalCount();
        $userIdsArr = explode(',', $userIds);

        if ($matchedCount === count($userIdsArr)) {
            return 0;
        }

        $customerGroupIdFilter = $this->filterBuilder->setField('group_id')
            ->setConditionType('neq')
            ->setValue($groupId)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$customerIdFilter])
            ->addFilters([$customerGroupIdFilter])
            ->create();
        
        $groupList = $this->customerRepository->getList($searchCriteria);
        return $groupList->getTotalCount();
    }
}
