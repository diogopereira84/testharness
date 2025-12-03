<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\Rate;

use Fedex\Base\Api\PriceEscaperInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDetailInterface;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterfaceFactory;
use Fedex\Base\Model\DataObject;

class Detail extends DataObject implements RateDetailInterface
{
    /**
     * Delivery line key
     */
    private const DELIVERY_LINES = 'deliveryLines';

    /**
     * Discounts collection key
     */
    private const DISCOUNTS = 'discounts';

    /**
     * Estimated vs actual key
     */
    private const ESTIMATED_VS_ACTUAL = 'estimatedVsActual';

    /**
     * Gross amount key
     */
    private const GROSS_AMOUNT = 'grossAmount';

    /**
     * Net amount key
     */
    private const NET_AMOUNT = 'netAmount';

    /**
     * Product lines key
     */
    private const PRODUCT_LINES = 'productLines';

    /**
     * Taxable amount key
     */
    private const TAXABLE_AMOUNT = 'taxableAmount';

    /**
     * Tax amount key
     */
    private const TAX_AMOUNT = 'taxAmount';

    /**
     * Total amount key
     */
    private const TOTAL_AMOUNT = 'totalAmount';

    /**
     * Total discount amount key
     */
    private const TOTAL_DISCOUNT_AMOUNT = 'totalDiscountAmount';


    /**
     * @inheritDoc
     */
    public function __construct(
        RateDeliveryLineCollectionInterfaceFactory $deliveryLineCollectionFactory,
        RateDiscountCollectionInterfaceFactory $discountCollectionFactory,
        private readonly PriceEscaperInterface $priceEscaper,
        array $data = []
    ) {

        if (!isset($data[self::DELIVERY_LINES]) || !is_a($data[self::DELIVERY_LINES], RateDeliveryLineCollectionInterface::class)) {
            $data[self::DELIVERY_LINES] = $deliveryLineCollectionFactory->create();
        }

        if (!isset($data[self::DISCOUNTS]) || !is_a($data[self::DISCOUNTS], RateDiscountCollectionInterface::class)) {
            $data[self::DISCOUNTS] = $discountCollectionFactory->create();
        }

        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryLines(): RateDeliveryLineCollectionInterface
    {
        return $this->getData(self::DELIVERY_LINES);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryLines(RateDeliveryLineCollectionInterface $deliveryLines): RateDetailInterface
    {
        return $this->setData(self::DELIVERY_LINES, $deliveryLines);
    }

    /**
     * @inheritDoc
     */
    public function getDiscounts(): RateDiscountCollectionInterface
    {
        return $this->getData(self::DISCOUNTS);
    }

    /**
     * @inheritDoc
     */
    public function setDiscounts(RateDiscountCollectionInterface $discounts): RateDetailInterface
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
    public function setEstimatedVsActual(string $estimatedVsActual): RateDetailInterface
    {
        return $this->setData(self::ESTIMATED_VS_ACTUAL, $estimatedVsActual);
    }

    /**
     * @inheritDoc
     */
    public function getGrossAmount(): string
    {
        return (string)$this->getData(self::GROSS_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setGrossAmount(string $grossAmount): RateDetailInterface
    {
        return $this->setData(self::GROSS_AMOUNT, $grossAmount);
    }

    /**
     * @inheritDoc
     */
    public function getNetAmount(): string
    {
        return (string)$this->getData(self::NET_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setNetAmount(string $netAmount): RateDetailInterface
    {
        return $this->setData(self::NET_AMOUNT, $netAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTaxableAmount(): string
    {
        return (string)$this->getData(self::TAXABLE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTaxableAmount(string $taxableAmount): RateDetailInterface
    {
        return $this->setData(self::TAXABLE_AMOUNT, $taxableAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTaxAmount(): string
    {
        return (string)$this->getData(self::TAX_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTaxAmount(string $taxAmount): RateDetailInterface
    {
        return $this->setData(self::TAX_AMOUNT, $taxAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalAmount(): string
    {
        return (string)$this->getData(self::TOTAL_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalAmount(string $totalAmount): RateDetailInterface
    {
        return $this->setData(self::TOTAL_AMOUNT, $totalAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalDiscountAmount(): string
    {
        return (string)$this->getData(self::TOTAL_DISCOUNT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalDiscountAmount(string $totalDiscountAmount): RateDetailInterface
    {
        return $this->setData(self::TOTAL_DISCOUNT_AMOUNT, $totalDiscountAmount);
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $keys = []): array
    {
        return [
            self::DELIVERY_LINES => ($this->getDeliveryLines()->count() > 0) ? $this->getDeliveryLines()->toArray()["items"] : [],
            self::DISCOUNTS => $this->getDiscounts()->toArrayItems(),
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
        return $this->getDeliveryLines()->getShippingDeliveryLine()->getDeliveryDiscountAmount() > 0;
    }

    /**
     * @inheritDoc
     */
    public function compareShippingDeliveryLineDiscounts(float|int $amount): bool
    {
        $deliveryDiscountAmount = $this->priceEscaper->escape(
            $this->getDeliveryLines()->getShippingDeliveryLine()
            ->getDeliveryDiscountAmount()
        );

        return $deliveryDiscountAmount == $amount;
    }
}
