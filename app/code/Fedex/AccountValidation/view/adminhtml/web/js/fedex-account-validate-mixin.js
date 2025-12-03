define([
    'Magento_Ui/js/lib/validation/validator',
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate',
], function (validator, $) {
    'use strict';

    return function () {
        validator.addRule(
            'fedex-account-validate',
            function (value) {
                if (window.isToggleE456656Enabled) {
                    let validator = this;
                    let accountStatus = window.printStatus ? window.printStatus.toLowerCase() : false;
                    let accountType = window.printAccountType ? window.printAccountType.toUpperCase() : '';
                    let holdCode = window.printHoldCode || 0;
                    let holdDescription = window.printDescription || '';
                    let VALID_ACCOUNT_LENGTHS = [9, 10, 14];
                    let regex = /^\d+$/;
                    let DEFAULT_MESSAGE = $.mage.__('Please enter a valid print payment account.');
                    const ERROR_MESSAGES = {
                        invalidFormat: $.mage.__('Please enter a numeric value.'),
                        inactive: $.mage.__('This account is inactive. Please enter a valid print payment account.'),
                        onHold: $.mage.__('This account is on-hold. ' + (holdDescription || '')),
                        notInvoiceable: $.mage.__('This account is not invoiceable. Please enter a valid print payment account.'),
                        forShipping: $.mage.__('This account is set-up for shipping. Please enter a valid print payment account.'),
                    };
                    if(window.printStatus == undefined){
                        accountStatus='intial';
                    }

                    if (value.length !== 0 && !regex.test(value)) {
                        validator.errorMessage = ERROR_MESSAGES.invalidFormat;
                        return false;
                    } else if (value.length === 0) {
                        return true;
                    } else if (!VALID_ACCOUNT_LENGTHS.includes(value.length)) {
                        validator.errorMessage = DEFAULT_MESSAGE;
                        return false;
                    } else if (accountStatus === 'inactive') {
                        if (accountType === 'PAYMENT') {
                            validator.errorMessage = ERROR_MESSAGES.inactive;
                        } else {
                            return false;
                        }
                    } else if (holdCode >= 1) {
                        validator.errorMessage = ERROR_MESSAGES.onHold;
                    } else if (accountStatus === 'active') {
                        if (accountType === 'DISCOUNT') {
                            validator.errorMessage = ERROR_MESSAGES.notInvoiceable;
                        } else if (accountType === 'SHIPPING') {
                            validator.errorMessage = ERROR_MESSAGES.forShipping;
                        } else if (accountType === 'PAYMENT') {
                            return true;
                        } else {
                            return false;
                        }
                    }
                    else if (accountStatus == false) {
                        validator.errorMessage = DEFAULT_MESSAGE;
                        return false;
                    }else {
                        return true;
                    }

                    return false;
                } else {
                    return true;
                }
            },
            function () {
                return this.errorMessage;
            }
        );
    };
});
