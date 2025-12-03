define([
'jquery',
    'ko',
    'checkout-common',
    "Magento_Checkout/js/model/step-navigator",
], function ($, ko, checkoutCommon, stepNavigator) {
    'use strict';

    return function(progressBar) {
        return progressBar.extend({
            defaults: {
                template: 'Fedex_MarketplaceCheckout/checkout/progress-bar',
                visible: true
            },

            initialize: function() {
                this.mapSteps();

                this._super();
            },

            mapSteps: function() {
                let steps = stepNavigator.steps();
                steps = steps.map(step => {
                    // Using "spread operator" to populate object.
                    if(step.code === 'shipping') return ({...step, title: 'Delivery Method'})
                    return step;
                })

                stepNavigator.steps(steps);
            },

            handleLabelType: function() {
                let progressBarDeliveryStepLabel = $(".opc-progress-bar li:first-child > span");
                let isEproStore = window.checkoutConfig.is_epro;
                let labelText = progressBarDeliveryStepLabel[0].innerHTML;

                if (!isEproStore) {
                    progressBarDeliveryStepLabel.replaceWith($('<small>' + labelText + '</small>'));
                }
            },

            /**
             * Return true if not pricable item added in the cart
             *
             * @return Bool
             */
             isCheckoutQuotePriceDashable: function() {
                return typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
            }
        })
    }
});
