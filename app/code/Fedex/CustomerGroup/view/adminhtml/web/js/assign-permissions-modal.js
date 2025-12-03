require([
    "jquery",
    "Magento_Ui/js/modal/modal",
    "Magento_Ui/js/modal/alert",
    'fedex/storage'
], function($, modal, alert, fxoStorage) {
    let selectedIds;
    let selectedPermissionCounter = 0;
    let selectedUsersCounter = 0;
    let isSiteAccessApprovalEmailSelected = false;

    $(document).on('click', "#assign-permission-button", function() {
        let localSelectedIds;

        if (window.e383157Toggle) {
            selectedIds = fxoStorage.get("selectedCustomerIds");
        }else{
            localSelectedIds = localStorage.getItem("selectedCustomerIds");
            selectedIds = JSON.parse(localSelectedIds);
        }

        if (selectedIds != null && selectedIds.length > 0) {
            selectedUsersCounter = selectedIds.length;
            populateSelectedUserIds(selectedIds);
            let options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: 'Assign Permissions',
                modalClass: 'assign-permissions-modal-container',
                buttons: [{
                    text: 'Cancel',
                    class: 'assign-permissions-modal-cancel',
                    click: function () {
                        this.closeModal();
                    }
                },{
                    text: 'Save',
                    class: 'assign-permissions-save-disabled assign-permissions-save',
                    click: function () {
                        savePermissions(selectedIds);
                        this.closeModal();
                    }
                }]
            };
            let assignPermissionsModal = modal(options, $('#assign-permissions-modal'));
            assignPermissionsModal.openModal();
            $('#assign-permission-button').blur();
            $('#assign-permissions-modal').css('display', 'block');
        } else {
            let errorMessage = "You haven't selected any items!";
            alert({ content: errorMessage });
        }
    });

    $('#assign-permissions-modal').on('modalclosed', function () {
        resetModal();
        if ($('#assign-permission-button').is(':focus')) {
            $('#assign-permission-button').blur();
        }
    });

    $(document).on("keyup","#ap_search_area", function(e) {
        if (e.which <= 90 && e.which >= 48 || e.which >= 96 && e.which <= 105 && $(this).val().length >= 3) {
            if ($('.customer-ids-multiselect').hasClass('dropdown-inactive') && !isOnlyFirstRow()) {
                setApActiveDropdown();
            }
            let postdata = { filterValue : $(this).val(), excludedUserIds: selectedIds };
            let findUsersUrl = $('#find-users-url').val();

            $.ajax({
                url: findUsersUrl,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: postdata,
                success: function(response) {
                    if (response && response?.status === "success" && response?.html) {
                        $(".ap-search-dropdown").html(response.html);
                        setNewGroupUsersDropDownSerachPosition();
                        $(".ap-search-dropdown-wrapper").show();
                    } else {
                        $(".ap-search-dropdown-wrapper").hide();
                         if (response?.status === 'error'){
                            console.log(response?.message);
                        }
                    }
                },
                error: function(response) {
                    console.log(response?.message);
                }
            });
        }
    });

    $(document).on("click", ".ap-search-dropdown li", function() {
        let newSelectedUserId = $(this).attr("data-index-id");
        let newSelectedUserName = $(this).text();
        const isOnlyFirstRowOld = isOnlyFirstRow();
        selectedIds.push(newSelectedUserId);
        if (selectedUsersCounter === 0 && selectedPermissionCounter !== 0) {
            let isManageUsersChecked = $('#admin-permissions-option-manage_users').is(':checked');
            if (!isManageUsersChecked) {
                $('.assign-permissions-save').removeClass('assign-permissions-save-disabled').addClass('primary');
            } else if (isSiteAccessApprovalEmailSelected) {
                $('.assign-permissions-save').removeClass('assign-permissions-save-disabled').addClass('primary');
            }
        }
        selectedUsersCounter++;

        let newSelectedUserHtml = '<span class="admin__action-multiselect-crumb selected-user-assign-permissions"><span>' +
            newSelectedUserName + '</span><button class="action-close remove-selected-user" type="button" data-index-id="' +
            newSelectedUserId + '"><span class="action-close-text">Close</span></button></span>';

        const lastSpan = $(".selected-users").find("span.selected-user-assign-permissions").last();
        if (lastSpan.length) {
            lastSpan.after(newSelectedUserHtml);
        } else {
            $(".selected-users").append(newSelectedUserHtml);
        }
        $("#ap_search_area").val('').focus();
        $(".ap-search-dropdown-wrapper").hide();
        const isOnlyFirstRowNew = isOnlyFirstRow();
        if (isOnlyFirstRowOld !== isOnlyFirstRowNew) {
            setSelectedUsersSection();
        }
    });

    $(document).on("click", ".remove-selected-user", function() {
        let removeSelectedUserId = $(this).attr("data-index-id");
        selectedIds = selectedIds.filter(function(id) {
            return id !== removeSelectedUserId;
        });
        selectedUsersCounter--;
        if (selectedUsersCounter === 0 && selectedPermissionCounter !== 0) {
            $('.assign-permissions-save').removeClass('primary').addClass('assign-permissions-save-disabled');
        }

        $(this).parent().remove();
        setSelectedUsersSection('remove_user');
        setNewGroupUsersDropDownSerachPosition();
    });

    $(document).on("click", ".customer-ids-multiselect", function (event) {
        if ($(event.target).is('.customer-ids-multiselect.dropdown-inactive') && !isOnlyFirstRow()) {
            setApActiveDropdown();
        } else if ($(event.target).is('.customer-ids-multiselect.dropdown-active')) {
            if ($('.ap-search-dropdown-wrapper').css('display') !== 'none') {
                $('.ap-search-dropdown-wrapper').hide();
                $(".ap-search-area").val('');
            }
            $('.customer-ids-multiselect').removeClass('dropdown-active').addClass('dropdown-inactive');
            $('.add-users-section').appendTo('.customer-ids-multiselect');
            $('.selected-users').removeClass('selected-users-section-dropdown').addClass('selected-users-section');
            setSelectedUsersSection();
        }
    });

    $(document).on("click", '.assign-permissions-modal-container', function(event) {
        if (!$(event.target).closest('.ap-search-dropdown-wrapper').length && 
            !$(event.target).closest('#ap_search_area').length) {
            if ($('.ap-search-dropdown-wrapper').css('display') !== 'none') {
                $('.customer-ids-multiselect').removeClass('dropdown-active').addClass('dropdown-inactive');
                $('.ap-search-dropdown-wrapper').hide();
                $(".ap-search-area").val('');
                $('.add-users-section').appendTo('.customer-ids-multiselect');
                $('.selected-users').removeClass('selected-users-section-dropdown').addClass('selected-users-section');
                setSelectedUsersSection();
            }
        }
    });

    $(document).on("change", ".admin-permissions-option .admin__field input[type='checkbox']", function() {
        let isManageUsersPermission = $(this).attr("id") === "admin-permissions-option-manage_users";

        if ($(this).is(':checked')) {
            if (isManageUsersPermission) {
                $(".site-access-approval-email-section").show();
            }
            if (selectedPermissionCounter === 0 && selectedUsersCounter !== 0 && !isManageUsersPermission) {
                $('.assign-permissions-save').removeClass('assign-permissions-save-disabled').addClass('primary');
            } else if (selectedPermissionCounter > 0 && selectedUsersCounter !== 0 && isManageUsersPermission) {
                $('.assign-permissions-save').removeClass('primary').addClass('assign-permissions-save-disabled');
            }
            selectedPermissionCounter++;
        } else {
            if (isManageUsersPermission) {
                $(".site-access-approval-email-section").hide();
                $('input[name="email_permission"]').prop('checked', false);
                isSiteAccessApprovalEmailSelected = false;
            }
            selectedPermissionCounter--;
            if (selectedPermissionCounter === 0 && selectedUsersCounter !== 0) {
                $('.assign-permissions-save').removeClass('primary').addClass('assign-permissions-save-disabled');
            } else if (
                $('.assign-permissions-save').hasClass('assign-permissions-save-disabled') &&
                selectedPermissionCounter > 0 && selectedUsersCounter > 0 && isManageUsersPermission
            ) {
                $('.assign-permissions-save').removeClass('assign-permissions-save-disabled').addClass('primary');
            }
        }
    });

    $(document).on("change", 'input[name="email_permission"]', function() {
        if (!isSiteAccessApprovalEmailSelected && selectedUsersCounter !== 0) {
            $('.assign-permissions-save').removeClass('assign-permissions-save-disabled').addClass('primary');
        }
        isSiteAccessApprovalEmailSelected = true;
    });

    function setApActiveDropdown() {
        $('.customer-ids-multiselect').removeClass('dropdown-inactive').addClass('dropdown-active');
        $(".ap-search-area").attr('placeholder', 'Add User');
        $('.selected-users').css('flex', '0 0 content');
        $('.add-users-section').appendTo('.selected-users');
        $('.selected-users').removeClass('selected-users-section').addClass('selected-users-section-dropdown');
    }

    function populateSelectedUserIds(selectedIds) {
        let getUsersUrl = $('#get-users-url').val();

        $.ajax({
            type: "POST",
            url: getUsersUrl,
            data: {
                selectedIds: selectedIds
            },
            dataType: "json",
            showLoader: true,
            success: function(response) {
                if (response && response?.status === "success" && response?.selectedUsersHtml && response?.addUsersHtml) {
                    $(".selected-users").html(response?.selectedUsersHtml);
                    $(".add-users-section").html(response?.addUsersHtml);
                    setSelectedUsersSection();
                } else {
                    console.log(response?.message);
                }
            },
            error: function (response) {
                console.log(response?.message);
            },
        });
    }

    function isOnlyFirstRow() {
        let selectedUsersSectionHeight = $(".selected-users").height();
        let usersFieldFirstRowHeight = 33.3;
        return selectedUsersSectionHeight <= usersFieldFirstRowHeight;
    }

    function setSelectedUsersSection(userAction = null) {
        let isDropDownInactive = $('.customer-ids-multiselect').hasClass('dropdown-inactive');
        if (userAction === 'remove_user' && isDropDownInactive) {
            $(".selected-users").css('flex', '0 0 content');
            $(".ap-search-area").attr('placeholder', 'Add User');
        }

        if (!isOnlyFirstRow() && isDropDownInactive) {
            let inputFieldTopOffset = 3;
            let userFieldWidthOffset = 8;
            let maxSelectedUsersSectionWidth = 350;
            let firstRowOffset = $('.ap-search-area').offset().top + inputFieldTopOffset;
            let firstRowWidth = 0;
            let hiddenUserCount = 0;
            $('.selected-users').children().each(function () {
                if ($(this).offset().top === firstRowOffset) {
                    firstRowWidth += this.scrollWidth + userFieldWidthOffset;
                } else {
                    hiddenUserCount++;
                }
            });
            if (firstRowWidth && firstRowWidth < maxSelectedUsersSectionWidth) {
                $(".selected-users").css('flex', '0 0 ' + firstRowWidth + 'px');
            } else {
                $(".selected-users").css('flex', '0 0 content');
            }
            if (hiddenUserCount) {
                $(".ap-search-area").attr('placeholder', '+ ' + hiddenUserCount);
            } else {
                $(".ap-search-area").attr('placeholder', 'Add User');
            }
        } else if (userAction === 'remove_user' && isOnlyFirstRow() && !isDropDownInactive) {
            $('.customer-ids-multiselect').removeClass('dropdown-active').addClass('dropdown-inactive');
            $('.add-users-section').appendTo('.customer-ids-multiselect');
            $('.selected-users').removeClass('selected-users-section-dropdown').addClass('selected-users-section');
        }
    }

    function setNewGroupUsersDropDownSerachPosition() {
        let userContainerHeight = $('.add-users-section').height();
        let searchAreaUserContainerSpacing = 4;
        let marginTop =  userContainerHeight + searchAreaUserContainerSpacing;

        $('.ap-search-dropdown-wrapper').css('margin-top', marginTop + 'px');
    }

    function savePermissions() {
        let selectedPermissions = {};
        let bulkSavePermissionsUrl = $('#bulk-save-permissions').val();
        $('.admin-permissions-option input[type="checkbox"]').each(function() {
            if ($(this).is(':checked')) {
                selectedPermissions[$(this).attr('data-index-permission-code')] = $(this).attr('data-index-permission-id');
            }
        });

        $('input[name="email_permission"]').each(function() {
            if ($(this).is(':checked')) {
                let emailPermissionId = $(this).attr('data-index-permission-id');
                if (emailPermissionId >= 0) {
                    selectedPermissions[$(this).attr('data-index-permission-code')] = emailPermissionId;
                } else {
                    console.log('Email permission id is not valid for bulk user save.');
                }
            }
        });

        $.ajax({
            type: "POST",
            url: bulkSavePermissionsUrl,
            data: {
                selectedIds: selectedIds,
                selectedPermissions: selectedPermissions
            },
            dataType: "json",
            showLoader: true,
            success: function(response) {
                if (response && response?.status === "success" && response?.redirect) {
                    if (window.e383157Toggle) {
                        fxoStorage.delete("selectedCustomerIds");
                    }else{
                        localStorage.removeItem("selectedCustomerIds");
                    }
                    window.location.href = response.redirect;
                } else {
                    console.log(response?.message);
                }
            },
            error: function (response) {
                console.log(response?.message);
            },
        });
    }

    function resetModal() {
        selectedIds = [];
        selectedPermissionCounter = 0;
        selectedUsersCounter = 0;
        isSiteAccessApprovalEmailSelected = false;

        $('.assign-permissions-save').removeClass('primary').addClass('assign-permissions-save-disabled');
        $('.admin-permissions-option input[type="checkbox"]').prop('checked', false);
        $('input[name="email_permission"]').prop('checked', false);
        $(".site-access-approval-email-section").hide();
        $("#ap_search_area").val('');
        $(".ap-search-dropdown-wrapper").hide();
        $(".selected-users").html('');
        $(".selected-users").css('flex', '0 0 content');
        $(".add-users-section").html('');
        if ($('.customer-ids-multiselect').hasClass('dropdown-active')) {
            $('.customer-ids-multiselect').removeClass('dropdown-active').addClass('dropdown-inactive');
            $('.customer-ids-multiselect').append('<div class="add-users-section"></div>');
            $('.selected-users').removeClass('selected-users-section-dropdown').addClass('selected-users-section');
        }
    }
});