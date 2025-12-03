define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'mage/url',
    'Fedex_AccountValidation/js/fedexaccount/validation'
], function (Element, $, urlBuilder, validation) {
    'use strict';

    return Element.extend({
        /**
         * Validate the FXO account number.
         *
         * @param {Object} ui - The UI component instance
         * @param {Event} e - The event triggered by the input change
         */
        getValidatedValue: function (ui, e) {
            if (window.isToggleE456656Enabled) {
                const fxoAccountNumber = e.target.value;
                this.value(fxoAccountNumber);
                validation.clearShippingStatus();
                if (validation.isValidAccountNumber(fxoAccountNumber)) {
                    this.validateAccountNumber(fxoAccountNumber);
                } else {
                    validation.resetShippingStatus();
                }
            }
        },

        /**
         * Make an AJAX request to validate the FXO shipping account number.
         *
         * @param {string} accountNumber - The FXO shipping account number
         */
        validateAccountNumber: function (accountNumber) {
            const element = this;

            validation.validateAccountNumber(accountNumber, window.accountValidationUrl,
                { 'fxo-shipping-account-number': accountNumber },
                function (result) {
                    element.handleValidationResult(result);
                },
                function (jqXHR, textStatus, errorThrown) {
                    console.error('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                    element.handleValidationFailure();
                });
        },

        /**
         * Handle the result of the account validation request.
         *
         * @param {Object} result - The result returned from the server
         */
        handleValidationResult: function (result) {
            window.shippingStatus = result.status ?? false;
            window.shippingAccountType = result.accountType ?? '';
            window.shippingHoldCode = result.holdCode ?? '';
            window.shippingDescription = result.holdCodeDescription ?? '';
            this.validate();
        },

        /**
         * Handle validation failure by resetting the shipping status.
         */
        handleValidationFailure: function () {
            window.shippingStatus = false;
            this.validate();
        }
    });
});
