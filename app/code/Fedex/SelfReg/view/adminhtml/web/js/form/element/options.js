/**
 * B-1257375 - Add configuration for site access (use access flow- all registered users, users from specific domain and admin approval)
 *
 */
define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal',
    'jquery'
], function (_, uiRegistry, select, modal,$) {
    'use strict';

    return select.extend({

        initialize: function () {
           this._super();
           var self = this;
           this.fieldDepend(self);
           return this;
        },

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            var field_domains = uiRegistry.get('index = domains');
            var field_error_message = uiRegistry.get('index = error_message');
            if(value=='domain_registration'){
                field_domains.show();
                field_error_message.show();
                //B-1716167
                $('textarea[name="authentication_rule[error_message]"]').val('').change();
                $('textarea[name="authentication_rule[error_message]"]').val('Your access request has been denied because your email address is not authorized.').change();
            }else if(value=='admin_approval'){
                field_error_message.show();
                field_domains.hide();
                //B-1716167
                $('textarea[name="authentication_rule[error_message]"]').val('').change();
                $('textarea[name="authentication_rule[error_message]"]').val('Your access request has been submitted. You will receive an email confirmation upon approval.').change();
            }else{
                field_domains.hide();
                field_error_message.hide();
            }
            return this._super();
        },

        fieldDepend: function (self)
        {
            $(document).ready(function(){
                var value = self.value();
                var field_self_reg_login_method = uiRegistry.get('index = self_reg_login_method');
                var field_domains = uiRegistry.get('index = domains');
                var field_error_message = uiRegistry.get('index = error_message');
                if(value=='domain_registration'){
                    field_domains.show();
                    field_error_message.show();
                    //B-1716167
                    $('textarea[name="authentication_rule[error_message]"]').val('').change();
                    $('textarea[name="authentication_rule[error_message]"]').val('Your access request has been denied because your email address is not authorized.').change();
                }else if(value=='admin_approval'){
                    field_error_message.show();
                    field_domains.hide();
                    //B-1716167
                    $('textarea[name="authentication_rule[error_message]"]').val('').change();
                    $('textarea[name="authentication_rule[error_message]"]').val('Your access request has been submitted. You will receive an email confirmation upon approval.').change();
                }else{
                    field_domains.hide();
                    field_error_message.hide();
                }

            });
        }

    });
});
