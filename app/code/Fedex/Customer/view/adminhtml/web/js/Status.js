define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'jquery'
], function (_, uiRegistry, select,$) {
    return select.extend({      
        initialize: function () {
            this._super();
            this.d_193860_fix = $('body').hasClass('mazegeeks_d_193860_fix');
            this.toggleData = $('body').hasClass('self_reg_admin_updates');
            if (this.d_193860_fix && this.toggleData) {
            let statusfield = uiRegistry.get('index=extension_attributes.company_attributes.status');
              statusfield.visible(false);
            } else if (!this.toggleData && this.d_193860_fix) {
                this.visible(false);
            }
        },

        onUpdate: function (value) {
            if (this.d_193860_fix && this.toggleData) {
            let statusfield = uiRegistry.get('index=extension_attributes.company_attributes.status');
            if ((value==0||value==2) && statusfield.value()==1) {
               $("input[name='"+statusfield.inputName+"']").click();
            } else if (value==1 && statusfield.value()==0) {
                $("input[name='"+statusfield.inputName+"']").click();
            }}
            return this._super();
        },
    });
});