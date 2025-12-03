/**
 * B-1257375 - Add configuration for site access (use access flow- all registered users, users from specific domain and admin approval)
 *
 */
define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/single-checkbox',
    'jquery'
], function (_, uiRegistry, singleCheckbox, $) {
    'use strict';

    return singleCheckbox.extend({
        initialize: function () {
           this._super();

           this.fieldDepend(this);

           return this;
        },

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            var hc_toggle = uiRegistry.get('index = hc_toggle');
            if(value == 1){
                hc_toggle.show();
            } else {
                hc_toggle.hide();
                hc_toggle.value(0);
            }
            return this._super();
        },

        fieldDepend: function (self)
        {
            $(document).ajaxStop(function() {
                var hc_toggle = uiRegistry.get('index = hc_toggle');
                if(self.value() == 1){
                    hc_toggle.show();
                } else {
                    hc_toggle.hide();
                    hc_toggle.value(0);
                }
            });
        }

    });
});
