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

                validation.clearDiscountStatus();

                if (validation.isValidAccountNumber(fxoAccountNumber)) {
                    this.validateAccountNumber(fxoAccountNumber);
                } else {
                    validation.resetDiscountStatus();
                }
            }
        },

        /**
         * Make an AJAX request to validate the FXO discount account number.
         *
         * @param {string} accountNumber - The FXO discount account number
         */
        validateAccountNumber: function (accountNumber) {
            const element = this;

            validation.validateAccountNumber(accountNumber, window.accountValidationUrl,
                { 'fxo-discount-account-number': accountNumber },
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
            window.discountStatus = result.status ?? false;
            window.discountAccountType = result.accountType ?? '';
            window.discountHoldCode = result.holdCode ?? '';
            window.discountDescription = result.holdCodeDescription ?? '';
            this.validate();
        },

        /**
         * Handle validation failure by resetting the discount status.
         */
        handleValidationFailure: function () {
            window.discountStatus = false;
            this.validate();
        }
    });
});
