<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine;

use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountInterface;
use Fedex\Base\Model\DataObject;

class Discount extends DataObject implements RateDeliveryLineDiscountInterface
{
    /**
     * Amount key
     */
    private const AMOUNT = 'amount';

    /**
     * Type key
     */
    private const TYPE = 'type';

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): RateDeliveryLineDiscountInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): string
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount(string $amount): RateDeliveryLineDiscountInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }
}
