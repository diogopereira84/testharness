<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\RateQuote\Detail\Discount;

use Fedex\FXOPricing\Api\Data\RateQuoteDiscountCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountInterface;

class Collection extends \Fedex\Base\Model\Data\Collection implements RateQuoteDiscountCollectionInterface
{
    /**
     * @var RateQuoteDiscountInterface
     */
    protected $_itemObjectClass = RateQuoteDiscountInterface::class;

    /**
     * Discount type KEY
     */
    private const DISCOUNT_TYPE_KEY = 'type';

    /**
     * Discount type COUPON
     */
    private const DISCOUNT_TYPE_COUPON = 'COUPON';

    /**
     * Discount type AR_CUSTOMERS
     */
    private const DISCOUNT_TYPE_ARS_CUSTOMER = 'AR_CUSTOMERS';

    /**
     * @inheritDoc
     */
    public function getItemByType(string $type): RateQuoteDiscountInterface
    {
        return $this->getItemByColumnValue(
            self::DISCOUNT_TYPE_KEY,
            $type
        ) ?? $this->_entityFactory->create($this->_itemObjectClass);
    }

    /**
     * @inheritDoc
     */
    public function getCouponDiscount(): RateQuoteDiscountInterface
    {
        return $this->getItemByType(self::DISCOUNT_TYPE_COUPON);
    }

    /**
     * @inheritDoc
     */
    public function getArCustomersDiscount(): RateQuoteDiscountInterface
    {
        return $this->getItemByType(self::DISCOUNT_TYPE_ARS_CUSTOMER);
    }

    public function hasCouponDiscount(): bool
    {
        return $this->getCouponDiscount()->getType() === self::DISCOUNT_TYPE_COUPON;
    }

    public function hasArCustomersDiscount(): bool
    {
        return $this->getArCustomersDiscount()->getType() === self::DISCOUNT_TYPE_ARS_CUSTOMER;
    }

}
