define([
    'underscore',
    'Magento_Ui/js/form/element/select',
    'jquery'
], function (_, select, $) {
    'use strict';

    return select.extend({

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            if (value == 'accountnumbers') {
                $('.shipping_account_number').show();
                $('.discount_account_number').show();
                $('.fedex_account_number').show();

                return this._super();
            } else {
                $('.fedex_account_number').hide();
                $('.discount_account_number').hide();
                $('.shipping_account_number').hide();
            }
        }
    });
});