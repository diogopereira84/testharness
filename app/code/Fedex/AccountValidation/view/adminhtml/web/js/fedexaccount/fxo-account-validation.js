define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'mage/url',
    'Fedex_AccountValidation/js/fedexaccount/validation'
], function (Element, $, urlBuilder, validation) {
    'use strict';

    const FXO_ACCOUNT_NUMBER_LENGTH = 9;
    const FXO_ACCOUNT_NUMBER_MAX_LENGTH = 10;

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

                validation.clearPrintStatus();

                if (validation.isValidAccountNumber(fxoAccountNumber)) {
                    this.validateAccountNumber(fxoAccountNumber);
                } else {
                    validation.resetPrintStatus();
                }
            }
        },

        /**
         * Make an AJAX request to validate the FXO account number.
         *
         * @param {string} accountNumber - The FXO account number
         */
        validateAccountNumber: function (accountNumber) {
            const element = this;

            validation.validateAccountNumber(accountNumber, window.accountValidationUrl,
                { 'fxo-account-number': accountNumber },
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
            window.printStatus = result.status ?? false;
            window.printAccountType = result.accountType ?? '';
            window.printHoldCode = result.holdCode ?? '';
            window.printDescription = result.holdCodeDescription ?? '';
            this.validate();
        },

        /**
         * Handle validation failure by resetting the print status.
         */
        handleValidationFailure: function () {
            window.printStatus = false;
            this.validate();
        }
    });
});
