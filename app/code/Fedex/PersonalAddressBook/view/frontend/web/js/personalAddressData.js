/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
require([
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/modal',
    'fedex/storage',
    "Fedex_Delivery/js/view/google-places-api",
    'mage/translate',
], function ($, url, modal, fxoStorage, googlePlacesApi) {
    "use strict";
    var currentScrollPositionShippingAddressLine = 0;
    window.alphabetSearched = 0;
    $(document).ready(function () {
        let isFirstnameValid = true;
        let isLastnameValid = true;
        let isEmailValid = true;
        let isCompanyValid = true;
        let isAddressValid = true;
        let isCityValid = true;
        let isPhoneValid = true;
        let isZipValid = true;
        let telephoneRegex = /^(\([0-9]{3}\) |[0-9]{3}-)[0-9]{3}-[0-9]{4}$/;
        let nameRegex = /[$/@*()^!~\\]+/;

        /**
         * Validate Input Name Field
         * @description - Validates the input name field based on E-376292 feature.
         * @param {String} element - The element to validate
         * @param {Object} e - The event object, if any
         * @returns {Array} [isValid,errorMessage] - An array containing the validation result and the error message
         */
        function validateInputNameField(element, e = null) {
            let maxLength = 30;
            let elementValue = $(element).val();
            let isValid = true;
            let errorMessage = "";
            let isFocusOutEvent = e?.type === 'focusout' || e?.type === 'blur';

            if (elementValue.length === 0) {
                isValid = false;
                errorMessage = "This is a required field.";
            }

            if (elementValue.length === 1 && isFocusOutEvent) {
                isValid = false;
                errorMessage = 'Please enter between 2 and 30 characters.';
            }

            if (nameRegex.test(elementValue)) {
                isValid = false;
                errorMessage = "Special characters are not allowed.";
            }

            // If the event attribute is a keyup, then we have to check if the max length limit has reached
            // and if the user is trying to type more characters, if so, then we have to show the error message.
            // The error message will be removed when the event is a focusout.
            if(e && e.type === 'keyup') {
                if(elementValue.length >= maxLength) {
                    isValid = false;
                    errorMessage = window.checkoutConfig.input_name_error_message;
                    // Set the value of the input field to the max length allowed.
                    $(element).val(elementValue.substring(0, maxLength));
                }
            }

            return [isValid, errorMessage];
        }

        const modal = document.getElementById('addressModal');
        $('.personal_address_book_table .data-grid-th._sortable .data-grid-cell-content').attr("data-order","asc");
        let isSaveForEdit = 0;
        $(document).on('click', '.secondary.add-new-address', function () {
            modal.style.display = "block";
            $('.personal-modal-header h2').text($.mage.__('New Contact'));
            isSaveForEdit = 0;
            $('.btn-address-add').text('ADD');
        });

        $('tbody.searchaddressbookdata').on('click', '.edit-contact', function () {
            modal.style.display = "block";
            let $row = $(this).closest('tr');
            let customerContactId = $row.find('input[name="contactIDs[]"]').val();
            // Set the contact ID in the edit form's hidden input
            $('#contactIdInput').val(customerContactId);
            $('.personal-modal-header h2').text($.mage.__('Edit Contact'));
            $('#firstName').val($row.find('td[data-th="FIRST NAME"] .data-grid-cell-content').text().trim());
            $('#lastName').val($row.find('td[data-th="LAST NAME"] .data-grid-cell-content').text().trim());
            $('#company').val($row.find('td[data-th="COMPANY"]').text().trim());
            $('#addressLine1').val($row.find('input[name="streetLines1"]').val().trim());
            $('#addressLine2').val($row.find('input[name="streetLines2"]').val().trim());
            $('#city').val($row.find('td[data-th="CITY"] .data-grid-cell-content').text().trim());
            $('#state').val($row.find('td[data-th="STATE"] .data-grid-cell-content').text().trim());
            $('#zipCode').val($row.find('td[data-th="ZIP"] .data-grid-cell-content').text().trim());
            $('#phone').val($row.find('input[name="phoneNumber"]').val().trim());
            $('#ext').val($row.find('input[name="phoneNumberExten"]').val().trim());
            $('.residence').attr('checked', false);
            if ($row.find('input[name="residence"]').val().trim() == 'true') {
                $('.residence').attr('checked', 'checked');
            }
            $('.btn-address-add').text('SAVE');
            $('.btn-address-add').removeAttr('disabled');
            isSaveForEdit = 1;
        });

        $('.close, .btn-address-cancel').on('click', function() {
            modal.style.display = "none";
            isSaveForEdit = 0;
            $('#personal-addressForm').find('div.mage-error').hide();
        });
        
        $('.close ,.btn-address-cancel').on('click', function () {
            closeModal();
            $('#personal-addressForm').find('div.mage-error').hide();
        });

        $('tbody.searchaddressbookdata').on('click', '.kebab-image.category-list', function(event) {
            $(this).parent().next().toggle();
            let kebabElement = this;
        });
        $(document.body).click( function(event) {
            if(event.target.className !== 'kebab-image category-list'){
                $('.kebab-image.category-list').parent().next().css("display","none");
            }
        });

        // Function to close the modal
        function closeModal() {
            $('#addressModal').hide();
            $('#personal-addressForm')[0].reset();
            restorePlaceholders();
        }

        // Remove placeholder when input is clicked
        $('input[placeholder]').on('focus', function () {
            // Store the original placeholder
            $(this).data('original-placeholder', $(this).attr('placeholder'));
            // Remove the placeholder
            $(this).attr('placeholder', '');
        });

        // Restore placeholder when input loses focus and is empty
        $('input[placeholder]').on('blur', function () {
            // Restore the original placeholder if the input is empty
            if ($(this).val() === '') {
                $(this).attr('placeholder', $(this).data('original-placeholder'));
            }
        });

        // Function to restore all placeholders (used when closing modal)
        function restorePlaceholders() {
            $('input[placeholder]').each(function () {
                if ($(this).val() === '') {
                    $(this).attr('placeholder', $(this).data('original-placeholder'));
                }
            });
        }

        $('.admin__control-checkbox.custom_header_checkbox').on('change click', function () {
            if ($(this).is(':checked')) {
                $(".admin__control-checkbox.custom_row_checkbox").prop("checked", true);
            } else {
                $(".admin__control-checkbox.custom_row_checkbox").prop("checked", false);
            }
        });

        $(document).on('keyup blur', 'input[name=firstName]', function (e) {
            let [isValid, errorMessage] = validateInputNameField(e.target, e);
            isFirstnameValid = isValid;

            if (isFirstnameValid) {
                $("#firstname_validate").empty();
                $('.btn-address-add').removeAttr('disabled');
            } else {
                $('#firstName-error').hide();
                $("#firstname_validate").html(errorMessage);
                $('.btn-address-add').attr('disabled', 'disabled');
                isFirstnameValid = false;
            }
        });
        $(document).on('keyup blur', 'input[name=lastName]', function (e) {
            let [isValid, errorMessage] = validateInputNameField(e.target, e);
            isLastnameValid = isValid;
            if (isLastnameValid) {
                $("#lastname_validate").empty();
                $('.btn-address-add').removeAttr('disabled');
            } else {
                $('#lastName-error').hide();
                $("#lastname_validate").html(errorMessage);
                $('.btn-address-add').attr('disabled', 'disabled');
                isLastnameValid = false;
            }
        });
        $(document).on('keyup blur', 'input[name=email]', function (e) {
            let inputText = e.target.value.trim();
            let pattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
            if (inputText.length == 0) {
                $("#email_validate").html('Email id is required.');
                $('.btn-address-add').attr('disabled', 'disabled');
                $('#email-error').hide();
                isEmailValid = false;
            } else if (!inputText.match(pattern)) {
                $("#email_validate").html('Please enter a valid email address.');
                $('.btn-address-add').attr('disabled', 'disabled');
                $('#email-error').hide();
                isEmailValid = false;
            } else if (inputText.length > 150) {
                $("#email_validate").html('Email address should not be greater than 150 characters.');
                $('.btn-address-add').attr('disabled', 'disabled');
                $('#email-error').hide();
                isEmailValid = false;
            } else {
                $("#email_validate").empty();
                isEmailValid = true;
                $('.btn-address-add').removeAttr('disabled');
            }
        });
        $(document).on('keyup blur', 'input[name=company]', function (e) {
            let company = e.target.value;
            if (company.length <= 30) {
                $("#company_validate").empty();
                $('.btn-address-add').removeAttr('disabled');
                isCompanyValid = true;
            } else {
                $("#company_validate").html('Please enter less than or equals to 30 characters.');
                $('.btn-address-add').attr('disabled', 'disabled');
                isCompanyValid = false;
            }
        });
        $(document).on('keyup blur', 'input[name=addressLine2]', function (e) {
            let addressLine2 = e.target.value;
            if (addressLine2.length <= 70) {
                $("#address2_validate").empty();
                $('.btn-address-add').removeAttr('disabled');
                isAddressValid = true;
            } else {
                $('#addressLine2-error').hide();
                $("#address2_validate").html('Please enter less than or equals to 70 characters.');
                $('.btn-address-add').attr('disabled', 'disabled');
                isAddressValid = false;
            }
        });
        $(document).on('keyup blur', 'input[name=city]', function (e) {
            let city = e.target.value;
            if (city.length == 0) {
                $('#city_validate').show();
                $('#city_validate').html("This is a required field.");
                $('.btn-address-add').attr('disabled', 'disabled');
                $('#city-error').hide();
                isCityValid = false;
            } else if (city.match(/[^A-Za-z0-9-' \d]/gi)) {
                $('#city_validate').show();
                $('#city_validate').html("Special characters are not allowed.");
                $('.btn-address-add').attr('disabled', 'disabled');
                $('#city-error').hide();
                isCityValid = false;
            } else {
                $('.btn-address-add').removeAttr('disabled');
                $('#city_validate').hide();
                $('#city-error').hide();
                isCityValid = true;
            }
        });
        $(document).on('keyup blur', 'input[name=phone]', function (e) {
            let phone = e.target.value;
            if (phone.length == 0) {
                $('#phone_validate').show();
                $('#phone_validate').html("This is a required field.");
                $('.btn-address-add').attr('disabled', 'disabled');
                $('#phone-error').hide();
                isPhoneValid = false;
            } else if (!telephoneRegex.test(phone)) {
                $('#phone_validate').show();
                $('#phone_validate').html("Please enter a valid phone number");
                $('.btn-address-add').attr('disabled', 'disabled');
                $('#phone-error').hide();
                isPhoneValid = false;
            } else {
                $('.btn-address-add').removeAttr('disabled');
                $('#phone_validate').hide();
                $('#phone-error').hide();
                isPhoneValid = true;
            }
        });
        $(document).on('keyup blur', 'input[name=zipCode]', function (e) {
            let zipCode = e.target.value;
            let zipCodeUpdated = zipCode.replace(/\D/g, '').match(/(\d{0,5})(\d{0,4})/);
            if (zipCodeUpdated[2]) {
                e.target.value = zipCodeUpdated[1] + '-' + zipCodeUpdated[2];
            } else {
                e.target.value = zipCodeUpdated[1];
            }
            if (zipCode.length == 0 || zipCode.length < 5) {
                $('.btn-address-add').attr('disabled', 'disabled');
                isZipValid = false;
            } else {
                $('.btn-address-add').removeAttr('disabled');
                isZipValid = true;
            }
        });
        $('.btn-address-add').on('click', function (e) {
            e.preventDefault();
            
            if (isFirstnameValid && isLastnameValid && isEmailValid && isCompanyValid && isAddressValid && isCityValid && isPhoneValid && isZipValid) {                
                let contactID = $('#contactIdInput').val();
                e.preventDefault();
                var firstName = $('#firstName').val();
                var lastName = $('#lastName').val();
                var streetLine1 = $('#addressLine1').val();
                var streetLine2 = $('#addressLine2').val();
                var streetLines = [streetLine1, streetLine2];
                var city = $('#city').val();
                var email = $('#email').val();
                var zipCode = $('#zipCode').val();
                var residence = $("input[type=checkbox]").prop("checked");
                var company = $('#company').val();
                var stateCode = $('#state').find(":selected").text();
                var phoneNumber = $('#phone').val();
                let ext = $('#ext').val();
                let saveAddressRequestUrl = url.build("personaladdressbook/index/saveaddressbook");
                var addresspostData = {
                    nickName: firstName + '' + lastName,
                    firstName: firstName,
                    lastName: lastName,
                    email: email,
                    localNumber: phoneNumber,
                    ext: ext,
                    streetLines: streetLines,
                    city: city,
                    stateOrProvinceCode: stateCode,
                    postalCode: zipCode,
                    countryCode: "US",
                    residential: residence,
                    type: residence ? 'HOME' : 'BUSINESS',
                    companyName: company,
                    opCoTypeCD: "EXPRESS_AND_GROUND",
                    contactID: contactID,
                    isSaveForEdit: isSaveForEdit
                };

                let addressValidationRequestUrl = url.build("shippingaddressvalidation/index/addressvalidate");
                let addressValidationData = {
                    postcode: zipCode,
                    city: city,
                    region: stateCode,
                    firstname: firstName,
                    lastname: lastName,
                    street: streetLines,
                    telephone: phoneNumber
                }
                validateAddress(addressValidationRequestUrl, addressValidationData, function(response) {
                    if (typeof response !== 'undefined' && response !== null && response.length !== 0 && response.hasOwnProperty("output") 
                        && response.output.hasOwnProperty("resolvedAddresses") && typeof response == 'object') {
                        fxoStorage.set('validatedAddress', response);
                        fxoStorage.set('shippingFormAddress', addressValidationData);
                        let validatedAddress = fxoStorage.get("validatedAddress");
                        if (validatedAddress != null && typeof validatedAddress.output != 'undefined') {
                            let addressModal = document.getElementById('addressModal');
                            addressModal.style.display = "none";
                            openAddressValidationModal(isSaveForEdit);
                        }
                    } else {
                        addAddressAjax(saveAddressRequestUrl, addresspostData, isSaveForEdit);
                    }
                })
            }
        });

        $('.img-close-msg').on('click', function (e) {
            $(".succ-msg").hide();
            $(".err-msg").hide();
        });
        $('.personal_address_book_table .data-grid-th._sortable .data-grid-cell-content.first-sort').trigger('click');
    });

    //Deleting addressbook Data 
    $('tbody.searchaddressbookdata').on('click', '.delete-contact', function () {
        let $row = $(this).closest('tr');
        let contactID = $row.find('input[name="contactIDs[]"]').val();
        let alertIconImage = typeof (window.checkout) != 'undefined' && typeof (window.checkout.alert_icon_image) != 'undefined' && window.checkout.alert_icon_image != null ? window.checkout.alert_icon_image : '';
        let contentDetails = '<div class="delete-popup-content"><h3 class="delete-item-name">Clear your cart?</h3><p class="delete-item-description">This action cannot be reversed.</p></div>';
        modal = $.mage.confirm({
            content: contentDetails,
            buttons: [{
                text: $.mage.__('CANCEL'),
                'class': 'clear-action-secondary clear-action-dismiss',
                click: function () {
                    modal.closeModal();
                }
            }, {
                text: $.mage.__('DELETE'),
                'class': 'delete-action-primary clear-action-accept',
                click: function () {
                    let requestUrl = url.build("personaladdressbook/index/deletepersonalbookdata");
                    var postData = {
                        contactID: contactID
                    };
                    $.ajax({
                        url: requestUrl,
                        type: "POST",
                        data: postData,
                        showLoader: true,
                        dataType: 'json',
                        success: function (response) {
                            if (response.error_msg || response.errors) {
                                $(".succ-msg").hide();
                                $(".err-msg .message").text("System error, Please try again.");
                                $(".err-msg").show();
                                $('html, body').animate({
                                    scrollTop: $(".msg-container").offset().top
                                }, 500);
                            } else {
                                $(".succ-msg .message").text('Your address has been deleted.');
                                $(".succ-msg").show();
                                $('html, body').animate({
                                    scrollTop: $(".msg-container").offset().top
                                }, 500);
                                $row.remove();
                            }
                            $('.confirm-clear-cart').hide();
                            $('.loading-mask[data-role="loader"]').hide();
                            $('.modals-overlay').hide();
                            $('.selected-delete-container').hide();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 500);
                        },

                    });

                }
            }],
            modalClass: 'confirm-clear-cart update-delete-popup',
            title: '<img src="' + alertIconImage + '" class="delete-alert-icon-img" aria-label="delete_image" /><br>' + $.mage.__('Are you sure you want to delete?'),
            closed: function () {
                $('.loading-mask[data-role="loader"]').hide();
            }
        });

        $(document).on('modalclosed', '.modal-popup', function () {
            $('.loading-mask[data-role="loader"]').hide();
        });
        $('.modal-inner-wrap .action-close').trigger('focus');
    });

    $(document).ready(function () {
        let selectedCount = 0;
        $(document).on('click', '.admin__control-checkbox', function () {
            // Update count based on checked boxes
            selectedCount = $('.custom_row_checkbox:checked').length;
            if (selectedCount > 0) {
                $('.selected-delete-container').show();
                $('.selected-count').text(selectedCount + ' Selected');
            } else {
                $('.selected-delete-container').hide();
            }
        });

        // Handle bulk delete click
        $('.bulk-delete-button').on('click', function () {
            let selectedIds = [];
            $('.admin__control-checkbox:checked').each(function () {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length > 0) {
                let alertIconImage = typeof (window.checkout) != 'undefined' &&
                    typeof (window.checkout.alert_icon_image) != 'undefined' &&
                    window.checkout.alert_icon_image != null ?
                    window.checkout.alert_icon_image : '';

                let contentDetails = '<div class="delete-popup-content">' +
                    '<h3 class="delete-item-name">Delete selected addresses?</h3>' +
                    '<p class="delete-item-description">This action cannot be reversed.</p></div>';

                modal = $.mage.confirm({
                    content: contentDetails,
                    buttons: [{
                        text: $.mage.__('CANCEL'),
                        'class': 'clear-action-secondary clear-action-dismiss',
                        click: function () {
                            modal.closeModal();
                        }
                    }, {
                        text: $.mage.__('DELETE'),
                        'class': 'delete-action-primary clear-action-accept',
                        click: function () {
                            let requestUrl = url.build("personaladdressbook/index/deletepersonalbookdata");
                            // Convert array to comma-separated string
                            let contactIDString = selectedIds.join(',');
                            $.ajax({
                                url: requestUrl,
                                type: "POST",
                                data: { contactID: contactIDString },
                                showLoader: true,
                                dataType: 'json',
                                success: function (response) {
                                    if (response.error_msg || response.errors) {
                                        $(".succ-msg").hide();
                                        $(".err-msg .message").text("System error, Please try again.");
                                        $(".err-msg").show();
                                    } else {
                                        $(".succ-msg .message").text('Your addresses have been deleted.');
                                        $(".succ-msg").show();
                                        selectedIds.forEach(function (id) {
                                            $(`input[value="${id}"]`).closest('tr').remove();
                                        });
                                    }
                                    $('.confirm-clear-cart').hide();
                                    $('.loading-mask[data-role="loader"]').hide();
                                    $('.modals-overlay').hide();
                                    $('.selected-delete-container').hide();
                                    $('html, body').animate({
                                        scrollTop: $(".msg-container").offset().top
                                    }, 500);
                                }
                            });
                        }
                    }],
                    modalClass: 'confirm-clear-cart update-delete-popup',
                    title: '<img src="' + alertIconImage + '" class="delete-alert-icon-img" aria-label="delete_image" /><br>' +
                        $.mage.__('Are you sure you want to delete?'),
                    closed: function () {
                        $('.loading-mask[data-role="loader"]').hide();
                    }
                });
            }
        });

        createPagination();
        setPageSize();

        $(document).on('keydown', function(e) {
            var target = e.target;
            var shiftPressed = e.shiftKey;
            if (e.keyCode == 9) {
                if ($('#addressModal').length) {
                    var borderElem = shiftPressed ?
                                        $('#addressModal').find('input:visible,select:visible,button:visible,textarea:visible').first() 
                                     :
                                        $('#addressModal').find('input:visible,select:visible,button:visible,textarea:visible').last();
                    if ($(borderElem).length) {
                        if ($(target).is($(borderElem))) {
                            $('#addressModal').find('input:visible,select:visible,button:visible,textarea:visible').first().focus();
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
            }
            return true;
        });

        $(document).on('input', '#phone', function (e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });

        $('#personal-addressForm input.required').on('focusout', function(e) {
            if ($(this).valid()) {
                $(this).next('.mage-error').hide();
            }
        });

        $('.clear-address-search').on('keydown', function(e) {
            var key = e.keyCode;
            if (key == 13) {
                $(this).trigger('click');
            }
        });

        $('.personal_address_book_table .data-grid-th._sortable').on('keydown', function(e){
            var key = e.keyCode;
            if (key == 13) {
                $(this).find('.data-grid-cell-content').trigger('click');
            }
        });

        $('#personal-addressForm').on('keydown', '.residence', function(e) {
            var key = e.keyCode;
            if (key == 13) {
                $(this).prop('checked', true);
                e.preventDefault();
                return false;
            }
        });

        $('table.personal_address_book_table').on('keydown', '.data-grid-checkbox-cell , .data-grid-multicheck-cell', function(e) {
            var key = e.keyCode;
            if (key == 13) {
                $(this).find('input.admin__control-checkbox').trigger('click');
            }
        });

        $('tbody.searchaddressbookdata').on('keydown', '.edit-contact', function(e) {
            var key = e.keyCode;
            if (key == 13) {
                $(this).trigger('click');
                $('.edit-contact-dropdown-content').hide();
            }
        });

        $('tbody.searchaddressbookdata').on('keydown', '.delete-contact', function(e) {
            var key = e.keyCode;
            if (key == 13) {
                $(this).trigger('click');
                $('.edit-contact-dropdown-content').hide();
            }
        });
    });

    $('select.address-search-options').on('change', function () {
        var valueSelected = this.value;
        valueSelected = valueSelected.replace("personal_address_", "");
        valueSelected = valueSelected.replaceAll("_", " ");
        valueSelected = valueSelected.replace(/([A-Z])/g, " $1").trim();
        valueSelected = valueSelected.toLowerCase();
        $('input.personal-address-search-field')[0].placeholder = "Search by "+valueSelected;
    });

    $('.address-filter-section .search-address-alphabet').on('click', function () {
        $('.search-address-alphabet').removeClass('selected-alphabet');
        $(this).addClass('selected-alphabet');
        var alphabet = $(this).data('alphabet');
        searchAddressbookbyAlphabet(alphabet, 0);
    });

    $('.clear-address-search').on('click', function () {
        $('.search-address-alphabet').removeClass('selected-alphabet');
        $('.personal-address-search-field').val('');
        searchAddressbookbyAlphabet('', 1);
    });

    $('.address-filter-section .search-address-alphabet').on('keypress', function (e) {
        if(e.which == 13){
            $(this).trigger('click');
        }

    });

    function searchAddressbookbyAlphabet(alphabet, clear) {
        var requestUrl = url.build("personaladdressbook/index/searchbyalphabet");
        var searchHtml = '';
        $.ajax({
                url: requestUrl,
                type: "POST",
                data: {alphabet:alphabet,clear:clear,alphabetSearched:window.alphabetSearched},
                showLoader: true,
                dataType: 'json',
                success: function (response) {
                    if (response.error_msg || response.errors) {
                        $(".succ-msg").hide();
                        $(".err-msg .message").text("System error, Please try again.");
                        $(".err-msg").show();
                        $('html, body').animate({
                            scrollTop: $(".msg-container").offset().top
                        }, 500);
                    } else {
                        var responseData = response.data;
                        window.alphabetSearched = 1;
                        if (typeof responseData !== 'undefined' && responseData.length) {
                            if (!clear) {
                                window.totalRecords = responseData.length;
                            } else {
                                window.totalRecords = window.allRecords;
                            }
                        }
                        searchHtml = addressesResponseHtml(responseData);
                            
                        $('tbody.searchaddressbookdata').html(searchHtml);
                        createPagination();
                    }
                }
        });
    }

    $('input.personal-address-search-field').on('keypress',function(e) {
        if(e.which == 13) {
            $('#personalAddressSearch').trigger('click');
        }
    });

    $('#personalAddressSearch').on('click', function (e) {
        e.preventDefault();
        var inputLength = $('#keyword').val().length;
            if (inputLength > 0 && inputLength < 2) {
            $(this).addClass('error');
            $('.error-message').show();
        } else {
            $(this).removeClass('error');
            $('.error-message').hide();
            var addressSearchOption = $('#addressSearchOptions').val();
            var searchField = $('input.personal-address-search-field').val();
            if(searchField) {
                searchAddressbook(searchField ,addressSearchOption);
            }
        }
    });

    /**
     * searchAddressbook
     * @param {*} searchField 
     * @param {*} addressSearchOption 
     */
    function searchAddressbook(searchField, addressSearchOption) {
        let requestUrl = url.build("personaladdressbook/index/searchaddressbook");
        var searchHtml = '';
        var postData = {
            addressSearchOption: addressSearchOption,
            searchField: searchField
        };
        $.ajax({
            url: requestUrl,
            type: "POST",
            data: postData,
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if (response.error_msg || response.errors) {
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 500);
                } else {
                    $(".err-msg").hide();
                    var responseData = response.data;
                    var totalRecords = response.totalRecords;
                    
                    if (typeof responseData !== 'undefined' && typeof totalRecords !== 'undefined' && responseData.length) {
                        window.totalRecords = totalRecords;
                    }
                        
                    searchHtml = addressesResponseHtml(responseData);
                       
                    $('tbody.searchaddressbookdata').html(searchHtml);
                    createPagination();
                }
            }
        });
    }
    var lastAddressSortOption;
    $('.personal_address_book_table .data-grid-th._sortable .data-grid-cell-content').after().on('click', function (e) {
        e.preventDefault();
        $('.personal_address_book_table .data-grid-th._sortable .data-grid-cell-content').removeClass('sorted');
        var addressSortOption = $(this).data('th'); 
        var order = $(this).data('order');
        if(addressSortOption != lastAddressSortOption) {
            $('.personal_address_book_table .data-grid-th._sortable .data-grid-cell-content').data("order","asc");
            $('.personal_address_book_table .data-grid-th._sortable .data-grid-cell-content').attr("data-order","asc");
        }
        if (order == 'desc') {
            $(this).data('order', "asc");
            $(this).attr('data-order', "asc");
        } else {
            $(this).data('order', "desc");
            $(this).attr('data-order', "desc");
        }
        $(this).addClass('sorted');
        lastAddressSortOption = addressSortOption;
        sortAddressbook(addressSortOption,order);
    });

    /**
     * Sort Addresses
     * @param {*} addressSortOption 
     * @param {*} order 
     */
    function sortAddressbook(addressSortOption, order) {
        let requestUrl = url.build("personaladdressbook/index/sortaddressbook");
        var responseHtml = '';
        var postData = {
            addressSortOption: addressSortOption,
            order: order
        };
        $.ajax({
            url: requestUrl,
            type: "POST",
            data: postData,
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if (response.error_msg || response.errors) {
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 500);
                } else {
                    $(".err-msg").hide();
                    var responseData = response.data;
                    responseHtml = addressesResponseHtml(responseData);
                    
                    $('tbody.searchaddressbookdata').html(responseHtml);
                    createPagination();
                }
            }
        });
    }


    /**
     * validateAddress
     * @param  {string}   requestUrl
     * @param  {object}   data
     * @param  {Boolean}  shipHere
     * @param  {Function} callback
     * @return 
     */
    function validateAddress(requestUrl, data, callback) {
        $.ajax({
            url: requestUrl,
            type: "POST",
            data: {
                zipcode: data.postcode,
                city: data.city,
                stateCode: data.region,
                firstName: data.firstname,
                lastName: data.lastname,
                streetLines: data.street,
                phoneNumber: data.telephone
            },
            dataType: "json",
            showLoader: true,
            async: true,
            success: function (data) {
                callback(data);
            }
        });
    }

        /**
     * Address Validation Modal
     * @param boolean isSaveForEdit
     * @return boolean
     */
    function openAddressValidationModal(isSaveForEdit) {
        var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: 'Verify your address',
                modalClass: 'address-validation-popup',
                    buttons: [
                               {
                                text: $.mage.__('Select Address'),
                                class: 'select-validated-address button action primary',
                                click: function () {
                                    let validatedAddress = fxoStorage.get("validatedAddress");                                    
                                    if (validatedAddress != null && typeof validatedAddress.output != 'undefined') {
                                        let resolvedAddresses = validatedAddress.output.resolvedAddresses;
                                        let regionCode = $("select[name=region_id] option:selected").text();
                                        let selectedAddress = $("input[name='validated-address']:checked").val();
                                        if (selectedAddress == 'recommend') {
                                                if (resolvedAddresses.length) {
                                                    resolvedAddresses.forEach(function (item, index) {
                                                        let streetLines = item.streetLinesToken[0];
                                                        let city = item.cityToken[0].value;
                                                        let postalcode = item.postalCodeToken.value;
                                                        let countryCode = item.countryCode;
                                                        $('input#addressLine1').val(streetLines).trigger("change");
                                                        $('input#city').val(city).trigger("change");
                                                        $('input#zipCode').val(postalcode).trigger("change");
                                                    });
                                                }
                                            } else {
                                                 let shippingFormAddress = fxoStorage.get("shippingFormAddress");
                                            }
                                        }
                                        this.closeModal();
                                    }
                                }
                            ]
                    };

            $('.address-validation-info').modal(options);
            $('.address-validation-info').on('modalopened', function (e) {
                $('button.action-close').focus();
                e.stopPropagation();
                if ($('.modal-footer div.primary').length == 0) {
                    $('.select-validated-address').wrap('<div class="primary"></div>');
                }
                $('.address-validation-popup').find('.modal-footer').addClass('actions-toolbar');
                $('.select-validated-address').attr("disabled", true);
                
                let validatedAddress = fxoStorage.get("validatedAddress");
                let shippingFormAddress = fxoStorage.get("shippingFormAddress");
                let recommendedOptionHtml = '';
                let regionCode = shippingFormAddress.region;
                if (validatedAddress != null && typeof validatedAddress.output != 'undefined') {
                    let resolvedAddresses = validatedAddress.output.resolvedAddresses;
                     if (resolvedAddresses.length) {
                        resolvedAddresses.forEach(function (item, index) {
                            let streetLines = item.streetLinesToken[0];
                            let city = item.cityToken[0].value;
                            let postalcode = item.postalCodeToken.value;
                            let countryCode = item.countryCode;
                            recommendedOptionHtml = '<input type="radio" name="validated-address" id="recommend-address" value="recommend" tabindex="-1"><label for="recommend-address">Recommended Address</label><br>';
                            recommendedOptionHtml += '<div class="address">' + streetLines + '<br>' + city + ', ' + regionCode + ' ' + postalcode + '</div>';
                            $('.recommended-validated-addresses').html(recommendedOptionHtml);
                        });
                    }
                }
                
                let originalOptionHtml = '';
                let streetLines = shippingFormAddress.street[0] + ' ' + shippingFormAddress.street[1];
                let city = shippingFormAddress.city;
                let postalcode = shippingFormAddress.postcode;
                originalOptionHtml = '<input type="radio" name="validated-address" id="original-address" value="original" tabindex="-1"><label for="original-address">Original Address</label><br>';
                originalOptionHtml += '<div class="address">' + streetLines + '<br>' + city + ', ' + regionCode + ' ' + postalcode + '</div>';
                $('.original-validated-addresses').html(originalOptionHtml);
                $("input[name='validated-address']").on('click', function() {
                    $('.select-validated-address').attr("disabled", false);
                });
                $("input[name='validated-address']").on('keypress', function(event) {
                    var keycode = (event.keyCode ? event.keyCode : event.which);
                    if (keycode == 13) {
                        $(this).prop("checked", true);
                    }
                });
                $(document).on('keydown', ".address-validation-container", function (e) {
                    if (e.which == 13) {
                        $(this).find("input[name='validated-address']").prop("checked", true).trigger('click');
                    }
                });
            });
            $('.address-validation-info').on('modalclosed', function (e) {
                e.stopImmediatePropagation();
                let contactID = $('#contactIdInput').val();
                var firstName = $('#firstName').val();
                var lastName = $('#lastName').val();
                var email = $('#email').val();
                var streetLine1 = $('#addressLine1').val();
                var streetLine2 = $('#addressLine2').val();
                var streetLines = [streetLine1, streetLine2];
                var city = $('#city').val();
                var zipCode = $('#zipCode').val();
                var residence = $("input[type=checkbox]").prop("checked");
                var company = $('#company').val();
                var stateCode = $('#state').find(":selected").text();
                var phoneNumber = $('#phone').val();
                var ext = $('#ext').val();
                let saveAddressRequestUrl = url.build("personaladdressbook/index/saveaddressbook");
                var addresspostData = {
                    nickName: firstName + '' + lastName,
                    firstName: firstName,
                    lastName: lastName,
                    email: email,
                    localNumber: phoneNumber,
                    streetLines: streetLines,
                    city: city,
                    stateOrProvinceCode: stateCode,
                    postalCode: zipCode,
                    ext: ext,
                    countryCode: "US",
                    residential: residence,
                    type: residence ? 'HOME' : 'BUSINESS',
                    companyName: company,
                    opCoTypeCD: "EXPRESS_AND_GROUND",
                    contactID: contactID,
                    isSaveForEdit: isSaveForEdit
                };
                addAddressAjax(saveAddressRequestUrl, addresspostData, isSaveForEdit);
            });
            $(".address-validation-info").modal("openModal");
        return true;
    }

    /**
     * addAddressAjax
     * @param {String}  requestUrl
     * @param {Object}  postData
     * @param {Boolean} isSaveForEdit
     */
    function addAddressAjax(requestUrl, postData, isSaveForEdit) {

        $('tbody.searchaddressbookdata').html($('.loaderData').html());
        
        $.ajax({
                url: requestUrl,
                type: "POST",
                data: postData,
                showLoader: true,
                dataType: 'json',
                success: function (response) {
                    if(isSaveForEdit) {
                        if (response.error_msg || response.errors) {
                            $(".succ-msg").hide();
                            $(".err-msg .message").text("System error, Please try again.");
                            $(".err-msg").show();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 500);
                        } else {
                            window.location.reload();
                            $(".succ-msg .message").text('You have successfully edited your contact information.');
                            $(".succ-msg").show();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 1000);
                        }
                    } else {
                        if (response.error_msg || response.errors) {
                            $(".succ-msg").hide();
                            $(".err-msg .message").text("System error, Please try again.");
                            $(".err-msg").show();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 500);
                        } else {
                            window.location.reload();
                            $(".succ-msg .message").text('You have successfully added a new contact.');
                            $(".succ-msg").show();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 1000);
                        }
                    }
                    let addressModal = document.getElementById('addressModal');
                    addressModal.style.display = "none";
                }
            });
    }

    //B-2011817 :: POD2.0: Implement google suggestion in Checkout page for shipping form
    $(document).on('keyup keypress', '#personal-addressForm #addressLine1', function (e) {
        e.stopImmediatePropagation();
        $('#personal-addressForm #addressLine1').attr("autocomplete", "off");
        currentScrollPositionShippingAddressLine = window.scrollY;
        if ($(this).data('key') !== e.which || e.which === 8) {
            let streetValue = null;
            let validateAddress = false;
            var inputDiv = $('#personal-addressForm .address-line-one');
            var div = $('<div id="geocoder-results-shipping" class="google-maps-main"></div>');
            inputDiv.append(div);
            let input = document.querySelector('#personal-addressForm #addressLine1');
            input.addEventListener('input', function() {
                streetValue = this.value;
                let addressLine1 = e.target.value;
                if (addressLine1.length > 0 && addressLine1.length <= 70) {
                    $("#address1_validate").empty();
                    $('#addressLine1-error').hide();
                    $('.btn-address-add').removeAttr('disabled');
                    validateAddress = true;
                } else if (addressLine1.length == 0) {
                    $('#addressLine1-error').hide();
                    $('.btn-address-add').attr('disabled', 'disabled');
                    validateAddress = false;
                } else {
                    $('#addressLine1-error').hide();
                    $("#address1_validate").html('Please enter less than or equals to 70 characters.');
                    $('.btn-address-add').attr('disabled', 'disabled');
                    validateAddress = false;
                }
                if (validateAddress) {
                    if (streetValue.length >= 2) {
                        $('#geocoder-results-shipping').show();
                        googlePlacesApi.loadAutocompleteServicePersonalAddress(streetValue);
                    } else {
                        $('#geocoder-results-shipping').hide();
                        googlePlacesApi.resetGeoCoderResults();
                    }
                }    
            });
            $(this).data('key', e.which);
        }
    });

    $(document).on('click', function (e) {
        if (!$(e.target).hasClass('.google-maps-main')) {
            $('#geocoder-results-shipping').hide();
        }
    });

    /**
     * Create Pagination Data
     * @return void
     */
    function createPagination() {
        var totalRecords = typeof window.totalRecords !== 'undefined' ? window.totalRecords : 0;
        var pageSize = typeof window.pageSize !== 'undefined' ? window.pageSize: 10;
        var pageCount = Math.ceil(totalRecords / pageSize);
        var currentPage = typeof window.currentPage !== 'undefined' ? window.currentPage : 1;
        var startIndex = (currentPage - 1) * pageSize + 1;
        var endIndex = Math.min(currentPage * pageSize, totalRecords);
        $('#pagination').empty();
        if (totalRecords == 0) {
            startIndex = 0;
        }
        $('.toolbar .pager .toolbar-amount span').text('Items '+ startIndex +' to '+ endIndex +' of '+ totalRecords +' total');       
        $('.toolbar .pages .page-title').text('Showing page '+ currentPage + ' of '+ pageCount);
        if (totalRecords > pageSize) {
            var button = '';
            if (currentPage > 1) {
                button += '<a class="page-button prev" href="#"><</a>';
            }
            for (var i = 1; i <= pageCount; i++) {
                button += '<a class="page-button" id="page-link-'+ i +'" href="#">' + i + '</a>';
            }
            if (currentPage < pageCount) {
               button += '<a class="page-button next" href="#">></a>';
            }
            $('#pagination').html(button);
            $('#page-link-'+currentPage).addClass('active');
            $('a.page-button').on('click',function(e) {
                e.preventDefault();
                if ($(this).text() == '>') {
                    currentPage = currentPage + 1;
                } else if($(this).text() == '<') {
                    currentPage = currentPage - 1;
                } else {
                    currentPage = parseInt($(this).text());
                }
                window.currentPage = currentPage;
                $('a.page-button').removeClass('active');
                $('#page-link-'+currentPage).addClass('active');
                addressBookPage(currentPage, pageSize);
                createPagination();
            });
        }
    }

    /**
     * Set Page Size
     */
    function setPageSize() {
        $(document).on('change', 'select.pagesize-options', function(){
            var pageSize = $(this).val();
            window.pageSize = pageSize;
            addressBookPage(1, pageSize, true);
            createPagination();
        });
    }

    /**
     * Address Book Paginate Ajax
     * @param  int  currentPage
     * @param  int  pageSize
     * @param  Boolean setPageSize
     * @return void
     */
    function addressBookPage(currentPage, pageSize, setPageSize = false) {
        var requestUrl = url.build("personaladdressbook/index/addressbookpage");
        var searchHtml = '';
        $.ajax({
            url: requestUrl,
            type: "POST",
            data: {currentPage:currentPage, pageSize:pageSize, setPageSize:setPageSize},
            showLoader: true,
            dataType: 'json',
            success: function (response) {
                if (response.error_msg || response.errors) {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 500);
                } else {
                    var responseData = response.data;
                    searchHtml = addressesResponseHtml(responseData);
                    
                    $('tbody.searchaddressbookdata').html(searchHtml);
                    createPagination();
                }
            }
        });
    }

    /**
     * Handle response HTML
     * @param {*} responseData 
     */
    function addressesResponseHtml(responseData) {
        let responseHtml = '';
        if (typeof responseData !== 'undefined' && responseData.length) {
            responseData.forEach(function (item, index) {
                let contactID = typeof item.contactID !== 'undefined' ? item.contactID : 0;
                let firstName = typeof item.firstName !== 'undefined' ? item.firstName : '';
                let lastName = typeof item.lastName !== 'undefined' ? item.lastName : '';
                let companyName = typeof item.companyName !== 'undefined' ? item.companyName : '';
                let streetLine1 = typeof item.address.streetLines[0] !== 'undefined' ? item.address.streetLines[0] : '';
                let streetLine2 = typeof item.address.streetLines[1] !== 'undefined' ? item.address.streetLines[1] : '';
                let addressData = streetLine1 + ' ' + streetLine2;
                let city = '';
                if (typeof item.city !== 'undefined') {
                    city = item.city;
                } else if (typeof item.address.city !== 'undefined') { 
                    city = item.address.city;
                }
                let stateOrProvinceCode = '';
                if (typeof item.stateOrProvinceCode !== 'undefined') {
                    stateOrProvinceCode = item.stateOrProvinceCode;
                } else if (typeof item.address.stateOrProvinceCode !== 'undefined') { 
                    stateOrProvinceCode = item.address.stateOrProvinceCode;
                }
                let postalCode = '';
                if (typeof item.postalCode !== 'undefined') {
                    postalCode = item.postalCode;
                } else if (typeof item.address.postalCode !== 'undefined') { 
                    postalCode = item.address.postalCode;
                }
                let residential = '';
                if (typeof item.residential !== 'undefined') {
                    residential = item.residential;
                } else if (typeof item.address.residential !== 'undefined') { 
                    residential = item.address.residential;
                }
                let phoneNumber = typeof item.phoneNumber !== 'undefined' ? item.phoneNumber : '';
                let phoneNumberExten = typeof item.phoneNumberExten !== 'undefined' ? item.phoneNumberExten : '';
                
                responseHtml += '<tr class="data-row disabled"><td tabindex="0" class="data-grid-checkbox-cell"><label class="data-grid-checkbox-cell-inner"><input aria-label="custom_row_checkbox" class="admin__control-checkbox custom_row_checkbox" type="checkbox" data-action="select-row"   id="idscheck'+ contactID +'" value="'+ contactID +'"><input id="contactID" type="hidden" name="contactIDs[]" value="'+ contactID +'"></label></td>';
                responseHtml += '<td class="long-text-field" data-th="LAST NAME"><div class="data-grid-cell-content">'+ lastName +'</div></td><td class="long-text-field" data-th="FIRST NAME"><div class="data-grid-cell-content">'+ firstName +'</div></td>';
                responseHtml += '<td class="long-text-field" data-th="COMPANY">'+ companyName +'</td><td data-th="ADDRESS"><div class="data-grid-cell-content">'+addressData+'</div></td><td data-th="CITY"><div class="data-grid-cell-content"> '+ city +'</div></td>';
                responseHtml += '<td class="long-text-field" data-th="STATE"><div class="data-grid-cell-content">'+ stateOrProvinceCode +'</div></td><td class="long-text-field" data-th="ZIP"><div class="data-grid-cell-content">'+ postalCode +'<input type="hidden" name="phoneNumber" value="'+ phoneNumber +'" /><input type="hidden" name="streetLines1" value="'+ streetLine1 +'" /><input type="hidden" name="streetLines2" value="'+ streetLine2 +'" /><input type="hidden" name="phoneNumberExten" value="'+ phoneNumberExten +'" /><input type="hidden" name="residence" value="'+ residential +'" /></div></td>';
                responseHtml += '<td class="data-grid-actions-cell col actions" data-th="ACTIONS"><div aria-label="edit-contact-dropdown" class="manageusers-dropdown edit-contact-dropdown"><div indextab="0"><a aria-label="Manage Contact Actions" class="kebab-image category-list" href="javascript:void(0)"></a></div><div class="manageusers-dropdown-content edit-contact-dropdown-content"><a tabindex="0" class="action edit-contact">Edit</a><a tabindex="0" class="action delete-contact">Delete</a></div></div></td></tr>';
            });
        } else {
            responseHtml += '<tr><td colspan="9">No Record Found.</td></tr>';
        }

        return responseHtml;
    }
});
