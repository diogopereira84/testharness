define([
    'ko',
    'uiComponent',
    'jquery',
    'Magento_Checkout/js/model/step-navigator',
    'mage/url',
    'fedex/storage',
    'uiRegistry',
], function (ko, Component, $, stepNavigator,urlBuilder,fxoStorage,registry) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_SubmitOrderSidebar/modal-time-mismatch'
        },

        initialize: function () {
            this._super();
            this.isPromiseTimeWarningToggleEnabled = ko.observable(window.checkoutConfig.promise_time_warning_enabled || false);
            this.updatedTime = ko.observable(null);
        },

        pendingOrderIconImgUrl: function () {    
            return typeof(window.checkoutConfig.xmen_order_approval_warning_icon) != 'undefined' && typeof(window.checkoutConfig.xmen_order_approval_warning_icon) != null ? window.checkoutConfig.xmen_order_approval_warning_icon : '';
        },

        /**
         * Handles "Accept New Time" CTA
         */
        acceptNewTime: function () {
            var updatedTime = $('.updated-time').first().text().trim();
            if (updatedTime) {
                this.updatedTime(updatedTime);
                $('[data-bind="text: estimated_pickup_time"]').text(updatedTime).trigger('change');
                var updateEarliesturl = urlBuilder.build('submitorder/quote/updateearliesttime');
                if(window.e383157Toggle){
                    fxoStorage.set("pickupDateTime", updatedTime);
                }else{
                    localStorage.setItem("pickupDateTime", updatedTime);
                }
                $('.pickup-date-time').text(updatedTime);
                $.ajax({
                    url: updateEarliesturl,
                    type: 'POST',
                    data: "data=" + updatedTime,
                    success: function (response) {
                        console.log(response)
                        if (response.success) {
                            console.log('Pickup time updated successfully.');
                        } else {
                            console.error('Error updating pickup time.');
                        }
                    },
                        error: function (status, error) {
                        console.error('AJAX error:', status, error);
                    }
                });
            }
            $('#time-change-modal-pickup, #time-change-modal-shipping').trigger('closeModal');
        },

        /**
         * Handles "View Options" CTA
         */
        viewOptions: function () {
            $('#time-change-modal-pickup, #time-change-modal-shipping').trigger('closeModal');
            stepNavigator.navigateTo('shipping');
            registry.async('checkout.steps.shipping-step.shippingAddress')(function(shippingComponent) {
                if (shippingComponent && typeof shippingComponent.onClickSearchLocation === 'function') {
                    shippingComponent.onClickSearchLocation();
                }
            });
        }
    });
});