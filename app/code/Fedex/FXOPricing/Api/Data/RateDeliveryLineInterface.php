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

interface RateDeliveryLineInterface extends DataObjectInterface
{
    /**
     * @return string
     */
    public function getDeliveryDiscountAmount(): string;

    /**
     * @param string $deliveryDiscountAmount
     * @return RateDeliveryLineInterface
     */
    public function setDeliveryDiscountAmount(string $deliveryDiscountAmount): RateDeliveryLineInterface;

    /**
     * @return RateDeliveryLineDiscountCollectionInterface
     */
    public function getDeliveryLineDiscounts(): RateDeliveryLineDiscountCollectionInterface;

    /**
     * @param RateDeliveryLineDiscountCollectionInterface $deliveryLineDiscounts
     * @return RateDeliveryLineInterface
     */
    public function setDeliveryLineDiscounts(RateDeliveryLineDiscountCollectionInterface $deliveryLineDiscounts): RateDeliveryLineInterface;

    /**
     * @return string
     */
    public function getDeliveryLinePrice(): string;

    /**
     * @param string $deliveryLinePrice
     * @return RateDeliveryLineInterface
     */
    public function setDeliveryLinePrice(string $deliveryLinePrice): RateDeliveryLineInterface;

    /**
     * @return string
     */
    public function getDeliveryLineType(): string;

    /**
     * @param string $deliveryLineType
     * @return RateDeliveryLineInterface
     */
    public function setDeliveryLineType(string $deliveryLineType): RateDeliveryLineInterface;

    /**
     * @return string
     */
    public function getDeliveryRetailPrice(): string;

    /**
     * @param string $deliveryRetailPrice
     * @return RateDeliveryLineInterface
     */
    public function setDeliveryRetailPrice(string $deliveryRetailPrice): RateDeliveryLineInterface;

    /**
     * @return string
     */
    public function getEstimatedDeliveryLocalTime(): string;

    /**
     * @param string $estimatedDeliveryLocalTime
     * @return RateDeliveryLineInterface
     */
    public function setEstimatedDeliveryLocalTime(string $estimatedDeliveryLocalTime): RateDeliveryLineInterface;

    /**
     * @return string
     */
    public function getEstimatedShipDate(): string;

    /**
     * @param string $estimatedShipDate
     * @return RateDeliveryLineInterface
     */
    public function setEstimatedShipDate(string $estimatedShipDate): RateDeliveryLineInterface;

    /**
     * @return bool
     */
    public function getPriceable(): bool;

    /**
     * @param bool $priceable
     * @return RateDeliveryLineInterface
     */
    public function setPriceable(bool $priceable): RateDeliveryLineInterface;

    /**
     * @return string
     */
    public function getRecipientReference(): string;

    /**
     * @param string $recipientReference
     * @return RateDeliveryLineInterface
     */
    public function setRecipientReference(string $recipientReference): RateDeliveryLineInterface;

    /**
     * Check if the delivery line has any discount
     *
     * @return bool
     */
    public function hasDiscounts(): bool;
}
