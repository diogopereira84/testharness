<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\RateQuote\Detail;

use Fedex\FXOPricing\Api\Data\RateQuoteDiscountInterface;
use Fedex\FXOPricing\Api\RateQuoteDiscountBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDiscountInterfaceFactory;

class DiscountBuilder implements RateQuoteDiscountBuilderInterface
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
     * @param RateQuoteDiscountInterfaceFactory $discountFactory
     */
    public function __construct(
        private readonly RateQuoteDiscountInterfaceFactory $discountFactory
    ) {
    }

    /**
     * @param array $data
     * @return RateQuoteDiscountInterface
     */
    public function build(array $data = []): RateQuoteDiscountInterface
    {
        $deliveryLineDiscount = $this->discountFactory->create();

        if (isset($data[self::TYPE])) {
            $deliveryLineDiscount->setType($data[self::TYPE]);
        }

        if (isset($data[self::AMOUNT])) {
            $deliveryLineDiscount->setAmount($data[self::AMOUNT]);
        }

        return $deliveryLineDiscount;
    }
}
