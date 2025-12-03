define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/checkbox-set',
    'jquery',
    'Fedex_Company/js/creditcard/creditcard-handler'
], function (_, uiRegistry, checkbox, $, CreditCardHandler) {
    'use strict';

    $(document).on("click", '.fedex-payment-methods .fieldset-wrapper-title', function (e) {
        setTimeout(function () {
            CreditCardHandler.addViewCreditCardLink();
        }, 300);
    });

    return checkbox.extend({

        /**
         * Initialize component handler
         * 
         * @returns
         */
        initialize: function () {
            let options = this._super().initialValue;
            this.showHideFields(options);

            return this;
        },

        /**
         * On value change handler.
         *
         * @param {Array} options
         */
        onUpdate: function (options) {
            this.showHideFields(options);
            this._super();
        },

        /**
         * Show / Hide fields
         *
         * @param {*} options 
         */
        showHideFields: function (options) {
            var self = this;
            setTimeout(function () {
                let fedexAccountOptions = uiRegistry.get('index = fedex_account_options');
                if (typeof fedexAccountOptions != 'undefined') {
                    if ($.inArray("fedexaccountnumber", options) !== -1) {
                        if(fedexAccountOptions.value() == 'custom_fedex_account') {
                            self.showHideAccountFields('show', true);
                        } else {
                            self.showHideAccountFields('hide', true);
                        }
                        fedexAccountOptions.show();
                    } else {
                        fedexAccountOptions.value('legacyaccountnumber');
                        self.showHideAccountFields('hide', true);
                        fedexAccountOptions.hide();
                    }
                }

                let creditcardOptions = uiRegistry.get('index = creditcard_options');
                if (typeof creditcardOptions != 'undefined') {
                    if ($.inArray("creditcard", options) !== -1) {
                        self.showHideAccountFields('show');
                        creditcardOptions.show();
                        CreditCardHandler.addViewCreditCardLink();
                    } else {
                        creditcardOptions.hide();
                    }
                }
                let defaultPaymentMethod = uiRegistry.get('index = default_payment_method');
                if (typeof defaultPaymentMethod != 'undefined') {
                    if ($.inArray("fedexaccountnumber", options) !== -1 && $.inArray("creditcard", options) !== -1) {
                        if(fedexAccountOptions.value() == 'custom_fedex_account') {
                            self.showHideAccountFields('show', true);
                        } else {
                            self.showHideAccountFields('hide', true);
                        }
                        defaultPaymentMethod.show();
                    } else {
                        defaultPaymentMethod.hide();
                    }
                }

            }, 500);
        },

        /**
         * Show / Hide Account fields
         *
         * @param {*} type
         */
        showHideAccountFields: function (type, account = false) {
            let formFields = [
                'fxo_shipping_account_number',
                'fxo_discount_account_number',
                'shipping_account_number_editable',
                'discount_account_number_editable'
            ];
            if (account) {
                formFields.push('fxo_account_number');
                formFields.push('fxo_account_number_editable');
            } else {
                const fxo_number_index = formFields.indexOf('fxo_account_number');
                if (fxo_number_index > -1) {
                    formFields.splice(fxo_number_index, 1);
                }
                const fxo_number_editable_index = formFields.indexOf('fxo_account_number_editable');
                if (fxo_number_editable_index > -1) {
                    formFields.splice(fxo_number_editable_index, 1);
                }
            }
            
            if (window.fedexAccountCCToggle) {
                uiRegistry.get(function (component) {
                    $.each(formFields, function (index, value) {
                        let field = uiRegistry.get('index = ' + value);
                        if (typeof field !== 'undefined') {
                            if (type == 'hide') {
                                field.hide();
                            } else {
                                field.show();
                            }
                        }
                    });
                });
            }
        }
    });
});
