define([
    'jquery',
    'underscore',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/step-navigator',
    "Magento_Customer/js/model/customer",
    "checkoutAdditionalScript",
    'Fedex_ExpressCheckout/js/fcl-profile-pickup-edit',
    'fedex/storage'
], function ($, _, ko, Component, stepNavigator, customer, checkoutAdditionalScript, profilePickEditBuilder,fxoStorage) {
    'use strict';

    /**
     * Checks if current user is FCL or not
     */
    let isLoggedIn = window.checkoutConfig.is_logged_in;
    let isSelfRegCustomer = window.checkoutConfig.is_selfreg_customer;
    let temp = stepNavigator.steps().slice(0,2);
    let steps = (isLoggedIn && !isSelfRegCustomer) ? ko.observableArray(temp) : stepNavigator.steps;

    /**
     * Checks if current logged in user is FCL or not
     */
    let isFclCustomer = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;

    if(isFclCustomer) {
        isFclCustomer = typeof (window.checkoutConfig.is_fcl_customer) !== "undefined" && window.checkoutConfig.is_fcl_customer !== null ? window.checkoutConfig.is_fcl_customer : false;
    }

    return Component.extend({
        defaults: {
            template: 'Magento_Checkout/progress-bar',
            visible: true
        },
        steps: steps,

        /** @inheritdoc */
        initialize: function () {
            let stepsValue;
            console.log("Custom steps");
            this._super();
            window.addEventListener('hashchange', _.bind(stepNavigator.handleHash, stepNavigator));

            if (!window.location.hash) {
                stepsValue = stepNavigator.steps();

                if (stepsValue.length) {
                    stepNavigator.setHash(stepsValue.sort(stepNavigator.sortItems)[0].code);
                }
            }

            stepNavigator.handleHash();
        },

        /**
         * @param {*} itemOne
         * @param {*} itemTwo
         * @return {*|Number}
         */
        sortItems: function (itemOne, itemTwo) {
            return stepNavigator.sortItems(itemOne, itemTwo);
        },

        /**
         * @param {Object} step
         */
        navigateTo: function (step) {
            stepNavigator.navigateTo(step.code);
            let paymentData
            if(window.e383157Toggle){
                paymentData = fxoStorage.get("paymentData");
            }else{
                paymentData = JSON.parse(localStorage.getItem('paymentData'));
            }
            if (step.code == 'shipping') {
                checkoutAdditionalScript.selectedDeliveryOptionChecked();
                if (isFclCustomer) {
                    let isShip,isPick,isExpressCheckout;
                    if(window.e383157Toggle){
                        isShip = fxoStorage.get("shipkey");
                        isPick = fxoStorage.get("pickupkey");
                        isExpressCheckout = fxoStorage.get('express-checkout');
                    }else{
                        isShip = localStorage.getItem("shipkey");
                        isPick = localStorage.getItem("pickupkey");
                        isExpressCheckout = localStorage.getItem('express-checkout');
                    }
                    if (isExpressCheckout && isShip === 'false' && isPick === 'true' && !$(".opc-progress-bar li:nth-child(1)").attr("data-active")) {
                        $("#checkoutSteps .pickup-title-checkout h1").next("a").trigger('click');
                    }
                    if(window.e383157Toggle){
                        fxoStorage.set('editActionOnExpress', true);
                    }else{
                        localStorage.setItem('editActionOnExpress', true);
                    }
                }
            } else if (step.code == 'step_code' && isFclCustomer && paymentData) {
                profilePickEditBuilder.autofillPaymentDetails(paymentData);
                if(window.e383157Toggle){
                    fxoStorage.set('editActionOnExpress', true);
                }else{
                    localStorage.setItem('editActionOnExpress', true);
                }
            }
            // Shipping option not selected issue fix stop
        },

        /**
         * @param {Object} item
         * @return {*|Boolean}
         */
        isProcessed: function (item) {
            return stepNavigator.isProcessed(item.code);
        }
    });
});
