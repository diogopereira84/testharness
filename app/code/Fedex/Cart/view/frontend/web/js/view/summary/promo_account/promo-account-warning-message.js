/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'uiComponent'
], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_Cart/summary/promo-account-warning-message'
        },

        initialize: function (config) {
            this._super();
            setTimeout(function() {
                if(config.combinedWarningMessageEnable)
                {
                    $("#warning-message-box").attr("style", "");
                }
            }, 5000)
        },

        /*
         * Get warning message for apply promo code and fedex number
         *
         * @return string {*}
         */
        promoCodeFedexNumberWarningMsg: function () {
            return window.checkoutConfig.promo_code_combined_discount_message;
        },

        /*
         * Close warning box
         */
        closeWarningBox: function (data,index) {
            $(index.currentTarget).parent().hide();
        },

        /*
         * Get warning icon image
         */
        promoWarningIconImgUrl: function () {

            return window.checkoutConfig.media_url+'/information.png';
        }
    });
});
