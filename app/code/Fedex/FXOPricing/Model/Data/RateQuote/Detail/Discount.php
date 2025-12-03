<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\RateQuote\Detail;

use Fedex\FXOPricing\Api\Data\RateQuoteDiscountInterface;
use Fedex\Base\Model\DataObject;

class Discount extends DataObject implements RateQuoteDiscountInterface
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
        return (string)$this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): RateQuoteDiscountInterface
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
    public function setAmount(float $amount): RateQuoteDiscountInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }
}
