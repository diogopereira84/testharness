<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\RateQuote\Detail\DeliveryLine;

use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountInterface;
use Fedex\Base\Model\DataObject;

class Discount extends DataObject implements RateQuoteDeliveryLineDiscountInterface
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
    public function setType(string $type): RateQuoteDeliveryLineDiscountInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): float
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount(float $amount): RateQuoteDeliveryLineDiscountInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }
}
