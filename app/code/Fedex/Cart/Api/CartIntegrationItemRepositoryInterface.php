<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Api;

use Fedex\Cart\Api\Data\CartIntegrationItemInterface;

interface CartIntegrationItemRepositoryInterface
{
    /**
     * @param CartIntegrationItemInterface $integrationItem
     * @return CartIntegrationItemInterface
     */
    public function save(CartIntegrationItemInterface $integrationItem): CartIntegrationItemInterface;

    /**
     * @param int $itemId
     * @param string $itemData
     * @return CartIntegrationItemInterface
     */
    public function saveByQuoteItemId(int $itemId, string $itemData): CartIntegrationItemInterface;

    /**
     * @param int $integrationItemId
     * @return CartIntegrationItemInterface
     */
    public function getById(int $integrationItemId): CartIntegrationItemInterface;

    /**
     * @param int $itemId
     * @return CartIntegrationItemInterface
     */
    public function getByQuoteItemId(int $itemId): CartIntegrationItemInterface;

    /**
     * @param CartIntegrationItemInterface $integrationItem
     * @return bool
     */
    public function delete(CartIntegrationItemInterface $integrationItem): bool;

    /**
     * @param int $integrationItemId
     * @return bool
     */
    public function deleteById(int $integrationItemId): bool;
}
