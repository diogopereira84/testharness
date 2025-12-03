/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require([
	'jquery'
], function ($) {
    /**
    * Validate company account form
    */
    $(document).on('keyup change click blur', '.cidpsg-acc-req-form input, .cidpsg-acc-req-form select', function () {
        let isCompanyInformationValidate = companyInformationValidation();
        let isChargeAccValidate = validateChargeAccOption();
        let isQuestionnaireValidate = validateQuestionnaire();
        let isTermAndConditionValidate = validateTermAndCondition();

        let isValidateCorrespondenceAddress;
        if ($("#company_info_set_as_default").val() == 0) {
            isValidateCorrespondenceAddress = validateCorrespondenceAddress();
        } else {
            isValidateCorrespondenceAddress = true;
        }

        let isChargeAccOptionForAddressValidate;
        if ($("#charge_acc_set_as_default").val() == 0) {
            isChargeAccOptionForAddressValidate = validateChargeAccOptionForAddress();
        } else {
            isChargeAccOptionForAddressValidate = true;
        }

        let isApplicationRequirementsValidate;
        if ($("#charge_special_requirements_yes").prop("checked") === true) {
            isApplicationRequirementsValidate = validateApplicationRequirements()
        } else {
            isApplicationRequirementsValidate = true;
        }

        let isTaxExemptFormValidate;
        if ($("#tax_exempt_set_as_default").val() == 1) {
            isTaxExemptFormValidate = taxExemptFormValidation();
        } else {
            isTaxExemptFormValidate = true;
        }

        if (!$('div').hasClass('charge-acc-option-container')) {
            isChargeAccValidate = true;
            isChargeAccOptionForAddressValidate = true;
            isApplicationRequirementsValidate = true;
        }

        let isAllValidationTrue = isCompanyInformationValidate && isValidateCorrespondenceAddress &&
            isChargeAccValidate && isChargeAccOptionForAddressValidate && isTaxExemptFormValidate &&
            isQuestionnaireValidate && isTermAndConditionValidate && isApplicationRequirementsValidate;
        if (isAllValidationTrue) {
            $("#btn_save_changes").prop('disabled', false);
        } else {
            $("#btn_save_changes").prop('disabled', true);
        }
    });

    /*
    * Tax Exempt
    */
    $(document).on('blur', '#state_of_exemption_input, .multiselect-checkbox', function () {
        taxExemptFormFieldValidation();
    });

    /**
    * Validate tax exempt
    * 
    * @returns bool
    */
    function taxExemptFormFieldValidation() {
        let stateOfExemption = ($('#state_of_exemption').val().length > 0) ? true : false;

        if (stateOfExemption) {
            $("#state_of_exemption_input").css("border-color", "#8e8e8e");
            $('#state_of_exemption-error').hide();
        } else {
            $("#state_of_exemption_input").css("border-color", "#de002e");
            $('#state_of_exemption-error').show();
        }
    }

    /**
    * Validate company information
    * 
    * @returns bool
    */
    function companyInformationValidation() {
        let legacyCompanyName = $('#legal_company_name').hasClass('valid');
        let preAccName = !$('#pre_acc_name').hasClass('mage-error');
        let contactFname = $('#contact_fname').hasClass('valid');
        let contactLname = $('#contact_lname').hasClass('valid');
        let streetAdd = $('#street_add').hasClass('valid');
        let suiteOther = !$('#suite_other').hasClass('mage-error');
        let city = $('#city').hasClass('valid');
        let cidPsgState = $('#cid_psg_state').hasClass('valid');
        let zip = $('#zip').hasClass('valid');
        let email = $('#email').hasClass('valid');
        let phoneNo = $('#phoneno').hasClass('valid');
        let fax = !$('#fax').hasClass('mage-error');
        let federalId = !$('#federal_id').hasClass('mage-error');
        let dunBradSteertNo = !$('#dun_bradstreet_no').hasClass('mage-error');
        let fedexOfficeAccountNumber = !$('#fedex_office_acc_no').hasClass('mage-error');
        let companyNameOnAcc = !$('#company_name_on_acc').hasClass('mage-error');
        let employeesNoNationwide = !$('#employees_no_nationwide').hasClass('mage-error');

        let isCompanyFormValidate = employeesNoNationwide && legacyCompanyName && preAccName && contactFname && contactLname && streetAdd && suiteOther && city && cidPsgState && zip && email && phoneNo && fax && federalId && dunBradSteertNo && fedexOfficeAccountNumber && companyNameOnAcc;

        if (isCompanyFormValidate) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Validate charge Account Options
    * 
    * @returns bool
    */
    function validateChargeAccOption() {
        let businessAccUsedIn = $('#business_acc_used_in').hasClass('valid');
        let natureOfBusiness = !$('#nature_of_business').hasClass('mage-error');
        let dateOfIncorp = !$('#date_of_incorp').hasClass('mage-error');
        let stateOfIncorp = !$('#state_of_incorp').hasClass('mage-error');
        let inBuisenessSince = !$('#in_buiseness_since').hasClass('mage-error');
        let stateOfBusiness = !$('#state_of_business').hasClass('mage-error');

        let isChargeAccountValidate = businessAccUsedIn && natureOfBusiness && dateOfIncorp && stateOfIncorp && inBuisenessSince && stateOfBusiness;

        if (isChargeAccountValidate) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Validate application requirements
    * 
    * @returns bool
    */
    function validateApplicationRequirements() {
        let applicableRequirements = $('#applicable_requirements').hasClass('valid');

        if (applicableRequirements) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Validate charge Account Options
    * 
    * @returns bool
    */
    function validateChargeAccOptionForAddress() {
        let chargeCidPsgState = $('#charge_cid_psg_state').hasClass('valid');
        let chargeFname = $('#charge_fname').hasClass('valid');
        let chargeLname = $('#charge_lname').hasClass('valid');
        let chargeStreetAdd = $('#charge_street_add').hasClass('valid');
        let chargeAddLine2 = !$('#charge_add_line2').hasClass('mage-error');
        let chargeSuiteOther = !$('#charge_suite_other').hasClass('mage-error');
        let chargeCity = $('#charge_city').hasClass('valid');
        let chargePostalCode = $('#charge_postal_code').hasClass('valid');
        let chargeEmail = $('#charge_email').hasClass('valid');
        let chargePhoneno = $('#charge_phoneno').hasClass('valid');
        let chargeFax = !$('#charge_fax').hasClass('mage-error');

        let isChargeAccountValidate = chargeCidPsgState && chargeFname && chargeLname && chargeStreetAdd && chargeAddLine2 && chargeSuiteOther && chargeCity && chargePostalCode && chargeEmail && chargePhoneno && chargeFax;

        if (isChargeAccountValidate) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Validate tax exempt
    * 
    * @returns bool
    */
    function taxExemptFormValidation() {
        let nameOnCertificate = $('#name_on_certificate').hasClass('valid');
        let stateOfExemption = ($('#state_of_exemption').val().length > 0) ? true : false;
        let noOfCertificate = !$('#no_of_certificate').hasClass('mage-error');
        let initials = $('#initials').hasClass('valid');

        let isTaxExemptionValidate = nameOnCertificate && stateOfExemption && noOfCertificate && initials;

        if (isTaxExemptionValidate) {
            return true;
        } else {
            return false;
        }
    }

    /** 
    * Questionnaire validation
    * 
    * @returns bool
    */
    function validateQuestionnaire() {
        let officeSpentAmount = !$('#office_spent_amount').hasClass('mage-error');
        let groundShipAmount = !$('#ground_ship_amount').hasClass('mage-error');
        let spentPercentWithFedex = !$('#spent_percent_with_fedex').hasClass('mage-error');
        let groundShipPerrcentWithFedex = !$('#ground_ship_perrcent_with_fedex').hasClass('mage-error');
        let expressShipAmount = !$('#express_ship_amount').hasClass('mage-error');
        let interShipAmount = !$('#inter_ship_amount').hasClass('mage-error');
        let expressShipPercentWithFedex = !$('#express_ship_percent_with_fedex').hasClass('mage-error');
        let interShipPercentWithFedex = !$('#inter_ship_percent_with_fedex').hasClass('mage-error');

        let isQuestionnaireValidate = officeSpentAmount && groundShipAmount && spentPercentWithFedex && groundShipPerrcentWithFedex && expressShipAmount && interShipAmount && expressShipPercentWithFedex && interShipPercentWithFedex;

        if (isQuestionnaireValidate) {
            return true;
        } else {
            return false;
        }
    }

    /** 
    * validate Term and conditions validation
    *
    *  @returns bool 
    */
    function validateTermAndCondition() {
        let tcFname = $('#tc_fname').hasClass('valid');
        let tcEmail = $('#tc_email').hasClass('valid');
        let tcLname = $('#tc_lname').hasClass('valid');
        let tcTitle = $('#tc_title').hasClass('valid');
        let tcPhoneno = $('#tc_phoneno').hasClass('valid');
        let tcSetAsDefault = $('#tc_set_as_default').hasClass('valid');

        let isTandCValidated = tcFname && tcEmail && tcLname && tcTitle && tcPhoneno && tcSetAsDefault;

        if (isTandCValidated) {
            return true;
        } else {
            return false;
        }
    }

    /** 
    * validate Correspondence Address in Company Information
    *
    *  @returns bool
    */
    function validateCorrespondenceAddress() {
        let corrFname = $('#corr_fname').hasClass('valid');
        let corrLname = $('#corr_lname').hasClass('valid');
        let corrStreetAdd = $('#corr_street_add').hasClass('valid');
        let corrSuiteOther = !$('#corr_suite_other').hasClass('mage-error');
        let corrCity = $('#corr_city').hasClass('valid');
        let corrCidPsgState = $('#corr_cid_psg_state').hasClass('valid');
        let corrPostalCode = $('#corr_postal_code').hasClass('valid');
        let corrEmail = !$('#corr_email').hasClass('mage-error');
        let corrPhoneno = $('#corr_phoneno').hasClass('valid');
        let corrFax = !$('#corr_fax').hasClass('mage-error');

        let isCorresAddress = corrFname && corrLname && corrStreetAdd && corrSuiteOther && corrCity && corrCidPsgState && corrPostalCode && corrEmail && corrPhoneno && corrFax;
        if (isCorresAddress) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return function
     * 
     */
    return {
        companyInformationValidation: companyInformationValidation,
        taxExemptFormValidation: taxExemptFormValidation,
        validateChargeAccOption: validateChargeAccOption,
        validateQuestionnaire: validateQuestionnaire,
        validateTermAndCondition: validateTermAndCondition,
        validateCorrespondenceAddress: validateCorrespondenceAddress,
        validateChargeAccOptionForAddress: validateChargeAccOptionForAddress,
        validateApplicationRequirements: validateApplicationRequirements
    };
});
