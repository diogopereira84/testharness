define([
    'jquery',
], function ($) {
    'use strict';

    const VALID_ACCOUNT_LENGTHS = [9, 10, 14];

    return {
        /**
         * Clear the print status from the global window object.
         */
        clearPrintStatus: function () {
            delete window.printStatus;
        },
        /**
         * Clear the shipping status from the global window object.
         */
        clearShippingStatus: function () {
            delete window.shippingStatus;
        },
        /**
         * Clear the discount status from the global window object.
         */
        clearDiscountStatus: function () {
            delete window.discountStatus;
        },

        /**
         * Reset the print status globally.
         */
        resetPrintStatus: function () {
            window.printStatus = null;
        },
        /**
         * Reset the shipping status globally.
         */
        resetShippingStatus: function () {
            window.shippingStatus = null;
        },
        /**
         * Reset the discount status globally.
         */
        resetDiscountStatus: function () {
            window.discountStatus = null;
        },

        /**
         * Validate if the account number is correct based on length.
         *
         * @param {string} accountNumber - The FXO account number
         * @returns {boolean} - True if the account number is valid
         */
        isValidAccountNumber: function (accountNumber) {
            return VALID_ACCOUNT_LENGTHS.includes(accountNumber.length);
        },

        /**
         * Make an AJAX request to validate the FXO account number.
         *
         * @param {string} accountNumber - The FXO account number
         * @param {string} validationUrl - The URL to validate the account number
         * @param {Object} data - The data to send in the AJAX request
         * @param {Function} handleValidationResult - The callback for success
         * @param {Function} handleValidationFailure - The callback for failure
         */
        validateAccountNumber: function (accountNumber, validationUrl, data, handleValidationResult, handleValidationFailure) {
            $.ajax({
                url: validationUrl,
                type: 'POST',
                data: data,
                dataType: 'json',
                showLoader: true,
                async: true
            }).done(function (result) {
                handleValidationResult(result);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                handleValidationFailure();
            });
        }
    };
});
