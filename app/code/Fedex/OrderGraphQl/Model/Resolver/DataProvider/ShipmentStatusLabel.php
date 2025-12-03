<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Json;

class ShipmentStatusLabel
{
    private const SHIPMENT_STATUS = "instore_toggle_configuration/instore_group_toggles/shipment_mapping";

    /**
     * @var array
     */
    private array $labelMap = [];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $jsonHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected Json $jsonHelper,
        protected ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @param int $shippingId
     * @return string
     */
    public function getShipmentLabel(int $shippingId): string
    {
        return $this->getLabels()[$shippingId] ?? '';
    }

    /**
     * @return array
     */
    private function getLabels(): array
    {
        if (empty($this->labelMap)) {
            $labelsTable = $this->resourceConnection->getTableName('shipment_status');
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()->from($labelsTable);
            foreach ($connection->fetchAll($select) as $status) {
                $value = $this->getKeyByValue($status['key']);
                $this->labelMap[$status['value']] = $value ?? $status['label'];
            }
        }
        return $this->labelMap;
    }

    /**
     * @param $value
     * @return string|null
     */
    private function getKeyByValue($value): ?string
    {
        $returnKey = null;
        $instoreShipmentStatus = $this->getShipmentStatusMappingValue();
        foreach ($instoreShipmentStatus as $filteredStatus) {
            if ($filteredStatus['magento_status'] == $value) {
                $returnKey = $filteredStatus['mapped_status'];
            }
        }
        return $returnKey;
    }

    /**
     * @param string $scope
     * @return mixed
     */
    public function getShipmentStatusMappingValue(string $scope = ScopeInterface::SCOPE_STORE): mixed
    {
        return  $this->jsonHelper->unserialize($this->scopeConfig->getValue(static::SHIPMENT_STATUS, $scope));
    }
}
