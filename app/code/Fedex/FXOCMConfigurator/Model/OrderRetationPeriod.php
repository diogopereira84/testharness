<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Model;

use Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface;
use Magento\Framework\Model\AbstractModel;

class OrderRetationPeriod extends AbstractModel implements OrderRetationPeriodInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getOrderItemId()
    {
        return $this->getData(self::ORDER_ITEM_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderItemId($itemId)
    {
        return $this->setData(self::ORDER_ITEM_ID, $itemId);
    }

    /**
     * @inheritDoc
     */
    public function getDocumentId()
    {
        return $this->getData(self::DOCUMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setDocumentId($documentId)
    {
        return $this->setData(self::DOCUMENT_ID, $documentId);
    }

    /**
     * @inheritDoc
     */
    public function getExtendedDate()
    {
        return $this->getData(self::EXTENDED_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setExtendedDate($extendedDate)
    {
        return $this->setData(self::EXTENDED_DATE, $extendedDate);
    }

    /**
     * @inheritDoc
     */
    public function getExtendedFlag()
    {
        return $this->getData(self::EXTENDED_FLAG);
    }

    /**
     * @inheritDoc
     */
    public function setExtendedFlag($extendedFlag)
    {
        return $this->setData(self::EXTENDED_FLAG, $extendedFlag);
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

