<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\RateQuote\Detail\DeliveryLine;

use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountInterface;
use Fedex\FXOPricing\Api\RateQuoteDeliveryLineDiscountBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountInterfaceFactory;

class DiscountBuilder implements RateQuoteDeliveryLineDiscountBuilderInterface
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
     * @param RateQuoteDeliveryLineDiscountInterfaceFactory $discountFactory
     */
    public function __construct(
        private readonly RateQuoteDeliveryLineDiscountInterfaceFactory $discountFactory
    ) {
    }

    /**
     * @param array $data
     * @return RateQuoteDeliveryLineDiscountInterface
     */
    public function build(array $data = []): RateQuoteDeliveryLineDiscountInterface
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
