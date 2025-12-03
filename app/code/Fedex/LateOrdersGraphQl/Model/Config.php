<?php
namespace Fedex\LateOrdersGraphQl\Model;

use Fedex\LateOrdersGraphQl\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    const XML_PATH_WINDOW_HOURS = 'graphql_settings/graphql_response_options/lateorder_query_max_window_hours';
    const XML_PATH_MAX_PAGINATION = 'graphql_settings/graphql_response_options/lateorder_query_max_pagination';
    const XML_PATH_DEFAULT_PAGINATION = 'graphql_settings/graphql_response_options/lateorder_query_default_pagination';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get max window by hours for late order GraphQL query and return in minutes
     * @return int|null
     */
    public function getLateOrderQueryWindowHours(): ?int
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_WINDOW_HOURS, ScopeInterface::SCOPE_STORE);
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get max pagination for late order GraphQL query response
     * @return int|null
     */
    public function getLateOrderQueryMaxPagination(): ?int
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_MAX_PAGINATION, ScopeInterface::SCOPE_STORE);
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get default pagination for late order GraphQL query response
     * @return int|null
     */
    public function getLateOrderQueryDefaultPagination(): ?int
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_DEFAULT_PAGINATION, ScopeInterface::SCOPE_STORE);
        return $value !== null ? (int)$value : null;
    }
}
