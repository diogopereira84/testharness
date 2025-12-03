<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api\Data;

interface LineItemInterface
{
    /**
     * Retrieve product name
     *
     * @return string
     */
    public function getProductName(): string;

    /**
     * Set product name
     *
     * @param string $productName
     *
     * @return LineItemInterface
     */
    public function setProductName(string $productName): LineItemInterface;

    /**
     * Retrieve sku id
     *
     * @return string
     */
    public function getSkuId(): string;

    /**
     * Set sku id
     *
     * @param string $skuId
     *
     * @return LineItemInterface
     */
    public function setSkuId(string $skuId): LineItemInterface;

    /**
     * Retrieve quantity
     *
     * @return string
     */
    public function getQuantity(): string;

    /**
     * Set quantity
     *
     * @param string $quantity
     *
     * @return LineItemInterface
     */
    public function setQuantity(string $quantity): LineItemInterface;

    /**
     * Retrieve price
     *
     * @return string
     */
    public function getPrice(): string;

    /**
     * Set price
     *
     * @param string $price
     *
     * @return LineItemInterface
     */
    public function setPrice(string $price): LineItemInterface;
}
