define([
    'ko',
    'jquery',
    'underscore',
    'mage/url',
    'Fedex_ExpressCheckout/js/express-checkout-shipto',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'Fedex_Delivery/js/model/pickup-data',

], function (
    ko,
    $,
    _,
    urlBuilder,
    expressCheckoutShiptoBuilder,
    togglesAndSettings,
    pickupData
) {
    'use strict';
    
    let telephoneRegex = /^(\([0-9]{3}\) |[0-9]{3}-)[0-9]{3}-[0-9]{4}$/;

    return function (shipping) {

        return shipping.extend({

            disablePlacePickupButton: ko.observable(true),

            initialize: function (config) {
                this._super();
                const self = this;
            },

            /**
             * Validate Input Name Field
             * @description - Validates the input name field based on E-376292 feature.
             * @param {String} element - The element to validate
             * @param {Object} e - The event object, if any
             * @returns {Array} [isValid,errorMessage] - An array containing the validation result and the error message
             */

            validateInputNameField: function (element, e = null) {
                let maxLength = 30;
                let elementValue = $(element).val();
                let isValid = true;
                let errorMessage = "";
                let isFocusOutEvent = e?.type === 'focusout' || e?.type === 'blur';

                if ( elementValue.length === 0 ) {
                    isValid = false;
                    errorMessage = "This is a required field.";
                }

                if ( elementValue.length === 1 && isFocusOutEvent ) {
                    isValid = false;
                    errorMessage = togglesAndSettings.nameErrorMessage;
                }

                // If the event attribute is a keyup, then we have to check if the max length limit has reached
                // and if the user is trying to type more characters, if so, then we have to show the error message.
                // The error message will be removed when the event is a focusout.
                if( e && e.type === 'keyup' ) {
                    if( elementValue.length >= maxLength ) {
                        isValid = false;
                        errorMessage = togglesAndSettings.nameErrorMessage;
                        // Set the value of the input field to the max length allowed.
                        $(element).val(elementValue.substring(0, maxLength));
                    }
                }

                return [isValid, errorMessage];
            },

            /**
             * validateContactOrAlternateInputNameField
             * @description - Validates the contact or alternate first name / last name field.
             * @param {String} element - The element to validate
             * @param {String} errorClass - The error class to add to the element
             * @param {Object} e - The event object, if any
             * @returns {Boolean} true if the field is valid, false otherwise
             */

            validateContactOrAlternateInputNameField: function (element, errorClass, e = null) {
                
                let [ isValid, errorMessage ] = this.validateInputNameField(element, e);
                let $errorElement = $(element).next();

                if (!isValid) {
                    $(element).addClass(errorClass);
                    $errorElement.show();
                    $errorElement.html(errorMessage);

                    return false;

                } else {
                    $(element).removeClass(errorClass);
                    $errorElement.hide();

                    return true;
                }

            },

            validateContactFirstName: function (e = null) {
                let contactFirstName = $('.contact-fname');
                let contactError = 'contact-error';

                return this.validateContactOrAlternateInputNameField(contactFirstName, contactError, e);
            },

            validateContactLastName: function (e = null) {
                let contactError = 'contact-error';
                let contactLastName = $('.contact-lname');

                return this.validateContactOrAlternateInputNameField(contactLastName, contactError, e);
            },

            validateContactNumber: function () { 
                let contactNumberValue = $('.contact-number').val();
                let contactNumberClean = $('.contact-number').val().replace(/\D/g, '');
                let contactNumber = $('.contact-number');
                let contactNumberError= $('.contact-number-error');
                let contactError = 'contact-error';
                
                if ( contactNumberClean.length === 0 ) {
                    contactNumber.addClass(contactError);
                    contactNumberError.show();
                    contactNumberError.html("Phone Number is required");

                    return false;
                } 
                else if ( contactNumberClean.length < 10 ) {
                    contactNumber.addClass(contactError);
                    contactNumberError.show();
                    contactNumberError.html("Please enter a valid phone number");

                    return false;
                } 
                else if ( !telephoneRegex.test(contactNumberValue) ) {
                    contactNumber.addClass('alternate-error');
                    contactNumberError.show(``);
                    contactNumberError.html("Please enter a valid phone number");

                    return false;
                } 
                else {
                    contactNumber.removeClass(contactError);
                    contactNumberError.hide();

                    return true;
                }
            },

            validateContactEmail: function () { 
                let userEmailValue = $('.contact-email').val();
                let regex = /^([_\-'.0-9a-zA-Z]+)@([_\-'.0-9a-zA-Z]+)\.([a-zA-Z]{2,7})$/;
                let contactEmail = $('.contact-email');
                let contactEmailError= $('.contact-email-error');
                let placePickupOrder = $('.place-pickup-order');
                let contactError = 'contact-error';

                if ( userEmailValue.length == 0 ) {
                    contactEmail.addClass(contactError);
                    contactEmailError.show();
                    contactEmailError.html("Email Address is required");

                    return false;
                } 
                else if ( !regex.test(userEmailValue) && userEmailValue.length > 0 ) {
                    contactEmail.addClass(contactError);
                    contactEmailError.show();
                    contactEmailError.html("Please enter a valid email address");

                    return false;
                } 
                else if ( userEmailValue.length > 150 ) {
                    contactEmail.addClass(contactError);
                    contactEmailError.show();
                    contactEmailError.html("Email address should not be greater than 150 characters");

                    this.disclosureModel.isCampaingAdDisclosureToggleEnable 
                        ? this.disablePlacePickupButton(true)
                        : placePickupOrder.attr('disabled', 'disabled');

                    placePickupOrder.addClass("place-pickup-order-disabled");
                    expressCheckoutShiptoBuilder.disabledReviewButtonForPickup();

                    return false;
                } 
                else {
                    contactEmail.removeClass(contactError);
                    contactEmailError.hide();

                    return true;
                }
            },

            validateAlternateFirstName: function (e = null) {
                let alternateFirstName = $('.alternate-fname');
                let alternateError = 'alternate-error';

                return this.validateContactOrAlternateInputNameField(alternateFirstName, alternateError, e);
            },

            validateAlternateLastName: function (e = null) {
                let alternateLastName = $('.alternate-lname');
                let alternateError = 'alternate-error';

                return this.validateContactOrAlternateInputNameField(alternateLastName, alternateError, e);
            },

            validateAlternateNumber: function () {
                let alternateContactNumber = $('.alternate-number').val();
                let alternateContactNumberClean = $('.alternate-number').val().replace(/\D/g, '');
                let alternateNumber = $('.alternate-number');
                let alternateNumberError = $('.alternate-number-error');
                let alternateError = 'alternate-error';

                if (alternateContactNumberClean.length === 0) {
                    alternateNumber.addClass(alternateError);
                    alternateNumberError.show();
                    alternateNumberError.html("Phone Number is required");

                    return false;
                } 
                else if (alternateContactNumberClean.length < 10) {
                    alternateNumber.addClass(alternateError);
                    alternateNumberError.show();
                    alternateNumberError.html("Please enter a valid phone number");

                    return false;
                } 
                else if (!telephoneRegex.test(alternateContactNumber)) {
                    alternateNumber.addClass(alternateError);
                    alternateNumberError.show();
                    alternateNumberError.html("Please enter a valid phone number");

                    return false;
                } 
                else {
                    alternateNumber.removeClass(alternateError);
                    alternateNumberError.hide();

                    return true;
                }
            },

            validateAlternateEmail: function () {
                
                let regex = /^([_\-'.0-9a-zA-Z]+)@([_\-'.0-9a-zA-Z]+)\.([a-zA-Z]{2,7})$/;
                
                let userEmailValue = $('.alternate-email').val();
                let alternateEmail = $('.alternate-email');
                let alternateEmailError = $('.alternate-email-error');
                let alternateError = 'alternate-error';

                if (userEmailValue.length == 0) {
                    alternateEmail.addClass(alternateError);
                    alternateEmailError.show();
                    alternateEmailError.html("Email Address is required");

                    return false;
                } 
                else if (!regex.test(userEmailValue) && userEmailValue.length > 0) {
                    alternateEmail.addClass(alternateError);
                    alternateEmailError.show();
                    alternateEmailError.html("Please enter a valid email address");

                    return false;
                } 
                else if ( userEmailValue.length > 150 ) {
                    alternateEmail.addClass(alternateError);
                    alternateEmailError.show();
                    alternateEmailError.html("Email address should not be greater than 150 characters");

                    this.disclosureModel.isCampaingAdDisclosureToggleEnable 
                        ? this.disablePlacePickupButton(true)
                        : $(".place-pickup-order").attr('disabled', 'disabled');
                    $(".place-pickup-order").addClass("place-pickup-order-disabled");
                    expressCheckoutShiptoBuilder.disabledReviewButtonForPickup();

                    return false;
                } 
                else {
                    alternateEmail.removeClass(alternateError);
                    alternateEmailError.hide();

                    return true;
                }
            },

            validateContactForm: function () { 
                
                let regex = /^([_\-'.0-9a-zA-Z]+)@([_\-'.0-9a-zA-Z]+)\.([a-zA-Z]{2,7})$/;

                let zipCodeFieldValidation = true;
                let contactFirstName = $('.contact-fname');
                let contactLastName = $('.contact-lname');
                let contactNumber = $('.contact-number');
                let placePickupOrder = $('.place-pickup-order');
                let contactEmail = $('.contact-email');
                let alternateFirstName = $('.alternate-fname');
                let alternateLastName = $('.alternate-lname');
                let placePickupOrderDisabled = 'place-pickup-order-disabled';

                if ( togglesAndSettings.isCheckoutQuotePriceDashable ) {
                    if ( $(".upload-to-quote #zipcodeLocation").val()?.length < 5) {
                        zipCodeFieldValidation = false;
                    }

                    if( togglesAndSettings.tiger_team_D_227679 && (
                        window.checkoutConfig.restricted_production_location || 
                        window.checkoutConfig.recommended_production_location
                    )) {
                        zipCodeFieldValidation = true;
                    }

                    // If its retail and window.checkoutConfig?.tiger_team_E_469378_u2q_pickup is true
                    if (window.checkoutConfig?.tiger_team_E_469378_u2q_pickup && window.checkoutConfig?.isRetailCustomer) {
                        zipCodeFieldValidation = pickupData.selectedPickupLocation() ? true : false;
                    }
                }
                if ( $(".alternate-from-container").css('display') == 'none' ) {
                    if ( ( contactFirstName.val().trim().length > 1 && contactFirstName.val().trim().length <= 30 ) 
                        && ( contactLastName.val().trim().length > 1 && contactLastName.val().trim().length <= 30 ) 
                        && telephoneRegex.test( contactNumber.val() ) 
                        && regex.test( contactEmail.val() ) && zipCodeFieldValidation
                    ){
                        this.disclosureModel.isCampaingAdDisclosureToggleEnable 
                            ? this.disablePlacePickupButton(false)
                            : placePickupOrder.prop("disabled", false);
                        placePickupOrder.removeClass(placePickupOrderDisabled);
                        expressCheckoutShiptoBuilder.enabledReviewButtonForPickup();
                    } 
                    else {
                        this.disclosureModel.isCampaingAdDisclosureToggleEnable 
                            ? this.disablePlacePickupButton(true)
                            : placePickupOrder.attr('disabled', 'disabled');
                        placePickupOrder.addClass(placePickupOrderDisabled);
                        expressCheckoutShiptoBuilder.disabledReviewButtonForPickup();
                    }
                } else {
                    if ( ( contactFirstName.val().trim().length > 1 && contactFirstName.val().trim().length <= 30 ) 
                        && ( contactLastName.val().trim().length > 1 && contactLastName.val().trim().length <= 30 )
                        && telephoneRegex.test( contactNumber.val() ) 
                        && regex.test( contactEmail.val() ) 
                        && ( alternateFirstName.val().trim().length > 1 && alternateFirstName.val().trim().length <= 30 ) 
                        && ( alternateLastName.val().trim().length > 1 && alternateLastName.val().trim().length <= 30 ) 
                        && telephoneRegex.test( $(".alternate-number").val() ) 
                        && regex.test( $(".alternate-email").val() )
                    ) {
                        this.disclosureModel.isCampaingAdDisclosureToggleEnable 
                            ? this.disablePlacePickupButton(false)
                            : placePickupOrder.prop("disabled", false);
                        placePickupOrder.removeClass(placePickupOrderDisabled);
                        expressCheckoutShiptoBuilder.enabledReviewButtonForPickup();
                    } 
                    else {
                        this.disclosureModel.isCampaingAdDisclosureToggleEnable 
                            ? this.disablePlacePickupButton(true)
                            : placePickupOrder.attr('disabled', 'disabled');
                        placePickupOrder.addClass(placePickupOrderDisabled);
                        expressCheckoutShiptoBuilder.disabledReviewButtonForPickup();
                    }
                }
            }    
        });
    };
});
