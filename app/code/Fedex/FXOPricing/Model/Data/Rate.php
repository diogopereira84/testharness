<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data;

use Fedex\Base\Api\PriceEscaperInterface;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDetailInterface;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\Base\Model\DataObject;

class Rate extends DataObject implements RateInterface
{
    /**
     * Currency key
     */
    private const CURRENCY = 'currency';

    /**
     * Details key
     */
    private const RATE_DETAILS = 'rateDetails';

    /**
     * @inheritDoc
     */
    public function __construct(
        RateDetailCollectionInterfaceFactory $detailCollectionFactory,
        private readonly PriceEscaperInterface $priceEscaper,
        array $data = []
    ) {

        if (!isset($data[self::RATE_DETAILS]) || !is_a($data[self::RATE_DETAILS], RateDetailCollectionInterface::class)) {
            $data[self::RATE_DETAILS] = $detailCollectionFactory->create();
        }

        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return (string)$this->getData(self::CURRENCY);
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): RateInterface
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @inheritDoc
     */
    public function getDetails(): RateDetailCollectionInterface
    {
        return $this->getData(self::RATE_DETAILS);
    }

    /**
     * @inheritDoc
     */
    public function setDetails(RateDetailCollectionInterface $details): RateInterface
    {
        return $this->setData(self::RATE_DETAILS, $details);
    }

    /**
     * @inheritDoc
     */
    public function hasDetailShippingDeliveryLine(): bool
    {
        return $this->getDetails()->getFirstItem()->hasShippingDeliveryLines();
    }

    /**
     * @inheritDoc
     */
    public function hasDetailFreeShipping(): bool
    {
        return $this->hasDetailShippingDeliveryLine()
            && $this->getDetails()->getFirstItem()->hasFreeShipping();
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $keys = []): array
    {
        return [
            self::CURRENCY => $this->getCurrency(),
            self::RATE_DETAILS => $this->getDetails()->toArrayItems(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function hasDetailShippingDeliveryDiscount(): bool
    {
        return $this->getDetails()->getFirstItem()->hasShippingDeliveryLineDiscount();
    }

    /**
     * @inheritDoc
     */
    public function hasDetailCouponDiscounts(): bool
    {
        return $this->getDetails()->getFirstItem()->hasCouponDiscounts();
    }

    /**
     * @inheritDoc
     */
    public function hasFreeGroundShipping(): bool
    {
        return $this->getDetails()->getFirstItem()->hasFreeGroundShipping();
    }

    /**
     * @inheritDoc
     */
    public function isDetailCouponDiscountSameAsShippingDeliveryLineDiscount(): bool
    {
        /** @var RateDetailInterface $detail */
        $detail = $this->getDetails()->getFirstItem();
        return $detail->compareShippingDeliveryLineDiscounts(
            $this->priceEscaper->escape($detail->getTotalDiscountAmount())
        );
    }
}
