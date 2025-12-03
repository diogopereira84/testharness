<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\RateQuote;

use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterface;
use Fedex\Base\Model\DataObject;

class Detail extends DataObject implements RateQuoteDetailInterface
{
    private const DELIVERY_LINES = 'deliveryLines';
    private const DISCOUNTS = 'discounts';
    private const ESTIMATED_VS_ACTUAL = 'estimatedVsActual';
    private const GROSS_AMOUNT = 'grossAmount';
    private const NET_AMOUNT = 'netAmount';
    private const PRODUCT_LINES = 'productLines';
    private const TAXABLE_AMOUNT = 'taxableAmount';
    private const TAX_AMOUNT = 'taxAmount';
    private const TOTAL_AMOUNT = 'totalAmount';
    private const TOTAL_DISCOUNT_AMOUNT = 'totalDiscountAmount';
    private const RATE_QUOTE_ID = 'rateQuoteId';
    private const RESPONSIBLE_LOCATION_ID = 'responsibleLocationId';

    /**
     * @inheritDoc
     */
    public function __construct(
        RateQuoteDeliveryLineCollectionInterfaceFactory $deliveryLineCollectionFactory,
        RateQuoteDiscountCollectionInterfaceFactory $discountCollectionFactory,
        array $data = []
    ) {
        if (!isset($data[self::DELIVERY_LINES]) || !is_a($data[self::DELIVERY_LINES], RateQuoteDeliveryLineCollectionInterface::class)) {
            $data[self::DELIVERY_LINES] = $deliveryLineCollectionFactory->create();
        }

        if (!isset($data[self::DISCOUNTS]) || !is_a($data[self::DISCOUNTS], RateQuoteDiscountCollectionInterface::class)) {
            $data[self::DISCOUNTS] = $discountCollectionFactory->create();
        }

        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryLines(): RateQuoteDeliveryLineCollectionInterface
    {
        return $this->getData(self::DELIVERY_LINES);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryLines(RateQuoteDeliveryLineCollectionInterface $deliveryLines): RateQuoteDetailInterface
    {
        return $this->setData(self::DELIVERY_LINES, $deliveryLines);
    }

    /**
     * @inheritDoc
     */
    public function getDiscounts(): RateQuoteDiscountCollectionInterface
    {
        return $this->getData(self::DISCOUNTS);
    }

    /**
     * @inheritDoc
     */
    public function setDiscounts(RateQuoteDiscountCollectionInterface $discounts): RateQuoteDetailInterface
    {
        return $this->setData(self::DISCOUNTS, $discounts);
    }

    /**
     * @inheritDoc
     */
    public function getEstimatedVsActual(): string
    {
        return (string)$this->getData(self::ESTIMATED_VS_ACTUAL);
    }

    /**
     * @inheritDoc
     */
    public function setEstimatedVsActual(string $estimatedVsActual): RateQuoteDetailInterface
    {
        return $this->setData(self::ESTIMATED_VS_ACTUAL, $estimatedVsActual);
    }

    /**
     * @inheritDoc
     */
    public function getGrossAmount(): float
    {
        return (float)$this->getData(self::GROSS_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setGrossAmount(float $grossAmount): RateQuoteDetailInterface
    {
        return $this->setData(self::GROSS_AMOUNT, $grossAmount);
    }

    /**
     * @inheritDoc
     */
    public function getNetAmount(): float
    {
        return (float)$this->getData(self::NET_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setNetAmount(float $netAmount): RateQuoteDetailInterface
    {
        return $this->setData(self::NET_AMOUNT, $netAmount);
    }

    /**
     * @inheritDoc
     */
    public function getProductLines(): array
    {
        return $this->getData(self::PRODUCT_LINES) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setProductLines(array $productLines): RateQuoteDetailInterface
    {
        return $this->setData(self::PRODUCT_LINES, $productLines);
    }

    /**
     * @inheritDoc
     */
    public function getTaxableAmount(): float
    {
        return (float)$this->getData(self::TAXABLE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTaxableAmount(float $taxableAmount): RateQuoteDetailInterface
    {
        return $this->setData(self::TAXABLE_AMOUNT, $taxableAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTaxAmount(): float
    {
        return (float)$this->getData(self::TAX_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTaxAmount(float $taxAmount): RateQuoteDetailInterface
    {
        return $this->setData(self::TAX_AMOUNT, $taxAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalAmount(): float
    {
        return (float)$this->getData(self::TOTAL_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalAmount(float $totalAmount): RateQuoteDetailInterface
    {
        return $this->setData(self::TOTAL_AMOUNT, $totalAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalDiscountAmount(): float
    {
        return (float)$this->getData(self::TOTAL_DISCOUNT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalDiscountAmount(float $totalDiscountAmount): RateQuoteDetailInterface
    {
        return $this->setData(self::TOTAL_DISCOUNT_AMOUNT, $totalDiscountAmount);
    }

    /**
     * @inheritDoc
     */
    public function getRateQuoteId(): string
    {
        return (string)$this->getData(self::RATE_QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRateQuoteId(string $rateQuoteId): RateQuoteDetailInterface
    {
        return $this->setData(self::RATE_QUOTE_ID, $rateQuoteId);
    }

    /**
     * @inheritDoc
     */
    public function getResponsibleLocationId(): string
    {
        return (string)$this->getData(self::RESPONSIBLE_LOCATION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setResponsibleLocationId(string $responsibleLocationId): RateQuoteDetailInterface
    {
        return $this->setData(self::RESPONSIBLE_LOCATION_ID, $responsibleLocationId);
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $keys = []): array
    {
        return [
            self::DELIVERY_LINES => ($this->getDeliveryLines()->count() > 0) ? $this->getDeliveryLines()->toArray()["items"] : [],
            self::DISCOUNTS => ($this->getDiscounts()->count() > 0) ? $this->getDiscounts()->toArray()["items"] : [],
            self::ESTIMATED_VS_ACTUAL => $this->getEstimatedVsActual(),
            self::GROSS_AMOUNT => $this->getGrossAmount(),
            self::NET_AMOUNT => $this->getNetAmount(),
            self::TAXABLE_AMOUNT => $this->getTaxableAmount(),
            self::TAX_AMOUNT => $this->getTaxAmount(),
            self::TOTAL_AMOUNT => $this->getTotalAmount(),
            self::TOTAL_DISCOUNT_AMOUNT => $this->getTotalDiscountAmount(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function hasShippingDeliveryLines(): bool
    {
        return $this->getDeliveryLines()->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function hasFreeShipping(): bool
    {
        return $this->getDeliveryLines()->getShippingDeliveryLine()->getDeliveryLineDiscounts()->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function hasShippingDeliveryLineDiscount(): bool
    {
        return $this->getDeliveryLines()->hasShippingDeliveryLineDiscounts();
    }

    /**
     * @inheritDoc
     */
    public function hasCouponDiscounts(): bool
    {
        return $this->getDiscounts()->count() > 0;
    }
}
