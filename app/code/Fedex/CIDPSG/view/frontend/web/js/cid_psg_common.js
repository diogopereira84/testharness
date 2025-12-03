require([
    'jquery',
    'mage/url',
    'Fedex_CIDPSG/js/confirmation_popup_modal',
    'Fedex_CIDPSG/js/multiselect.min',
    'mage/validation',
    'mage/calendar',
], function ($, urlBuilder, modalPopup) {
    $(window).on('load resize', function () {
        makeSpaceBetweenText();
    });

    /**
     * Make space between text
     *
     * @returns void
     */
    function makeSpaceBetweenText() {
        let buttonSpan = $("span[aria-label='Open Invoiced/Charge Account']");
        if (buttonSpan.length > 0 && $(window).width() < 373) {
            buttonSpan.text('Open Invoiced/ Charge Account');
        } else {
            buttonSpan.text('Open Invoiced/Charge Account');
        }
    }

    /**
     * Trigger radio button when enter or space key is pressed
     */
     $(document).on('keypress', '.custome-radio-btn-no , .custome-radio-btn-yes', function (e) {
        if(getSpaceEnterKeyCode(e)) {
            e.preventDefault();
            if($(this).hasClass('custome-radio-btn-no')) {
                $(".account_no_radio_no").trigger('click');
            } else if($(this).hasClass('custome-radio-btn-yes')) {
                $(".account_no_radio_yes").trigger('click');
            }
        }
    });

    /**
     * Trigger agreement checkbox on press of space and tab key
     */
    $(document).on('keypress', '.authorize-user-is-agree', function (e) {
        let _this = this;
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            e.preventDefault();
            $(_this).prev('#is_agree').trigger('click');
        }
    });

    /**
     * Trigger multiselect in account request form
     */
    document.multiselect('#state_of_exemption');
    $('#state_of_exemption_input').attr("placeholder", "Select an option");
    $('#state_of_exemption_input').attr("data-testid","E-349679-B-1648248-TK-2820794-cid-acc-request-state-exemption");
    
    /**
     * Trigger show hide tax exempt section
     */
    $(document).on("click keypress", '#tax_exe_check', function () {
        if($('#tax_exempt_set_as_default').is(':checked')) {
            $('#tax_exempt_checkbox').val("0");
            $('#tax_exempt_section').hide();
        } else {
            $('#tax_exempt_checkbox').val("1");
            $('#tax_exempt_section').show();
            $('#tax_exempt_set_as_default').prop('checked', false);
        }
    });

    /**
     * Set focus on checkbox for ADA
     */
    $(document).on('click', '#tax_exempt_set_as_default, #charge_acc_set_as_default, #company_info_set_as_default', function () {
        if($(this).hasClass('tax-exempt-check-input')) {
            $(this).next("#tax_exe_check").focus();
        } else if($(this).hasClass('charge-acc-bill-check-input')) {
            $(this).next("#charge_bill_checkbox").focus();
        } else if($(this).hasClass('company-info-check-input')) {
            $(this).next("#corr_checkbox").focus();
        }
    });

    /**
     * Trigger radio button when enter or space key is pressed
     */
    $(document).on('keypress', '.custom-radio-btn-yes , .custom-radio-btn-no, .special-req-radio-btn-no, .special-req-radio-btn-yes, #state_of_exemption_input, .multiselect-checkbox', function (e) {
        if(getSpaceEnterKeyCode(e)) {
            e.preventDefault();
            if($(this).hasClass('custom-radio-btn-no')) {
                $('#card_with_acc_no').trigger('click');
            }else if($(this).hasClass('custom-radio-btn-yes')) {
                $("#card_with_acc_yes").trigger('click');
            }else if($(this).hasClass('special-req-radio-btn-no')) {
                $("#charge_special_requirements_no").trigger('click');
            }else if($(this).hasClass('special-req-radio-btn-yes')) {
                $("#charge_special_requirements_yes").trigger('click');
            }else if($(this).hasClass('multiselect-input')) {
                $("#state_of_exemption_itemList").addClass('active');
            }else if($(this).hasClass('multiselect-checkbox')) {
                $(this).trigger('click');
            }
        }
    });

    /**
     * Trigger checkbox when enter or space key is pressed
     */
     $(document).on('keypress', '#corr_checkbox , #charge_bill_checkbox, #tax_exe_check, #tnc_check', function (e) {
        if(getSpaceEnterKeyCode(e)) {
            e.preventDefault();
            if($(this).hasClass('corr-company-add')) {
                $('#corr_checkbox').trigger('click');
                $(this).focus();
            }else if($(this).hasClass('charge-bill-checkbox')) {
                $("#charge_bill_checkbox").trigger('click');
                $(this).focus();
            }else if($(this).hasClass('tax-exe-check')) {
                $("#tax_exe_check").trigger('click');
                $(this).focus();
            }else if($(this).hasClass('tnc-check')) {
                $("#tc_set_as_default").trigger('click');
                $(this).focus();
            }
        }
     });

    /**
     * Authorized User form Submit
     * 
     */
    $(document).on('click','.au-form #btn_save_changes',function() {
        let accountCreateUrl = urlBuilder.build('cid/index/authorizedformsubmit');
        let formData = $('#au-form');
        let param = formData.serialize();
        let submitBtn = $(this);
        submitBtn.prop('disabled', true);
        if (formData.valid()) {
            $.ajax({
                type: 'post',
                url: accountCreateUrl,
                showLoader: true,
                data: formData.serialize(),
                dataType: 'json',
                cache: false,
                success: function(responseData) {
                    openSuccessPopup(formData);
                }
            });
        } else {
            $('input,select').trigger('click');
        }
    });

    /**
     * Trigger Account request form submit
     */
    $(document).on('click','.cidpsg-acc-req-form #btn_save_changes',function() {
        let accountCreateUrl = urlBuilder.build('cid/index/accountrequestsubmit');
        let formData = $('#cidpsg_acc_req_form');
        submitAccountRequestData(this, accountCreateUrl, formData);    
    });

    /**
     * To submit Account request form data with no of retires on faliure response
     */
    function submitAccountRequestData(btnObj, accountCreateUrl, formData){
        let submitBtn = $(btnObj);
        submitBtn.prop('disabled', true);
        if (formData.valid()) {
            $.ajax({
                type: 'post',
                url: accountCreateUrl,
                showLoader: true,
                data: formData.serialize(),
                dataType: 'json',
                cache: false,
                success: function(data){
                    if(data && data.Status != 'undefined' && typeof data.Status != 'undefined' &&
                    data.Status == "Success") {
                        openSuccessPopup(formData);
                    } else if((window.pegaRetryCount = (window.pegaRetryCount-1)) > 0){
                        submitAccountRequestData(btnObj, accountCreateUrl, formData);
                    } else {
                        triggerMailSend(formData);
                    }
                }
            });
        } else {
            $('input, select').trigger('click');
        }
    }

    /**
     * To send Email to Accounts and support Team after faliure response
     */
    function triggerMailSend(formData){
        let sendEmailUrl = urlBuilder.build('cid/index/sendsupportemail');
        $.ajax({
            type: 'post',
            url: sendEmailUrl,
            showLoader: true,
            dataType: 'json',
            cache: false,
            success: function() {
                openSuccessPopup(formData);
            }
        });
    }

    /**
     * open success popup
     *
     * @returns void
     */
    function openSuccessPopup(formData) {
        modalPopup().openModal();
        formData.trigger('reset');
    }

    /**
     * return keycode
     *
     * @returns bool
     */
    function getSpaceEnterKeyCode(event) {
        let keycode = (event.keyCode ? event.keyCode : event.which);
        return (keycode  == 13 || keycode  == 32) ? true : false
    }
    
    /**
     * show hide sections Account request form
     *
     * @returns void
     */
    function showHideSection(checkboxId, checkboxInptVal, targetSection ) {
        if($('#'+checkboxId).is(':checked')) {
            $('#'+checkboxInptVal).val("0");
            $('#'+targetSection).show();
        } else {
            $('#'+checkboxInptVal).val("1");
            $('#'+targetSection).hide();
            $('#'+checkboxId).prop('checked', false);
        }
    }

    /**
     * Get states of selected country
     *
     * @returns void
     */
      function getAllStates(e, stateId, stateIdNext) {
        let url = urlBuilder.build('cid/index/accountRequest');
        $.ajax({
            type: 'post',
            url: url,
            showLoader: true,
            data: { country_code: e.value },
            dataType: 'json',
            success: function(response) {
                let options = $('#'+stateId).empty();
                let optionsNext = '';
                let defaultOption = '<option value="">Select an option</option>';
                options.append(defaultOption);
                if (stateIdNext != undefined) {
                    optionsNext = $('#'+stateIdNext).empty();
                    optionsNext.append(defaultOption);
                }
                $.each(response, function () {
                    options.append('<option value=' + this.label + '>' + this.title + '</option>');
                    if (stateIdNext != undefined) {
                        optionsNext.append('<option value=' + this.label + '>' + this.title + '</option>');
                    }
                });
            }
        });
    }

    /**
     * formate phone no in USA format Ex: (123)456-7890
     *
     * @returns void
     */
    function formatPhoneNo(event, id) {
        let keyCode = event.keyCode || event.which;
        let key = String.fromCharCode(keyCode);
        if (keyCode === 8 || keyCode === 46) {
            return;
        }
        if (!/^\d$/.test(key)) {
            event.preventDefault();
        }
        $("#" + id).keyup(function () {
            $(this).val($(this).val().replace(/^(\d{3})(\d{3})(\d+)$/, "($1)$2-$3 "));
        });
    }

    /**
     * formate phone no in USA format Ex: (123)456-7890
     *
     * @returns void
     */
    $(document).on('input', '#phone, #phoneno, #fax, #corr_phoneno, #corr_fax, #charge_phoneno, #charge_fax, #tc_phoneno, .pa_phone', function (e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    });

    /**
     * Checkbox border red on error
     *
     * @returns void
     */
    $(document).on('click', '#tc_set_as_default', function () {
        if ($(this).hasClass('mage-error')) {
            $(".tnc-check").attr("style", "border:1px solid #de002e !important");
        } else {
            $(".tnc-check").attr("style", "");
        }
    });

    /**
     * Allow number only
     *
     * @returns void
     */
    $(document).on('input', '#employees_no_nationwide, #fedex_office_acc_no, #office_spent_amount, #spent_percent_with_fedex, #express_ship_amount, #express_ship_percent_with_fedex, #ground_ship_amount,#ground_ship_perrcent_with_fedex, #inter_ship_amount,#inter_ship_percent_with_fedex,.pa_allow_number_only', function (e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });

    /**
     * Checkbox border red on error
     *
     * @returns void
     */
    $(document).on('click', '#is_agree', function () {
        if ($(this).hasClass('mage-error')) {
            $(".authorize-user-is-agree").attr("style",  "border:1px solid #de002e !important");
        } else {
            $(".authorize-user-is-agree").attr("style",  "");
        }

    });

    /**
     * format zip code in 99999 or 99999-9999
     *
     * @returns void
     */
    function formatZipCodeValue(event) {
        let input = event.target;
        let inputValue = input.value.replace(/\D/g, '');
        if (inputValue.length > 4) {
            inputValue = inputValue.slice(0, 5) + '-' + inputValue.slice(5, 9);
        }
        input.value = inputValue;
    }

    /**
     * allow number in the field only
     *
     * @returns void
     */
    function allowOnlyNumbers(event) {
        let keyCode = event.keyCode || event.which;
        let key = String.fromCharCode(keyCode);
        if (keyCode === 8 || keyCode === 46) {
            return;
        }
        if (!/^\d$/.test(key)) {
            event.preventDefault();
        }
    }

    /**
     * validate authorize form
     *
     * @returns void
     */
    $(document).on('keyup change click', '#office_account_no.v-validate, #account_user_name.v-validate, #state.v-validate, #company_name.v-validate, #street_address.v-validate, #address_line_two.v-validate, #suite.v-validate, #city.v-validate, #zipcode.v-validate, #email.v-validate, #phone.v-validate, #is_agree.v-validate', function () {
        let accUserName = $('#account_user_name.v-validate').hasClass('valid');
        let officeAccountNo = $('#office_account_no.v-validate').hasClass('valid');
        let state = $('#state.v-validate').hasClass('valid');
        let companyName = $('#company_name.v-validate').hasClass('valid');
        let streetAddress = $('#street_address.v-validate').hasClass('valid');
        let addressLineTwo = !$('#address_line_two.v-validate').hasClass('mage-error');
        let suite = !$('#suite.v-validate').hasClass('mage-error');
        let city = $('#city.v-validate').hasClass('valid');
        let zipcode = $('#zipcode.v-validate').hasClass('valid');
        let email = $('#email.v-validate').hasClass('valid');
        let phone = $('#phone.v-validate').hasClass('valid');
        let isAgree = $('#is_agree.v-validate').hasClass('valid');

        if (accUserName && officeAccountNo && state && companyName && streetAddress && addressLineTwo && suite && city && zipcode && email && phone && isAgree) {
            $('#btn_save_changes').prop('disabled', false);
        } else {
            $('#btn_save_changes').prop('disabled', true);
        }
    });

    /**
     * Check agree button is clicked or not
     *
     * @returns void
     */
    $(document).on("click", '#is_agree', function () {
        if ($('#is_agree').is(':checked')) {
            $('#is_agree').val("1");
            $("#is_agree-error").hide();
        } else {
            $('#is_agree').val("0");
            $("#is_agree-error").show();
        }
    });

    /**
     * Check company page agree button is clicked or not
     *
     * @returns void
     */
    $(document).on("click", '#tc_set_as_default', function () {
        if ($('#tc_set_as_default').is(':checked')) {
            $('#tc_checkbox').val("1");
            $('#tc_set_as_default').val("1");
            $("#tc_set_as_default-error").hide();
        } else {
            $('#tc_checkbox').val("0");
            $('#tc_set_as_default').val("0");
            $("#tc_set_as_default-error").show();
        }
    });

    /**
     * Trigger show hide Correspondence Address section to add value
     */
    $(document).on("click keypress", '#company_info_set_as_default', function () {
        if ($('#company_info_set_as_default').is(':checked')) {
            $('#company_info_set_as_default').val("1");
        } else {
            $('#company_info_set_as_default').val("0");
        }
    });


    /**
     * Trigger show hide Billing/Invoicing Address section to add value
     */
    $(document).on("click keypress", '#charge_acc_set_as_default', function () {
        if ($('#charge_acc_set_as_default').is(':checked')) {
            $('#charge_acc_set_as_default').val("1");
        } else {
            $('#charge_acc_set_as_default').val("0");
        }
    });

    /**
     * Trigger show hideTax Exempt Status section to add value
     */
    $(document).on("click keypress", '#tax_exempt_set_as_default', function () {
        if ($('#tax_exempt_set_as_default').is(':checked')) {
            $('#tax_exempt_set_as_default').val("1");
            $('#tax_exempt_checkbox').val("1");
        } else {
            $('#tax_exempt_set_as_default').val("0");
            $('#tax_exempt_checkbox').val("0");
        }
    });

    /**
     * Trigger show special requirements section
     */
    $('#charge_special_requirements_no, #charge_special_requirements_yes').click(function () {
        if ($("#charge_special_requirements_yes").prop("checked")) {
            $('#application-req-container').show();
        } else if ($("#charge_special_requirements_no").prop("checked")) {
            $('#application-req-container').hide();
        }
    });

    /**
     * PA agreement mail send call and redirect to cid account page.
     */
     $(document).on('click','.psg-agreement-form #btn_agree_changes',function() {
        let paAgreementEmailUrl = urlBuilder.build('cid/index/sendpsgagreementemail');
        let formData = $('#psg-agreement-form');
        let submitBtn = $(this);
        submitBtn.prop('disabled', true);
        if (formData.valid()) {
            $.ajax({
                type: 'post',
                url: paAgreementEmailUrl,
                showLoader: true,
                data: formData.serialize(),
                dataType: 'json',
                cache: false,
                success: function (data) {
                    let accountType = $.inArray(data.account_type, [0, 1]) !== -1 ? data.account_type : 0;
                    window.location.href = window.BASE_URL + "cid/fedex-office-account-request?account=" + accountType + "&source=" + data.source;
                }
            });
        } else {
            $('input, select').trigger('click');
        }
    });

    /**
     * call PA agreement Form.
     */

    $(document).on('keyup change click blur', '#psg-agreement-form input', function () {
        paAgreementValidation();
    });
    
    /**
     * validate PA agreement Form.
     */
    function paAgreementValidation() {
        let inpuArr = [];
        $('#psg-agreement-form input:not(:hidden)').each(function (input) {
            let inputFieldId = $(this).attr('id');
            let inputFieldisRequired = $(this).data("isrequired");

            if (!$('#' + inputFieldId).is('[readonly]')) {
                if (inputFieldisRequired == 'required') {
                    let isValidClass = $('#' + inputFieldId).hasClass('valid');
                    inpuArr.push(isValidClass);
                } else {
                    let noErrorClass = !$('#' + inputFieldId).hasClass('mage-error');
                    inpuArr.push(noErrorClass);
                }
            }
        });

        let isFormValide = inpuArr.every(element => element === true);    
        if (isFormValide) {
            $('#btn_agree_changes').prop('disabled', false);
        } else {
            $('#btn_agree_changes').prop('disabled', true);
        }
    }

    /* 
    * To remove extra hidden div of mage-error class
    */
    $(document).on("change keyup keypress paste click focusout focusin", '.v-validate', function() {
        $('div.mage-error').each(function() {
            if ($(this).css('display') === 'none') {
                $(this).remove();
            }
        });
    });

    /*
    * To remove red border on the 'STATE OF EXEMPTION' field
    */
    $(document).on("change keyup keypress click focusout focusin", ".multiselect-checkbox", function () {
        if ($('.multiselect-checkbox:checked').length > 0) {
            $("#state_of_exemption_input").css("border-color", "rgb(142, 142, 142)");
        } else {
            $("#state_of_exemption_input").css("border-color", "rgb(222, 0, 46)");
        }
    });

    window.getAllStates = getAllStates;
    window.showHideSection = showHideSection;
    window.formatPhoneNo = formatPhoneNo;
    window.allowOnlyNumbers = allowOnlyNumbers;
    window.formatZipCodeValue = formatZipCodeValue;
    window.paAgreementValidation = paAgreementValidation;
});
