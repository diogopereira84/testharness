/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
    "Magento_Ui/js/lib/view/utils/dom-observer",
    'underscore'
    ], function($, customerData, $dom){
        $.widget('mage.minicartExpiryMessage', {

            options: {
                isEnabled: true,
                miniCartSelector: '[data-block=\'minicart\']',
                expressCheckoutButtonSelector: '#mini-cart-express-checkout'
            },

            /**
             * Widget initialization
             */
            _create: function() {
                if (this.options.isEnabled) {
                    this._bindEvent();
                }
            },

            /**
             * To bind the event on html element
             */
            _bindEvent: function(){
                let self = this;
                $dom.get(this.options.expressCheckoutButtonSelector, function(elem) {
                    self.disbaledExpressCheckout();
                });
                $(this.options.miniCartSelector).on('dropdowndialogopen', function () {
                    self.disbaledMinicartQty();
                });
            },

            /**
             * To disable express checkout button
             */
            disbaledExpressCheckout: function(){
                let cartData = customerData.get('cart');
                if ($(this.options.expressCheckoutButtonSelector).length && cartData().expired_msg !== undefined) {
                    $(this.options.expressCheckoutButtonSelector).addClass("disabled"); 
                }
            },

            /**
             * To disable minicart qty
             */
             disbaledMinicartQty: function() {
                let cartData = customerData.get('cart'),
                items = !_.isUndefined(cartData().items) ? cartData().items : [];
                _(items).each(function(item, i) {
                    if (!_.isUndefined(item.is_expired) && item.is_expired == true) {
                        $dom.get("#cart-item-"+item.item_id+"-qty", function(elem) {
                            elem.disabled = true;
                        });
                    }
                });
            }

        });
        
    return $.mage.minicartExpiryMessage;
});
