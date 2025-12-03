/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    "Magento_Catalog/js/price-utils",
    'Magento_Checkout/js/model/quote',
    "jquery",
    "mage/url",
], function (Component, priceUtils, quote, $, urlBuilder) {
    'use strict';

    var displaySubtotalMode = window.checkoutConfig.reviewTotalsDisplayMode;

    return Component.extend({
        defaults: {
            displaySubtotalMode: displaySubtotalMode,
            template: 'Magento_Tax/checkout/summary/subtotal'
        },
        totals: quote.getTotals(),

        /**
         * @return {*|String}
         */
        getValue: function () {
            var price = 0;
            if (this.totals()) {
                price = this.totals().subtotal;
            }
            let isQuotePriceIsDashable =  typeof(window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ?
            window.checkoutConfig.is_quote_price_is_dashable : null;
            if (isQuotePriceIsDashable == 1) {
                return '$--.--';
            }

            return this.getFormattedPrice(price);
        },

        /**
         * @return {Boolean}
         */
        isBothPricesDisplayed: function () {
            return this.displaySubtotalMode == 'both'; //eslint-disable-line eqeqeq
        },

        /**
         * @return {Boolean}
         */
        isIncludingTaxDisplayed: function () {
            return this.displaySubtotalMode == 'including'; //eslint-disable-line eqeqeq
        },

        /**
         * @return {*|String}
         */
        getValueInclTax: function () {
            var price = 0;

            if (this.totals()) {
                price = this.totals()['subtotal_incl_tax'];
            }

            return this.getFormattedPrice(price);
        },
        
        /**
         * @return {String}
         */
        getTitle: function () {
            return 'Items';
        },
        
        getCheckoutItemsCount: function () {
            if(window.checkoutConfig !== undefined){
                return window.checkoutConfig.quoteItemData.length;
            }
            
            return 0;
        },

        /**
         * Check qoute is price is dashed or not
         * 
         * @return Boolean
         */
        isQuotePriceIsDashable: function () {
            return typeof(window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ?
            window.checkoutConfig.is_quote_price_is_dashable : null;
        }
    });
});

