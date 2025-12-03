<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

use Fedex\Base\Api\Data\DataObjectInterface;

interface RateQuoteDeliveryLineInterface extends DataObjectInterface
{
    /**
     * @return float
     */
    public function getDeliveryDiscountAmount(): float;

    /**
     * @param float $deliveryDiscountAmount
     * @return RateQuoteDeliveryLineInterface
     */
    public function setDeliveryDiscountAmount(float $deliveryDiscountAmount): RateQuoteDeliveryLineInterface;

    /**
     * @return RateQuoteDeliveryLineDiscountCollectionInterface
     */
    public function getDeliveryLineDiscounts(): RateQuoteDeliveryLineDiscountCollectionInterface;

    /**
     * @param RateQuoteDeliveryLineDiscountCollectionInterface $deliveryLineDiscounts
     * @return RateQuoteDeliveryLineInterface
     */
    public function setDeliveryLineDiscounts(RateQuoteDeliveryLineDiscountCollectionInterface $deliveryLineDiscounts): RateQuoteDeliveryLineInterface;

    /**
     * @return float
     */
    public function getDeliveryLinePrice(): float;

    /**
     * @param float $deliveryLinePrice
     * @return RateQuoteDeliveryLineInterface
     */
    public function setDeliveryLinePrice(float $deliveryLinePrice): RateQuoteDeliveryLineInterface;

    /**
     * @return string
     */
    public function getDeliveryLineType(): string;

    /**
     * @param string $deliveryLineType
     * @return RateQuoteDeliveryLineInterface
     */
    public function setDeliveryLineType(string $deliveryLineType): RateQuoteDeliveryLineInterface;

    /**
     * @return float
     */
    public function getDeliveryRetailPrice(): float;

    /**
     * @param float $deliveryRetailPrice
     * @return RateQuoteDeliveryLineInterface
     */
    public function setDeliveryRetailPrice(float $deliveryRetailPrice): RateQuoteDeliveryLineInterface;

    /**
     * @return bool
     */
    public function getPriceable(): bool;

    /**
     * @param bool $priceable
     * @return RateQuoteDeliveryLineInterface
     */
    public function setPriceable(bool $priceable): RateQuoteDeliveryLineInterface;

    /**
     * @return string
     */
    public function getRecipientReference(): string;

    /**
     * @param string $recipientReference
     * @return RateQuoteDeliveryLineInterface
     */
    public function setRecipientReference(string $recipientReference): RateQuoteDeliveryLineInterface;

    /**
     * Check if the delivery line has any discount
     *
     * @return bool
     */
    public function hasDiscounts(): bool;
}
