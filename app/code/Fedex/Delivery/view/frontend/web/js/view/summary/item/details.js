/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'mage/url',
    'Magento_Customer/js/customer-data'
], function (Component,urlBuilder, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/summary/item/details'
        },

        /**
         * @param {Object} quoteItem
         * @return {String}
         */
        getValue: function (quoteItem) {
            return quoteItem.name;
        },
        getCartUrl: function () {
            return urlBuilder.build('checkout/cart');
        },

        /**
         * Check item is priceable or not
         *
         * @return {Boolean}
         */
        isItemPriceable: function (itemId) {
            let cartData = customerData.get('cart')();
            for (let item of cartData.items) {
                if (itemId == item.item_id) {
                    return item.isItemPriceable;
                }
            }

            return true;
        }
    });
});
