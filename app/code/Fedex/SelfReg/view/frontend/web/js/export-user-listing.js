/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/
define([
    'jquery',
    'uiComponent',
    'mage/url'
], function ($, Component, urlBuilder) {
    'use strict';

    return Component.extend({

        /** @inheritdoc */
        initialize: function () {
            this._super();
            let crossIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"> <path fill-rule="evenodd" clip-rule="evenodd" d="M4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12ZM19 12C19 15.866 15.866 19 12 19C8.13401 19 5 15.866 5 12C5 8.13401 8.13401 5 12 5C15.866 5 19 8.13401 19 12ZM8.14645 8.85355C7.95118 8.65829 7.95118 8.34171 8.14645 8.14645C8.34171 7.95118 8.65829 7.95118 8.85355 8.14645L12 11.2929L15.1464 8.14645C15.3417 7.95118 15.6583 7.95118 15.8536 8.14645C16.0488 8.34171 16.0488 8.65829 15.8536 8.85355L12.7071 12L15.8536 15.1464C16.0488 15.3417 16.0488 15.6583 15.8536 15.8536C15.6583 16.0488 15.3417 16.0488 15.1464 15.8536L12 12.7071L8.85355 15.8536C8.65829 16.0488 8.34171 16.0488 8.14645 15.8536C7.95118 15.6583 7.95118 15.3417 8.14645 15.1464L11.2929 12L8.14645 8.85355Z" fill="#333333"/> </svg>';
            let maxEmailAddressAllowLimit = 0;
            maxEmailAddressAllowLimit = this.userEmailAddressLimit;

            $('.cancel-export').on('click', function() {
                $('.email-ids').remove();
                $('#recipient_email_address').val('');
                $('.action-close').trigger('click');
                $('#recipient_email_address').attr("placeholder", 'Enter recipient email');
                $('.email-input-text').css('border', '');
                $('.email-input-text').css('box-shadow', '');
            });

            $('.additional-recipients-email-address').on('click', function() {
                $(this).find('input').focus();
            });

            $('#recipient_email_address').on('keypress blur', function(e) {
                if (e.keyCode === 13 || e.handleObj.type == "blur") {
                    let finalEmailsInputValue = '';
                    let emailAddressFieldValue = $(this).val();
                    let validateEmailAddress = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
                    let emailTabsLimit = 0;
                    let emailTabs = $('.all-mail .email-ids');

                    if (emailAddressFieldValue) {
                        emailAddressFieldValue = emailAddressFieldValue.replace(/,\s*$/, "");
                        let countEmailSeparateByComma = (emailAddressFieldValue.match(/,/g) || []).length;
                        emailTabsLimit = emailTabs.length;
                        if (countEmailSeparateByComma > 0) {
                            let emailAddressFieldArrays = emailAddressFieldValue.split(",");
                            $.each(emailAddressFieldArrays, function(i, emailAddress) {
                                if (emailTabsLimit && emailTabsLimit < maxEmailAddressAllowLimit && validateEmailAddress.test(emailAddress)) {
                                    finalEmailsInputValue+= '<span class="email-ids">'+emailAddress.trim()+'<span class="cancel-email" tabindex="0">' + crossIcon + '</span></span>';
                                    emailTabsLimit++;
                                } else if (!emailTabsLimit && i < maxEmailAddressAllowLimit && validateEmailAddress.test(emailAddress)) {
                                    finalEmailsInputValue+= '<span class="email-ids">'+emailAddress.trim()+'<span class="cancel-email" tabindex="0">' + crossIcon + '</span></span>';
                                }
                            });
                            $('.all-mail').append(finalEmailsInputValue);
                            $('.email-input-text').css('border', '0px');
                            $('.email-input-text').css('box-shadow', 'none');
                        } else {
                            if (emailTabsLimit < maxEmailAddressAllowLimit && validateEmailAddress.test(emailAddressFieldValue)) {
                                $('.all-mail').append('<span class="email-ids">'+emailAddressFieldValue+'<span class="cancel-email" tabindex="0">' + crossIcon + '</span></span>');
                                $('.email-input-text').css('border', '0px');
                                $('.email-input-text').css('box-shadow', 'none');
                            }
                        }
                    }
                    $(this).val('');

                    if ($('.all-mail').html()) {
                        $('#recipient_email_address').attr("placeholder", '');
                    }
                }
            });

            //removing the email with enter key
            $(document).on('click keypress', '.cancel-email', function (e) {
                if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                    $(this).closest('.email-ids').remove();
                    if (!$('.all-mail').html()) {
                        $('.email-input-text').css('border', '');
                        $('.email-input-text').css('box-shadow', '');
                        $('#recipient_email_address').attr("placeholder", 'Enter recipient email');
                    }
                }
            });

            //Go back to calendar input when clear btn is click
            $(document).on('click keypress', '.export-cancel-btn', function (e) {
                if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
                    e.preventDefault();
                }
            });

            $(document).on('keydown', 'button.action-close', function (e) {
                if (e.which === 9) { // Tab key
                    setTimeout(function () {
                        $('button.action-close').attr('tabindex', '0');
                    },500);
                }
            });

            $('.export-user-listing-section span.action-close').on('keydown', function(e) {
                if (e.keyCode == 13) {
                    $('.email-input-text').css('border', '');
                    $('.email-input-text').css('box-shadow', '');
                    $(this).trigger('click');
                }
            });

            $(".reorder-close-icon").click(function(){
                $(".reorder-success").hide();
            });

            $(document).on('click', 'button.export-data', function (e) {
                let emailIds = [];
                let counter = 0;
                let userIds = $("#selected_users").val();
                $('.email-ids').each(function() { 
                    let emailHtml = '';
                    emailHtml = $(this).html().split("<span");
                    emailIds[counter] = emailHtml[0];
                    counter++;
                });

                // dataRange & Emails and make Ajax call to a controller to get Spreadsheet
                let requestUrl = "shared/users/generateuserreport/?userIds="+userIds+"&emailData="+emailIds;
                $.ajax({
                    url: urlBuilder.build(
                        requestUrl
                    ),
                    type: "GET",
                    data: '',
                    showLoader: true,
                    async: true
                }).done(function (response) {
                    $('.cancel-export').trigger('click');
                    $('.reorder-success-msg').html('');
                    $('.reorder-success-msg').append('<p class="reorder-notification-msg"><b>List exported</b></p>');
                    $('.reorder-success-msg').append('<p>You will receive the list of users in your inbox shortly.</p>'); 
                    $('.reorder-view-cart').hide();
                    $('.reorder-success').show();
                    $('.selected-number').hide();
                    $('.export-user-list-btn').prop('disabled', true);
                    $('.export-user-list-btn').removeClass('active');
                    $('.export-user-list-btn').addClass('disabled');
                    $('input[type=checkbox]').each( 
                        function () {
                          if ($('.custom_header_checkbox').is(":checked")) {
                                $(".custom_header_checkbox").trigger('click');
                          } else {
                                $(".custom_header_checkbox").trigger('click').trigger('click');
                          }
                    });
                });
            });
        },

        exportUserListingPopupModel: function() {
            let options = {
                type: 'popup',
                modalClass: 'export-user-listing-poup-model',
                responsive: true,
                innerScroll: false,
                title: '',
                buttons: [{
                    class: '',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };

            $('.export-user-listing-section').modal(options).modal('openModal');
            let selectedUsers = []; 
            $("input[type=checkbox]").each(function() {
                if (this.checked) {
                    if ($.isNumeric($(this).val())) {
                        selectedUsers.push($(this).val());
                    }
                } 
            });      
            $("#selected_users").val(selectedUsers);
            $('.export-user-listing-section').on('modalclosed', function() {
                $('.email-ids').remove();
                $('#recipient_email_address').val('')
                $('.email-input-text').css('border', '');
                $('.email-input-text').css('box-shadow', '');
                $('#recipient_email_address').attr("placeholder", 'Enter recipient email');
            });
        }
    });
});
