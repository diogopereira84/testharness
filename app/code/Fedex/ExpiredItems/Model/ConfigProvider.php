<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Model Class ConfigProvider
 */
class ConfigProvider
{
    public const XML_PATH_EXPIRRY_ITEM_THRESHOLD_TIME = 'expired_items_config/general/expiry_item_threshold_time';
    public const XML_PATH_EXPIRY_ITEM_TIME = 'expired_items_config/general/expiry_item_time';
    public const XML_PATH_CART_EXPIRING_TITLE = 'expired_items_config/cart_message/cart_expiring_title';
    public const XML_PATH_CART_EXPIRING_MSG = 'expired_items_config/cart_message/cart_expiring_msg';
    public const XML_PATH_CART_EXPIRED_TITLE = 'expired_items_config/cart_message/cart_expired_title';
    public const XML_PATH_CART_EXPIRED_MSG = 'expired_items_config/cart_message/cart_expired_msg';
    public const XML_PATH_CART_ITEM_EXPIRING_TITLE = 'expired_items_config/cart_item_message/cart_item_expiring_title';
    public const XML_PATH_CART_ITEM_EXPIRING_MSG = 'expired_items_config/cart_item_message/cart_item_expiring_msg';
    public const XML_PATH_CART_ITEM_EXPIRED_TITLE = 'expired_items_config/cart_item_message/cart_item_expired_title';
    public const XML_PATH_CART_ITEM_EXPIRED_MSG = 'expired_items_config/cart_item_message/cart_item_expired_msg';
    public const XML_PATH_CART_ITEM_EXPIRING_3P_TITLE = 'expired_items_config/cart_item_message_3p/cart_item_expiring_title';
    public const XML_PATH_CART_ITEM_EXPIRING_3P_MSG = 'expired_items_config/cart_item_message_3p/cart_item_expiring_msg';
    public const XML_PATH_CART_ITEM_EXPIRED_3P_TITLE = 'expired_items_config/cart_item_message_3p/cart_item_expired_title';
    public const XML_PATH_CART_ITEM_EXPIRED_3P_MSG = 'expired_items_config/cart_item_message_3p/cart_item_expired_msg';
    public const XML_PATH_MINICART_ITEM_EXPIRING_MSG = 'expired_items_config/minicart_message/minicart_expiring_msg';
    public const XML_PATH_MINICART_ITEM_EXPIRED_MSG = 'expired_items_config/minicart_message/minicart_expired_msg';
    public const XML_PATH_POPUP_EXPIRING_TITLE = 'expired_items_config/popup_message/popup_expiring_title';
    public const XML_PATH_POPUP_EXPIRING_MSG = 'expired_items_config/popup_message/popup_expiring_msg';
    public const XML_PATH_POPUP_EXPIRED_TITLE = 'expired_items_config/popup_message/popup_expired_title';
    public const XML_PATH_POPUP_EXPIRED_MSG = 'expired_items_config/popup_message/popup_expired_msg';
    public const XML_PATH_UPLOAD_TO_QUOTE_EXPIRING_TITLE = 'expired_items_config/upload_to_quote_item_message/upload_to_quote_item_expiring_title';
    public const XML_PATH_UPLOAD_TO_QUOTE_EXPIRING_MSG = 'expired_items_config/upload_to_quote_item_message/upload_to_quote_item_expiring_msg';
    public const XXML_PATH_UPLOAD_TO_QUOTE_EXPIRED_TITLE = 'expired_items_config/upload_to_quote_item_message/upload_to_quote_item_expired_title';
    public const XML_PATH_UPLOAD_TO_QUOTE_EXPIRED_MSG = 'expired_items_config/upload_to_quote_item_message/upload_to_quote_item_expired_msg';

    public const XML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRING_TITLE = 'expired_items_config/upload_to_quote_cart_message/upload_to_quote_cart_expiring_title';
    public const XML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRING_MSG = 'expired_items_config/upload_to_quote_cart_message/upload_to_quote_cart_expiring_msg';
    public const XXML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRED_TITLE = 'expired_items_config/upload_to_quote_cart_message/upload_to_quote_cart_expired_title';
    public const XML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRED_MSG = 'expired_items_config/upload_to_quote_cart_message/upload_to_quote_cart_expired_msg';

    /**
     * Initilizing constructor
     * ConfigProvider constructor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * To get expiry time
     *
     * @return int
     */
    public function getExpiryTime()
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_EXPIRY_ITEM_TIME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get expiry threshold time
     *
     * @return int
     */
    public function getExpiryThresholdTime()
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_EXPIRRY_ITEM_THRESHOLD_TIME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get expiry title
     *
     * @return string
     */
    public function getCartExpiryTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_EXPIRING_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get expiry message
     *
     * @return string
     */
    public function getCartExpiryMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_EXPIRING_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get expired title
     *
     * @return string
     */
    public function getCartExpiredTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_EXPIRED_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get expired message
     *
     * @return string
     */
    public function getCartExpiredMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_EXPIRED_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expiry title
     *
     * @return string
     */
    public function getCartItemExpiryTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRING_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expiry message
     *
     * @return string
     */
    public function getCartItemExpiryMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRING_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expired title
     *
     * @return string
     */
    public function getCartItemExpiredTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRED_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expired message
     *
     * @return string
     */
    public function getCartItemExpiredMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRED_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expiry third party product title
     *
     * @return string
     */
    public function getCartItemExpiry3pTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRING_3P_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expiry third party product message
     *
     * @return string
     */
    public function getCartItemExpiry3pMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRING_3P_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expired third party product title
     *
     * @return string
     */
    public function getCartItemExpired3pTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRED_3P_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get cart item expired third party product message
     *
     * @return string
     */
    public function getCartItemExpired3pMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CART_ITEM_EXPIRED_3P_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get minicart expiry message
     *
     * @return string
     */
    public function getMiniCartExpiryMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MINICART_ITEM_EXPIRING_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get minicart expired message
     *
     * @return string
     */
    public function getMiniCartExpiredMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MINICART_ITEM_EXPIRED_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get popup expiry title
     *
     * @return string
     */
    public function getPopUpExpiryTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_POPUP_EXPIRING_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get popup expiry message
     *
     * @return string
     */
    public function getPopUpExpiryMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_POPUP_EXPIRING_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get popup expired title
     *
     * @return string
     */
    public function getPopUpExpiredTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_POPUP_EXPIRED_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get popup expired message
     *
     * @return string
     */
    public function getPopUpExpiredMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_POPUP_EXPIRED_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote expiring title
     *
     * @return string
     */
    public function getUploadToQuoteExpiringTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_UPLOAD_TO_QUOTE_EXPIRING_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote expiring message
     *
     * @return string
     */
    public function getUploadToQuoteExpiringMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_UPLOAD_TO_QUOTE_EXPIRING_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote expired title
     *
     * @return string
     */
    public function getUploadToQuoteExpiredTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XXML_PATH_UPLOAD_TO_QUOTE_EXPIRED_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote expired message
     *
     * @return string
     */
    public function getUploadToQuoteExpiredMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRED_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote summary expiring title
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiringTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRING_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote summary expiring message
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiringMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRING_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote summary expired title
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiredTitle()
    {
        return (string) $this->scopeConfig->getValue(
            self::XXML_PATH_UPLOAD_TO_QUOTE_SUMMARY_EXPIRED_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * To get upload to quote summary expired message
     *
     * @return string
     */
    public function getUploadToQuoteSummaryExpiredMessage()
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_UPLOAD_TO_QUOTE_EXPIRED_MSG,
            ScopeInterface::SCOPE_STORE
        );
    }

}
