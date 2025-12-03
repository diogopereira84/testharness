<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface UserGroupsPermissionRepositoryInterface
{
    /**
     * Save user_groups_permission
     * @param \Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface $userGroupsPermission
     * @return \Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Fedex\Selfreg\Api\Data\UserGroupsPermissionInterface $userGroupsPermission
    );

    /**
     * Retrieve user_groups_permission
     * @param string $id
     * @return \Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve user_groups_permission matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Fedex\SelfReg\Api\Data\UserGroupsPermissionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete user_groups_permission
     * @param \Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface $userGroupsPermission
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface $userGroupsPermission
    );

    /**
     * Delete user_groups_permission by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);

    /**
     * Get user group permissions by group ID
     *
     * @param int $groupId
     * @return UserGroupsPermissionInterface[]
     * @throws NoSuchEntityException
     */
    public function getByGroupId(int $groupId): array;

    /**
     * Delete user group permissions where company_id is equal &
     * group_id is not equal to the provided values.
     * Also user_id is in the specified user list along with
     * specific group type.
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
    ): void;

    /**
     * Check for duplicate users in a group within the company.
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
    );
}
