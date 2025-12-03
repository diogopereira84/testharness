define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_CheckoutAgreements/js/model/agreements-modal',
    'Magento_Checkout/js/model/step-navigator'
], function (ko, $, Component, agreementsModal, stepNavigator) {
    'use strict';

    var checkoutConfig = window.checkoutConfig,
        agreementManualMode = 1,
        agreementsConfig = checkoutConfig ? checkoutConfig.checkoutAgreements : {};

    var isCheckoutConfig = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;
    var isTermsAndConditionsStatus = false;
    var isSdeCustomer = false;
    var isSelfRegCustomer = false;
    var isEproCustomer = false;

    if (isCheckoutConfig) {

        isTermsAndConditionsStatus = typeof (window.checkoutConfig.terms_and_conditions_enabled) !== "undefined" && window.checkoutConfig.terms_and_conditions_enabled !== null ? window.checkoutConfig.terms_and_conditions_enabled : false;

        isSdeCustomer = typeof (window.checkoutConfig.is_sde_store) !== "undefined" && window.checkoutConfig.is_sde_store !== null ? window.checkoutConfig.is_sde_store : false;

        isSelfRegCustomer = typeof (window.checkoutConfig.is_selfreg_customer) !== "undefined" && window.checkoutConfig.is_selfreg_customer !== null ? window.checkoutConfig.is_selfreg_customer : false;

        isEproCustomer = typeof (window.checkoutConfig.is_commercial) !== "undefined" && window.checkoutConfig.is_commercial !== null ? window.checkoutConfig.is_commercial : false;
    }

    return Component.extend({
        defaults: {
            template: 'Fedex_SubmitOrderSidebar/checkout/checkout-agreements'
        },
        isVisible: agreementsConfig.isEnabled,
        agreements: agreementsConfig.agreements,
        modalTitle: ko.observable(null),
        modalContent: ko.observable(null),
        contentHeight: ko.observable(null),
        modalWindow: null,
        initialize: function () {
            this._super();

            // Show terms and condition in last step only
            this.showTerms = ko.computed(function () {
                if (stepNavigator.isProcessed('payment')
                || window.location.href.includes('checkout#payment')
                || window.location.href.includes('checkout/#payment')
                ) {
                    $("#terms-conditions").show();
                    return true;
                } else {
                    $("#terms-conditions").hide();
                    return false;
                }
            }, this);
        },

        /**
         * Checks if agreement required
         *
         * @param {Object} element
         */
        isAgreementRequired: function (element) {
            return element.mode == agreementManualMode;
        },

        /**
         * Show agreement content in modal
         *
         * @param {Object} element
         */
        showContent: function (element) {
            this.modalTitle(element.checkboxText);
            this.modalContent(element.content);
            this.contentHeight(element.contentHeight ? element.contentHeight : 'auto');
            agreementsModal.showModal();
        },

        /**
         * build a unique id for the term checkbox
         *
         * @param {Object} context - the ko context
         * @param {Number} agreementId
         */
        getCheckboxId: function (context, agreementId) {
            let paymentMethodName = '',
            paymentMethodRenderer = context.$parents[1];

            // corresponding payment method fetched from parent context
            if (paymentMethodRenderer) {
                // item looks like this: {title: "Check / Money order", method: "checkmo"}
                paymentMethodName = paymentMethodRenderer.item ? paymentMethodRenderer.item.method : '';
            }

            return 'agreement_' + paymentMethodName + '_' + agreementId;
        },

        /**
         * Init modal window for rendered element
         *
         * @param {Object} element
         */
        initModal: function (element) {
            agreementsModal.createModal(element);
        },

        /**
         * Checks if all commercial customer
         *
         * @returns Bool
         */
        isAllCommercialCustomer: function () {

            return isSdeCustomer || isSelfRegCustomer || isEproCustomer ? true : false;
        },

        /**
         * Checks if terms and condition enable or disable
         *
         * @returns Bool
         */
        isTermsAndConditionsEnabled: function () {

            return isTermsAndConditionsStatus;
        }
    });
});
