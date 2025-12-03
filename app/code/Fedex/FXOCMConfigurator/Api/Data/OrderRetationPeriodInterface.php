<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Api\Data;

interface OrderRetationPeriodInterface
{

    const ORDER_ITEM_ID = 'order_item_id';
    const DOCUMENT_ID = 'document_id';
    const UPDATED_AT = 'updated_at';
    const EXTENDED_FLAG = 'extended_flag';
    const ID = 'id';
    const CREATED_AT = 'created_at';
    const EXTENDED_DATE = 'extended_date';

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param string $id
     * @return \Fedex\FXOCMConfigurator\OrderRetationPeriod\Api\Data\OrderRetationPeriodInterface
     */
    public function setId($id);

    /**
     * Get order_item_id
     * @return string|null
     */
    public function getOrderItemId();

    /**
     * Set order_item_id
     * @param string $itemId
     * @return \Fedex\FXOCMConfigurator\OrderRetationPeriod\Api\Data\OrderRetationPeriodInterface
     */
    public function setOrderItemId($itemId);

    /**
     * Get document_id
     * @return string|null
     */
    public function getDocumentId();

    /**
     * Set document_id
     * @param string $documentId
     * @return \Fedex\FXOCMConfigurator\OrderRetationPeriod\Api\Data\OrderRetationPeriodInterface
     */
    public function setDocumentId($documentId);

    /**
     * Get extended_date
     * @return string|null
     */
    public function getExtendedDate();

    /**
     * Set extended_date
     * @param string $extendedDate
     * @return \Fedex\FXOCMConfigurator\OrderRetationPeriod\Api\Data\OrderRetationPeriodInterface
     */
    public function setExtendedDate($extendedDate);

    /**
     * Get extended_flag
     * @return string|null
     */
    public function getExtendedFlag();

    /**
     * Set extended_flag
     * @param string $extendedFlag
     * @return \Fedex\FXOCMConfigurator\OrderRetationPeriod\Api\Data\OrderRetationPeriodInterface
     */
    public function setExtendedFlag($extendedFlag);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Fedex\FXOCMConfigurator\OrderRetationPeriod\Api\Data\OrderRetationPeriodInterface
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
     * @return \Fedex\FXOCMConfigurator\OrderRetationPeriod\Api\Data\OrderRetationPeriodInterface
     */
    public function setUpdatedAt($updatedAt);
}

