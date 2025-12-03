/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    let mixin = {
        /**
         * Check item image is standard or not
         * 
         * @param {Object} item
         * @return {Boolean}
         */
         IsNonStandardFile: function (item) {
            let cartData = customerData.get('cart')();
            for (let itemData of cartData.items) {
                if (item['item_id'] == itemData.item_id) {
                    return itemData.isNonStandardFile;
                }
            }
            
            return false;
        },

        /**
         * Get non-standard image url
         * 
         * @return {String}
         */
        getNonStandardImage: function () {
           return typeof(window.checkoutConfig.non_standard_imageurl) != 'undefined' && window.checkoutConfig.non_standard_imageurl != null ?
           window.checkoutConfig.non_standard_imageurl : null;
        }
    };

    return function (target) {
        return target.extend(mixin);
    }
});
