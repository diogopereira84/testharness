/**
 * B-1257375 - Add configuration for site access (use access flow- all registered users, users from specific domain and admin approval)
 * 
 */
define([
   'underscore',
   'uiRegistry',
   'Magento_Ui/js/form/element/single-checkbox',
   'Magento_Ui/js/modal/modal',
   'ko',
   'jquery'
], function (_, uiRegistry, select, modal, ko, $) {
   'use strict';
   return select.extend({

       initialize: function () {
           this._super();
           this.fieldDepend(this.value());
           return this;
       },

       onUpdate: function (value)
       {            
           var field_self_reg_login_method = uiRegistry.get('index = self_reg_login_method');
           var field_domains = uiRegistry.get('index = domains');
           var field_error_message = uiRegistry.get('index = error_message');
           if (value == 0) {
           		field_self_reg_login_method.hide();
           		field_domains.hide();
           		field_error_message.hide();
           }else{
           		field_self_reg_login_method.show();                
                if($('select[name="self_reg_login[self_reg_login_method]"]').find(":selected").val()=='admin_approval'){
                    field_error_message.show();
                }else if($('select[name="self_reg_login[self_reg_login_method]"]').find(":selected").val()=='domain_registration'){                    
                    field_domains.show();
                    field_error_message.show();
                }		
           }
           return this._super();

       },

       fieldDepend: function (value)
       {           
       		$(document).ajaxComplete(function() {              
       			var field_self_reg_login_method = uiRegistry.get('index = self_reg_login_method');
		        var field_domains = uiRegistry.get('index = domains');
		        var field_error_message = uiRegistry.get('index = error_message');
		           if (value == 0) {
		           		field_self_reg_login_method.hide();
		           		field_domains.hide();
		           		field_error_message.hide();
		           }else{
		           		field_self_reg_login_method.show();
                        if($('select[name="self_reg_login[self_reg_login_method]"]').find(":selected").val()=='admin_approval'){
                            field_error_message.show();
                        }else if($('select[name="self_reg_login[self_reg_login_method]"]').find(":selected").val()=='domain_registration'){                    
                            field_domains.show();
                            field_error_message.show();
                        }
		           }
       		});
       }
   });

});