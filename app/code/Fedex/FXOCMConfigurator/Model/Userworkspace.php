<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Model;

use Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface;
use Magento\Framework\Model\AbstractModel;

class Userworkspace extends AbstractModel implements UserworkspaceInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace::class);
    }

    /**
     * @inheritDoc
     */
    public function getUserworkspaceId()
    {
        return $this->getData(self::USERWORKSPACE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setUserworkspaceId($userworkspaceId)
    {
        return $this->setData(self::USERWORKSPACE_ID, $userworkspaceId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getWorkspaceData()
    {
        return $this->getData(self::WORKSPACE_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setWorkspaceData($workspaceData)
    {
        return $this->setData(self::WORKSPACE_DATA, $workspaceData);
    }

    /**
     * @inheritDoc
     */
    public function getApplicationType()
    {
        return $this->getData(self::APPLICATION_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setApplicationType($applicationType)
    {
        return $this->setData(self::APPLICATION_TYPE, $applicationType);
    }

    /**
     * @inheritDoc
     */
    public function getOldUploadDate()
    {
        return $this->getData(self::OLD_UPLOAD_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setOldUploadDate($oldUploadDate)
    {
        return $this->setData(self::OLD_UPLOAD_DATE, $oldUploadDate);
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

