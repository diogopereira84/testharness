<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

use Fedex\Base\Api\Data\DataObjectInterface;

interface RateDiscountInterface extends DataObjectInterface
{
    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     * @return RateDiscountInterface
     */
    public function setType(string $type): RateDiscountInterface;

    /**
     * @return string
     */
    public function getAmount(): string;

    /**
     * @param string $amount
     * @return RateDiscountInterface
     */
    public function setAmount(string $amount): RateDiscountInterface;
}
