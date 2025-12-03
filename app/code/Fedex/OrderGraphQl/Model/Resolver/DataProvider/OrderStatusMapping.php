<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Json;

class OrderStatusMapping
{
    private const ORDER_STATUS = "instore_toggle_configuration/instore_group_toggles/status_mapping";

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $jsonHelper
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected Json $jsonHelper
    ) {
    }

    /**
     * @param string $status
     * @return string
     */
    public function getMappingKey(string $status): string
    {
        $instoreOrderStatus = $this->getOrderStatusMappingValue();
        foreach ($instoreOrderStatus as $filteredStatus) {
            if ($filteredStatus['magento_status'] == $status) {
                return $filteredStatus['mapped_status'];
            }
        }
        return $status;
    }
    /**
     * @param string $status
     * @return array
     */
    public function getMappingValues(string $status): array
    {
        $instoreOrderStatus = $this->getOrderStatusMappingValue();
        foreach ($instoreOrderStatus as $filteredStatus) {
            if ($filteredStatus['magento_status'] == $status) {
                return $filteredStatus['magento_status'];
            }
        }

        return [];
    }

    /**
     * @param string $scope
     * @return mixed
     */
    public function getOrderStatusMappingValue(string $scope = ScopeInterface::SCOPE_STORE): mixed
    {
        return  $this->jsonHelper->unserialize($this->scopeConfig->getValue(static::ORDER_STATUS, $scope));
    }
}
