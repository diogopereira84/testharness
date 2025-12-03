/**
 * B-2261084 -  Modify visibility logic of NSC company level toggle
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
            let allowNonStandardCatalog = uiRegistry.get('index = allow_non_standard_catalog');
            if(value !== undefined && value == 1) {
                allowNonStandardCatalog.show();
            } else {
                allowNonStandardCatalog.hide();
                allowNonStandardCatalog.value(0);
            }
            return this._super();
        },

        fieldDepend: function (self)
        {
            $(document).ajaxStop(function() {
                let allowNonStandardCatalog = uiRegistry.get('index = allow_non_standard_catalog');
                if(self.value() !== undefined && self.value() == 1){
                    allowNonStandardCatalog.show();
                } else {
                    allowNonStandardCatalog.hide();
                    allowNonStandardCatalog.value(0);
                }
            });
        }

    });
});
