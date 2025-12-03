<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\Alert;

use Fedex\FXOPricing\Api\Data\AlertInterface;
use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;

class Collection extends \Fedex\Base\Model\Data\Collection implements AlertCollectionInterface
{
    /**
     * Code key
     */
    private const CODE = 'code';

    /**
     * Invalid coupon code
     */
    private const CODE_COUPONS_CODE_INVALID = 'COUPONS.CODE.INVALID';

    /**
     * @inheritDoc
     */
    protected $_itemObjectClass = AlertInterface::class;

    /**
     * @inheritDoc
     */
    public function getItemByCode(string $type): AlertInterface
    {
        return $this->getItemByColumnValue(
            self::CODE,
            $type
        ) ?? $this->_entityFactory->create($this->_itemObjectClass);
    }

    /**
     * @inheritDoc
     */
    public function hasAlerts(): bool
    {
        return $this->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function getCouponCodeInvalid(): AlertInterface
    {
        return $this->getItemByCode(self::CODE_COUPONS_CODE_INVALID);
    }

    /**
     * @inheritDoc
     */
    public function hasInvalidCouponCode(): bool
    {
        return $this->getCouponCodeInvalid()->getCode() === self::CODE_COUPONS_CODE_INVALID;
    }
}
