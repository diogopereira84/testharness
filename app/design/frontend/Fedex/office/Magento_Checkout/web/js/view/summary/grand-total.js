/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote'
], function (Component, quote) {
    'use strict';
    
    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/summary/grand-total'
        },

        /**
         * @return {*}
         */
        isDisplayed: function () {
            return this.isFullMode();
        },

        /**
         * Get pure value.
         */
        getPureValue: function () {
            var totals = quote.getTotals()();

            if (totals) {
                return totals['grand_total'];
            }
            return quote['grand_total'];
        },
        getPureCartValue: function () {
            var totals = quote.getTotals()();
            if (totals) {
                return totals['base_grand_total'];
            }
            return quote['subtotal'];
        },

        /**
         * @return {*|String}
         */
        getCartValue: function () {
            return this.getFormattedPrice(this.getPureCartValue());
        },
        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        },

        /**
         * Check quote is priceable or not
         * 
         * @return {Boolean}
         */
        isQuotePricIsDashable: function () {
            return typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
        }
    });
});
