<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Api\Data;

interface UserGroupsInterface
{
    const GROUP_NAME = 'group_name';
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = 'created_at';
    const GROUP_TYPE = 'group_type';
    const USER_GROUPS_ID = 'id';

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param string $id
     * @return \Fedex\SelfReg\UserGroups\Api\Data\UserGroupsInterface
     */
    public function setId($id);

    /**
     * Get group_name
     * @return string|null
     */
    public function getGroupName();

    /**
     * Set group_name
     * @param string $groupName
     * @return \Fedex\SelfReg\UserGroups\Api\Data\UserGroupsInterface
     */
    public function setGroupName($groupName);

    /**
     * Get group_type
     * @return string|null
     */
    public function getGroupType();

    /**
     * Set group_type
     * @param string $groupType
     * @return \Fedex\SelfReg\UserGroups\Api\Data\UserGroupsInterface
     */
    public function setGroupType($groupType);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Fedex\SelfReg\UserGroups\Api\Data\UserGroupsInterface
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
     * @return \Fedex\SelfReg\UserGroups\Api\Data\UserGroupsInterface
     */
    public function setUpdatedAt($updatedAt);
}

