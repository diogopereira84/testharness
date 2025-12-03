/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'jquery',
    'Magento_Checkout/js/view/summary/shipping',
    'Magento_Checkout/js/model/quote'
], function ($, Component, quote) {
    'use strict';

    var displayMode = window.checkoutConfig.reviewShippingDisplayMode;

    return Component.extend({
        defaults: {
            displayMode: displayMode,
            template: 'Magento_Tax/checkout/summary/shipping'
        },

        /**
         * @return {Boolean}
         */
        isBothPricesDisplayed: function () {
            return this.displayMode == 'both'; //eslint-disable-line eqeqeq
        },

        /**
         * @return {Boolean}
         */
        isIncludingDisplayed: function () {
            return this.displayMode == 'including'; //eslint-disable-line eqeqeq
        },

        /**
         * @return {Boolean}
         */
        isExcludingDisplayed: function () {
            return this.displayMode == 'excluding'; //eslint-disable-line eqeqeq
        },

        /**
         * @return {*|Boolean}
         */
        isCalculated: function () {
            return this.totals() && this.isFullMode() && quote.shippingMethod() != null;
        },

        /**
         * @return {*}
         */
        getIncludingValue: function () {
            var price;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            price = this.totals()['shipping_incl_tax'];

            return this.getFormattedPrice(price);
        },

        /**
         * @return {*}
         */
        getExcludingValue: function () {
            var price;

            if (!this.isCalculated()) {
                return this.notCalculatedMessage;
            }
            price = this.totals()['shipping_amount'];

            return this.getFormattedPrice(price);
        },

        /**
         * @return {*|string}
         */
        getnonCalculatedValue: function () {
            return "TBD";
        },

        /**
         * Check qoute is price is dashed or not
         *
         * @return Boolean
         */
        isQuotePriceIsDashable: function () {
            return typeof(window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ?
            window.checkoutConfig.is_quote_price_is_dashable : null;
        },

        /**
         * Is Essendant toggle enabled
         *
         * @return {string}
         */
        isEssendantEnabled: function () {
            return typeof (window.checkoutConfig.tiger_enable_essendant) != 'undefined' && typeof (window.checkoutConfig.tiger_enable_essendant) != null ? window.checkoutConfig.tiger_enable_essendant : false;
        },

        /**
         * Get shipping cost class
         * @param shippingPrice
         * @returns {string}
         */
        shippingCostClass: function (value) {
            return value === 'FREE' ? 'fedex-bold weight-700 cl-green' : 'price';
        },
    });
});
