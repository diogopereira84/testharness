/**
 * Grand Total Mixin
 */

define([], function () {
    'use strict';

    /**
     * Mixin for Grand total to add methods for SDE.
     */
    var mixin = {
        /**
         * Check if store is SDE or not
         *
         * @returns bool
         */
         isSdeStore: function () {
            var is_sde = window.checkoutConfig.is_sde_store != undefined ? Boolean(window.checkoutConfig.is_sde_store) : false;
            if (is_sde === true) {
                return true;
            }
            return false;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});