<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\Source;

use Fedex\SubmitOrderSidebar\Api\Data\DataSourceInterface;
use Magento\Sales\Model\Order\Item;

class ThirdPartyDeliveryDataSource extends DeliveryDataSource implements DataSourceInterface
{
    /**
     * @var null
     */
    protected $shopId = null;

    /**
     * Set shopId to be split by vendor
     *
     * @param string $shopId
     * @return void
     */
    public function setShopId(string $shopId)
    {
        $this->shopId = $shopId;
    }

    /**
     * @inheritDoc
     */
    protected function filterOrderItems(Item $item): bool
    {
        return $item->getMiraklShopId() == $this->shopId;
    }

    /**
     * @inheritDoc
     */
    protected function getProducerType(Item $item): string
    {
        return $item->getMiraklShopName();
    }
}
