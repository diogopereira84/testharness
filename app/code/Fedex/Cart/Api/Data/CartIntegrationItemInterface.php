<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Api\Data;

interface CartIntegrationItemInterface
{
    const KEY_INTEGRATION_ITEM_ID = 'integration_item_id';
    const KEY_QUOTE_ID = 'quote_id';
    const KEY_ITEM_ID = 'item_id';
    const KEY_ITEM_DATA = 'item_data';

    /**
     * Returns the integration ID.
     *
     * @return int
     */
    public function getIntegrationItemId(): int;

    /**
     * Sets the integration ID.
     *
     * @param int $integrationItemId
     * @return $this
     */
    public function setIntegrationItemId(int $integrationItemId): static;

    /**
     * Returns Item id.
     *
     * @return int
     */
    public function getItemId(): int;

    /**
     * Sets Item id.
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): static;

    /**
     * Returns Item id.
     *
     * @return string
     */
    public function getItemData(): string;

    /**
     * Sets Item id.
     *
     * @param string $itemData
     * @return $this
     */
    public function setItemData(string $itemData): static;
}
