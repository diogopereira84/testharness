<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Fedex\SelfReg\Api\Data\UserGroupsInterface;
use Magento\Framework\Model\AbstractModel;

class UserGroups extends AbstractModel implements UserGroupsInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Fedex\SelfReg\Model\ResourceModel\UserGroups::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::USER_GROUPS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::USER_GROUPS_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getGroupName()
    {
        return $this->getData(self::GROUP_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setGroupName($groupName)
    {
        return $this->setData(self::GROUP_NAME, $groupName);
    }

    /**
     * @inheritDoc
     */
    public function getGroupType()
    {
        return $this->getData(self::GROUP_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setGroupType($groupType)
    {
        return $this->setData(self::GROUP_TYPE, $groupType);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
