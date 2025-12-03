<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Zend_Db_Expr;
use Magento\Framework\DB\Select;
use Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface;
use Fedex\SelfReg\Api\Data\UserGroupsPermissionInterfaceFactory;
use Fedex\SelfReg\Api\Data\UserGroupsPermissionSearchResultsInterfaceFactory;
use Fedex\SelfReg\Api\UserGroupsPermissionRepositoryInterface;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission as ResourceUserGroupsPermission;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission\CollectionFactory as UserGroupsPermissionCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class UserGroupsPermissionRepository implements UserGroupsPermissionRepositoryInterface
{
    /**
     * UserGroupsPermissionRepository class constructor
     *
     * @param ResourceUserGroupsPermission $resource
     * @param UserGroupsPermissionInterfaceFactory $userGroupsPermissionFactory
     * @param UserGroupsPermissionCollectionFactory $userGroupsPermissionCollectionFactory
     * @param UserGroupsPermissionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        protected ResourceUserGroupsPermission $resource,
        protected UserGroupsPermissionInterfaceFactory $userGroupsPermissionFactory,
        protected UserGroupsPermissionCollectionFactory $userGroupsPermissionCollectionFactory,
        protected UserGroupsPermissionSearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function save(
        UserGroupsPermissionInterface $userGroupsPermission
    ) {
        try {
            $this->resource->save($userGroupsPermission);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the userGroupsPermission: %1',
                $exception->getMessage()
            ));
        }
        return $userGroupsPermission;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        $userGroupsPermission = $this->userGroupsPermissionFactory->create();
        $this->resource->load($userGroupsPermission, $id);
        if (!$userGroupsPermission->getId()) {
            throw new NoSuchEntityException(__('user_groups_permission with id "%1" does not exist.', $id));
        }
        return $userGroupsPermission;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->userGroupsPermissionCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        UserGroupsPermissionInterface $userGroupsPermission
    ) {
        try {
            $userGroupsPermissionModel = $this->userGroupsPermissionFactory->create();
            $this->resource->load($userGroupsPermissionModel, $userGroupsPermission->getId());
            $this->resource->delete($userGroupsPermissionModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the user_groups_permission: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        return $this->delete($this->get($id));
    }

    /**
     * Get user group permissions by group ID
     *
     * @param int $groupId
     * @return UserGroupsPermissionInterface[]
     * @throws NoSuchEntityException
     */
    public function getByGroupId(int $groupId): array
    {
        $collection = $this->userGroupsPermissionCollectionFactory->create();
        $collection->addFieldToFilter('group_id', $groupId);

        if ($collection->getSize() == 0) {
            throw new NoSuchEntityException(__('No user group permissions found for group_id "%1"', $groupId));
        }

        return $collection->getItems();
    }

    /**
     * Delete user group permissions where company_id is equal &
     * group_id is not equal to the provided values.
     * Also user_id is in the specified user list along
     * with specific group type.
     *
     * @param int $companyId
     * @param int $groupId
     * @param string $groupType
     * @param array $userIdArray
     * @return void
     */
    public function deleteByUserGroupInfo(
        int $companyId,
        int $groupId,
        string $groupType,
        array $userIdArray
    ): void {
        $collection = $this->userGroupsPermissionCollectionFactory->create();

        // Join with the user_group table on group_id
        // where group_type = 'order_approval' | 'folder_permission'
        $collection->getSelect()->join(
            ['ug' => 'user_groups'], // Alias for the user_group table
            'main_table.group_id = ug.id AND ug.group_type = "'.$groupType.'"',
            [] // Empty array to avoid selecting any columns from the joined table
        );

        // Filter the collection to exclude records with the specified group_id
        $collection->addFieldToFilter('group_id', ['neq' => $groupId]);

        // Filter the collection to include records with the specified company_id
        $collection->addFieldToFilter('company_id', ['eq' => $companyId]);

        $newCollection = clone $collection;

        $newCollection->getSelect()->columns([
            'users' => new Zend_Db_Expr('COUNT(main_table.group_id)'),
        ])->group(
            'main_table.group_id'
        );

        $userGroupsCount = [];
        foreach ($newCollection->getItems() as $userPermission) {
            $userGroupsCount[$userPermission->getGroupId()] = $userPermission->getUsers();
        }

        // Filter the collection to include only records with user_id in the provided list
        $collection->addFieldToFilter('user_id', ['in' => $userIdArray]);

        // Iterate through the collection and delete each item
        if ($collection) {
            foreach ($collection as $item) {
                if (isset($userGroupsCount[$item->getGroupId()]) && $userGroupsCount[$item->getGroupId()] == 1) {
                    $item->setUserId(null);
                    $item->save();
                } else {
                    $userGroupsCount[$item->getGroupId()] -= 1;
                    $item->delete();
                }
            }
        }
    }

    /**
     * Check if a user exists in the specified company group
     *
     * @param int $companyId
     * @param int $groupId
     * @param string $groupType
     * @param string $userIds
     * @return int
     */
    public function checkDuplicateUsersInGroup(
        int $companyId,
        int $groupId,
        string $groupType,
        string $userIds
    ) {
        $collection = $this->userGroupsPermissionCollectionFactory->create();

        $collection->addFieldToFilter('company_id', [
            'eq' => $companyId
        ]);
        $collection->addFieldToFilter('group_type', [
            'eq' => $groupType
        ]);
        $collection->addFieldToFilter('group_id', [
            'neq' => $groupId
        ]);
        $collection->addFieldToFilter('user_id', [
            'in' => $userIds
        ]);

        $collection->getSelect()->join(
            ['ug' => $collection->getTable('user_groups')],
            'main_table.group_id = ug.id',
            []
        );

        $collection
            ->getSelect()
            ->reset(Select::COLUMNS)
            ->columns([
                'duplicate_count' => new Zend_Db_Expr('COUNT(main_table.user_id)'),
                'group_id' => 'main_table.group_id'
            ]);

        $result = $collection->getConnection()->fetchRow($collection->getSelect());
        return $result;
    }
}
