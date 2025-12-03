<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\RateQuote\Detail;

use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineInterface;
use Fedex\FXOPricing\Api\RateQuoteDeliveryLineBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineDiscountCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\RateQuoteDeliveryLineDiscountBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDeliveryLineInterfaceFactory;

class DeliveryLineBuilder implements RateQuoteDeliveryLineBuilderInterface
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
        private readonly RateQuoteDeliveryLineDiscountCollectionInterfaceFactory $deliveryLineDiscountCollectionFactory,
        private readonly RateQuoteDeliveryLineInterfaceFactory $deliveryLineFactory,
        private readonly RateQuoteDeliveryLineDiscountBuilderInterface $discountBuilder,
    ) {
    }

    public function build(array $data = []): RateQuoteDeliveryLineInterface
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
            $deliveryLine->setDeliveryDiscountAmount((float)$data[self::DELIVERY_DISCOUNT_AMOUNT]);
        }
        if (isset($data[self::DELIVERY_LINE_PRICE])) {
            $deliveryLine->setDeliveryLinePrice((float)$data[self::DELIVERY_LINE_PRICE]);
        }
        if (isset($data[self::DELIVERY_LINE_TYPE])) {
            $deliveryLine->setDeliveryLineType((string)$data[self::DELIVERY_LINE_TYPE]);
        }
        if (isset($data[self::DELIVERY_RETAIL_PRICE])) {
            $deliveryLine->setDeliveryRetailPrice((float)$data[self::DELIVERY_RETAIL_PRICE]);
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
