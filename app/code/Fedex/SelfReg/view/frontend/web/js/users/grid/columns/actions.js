/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/grid/columns/actions',
    'Magento_Company/js/user-edit',
    'Magento_Company/js/user-delete',
    'Magento_Company/js/role-delete'
], function ($, Actions) {
    'use strict';

    return Actions.extend({
        defaults: {
            bodyTmpl: 'Fedex_SelfReg/users/grid/cells/actions'
        },

        /**
         * Callback after click on element.
         *
         * @public
         */
        initialize: function () {
            this._super();
            var userPermissionRoles = typeof (window.checkout.user_roles_permission) != 'undefined' && window.checkout.user_roles_permission != null ? window.checkout.user_roles_permission : false;
            if(!userPermissionRoles)
            {
               this.bodyTmpl='Magento_Company/users/grid/cells/actions';
            }
        },
        toogleMenu:function()
        {
            if($('.selected-number').is(':hidden')){
                var element=this;
                $(this.target).parent().next().toggle();       
                $(document.body).click( function(event) {
                    if(event.target!=element.target){
                    $(element.target).parent().next().css("display","none");
                    }
                });
            }
        },
        applyAction: function () {
            switch (this.type) {
                case 'edit-user':
                    $(this).userEdit(this.options)
                        .trigger('editUser');
                    break;

                case 'delete-user':
                    $(this).userDelete(this.options)
                        .trigger('deleteUser');
                    break;

                case 'delete-role':
                    $(this).roleDelete(this.options)
                        .trigger('deleteRole');
                    break;

                default:
                    return true;
            }
        }
    });
});