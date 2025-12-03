<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\Rate\Detail\DeliveryLine;

use Fedex\FXOPricing\Api\Data\RateDeliveryLineCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateDeliveryLineInterface;

class Collection extends \Fedex\Base\Model\Data\Collection implements RateDeliveryLineCollectionInterface
{
    /**
     * Delivery line type KEY
     */
    private const DELIVERY_LIVE_TYPE_KEY = 'deliveryLineType';

    /**
     * Delivery line type shipping
     */
    private const DELIVERY_LIVE_TYPE_SHIPPING = 'SHIPPING';

    /**
     * Delivery line type packing and handling
     */
    private const DELIVERY_LIVE_TYPE_PACKING_AND_HANDLING = 'PACKING_AND_HANDLING';

    /**
     * @inheritDoc
     */
    protected $_itemObjectClass = RateDeliveryLineInterface::class;

    /**
     * @inheritDoc
     */
    public function getItemByType(string $type): RateDeliveryLineInterface
    {
        return $this->getItemByColumnValue(
            self::DELIVERY_LIVE_TYPE_KEY,
            $type
        ) ?? $this->_entityFactory->create($this->_itemObjectClass);
    }

    /**
     * @inheritDoc
     */
    public function getShippingDeliveryLine(): RateDeliveryLineInterface
    {
        return $this->getItemByType(self::DELIVERY_LIVE_TYPE_SHIPPING);
    }

    /**
     * @inheritDoc
     */
    public function getPackingAndHandlingDeliveryLine(): RateDeliveryLineInterface
    {
        return $this->getItemByType(self::DELIVERY_LIVE_TYPE_PACKING_AND_HANDLING);
    }

    /**
     * @inheritDoc
     */
    public function hasShippingDeliveryLineDiscounts(): bool
    {
        return $this->getShippingDeliveryLine()->hasDiscounts();
    }
}
