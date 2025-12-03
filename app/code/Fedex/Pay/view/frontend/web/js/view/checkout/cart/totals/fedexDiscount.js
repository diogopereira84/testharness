/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'jquery',
    'Fedex_Delivery/js/model/toggles-and-settings',
], function (Component, quote, $, togglesAndSettings) {
    'use strict';

    let discount = window.checkoutConfig.quoteData.discount;
    return Component.extend({
        defaults: {
            template: 'Fedex_Pay/checkout/cart/totals/fedexDiscount'
        },

        /**
         * Get pure value.
         *
         * @return {*}
         */
        getPureValue: function () {
            return discount;
        },

        /**
         * Get title value.
         *
         * @return {String}
         */
         getTitle: function () {
            let discount_breakdown = this.isDiscountBreakdownEnable();
            let discounts = 'Total Discount';
            let isQuotePriceIsDashable = typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
            if(discount_breakdown && !isQuotePriceIsDashable) {
                discounts = '<span id="discdrop" tabindex="0">Total Discount(s)  <span id="discbreak"  class="arrow down"></span></span>';
            } else {
                discounts = 'Total Discount(s)';
            }

            return discounts;
        },

        /**
         * @return {*|String}
         */
        getValue: function () {
            let discount = window.checkoutConfig.quoteData.discount;
            let isQuotePriceIsDashable = typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
            if(discount > 0 && !isQuotePriceIsDashable){
                return '-' + this.getFormattedPrice(discount);
            } else {
                return '-';
            }

        },

        /**
         * Is Volume Discount Applied
         *
         * @return {Boolean}
         */
        isVolumeDiscountApplied: function () {
            let volume_discount = typeof window.checkoutConfig.quoteData.volume_discount !== 'undefined' ? window.checkoutConfig.quoteData.volume_discount : 0;
            if(volume_discount > 0){
                return true;
            }

            return false;
        },

        /**
         * Is Volume Discount Applied
         *
         * @return {Boolean}
         */
        isBundleDiscountApplied: function () {
            let bundle_discount = typeof window.checkoutConfig.quoteData.bundle_discount !== 'undefined' ? window.checkoutConfig.quoteData.bundle_discount : 0;
            if(bundle_discount > 0){
                return true;
            }

            return false;
        },

        /**
         * Is Account Discount Applied
         *
         * @return {Boolean}
         */
        isAccountDiscountApplied: function () {
            let account_discount = typeof window.checkoutConfig.quoteData.account_discount !== 'undefined' ? window.checkoutConfig.quoteData.account_discount : 0;
            if(account_discount > 0){
                return true;
            }

            return false;
        },

        /**
         * Is Promo Discount Applied
         *
         * @return {Boolean}
         */
        isPromoDiscountApplied: function () {
            let promo_discount = typeof window.checkoutConfig.quoteData.promo_discount !== 'undefined' ? window.checkoutConfig.quoteData.promo_discount : 0;
            if(promo_discount > 0){
                return true;
            }

            return false;
        },

        /**
         * Is Promo Discount Applied
         *
         * @return {Boolean}
         */
        isShippingDiscountApplied: function () {
            let shipping_discount = typeof window.checkoutConfig.quoteData.shipping_discount !== 'undefined' ? window.checkoutConfig.quoteData.shipping_discount : 0;
            if(shipping_discount > 0){
                return true;
            }

            return false;
        },

        /**
         * Is Discount Breakdown enable
         *
         * @return {Boolean}
         */
        isDiscountBreakdownEnable: function () {
            return (this.isVolumeDiscountApplied() || this.isBundleDiscountApplied() || this.isAccountDiscountApplied() || this.isPromoDiscountApplied());
        },

        /**
         * Is Discount Breakdown enable
         *
         * @return {Boolean}
         */
        toggleDiscount: function () {
            let isQuotePriceIsDashable = typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
            if(!isQuotePriceIsDashable){
                $('.toggle-discount .arrow').toggleClass("up");
                $('.discount_breakdown').slideToggle(100);
            } else {
                return false;
            }
        },

        /**
         * Get Sorted Discounts
         *
         * @return {Array}
         */
        getSortedDiscounts: function() {
            var self = this;
            let promo_discount = typeof window.checkoutConfig.quoteData.promo_discount !== 'undefined' ? parseFloat(window.checkoutConfig.quoteData.promo_discount) : 0;
            let volume_discount = typeof window.checkoutConfig.quoteData.volume_discount !== 'undefined' ? parseFloat(window.checkoutConfig.quoteData.volume_discount) : 0;
            let account_discount = typeof window.checkoutConfig.quoteData.account_discount !== 'undefined' ? parseFloat(window.checkoutConfig.quoteData.account_discount) : 0;
            let bundle_discount = typeof window.checkoutConfig.quoteData.bundle_discount !== 'undefined' ? parseFloat(window.checkoutConfig.quoteData.bundle_discount) : 0;

            let formated_promo_discount = '-' + this.getFormattedPrice(promo_discount);
            let formated_volume_discount = '-' + this.getFormattedPrice(volume_discount);
            let formated_account_discount = '-' + this.getFormattedPrice(account_discount);
            let formated_bundle_discount = '-' + this.getFormattedPrice(bundle_discount);

            if(window.checkout.mazegeek_b2352379_discount_breakdown === true || window.checkoutConfig.mazegeek_b2352379_discount_breakdown === true) {
                let shipping_discount = typeof window.checkoutConfig.quoteData.shipping_discount !== 'undefined' ? parseFloat(window.checkoutConfig.quoteData.shipping_discount) : 0;

                let formated_shipping_discount = "-"+this.getFormattedPrice(shipping_discount);

                var discountAmounts = [
                    {"sort_price":promo_discount,"type":"promo_discount","price":formated_promo_discount,"label":"Promo Discount","is_active":self.isPromoDiscountApplied()},
                    {"sort_price":account_discount,"type":"account_discount","price":formated_account_discount,"label":"Account Discount","is_active":self.isAccountDiscountApplied()},
                    {"sort_price":volume_discount,"type":"volume_discount","price":formated_volume_discount,"label":"Volume Discount","is_active":self.isVolumeDiscountApplied()},
                    {"sort_price":shipping_discount,"type":"shipping_discount","price":formated_shipping_discount,"label":"Shipping Discount","is_active":self.isShippingDiscountApplied()}
                ];
            } else {
                var discountAmounts = [
                    {"sort_price":promo_discount,"type":"promo_discount","price":formated_promo_discount,"label":"Promo Discount","is_active":self.isPromoDiscountApplied()},
                    {"sort_price":account_discount,"type":"account_discount","price":formated_account_discount,"label":"Account Discount","is_active":self.isAccountDiscountApplied()},
                    {"sort_price":volume_discount,"type":"volume_discount","price":formated_volume_discount,"label":"Volume Discount","is_active":self.isVolumeDiscountApplied()}
                ];
            }

            if (togglesAndSettings.isToggleEnabled('tiger_e468338')) {
                discountAmounts.push(
                    {
                        "sort_price": bundle_discount,
                        "type": "bundle_discount",
                        "price": formated_bundle_discount,
                        "label": "Bundle Discount",
                        "is_active": self.isBundleDiscountApplied()
                    }
                );
            }

            let sortedAmounts = discountAmounts.sort((p1, p2) => (p1.sort_price < p2.sort_price) ? 1 : (p1.sort_price > p2.sort_price) ? -1 : 0);

            return sortedAmounts;
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
