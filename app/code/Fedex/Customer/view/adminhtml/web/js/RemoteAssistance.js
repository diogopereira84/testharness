define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/single-checkbox',
    'jquery'
], function (_, uiRegistry, checkbox,$) {
    return checkbox.extend({      
        initialize: function () {
            this._super();
            if ($('body').hasClass('mazegeeks_ctc_admin_impersonator')) {
                this.visible(false);
             }
        }
    });
});