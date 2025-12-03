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
            'fedex-discount-validate',
            function (value) {
                if (window.isToggleE456656Enabled) {
                    let validator = this;
                    let accountStatus = window.discountStatus ? window.discountStatus.toLowerCase() : false;
                    let accountType = window.discountAccountType ? window.discountAccountType.toUpperCase() : '';
                    let holdCode = window.discountHoldCode || '';
                    let holdDescription = window.discountDescription || '';
                    let VALID_ACCOUNT_LENGTHS = [9, 10, 14];
                    let regex = /^\d+$/;

                    const ERROR_MESSAGES = {
                        invalidAccount: $.mage.__('Please enter a valid discount account.'),
                        invalidFormat: $.mage.__('Please enter a numeric value.'),
                        inactive: $.mage.__('This account is inactive. Please enter a valid discount account.'),
                        onHold: $.mage.__('This account is on-hold. ' + (holdDescription || '')),
                        forShipping: $.mage.__('This account is set-up for shipping. Please enter a valid discount account or print payment account.'),
                       };
                    if(window.discountStatus == undefined){
                        accountStatus='intial';
                    }

                    if (value.length !== 0 && !regex.test(value)) {
                        validator.errorMessage = ERROR_MESSAGES.invalidFormat;
                        return false;
                    } else if (value.length === 0) {
                        return true;
                    } else if (!VALID_ACCOUNT_LENGTHS.includes(value.length)) {
                        validator.errorMessage = ERROR_MESSAGES.invalidAccount;
                        return false;
                    }  else if (accountStatus === 'inactive') {
                        if (accountType === 'DISCOUNT') {
                            validator.errorMessage = ERROR_MESSAGES.inactive;
                        } else {
                            return false;
                        }
                    } else if (holdCode >= 1) {
                        validator.errorMessage = ERROR_MESSAGES.onHold;
                    } else if (accountStatus === 'active') {
                        if (accountType === 'SHIPPING') {
                            validator.errorMessage = ERROR_MESSAGES.forShipping;
                        }  else if (accountType === 'DISCOUNT' || accountType === 'PAYMENT') {
                            return true;
                        } else {
                            return true;
                        }
                    } else if (accountStatus == false) {
                        validator.errorMessage =  ERROR_MESSAGES.invalidAccount;
                        return false;
                    }
                    else {
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
