<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Rate;

use Fedex\FXOPricing\Api\Data\RateDetailInterface;
use Fedex\FXOPricing\Api\RateDetailBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateDetailInterfaceFactory;
use Fedex\FXOPricing\Api\RateDeliveryLineBuilderInterface;
use Fedex\FXOPricing\Api\RateDiscountBuilderInterface;

class DetailBuilder implements RateDetailBuilderInterface
{
    /**
     * Discounts collection key
     */
    private const DISCOUNTS = 'discounts';

    /**
     * Estimated vs actual key
     */
    private const ESTIMATED_VS_ACTUAL = 'estimatedVsActual';

    /**
     * Gross amount key
     */
    private const GROSS_AMOUNT = 'grossAmount';

    /**
     * Net amount key
     */
    private const NET_AMOUNT = 'netAmount';

    /**
     * Taxable amount key
     */
    private const TAXABLE_AMOUNT = 'taxableAmount';

    /**
     * Tax amount key
     */
    private const TAX_AMOUNT = 'taxAmount';

    /**
     * Total amount key
     */
    private const TOTAL_AMOUNT = 'totalAmount';

    /**
     * Total discount amount key
     */
    private const TOTAL_DISCOUNT_AMOUNT = 'totalDiscountAmount';

    /**
     * Delivery line key
     */
    private const DELIVERY_LINES = 'deliveryLines';

    public function __construct(
        private readonly RateDeliveryLineCollectionInterfaceFactory $deliveryLineCollectionFactory,
        private readonly RateDiscountCollectionInterfaceFactory $discountCollectionFactory,
        private readonly RateDetailInterfaceFactory $detailFactory,
        private readonly RateDeliveryLineBuilderInterface $deliveryLineBuilder,
        private readonly RateDiscountBuilderInterface $discountBuilder
    ) {
    }

    public function build(array $data = []): RateDetailInterface
    {
        $detail = $this->detailFactory->create();
        $deliveryLineCollection = $this->deliveryLineCollectionFactory->create();
        $discountCollection = $this->discountCollectionFactory->create();

        if (isset($data[self::DELIVERY_LINES]) && is_array($data[self::DELIVERY_LINES])) {
            foreach ($data[self::DELIVERY_LINES] as $deliveryLineData) {
                $deliveryLine = $this->deliveryLineBuilder->build($deliveryLineData);
                $deliveryLineCollection->addItem($deliveryLine);
            }
            $detail->setDeliveryLines($deliveryLineCollection);
        }

        if (isset($data[self::DISCOUNTS]) && is_array($data[self::DISCOUNTS])) {
            foreach ($data[self::DISCOUNTS] as $discountData) {
                $discount = $this->discountBuilder->build($discountData);
                $discountCollection->addItem($discount);
            }
            $detail->setDiscounts($discountCollection);
        }

        if (isset($data[self::ESTIMATED_VS_ACTUAL])) {
            $detail->setEstimatedVsActual((string)$data[self::ESTIMATED_VS_ACTUAL]);
        }

        if (isset($data[self::GROSS_AMOUNT])) {
            $detail->setGrossAmount((string)$data[self::GROSS_AMOUNT]);
        }

        if (isset($data[self::NET_AMOUNT])) {
            $detail->setNetAmount((string)$data[self::NET_AMOUNT]);
        }

        if (isset($data[self::TAXABLE_AMOUNT])) {
            $detail->setTaxableAmount((string)$data[self::TAXABLE_AMOUNT]);
        }

        if (isset($data[self::TAX_AMOUNT])) {
            $detail->setTaxAmount((string)$data[self::TAX_AMOUNT]);
        }

        if (isset($data[self::TOTAL_AMOUNT])) {
            $detail->setTotalAmount((string)$data[self::TOTAL_AMOUNT]);
        }

        if (isset($data[self::TOTAL_DISCOUNT_AMOUNT])) {
            $detail->setTotalDiscountAmount((string)$data[self::TOTAL_DISCOUNT_AMOUNT]);
        }

        return $detail;
    }
}
