<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Api\Data;

interface UserGroupsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get user_groups list.
     * @return \Fedex\SelfReg\Api\Data\UserGroupsInterface[]
     */
    public function getItems();

    /**
     * Set group_name list.
     * @param \Fedex\SelfReg\Api\Data\UserGroupsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

