<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Fedex\SelfReg\Api\Data\UserGroupsPermissionInterface;
use Magento\Framework\Model\AbstractModel;

class UserGroupsPermission extends AbstractModel implements UserGroupsPermissionInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::USER_GROUPS_PERMISSION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::USER_GROUPS_PERMISSION_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getGroupId()
    {
        return $this->getData(self::GROUP_ID);
    }

    /**
     * @inheritDoc
     */
    public function setGroupId($groupId)
    {
        return $this->setData(self::GROUP_ID, $groupId);
    }

    /**
     * @inheritDoc
     */
    public function getUserId()
    {
        return $this->getData(self::USER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setUserId($userId)
    {
        return $this->setData(self::USER_ID, $userId);
    }

    /**
     * @inheritDoc
     */
    public function getCompanyId()
    {
        return $this->getData(self::COMPANY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCompanyId($companyId)
    {
        return $this->setData(self::COMPANY_ID, $companyId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderApproval()
    {
        return $this->getData(self::ORDER_APPROVAL);
    }

    /**
     * @inheritDoc
     */
    public function setOrderApproval($orderApproval)
    {
        return $this->setData(self::ORDER_APPROVAL, $orderApproval);
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
    public function setUpdatedAt($updateAt)
    {
        return $this->setData(self::UPDATED_AT, $updateAt);
    }
}

