require(['jquery'], function($) {
    'use strict';
    $(document).ready(function() {
        $(document).on("click", "#admin-permissions-option-manage_users", function() {
            if ($(this).is(":checked")) {
                $(".site-access-approval-email-section").show();
                $('#save[title="Save Customer"]').prop('disabled', true);
                $('#save_and_continue[title="Save and Continue Edit"]').prop('disabled', true);
            } else {
                $(".site-access-approval-email-section").hide();
                $('input[name="email_permission"]').prop('checked', false);
                $('#save[title="Save Customer"]').prop('disabled', false);
                $('#save_and_continue[title="Save and Continue Edit"]').prop('disabled', false);
            }
        });

        $(document).on("click", 'input[name="email_permission"]', function() {
            if ($('#save[title="Save Customer"]').prop('disabled')) {
                $('#save[title="Save Customer"]').prop('disabled', false);
                $('#save_and_continue[title="Save and Continue Edit"]').prop('disabled', false);
            }
        });
    });
});