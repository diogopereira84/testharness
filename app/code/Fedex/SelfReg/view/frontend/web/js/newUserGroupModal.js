define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'Fedex_SelfReg/js/componentReloader',
    'mage/validation',
], function($, modal, urlBuilder, reloadGrid) {
    'use strict';
 
    return function(config, element) {
        $('#manager_user_groups_clear_all').on('click', function(e) {
            window.location.reload();
        });

        $("#user-group-search").on('click', function(e) {
            if($(".user-group-search-field").val().length > 2){
                $('#manager_user_groups_clear_all').show();
            }
        });

        $('#user-group-search').on('click', function() {
            var searchStr = $('#keyword').val();
            if(searchStr.length > 2) {
                $('#warning-message-selfreg-user').hide();
                    reloadGrid.reloadUIComponent("manage_user_groups_listing.selfreg_users_manageusergroups_listing_data_source",searchStr);
            } else {
                    $('#warning-message-selfreg-user').html("Please enter at least 3 characters.");
                    $('#warning-message-selfreg-user').show();
            }
        });

        $('.user-group-search-field').keypress(function(e){
            if(e.which == 13){//Enter key pressed
                $('#user-group-search').click();//Trigger search button click event
            }
        });

        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            modalClass: 'custom-modal-class',
            buttons: []
        };
 
        $(element).modal(options);

        $('#group_type_radio input:radio').click(function() {
            if ($(this).val() == 'order_approval') {
                 $("#new-user-group-order-approval").show();
            }else{
                 $("#new-user-group-order-approval").hide();
            }
        });
 
        $(document).on('click', '#new_user_group_button', function() {
            const newUserGroupModal = $('#new_user_group_modal_container');
            if (newUserGroupModal.length) {
                $(".new-user-group-header").text('New User Group');
                $("#groupId").val('');
                $("#siteUrl").val('');
                $("#new-user-group-name").val('');
                $(".group-name-error").hide();
                $(".group-name-error").val('');
                $(".group_change").show();
                $('#group_type_folder_permissions').prop("disabled", false);
                $("#group_type_folder_permissions").css("appearance","");
                $('#group_type_order_approval').prop("disabled", false);
                $("#group_type_order_approval").css("appearance","");
                $("#group_type_folder_permissions").prop("checked",true);
                $("#new-user-group-order-approval").hide();
                $(".users-tag-container").remove();
                $("#new_group_users_id").val('');
                $("#selected_order_approver").val('');
                if (config.groupTypeSection=='none') { // check added if default flow should be for order approval
                    $("#group_type_order_approval").trigger("click");
                }
                newUserGroupModal.modal(options).modal('openModal');
            }
        });

        $(document).on("focus","#new_group_user_area",function(){
            setNewGroupUsersDropDownSerachPosition();
            if($(this).val().length < 3) {
                $("#edit_new_group_users_search_dropdown").html("");
            }
            if($(".edit_users-datalist-wrapper ul li").length > 0) {
                $(".edit_users-datalist-wrapper ul li").each(function(){
                    if($(this).attr("style") === undefined || $(this).attr("style") == "") {
                        $(".edit_new_group_users_datalist_wrapper_new").show();
                    }
                });
            } else {
                $(".edit_new_group_users_datalist_wrapper_new").hide();
            }
        });

        $(document).on("keyup","#new_group_user_area",function(e){
            if(((e.which >= 48 && e.which <= 90) || (e.which >= 96 && e.which <= 105) || e.which === 8 || e.which === 46) && $(this).val().length >= 2) {
                let selectedNewGroupUsersVal = $("#new_group_users_id").val();
                var postdata = {filter : $(this).val() , exclude_user : selectedNewGroupUsersVal, includeCustomerAdmin: true};
                var findUserUrl = urlBuilder.build('selfreg/users/findUsers');

                $.ajax({
                    url: findUserUrl,
                    type: 'POST',
                    dataType: 'json',
                    showLoader: true,
                    data: postdata,
                    success: function(response) {
                        if(response !== undefined && response.status !== undefined && response.status == "success" && response.counter != "0") {
                            $("#edit_new_group_users_search_dropdown").html(response.html);
                            setNewGroupUsersDropDownSerachPosition();
                            $(".edit_new_group_users_datalist_wrapper_new").show();
                        } else {
                            $(".edit_new_group_users_datalist_wrapper_new").hide();
                        }
                    },
                    error: function(xhr, status, errorThrown) {

                    }
                });
            }
        });

        // function for search result placement
        function setNewGroupUsersDropDownSerachPosition()
        {
            var rect = $('.edit_new_group_users_search').position();
            var scrollHeight = document.getElementById('selected_new_group_users_container').scrollHeight;
            var initialHeight = $('#selected_new_group_users_container').height();
            
            var parentLeft = $('#selected_new_group_users_container').offset().left,
                parentTop = $('#selected_new_group_users_container').offset().top,
                childLeft = $('.edit_new_group_users_search').offset().left,
                childTop = $('.edit_new_group_users_search').offset().top;

            var searchInputTopFinal = childTop - parentTop,
            searchInputLeftFinal = childLeft - parentLeft;
            let marginLeft =  rect.left + 5;
            jQuery(".edit_new_group_users_datalist_wrapper_new").css("margin-left", marginLeft+"px");
         
            if(Math.ceil(scrollHeight) > Math.ceil(initialHeight)) {
                jQuery(".edit_new_group_users_datalist_wrapper_new").css("margin-top", "-15px");
            } else {
                let topDistance = initialHeight - (searchInputTopFinal + 32);
                jQuery(".edit_new_group_users_datalist_wrapper_new").css("margin-top", "-"+topDistance+"px");
            }
        }

        // function for applying tag for new user group users
        $(document).on("click", ".edit_new_group_users_datalist_wrapper_new #edit_new_group_users_search_dropdown li", function() {
            let selectedNewGroupListUser = $(this).attr("data-index-id");
            let selectedNewGroupUsersVal = $("#new_group_users_id").val();

            if (selectedNewGroupUsersVal != "") {
                selectedNewGroupUsersVal = selectedNewGroupUsersVal + "," + selectedNewGroupListUser;
            } else {
                selectedNewGroupUsersVal = selectedNewGroupListUser;
            }
            $("#new_group_users_id").val(selectedNewGroupUsersVal);
            let crossmarksvg = '<svg data-user-id="' + selectedNewGroupListUser + '" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12ZM19 12C19 15.866 15.866 19 12 19C8.13401 19 5 15.866 5 12C5 8.13401 8.13401 5 12 5C15.866 5 19 8.13401 19 12ZM8.14645 8.85355C7.95118 8.65829 7.95118 8.34171 8.14645 8.14645C8.34171 7.95118 8.65829 7.95118 8.85355 8.14645L12 11.2929L15.1464 8.14645C15.3417 7.95118 15.6583 7.95118 15.8536 8.14645C16.0488 8.34171 16.0488 8.65829 15.8536 8.85355L12.7071 12L15.8536 15.1464C16.0488 15.3417 16.0488 15.6583 15.8536 15.8536C15.6583 16.0488 15.3417 16.0488 15.1464 15.8536L12 12.7071L8.85355 15.8536C8.65829 16.0488 8.34171 16.0488 8.14645 15.8536C7.95118 15.6583 7.95118 15.3417 8.14645 15.1464L11.2929 12L8.14645 8.85355Z" fill="#333333"/></svg></button></div></div>';
            if ($(".users-tag-container").length >= 4) {
                $('<div class="users-tag-container show-more"><div class="users-tag-value">' + $(this).html() + '</div><div class="users-tag-cross"><button type="button" aria-label="close" data-test-id="B-2051027-edit-users-single-user-close-' + selectedNewGroupUsersVal + '">' + crossmarksvg).insertBefore(".edit_new_group_users_search");
            } else {
                $('<div class="users-tag-container"><div class="users-tag-value">' + $(this).html() + '</div><div class="users-tag-cross"><button type="button" aria-label="close" data-test-id="B-2051027-edit-users-single-user-close-' + selectedNewGroupUsersVal + '">' + crossmarksvg).insertBefore(".edit_new_group_users_search");
            }
            $(this).hide();
            $("#new_group_user_area").val('').focus();
            $(".edit_new_group_users_datalist_wrapper_new").hide();
            setNewGroupUsersDropDownSerachPosition();
        });

        // function to remove tags
        $(document).on("click", ".users-tag-container .userApprover svg", function() {
            let selectedNewGroupUsersVal = $("#new_group_users_id").val();
            let selectedNewGroupUsersValArr = selectedNewGroupUsersVal.split(",");
            let removeVal = $(this).attr("data-user-id");
            let newselectedNewGroupUsersValArr = removeItemOnce(selectedNewGroupUsersValArr, removeVal);
            let newselectedNewGroupUsersVal = newselectedNewGroupUsersValArr.join(",");
            $(this).parents('.users-tag-container').remove();
            $("#new_group_users_id").val(newselectedNewGroupUsersVal);
            setNewGroupUsersDropDownSerachPosition();
            if ($(".edit_new_group_users_datalist_wrapper_new ul li").length > 0) {
                $(".edit_new_group_users_datalist_wrapper_new ul li").each(function() {
                    if ($(this).attr("data-index-id") !== undefined && $(this).attr("data-index-id") == removeVal) {
                        $(this).show();
                    }
                });
            }
        });

        $(document).on("focus","#new-user-order-approval-area",function(){
            setDropDownSerachPosition();
            if($(this).val().length < 3) {
                $("#edit_users_search_dropdown").html("");
            }
            if($(".edit_users-datalist-wrapper ul li").length > 0) {
                $(".edit_users-datalist-wrapper ul li").each(function(){
                    if($(this).attr("style") === undefined || $(this).attr("style") == "") {
                        $(".edit_users-datalist-wrapper-new").show();
                    }
                });
            } else {
                $(".edit_users-datalist-wrapper-new").hide();
            }
           
        })

        $(document).on("keyup","#new-user-order-approval-area",function(e){
            if (e.which <= 90 && e.which >= 48 || e.which >= 96 && e.which <= 105 && $(this).val().length >= 3) {
                let selectedUsersVal = $("#selected_order_approver").val();
                var postdata = {filter : $(this).val() , exclude_user : selectedUsersVal, includeCustomerAdmin: true};
                var findUserUrl = urlBuilder.build('selfreg/users/findUsers');
               
                $.ajax({
                    url: findUserUrl,
                    type: 'POST',
                    dataType: 'json',
                    showLoader: true,
                    data: postdata,
                    success: function(response) {
                        if(response !== undefined && response.status !== undefined && response.status == "success" && response.counter != "0") {
                            $("#edit_users_search_dropdown").html(response.html);
                            setDropDownSerachPosition();
                            $(".edit_users-datalist-wrapper-new").show();
                        } else {
                            $(".edit_users-datalist-wrapper-new").hide();
                        }
                    },
                    error: function(xhr, status, errorThrown) {
                        
                    }
                });
            }
        });

        // function for search result placement
        function setDropDownSerachPosition()
        {
            var rect = $('.edit_users_search').position();
            var scrollHeight = document.getElementById('selected-users-container').scrollHeight;
            var initialHeight = $('#selected-users-container').height();
        
            var parentLeft = $('#selected-users-container').offset().left;
            var parentTop = $('#selected-users-container').offset().top;
            var childLeft = $('.edit_users_search').offset().left;
            var childTop = $('.edit_users_search').offset().top;

            var searchInputTopFinal = childTop - parentTop,
            searchInputLeftFinal = childLeft - parentLeft;
            let marginLeft =  rect.left + 5;
            jQuery(".edit_users-datalist-wrapper-new").css("margin-left", marginLeft+"px");

                let topDistance = initialHeight - (searchInputTopFinal + 22);
                jQuery(".edit_users-datalist-wrapper-new").css("margin-top", "-"+topDistance+"px");
        }

         // function for applying tag
         $(document).on("click", ".edit_users-datalist-wrapper-new #edit_users_search_dropdown li", function() {
             let selectedListUser = $(this).attr("data-index-id");
             let selectedUsersVal = $("#selected_order_approver").val();
             if (selectedUsersVal != "") {
                 selectedUsersVal = selectedUsersVal + "," + selectedListUser;
             } else {
                 selectedUsersVal = selectedListUser;
             }
             $("#selected_order_approver").val(selectedUsersVal);
             let crossmarksvg = '<svg data-user-id="' + selectedListUser + '" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12ZM19 12C19 15.866 15.866 19 12 19C8.13401 19 5 15.866 5 12C5 8.13401 8.13401 5 12 5C15.866 5 19 8.13401 19 12ZM8.14645 8.85355C7.95118 8.65829 7.95118 8.34171 8.14645 8.14645C8.34171 7.95118 8.65829 7.95118 8.85355 8.14645L12 11.2929L15.1464 8.14645C15.3417 7.95118 15.6583 7.95118 15.8536 8.14645C16.0488 8.34171 16.0488 8.65829 15.8536 8.85355L12.7071 12L15.8536 15.1464C16.0488 15.3417 16.0488 15.6583 15.8536 15.8536C15.6583 16.0488 15.3417 16.0488 15.1464 15.8536L12 12.7071L8.85355 15.8536C8.65829 16.0488 8.34171 16.0488 8.14645 15.8536C7.95118 15.6583 7.95118 15.3417 8.14645 15.1464L11.2929 12L8.14645 8.85355Z" fill="#333333"/></svg></button></div></div>';
             if ($(".users-tag-container").length >= 4) {
                 $('<div class="users-tag-container show-more"><div class="users-tag-value">' + $(this).html() + '</div><div class="users-tag-cross"><button type="button" aria-label="close" data-test-id="E-404291-B-2010881-edit-users-single-user-close-' + selectedUsersVal + '">' + crossmarksvg).insertBefore(".edit_users_search");
             } else {
                 $('<div class="users-tag-container"><div class="users-tag-value">' + $(this).html() + '</div><div class="users-tag-cross"><button type="button" aria-label="close" data-test-id="E-404291-B-2010881-edit-users-single-user-close-' + selectedUsersVal + '">' + crossmarksvg).insertBefore(".edit_users_search");
             }
             $(this).hide();
             $("#new-user-order-approval-area").val('').focus();
             $(".edit_users-datalist-wrapper-new").hide();
             setDropDownSerachPosition();
         });

         // function to remove tags
         $(document).on("click", ".users-tag-container .manageOrder svg", function() {
             let selectedUsersVal = $("#selected_order_approver").val();
             let selectedUsersValArr = selectedUsersVal.split(",");
             let removeVal = $(this).attr("data-user-id");
             let newselectedUsersValArr = removeItemOnce(selectedUsersValArr, removeVal);
             let newselectedUsersVal = newselectedUsersValArr.join(",");
             $(this).parents('.users-tag-container').remove();
             $("#selected_order_approver").val(newselectedUsersVal);
             setDropDownSerachPosition();
             if ($(".edit_users-datalist-wrapper ul li").length > 0) {
                 $(".edit_users-datalist-wrapper ul li").each(function() {
                     if ($(this).attr("data-index-id") !== undefined && $(this).attr("data-index-id") == removeVal) {
                         $(this).show();
                     }
                 });
             }
         });

        function removeItemOnce(arr, value) {
            var index = arr.indexOf(value);
            if (index > -1) {
                arr.splice(index, 1);
            }
            return arr;
        }

        // Save user group details
        $(document).on("click","#selected-users-container",function(ele){
            $("#new-user-order-approval-area").focus();
        });

        $(document).on("click","#selected_new_group_users_container",function(ele){
            $("#new_group_user_area").focus();
        });

        $(document).on('click', '.new-user-group-save-button', function() {
            if (validateRequiredFlds()) {
                confirmDuplicateUserNSave().then(duplicateDetected => {
                    if (!duplicateDetected) {
                        saveUserGroupRecord(false);
                    }
                });
            }
        });

        // Close modal
        $(document).on('click', '.new-user-group-cancel-button', function() {
            $('.custom-modal-class .action-close').click();
        });

        // Reset new user group form error
        function resetFormError() {
            // Remove error-text class from all inputs
            $('.new-user-group-name').removeClass('error-text');
            $('.new-user-order-approval').removeClass('error-text');
            $('.group-name-error').hide();
            $('.group-name-error').val('');

            // Remove the error message elements
            $('#new_user_group_modal_container').find('.error').hide();
        }

        // Close validation modal
        $(document).on('click', '.validate-user-group-cancel-button', function() {
            resetFormError();
            $('.validate-modal .action-close').click();
        });

        // Process Save from validation modal
        $(document).on('click', '.validate-user-group-save-button', function() {
            resetFormError();
            saveUserGroupRecord(true);
        });

        $(document).on("keyup", ".new-user-group-name", function(e){
            if ($('.group-name-error').is(':visible') || $('.group-name-error').val() !== '') {
                $('.group-name-error').hide();
                $('.group-name-error').val('');
                $('.new-user-group-name').removeClass('error-text');
            }
        });

        // Reset form on initial page load
        $(function() {
            resetFormError();

            // Reset form on modal close
            $(document).on('click', 'button.action-close[data-role="closeBtn"]', function() {
                resetFormError();
            });
        });

        function validateRequiredFlds() {
            let isValid = true;

            let groupName = $("#new-user-group-name").val();
            let groupType = $('input[name="group_type_radio"]:checked').val();
            let orderApprovers = $("#selected_order_approver").val();

            if (groupName) {
                $("#new-user-group-name").removeClass('error-text');
                $('.group-name-error').hide();
                $('.group-name-error').val('');
            } else {
                $("#new-user-group-name").addClass('error-text');
                $('.group-name-error').html("User group name is required.").show();
                isValid = false; 
            }

            if (groupType && groupType == 'order_approval') {
                if (orderApprovers) {
                    $('.new-user-order-approval').removeClass('error-text');
                    $('.selected-order-approver-error').hide();
                } else {
                    $('.new-user-order-approval').addClass('error-text');
                    $('.selected-order-approver-error').show();
                    isValid = false;
                }
            }

            return isValid;
        }

        function confirmDuplicateUserNSave() {
            return new Promise((resolve, reject) => {
                var validateDupeUrl = urlBuilder.build('selfreg/users/checkDupes');
                var userIds = $("#new_group_users_id").val();
                var groupId = $("#groupId").val();
                var groupType = $('input[name="group_type_radio"]:checked').val();
                var duplicateUserDetected = false;
                let isFolderPermissionGroup = $('#group_type_folder_permissions').prop('checked');

                if (!groupType) {
                    groupType = 'folder_permissions';
                    isFolderPermissionGroup = true;
                }

                if (userIds) {
                    $.ajax({
                        url: validateDupeUrl,
                        type: 'POST',
                        dataType: 'json',
                        showLoader: true,
                        data: {
                            groupId: groupId,
                            groupType: groupType,
                            userIds: userIds,
                            isFolderPermissionGroup: isFolderPermissionGroup
                        },
                        success: function(response) {
                            if (response.success && response.duplicate) {
                                // Trigger validation modal
                                var options = {
                                    type: 'popup',
                                    responsive: true,
                                    innerScroll: true,
                                    modalClass: 'validate-modal',
                                    buttons: []
                                };

                                var validationPopup = $('#validateUserGroupContainer');
                                if (validationPopup.length) {
                                    validationPopup.modal(options).modal('openModal');
                                    if(response.duplicate_count == 1 && response.folder_permission_group == false) {
                                        validationPopup.find('#validateUserGroupTitle').html("<span style='color: grey;'>This user is already in the user group,</span><b><span id='safeGroupName'></span></b>");
                                        validationPopup.find('#safeGroupName').text(response.group_name);
                                    } else if(response.duplicate_count > 1 || response.folder_permission_group == true) {
                                        validationPopup.find('#validateUserGroupTitle').html("<span style='color: grey;'>One or more of these users is currently assigned to a user group</span>");
                                    }
                                }

                                duplicateUserDetected = true;
                            }

                            resolve(duplicateUserDetected);
                        },
                        error: function(xhr, status, error) {
                            reject(error);
                        }
                    });
                } else {
                    resolve(duplicateUserDetected);
                }
            });
        }

        function saveUserGroupRecord(isDuplicateModalOpen) {
            var saveUrl = urlBuilder.build('company/user/save');
            var groupName = $("#new-user-group-name").val();
            var groupType = $('input[name="group_type_radio"]:checked').val();
            var orderApprovers = $("#selected_order_approver").val();
            var userIds = $("#new_group_users_id").val();
            var groupId = $("#groupId").val();
            let siteUrl = $("#siteUrl").val();

            if (!groupType) {
                groupType = 'folder_permissions';
            }

            $.ajax({
                url: saveUrl,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: { 
                    groupId: groupId,
                    groupName: groupName,
                    groupType: groupType,
                    userIds: userIds,
                    orderApprovers: orderApprovers,
                    siteUrl: siteUrl
                }, 
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        if (response.existingErrorMessage) {
                            $("#new-user-group-name").addClass('error-text');
                            $('.group-name-error').html(response.existingErrorMessage).show();

                            if (isDuplicateModalOpen) {
                                let validationPopup = $('#validateUserGroupContainer');
                                validationPopup.modal(options).modal('closeModal');
                            }
                        }
                    }
                }
            });
        }
    };
});