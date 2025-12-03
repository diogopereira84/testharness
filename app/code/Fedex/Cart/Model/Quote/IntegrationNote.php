<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote;

use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class IntegrationNote extends AbstractExtensibleModel implements IdentityInterface, CartIntegrationNoteInterface
{
    public const CACHE_TAG = 'integration_note';

    /**
     * @var string
     */
    protected $_eventPrefix = 'integration_note';

     /**
     * Initialise resource model
     * phpcs:disable
     */
    protected function _construct()
    {
        $this->_init(IntegrationNoteResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function setParentId(int $parentId): void
    {
        $this->setData(static::PARENT_ID, $parentId);
    }

    /**
     * @inheritdoc
     */
    public function getParentId(): int
    {
        return (int)$this->getData(static::PARENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setNote(string|null $quoteIntegrationNote): void
    {
        $this->setData(static::NOTE, $quoteIntegrationNote);
    }

    /**
     * @inheritdoc
     */
    public function getNote(): ?string
    {
        return $this->getData(static::NOTE);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $date): void
    {
        $this->setData(static::CREATED_AT, $date);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(static::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $date): void
    {
        $this->setData(static::UPDATED_AT, $date);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(static::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
