define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/modal'
], function($, urlBuilder,modal) {
    'use strict';

    return function(config, element) {
        var options = {
        'type': 'popup',
        'modalClass': 'folder-permission-edit-folder-access-modal',
        'responsive': true,
        'innerScroll': true,
        buttons: []
    };

    $(document).on('click', '.edit-folder-access-action', function() {
        let categoryId = $(this).data('folder-id');

        var loadModalUrl = urlBuilder.build('selfreg/ajax/loadUserGroupsModal');
        $.ajax({
            url: loadModalUrl,
            data: {
                categoryId: categoryId
            },
            type: 'POST',
            showLoader: true,
            success: function (response) {
                $('#folder-permission-edit-folder-access-modal .folder-permission-edit-folder-access-modal-body').html(response);
                $('#folder-permission-edit-folder-access-modal').modal(options).modal('openModal');
                initializeToggleState();
            },
            error: function () {
                console.error('Failed to load the modal content.');
            }
        });
    });

    function initializeToggleState() {
        const userGroupCheckboxes = document.querySelectorAll('.user-group-name-checkbox');
        const selectAllCheckbox = document.getElementById('select_all');
        const toggleCheckbox = document.querySelector('.folder-access-toggle-checkbox');
        
        // Check if all checkboxes are selected
        const allSelected = Array.from(userGroupCheckboxes).every(checkbox => checkbox.checked);
        const anyUnselected = Array.from(userGroupCheckboxes).some(checkbox => !checkbox.checked);
    
        if (allSelected && toggleCheckbox.checked) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.disabled = false;
        } else if (allSelected) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.disabled = true;
        } else if (anyUnselected) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.disabled = false;
        }

        toggleFolderAccessStatus(toggleCheckbox);
    }

    $(document).on('click', '.folder-permission-edit-folder-access-cancel-button', function() {
        $('#folder-permission-edit-folder-access-modal').modal('closeModal');
    });

     $(document).on('click', '.folder-permission-edit-folder-access-save-button', function() {
        const categoryId = $(this).attr('data-folder-id');
        let selectedGroupIds = {};
        const checkBoxes = document.querySelectorAll('.user-group-name-checkbox');
        if (checkBoxes.length > 0) {
            for (let i = 0; i < checkBoxes.length; i++) {
                if (checkBoxes[i].checked) {
                    selectedGroupIds[checkBoxes[i].value] = -1;
                } else {
                    selectedGroupIds[checkBoxes[i].value] = -2;
                }
            }
        }

        var saveRequestUrl =  urlBuilder.build('selfreg/ajax/save/');
        let isFolderRestricted = $('.folder-access-toggle-checkbox').prop('checked') ? 1 : 0;
        $.ajax(saveRequestUrl, {
            type: 'POST',  // http method
            showLoader: true,
            data: {
                groupIds:selectedGroupIds,
                categoryId:categoryId,
                isFolderRestricted: isFolderRestricted
            },  // data to submit
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    location.reload();
                } else {
                    console.error(response.message || 'Failed to save permissions.');
                }
            },
            error: function (error) {
                console.error('Error in AJAX request:', error);
            },
        });

        $('#folder-permission-edit-folder-access-modal').modal('closeModal');
    });

    $(document).on('change', '.folder-access-toggle-checkbox', function() {
        toggleFolderAccessStatus(this);
    });

    function toggleFolderAccessStatus(checkbox) {
        const offStatus = document.querySelector('.folder-permission-edit-folder-access-access-status-off');
        const onStatus = document.querySelector('.folder-permission-edit-folder-access-access-status-on');
        const label = checkbox.nextElementSibling.querySelector('.labels');
        const userGroupCheckboxes = document.querySelectorAll('.user-group-name-checkbox');
        const selectAllCheckbox = document.getElementById('select_all');

        if (checkbox.checked) {
            offStatus.style.display = 'none';
            onStatus.style.display = 'inline';
            label.setAttribute('data-on', 'ON');
            label.setAttribute('data-off', '');
            enableUserGroupCheckboxes(userGroupCheckboxes, true);
            selectAllCheckbox.disabled = false; // Enable Select All
        } else {
            offStatus.style.display = 'inline';
            onStatus.style.display = 'none';
            label.setAttribute('data-on', '');
            label.setAttribute('data-off', 'OFF');
            enableUserGroupCheckboxes(userGroupCheckboxes, false);
            selectAllCheckbox.checked = true; // Ensure Select All is checked
            selectAllCheckbox.disabled = true; // Disable Select All
            selectAllCheckboxes(userGroupCheckboxes, true);
        }
    }

    function enableUserGroupCheckboxes(checkboxes, enable) {
        checkboxes.forEach(function(checkbox) {
            checkbox.disabled = !enable;
        });
    }    

     // Select or deselect all checkboxes
    function selectAllCheckboxes(checkboxes, selectAll) {
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = selectAll;
        });
    }

    // "Select All" checkbox logic
    $(document).on('change', '#select_all', function() {
        if ($('.folder-access-toggle-checkbox').prop('checked')) {
            var isChecked = $(this).prop('checked');
            $('.user-group-name-checkbox').prop('checked', isChecked);
        }
    });

    // Uncheck "Select All" if any individual group checkbox is unchecked
    $(document).on('change', '.user-group-name-checkbox:not(#select_all)', function() {
        if ($('.folder-access-toggle-checkbox').prop('checked')) {
            var allChecked = $('.user-group-name-checkbox:not(#select_all)').length === $('.user-group-name-checkbox:not(#select_all):checked').length;
        $('#select_all').prop('checked', allChecked);
        }
    });
  
    // Search Functionality
        $(document).on('click', '#folder-permission-edit-folder-access-user-group-search-button', function () {
            var searchValue = $('#folder-permission-edit-folder-access-user-group-search').val().toLowerCase();
            $('.folder-permission-user-groups-list li').each(function () {
                var groupName = $(this).find('label').text().toLowerCase();
                $(this).toggle(groupName.includes(searchValue));
            });
        });

    // Show all groups when clearing search box
    $('#folder-permission-edit-folder-access-user-group-search').on('input', function () {
        if (!$(this).val().trim()) {
            $('.folder-permission-user-groups-list li').show();
        }
    });

    $('#folder-permission-edit-folder-access-user-group-search').on('keydown', function(e) {
        if(e.which == 13){ //Enter key pressed
            $('#folder-permission-edit-folder-access-user-group-search-button').click();
        }
    });
};
});
