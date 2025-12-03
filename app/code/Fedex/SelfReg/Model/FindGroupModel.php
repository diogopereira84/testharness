<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Fedex\SelfReg\Ui\Component\Listing\Column\CompanyUsersActions;

class FindGroupModel extends AbstractModel
{
    /**
     * FindGroupModel class constructor
     * @param CustomerRepositoryInterface $customerRepository
     * @param GroupRepositoryInterface $groupRepository
     * @param FilterBuilder filterBuilder
     * @param SearchCriteriaBuilder searchCriteriaBuilder
     * @param CompanyUsersActions $companyUsersActions
     */
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private GroupRepositoryInterface $groupRepository,
        private FilterBuilder $filterBuilder,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private CompanyUsersActions $companyUsersActions
    ) {}

    /**
     * get all customer group names
     * @param [] $selectedUserIds
     * @return []
     */
    public function getAllCustomersGroupName($selectedUserIds) {
        $customerGroupNames = [];
        
        // Remove duplicate user ids
        $userIdsArray = array_unique(explode(',', ($selectedUserIds ?? '')));
        $customerGroupIds = $this->getAllCustomerGroupIds($userIdsArray);

        if (isset($customerGroupIds) && !empty($customerGroupIds)) {
            $customerGroupNames = $this->getAllCustomerGroupCodes($customerGroupIds);
        }

        return $customerGroupNames;
    }

    /**
     * get all customer group ids
     * @param [] $userIdsArray
     * @return []
     */
    public function getAllCustomerGroupIds(array $userIdsArray) {
        $customerIds = [];

        $customerFilter = $this->filterBuilder->setField('entity_id')
                ->setConditionType('in')
                ->setValue($userIdsArray)
                ->create();

        $customerSearchCriteria = $this->searchCriteriaBuilder
                ->addFilters([$customerFilter])
                ->create();

        $customers = $this->customerRepository->getList($customerSearchCriteria)->getItems();
        if($customers !== null) {
            foreach ($customers as $customer) {
                array_push($customerIds,$customer->getGroupId());
            }
        }

        return $customerIds;
    }

    /**
     * get all customer group codes
     * @param [] $groupIds
     * @return []
     */
    public function getAllCustomerGroupCodes(array $groupIds) {
        $groupCodes = [];

        $groupFilter = $this->filterBuilder->setField('customer_group_id')
                ->setConditionType('in')
                ->setValue($groupIds)
                ->create();

        $groupSearchCriteria = $this->searchCriteriaBuilder
                ->addFilters([$groupFilter])
                ->create();

        $groups = $this->groupRepository->getList($groupSearchCriteria)->getItems();
        if ($groups && count($groups) === 1 && !empty($groups[0])) {
            $parentGroupId = $this->companyUsersActions->getParentGroupId($groups[0]->getId());
            if ($parentGroupId) {
                $groupCodes = ['Default'];
            } else {
                $groupCodes = [$groups[0]->getCode()];
            }
        } elseif ($groups) {
            foreach ($groups as $group) {
                array_push($groupCodes, $group->getCode());
            }
        }

        return $groupCodes;
    }
}