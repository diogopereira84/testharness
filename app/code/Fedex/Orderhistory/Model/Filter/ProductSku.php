<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Orderhistory\Model\Filter;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * Class ProductSku.
 *
 * Model for 'Product Name' filter for order search filter.
 */
class ProductSku extends \Magento\OrderHistorySearch\Model\Filter\ProductSku
{
    /**
     * ProductSku constructor.
     *
     * @param ItemRepository $itemRepository
     */
    public function __construct(
        private ItemRepository $itemRepository,
        private ResourceConnection $resourceConnection
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function applyFilter(Collection $ordersCollection, $value): Collection
    {
        /* D-86703 -PROD_EPRO_Getting scheduled maintenance page when searched with presentations */
        /* B-1281434 - Order history search by product name */

            $salesOrderItemTable = $this->resourceConnection->getTableName('sales_order_item');
            $ordersCollection->getSelect()
                ->joinLeft(['soi' => $salesOrderItemTable], 'main_table.entity_id = soi.order_id', ['soi.name'])
                ->group('main_table.entity_id');
            $ordersCollection->addFieldToFilter('soi.name', ['like' => '%' . $value . '%']);

        return $ordersCollection;
    }
}
