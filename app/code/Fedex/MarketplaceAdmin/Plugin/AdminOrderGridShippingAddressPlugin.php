<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Ui\Component\Listing\Column\Address as AddressColumn;
use Fedex\MarketplaceAdmin\Model\Config;
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressGridFormatter;
use Fedex\MarketplaceAdmin\Service\Address\RegionNameResolver;

class AdminOrderGridShippingAddressPlugin
{
    private const GRID_NAMESPACE = 'sales_order_grid';
    private const TARGET_COLUMN  = 'shipping_address';
    private const PICKUP_METHOD  = 'fedexshipping_PICKUP';

    /**
     * @param ResourceConnection $resource
     * @param Config $config
     * @param MiraklShippingAddressGridFormatter $gridFormatter
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly Config $config,
        private readonly MiraklShippingAddressGridFormatter $gridFormatter,
        private readonly RegionNameResolver $regionResolver
    ) {}

    /**
     * @param AddressColumn $subject
     * @param array $result
     * @return array
     */
    public function afterPrepareDataSource(AddressColumn $subject, array $result): array
    {
        $namespace = (string)$subject->getContext()->getNamespace();
        if ($namespace !== self::GRID_NAMESPACE) {
            return $result;
        }

        if ($subject->getName() !== self::TARGET_COLUMN) {
            return $result;
        }

        if (!$this->config->isD226848Enabled()) {
            return $result;
        }

        $items = $result['data']['items'] ?? null;
        if (!is_array($items) || $items === []) {
            return $result;
        }

        $orderIds = $this->collectOrderIds($items);
        if ($orderIds === []) {
            return $result;
        }

        $shippingMethodByOrder = $this->fetchShippingMethods($orderIds);
        $miraklAddressByOrder  = $this->fetchMiraklAddresses($orderIds);

        $regionIds = [];
        foreach ($miraklAddressByOrder as $addr) {
            if (!empty($addr['regionId'])) {
                $regionIds[] = (int)$addr['regionId'];
            }
        }
        $this->regionResolver->preload($regionIds);

        foreach ($result['data']['items'] as &$row) {
            $orderId = (int)($row['entity_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $method = (string)($shippingMethodByOrder[$orderId] ?? '');
            if ($method !== self::PICKUP_METHOD) {
                continue;
            }

            $miraklAddress = $miraklAddressByOrder[$orderId] ?? null;
            if (!is_array($miraklAddress)) {
                continue;
            }

            $row[self::TARGET_COLUMN] = $this->gridFormatter->formatInline($miraklAddress);
        }

        return $result;
    }

    /** @return int[] */
    private function collectOrderIds(array $rows): array
    {
        $ids = [];
        foreach ($rows as $r) {
            $id = (int)($r['entity_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids ? array_values(array_unique($ids)) : [];
    }

    /**
     * @param array $orderIds
     * @return array
     */
    private function fetchShippingMethods(array $orderIds): array
    {
        $conn   = $this->connection();
        $table  = $this->resource->getTableName('sales_order');

        return $conn->fetchPairs(
            $conn->select()
                ->from($table, ['entity_id', 'shipping_method'])
                ->where('entity_id IN (?)', $orderIds)
        ) ?: [];
    }

    /**
     * @param array $orderIds
     * @return array
     */
    private function fetchMiraklAddresses(array $orderIds): array
    {
        $connection          = $this->connection();
        $salesOrderItemTable = $this->resource->getTableName('sales_order_item');

        $itemRows = $connection->fetchAll(
            $connection->select()
                ->from($salesOrderItemTable, ['order_id', 'additional_data'])
                ->where('order_id IN (?)', $orderIds)
                ->where('additional_data IS NOT NULL')
        );

        $addressesByOrderId = [];

        foreach ($itemRows as $itemRow) {
            $orderId = (int)$itemRow['order_id'];

            if (isset($addressesByOrderId[$orderId])) {
                continue;
            }

            $additionalDataJson = (string)($itemRow['additional_data'] ?? '');
            if ($additionalDataJson === '') {
                continue;
            }

            if (strpos($additionalDataJson, 'mirakl_shipping_data') === false) {
                continue;
            }

            $additionalData = json_decode($additionalDataJson, true);
            if (!is_array($additionalData)) {
                continue;
            }

            $miraklAddress = $additionalData['mirakl_shipping_data']['address'] ?? null;
            if (is_array($miraklAddress)) {
                $addressesByOrderId[$orderId] = $miraklAddress;
            }
        }

        return $addressesByOrderId;
    }

    /**
     * @return AdapterInterface
     */
    private function connection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}
