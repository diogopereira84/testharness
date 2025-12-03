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

interface RateQuoteDetailInterface extends DataObjectInterface
{
    /**
     * @return RateQuoteDeliveryLineCollectionInterface
     */
    public function getDeliveryLines(): RateQuoteDeliveryLineCollectionInterface;

    /**
     * @param RateQuoteDeliveryLineCollectionInterface $deliveryLines
     * @return RateQuoteDetailInterface
     */
    public function setDeliveryLines(RateQuoteDeliveryLineCollectionInterface $deliveryLines): RateQuoteDetailInterface;

    /**
     * @return RateQuoteDiscountCollectionInterface
     */
    public function getDiscounts(): RateQuoteDiscountCollectionInterface;

    /**
     * @param RateQuoteDiscountCollectionInterface $discounts
     * @return RateQuoteDetailInterface
     */
    public function setDiscounts(RateQuoteDiscountCollectionInterface $discounts): RateQuoteDetailInterface;

    /**
     * @return string
     */
    public function getEstimatedVsActual(): string;

    /**
     * @param string $estimatedVsActual
     * @return RateQuoteDetailInterface
     */
    public function setEstimatedVsActual(string $estimatedVsActual): RateQuoteDetailInterface;

    /**
     * @return float
     */
    public function getGrossAmount(): float;

    /**
     * @param float $grossAmount
     * @return RateQuoteDetailInterface
     */
    public function setGrossAmount(float $grossAmount): RateQuoteDetailInterface;

    /**
     * @return float
     */
    public function getNetAmount(): float;

    /**
     * @param float $netAmount
     * @return RateQuoteDetailInterface
     */
    public function setNetAmount(float $netAmount): RateQuoteDetailInterface;

    /**
     * @return array
     */
    public function getProductLines(): array;

    /**
     * @param array $productLines
     * @return RateQuoteDetailInterface
     */
    public function setProductLines(array $productLines): RateQuoteDetailInterface;

    /**
     * @return string
     */
    public function getRateQuoteId(): string;

    /**
     * @param string $rateQuoteId
     * @return RateQuoteDetailInterface
     */
    public function setRateQuoteId(string $rateQuoteId): RateQuoteDetailInterface;

    /**
     * @return string
     */
    public function getResponsibleLocationId(): string;

    /**
     * @param string $responsibleLocationId
     * @return RateQuoteDetailInterface
     */
    public function setResponsibleLocationId(string $responsibleLocationId): RateQuoteDetailInterface;

    /**
     * @return float
     */
    public function getTaxableAmount(): float;

    /**
     * @param float $taxableAmount
     * @return RateQuoteDetailInterface
     */
    public function setTaxableAmount(float $taxableAmount): RateQuoteDetailInterface;

    /**
     * @return float
     */
    public function getTaxAmount(): float;

    /**
     * @param float $taxAmount
     * @return RateQuoteDetailInterface
     */
    public function setTaxAmount(float $taxAmount): RateQuoteDetailInterface;

    /**
     * @return float
     */
    public function getTotalAmount(): float;

    /**
     * @param float $totalAmount
     * @return RateQuoteDetailInterface
     */
    public function setTotalAmount(float $totalAmount): RateQuoteDetailInterface;

    /**
     * @return float
     */
    public function getTotalDiscountAmount(): float;

    /**
     * @param float $totalDiscountAmount
     * @return RateQuoteDetailInterface
     */
    public function setTotalDiscountAmount(float $totalDiscountAmount): RateQuoteDetailInterface;

    /**
     * @return bool
     */
    public function hasShippingDeliveryLineDiscount(): bool;

    /**
     * @return bool
     */
    public function hasCouponDiscounts(): bool;
}
