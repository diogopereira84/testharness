<?php
/**
 * @category    Fedex
 * @package     Fedex_InStoreConfigurations
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Model\System;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * class Config
 */
class Config implements ConfigInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestQueryValidator $requestQueryValidator
     * @param Json $jsonHelper
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly RequestQueryValidator $requestQueryValidator,
        private readonly Json $jsonHelper
    ) {}

    /**
     * @inheritDoc
     */
    public function isEnabledThrowExceptionOnGraphqlRequests(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool {
        return (bool) ($this->requestQueryValidator->isGraphQl());
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnabledXOnBehalfOfHeader(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        $roundItemsFlag = (bool)$this->scopeConfig->getValue(
            static::XML_PATH_ROUND_ENABLE_XBEHALFOF_GRAPHQL,
            $scope
        );

        return ($roundItemsFlag && $this->requestQueryValidator->isGraphQl());
    }

    /**
     * @param string $scope
     * @return string|null
     */
    public function getLivesearchCustomSharedCatalogId(string $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->scopeConfig->getValue(
            static::XML_PATH_LIVESEARCH_CUSTOM_SHARED_CATALOG_ID,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isFixPlaceOrderRetry(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_FIX_PLACE_ORDER_RETRY,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isCheckoutRetryImprovementEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_CHECKOUT_RETRY_IMPROVEMENT_ENABLED,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isRateQuoteProductAssociationEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_RATE_QUOTE,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isAddOrUpdateDueDateEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_DUE_DATE,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isAddOrUpdateFedexAccountNumberEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_FEDEX_ACCOUNT_NUMBER,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isSupportLteIdentifierEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_LTE_IDENTIFIER,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function isEnabledAddNotes(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(static::XML_PATH_ADD_NOTES, $scope);
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isLoggingToNewrelicEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_LOG_TO_NEWRELIC,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return array
     */
    public function headersLoggedToNewrelic(string $scope = ScopeInterface::SCOPE_STORE): array
    {
        $headers = [];
        $headerString = $this->scopeConfig->getValue(
            static::XML_PATH_LOG_TO_NEWRELIC_HEADERS,
            $scope
        );
        if ($headerString) {
            $headerString = preg_replace('/\s+/','', $headerString);
            $headers = explode(",", $headerString);
        }
        return $headers;
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isHandleRAQTimeoutErrorEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_HANDLE_RAQ_TIMEOUT_ERROR,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnableServiceTypeForRAQ(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_SERVICE_TYPE_ENABLE_DISABLE,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return array
     */
    public function serviceTypeForRAQ(string $scope = ScopeInterface::SCOPE_STORE): array
    {
        $serviceTypeArray = [];
        $serviceTypes = $this->jsonHelper->unserialize($this->scopeConfig->getValue(static::XML_PATH_SERVICE_TYPE, $scope));
        foreach ($serviceTypes as $serviceType) {
            $serviceTypeArray[] = $serviceType['service_type'];
        }
        return $serviceTypeArray;
    }

    /**
     * @inheritDoc
     */
    public function isEnableEstimatedSubtotalFix(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_ESTIMATED_SUBTOTAL_FIX,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function isEnabledCartPricingFix(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_CART_PRICING_FIX,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function isEnabledUserCannotPerformCartOperationsFix(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_USER_CANNOT_PERFORM_CART_OPERATIONS_FIX,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function isUnableToPlaceOrderDueToRemovedPreferenceFix(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_UNABLE_TO_PLACE_ORDERS_DUE_TO_REMOVED_PREFERENCE,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function isEmptyTokenErrorLogEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_EMPTY_TOKEN_ERROR_LOG,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function getNewrelicGraphqlMutationsList(string $scope = ScopeInterface::SCOPE_STORE): array
    {
        $mutationList = $this->scopeConfig->getValue(
            static::XML_PATH_INSTORE_NEWRELIC_GRAPHQL_MUTATIONS_LIST,
            $scope
        ) ?? '';
        return explode(',', $mutationList) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function isDeliveryDatesFieldsEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_DELIVERY_DATES_FIELDS_FOR_FUSE,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function isUpdateDueDateEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_UPDATE_DUEDATE_FOR_FUSE,
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function isSpanIdLoggedForGetAllQuotes(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_GET_ALL_QUOTES_NEWRELIC_FOR_FUSE,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isFilterForGetAllQuotesEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_FILTER_GET_ALL_QUOTES,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isAddShipByDateEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_FILTER_ADD_SHIP_BY_DATE,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnablePoliticalDisclosureInOrderSearch(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_ADD_POLITICAL_DISCLOSURE_ORDER_SEARCH,
            $scope
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnablePoliticalDisclosureInPlaceOrder(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_ADD_POLITICAL_DISCLOSURE,
            $scope
        );
    }

    public function canApplyShippingDiscount(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_APPLY_SHIPPING_DISCOUNT,
            $scope
        );
    }

    public function canSaveLteIdentifier(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool) $this->scopeConfig->getValue(
            static::XML_PATH_SAVE_LTE_IDENTIFIER,
            $scope
        );
    }
}
