<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Api\Data;

interface CartIntegrationNoteInterface
{
    public const ENTITY = 'quote_integration_note';
    public const QUOTE_INTEGRATION_NOTE_ID = 'entity_id';
    public const PARENT_ID = 'parent_id';
    public const NOTE = 'note';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Set quote integration note id
     *
     * @param mixed $quoteIntegrationNoteId
     * @return mixed
     */
    public function setId($quoteIntegrationNoteId);

    /**
     * Get quote integration note id
     *
     * @return mixed
     */
    public function getId();

    /**
     * Set quote integration note parent id
     *
     * @param int $parentId
     * @return void
     */
    public function setParentId(int $parentId): void;

    /**
     * Get quote integration note parent id
     *
     * @return int
     */
    public function getParentId(): int;

    /**
     * Set quote integration note
     *
     * @param string $quoteIntegrationNote
     * @return void
     */
    public function setNote(string|null $quoteIntegrationNote): void;

    /**
     * Get quote integration note
     *
     * @return null|string
     */
    public function getNote(): ?string;

    /**
     * Get quote integration note Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set quote integration note Created At
     *
     * @param string $date
     * @return void
     */
    public function setCreatedAt(string $date): void;

    /**
     * Get quote integration note Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set quote integration note Updated At
     *
     * @param string $date
     * @return void
     */
    public function setUpdatedAt(string $date): void;
}
