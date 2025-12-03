/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Checkout/js/view/summary',
    'Magento_Checkout/js/model/step-navigator',
    'shippingFormAdditionalScript'
], function (
    $,
    Component,
    stepNavigator,
    shippingFormAdditionalScript
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_Cart/summary/review-order-submit-button'
        },

        initialize: function () {
            this._super();
            var self = this;
            // B-1415208 : Make terms and conditions mandatory
            $(document).on('click', '.checkout-agreements input.agreement_enable', function () {
                shippingFormAdditionalScript.hasAgreedToTermsAndConditions();
            });

        },

        isCheckoutFirstStep: function () {
            var firstStep = stepNavigator.getActiveItemIndex() == 0;

            return firstStep;
        },

        isCheckoutPaymentStep: function () {
            var paymentStep = stepNavigator.getActiveItemIndex() == 1;

            return paymentStep;
        },

        isVisible: function () {
            return stepNavigator.isProcessed('step_code');
        },

        /*
         * Get review button text
         *
         * @return string {*} 
         */
        reviewButtonText: function () {

            return ('Review Order');
        },

        /*
         * Credit card order review
         *
         * @return void
         */
        creditCardOrderReview: function (data, event) {
            $('button.credit-card-review-button').trigger('click');
        },

        /*
         * Fedex account pay order review
         *
         * @return void
         */
        fedexAccountPayOrderReview: function (data, event) {
            $('button.fedex-account-number-review-button').trigger('click');
        }
    });
});
