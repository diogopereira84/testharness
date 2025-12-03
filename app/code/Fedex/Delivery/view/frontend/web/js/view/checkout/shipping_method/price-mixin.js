/**
 * @api
 */

 define([
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    "ko"
], function (Component, quote, priceUtils,ko) {
    'use strict';

    return function (Component){

        var isCheckoutConfigAvaliable = typeof (window.checkoutConfig) !== 'undefined' && window.checkoutConfig !== null ? true : false;

        return Component.extend({

            infoUrl:ko.observable(window.checkoutConfig.media_url+"/information.png"),
            /**
            * @param {*} price
            * @return {*|String}
            */
            getFormattedPrice: function (price) {
                //todo add format data
                return price==0 ?"FREE":priceUtils.formatPrice(price, quote.getPriceFormat());
            },

            getShipping : function(shipping_carrier_title) {
                    if(shipping_carrier_title!='FedEx Local Delivery') {
                        return true;
                    }
                    return false;
            }
        });
    }
});
