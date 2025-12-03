<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\DataSourceInterface;
use Magento\Sales\Model\Order\Item;

class FirstPartyDeliveryDataSource extends DeliveryDataSource implements DataSourceInterface
{
    /**
     * Producer type for FedEx Office
     */
    private const PRODUCER_TYPE = 'FedEx Office';

    /**
     * @inheritDoc
     */
    protected function filterOrderItems(Item $item): bool
    {
        return $item->getMiraklOfferId() == null;
    }

    /**
     * @inheritDoc
     */
    protected function getProducerType(Item $item): string
    {
        return self::PRODUCER_TYPE;
    }
}
