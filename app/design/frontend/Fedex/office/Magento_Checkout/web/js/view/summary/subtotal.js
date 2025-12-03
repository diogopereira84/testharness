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
            template: 'Magento_Checkout/summary/subtotal'
        },

        /**
         * Get pure value.
         *
         * @return {*}
         */
        getPureValue: function () {
            var totals = quote.getTotals()();

            if (totals) {
                return totals.subtotal;
            }

            return quote.subtotal;
        },

        /**
         * @return {String}
         */
        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        },

        /**
         * @return {Int}
         */
        getCartItemsCount: function () {

            if(window.checkoutConfig !== undefined){
                return window.checkoutConfig.quoteItemData.length;
            }
            
            return 0;
        },

        /**
         * @return {String}
         */
        getTitle: function () {
            return 'Items';
        },

        /**
         * Check quote is priceable or not
	 *
         * @return {Boolean}
         */
        isQuotePriceIsDashable: function () {
            return typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
        },
    });
});
