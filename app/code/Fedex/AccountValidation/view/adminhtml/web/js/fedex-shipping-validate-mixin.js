define([
    'Magento_Ui/js/lib/validation/validator',
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate',
], function (validator, $) {
    "use strict";

    return function () {
        validator.addRule(
            'fedex-shipping-validate',
            function (value) {
                if (window.isToggleE456656Enabled) {
                    let validator = this;
                    let accountStatus = window.shippingStatus ? window.shippingStatus.toLowerCase() : false;
                    let accountType = window.shippingAccountType ? window.shippingAccountType.toUpperCase() : '';
                    let holdCode = window.shippingHoldCode || '';
                    let holdDescription = window.shippingDescription || '';
                    let VALID_ACCOUNT_LENGTHS = [9, 10, 14];
                    let regex = /^\d+$/;

                    const ERROR_MESSAGES = {
                        invalidFormat: $.mage.__('Please enter a numeric value.'),
                        invalidAccount: $.mage.__('Please enter a valid shipping account.'),
                        onHold: $.mage.__('This account is on-hold. ' + (holdDescription || '')),
                        inactive: $.mage.__('This account is inactive. Please enter a valid shipping account.'),
                        forPrinting: $.mage.__('This account is set-up for printing. Please enter a valid shipping account.'),
                      };
                    if(window.shippingStatus == undefined){
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
                    } else if (holdCode === 2) {
                        validator.errorMessage = ERROR_MESSAGES.onHold;
                        return false;
                    } else if (accountStatus === 'inactive') {
                        if (accountType === 'SHIPPING') {
                            validator.errorMessage = ERROR_MESSAGES.inactive;
                            return false;
                        }
                        validator.errorMessage = ERROR_MESSAGES.invalidAccount;
                        return false;
                    } else if (accountStatus === 'active') {
                        if (accountType === 'PAYMENT') {
                            validator.errorMessage = ERROR_MESSAGES.forPrinting;
                            return false;
                        } else if (accountType === 'SHIPPING') {
                            return true;
                        } else if (accountType === 'DISCOUNT') {
                            validator.errorMessage = ERROR_MESSAGES.forPrinting;
                            return false;
                        }
                    }else if (accountStatus == false) {
                        validator.errorMessage =  ERROR_MESSAGES.invalidAccount;
                        return false;
                    } else {
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
