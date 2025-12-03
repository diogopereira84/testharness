<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Api\Data;

interface UserGroupsPermissionInterface
{
    const USER_ID = 'user_id';
    const USER_GROUPS_PERMISSION_ID = 'id';
    const UPDATED_AT = 'updated_at';
    const COMPANY_ID = 'company_id';
    const ORDER_APPROVAL = 'order_approval';
    const FOLDER_PERMISSIONS = 'folder_permissions';
    const CREATED_AT = 'created_at';
    const GROUP_ID = 'group_id';

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param string $id
     * @return \Fedex\SelfReg\UserGroupsPermission\Api\Data\UserGroupsPermissionInterface
     */
    public function setId($id);

    /**
     * Get group_id
     * @return string|null
     */
    public function getGroupId();

    /**
     * Set group_id
     * @param string $groupId
     * @return \Fedex\SelfReg\UserGroupsPermission\Api\Data\UserGroupsPermissionInterface
     */
    public function setGroupId($groupId);

    /**
     * Get user_id
     * @return string|null
     */
    public function getUserId();

    /**
     * Set user_id
     * @param string $userId
     * @return \Fedex\SelfReg\UserGroupsPermission\Api\Data\UserGroupsPermissionInterface
     */
    public function setUserId($userId);

    /**
     * Get company_id
     * @return string|null
     */
    public function getCompanyId();

    /**
     * Set company_id
     * @param string $companyId
     * @return \Fedex\SelfReg\UserGroupsPermission\Api\Data\UserGroupsPermissionInterface
     */
    public function setCompanyId($companyId);

    /**
     * Get order_approval
     * @return string|null
     */
    public function getOrderApproval();

    /**
     * Set order_approval
     * @param string $orderApproval
     * @return \Fedex\SelfReg\UserGroupsPermission\Api\Data\UserGroupsPermissionInterface
     */
    public function setOrderApproval($orderApproval);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Fedex\SelfReg\UserGroupsPermission\Api\Data\UserGroupsPermissionInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Fedex\SelfReg\UserGroupsPermission\Api\Data\UserGroupsPermissionInterface
     */
    public function setUpdatedAt($updatedAt);
}
