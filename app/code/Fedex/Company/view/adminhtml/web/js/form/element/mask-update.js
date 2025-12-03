
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
            const masks = {
                'validate-number': '^\\s*-?\\d*(\\.\\d*)?\\s*$',
                'validate-alpha': '^[a-zA-Z]+$',
                'validate-number-6': '^[0-9]{6,6}$',
                'validate-alpha-6': '^[a-zA-Z]{6}$',
                'custom': '',
            };
            const container = uiRegistry.get(this.parentName);
            if (container !== undefined) {
                const customMask = container.getChild('custom_mask');
                const required = container.getChild('required');
                const errorMessage = container.getChild('error_message');

                if (null === value || '' === value || masks[value] === undefined) {
                    customMask.value('')
                    customMask.disable()
                    customMask.validate()
                     errorMessage.disable();
                    if (required.value() === '0') {
                        errorMessage.disable();
                        return;
                    }
                 return;
                }
                    errorMessage?.enable();
                customMask.value(masks[value])
                customMask.enable()

                if (value !== 'custom') {
                    customMask.disable();
                }
            }
            this._super();
        },

    });
});
