<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
namespace Fedex\MarketplaceCheckout\Api;

interface OrderItemsInterface
{
    /**
     * Format a currency value.
     *
     * @param $value
     * @return float|string
     */
    public function formattedCurrencyValue($value): float|string;

    /**
     * Retrieve the URL of a view file.
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = []): string;

    /**
     * Get formatted shipment items.
     *
     * @return array
     */
    public function getShipmentItemsFormatted(): array;
}
