<?php
/**
 * @category    Fedex
 * @package     Fedex_InStoreConfigurations
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Api;

use Magento\Store\Model\ScopeInterface;

/**
 * ConfigInterface
 */
interface ConfigInterface
{
    public const XML_PATH_FORCE_FUJITSU_ERROR_EMPTY_RETAIL_PRINT_ORDER_RESPONSE =
        'instore_toggle_configuration/instore_group_toggles/instore_section_force_fujitsu_error_empty_retail_print_order_response'; // phpcs:ignore

    public const XML_PATH_ROUND_ENABLE_XBEHALFOF_GRAPHQL =
        'instore_toggle_configuration/instore_group_toggles/instore_section_enable_xbehalfof_graphql'; // phpcs:ignore
    public const XML_PATH_LIVESEARCH_CUSTOM_SHARED_CATALOG_ID =
        'instore_toggle_configuration/instore_group_toggles/instore_section_adobe_livesearch_custom_shared_catalog_id'; // phpcs:ignore

    public const XML_PATH_FIX_PLACE_ORDER_RETRY =
        'instore_toggle_configuration/instore_group_toggles/instore_section_fix_place_order_retry';

    public const XML_PATH_CHECKOUT_RETRY_IMPROVEMENT_ENABLED =
        'instore_toggle_configuration/instore_group_toggles/instore_section_checkout_retry_improvement_enabled';

    public const XML_PATH_RATE_QUOTE =
        'instore_toggle_configuration/instore_group_toggles/instore_rate_quote_product_association_enabled';

    public const XML_PATH_DUE_DATE =
        'instore_toggle_configuration/instore_group_toggles/instore_add_or_update_due_date_enabled';

    public const XML_PATH_FEDEX_ACCOUNT_NUMBER =
        'instore_toggle_configuration/instore_group_toggles/instore_add_or_update_fedex_account_number_enabled';
    public const XML_PATH_LTE_IDENTIFIER =
        'instore_toggle_configuration/instore_group_toggles/instore_support_lte_identifier_enabled';
    public const XML_PATH_ADD_NOTES =
        'instore_toggle_configuration/instore_group_toggles/instore_section_add_notes';

    public const XML_PATH_LOG_TO_NEWRELIC =
        'instore_toggle_configuration/instore_group_toggles/instore_fedex_log_to_newrelic';

    public const XML_PATH_LOG_TO_NEWRELIC_HEADERS =
        'instore_toggle_configuration/instore_group_toggles/instore_fedex_log_to_newrelic_headers';

    public const XML_PATH_HANDLE_RAQ_TIMEOUT_ERROR =
        'instore_toggle_configuration/instore_group_toggles/instore_handle_raq_timeout_error_enabled';

    public const XML_PATH_SERVICE_TYPE_ENABLE_DISABLE =
        'instore_toggle_configuration/instore_group_toggles/instore_fedex_shipment_service_type_enable';

    public const XML_PATH_SERVICE_TYPE =
        'instore_toggle_configuration/instore_group_toggles/instore_fedex_shipment_service_types';

    public const XML_PATH_ESTIMATED_SUBTOTAL_FIX =
        'instore_toggle_configuration/instore_group_toggles/instore_fedex_estimated_subtotal_fix_enabled';

    public const XML_PATH_CART_PRICING_FIX =
        'instore_toggle_configuration/instore_group_toggles/instore_fedex_delivery_pricing_fix_enabled';

    public const XML_PATH_USER_CANNOT_PERFORM_CART_OPERATIONS_FIX =
        'instore_toggle_configuration/instore_group_toggles/instore_user_cannot_perform_cart_operations_fix_enabled';

    public const XML_PATH_UNABLE_TO_PLACE_ORDERS_DUE_TO_REMOVED_PREFERENCE =
        'instore_toggle_configuration/instore_group_toggles/instore_unable_to_place_orders';

    public const XML_PATH_EMPTY_TOKEN_ERROR_LOG =
        'instore_toggle_configuration/instore_group_toggles/instore_empty_token_error';

    public const XML_PATH_INSTORE_NEWRELIC_GRAPHQL_MUTATIONS_LIST =
        'instore_toggle_configuration/instore_group_toggles/instore_newrelic_graphql_mutations_list';

    public const XML_PATH_DELIVERY_DATES_FIELDS_FOR_FUSE =
        'instore_toggle_configuration/instore_group_toggles/tiger_delivery_dates_fields_for_fuse';

    public const XML_PATH_UPDATE_DUEDATE_FOR_FUSE =
        'instore_toggle_configuration/instore_group_toggles/instore_due_date_update';

    public const XML_PATH_GET_ALL_QUOTES_NEWRELIC_FOR_FUSE =
        'instore_toggle_configuration/instore_group_toggles/instore_newrelic_get_all_quotes';

    public const XML_PATH_FILTER_GET_ALL_QUOTES =
        'instore_toggle_configuration/instore_group_toggles/instore_filter_get_all_quotes';

    public const XML_PATH_FILTER_ADD_SHIP_BY_DATE =
        'instore_toggle_configuration/instore_group_toggles/instore_add_ship_by_date_to_due_date';

    public const XML_PATH_ADD_POLITICAL_DISCLOSURE_ORDER_SEARCH =
        'instore_toggle_configuration/instore_group_toggles/instore_add_political_disclosure_to_order_search';

    public const XML_PATH_ADD_POLITICAL_DISCLOSURE =
        'instore_toggle_configuration/instore_group_toggles/instore_add_political_disclosure_to_place_order';

    public const XML_PATH_APPLY_SHIPPING_DISCOUNT =
        'instore_toggle_configuration/instore_group_toggles/instore_fuse_shipping_discount';

    public const XML_PATH_SAVE_LTE_IDENTIFIER =
        'instore_toggle_configuration/instore_group_toggles/instore_fuse_save_lte_identifier';

    /**
     * Is Enabled Throw Exception On Graphql Requests
     *
     * @param string $scope
     * @return bool
     */
    public function isEnabledThrowExceptionOnGraphqlRequests(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnabledXOnBehalfOfHeader(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return string|null
     */
    public function getLivesearchCustomSharedCatalogId(
        string $scope = ScopeInterface::SCOPE_STORE
    ): ?string;

    /**
     * @param string $scope
     * @return bool
     */
    public function isFixPlaceOrderRetry(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isCheckoutRetryImprovementEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isRateQuoteProductAssociationEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isAddOrUpdateDueDateEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isAddOrUpdateFedexAccountNumberEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isSupportLteIdentifierEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * Is enabled add notes
     *
     * @param string $scope
     * @return bool
     */
    public function isEnabledAddNotes(string $scope = ScopeInterface::SCOPE_STORE): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isLoggingToNewrelicEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return array
     */
    public function headersLoggedToNewrelic(
        string $scope = ScopeInterface::SCOPE_STORE
    ): array;

    /**
     * @param string $scope
     * @return bool
     */
    public function isHandleRAQTimeoutErrorEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnableServiceTypeForRAQ(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return array
     */
    public function serviceTypeForRAQ(
        string $scope = ScopeInterface::SCOPE_STORE
    ): array;


    /**
     * @param string $scope
     * @return bool
     */
    public function isEnableEstimatedSubtotalFix(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnabledCartPricingFix(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;


    /**
     * @param string $scope
     * @return bool
     */
    public function isEnabledUserCannotPerformCartOperationsFix(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isUnableToPlaceOrderDueToRemovedPreferenceFix(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isEmptyTokenErrorLogEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return array
     */
    public function getNewrelicGraphqlMutationsList(
        string $scope = ScopeInterface::SCOPE_STORE
    ): array;

    /**
     * @param string $scope
     * @return bool
     */
    public function isDeliveryDatesFieldsEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isUpdateDueDateEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isSpanIdLoggedForGetAllQuotes(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;


    /**
     * @param string $scope
     * @return bool
     */
    public function isFilterForGetAllQuotesEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isAddShipByDateEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnablePoliticalDisclosureInOrderSearch(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function isEnablePoliticalDisclosureInPlaceOrder(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function canApplyShippingDiscount(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * @param string $scope
     * @return bool
     */
    public function canSaveLteIdentifier(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}
