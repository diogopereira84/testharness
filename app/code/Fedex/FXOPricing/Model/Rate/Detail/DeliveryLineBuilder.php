<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Rate\Detail;

use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface;
use Fedex\FXOPricing\Api\RateDeliveryLineBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\RateDeliveryLineDiscountBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterfaceFactory;

class DeliveryLineBuilder implements RateDeliveryLineBuilderInterface
{
    /**
     * Delivery Discount amount key
     */
    private const DELIVERY_DISCOUNT_AMOUNT = 'deliveryDiscountAmount';

    /**
     * Delivery line discounts key
     */
    private const DELIVERY_LINE_DISCOUNTS = 'deliveryLineDiscounts';

    /**
     * Delivery line price key
     */
    private const DELIVERY_LINE_PRICE = 'deliveryLinePrice';

    /**
     * Delivery type key
     */
    private const DELIVERY_LINE_TYPE = 'deliveryLineType';

    /**
     * Delivery retail price key
     */
    private const DELIVERY_RETAIL_PRICE = 'deliveryRetailPrice';

    /**
     * Estimated delivery local time key
     */
    private const ESTIMATED_DELIVERY_LOCAL_TIME = 'estimatedDeliveryLocalTime';

    /**
     * Estimated ship date key
     */
    private const ESTIMATED_SHIP_DATE = 'estimatedShipDate';

    /**
     * Priceable key
     */
    private const PRICEABLE = 'priceable';

    /**
     * Recipient reference key
     */
    private const RECIPIENT_REFERENCE = 'recipientReference';

    public function __construct(
        private readonly RateDeliveryLineDiscountCollectionInterfaceFactory $deliveryLineDiscountCollectionFactory,
        private readonly RateDeliveryLineInterfaceFactory $deliveryLineFactory,
        private readonly RateDeliveryLineDiscountBuilderInterface $discountBuilder,
    ) {
    }

    public function build(array $data = []): RateDeliveryLineInterface
    {
        $deliveryLine = $this->deliveryLineFactory->create();

        if (isset($data[self::DELIVERY_LINE_DISCOUNTS]) && is_array($data[self::DELIVERY_LINE_DISCOUNTS])) {
            $deliveryLineDiscountCollection = $this->deliveryLineDiscountCollectionFactory->create();
            foreach ($data[self::DELIVERY_LINE_DISCOUNTS] as $discount) {
                $deliveryLineDiscount = $this->discountBuilder->build($discount);
                $deliveryLineDiscountCollection->addItem($deliveryLineDiscount);
            }

            $deliveryLine->setDeliveryLineDiscounts($deliveryLineDiscountCollection);
        }

        if (isset($data[self::DELIVERY_DISCOUNT_AMOUNT])) {
            $deliveryLine->setDeliveryDiscountAmount((string)$data[self::DELIVERY_DISCOUNT_AMOUNT]);
        }
        if (isset($data[self::DELIVERY_LINE_PRICE])) {
            $deliveryLine->setDeliveryLinePrice((string)$data[self::DELIVERY_LINE_PRICE]);
        }
        if (isset($data[self::DELIVERY_LINE_TYPE])) {
            $deliveryLine->setDeliveryLineType((string)$data[self::DELIVERY_LINE_TYPE]);
        }
        if (isset($data[self::DELIVERY_RETAIL_PRICE])) {
            $deliveryLine->setDeliveryRetailPrice((string)$data[self::DELIVERY_RETAIL_PRICE]);
        }
        if (isset($data[self::ESTIMATED_DELIVERY_LOCAL_TIME])) {
            $deliveryLine->setEstimatedDeliveryLocalTime((string)$data[self::ESTIMATED_DELIVERY_LOCAL_TIME]);
        }
        if (isset($data[self::ESTIMATED_SHIP_DATE])) {
            $deliveryLine->setEstimatedShipDate((string)$data[self::ESTIMATED_SHIP_DATE]);
        }
        if (isset($data[self::PRICEABLE])) {
            $deliveryLine->setPriceable((bool)$data[self::PRICEABLE]);
        }
        if (isset($data[self::RECIPIENT_REFERENCE])) {
            $deliveryLine->setRecipientReference((string)$data[self::RECIPIENT_REFERENCE]);
        }

        return $deliveryLine;
    }
}
