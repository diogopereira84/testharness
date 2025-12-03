define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Fedex_SelfReg/js/user-edit',
], function($, modal, userEdit) {
    'use strict';

    return function(config, element) {
        const userGroupModal = $(element);
        const bulkEditWarningModal = $('#bulk_edit_warning_modal_container');
        const currentUserGroup = $('input[id="usergroup"]');
        const bulkUserGroup = $('input[id="bulk_edit_usergroup"]');
        let savedUserGroup = '';
        let isBulkEdit = false;
        let isBulkEditWarningModalUpdated = false;
        let currentUserGroupValue = '';


        let userGroupModalOptions = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            modalClass: 'custom-modal-class',
            currentUserGroup: '',
            buttons: []
        };

        let bulkEditWarningModalOptions = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            modalClass: 'bluk-edit-warning-modal-class',
            buttons: []
        };

        userGroupModal.modal(userGroupModalOptions);
        bulkEditWarningModal.modal(bulkEditWarningModalOptions);
 
        $(document).on('click', '#change-user-group-modal-link', function() {
            const isUserEdited = $('#editedUserGroupId').val() ? true : false;
            if (!isUserEdited) {
                savedUserGroup = currentUserGroup.val();
            }

            if (isBulkEdit) {
                isBulkEdit = false;
            }

            currentUserGroupValue = currentUserGroup.val();
            userGroupModal.modal('openModal');
        });

        $(document).on('click', '#user_group_update_button', function() {
            if (savedUserGroup !== 'Default' && isBulkEdit) {
                bulkEditWarningModal.modal('openModal');
            } else {
                saveSelectedUserGroup();
                userGroupModal.modal('closeModal');
            }
        });

        $(document).on('click', '#user_group_cancel_button', function() {
            userGroupModal.modal('closeModal');
        });

        $(document).on('click', '#bulk_edit_usergroup_change-user-group-modal-link', function() {
            const isUserEdited = $('#editedBulkGroupId').val() ? true : false;
            if (!isUserEdited) {
                savedUserGroup = bulkUserGroup.val();
            }

            if (!isBulkEdit) {
                isBulkEdit = true;
            }

            currentUserGroupValue = bulkUserGroup.val();
            userGroupModal.modal('openModal');
        });

        $(document).on('click', ' #bulk_edit_warning_modal_update_button ', function() {
            isBulkEditWarningModalUpdated = true;

            saveSelectedUserGroup();

            bulkEditWarningModal.modal('closeModal');
        });

        $(document).on('click', '#user-group-search-button', function() {
            var container = document.getElementById('user_groups_list_container');
            container.innerHTML = '';

            var searchResultGroups = [];
            var userGroups = config.groups;
            var searchValue = document.getElementById('user-group-search').value;
            searchResultGroups = Object.fromEntries(Object.entries(userGroups).filter(([key, value]) => value.toLowerCase().includes(searchValue.toLowerCase())));              

            var ul = document.createElement('ul');
            $(ul).addClass('user_groups_list');
            ul.innerHTML = '';

            Object.entries(searchResultGroups).forEach(function([customerGroupId, group]) {
                var li = document.createElement('li');
                var radio = document.createElement('input');
                var label = document.createElement('label');
                var img = document.createElement('img');

                radio.type = 'radio';
                radio.name = 'user_group_name';
                radio.id = 'user_group_name_' + customerGroupId;

                radio.classList.add('user-group-name-button');
                label.htmlFor = radio.id;
                label.classList.add('user-group-name-button-label');

                img.src = config.imageURL;
                img.alt = "User Groups Icon";
                label.textContent = group.replace(/<.*?>\s*/, '').trim();;

                li.appendChild(radio);
                li.appendChild(img);
                li.appendChild(label);
                ul.appendChild(li);
            });

            container.appendChild(ul);
        });

        $('#user-group-search').keypress(function(e){
            if(e.which == 13){//Enter key pressed
                $('#user-group-search-button').click();//Trigger search button click event
            }
        });

        userGroupModal.on('modalopened', function () {
            if (currentUserGroupValue) {
                let userGroupsList = $('.user_groups_list li');
                if (currentUserGroupValue !== 'Multiple Groups') {
                    const selectedUserGroupElement = userGroupsList.filter(function() {
                        return $(this).find('label.user-group-name-button-label').text().trim() === currentUserGroupValue;
                    }).first();
                    const selectedUserGroupInput = selectedUserGroupElement.find('input.user-group-name-button');
                    selectedUserGroupInput.prop('checked', true);
                } else if (currentUserGroupValue === 'Multiple Groups') {
                    const oldCheckedUserGroup = userGroupsList.filter(function() {
                        return $(this).find('input.user-group-name-button').prop('checked');
                    });
                    if (oldCheckedUserGroup.length > 0) {
                        oldCheckedUserGroup.find('input.user-group-name-button').prop('checked', false);
                    }
                }
            }
        });

        bulkEditWarningModal.on('modalclosed', function() {
            if (isBulkEditWarningModalUpdated) {
                userGroupModal.modal('closeModal');
                isBulkEditWarningModalUpdated = false;
            }
        });

        function saveSelectedUserGroup() {
            const selectedCustomerGroup = getSelectedCustomerGroupData();
            if (isBulkEdit) {
                $('#editedBulkGroupId').val(selectedCustomerGroup.customerGroupId);
                bulkUserGroup.val(selectedCustomerGroup.labelText);
            } else {
                const fieldName = 'group_id';

                userEdit()._setPopupFields(fieldName, selectedCustomerGroup.labelText, selectedCustomerGroup.customerGroupId, true);
            }
        };

        function getSelectedCustomerGroupData() {
            const selectedRadio = document.querySelector('input[name="user_group_name"]:checked');
            
            if (selectedRadio) {
                const groupId = selectedRadio.id.split('_').pop();
                const label = document.querySelector(`label[for="${selectedRadio.id}"]`);
                return {
                    customerGroupId: groupId ? groupId.trim() : null, 
                    labelText: label ? label.textContent.trim() : null
                };
            }
            return null;
        };
    };
});