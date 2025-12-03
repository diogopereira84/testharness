<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\RateQuote\Detail;

use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineInterface;
use Fedex\Base\Model\DataObject;

class DeliveryLine extends DataObject implements RateQuoteDeliveryLineInterface
{
    private const DELIVERY_DISCOUNT_AMOUNT = 'deliveryDiscountAmount';
    private const DELIVERY_LINE_DISCOUNTS = 'deliveryLineDiscounts';
    private const DELIVERY_LINE_PRICE = 'deliveryLinePrice';
    private const DELIVERY_LINE_TYPE = 'deliveryLineType';
    private const DELIVERY_RETAIL_PRICE = 'deliveryRetailPrice';
    private const PRICEABLE = 'priceable';
    private const RECIPIENT_REFERENCE = 'recipientReference';

    /**
     * @inheritDoc
     */
    public function __construct(
        RateQuoteDeliveryLineDiscountCollectionInterfaceFactory $discountCollectionFactory,
        array $data = []
    ) {
        if (!isset($data[self::DELIVERY_LINE_DISCOUNTS]) || !is_a($data[self::DELIVERY_LINE_DISCOUNTS], RateQuoteDeliveryLineDiscountCollectionInterface::class)) {
            $data[self::DELIVERY_LINE_DISCOUNTS] = $discountCollectionFactory->create();
        }
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryDiscountAmount(): float
    {
        return (float)$this->getData(self::DELIVERY_DISCOUNT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryDiscountAmount(float $deliveryDiscountAmount): RateQuoteDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_DISCOUNT_AMOUNT, $deliveryDiscountAmount);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryLineDiscounts(): RateQuoteDeliveryLineDiscountCollectionInterface
    {
        return $this->getData(self::DELIVERY_LINE_DISCOUNTS);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryLineDiscounts(RateQuoteDeliveryLineDiscountCollectionInterface $deliveryLineDiscounts): RateQuoteDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_LINE_DISCOUNTS, $deliveryLineDiscounts);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryLinePrice(): float
    {
        return (float)$this->getData(self::DELIVERY_LINE_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryLinePrice(float $deliveryLinePrice): RateQuoteDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_LINE_PRICE, $deliveryLinePrice);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryLineType(): string
    {
        return (string)$this->getData(self::DELIVERY_LINE_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryLineType(string $deliveryLineType): RateQuoteDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_LINE_TYPE, $deliveryLineType);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryRetailPrice(): float
    {
        return (float)$this->getData(self::DELIVERY_RETAIL_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryRetailPrice(float $deliveryRetailPrice): RateQuoteDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_RETAIL_PRICE, $deliveryRetailPrice);
    }

    /**
     * @inheritDoc
     */
    public function getPriceable(): bool
    {
        return (bool)$this->getData(self::PRICEABLE);
    }

    /**
     * @inheritDoc
     */
    public function setPriceable(bool $priceable): RateQuoteDeliveryLineInterface
    {
        return $this->setData(self::PRICEABLE, $priceable);
    }

    /**
     * @inheritDoc
     */
    public function getRecipientReference(): string
    {
        return (string)$this->getData(self::RECIPIENT_REFERENCE);
    }

    /**
     * @inheritDoc
     */
    public function setRecipientReference(string $recipientReference): RateQuoteDeliveryLineInterface
    {
        return $this->setData(self::RECIPIENT_REFERENCE, $recipientReference);
    }

    /**
     * @inheritDoc
     */
    public function hasDiscounts(): bool
    {
        return $this->getDeliveryLineDiscounts()->count() > 0;
    }
}
