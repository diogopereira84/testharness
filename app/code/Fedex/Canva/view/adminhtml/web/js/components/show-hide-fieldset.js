define([
    'Magento_Catalog/js/components/visible-on-option/fieldset'
], function (Fieldset) {
    'use strict';

    return Fieldset.extend(
        {
            /**
             * Show element.
             */
            show: function () {
                this.visible(true);
                return this;
            },

            /**
             * Hide element.
             */
            hide: function () {
                this.visible(false);
                return this;
            },
        }
    );
});
