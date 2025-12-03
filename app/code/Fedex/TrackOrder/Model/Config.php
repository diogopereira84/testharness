<?php
/**
 * @category    Fedex
 * @package     Fedex_TrackOrder
 * @copyright   Copyright (c) 2023 Fedex
 * @author      sirichandana <sirichandana.guttha@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\TrackOrder\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * XML path for meta title
     */
    public const XML_PATH_META_TITLE = 'seo_settings/order_tracking/metatitle';

    /**
     * XML path for meta description
     */
    public const XML_PATH_META_DESCRIPTION = 'seo_settings/order_tracking/metadescription';

    /**
     * XML path for track order header
     */
    public const XML_PATH_TRACK_ORDER_HEADER = 'seo_settings/order_tracking/track_order_header';

    /**
     * XML path for track order description
     */
    public const XML_PATH_TRACK_ORDER_DESCRIPTION = 'seo_settings/order_tracking/track_order_description';

    /**
     * XML path for track shipment URL
     */
    public const XML_PATH_TRACK_SHIPMENT_URL = 'seo_settings/order_tracking/track_shipment_url';

    /**
     * XML path for order detail XAPI URL
     */
    public const XML_PATH_ORDER_DETAIL_XAPI_URL = 'fedex/general/order_detail_xapi_url';

    /**
     * XML path for legacy track order URL
     */
    public const XML_PATH_LEGACY_TRACK_ORDER_URL = 'fedex/general/legacy_track_order_url';

    /**
     * XML path for product due date message
     */
    public const XML_PATH_PRODUCT_DUE_DATE_MESSAGE = 'fedex/one_p_product/product_order_due_date_message';

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * Get meta title from store configuration
     *
     * @param null|int|string $store
     * @return string
     */
    public function getMetaTitle($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_META_TITLE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get meta description from store configuration
     *
     * @param null|int|string $store
     * @return string
     */
    public function getMetaDescription($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_META_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get track order header from store configuration
     *
     * @param null|int|string $store
     * @return string
     */
    public function getTrackOrderHeader($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TRACK_ORDER_HEADER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get track order description from store configuration
     *
     * @param null|int|string $store
     * @return string
     */
    public function getTrackOrderDescription($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TRACK_ORDER_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get track shipment URL from store configuration
     *
     * @param null|int|string $store
     * @return string
     */
    public function getTrackShipmentUrl($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TRACK_SHIPMENT_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get order detail XAPI URL from api configuration
     *
     * @param null|int|string $store
     * @return string|null
     */
    public function getOrderDetailXapiUrl($store = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ORDER_DETAIL_XAPI_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get legacy track order URL from configuration
     *
     * @param null|int|string $store
     * @return string|null
     */
    public function getLegacyTrackOrderUrl($store = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_LEGACY_TRACK_ORDER_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Order Due Date Message for 1P Products
     *
     * @param null|int|string $store
     * @return string|null
     */
    public function getProductDueDateMessage($store = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_DUE_DATE_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}