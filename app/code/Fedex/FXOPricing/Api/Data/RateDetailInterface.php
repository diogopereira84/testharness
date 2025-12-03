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

interface RateDetailInterface extends DataObjectInterface
{
    /**
     * @return RateDeliveryLineCollectionInterface
     */
    public function getDeliveryLines(): RateDeliveryLineCollectionInterface;

    /**
     * @param RateDeliveryLineCollectionInterface $deliveryLines
     * @return RateDetailInterface
     */
    public function setDeliveryLines(RateDeliveryLineCollectionInterface $deliveryLines): RateDetailInterface;

    /**
     * @return RateDiscountCollectionInterface
     */
    public function getDiscounts(): RateDiscountCollectionInterface;

    /**
     * @param RateDiscountCollectionInterface $discounts
     * @return RateDetailInterface
     */
    public function setDiscounts(RateDiscountCollectionInterface $discounts): RateDetailInterface;

    /**
     * @return string
     */
    public function getEstimatedVsActual(): string;

    /**
     * @param string $estimatedVsActual
     * @return RateDetailInterface
     */
    public function setEstimatedVsActual(string $estimatedVsActual): RateDetailInterface;

    /**
     * @return string
     */
    public function getGrossAmount(): string;

    /**
     * @param string $grossAmount
     * @return RateDetailInterface
     */
    public function setGrossAmount(string $grossAmount): RateDetailInterface;

    /**
     * @return string
     */
    public function getNetAmount(): string;

    /**
     * @param string $netAmount
     * @return RateDetailInterface
     */
    public function setNetAmount(string $netAmount): RateDetailInterface;

    /**
     * @return string
     */
    public function getTaxableAmount(): string;

    /**
     * @param string $taxableAmount
     * @return RateDetailInterface
     */
    public function setTaxableAmount(string $taxableAmount): RateDetailInterface;

    /**
     * @return string
     */
    public function getTaxAmount(): string;

    /**
     * @param string $taxAmount
     * @return RateDetailInterface
     */
    public function setTaxAmount(string $taxAmount): RateDetailInterface;

    /**
     * @return string
     */
    public function getTotalAmount(): string;

    /**
     * @param string $totalAmount
     * @return RateDetailInterface
     */
    public function setTotalAmount(string $totalAmount): RateDetailInterface;

    /**
     * @return string
     */
    public function getTotalDiscountAmount(): string;

    /**
     * @param string $totalDiscountAmount
     * @return RateDetailInterface
     */
    public function setTotalDiscountAmount(string $totalDiscountAmount): RateDetailInterface;

    /**
     * @return bool
     */
    public function hasShippingDeliveryLineDiscount(): bool;

    /**
     * @return bool
     */
    public function hasCouponDiscounts(): bool;

    /**
     * @param float|int $amount
     * @return bool
     */
    public function compareShippingDeliveryLineDiscounts(float|int $amount): bool;
}
