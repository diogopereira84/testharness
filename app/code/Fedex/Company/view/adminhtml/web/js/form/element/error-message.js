
define([
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract'
], function (uiRegistry, abstract) {
    'use strict';

    return abstract.extend({

        /**
         * Initialize component handler
         *
         * @returns
         */
        initialize: function () {
             this._super();
            const container = uiRegistry.get(this.parentName);
                

            if (container !== undefined) {
                
                container.data().mask !== undefined ? this.enable() : this.disable();
                return
            }

            this.enable();

            return this;

        }

    });
});
