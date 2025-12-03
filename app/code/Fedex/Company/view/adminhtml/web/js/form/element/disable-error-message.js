
define([
    'uiRegistry',
    'Magento_Ui/js/form/element/select'
], function (uiRegistry, select) {
    'use strict';

    return select.extend({
        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            const container = uiRegistry.get(this.parentName);

            if (container !== undefined) {
                const selectMask = container.getChild('mask');

                if (selectMask.value() === 'custom') {
                    const customMask = container.getChild('custom_mask');
                    const errorMessage = container.getChild('error_message');
                    customMask.enable();
                    errorMessage.enable();
                    return;
                }
            }
            this._super();
        },

    });
});
