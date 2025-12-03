<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Rate\Detail;

use Fedex\FXOPricing\Api\Data\RateDiscountInterface;
use Fedex\FXOPricing\Api\RateDiscountBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDiscountInterfaceFactory;

class DiscountBuilder implements RateDiscountBuilderInterface
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
     * @param RateDiscountInterfaceFactory $discountFactory
     */
    public function __construct(
        private readonly RateDiscountInterfaceFactory $discountFactory
    ) {
    }

    /**
     * @param array $data
     * @return RateDiscountInterface
     */
    public function build(array $data = []): RateDiscountInterface
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
