<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceAdmin
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Plugin;

use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Fedex\MarketplaceAdmin\Model\Config;

class UpdateOrderOriginPlugin
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param OrderHelper $orderHelper
     * @param Config $config
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly OrderHelper        $orderHelper,
        private readonly Config             $config
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param SubmitOrderApi $subject
     * @param Order $result
     * @return Order
     */
    public function afterCreateOrderBeforePayment(
        SubmitOrderApi           $subject,
        Order                    $result
    ) {
        if (!$this->config->isMktSelfregEnabled()) {
            return $result;
        }

        $origin     = $this->getOrderOrigin($result);
        $connection = $this->resourceConnection->getConnection();
        $tableName  = $connection->getTableName('sales_order_grid');

        $connection->update(
            $tableName,
            ['flag' => $origin],
            ['entity_id = ?' => $result->getId()]
        );
        return $result;
    }

    /**
     * Get order origin.
     *
     * @param Order $order
     * @return string
     */
    public function getOrderOrigin(Order $order): string
    {
        $origin = 'operator';
        if ($this->orderHelper->isFullMiraklOrder($order)) {
            $origin = $this->config::ORIGIN_MARKETPLACE;
        } elseif ($this->orderHelper->isMiraklOrder($order)) {
            $origin = $this->config::ORIGIN_MIXED;;
        }
        return $origin;
    }
}
