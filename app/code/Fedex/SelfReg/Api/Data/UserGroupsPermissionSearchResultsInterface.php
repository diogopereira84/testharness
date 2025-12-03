<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Api\Data;

interface UserGroupsPermissionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get user_groups_permission list.
     * @return \Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface[]
     */
    public function getItems();

    /**
     * Set group_id list.
     * @param \Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

