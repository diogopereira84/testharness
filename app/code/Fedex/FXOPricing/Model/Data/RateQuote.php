<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data;

use Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use Fedex\Base\Model\DataObject;

class RateQuote extends DataObject implements RateQuoteInterface
{
    /**
     * Currency key
     */
    private const CURRENCY = 'currency';

    /**
     * Details key
     */
    private const RATE_QUOTE_DETAILS = 'rateQuoteDetails';

    /**
     * @inheritDoc
     */
    public function __construct(
        RateQuoteDetailCollectionInterfaceFactory $detailCollectionFactory,
        array $data = []
    ) {
        if (!isset($data[self::RATE_QUOTE_DETAILS]) || !is_a($data[self::RATE_QUOTE_DETAILS], RateQuoteDetailCollectionInterface::class)) {
            $data[self::RATE_QUOTE_DETAILS] = $detailCollectionFactory->create();
        }
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return $this->getData(self::CURRENCY);
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): RateQuoteInterface
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @inheritDoc
     */
    public function getDetails(): RateQuoteDetailCollectionInterface
    {
        return $this->getData(self::RATE_QUOTE_DETAILS);
    }

    /**
     * @inheritDoc
     */
    public function setDetails(RateQuoteDetailCollectionInterface $details): RateQuoteInterface
    {
        return $this->setData(self::RATE_QUOTE_DETAILS, $details);
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $keys = []): array
    {
        return [
            self::CURRENCY => $this->getCurrency(),
            self::RATE_QUOTE_DETAILS => ($this->getDetails()->count() > 0) ? $this->getDetails()->toArray()["items"] : [],
        ];
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
     * @codeCoverageIgnore
     */
    public function hasMultiplePromotion(): bool
    {
        $couponDiscount = false;
        $accountDiscount = false;
        if (!empty($this->getDetails()->getFirstItem()->getDiscounts())) {
            foreach ($this->getDetails()->getFirstItem()->getDiscounts() as $discount) {
                if ($discount->getType() == 'COUPON') {
                    $couponDiscount = true;
                }
                if ($discount->getType() == 'AR_CUSTOMERS' || $discount->getType() == 'CORPORATE') {
                    $accountDiscount = true;
                }
            }
        }

        return $accountDiscount && $couponDiscount;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function hasSinglePromotion(): bool
    {
        $couponDiscount = false;
        $accountDiscount = false;
        if (!empty($this->getDetails()->getFirstItem()->getDiscounts())) {
            foreach ($this->getDetails()->getFirstItem()->getDiscounts() as $discount) {
                if ($discount->getType() == 'COUPON') {
                    $couponDiscount = true;
                }
                if ($discount->getType() == 'AR_CUSTOMERS' || $discount->getType() == 'CORPORATE') {
                    $accountDiscount = true;
                }
            }
        }

        return (!$accountDiscount && $couponDiscount) || ($accountDiscount && !$couponDiscount);
    }
}
