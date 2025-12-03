define([
    'jquery',
    'Magento_Ui/js/modal/modal',
], function($, modal) {
    'use strict';
 
    return function(config, element) {
        const bulkEditWarningModal = $(element);
 
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            modalClass: 'bluk-edit-warning-modal-class',
            buttons: []
        };
 
        bulkEditWarningModal.modal(options);

        $(document).on('click', ' #bulk_edit_warning_modal_cancel_button ', function() {
            bulkEditWarningModal.modal('closeModal');
        });
    };
});