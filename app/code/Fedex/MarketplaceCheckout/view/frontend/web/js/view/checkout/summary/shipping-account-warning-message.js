/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
    'uiComponent',
], function ($, ko, quoteHelper, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_MarketplaceCheckout/checkout/summary/shipping-account-warning'
        },

        shippingAccountNumber: ko.observable(window.checkoutConfig.shipping_account_number),
        
        initialize: function () {
            this._super();

            setTimeout(function() {
                if(this.shippingAccountNumber && quoteHelper.isMixedQuote()) {
                    $("#shipping-warning-message-box").attr("style", "");    
                }
            }, 5000)
        },

        /*
         * Get warning message for apply promo code and fedex number
         *
         * @return string {*} 
         */
        shippingAccountFedexNumberWarningMsg: function () {
            return window.checkoutConfig.shippingAccountMessage;
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
        warningIconImgUrl: function () {
            return window.checkoutConfig.media_url+'/information.png';
        }
    });
});
