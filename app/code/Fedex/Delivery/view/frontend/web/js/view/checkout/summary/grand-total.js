/**
 * Grand Total Mixin
 */

 define([
    "shippingFormAdditionalScript"
], function (shippingFormAdditionalScript) {
    'use strict';

    /**
     * Mixin for Grand total to add methods for isShippingAcountPlacement.
     */
    var mixin = {
        /**
         * Check if isSdeStore is on or not
         *
         * @returns bool
         */
        isSdeStore: function () {
            var isSdeStore = window.checkoutConfig.is_sde_store != undefined ? Boolean(window.checkoutConfig.is_sde_store) : false;
            if (isSdeStore === true) {
                return true;
            }
            return false;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
