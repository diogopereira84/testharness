/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    "mage/url",
    "Fedex_Pay/js/view/ans1",
    "Fedex_Pay/js/view/rsaes-oaep",
    "Fedex_Recaptcha/js/reCaptcha",
    "cardValidator",
    "fedex/storage"
    ],
    function(
        $,
        url,
        ans,
        rsa,
        reCaptcha,
        cardValidator,
        fxoStorage
    ){
    "use strict";

    if (typeof(window.validateFdxLogin) != 'undefined' && window.validateFdxLogin == '') {
        $(".err-msg").show();
        $(".err-msg .message").html('System error, Please try again.</span>');
    }

    $(".box-add-new-card .box-title a").on('click', function(e) {
        getEncryptionKey();
        e.preventDefault();
        let _this = this;
        $(_this).parent(".box-title").parent(".box-add-new-card").next(".box-credit-card-form-container").show();
        $(_this).parent(".box-title").parent(".box-add-new-card").hide();
        if($("#save_status").val() == 2){
            $("#save_status").val('');
        }
        $(".err-msg").hide();
        $(".succ-msg").hide();
        $(".msg-credit-card").hide();
        $(".msg").hide();
    });
    $(".btn-cancel").on('click', function() {
        let _this = this;
        $(_this).parent(".btn-cancel-container").parent(".btns-container").parent("#cc-form").trigger("reset");
        $(_this).parent(".btn-cancel-container").parent(".btns-container").parent("#cc-form").parent(".box-credit-card-form-container").hide();
        $(_this).parent(".btn-cancel-container").parent(".btns-container").parent("#cc-form").parent(".box-credit-card-form-container").prev(".box-add-new-card").show();
        $(".err-msg").hide();
        $(".succ-msg").hide();
        $(".check-icon").hide();
        $(".img-card").attr("src", window.mediaPath + "/Generic.png");
        $(".img-card").css({"opacity" : 0.5});
        $(".cc-form .btn-submit").attr("disabled", "disabled");
        $(".expiration-month").addClass("select-empty");
        $(".expiration-year").addClass("select-empty");
        $("#state").addClass("select-empty");
        $(".profile_credit_card_id").val();

        $(".second-address-line-link").show();
        $(".input-container-toogle").hide();
        $(".error").html('');
        $("input, select").removeClass('error-text select-empty error-year-text');
        $('#edit_status').val('');
        if ($("#credit-card-count").val() == 'true') {
            $("#set_as_default").prop("disabled", false);
            $(".checkmark-container .checkmark").removeClass("disabled");
            $("#set_as_default").prop("checked", false);
        }
        let saveStatus = $("#save_status").val();
        if (saveStatus == 1)  {
            $('html, body').animate({
                scrollTop: $(".current-edit").offset().top
            }, 500);
            $(".profile_credit_card_id").val("");
            $("#save_status").val("");
        } else if (saveStatus == '') {
            $('html, body').animate({
                scrollTop: $(".stored-credit-cards").offset().top
            }, 500);
        }
    });
    $("#is_term_and_conditions").on('click', function(e) {
        if ($("#is_term_and_conditions").is(":checked")) {
            $(".error.set-default").html("");
        }
    });
    $(".second-address-line-link a").on('click', function(e) {
        e.preventDefault();
        let _this = this;
        $(_this).parent(".box-title").parent(".second-address-line-link").next(".input-container").show();
        $(_this).parent(".box-title").parent(".second-address-line-link").hide();
    });
    let month = $(".expiration-month").val();
    if (month == null){
        $(".expiration-month").addClass("select-empty");
    }
    let year = $(".expiration-year").val();
    if (year == ''){
        $(".expiration-year").addClass("select-empty");
    }
    let state = $("#state").val();
    if (state == ''){
        $("#state").addClass("select-empty");
    }
    $("#cc-form select").on('change', function() {
        let _this = this;
        let SelectValue = $(_this).val();
        if(SelectValue == '' || SelectValue == null) {
            $(_this).addClass("select-empty");
        } else {
            $(_this).addClass("selected").removeClass("select-empty");
        }
    });
    $(document).on('keyup blur', '#name_card', function () {
        const _this = this;
        let nameOnCard = $(_this).val();
            const errorMessage = cardValidator.cardNameValidator(nameOnCard)

            if (errorMessage) {
                $(_this).next(".error").html(`<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> ${errorMessage} </span>`);
                $(_this).addClass("error-text");
            } else {
                $(_this).next(".error").html("");
                $(_this).removeClass("error-text");
            }
        enableOrDisableButton();
    });
    $(document).on('keyup blur', '#nick_name', function () {
        $(".error.nick-name").html("");
    });
    $(document).on('keyup blur', '#nickname', function () {
        $(".error.account-nick-name").html("");
    });
    $(document).on('keyup blur', '#card_number', function () {
        let _this = this;
        let cardNumber = $(_this).val();
        let card = cardNumber.replaceAll(' ', '');
        if (cardNumber == '') {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Please enter a card number.</span>');
            $(_this).addClass("error-text");
            $(".check-icon").hide();
        } else if (card.length > 13) {
            $(_this).next(".error").html("");
            $(_this).removeClass("error-text");
        } else if(card.length < 13) {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> The card number length is invalid. Please check that it is 16-digits.</span>');
            $(_this).addClass("error-text");
            $(".check-icon").hide();
        }
        enableOrDisableButton();
    });
    $(document).on('keypress change', '#card_number', function () {
        let _this = this;
        let cardNumber = $(_this).val();
        if (cardNumber[0] == "4") {
            $(".img-card").attr("src", window.mediaPath + "/Visa.png");
        } else if (cardNumber[0] == "5") {
            $(".img-card").attr("src", window.mediaPath + "/MasterCard.png");
        } else if ((cardNumber[0] == "3" && cardNumber[1] == "4") || (cardNumber[0] == "3" && cardNumber[1] == "7")) {
            $(".img-card").attr("src", window.mediaPath + "/Amex.png");
        } else if (cardNumber[0] == "6") {
            $(".img-card").attr("src", window.mediaPath + "/Discover.png");
        } else if (cardNumber[0] == "3" && cardNumber[1] == "8" || (cardNumber[0] == "3" && cardNumber[1] == "6")) {
            $(".img-card").attr("src", window.mediaPath + "/Diners-Club.png");
        } else {
            $(".img-card").attr("src", window.mediaPath + "/Generic.png");
        }
        $(_this).val(function (index, value) {
            return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
        });
    });
    $(document).on(' blur', '#card_number', function () {
        let _this = this;
        let cardNumber = $(_this).val();
        let card = cardNumber.replaceAll(' ', '');

        if(window.e383157Toggle){
            fxoStorage.set("cardNumber", cardNumber);
        }else{
            localStorage.setItem("cardNumber", cardNumber);
        }

        if (card.length > 13) {
            if (!window.d196604_toggle) { //removing on this toggle
                let masked = "*" + card.substr(-4);
                $(_this).val(masked);
            }
            $(".check-icon").show();
        }
    });
    $(document).on('paste', '#card_number', function (e) {
        e.preventDefault();
    });
    $(document).on('keypress', '#card_number, #cvv, #zipcode', function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }
    });
    $(document).on('focus', '#card_number', function () {
        let _this = this;
        let cardNumber = $(_this).val();
        let cardNumberVal;
        if (cardNumber.length > 0) {
            if(window.e383157Toggle){
                cardNumberVal = fxoStorage.get("cardNumber");
            }else{
                cardNumberVal = localStorage.getItem("cardNumber");
            }
            $(_this).val(cardNumberVal);

        }
        $(".img-card").css({"opacity":"1.0"});
    });
    $(document).on('focusout', '#card_number', function () {
        let _this = this;
        let cardNumber = $(_this).val();
        if(cardNumber == ''){
            $(".img-card").css({"opacity":"0.5"});
        }
    });
    $(document).on('change', '#expiration_month, #expiration_year', function () {
        let _this = this;
        let expirationMonth = parseInt($("#expiration_month").val());
        let expirationYear = parseInt($("#expiration_year").val());

        let date = new Date();
        let currYear = date.getFullYear();
        let currMonth = date.getMonth() + 1;
        if (expirationMonth < currMonth && expirationYear == currYear) {
            $("#expiration_month").next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Date occurs in the past.</span>');
            $("#expiration_month").addClass("error-text");
        } else {
            $("#expiration_month").next(".error").html("");
            $("#expiration_month").removeClass("error-text");
            $("#expiration_year").removeClass("error-year-text");
        }
        enableOrDisableButton();
    });
    $(document).on('keyup blur', '#cvv', function () {
        let _this = this;
        let nameOnCard = $(_this).val();
        if (nameOnCard == '') {
            $(_this).addClass("error-text");
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Please enter CVV.</span>');
        } else {
            $(_this).removeClass("error-text");
            $(_this).next(".error").html('');
        }
        enableOrDisableButton();
    });
    $(document).on('keyup blur', '#company_name', function () {
        let _this = this;
        let nameOnCard = $(_this).val();
        if(nameOnCard.length == 1) {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Must be more than 1 character</span>');
            $(_this).addClass("error-text");
        } else {
            $(_this).next(".error").html("");
            $(_this).removeClass("error-text");
        }
        enableOrDisableButton();
    });
    $(document).on('keyup blur', '#address_line_one', function () {
        let _this = this;
        let nameOnCard = $(_this).val();
        if(nameOnCard == ''){
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Address is required.</span>');
            $(_this).addClass("error-text");
        } else {
            $(_this).next(".error").html("");
            $(_this).removeClass("error-text");
        }
        enableOrDisableButton();
    });
    $(document).on('keyup blur', '#zipcode', function () {
        let _this = this;
        let nameOnCard = $(_this).val();
        if (nameOnCard == '') {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Zip Code is required.</span>');
            $(_this).addClass("error-text");
        } else if (nameOnCard.length < 5) {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Zip code must be at least 5 numbers.</span>');
            $(_this).addClass("error-text");
        } else {
            $(_this).next(".error").html("");
            $(_this).removeClass("error-text");
        }
        enableOrDisableButton();
    });
    $(document).on('keyup blur', '#city', function () {
        let _this = this;
        let nameOnCard = $(_this).val();
        if (nameOnCard == '') {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> City is required.</span>');
            $(_this).addClass("error-text");
        } else if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/.test(nameOnCard)) {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> City must be letters only.</span>');
            $(_this).addClass("error-text");
        } else {
            $(_this).next(".error").html("");
            $(_this).removeClass("error-text");
        }
        enableOrDisableButton();
    });
    $(document).on('keypress', '#city', function (event) {
        let inputValue = event.which;
        if (!(inputValue >= 65 && inputValue <= 122) && (inputValue != 32 && inputValue != 0)) {
            event.preventDefault();
        }
    });
    $(document).on('change', '#state', function () {
        let _this = this;
        let nameOnCard = $(_this).val();
        if (nameOnCard == '') {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> State is required.</span>');
            $(_this).addClass("error-text");
        } else {
            $(_this).next(".error").html("");
            $(_this).removeClass("error-text");
        }
        enableOrDisableButton();
    });

    function enableOrDisableButton() {
        let nameOnCard = $("#name_card").val();
        let cardNumber = $("#card_number").val();
        let expirationMonth = $("#expiration_month").val();
        let expirationYear = $("#expiration_year").val();
        let cvv = $("#cvv").val();
        let country = $("#country").val();
        let addressLineOne = $("#address_line_one").val();
        let zipcode = $("#zipcode").val();
        let city = $("#city").val();
        let state = $("#state").val();

        if (nameOnCard == '' || cardNumber == '' || expirationMonth == '' || expirationYear == '' || cvv == '' || country == '' || addressLineOne == '' || zipcode == '' || city == '' || state == '' || $(".error-text").length > 0) {
            $(".cc-form .btn-submit").attr("disabled", "disabled");
        } else {
            $(".cc-form .btn-submit").removeAttr("disabled");
        }
    }

    $(document).on('click', '.payment-method-save-changes', function () {
        let _this = this;
        let paymentMethod = $(_this).parent(".save-changes-container").parent(".payment-method-container").children(".first-cocus").children(".custom-radio-btn").children(".payment-type").val();
        let paymentStatus = $(_this).parent(".save-changes-container").parent(".payment-method-container").children(".first-cocus").children(".custom-radio-btn").children(".payment-type").is(':checked');
        if (paymentStatus) {
            let ajaxUrl =  url.build('customer/account/preferredpaymentmethod');
            $.ajax({
                type: "POST",
                url: ajaxUrl,
                data: {userProfileId: window.userProfileId, paymentMethod: paymentMethod},
                cache: false,
                showLoader: true,
                success: function (data) {
                    if (data.Failure || data.errors) {
                        if(paymentMethod == 'CREDIT_CARD') {
                            $(".succ-msg").hide();
                            $(".err-msg").hide();
                            $(".msg-credit-card").show();
                            $(".msg-credit-card").html('<span class="failure"><i class="fa fa-times"></i> System error, Please try again.</span>');
                            $(".msg").hide();
                        } else if(paymentMethod == 'ACCOUNT') {
                            $(".succ-msg").hide();
                            $(".err-msg").hide();
                            $(".msg").show();
                            $(".msg").html('<span class="failure"><i class="fa fa-times"></i> System error, Please try again.</span>');
                            $(".msg-credit-card").hide();
                        }
                    } else {
                        if(paymentMethod == 'CREDIT_CARD') {
                            $(".succ-msg").hide();
                            $(".err-msg").hide();
                            $(".msg-credit-card").show();
                            $(".msg-credit-card").html('<span class="success"><i class="fa fa-check"></i> Your preferred payment method has been saved.</span>');
                            $(".payment-method-save-changes").removeAttr("disabled");
                            $(_this).attr("disabled", "disabled");
                            $(".msg").hide();
                        } else if(paymentMethod == 'ACCOUNT') {
                            $(".succ-msg").hide();
                            $(".err-msg").hide();
                            $(".msg").show();
                            $(".msg").html('<span class="success"><i class="fa fa-check"></i> Your preferred payment method has been saved.</span>');
                            $(".payment-method-save-changes").removeAttr("disabled");
                            $(_this).attr("disabled", "disabled");
                            $(".msg-credit-card").hide();
                        }
                    }
                }
            });
        } else {
            $(".succ-msg").hide();
            $(".err-msg").hide();
        }
    });

    /* Credit Card Save & Edit Functionality */
    $(document).on('click', '.action .action-edit', function () {
        getEncryptionKey();
        $("#cc-form").trigger("reset");
        $(".msg-credit-card").hide();
        $(".msg").hide();
        let editInfoUrl = url.build('customer/creditcard/editinfo');
        let cardId = $(this).attr('data-cardId');
        let isPrimary = $(this).children().attr('data-primary');
        let profileCreditCardId = $(this).attr('data-profilecreditid');
        if (isPrimary) {
            $("#set_as_default").prop("disabled", true);
            $(".checkmark-container .checkmark").addClass("disabled");
            $("#set_as_default").prop("checked", true);
        } else {
            $("#set_as_default").prop("disabled", false);
            $(".checkmark-container .checkmark").removeClass("disabled");
            $("#set_as_default").prop("checked", false);
        }
        $(".credit-cart-content").removeClass('current-edit');
        $(this).parent().parent().parent().parent().addClass('current-edit');
        $.ajax({
            url: editInfoUrl,
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: {
                cardId: cardId
            },
            success: function (data) {
                if(data.status === 'error') {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text(data.message);
                    $(".err-msg").show();
                    $(".img-close-msg").trigger('focus');
                } else {
                    let cardInfo = data.cardInfo;
                    let billingAddress = cardInfo.billingAddress;
                    $("#name_card").val(cardInfo.cardHolderName);
                    if(window.e383157Toggle){
                        fxoStorage.set("cardNumber", '');
                    }else{
                        localStorage.setItem("cardNumber", '');
                    }
                    let creditCardNumber = cardInfo.maskedCreditCardNumber;
                    let masked = "*" + creditCardNumber.substr(-4);
                    $(".error").html('');
                    $("input, select").removeClass('error-text select-empty error-year-text');
                    $("#card_number").val(masked);
                    $("#expiration_month").val(cardInfo.expirationMonth);
                    $("#expiration_year").val(cardInfo.expirationYear);
                    $("#cvv").val('');
                    $(".check-icon").hide();
                    $("#nick_name").val(cardInfo.creditCardLabel);
                    $("#company_name").val(billingAddress.company.name);
                    $("#address_line_one").val(billingAddress.streetLines[0]);
                    if (typeof(billingAddress.streetLines[1]) != 'undefined') {
                        if (billingAddress.streetLines[1].trim()) {
                            $("#address_line_two").val(billingAddress.streetLines[1]);
                            $(".add-address-line-two").trigger("click");
                        } else {
                            $(".second-address-line-link").show();
                            $(".input-container-toogle").hide();
                            $("#address_line_two").val('');
                        }
                    }
                    $("#zipcode").val(billingAddress.postalCode);
                    $("#city").val(billingAddress.city);
                    $("#state").val(billingAddress.stateOrProvinceCode);
                    $("#save_status").val('1');
                    $("#profile_credit_card_id").val(profileCreditCardId);
                    $('#edit_status').val('');
                    $(".box-add-new-card .box-title a").trigger("click");
                    $('html, body').animate({
                        scrollTop: $("#cc-form").offset().top
                    }, 500);
                }
            }
        });
    });

    $(document).on('click', '#card_number, #cvv, #expiration_year, #expiration_month', function () {
        let saveStatus = $("#save_status").val();
        let maskedCreditCardNumber;
        if(window.e383157Toggle){
            maskedCreditCardNumber = fxoStorage.get("cardNumber");
        }else{
            maskedCreditCardNumber = localStorage.getItem("cardNumber");
        }
        let editStatus = $('#edit_status').val();
        if (saveStatus && maskedCreditCardNumber == '' && editStatus == '') {
            $('#expiration_year').val('empty');
            $('#expiration_month').val('empty');
            $('#card_number').val('');
            $('#expiration_month').trigger('change');
            $('#expiration_year').trigger('change');
            $("#expiration_month").val('');
            $("#expiration_year").val('');
            $('#card_number').trigger('blur');
            $('#cvv').trigger('blur');
            $('#edit_status').val('1');
        }
    });

    $(document).on('keyup blur', '#expiration_month, #expiration_year', function () {
            let expirationMonth = parseInt($("#expiration_month").val());
            let expirationYear = parseInt($("#expiration_year").val());

            let date = new Date();
            let currYear = date.getFullYear();
            let currMonth = date.getMonth() + 1;
            if (isNaN(expirationMonth) || isNaN(expirationYear) || expirationMonth === 0 || expirationYear === 0) {
                $("#expiration_month").next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">Please enter a valid expiration date.</span>');
                $("#expiration_month, #expiration_year").addClass("error-text");
            } else if (expirationMonth < currMonth && expirationYear == currYear) {
                $("#expiration_month").next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">Date occurs in the past.</span>');
                $("#expiration_month, #expiration_year").addClass("error-text");
            }
            else {
                $("#expiration_month").next(".error").empty();
                $("#expiration_month, #expiration_year").removeClass("error-text");
            }
            enableOrDisableButton();
    });

    $(document).on('click', '.btn-submit-container .btn-submit', async function () {
        $(".error.nick-name").html("");
        if (!$("#is_term_and_conditions").is(":checked")) {
            $(".error.set-default").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">Please accept the terms and conditions.</span>');
            return false;
        } else {
            $(".error.set-default").html("");
        }
        let cardHolderName = $("#name_card").val();
        let maskedCreditCardNumber;
        if (window.e383157Toggle) {
            maskedCreditCardNumber = fxoStorage.get("cardNumber").replace(/ /g, '');
        } else {
            maskedCreditCardNumber = localStorage.getItem("cardNumber").replace(/ /g, '');
        }
        let lastFiveCreditCard = maskedCreditCardNumber.substr(-5);
        let creditCardLabel = '';
        let isNickName = false;
        if ($("#nick_name").val()) {
            creditCardLabel = $("#nick_name").val();
            isNickName = true;
        } else {
            let lastNo = maskedCreditCardNumber.substr(-5);
            creditCardLabel = getCardType(maskedCreditCardNumber) + '_' + lastNo;
        }
        let creditCardType = getCardType(maskedCreditCardNumber);
        let validationKey = $("#validation_key").val();
        let expirationMonth = $("#expiration_month").val();
        let expirationYear = $("#expiration_year").val();
        let cvv = $("#cvv").val();
        let company = $("#company_name").val();
        let streetLineOne = $("#address_line_one").val();
        let streetLineTowo = $("#address_line_two").val();
        let streetLines = '';
        if (streetLineTowo) {
            streetLines = streetLineOne + '",' + '"'+streetLineTowo;
        } else {
            streetLines = streetLineOne + '",' + '" ';
        }
        let postalCode = $("#zipcode").val();
        let city = $("#city").val();
        let stateOrProvinceCode = $("#state").val();
        let countryCode = $("#country").val();
        let primary = $('#set_as_default').is(':checked');
        let saveStatus = $("#save_status").val();
        let profileCreditCardId = $("#profile_credit_card_id").val();

        let year = expirationYear.substring(2, 4);
        let manualCC = 'M' + maskedCreditCardNumber + '=' + year + expirationMonth + ':' + cvv;
        let pubKey;
        if(window.e383157Toggle){
            pubKey = fxoStorage.get('ccEncryptedKey');
        }else{
            pubKey = localStorage.getItem('ccEncryptedKey');
        }
        if (pubKey) {
            $(".err-msg").hide();
            $(".succ-msg").hide();
            let encryptedCreditCard = fetchEncryptedCreditCard(manualCC, pubKey);
            if (encryptedCreditCard) {
                let recaptchaToken = await reCaptcha.generateRecaptchaToken('profile_cc');
                let saveInfoUrl = url.build('customer/creditcard/saveinfo');
                $.ajax({
                    url: saveInfoUrl,
                    type: "POST",
                    data: {
                        loginValidationKey: validationKey,
                        profileCreditCardId: profileCreditCardId,
                        cardHolderName: cardHolderName,
                        maskedCreditCardNumber: lastFiveCreditCard,
                        creditCardLabel: creditCardLabel,
                        creditCardType: creditCardType,
                        expirationMonth: expirationMonth,
                        expirationYear: expirationYear,
                        company: company,
                        streetLines: streetLines,
                        postalCode: postalCode,
                        city: city,
                        stateOrProvinceCode: stateOrProvinceCode,
                        countryCode: countryCode,
                        primary: primary,
                        saveStatus: saveStatus,
                        encryptedData: encryptedCreditCard,
                        isNickName: isNickName,
                        'g-recaptcha-response': recaptchaToken
                    },
                    dataType: 'json',
                    showLoader: true,
                    success: function (data) {
                        $(".error.nick-name").html("");
                        if (data.status === 'nick_name_status') {
                            $("#nick_name").trigger('focus');
                            $(".error.nick-name").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> This nickname already exists in your profile.</span>');
                        } else if (data.status === 'error') {
                            if (data?.info?.errors[0]?.code == 'SPOS.CREDITCARDTOKEN.200') {
                                $(".err-msg .message").text("Check that your credit card and billing details are accurate and try again.\n Transaction ID: " + data?.info?.transactionId);
                            } else {
                                $(".err-msg .message").text("System error, Please try again.");
                            }
                            $(".succ-msg").hide();
                            $(".err-msg").show();
                            $('html, body').animate({ scrollTop: $(".msg-container").offset().top }, 1000);
                        }
                        else if (data.status === 'recaptcha_error') {
                            $(".succ-msg").hide();
                            $(".err-msg .message").text(data.message);
                            $(".err-msg").show();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 1000);
                        } else {
                            $("#credit-card-count").val('true');
                            if (primary) {
                                let makeAsDefaultHtml = '<div class="cart-status-make-content"><div class="cart-status-make"><span class="default" tabindex="0">Make Default </span></div></div>';
                                $(".cart-status-default-content").parent().html(makeAsDefaultHtml);
                                $(".action-edit").children(".edit").removeAttr("data-primary");
                            }
                            if (data.status == '1') {
                                $(".current-edit").html(data.info);
                            } else {
                                $(".save-credit-card-changes").after(data.info);
                            }
                            if (data.isPayment == true) {
                                $("#credit-cards-option").trigger("click");
                                $(".payment-method-save-changes").removeAttr("disabled");
                                $(".payment-method-save-changes.ceditcard").attr("disabled", "disabled");
                            }
                            $(".err-msg").hide();
                            $(".succ-msg .message").text(data.message);
                            $("#cc-form").trigger("reset");
                            $("#cc-form").parent(".box-credit-card-form-container").hide();
                            $("#cc-form").parent(".box-credit-card-form-container").prev(".box-add-new-card").show();
                            $(".check-icon").hide();
                            $(".img-card").attr("src", window.mediaPath + "/Generic.png");
                            $(".img-card").css({"opacity" : 0.5});
                            $(".cc-form .btn-submit").attr("disabled", "disabled");
                            $(".expiration-month").addClass("select-empty");
                            $(".expiration-year").addClass("select-empty");
                            $("#state").addClass("select-empty");
                            $(".second-address-line-link").show();
                            $(".input-container-toogle").hide();
                            $(".error").html('');
                            $("#save_status").val('');
                            $("#profile_credit_card_id").val('');
                            $('#edit_status').val('');
                            $(".checkmark-container .checkmark").removeClass("disabled");
                            $("#set_as_default").prop("checked", false);
                            $("#set_as_default").prop("disabled", false);
                            $(".succ-msg").show();
                            $('html, body').animate({
                                scrollTop: $(".msg-container").offset().top
                            }, 1000);
                        }
                    }
                });
            } else {
                $(".succ-msg").hide();
                $(".err-msg .message").text("System error, Please try again.");
                $(".err-msg").show();
                $('html, body').animate({
                    scrollTop: $(".msg-container").offset().top
                }, 1000);
            }
        } else {
            $(".succ-msg").hide();
            $(".err-msg .message").text("System error, Please try again.");
            $(".err-msg").show();
            $('html, body').animate({
                scrollTop: $(".msg-container").offset().top
            }, 1000);
        }
    });

    /* Add New Account Functionality */
    $(document).on('click', '.fedex-new-account-form .action.submit', async function () {
        $(".error.account-nick-name").html("");
        $(".success").html("");
        $(".account-error").html("");
        let containerId = $("#container_id").val();
        if (containerId == '') {
            let accountNumber = $("#account-number").val();
            let nickName = '';
            let billingReference = '';
            let isNickName = false;
            if ($("#nickname").val()) {
                nickName = $("#nickname").val();
                isNickName = true;
            } else {
                let lastNo = accountNumber.substr(-4);
                nickName = 'FedEx Account ' + lastNo;
            }
            if ($("#billing-reference").val()) {
                billingReference = $("#billing-reference").val();
            } else {
                billingReference = "NULL";
            }
            let recaptchaToken = await reCaptcha.generateRecaptchaToken('profile_fedex_account');
            let validateAccountUrl = url.build('customer/account/validateaccount');
            $.ajax({
                url: validateAccountUrl,
                type: "POST",
                data: {
                    accountNumber: accountNumber,
                    'g-recaptcha-response': recaptchaToken
                },
                dataType: 'json',
                showLoader: true,
                success: async function (data) {
                    $(".account-error").html("");
                    if (data.status == false) {
                        $(".succ-msg").hide();
                        $(".err-msg").hide();
                        $(".account-error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> The account number entered is invalid.</span>');
                        $(".account-number").trigger('focus');
                    } else if (data.status == 'recaptcha_error') {
                        $(".succ-msg").hide();
                        $(".err-msg").hide();
                        $(".account-error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">' + data.message +'</span>');
                        $(".account-number").trigger('focus');
                    } else {
                        let isPrimary = false;
                        let accountStatus = data.info.account_status.toUpperCase();
                        let accountType = data.info.account_type.toUpperCase();
                        let isFedExAccount = data.isFedExAccount;
                        if (!isFedExAccount && accountStatus == 'ACTIVE' && accountType != 'DISCOUNT') {
                            isPrimary = true;
                        }
                        let recaptchaToken = await reCaptcha.generateRecaptchaToken('checkout_fedex_account');
                        let saveInfoUrl = url.build('customer/account/addnewaccount');
                        $.ajax({
                            url: saveInfoUrl,
                            type: "POST",
                            data: {
                                userProfileId: window.userProfileId,
                                accountNumber: accountNumber,
                                nickName: nickName,
                                billingReference: billingReference,
                                isPrimary: isPrimary,
                                isNickName: isNickName,
                                fromProfile: true,
                                'g-recaptcha-response': recaptchaToken
                            },
                            dataType: 'json',
                            showLoader: true,
                            success: function (data) {
                                if (data.status === 'nick_name_status') {
                                    $("#nickname").trigger('focus');
                                    $(".error.account-nick-name").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> This nickname already exists in your profile.</span>');
                                } else if (data.status == 'recaptcha_error') {
                                    $(".succ-msg").hide();
                                    $(".err-msg").hide();
                                    $(".account-error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">' + data.message +'</span>');
                                    $(".account-number").trigger('focus');
                                } else if ((typeof (data.errors) != 'undefined' || typeof (data.error) != 'undefined') && (data.errors || data.error)) {
                                    $(".succ-msg").hide();
                                    if (data.errors) {
                                        if (data.errors[0].code == 'REQUEST.ACCOUNTNUMBER.ALREADYEXISTS') {
                                            $(".account-error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">This account already exists in your profile.</span>');
                                            $(".account-number").trigger('focus');
                                            return false;
                                        } else {
                                            $(".err-msg .message").text('System error, Please try again.');
                                            console.error('Saving credit card Fedex account error details[' +
                                                saveInfoUrl + ']: ', data.errors);
                                        }
                                    }
                                    if (data.error) {
                                        $(".err-msg .message").text(data.error);
                                    }
                                    $(".err-msg").show();
                                    $(".img-close-msg").trigger('focus');
                                } else {
                                    if (data.status == true) {
                                        $(".fedex-account-container .block-content").prepend(data.info);
                                    }
                                    if (data.isPayment == true) {
                                        $("#fedex-account-option").trigger("click");
                                        $(".payment-method-save-changes").attr("disabled", "disabled");
                                        $(".payment-method-save-changes.ceditcard").removeAttr("disabled");
                                    }
                                    $(".err-msg").hide();
                                    $(".succ-msg").show();
                                    $(".succ-msg .message").text('FedEx account has been successfully added.');
                                    $(".succ-msg").show();
                                    $(".img-close-msg").trigger('focus');
                                    $(".add-new-fedex-account").show();
                                    $(".fedex-new-account-form").hide();
                                    $('#fedex-account-form').trigger("reset");
                                }
                            }
                        });
                    }
                }
            });
        }
    });

    $(document).on('click', '.action .remove', function () {
        $("#save_status").val('2');
        $(".msg-credit-card").hide();
        $(".msg").hide();
        $(".btn-cancel").trigger('click');
        let editInfoUrl = url.build('customer/creditcard/removeinfo');
        let cardId = $(this).attr('data-cardId');
        $(this).parent().parent().parent().parent().addClass('hide-section');
        $('#expiration_month').trigger('change');
        $('#expiration_year').trigger('change');
        $('#expiration_month').val('');
        $('#expiration_year').val('');

        $.ajax({
            url: editInfoUrl,
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: {
                cardId: cardId
            },
            success: function (data) {
                if (data.status === 'error') {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text(data.message);
                    $(".err-msg").show();
                    $(".img-close-msg").trigger('focus');
                } else {
                    $(".hide-section").hide();
                    $(".err-msg").hide();
                    $(".succ-msg").show();
                    $(".succ-msg .message").text(data.message);
                    $(".succ-msg").show();
                    $(".img-close-msg").trigger('focus');
                    if (data.creditCount) {
                        $("#credit-card-count").val('false');
                        $("#set_as_default").prop("disabled", true);
                        $(".checkmark-container .checkmark").addClass("disabled");
                        $("#set_as_default").prop("checked", true);
                    }
                }
            }
        });
    });

    $(document).on('click', '.cart-status-make .default', function () {
        let _this = this;
        $(".msg-credit-card").hide();
        $(".msg").hide();
        $("#save_status").val('2');
        $(".btn-cancel").trigger('click');
        let editInfoUrl = url.build('customer/creditcard/makeasdefault');
        let cardId = $(this).parent().parent().parent().attr('data-cardId');
        $(".head-right").removeClass('card-primary');
        let makeAsDefult = $(this).parent().parent().parent().html();
        $(this).parent().parent().parent().addClass('card-primary');
        let cardStatusDefault = '<div class="cart-status-default-content"><div class="cart-status-default"><span class="default">Default</span></div></div>';
        $.ajax({
            url: editInfoUrl,
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: {
                cardId: cardId
            },
            success: function (data) {
                if(data.status === 'error') {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text(data.message);;
                    $(".err-msg").show();
                    $(".img-close-msg").trigger('focus');
                } else {
                    $(".err-msg").hide();
                    $(".succ-msg").show();
                    $(".succ-msg .message").text(data.message);;
                    $(".succ-msg").show();
                    $(".img-close-msg").trigger('focus');
                    $(".action-edit").children(".edit").removeAttr("data-primary");
                    $(_this).parent(".cart-status-make").parent(".cart-status-make-content").parent(".head-right").parent(".credit-card-head").next(".credit-card-body").children(".credit-card-name").children(".action").children(".action-edit").children(".edit").attr("data-primary", 1);
                    $(".cart-status-default").parent().parent().html(makeAsDefult);
                    $(".card-primary").html(cardStatusDefault);
                }
            }
        });
    });

    /* B-1241756 Start Here */
    $(document).on('click', '.make-default-link', function () {
        let _this = this;
        let profileAccountId = $(_this).attr("data-profile-account-id");
        let accountNumber = $(_this).attr("data-account-number");
        let maskedAccountNumber = $(_this).attr("data-masked-account-number");
        let accountLabel = $(_this).attr("data-account-label");
        let accountType = $(_this).attr("data-account-type");
        let billingReference = $(_this).attr("data-billing-reference");
        let primary = true;
        $('.fedex-new-account-form').hide();
        $('.add-new-fedex-account').show();
        $('#fedex-account-form').trigger("reset");
        $("#container_id").val("");
        let ajaxUrl =  url.build('customer/account/updateaccount');
        if (accountType == 'SHIPPING') {
            ajaxUrl =  url.build('customer/account/makeadefaultshippingaccount');
        }
        $.ajax({
            type: "POST",
            url: ajaxUrl,
            data: {userProfileId: window.userProfileId, profileAccountId: profileAccountId, accountNumber: accountNumber, maskedAccountNumber: maskedAccountNumber, accountLabel: accountLabel, accountType: accountType, billingReference: billingReference, primary: primary},
            cache: false,
            showLoader: true,
            success: function (data) {
                if (data == null) {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $(".img-close-msg").trigger('focus');
                } else {
                    if(data.Failure || data.errors) {
                        $(".succ-msg").hide();
                        $(".err-msg .message").text("System error, Please try again.");
                        $(".err-msg").show();
                        $(".img-close-msg").trigger('focus');
                    } else {
                        if(accountType == 'SHIPPING') {
                            $(".succ-msg").hide();
                            $(".err-msg").hide();
                            $(".shipping-default").html("Make Default");
                            $(".shipping-default").addClass("make-default-link shipping-make-default");
                            $(".shipping-default").removeClass("default shipping-default");
                            $(_this).removeClass("make-default-link shipping-make-default");
                            $(_this).addClass("default shipping-default");
                            $(_this).html("Default");
                            $(".err-msg").hide();
                            $(".succ-msg").show();
                            $(".succ-msg .message").text('Default shipping account has been successfully updated.');
                            $(".succ-msg").show();
                            $(".img-close-msg").trigger('focus');
                        } else if(accountType == 'PRINTING') {
                            $(".succ-msg").hide();
                            $(".err-msg").hide();
                            $(".payment-default").html("Make Default");
                            $(".payment-default").addClass("make-default-link payment-make-default");
                            $(".payment-default").removeClass("default payment-default");
                            $(_this).removeClass("make-default-link payment-make-default");
                            $(_this).addClass("default payment-default");
                            $(_this).html("Default");
                            $(".err-msg").hide();
                            $(".succ-msg").show();
                            $(".succ-msg .message").text('Default FedEx account has been successfully updated.');
                            $(".succ-msg").show();
                            $(".img-close-msg").trigger('focus');
                        }
                    }
                }
            }
        });
    });

    $(document).on('click', '.btn-edit', function () {
        let _this = this;
        let containerId = $(_this).parent(".action-edit").parent(".action-container").parent(".payment-account-info-container").parent(".payment-account-list").attr("id");
        let profileAccountId = $(_this).attr("data-profile-account-id");
        let accountNumber = $(_this).attr("data-masked-account-number");
        let maskedAccountNumber = $(_this).attr("data-masked-account-number");
        let accountLabel = $(_this).attr("data-account-label");
        let accountType = $(_this).attr("data-account-type");
        let billingReference = $(_this).attr("data-billing-reference");
        if (billingReference == "NULL") {
            billingReference = '';
        }
        let primary = false;
        if ($(_this).parent(".action-edit").parent(".action-container").parent(".payment-account-info-container").prev(".payment-account-top").children(".default-container").children(".payment-default").length > 0) {
            let primary = true;
        }
        $(".account-error").html("");
        $("#container_id").val(containerId);
        $("#profile_account_id").val(profileAccountId);
        $("#masked_account_number").val(maskedAccountNumber);
        $("#account_type").val(accountType);
        $("#primary_account").val(primary);
        $("#account-number").val(accountNumber);
        $("#nickname").val(accountLabel);
        $("#billing-reference").val(billingReference);
        $(".new-fedex-account").trigger('click');
        $("#btn_payment_save_changes").addClass("edit_btn_payment_save_changes");
        $("#account-number").attr("disabled", "disabled");
        $(".account-heading").text('Update Account');
        $('html, body').animate({
            scrollTop: $(".fedex-new-account-form").offset().top
        }, 1000);
    });

    $(document).on('click', '#btn_payment_save_changes', function () {
        let containerId = $("#container_id").val();
        let profileAccountId = $("#profile_account_id").val();
        let accountNumber = null;
        if (containerId != '') {
            accountNumber = $("#"+containerId+" .btn-edit").attr('data-account-number');
        }
        let maskedAccountNumber = $("#masked_account_number").val();
        let isNickName = false;
        let accountLabel = "";
        if ($("#nickname").val()) {
            accountLabel = $("#nickname").val();
            isNickName = true;
        } else if (containerId != '') {
            let lastNo = accountNumber.substr(-4);
            accountLabel = 'FedEx Account ' + lastNo;
        }
        let accountType = $("#account_type").val();
        let billingReference = "";
        if ($("#billing-reference").val()) {
            billingReference = $("#billing-reference").val();
        } else {
            billingReference = "NULL";
        }
        let primary = $("#primary_account").val();
        let ajaxUrl =  url.build('customer/account/updateaccount');
        if (containerId != '') {
            $.ajax({
                type: "POST",
                url: ajaxUrl,
                data: {userProfileId: window.userProfileId, profileAccountId: profileAccountId, accountNumber: accountNumber, maskedAccountNumber: maskedAccountNumber, accountLabel: accountLabel, accountType: accountType, billingReference: billingReference, primary: primary, isNickName: isNickName},
                cache: false,
                showLoader: true,
                success: function (data) {
                    $(".error.account-nick-name").html("");
                    if (data.status === 'nick_name_status') {
                        $("#nickname").trigger('focus');
                        $(".error.account-nick-name").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> This nickname already exists in your profile.</span>');
                    } else if (data.Failure || data.errors) {
                        $(".succ-msg").hide();
                        $(".err-msg .message").text("System error, Please try again.");
                        $(".err-msg").show();
                        $(".img-close-msg").trigger('focus');
                    } else {
                        $("#"+containerId).children(".payment-account-top").children(".shipping-account-container").children("h3").text(accountLabel);
                        $("#"+containerId).children(".payment-account-top").children(".shipping-account-container").children(".mask-account").text("Ending in "+maskedAccountNumber);

                        if ($("#"+containerId).children(".payment-account-top").children(".default-container").children(".payment-default").length > 0) {

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".payment-default").attr("data-profile-account-id", profileAccountId);
                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".payment-default").attr("data-account-number", accountNumber);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".payment-default").attr("data-masked-account-number", maskedAccountNumber);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".payment-default").attr("data-account-label", accountLabel);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".payment-default").attr("data-account-type", accountType);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".payment-default").attr("data-billing-reference", billingReference);

                        } else if ($("#"+containerId).children(".payment-account-top").children(".default-container").children(".shipping-make-default").length > 0) {

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".shipping-make-default").attr("data-profile-account-id", profileAccountId);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".shipping-make-default").attr("data-account-number", accountNumber);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".shipping-make-default").attr("data-masked-account-number", maskedAccountNumber);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".shipping-make-default").attr("data-account-label", accountLabel);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".shipping-make-default").attr("data-account-type", accountType);

                            $("#"+containerId).children(".payment-account-top").children(".default-container").children(".shipping-make-default").attr("data-billing-reference", billingReference);

                        }
                        if ($("#"+containerId).children(".payment-account-info-container").children(".action-container").children(".action-edit").children(".btn-edit").length > 0) {

                            $("#"+containerId).children(".payment-account-info-container").children(".action-container").children(".action-edit").children(".btn-edit").attr("data-profile-account-id", profileAccountId);

                            $("#"+containerId).children(".payment-account-info-container").children(".action-container").children(".action-edit").children(".btn-edit").attr("data-account-number", accountNumber);

                            $("#"+containerId).children(".payment-account-info-container").children(".action-container").children(".action-edit").children(".btn-edit").attr("data-masked-account-number", maskedAccountNumber);

                            $("#"+containerId).children(".payment-account-info-container").children(".action-container").children(".action-edit").children(".btn-edit").attr("data-account-label", accountLabel);

                            $("#"+containerId).children(".payment-account-info-container").children(".action-container").children(".action-edit").children(".btn-edit").attr("data-account-type", accountType);

                            $("#"+containerId).children(".payment-account-info-container").children(".action-container").children(".action-edit").children(".btn-edit").attr("data-billing-reference", billingReference);

                        }
                        $("#container_id").val('');
                        $("#profile_account_id").val('');
                        $("#masked_account_number").val('');
                        $("#account_type").val('');
                        $("#primary_account").val('');
                        $("#account-number").val('');
                        $("#nickname").val('');
                        $("#billing-reference").val('');
                        $(".add-new-fedex-account").show();
                        $(".fedex-new-account-form").hide();
                        $(".err-msg").hide();
                        $(".succ-msg").show();
                        $(".succ-msg .message").text('FedEx account has been successfully updated.');
                        $(".succ-msg").show();
                        $(".img-close-msg").trigger('focus');
                        $('#fedex-account-form').trigger("reset");
                    }
                }
            });
        }
    });

    $(document).on('click', '.remove-link', function () {
        let _this = this;
        let profileAccountId = $(_this).attr("data-profile-account-id");
        let ajaxUrl =  url.build('customer/account/deleteaccount');
        $('.fedex-new-account-form').hide();
        $('.add-new-fedex-account').show();
        $('#fedex-account-form').trigger("reset");
        $("#container_id").val("");
        $.ajax({
            type: "POST",
            url: ajaxUrl,
            data: {userProfileId: window.userProfileId, profileAccountId: profileAccountId},
            cache: false,
            showLoader: true,
            success: function (data) {
                if (data.Failure || data.errors) {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $(".img-close-msg").trigger('focus');
                } else {
                    $(_this).parent(".action-remove").parent(".action-container").parent(".payment-account-info-container").parent(".payment-account-list").remove();
                    $(".err-msg").hide();
                    $(".succ-msg").show();
                    $(".succ-msg .message").text('FedEx account has been successfully removed.');
                    $(".succ-msg").show();
                    $(".img-close-msg").trigger('focus');
                }
            }
        });
    });
    /* B-1241756 End Here */

    $(document).on('click', '.mobile-edit', function () {
        let _this = this;
        $(_this).parent(".address-content").parent(".credit-card-address").prev(".credit-card-name").children(".action").children(".action-edit").trigger('click');
    });

    function getCardType(cardNumber) {
        let cardType = null;
        if (cardNumber[0] == "4") {
            cardType = "VISA";
        } else if (cardNumber[0] == "5") {
            cardType = "MASTERCARD";
        } else if ((cardNumber[0] == "3" && cardNumber[1] == "4") || (cardNumber[0] == "3" && cardNumber[1] == "7")) {
            cardType = "AMEX";
        } else if (cardNumber[0] == "6") {
            cardType = "DISCOVER";
        } else if (cardNumber[0] == "3" && cardNumber[1] == "8" || (cardNumber[0] == "3" && cardNumber[1] == "6")) {
            cardType = "DINERS";
        } else {
            cardType = "GENERIC";
        }
        return cardType;
    }

    /**
     * Get public key to gerenate credit card token
     */
    function getExtractPublicKey (publicKey) {
        const pem_re = /-----BEGIN (?:[\w\s]+)-----\s*([0-9A-Za-z+/=\s]+)-----END/;
        const pem_result = pem_re.exec(publicKey);
        if (pem_result != null) {
            publicKey = pem_result[1];
        }
        const tempPublicKey = publicKey.replace(/\n/g, '');
        return window.atob(tempPublicKey);
    }

    /**
     * Get parase public key gerenate credit card token
     */
    function getParsePublicKey (pubkey) {
        const pubkey_tree = ans.decode(pubkey);
        const n_raw = pubkey_tree.sub[1].sub[0].sub[0].rawContent();
        const e_raw = pubkey_tree.sub[1].sub[0].sub[1].rawContent();
        const n = n_raw;
        let e = 0;
        for (let i = 0; i < e_raw.length; i++) {
            e = (e << 8) | e_raw.charCodeAt(i);
        }
        return [n, e];
    }

    /**
     * Get encryptrd credit card token
     */
    function fetchEncryptedCreditCard (textBlock, publicKey) {
        var publicKey = getExtractPublicKey(publicKey);
        const pki = getParsePublicKey(publicKey);
        const chdkey_modulus = pki[0];
        const chdkey_exponent = pki[1];
        let rsaObj = new rsa(chdkey_modulus, chdkey_exponent);
        let encryptedCreditCard = rsaObj.encrypt(textBlock);
        return encryptedCreditCard;
    }

    /**
     * Get encryption key gerenate credit card token
     */
    function getEncryptionKey() {
        let encryptionkey = url.build('delivery/index/encryptionkey');
        $.ajax({
            url: encryptionkey,
            type: "POST",
            dataType: "json",
            success: function (data) {
                if (typeof (data.encryption) != "undefined" && data.encryption !== null) {
                    if (window.e383157Toggle) {
                        fxoStorage.set('ccEncryptedKey', data.encryption.key);
                    } else {
                        localStorage.setItem('ccEncryptedKey', data.encryption.key);
                    }
                }
            }
        });
    }

    /* End Credit Card Save & Edit Functionality */

    /* Ada for credit card & account page */
    $(document).ready(function() {
        $(".column.main .action .action-edit, .column.main .remove, .column.main .cart-status-make .default, .column.main .checkmark, .column.main .custom-radio-btn label, .column.main .info-icon img, .column.main .shipping-make-default, .column.main .btn-edit, .column.main .remove-link").each(function() {
            $(this).attr('tabindex', '0');
        });
        $(".customer-account-accountsandcreditcards .page-main").on("keydown", ".nav.items, .column.main", function(e) {
            switch (e.which) {
                case 13: // enter KEY
                    $(e.target).trigger('click')
                    break;
            }
        });
    });
    /* End Ada for credit card & account page */
});


