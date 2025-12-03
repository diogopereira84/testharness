define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal',
    'jquery'
], function (_, uiRegistry, select, modal,$) {
    'use strict';

    return select.extend({

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
        	//$("input .dynrule").val("");
            //$("input .dyncrule").val("");
            if (value=='both') {
                $('.auth_rule').show();
            } else {
                $('.auth_rule').hide();
                $('.'+value).show();
            }

            return this._super();
        },
    });
});
