<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Rate\Detail\DeliveryLine;

use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountInterface;
use Fedex\FXOPricing\Api\RateDeliveryLineDiscountBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountInterfaceFactory;

class DiscountBuilder implements RateDeliveryLineDiscountBuilderInterface
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
     * @param RateDeliveryLineDiscountInterfaceFactory $discountFactory
     */
    public function __construct(
        private readonly RateDeliveryLineDiscountInterfaceFactory $discountFactory
    ) {
    }

    /**
     * @param array $data
     * @return RateDeliveryLineDiscountInterface
     */
    public function build(array $data = []): RateDeliveryLineDiscountInterface
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
