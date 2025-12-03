/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    "Magento_Catalog/js/price-utils",
    "Magento_Checkout/js/model/quote",
    "fedex/storage",
    "Magento_Ui/js/modal/modal",
    "Fedex_Delivery/js/view/google-places-api",
    "Magento_Checkout/js/action/select-shipping-address",
    'Fedex_Delivery/js/model/toggles-and-settings',
    "uiRegistry"
], function ($, priceUtils, quote, fxoStorage, modal, googlePlacesApi, selectShippingAddress, togglesAndSettings, uiRegistry) {
    'use strict';

    var explorersD174773Fix = false;
    var isCheckoutConfigAvaliable = typeof (window.checkoutConfig) !== 'undefined' && window.checkoutConfig !== null ? true : false;
    var currentScrollPositionShippingAddressLine = 0;
    var isFocusInFromGoggleSuggestions = false;
    var maegeeks_pobox_validation = typeof window.checkoutConfig.maegeeks_pobox_validation != 'undefined' ? window.checkoutConfig.maegeeks_pobox_validation : false;


    if (isCheckoutConfigAvaliable) {
        explorersD174773Fix = typeof (window.checkoutConfig.explorers_d_174773_fix) != 'undefined' && window.checkoutConfig.explorers_d_174773_fix != null ? window.checkoutConfig.explorers_d_174773_fix : false;
    }
    /**
     * Shipping address form fields listeners
     */
    $(document).on('keyup blur', 'form.form-shipping-address :input', function (e) {
        if (typeof $(this).attr("name") != "undefined") {
            continueToPaymentButtonHandler();
        }
    })

    /**
     * Fedex shipping account number field listener
     */
    $(document).on('blur', '.fedex_account_number-field', function () {
        continueToPaymentButtonHandler();
    })

    /**
     * Check store is sde or not
     *
     * @returns bool
     */
    function isSdeStore() {
        return window.checkoutConfig.is_sde_store != undefined
            ? Boolean(window.checkoutConfig.is_sde_store)
            : false;
    }

    /**
     * Check customer is isSelfRegCustomer or not
     *
     * @returns bool
     */
    function isSelfRegCustomer() {
        return window.checkoutConfig.is_selfreg_customer;
    }
    /**
     * Enabled / Disable continue to payment button
     *
     * @param {boolean} enable
     */
    function continueToPaymentButtonStatus(enable) {
        var continueToPaymentButton = $('#shipping-method-buttons-container').find('.continue');
        if (enable === true) {
            continueToPaymentButton.prop("disabled", false);
            continueToPaymentButton.removeClass("disabled");
        } else {
            continueToPaymentButton.prop("disabled", true);
            continueToPaymentButton.addClass("disabled");
        }
    }

    /**
     * Check if phone number is valid or not
     *
     * @param {string} phoneNumber
     * @return bool
     */
    function isValidPhoneNumber(phoneNumber) {
        if (phoneNumber.length > 0 && phoneNumber.length === 14 && isNaN(phoneNumber)) {
            return true;
        }
        return false;
    }

    /**
     * Check if email address is valid or not
     *
     * @param {string} email
     * @return bool
     */
    function isValidEmailAddress(email) {
        let pattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
        if (email.length > 0 && email.length <= 150 && email.match(pattern)) {
            return true;
        }
        return false;
    }

    /**
     * Check if continue to payment button can be enabled or not
     * Should be enabled only when store is SDE and all the required fields are entered and field should contain valid entry
     *
     * @return void
     */
    function continueToPaymentButtonHandler() {
        if ((isSdeStore() === true || isSelfRegCustomer() === true) && $('#shipping-method-buttons-container').is(":visible")) {
            var buttonDisabled = false;
            continueToPaymentButtonStatus(true);
            $('form.form-shipping-address :input').each(function () {
                if ($(this).is(":visible")
                    && typeof $(this).attr('name') != "undefined"
                    && ((typeof $(this).attr('aria-required') != "undefined" && $(this).attr('aria-required') == 'true' && $(this).val() == '')
                        || (typeof $(this).attr('aria-invalid') != "undefined" && $(this).attr('aria-invalid') == 'true')
                    )
                ) {
                    continueToPaymentButtonStatus(false);
                    buttonDisabled = true;
                }
            })
            //if button not disabled validate additional fields to confirm
            if (buttonDisabled === false) {
                if (!togglesAndSettings.tiger_e486666) {
                    //1. Fedex Shipping Account number
                    let shippingAccountNumberField = $('.fedex_account_number-field');
                    if (shippingAccountNumberField.prop('disabled') === false && isSelfRegCustomer() === false) {
                        continueToPaymentButtonStatus(false);
                        buttonDisabled = true;
                    }
                }
            }
            if (buttonDisabled === false) {
                //2. Alternate contact person form field validation
                let contactSelectionElement = $('input[name=contact-form]:checked');
                if (contactSelectionElement.val() == 'isNotsame') {
                    let firstName = $('input[name=alternate_firstname]').val().trim();
                    let lastName = $('input[name=alternate_lastname]').val().trim();
                    let phoneNumber = $('input[name=alternate_phonenumber]').val();
                    let email = $('input[name=alternate_email]').val();
                    let nameRegex = /[$/@*()^!~\\]+/;
                    let isD193257ToggleEnable = typeof window.checkoutConfig.explorers_d_193257_fix != 'undefined' ? window.checkoutConfig.explorers_d_193257_fix : false;
                    let nameFieldValidation = false;
                    if (isD193257ToggleEnable && (nameRegex.test(firstName) || nameRegex.test(lastName))) {
                        nameFieldValidation = true;
                    }
                    if (firstName.length <= 0 || firstName.length < 2 || firstName.length > 30
                        || lastName.length <= 0 || lastName.length < 2 || lastName.length > 30
                        || !isValidPhoneNumber(phoneNumber.trim())
                        || !isValidEmailAddress(email.trim())
                        || nameFieldValidation
                    ) {
                        continueToPaymentButtonStatus(false);
                        buttonDisabled = true;
                    }
                }
            }
        }
    }

    /**
     * Get default shipping account number
     *
     * @returns string
     */
    function getDefaultShippingAccountNumber() {
        return window.checkoutConfig.shipping_account_number != undefined
            ? window.checkoutConfig.shipping_account_number
            : '';
    }

    /**
     * Mask shipping account number in the field
     */
    function maskShippingAccountNumber(accountNumber) {
        if (accountNumber.length > 8) {
            var masked = "*" + accountNumber.substr(-4);
            $('.fedex_account_number-field').val(masked);
        }
    }

    function isCommercialCustomer() {
        if ( window.checkoutConfig?.tiger_team_B_2429967) {
            return window.checkoutConfig?.is_commercial;
        }

        return isSelfRegCustomer() || isSdeStore();
    }

    /**
     * Apply default shipping account number in checkout
     * B-1517822 | Allow Shipping account number for SelfReg
     */
    function applyDefaultShippingAccountNumber() {
        if (isCommercialCustomer()) {
            if (typeof $('.fedex_account_number-field').val() !== "undefined"){
                maskShippingAccountNumber($('.fedex_account_number-field').val());
            }
            $('#addFedExAccountNumberButton').trigger('click');
        }
    }

    /**
     * onloadMaskShippingAccountNumber
     * @return void
     */
    function onloadMaskShippingAccountNumber(self) {
        let defaultShippingAccountNumber = getDefaultShippingAccountNumber();
        if (isCommercialCustomer()) {
            if (typeof defaultShippingAccountNumber !== "undefined" && defaultShippingAccountNumber.length > 8) {
                let maskedAccountNumber = "*" + defaultShippingAccountNumber.substr(-4);
                if(window.d207891_toggle){
                    self.shippingAccountNumber(defaultShippingAccountNumber);
                    self.shippingAccountNumberPlaceHolder(maskedAccountNumber);
                }else{
                    self.shippingAccountNumber(maskedAccountNumber);
                }
            } else {
                $('.checkout-shipping-method').hide();
            }
        }
    }

    /**
    * AutoPopulate  ShippingAccountNumber
    *
    * @return boolean
    */
    function autoPopulateShippingAccountNumber(self) {
        if (isCommercialCustomer()) {
            let defaultShippingAccountNumber = getDefaultShippingAccountNumber();
            if (typeof defaultShippingAccountNumber !== "undefined" &&  defaultShippingAccountNumber != '') {
                self.shippingAccountNumber(defaultShippingAccountNumber);
                self.isMaskonAutopopulate(true);
                applyDefaultShippingAccountNumber();
                if (isSdeStore()){
                    return false;
                }
            } else if(isSdeStore()){
                $('.checkout-shipping-method').hide();
            }
        }
        return true;
    }

    /**
     * Shipping Info Validate
     *
     * @param  object self
     * @param  boolean validate
     * @return boolean
     */
    function shipInfoValidate(self, validate) {
        if (validate){
            $('.checkout-shipping-method').show();
            if (!window.checkoutConfig.tigerteamE469373enabled) {
                $('#addFedExAccountNumberButton').trigger('click');
            }
            $('#closeLocalDeliveryMessage').trigger('click');
        } else {
            $('.checkout-shipping-method').hide();
        }
        let fedExAccountNumberFieldLength = (typeof $('.fedex_account_number-field').val() !== "undefined") ? $('.fedex_account_number-field').val().length : 0;
        if(!togglesAndSettings.tiger_e486666 && isSdeStore() && (self.shippingAccountNumber().length == 0 || fedExAccountNumberFieldLength == 0)) {
            $("#fedExAccountNumber_validate").html('Fedex account number is required.');
            $('.checkout-shipping-method').hide();

            return false;
        }
        return true;
    }

    $(document).on('input keypress click', '#fedex_account_no', function (e) {
        specialCharacterRestriction(this);
    });

    $(document).on('input keypress click', '#fedex-account-number', function (e) {
        specialCharacterRestriction(this);
    });

    /**
     * Shipping Info Validate
     *
     * @param  object self
     * @param  boolean validate
     * @return boolean
     */
    function specialCharacterRestriction(inputText) {
        $(inputText).on('keypress',$.proxy(function (evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }, this));

        $(inputText).on('paste', $.proxy(function (event) {
            if (event.originalEvent.clipboardData.getData('Text').match(/[^\d]/)) {
                event.preventDefault();
            }
        }, this));
    }

    /**
     * Show estimated shipping total
     *
     * D-97499 Estimated shipping total issue fix while applying/removing fedex account number
     * B-1309407 Implementing similar summary for retail and sde
     * @param {*} estimatedShippingTotal
     */
    function handleEstimatedShippingTotal(estimatedShippingTotal) {
        let deliveriesTotalAmt;
        if(window.e383157Toggle){
            deliveriesTotalAmt = fxoStorage.get("marketplaceShippingPrice");
        }else{
            deliveriesTotalAmt  = localStorage.getItem('marketplaceShippingPrice');
        }
        deliveriesTotalAmt = typeof deliveriesTotalAmt === 'string' && deliveriesTotalAmt !== '' ? +deliveriesTotalAmt : 0.00;
        if (typeof estimatedShippingTotal !== 'object' && estimatedShippingTotal != $("TBD").selector) {
            estimatedShippingTotal = priceUtils.formatPrice(estimatedShippingTotal, quote.getPriceFormat());
        }
        if (typeof estimatedShippingTotal == 'object') {
            estimatedShippingTotal = "TBD";
        }
        $(".estimated.totals .amount .estimated_shipping_total").text(estimatedShippingTotal);
        var shippingEstimatedTotal = $(".estimated.totals .amount .estimated_shipping_total").text();
        if (shippingEstimatedTotal != "TBD" && shippingEstimatedTotal != "$0.00") {
            $(".estimated.totals.estimated-shipping-total").removeClass("hide-estimated-shipping-total");
            $(".estimated-shipping-message").removeClass("hide-estimated-shipping-total");
            if(!deliveriesTotalAmt) {
                $(".totals.shipping").addClass("hide-shipping-total");
                $(".checkout-index-index.is-fcl-global .totals.shipping").addClass("hide-shipping-total");
            }
            $(".masked-shipping-account-number").text(getShippingAccountNumber());
        } else {
            $(".estimated.totals.estimated-shipping-total").addClass("hide-estimated-shipping-total");
            $(".estimated-shipping-message").addClass("hide-estimated-shipping-total");
            $(".totals.shipping").removeClass("hide-shipping-total");
            $(".checkout-index-index.is-fcl-global .totals.shipping").removeClass("hide-shipping-total");
        }
    }

    /**
     * Get Applied Shipping account number
     *
     * @returns int
     */
    function getShippingAccountNumber() {
        var shippingAccountNumber;
        if(window.e383157Toggle){
            shippingAccountNumber = fxoStorage.get("shipping_account_number");
        }else{
            shippingAccountNumber = window.localStorage.getItem("shipping_account_number");
        }
        if (shippingAccountNumber != '' && !isNaN(shippingAccountNumber)) {
            return Number(String(shippingAccountNumber).slice(-4));
        }

        return null;
    }

    /**
     * B-1294484: Get Site Configured Fedex Account Number
     *
     * @returns string
     */
    function getCompanyFedExAccountNumber() {
        let companyFedexAccountNumber = null;
        let defaultPaymentMethod = typeof window.checkoutConfig.default_payment_method != 'undefined'
            ? window.checkoutConfig.default_payment_method
            : '';
        let fedexAccountNumberPaymentMethod = window.checkoutConfig.fedex_account_payment_method_identifier;
        let paymentInfo = typeof window.checkoutConfig.default_payment_method_value != 'undefined'
            ? window.checkoutConfig.default_payment_method_value
            : '';
        if (defaultPaymentMethod && defaultPaymentMethod != '') {
            if (defaultPaymentMethod == fedexAccountNumberPaymentMethod && paymentInfo && paymentInfo != '') {
                companyFedexAccountNumber = paymentInfo;
            }
        } else if (defaultPaymentMethod && defaultPaymentMethod == '' && paymentInfo && paymentInfo != '') {
            if (typeof paymentInfo[fedexAccountNumberPaymentMethod] !== 'undefined') {
                companyFedexAccountNumber = paymentInfo[fedexAccountNumberPaymentMethod];
            }
        }

        return companyFedexAccountNumber;
    }

    /**
     * Check if customer has agreed to terms and conditions
     * B-1415208 : Make terms and conditions mandatory
     *
     * @returns bool
     */
    function hasAgreedToTermsAndConditions() {
        if ($(".checkout-agreements input.agreement_enable").is(":checked")) {
            $('#terms-and-conditions-error').hide();
            return true;
        } else {
            $('#terms-and-conditions-error').show();
            return false;
        }
    }
    /**
     * Address Validation API Call
     * @param  string requestUrl
     * @param array data
     * @param function callback
     */
    function getAddress(requestUrl, data, shipHere = false, callback) {
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
                if (typeof data !== 'undefined' && data !== null && data.length !== 0 && data.hasOwnProperty("output") && data.output.hasOwnProperty("resolvedAddresses")) {
                    callback(data);
                } else {
                    if (data.hasOwnProperty("errors")) {
                        if(window.e383157Toggle){
                            fxoStorage.set('isAddressValidated', false);
                        }else{
                            localStorage.setItem('isAddressValidated', false);
                        }
                        $('.error-container').removeClass('api-error-hide');
                        $('.message-container').html('System error, Please try again. <br> '+ data.errors);
                    } else {
                        if(window.e383157Toggle){
                            fxoStorage.set('isAddressValidated', true);
                        }else{
                            localStorage.setItem('isAddressValidated', true);
                        }
                        window.addressValidated = true;
                        if ($('.shipping-address-item').length && shipHere) {
                            selectShippingAddress(quote.shippingAddress());
                        } else {
                            $('#get-Shipping-result').click();
                        }
                    }
                }
            },
            error: function(data) {
                if(window.e383157Toggle){
                    fxoStorage.set('isAddressValidated', true);
                }else{
                    localStorage.setItem('isAddressValidated', true);
                }
                if ($('.shipping-address-item').length && !shipHere) {
                    selectShippingAddress(quote.shippingAddress());
                }
            }
        });
    }

    /**
     * Address Validation Modal
     * @return boolean
     */
    function openAddressValidationModal() {
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
                        let validatedAddress;
                        if (window.e383157Toggle) {
                            fxoStorage.set('isAddressValidated', true);
                            validatedAddress = fxoStorage.get("validatedAddress");
                        } else {
                            localStorage.setItem('isAddressValidated', true);
                            validatedAddress = localStorage.getItem("validatedAddress");
                            validatedAddress = JSON.parse(validatedAddress);
                        }
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
                                        $('input[name="street[0]"]').val(streetLines).trigger("change");
                                        $('input[name="city"]').val(city).trigger("change");
                                        $('input[name="postcode"]').val(postalcode).trigger("change");
                                        if ($('.shipping-address-item').length) {
                                            quote.shippingAddress()['street'] = [streetLines];
                                            quote.shippingAddress()['city'] = city;
                                            quote.shippingAddress()['postcode'] = postalcode;
                                        }
                                    });
                                }
                            } else {
                                let shippingFormAddress;
                                if (window.e383157Toggle) {
                                    shippingFormAddress = fxoStorage.get("shippingFormAddress");
                                } else {
                                    shippingFormAddress = localStorage.getItem("shippingFormAddress");
                                    shippingFormAddress = JSON.parse(shippingFormAddress);
                                }
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
            let validatedAddress = null;
            if (window.e383157Toggle) {
                validatedAddress = fxoStorage.get("validatedAddress");
            } else {
                validatedAddress = localStorage.getItem("validatedAddress");
                validatedAddress = JSON.parse(validatedAddress);
            }
            let recommendedOptionHtml = '';
            let regionCode = $("select[name=region_id] option:selected").text();
            if (validatedAddress != null && typeof validatedAddress.output != 'undefined') {
                let resolvedAddresses = validatedAddress.output.resolvedAddresses;
                if (resolvedAddresses.length) {
                    resolvedAddresses.forEach(function (item, index) {
                        let streetLines = item.streetLinesToken[0];
                        let streetLines2 = item.streetLinesToken[1] !== undefined ? ' ' + item.streetLinesToken[1] : '';
                        let city = item.cityToken[0].value;
                        let postalcode = item.postalCodeToken.value;
                        let countryCode = item.countryCode;
                        recommendedOptionHtml = '<input type="radio" name="validated-address" id="recommend-address" value="recommend" tabindex="-1"><label for="recommend-address">Recommended Address</label><br>';
                        recommendedOptionHtml += '<div class="address">' + streetLines + streetLines2 + '<br>' + city + ', ' + regionCode + ' ' + postalcode + '</div>';
                        $('.recommended-validated-addresses').html(recommendedOptionHtml);
                    });
                }
            }

            let shippingFormAddress = null;
            if (window.e383157Toggle) {
                shippingFormAddress = fxoStorage.get("shippingFormAddress");
            } else {
                shippingFormAddress = localStorage.getItem("shippingFormAddress");
                shippingFormAddress = JSON.parse(shippingFormAddress);
            }
            let originalOptionHtml = '';
            let streetLines = shippingFormAddress.street[0] + ' ' + shippingFormAddress.street[1];
            let city = shippingFormAddress.city;
            let postalcode = shippingFormAddress.postcode;
            originalOptionHtml = '<input type="radio" name="validated-address" id="original-address" value="original" tabindex="-1"><label for="original-address">Original Address</label><br>';
            originalOptionHtml += '<div class="address">' + streetLines + '<br>' + city + ', ' + regionCode + ' ' + postalcode + '</div>';
            $('.original-validated-addresses').html(originalOptionHtml);
            $("input[name='validated-address']").on('click', function () {
                $('.select-validated-address').attr("disabled", false);
            });
            $("input[name='validated-address']").on('keypress', function (event) {
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
            if (window.e383157Toggle) {
                fxoStorage.set('isAddressValidated', true);
            } else {
                localStorage.setItem('isAddressValidated', true);
            }
            if ($('.shipping-address-item').length) {
                selectShippingAddress(quote.shippingAddress());
            } else {
                window.addressValidated = true;
                $('#get-Shipping-result').click();
            }
        });
        $(".address-validation-info").modal("openModal");

        return true;
    }

    /**
     * getGoogleSuggestedShippingAddress
     *
     * @return array
     */
    function getGoogleSuggestedShippingAddress() {
        let data = [];
        let city = null,
            stateCode = null,
            pinCode = null,
            streetLine1 = null,
            streetLine2 = null,
            regionId = null;
        city = $('input[name="city"]').val();
        pinCode = $('input[name="postcode"]').val();
        streetLine1 = $('input[name="street[0]"]').val();
        streetLine2 = $('input[name="street[1]"]').val();
        stateCode = $("select[name=region_id] option:selected").text();
        regionId = $("select[name=region_id] option:selected").val();
        data = {
                zipcode: pinCode,
                city: city,
                stateCode : stateCode,
                regionId : regionId,
                streetLines: [streetLine1,streetLine2]
            };
        return data;
    }


    function getValidFormData(shippingFormData, googleSuggestedAddress) {
        if(typeof googleSuggestedAddress != 'undefined' && googleSuggestedAddress != null && shippingFormData != null) {
            shippingFormData.city = googleSuggestedAddress.city;
            shippingFormData.postcode = googleSuggestedAddress.zipcode;
            shippingFormData.street = googleSuggestedAddress.streetLines;
            shippingFormData.region = googleSuggestedAddress.stateCode;
            shippingFormData.region_id = googleSuggestedAddress.regionId;
        }
        if ($('.shipping-address-item').length) {
            var shippingFormData = new Object();
            var shippingAddress = quote.shippingAddress();
            shippingFormData.city = shippingAddress.city;
            shippingFormData.postcode = shippingAddress.postcode;
            shippingFormData.street = [shippingAddress.street[0],''];
            shippingFormData.region = shippingAddress.regionCode;
            shippingFormData.region_id = shippingAddress.regionId;
            shippingFormData.firstname = shippingAddress.firstname;
            shippingFormData.lastname = shippingAddress.lastname;
            shippingFormData.telephone = shippingAddress.telephone;
        }
        return shippingFormData;
    }

    function priceFormatWithCurrency(price) {
        let formattedPrice = '';
        if (typeof (price) == 'string') {
            formattedPrice = price.replaceAll('$', '').replaceAll(',', '').replaceAll('(', '').replaceAll(')', '');
            formattedPrice = priceUtils.formatPrice(formattedPrice, quote.getPriceFormat());
        } else {
            formattedPrice = priceUtils.formatPrice(price, quote.getPriceFormat());
        }
        return formattedPrice;
    }

    /**
    * Trigger remove unfinished project popup
    */
    $(document).on('keypress', '#unfinishedPopupClose', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if (keycode  == 13 || keycode  == 32) {
            $('#unfinishedPopupClose').trigger('click');
        }
    });

    $(document).on("click", "#unfinishedPopupClose", function (f) {
        $(".megamenu-primary-menu .cart-alertbox.unfinished-project-popup").hide();
    });

    //B-2011817 :: POD2.0: Implement google suggestion in Checkout page for shipping form
    $(document).on('keyup keypress', 'div[name="shippingAddress.street.0"] .input-text', function (e) {
        currentScrollPositionShippingAddressLine = window.scrollY;
        $('div[name="shippingAddress.street.0"] .input-text').attr("autocomplete", "off");
        if ($(this).data('key') !== e.which || e.which === 8) {
            let streetValue = null;
            var inputDiv = $('div[name="shippingAddress.street.0"]');
            var div = $('<div id="geocoder-results-shipping" class="google-maps-main"></div>');
            inputDiv.append(div);
            let input = document.querySelector('div[name="shippingAddress.street.0"]').getElementsByTagName('input')[0];
            input.addEventListener('input', function () {
                streetValue = this.value;
                if (streetValue.length >= 2) {
                    $('#geocoder-results-shipping').show();
                    googlePlacesApi.loadAutocompleteServiceShippingAddress(streetValue);
                } else {
                    $('#geocoder-results-shipping').hide();
                    googlePlacesApi.resetGeoCoderResults();
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

    $(document).on('click', '#co-shipping-form', function (e) {
        $('#geocoder-results-shipping').hide();
    });

     //Tab accessible for Shipping form google suggestion
     $(document).on('keydown', 'div[name="shippingAddress.street.0"] .input-text', function (event) {
         if (event.which === 9) {
             if ($('.google-maps-main .result-wrapper-shipping-address-suggestions').length > 0) {
                 event.preventDefault();
                 $('.result-wrapper-shipping-address-suggestions .result').attr('tabindex', '1').focus();
                 var tabIndex = 1;
                 isFocusInFromGoggleSuggestions = true;
                 $('.google-maps-main .result-wrapper-shipping-address-suggestions .result').each(function () {
                     $(this).attr('tabindex', tabIndex++);
                 });
                 $('.google-maps-main .result-wrapper-shipping-address-suggestions .result:first').focus()
             }
             if ($('.google-maps-main').css('display') === 'none') {
                 event.preventDefault();
                 $('input[name="street[1]"]').focus();
             }
         }
     });

    $(document).on('click keypress', '.result-wrapper-shipping-address-suggestions', function (e) {
        if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
            var description = $(this).find('.result').text();
            let geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': description}, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    let address = results[0].address_components;
                    googlePlacesApi.setShippingAddressOnAutoComplete(address);
                }
                $('div[name="shippingAddress.street.1"] .input-text').focus();
                $('.google-maps-main').hide();
            });
        }
    });

    // scroll fix on street 2 field
    if(maegeeks_pobox_validation){
           $(document).on('focus', 'div[name="shippingAddress.street.1"] .input-text', function (event) {
            if (isFocusInFromGoggleSuggestions) {
            setTimeout(function () {
                window.scrollTo(0, currentScrollPositionShippingAddressLine);
            }, 100);
        }
    });
    $(document).on('focusout', 'div[name="shippingAddress.street.0"] .input-text', function (event) {
        setTimeout(function(){
        let street1 = uiRegistry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.0');
        let value= street1.value();
        let poboxpattern1 = /\bP(ost|ostal)?([ \.\-]*(O|0)(ffice)?)?([ \.\-]*(box|bx|bo|b))\b/i;
        let poboxpattern2 = /\bpostal[ \.]*office\b/i;
        if (poboxpattern1.test(value) || poboxpattern2.test(value)) {
            street1.error("PO boxes not allowed");
        }
    },300)});

    $(document).on('focusout', 'div[name="shippingAddress.street.1"] .input-text', function (event) {
        let street2 = uiRegistry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.1');
        let value= street2.value();
        let poboxpattern1 = /\bP(ost|ostal)?([ \.\-]*(O|0)(ffice)?)?([ \.\-]*(box|bx|bo|b))\b/i;
        let poboxpattern2 = /\bpostal[ \.]*office\b/i;
        if (poboxpattern1.test(value) || poboxpattern2.test(value)) {
            street2.error("PO boxes not allowed");
        }
    });
} else{
            // To prevent autosrolling while using Tab
        $(document).on('focus', 'div[name="shippingAddress.street.1"] .input-text', function (event) {
            setTimeout(function () {
                window.scrollTo(0, currentScrollPositionShippingAddressLine);
            }, 100);
        });}

    $(document).ready(function() {
        $(document).on("click", function (event) {
            let container = $('.address-one-container');
            let geocoderResults = $('#geocoder-results-billing.google-suggestion');
            if (!container.is(event.target) && container.has(event.target).length === 0) {
                geocoderResults.hide();
            }
        });
    });

    $(document).on('keyup keypress', '.address-one-container input', function (e) {
        if ($(this).data('key') !== e.which || e.which === 8) {
            let streetValue = null;
            let input = document.querySelector('.address-one-container').getElementsByTagName('input')[0];
            input.addEventListener('input', function () {
                streetValue = this.value;
                if (streetValue.length >= 2) {
                    googlePlacesApi.loadAutocompleteServiceBillingAddress(streetValue);
                    $('#geocoder-results-billing.google-suggestion').show();
                } else {
                    $('#geocoder-results-billing.google-suggestion').hide();
                    googlePlacesApi.resetBillingResults();
                }
            });
            $(this).data('key', e.which);
        }
    });

    //D-174773 After selecting pickup flow, auto populated address dropdown is not getting closed automatically
    $(document).on("mouseup", function (e) {
        var input = $(".zipcodePickup");
        var result = $(".result-wrapper .result");
        if (explorersD174773Fix && !input.is(e.target) && input.has(e.target).length === 0 && !result.is(e.target) && result.has(e.target).length === 0) {
            googlePlacesApi.resetResults();
        }
    });

    //Tab accessible for Billing form google suggestion
    $(document).ready(function () {
        $(document).on('keydown', '.address-one', function (event) {
            if (event.which === 9) {
                if ($('.google-suggestion .result-wrapper').length > 0) {
                    event.preventDefault();
                    $('.result-wrapper .result').attr('tabindex', '1').focus();
                    var tabIndex = 1;
                    $('.google-suggestion .result-wrapper .result').each(function () {
                        $(this).attr('tabindex', tabIndex++);
                    });
                    $('.google-suggestion .result-wrapper .result:first').focus();
                }
                if ($('.google-suggestion').css('display') === 'none') {
                    setTimeout(function () {
                        $('#address-two').focus();
                    }, 100);

                }
            }
        });
    });

    $(document).on('click keypress', '.result-wrapper', function (e) {
        // Disable legacy events when new component is initialized
        if ($('.new-pickup-selector').length > 0) {
            $(document).off('click keypress', '.result-wrapper');
            return;
        }
        if (e.type === "click" || (e.type === "keypress" && (e.which === 13 || e.which === 32))) {
            var description = $(this).find('.result').text();
            let geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': description}, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    let address = results[0].address_components;
                    googlePlacesApi.setAddressInMapForAutoCompleteBillingForm(address);
                }
                $('.address-two').focus();
                $('.google-suggestion').hide();
            });
        }
    });

    $(document).on('focus', '.address-two', function (e) {
        const currentScrollPosition = window.scrollY;
        setTimeout(function () {
            window.scrollTo(0, 1097);
        }, 100);
    });

    return {
        isSdeStore: isSdeStore,
        continueToPaymentButtonHandler: continueToPaymentButtonHandler,
        getDefaultShippingAccountNumber: getDefaultShippingAccountNumber,
        maskShippingAccountNumber: maskShippingAccountNumber,
        applyDefaultShippingAccountNumber: applyDefaultShippingAccountNumber,
        handleEstimatedShippingTotal: handleEstimatedShippingTotal,
        getCompanyFedExAccountNumber: getCompanyFedExAccountNumber,
        hasAgreedToTermsAndConditions: hasAgreedToTermsAndConditions,
        autoPopulateShippingAccountNumber: autoPopulateShippingAccountNumber,
        onloadMaskShippingAccountNumber: onloadMaskShippingAccountNumber,
        shipInfoValidate: shipInfoValidate,
        specialCharacterRestriction: specialCharacterRestriction,
        getAddress: getAddress,
        openAddressValidationModal: openAddressValidationModal,
        getGoogleSuggestedShippingAddress: getGoogleSuggestedShippingAddress,
        priceFormatWithCurrency: priceFormatWithCurrency,
        getValidFormData: getValidFormData,
        isCommercialCustomer: isCommercialCustomer,
    }
})
