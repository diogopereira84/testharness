<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface UserGroupsRepositoryInterface
{
    /**
     * Save user_groups
     * @param \Fedex\SelfReg\Api\Data\UserGroupsInterface $userGroups
     * @return \Fedex\SelfReg\Api\Data\UserGroupsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Fedex\SelfReg\Api\Data\UserGroupsInterface $userGroups
    );

    /**
     * Retrieve user_groups
     * @param string $id
     * @return \Fedex\SelfReg\Api\Data\UserGroupsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve user_groups matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Fedex\SelfReg\Api\Data\UserGroupsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete user_groups
     * @param \Fedex\SelfReg\Api\Data\UserGroupsInterface $userGroups
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Fedex\SelfReg\Api\Data\UserGroupsInterface $userGroups
    );

    /**
     * Delete user_groups by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}

