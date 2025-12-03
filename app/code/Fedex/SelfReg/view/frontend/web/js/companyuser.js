/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/
require([
    'jquery',
    'mage/url',
    'fedex/storage'
],function($, urlBuilder, fxoStorage){
    $("#add-user-form #saveuser").on("click", function(){
        var saveRequestUrl =  urlBuilder.build('selfreg/customer/save/');
        var input = $('.customer_id').val();
        var fname = $('#firstname').val();
        var lname = $('#lastname').val();
        var cemail = $('#email').val();
        var crole = $('#role').val();
        var editedUserGroupId = $('#editedUserGroupId').val();
        var cstatus;
        var rolePermissions = {};
        var emailApproval = "";
        $('#add-user-form .edit_single_role_permissions:checked').each(function(){
            rolePermissions[$(this).attr("id")] = $(this).val();
        });
        emailApproval = $('#add-user-form input[name="email-approval"]:checked').val();
        cstatus = $('#add-user-form input[name="status"]:checked').val();
        $.ajax(saveRequestUrl, {
            type: 'POST',  // http method
            showLoader: true,
            data: {
                customer_id:input,
                firstname:fname,
                lastname:lname,
                email:cemail,
                role:crole,
                group:editedUserGroupId,
                status:cstatus,
                rolePermissions,
                emailApproval: emailApproval
            },  // data to submit
            dataType: 'json',
            success: function (response, status, xhr) {
                let msg;
                if (window.e383157Toggle) {
                    fxoStorage.set("user-edited", "true");
                    msg = response.message;
                    fxoStorage.set("user-edited-response", msg);
                } else {
                    localStorage.setItem("user-edited", "true");
                    msg = response.message;
                    localStorage.setItem("user-edited-response", msg);
                }
                location.reload();
            }
        });
    });
    $(document).ready(function () {
        let isUserEdited;
        let alertBoxContainer = $('.user-edit-success');
        if (window.e383157Toggle) {
            isUserEdited = fxoStorage.get("user-edited");
        } else {
            isUserEdited = localStorage.getItem("user-edited");
        }
        if (isUserEdited === 'true') {
            if ($(window).width() < 639) {
                if (!$( "body" ).hasClass( "update_roles_and_permission" )) {
                    $('.data-grid.data.table tr').find('th:last-child, td:last-child').attr('data-th','');
                }
                $('.data-grid.data.table tr').find('td:eq(2) > div').css('margin-left','58px');
                $('.data-grid.data.table tr').find('td:eq(3) > div').css('margin-left','45px');
            }
            alertBoxContainer.show();
            let userEditedResponse;
            if (window.e383157Toggle) {
                userEditedResponse = fxoStorage.get("user-edited-response");
            } else {
                userEditedResponse = localStorage.getItem("user-edited-response");
            }
            $('.user-edit-notification-msg').text(userEditedResponse);
            $('.user-edit-close-icon').on('click', function () {
                alertBoxContainer.hide();
                if (window.e383157Toggle) {
                    fxoStorage.set("user-edited", "false");
                } else {
                    localStorage.setItem("user-edited", "false");
                }
            });
        } else {
            if (window.e383157Toggle) {
                fxoStorage.set("user-edited", "false");
            } else {
                localStorage.setItem("user-edited", "false");
            }
            alertBoxContainer.hide();
        }
        $(document).on("click", function (e) {
            if (!$(e.target).is(".user-edit-success")) {
                if ($('.user-edit-success').is(':visible')) {
                    $('.user-edit-success').hide();
                    if (window.e383157Toggle) {
                        fxoStorage.set("user-edited", "false");
                    } else {
                        localStorage.setItem("user-edited", "false");
                    }
                }
            }
        });
        if (window.e383157Toggle) {
            fxoStorage.set("user-edited", "false");
        } else {
            localStorage.setItem("user-edited", "false");
        }
    });
});
