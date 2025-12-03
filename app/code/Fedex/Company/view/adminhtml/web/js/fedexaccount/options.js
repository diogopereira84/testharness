/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/checkbox-set',
    'uiRegistry',
    'jquery'
], function (Checkbox, registry, $) {
    'use strict';

    return Checkbox.extend({

        /**
         * Initialize component handler
         */
        initialize: function () {
            if (this._super().initialValue == 'custom_fedex_account') {
                this.showHideFields('show');
            } else {
                this.showHideFields('hide');
            }
            this._super();
        },

        /**
         * On value change handler.
         */
        onUpdate: function () {
            if (this.value() == 'custom_fedex_account') {
                this.showHideFields('show');
            } else {
                this.showHideFields('hide');
            }

            this._super();
        },

        /**
         * Show / Hide fields
         *
         * @param {*} type
         */
        showHideFields: function (type) {
            let formFields = [
                'fxo_account_number',
                'fxo_shipping_account_number',
                'fxo_discount_account_number',
                'fxo_account_number_editable',
                'shipping_account_number_editable',
                'discount_account_number_editable'
            ];

            registry.get(function (component) {
                $.each(formFields, function (index, value) {
                    let field = registry.get('index = ' + value);
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
    });
});
