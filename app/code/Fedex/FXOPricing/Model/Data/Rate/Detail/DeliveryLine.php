<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\Rate\Detail;

use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface;
use Fedex\Base\Model\DataObject;

class DeliveryLine extends DataObject implements RateDeliveryLineInterface
{
    /**
     * Delivery Discount amount key
     */
    private const DELIVERY_DISCOUNT_AMOUNT = 'deliveryDiscountAmount';

    /**
     * Delivery line discounts key
     */
    private const DELIVERY_LINE_DISCOUNTS = 'deliveryLineDiscounts';

    /**
     * Delivery line price key
     */
    private const DELIVERY_LINE_PRICE = 'deliveryLinePrice';

    /**
     * Delivery type key
     */
    private const DELIVERY_LINE_TYPE = 'deliveryLineType';

    /**
     * Delivery retail price key
     */
    private const DELIVERY_RETAIL_PRICE = 'deliveryRetailPrice';

    /**
     * Estimated delivery local time key
     */
    private const ESTIMATED_DELIVERY_LOCAL_TIME = 'estimatedDeliveryLocalTime';

    /**
     * Estimated ship date key
     */
    private const ESTIMATED_SHIP_DATE = 'estimatedShipDate';

    /**
     * Priceable key
     */
    private const PRICEABLE = 'priceable';

    /**
     * Recipient reference key
     */
    private const RECIPIENT_REFERENCE = 'recipientReference';

    /**
     * @inheritDoc
     */
    public function __construct(
        RateDeliveryLineDiscountCollectionInterfaceFactory $discountCollectionFactory,
        array $data = []
    ) {
        if (!isset($data[self::DELIVERY_LINE_DISCOUNTS]) || !is_a($data[self::DELIVERY_LINE_DISCOUNTS], RateDeliveryLineDiscountCollectionInterface::class)) {
            $data[self::DELIVERY_LINE_DISCOUNTS] = $discountCollectionFactory->create();
        }
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryDiscountAmount(): string
    {
        return (string)$this->getData(self::DELIVERY_DISCOUNT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryDiscountAmount(string $deliveryDiscountAmount): RateDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_DISCOUNT_AMOUNT, $deliveryDiscountAmount);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryLineDiscounts(): RateDeliveryLineDiscountCollectionInterface
    {
        return $this->getData(self::DELIVERY_LINE_DISCOUNTS);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryLineDiscounts(RateDeliveryLineDiscountCollectionInterface $deliveryLineDiscounts): RateDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_LINE_DISCOUNTS, $deliveryLineDiscounts);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryLinePrice(): string
    {
        return (string)$this->getData(self::DELIVERY_LINE_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryLinePrice(string $deliveryLinePrice): RateDeliveryLineInterface
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
    public function setDeliveryLineType(string $deliveryLineType): RateDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_LINE_TYPE, $deliveryLineType);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryRetailPrice(): string
    {
        return (string)$this->getData(self::DELIVERY_RETAIL_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryRetailPrice(string $deliveryRetailPrice): RateDeliveryLineInterface
    {
        return $this->setData(self::DELIVERY_RETAIL_PRICE, $deliveryRetailPrice);
    }

    /**
     * @inheritDoc
     */
    public function getEstimatedDeliveryLocalTime(): string
    {
        return (string)$this->getData(self::ESTIMATED_DELIVERY_LOCAL_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setEstimatedDeliveryLocalTime(string $estimatedDeliveryLocalTime): RateDeliveryLineInterface
    {
        return $this->setData(self::ESTIMATED_DELIVERY_LOCAL_TIME, $estimatedDeliveryLocalTime);
    }

    /**
     * @inheritDoc
     */
    public function getEstimatedShipDate(): string
    {
        return (string)$this->getData(self::ESTIMATED_SHIP_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setEstimatedShipDate(string $estimatedShipDate): RateDeliveryLineInterface
    {
        return $this->setData(self::ESTIMATED_SHIP_DATE, $estimatedShipDate);
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
    public function setPriceable(bool $priceable): RateDeliveryLineInterface
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
    public function setRecipientReference(string $recipientReference): RateDeliveryLineInterface
    {
        return $this->setData(self::RECIPIENT_REFERENCE, $recipientReference);
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $keys = []): array
    {
        return array_merge(
            parent::toArray(), [
                self::DELIVERY_LINE_DISCOUNTS => $this->getDeliveryLineDiscounts()->toArrayItems(),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function hasDiscounts(): bool
    {
        return $this->getDeliveryLineDiscounts()->count() > 0;
    }
}
