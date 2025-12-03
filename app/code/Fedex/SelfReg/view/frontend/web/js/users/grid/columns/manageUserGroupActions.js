/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/grid/columns/actions',
    'Fedex_SelfReg/js/delete-group',
    'mage/url',
    'Magento_Ui/js/modal/modal'
], function($, Actions, DeleteGroup, urlBuilder, modal) {
    'use strict';

    return Actions.extend({
        defaults: {
            bodyTmpl: 'Fedex_SelfReg/users/grid/cells/manageUserGroupActions'
        },
        /**
         * Callback after click on element.
         *
         * @public
         */
        initialize: function () {
            this._super();
        },
        toogleMenu:function()
        {
            var element=this;
            $(this.target).parent().next().toggle();       
            $(document.body).click( function(event) {
                if(event.target!=element.target){
                    $(element.target).parent().next().css("display","none");
                }
            });
        },
        applyAction: function () {
            switch (this.type) {
                case 'edit-group':
                    var options = {
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        modalClass: 'custom-modal-class',
                        buttons: []
                    };
                    $(".new-user-group-header").text('Edit User Group');
                    $("#new-user-group-name").val('');
                    $(".group_change").show();
                    $('#group_type_folder_permissions').prop("disabled", false);
                    $("#group_type_folder_permissions").css("appearance", "");
                    $('#group_type_order_approval').prop("disabled", false);
                    $("#group_type_order_approval").css("appearance", "");
                    $("#group_type_folder_permissions").prop("checked", true);
                    $("#new-user-group-order-approval").hide();
                    $(".users-tag-container").remove();
                    $("#selected_order_approver").val('');
                    $("#siteUrl").val(this.site_url);

                    $.ajax({
                        url: urlBuilder.build('selfreg/users/usergroup'),
                        type: 'POST',
                        showLoader: true,
                        data: {
                            id: this.id
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.output.id) {
                                $("#groupId").val(data.output.id);
                                $(".group_change").hide();
                                $("#new-user-group-name").val(data.output.group_name);
                                $('#group_type_folder_permissions').attr('disabled', 'disabled');
                                $('#group_type_order_approval').attr('disabled', 'disabled');
                                $(".users-tag-container").remove();
                                $.each(data.output.users_list, function(key, value) {
                                    let crossmarksvg = '<svg data-user-id="' + value.id + '" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12ZM19 12C19 15.866 15.866 19 12 19C8.13401 19 5 15.866 5 12C5 8.13401 8.13401 5 12 5C15.866 5 19 8.13401 19 12ZM8.14645 8.85355C7.95118 8.65829 7.95118 8.34171 8.14645 8.14645C8.34171 7.95118 8.65829 7.95118 8.85355 8.14645L12 11.2929L15.1464 8.14645C15.3417 7.95118 15.6583 7.95118 15.8536 8.14645C16.0488 8.34171 16.0488 8.65829 15.8536 8.85355L12.7071 12L15.8536 15.1464C16.0488 15.3417 16.0488 15.6583 15.8536 15.8536C15.6583 16.0488 15.3417 16.0488 15.1464 15.8536L12 12.7071L8.85355 15.8536C8.65829 16.0488 8.34171 16.0488 8.14645 15.8536C7.95118 15.6583 7.95118 15.3417 8.14645 15.1464L11.2929 12L8.14645 8.85355Z" fill="#333333"/></svg></button></div></div>';
                                    $('<div class="users-tag-container"><div class="users-tag-value" >' + value.name + '</div><div class="users-tag-cross userApprover"><button type="button" aria-label="close" data-test-id="E-404291-B-2010881-edit-users-single-user-close-' + value.id + '">' + crossmarksvg + '</button>').insertBefore($(".edit_new_group_users_search"));

                                });
                                $.each(data.output.order_approval_list, function(key, value) {
                                    let crossmarksvg = '<svg data-user-id="' + value.id + '" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12ZM19 12C19 15.866 15.866 19 12 19C8.13401 19 5 15.866 5 12C5 8.13401 8.13401 5 12 5C15.866 5 19 8.13401 19 12ZM8.14645 8.85355C7.95118 8.65829 7.95118 8.34171 8.14645 8.14645C8.34171 7.95118 8.65829 7.95118 8.85355 8.14645L12 11.2929L15.1464 8.14645C15.3417 7.95118 15.6583 7.95118 15.8536 8.14645C16.0488 8.34171 16.0488 8.65829 15.8536 8.85355L12.7071 12L15.8536 15.1464C16.0488 15.3417 16.0488 15.6583 15.8536 15.8536C15.6583 16.0488 15.3417 16.0488 15.1464 15.8536L12 12.7071L8.85355 15.8536C8.65829 16.0488 8.34171 16.0488 8.14645 15.8536C7.95118 15.6583 7.95118 15.3417 8.14645 15.1464L11.2929 12L8.14645 8.85355Z" fill="#333333"/></svg></button></div></div>';
                                    $('<div class="users-tag-container"><div class="users-tag-value" >' + value.name + '</div><div class="users-tag-cross manageOrder" id="manageOrder"><button type="button" aria-label="close" data-test-id="E-404291-B-2010881-edit-users-single-user-close-' + value.id + '">' + crossmarksvg + '</button>').insertBefore($(".edit_users_search"));

                                });
                                $("#selected_order_approver").val(data.output.order_approval);
                                $("#new_group_users_id").val(data.output.users);
                                if (data.output.group_type == 'folder_permissions') {
                                    $('#group_type_folder_permissions').prop('checked', true);
                                    $("#group_type_folder_permissions").css("appearance", "");
                                    $("#group_type_order_approval").css("appearance", "none");
                                    $("#group_type_order_approval").css("border-radius", "50%");
                                    $("#group_type_order_approval").css("background-color", "#E3E3E3");
                                }
                                if (data.output.group_type == 'order_approval') {
                                    $('#group_type_order_approval').prop('checked', true);
                                    $("#group_type_order_approval").css("appearance", "");
                                    $("#new-user-group-order-approval").show();
                                    $("#group_type_folder_permissions").css("appearance", "none");
                                    $("#group_type_folder_permissions").css("border-radius", "50%");
                                    $("#group_type_folder_permissions").css("background-color", "#E3E3E3");
                                }
                            }
                        },
                        error: function(data) {}
                    });
                    const newUserGroupModal = $('#new_user_group_modal_container');
                    if (newUserGroupModal.length) {
                        newUserGroupModal.modal(options).modal('openModal');
                    }
                    break;

                case 'delete-group':
                    $(this).groupDelete(this.options)
                        .trigger('deleteGroup');
                    break;

                default:
                    return true;
            }
        }
    });
});