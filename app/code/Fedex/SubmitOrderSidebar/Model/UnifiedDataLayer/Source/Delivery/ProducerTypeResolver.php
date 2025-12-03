<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source\Delivery;

use Magento\Sales\Model\Order\Item;

class ProducerTypeResolver
{
    /**
     * Producer type for FedEx Office
     */
    private const PRODUCER_TYPE_FEDEX = 'FedEx Office';

    /**
     * Return the order producer type
     *
     * @param Item $item
     *
     * @return string
     */
    public function resolve(Item $item): string
    {
        return $item->getMiraklOfferId()
            ? $item->getMiraklShopName()
            : self::PRODUCER_TYPE_FEDEX;
    }
}
