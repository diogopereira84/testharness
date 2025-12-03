define([
    'jquery',
    "mage/url",
    "Fedex_Pay/js/view/ans1",
    "Fedex_Pay/js/view/rsaes-oaep",
    "Fedex_Recaptcha/js/reCaptcha",
    "fedex/storage",
    "Magento_Ui/js/modal/confirm",
    "jquery/ui",
    "Magento_Ui/js/modal/modal"
    ], function($, url, ans, rsa, reCaptcha, fxoStorage, confirm, modal) {
    "use strict";

    var fedexAccountPopupModel = "#modal-content-fedex-account";
    var isFedexPrintAccount = false;
    var isFedexShipAccount = false;
    var isFedexDiscountAccount = false;
    var fedexDiscountAccount = '';
    let fedexAccountPopupOptions = {
        type: 'popup',
        responsive: true,
        innerScroll: false,
        clickableOverlay: false,
        modalClass: 'add-account-popup-modal',
        title: '',
        buttons: []
    };

    if (typeof (window.fedexPrintAccount) != 'undefined' && window.fedexPrintAccount.length > 0) {
        isFedexPrintAccount = true;
    }

    if (typeof (window.fedexShipAccount) != 'undefined' && window.fedexShipAccount.length > 0) {
        isFedexShipAccount = true;
    }

    if (typeof(window.validateFdxLogin) != 'undefined' && window.validateFdxLogin == '') {
        $(".err-msg").show();
        $(".err-msg .message").html('System error, Please try again.</span>');
    }

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
        if (SelectValue == '' || SelectValue == null) {
            $(_this).addClass("select-empty");
        } else {
            $(_this).removeClass("select-empty");
        }
    });
    $(document).on('keyup blur', '#name_card', function () {
        let _this = this;
        let nameOnCard = $(_this).val();
        if (nameOnCard == '') {
            $(_this).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Please enter a name.</span>');
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
            $(".img-card").attr("src", window.mediaPath + "/visa.png");
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
    $(document).on('blur', '#card_number', function () {
        let _this = this;
        let cardNumber = $(_this).val();
        let card = cardNumber.replaceAll(' ', '');
        if(window.e383157Toggle){
            fxoStorage.set("cardNumber", cardNumber);
        }else{
            localStorage.setItem("cardNumber", cardNumber);
        }
        if (card.length > 13) {
            let masked = "*" + card.substr(-4);
            $(_this).val(masked);
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
        if (cardNumber.length > 0) {
            let maskedCreditCardNumber;
            if (window.e383157Toggle) {
                maskedCreditCardNumber = fxoStorage.get("cardNumber");
            } else {
                maskedCreditCardNumber = localStorage.getItem("cardNumber");
            }
            $(_this).val(maskedCreditCardNumber);
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

    /* Credit Card Edit Functionality */
    $(document).on('click', '.action .action-edit', function () {
        $('.credit-card-form-container').show();
        $("#cc-form").parent(".box-credit-card-form-container").show();
        $("#save_status").val(1);
        $('#edit_status').val('');
        let cardInfo = JSON.parse($(".credit-cart-content").attr('data'));
        cardInfo = cardInfo.data;
        $('html, body').animate({
            scrollTop: $(".cc-form").offset().top
        }, 1000);
        $("#name_card").val(cardInfo.nameOnCard);
        if(window.e383157Toggle){
            fxoStorage.set("cardNumber", '');
        }else{
            localStorage.setItem("cardNumber", '');
        }
        let creditCardNumber = cardInfo.ccNumber;
        let masked = "*" + creditCardNumber.substr(-4);
        $(".error").html('');
        $("input, select").removeClass('error-text select-empty error-year-text');
        $("#card_number").val(masked);
        $("#expiration_month").val(cardInfo.ccExpiryMonth);
        $("#expiration_year").val(cardInfo.ccExpiryYear);
        $("#cvv").val('');
        $(".check-icon").hide();
        $("#address_line_one").val(cardInfo.addressLine1);
        if (typeof(cardInfo.addressLine2) != 'undefined' && cardInfo.addressLine2) {
            if (cardInfo.addressLine2.trim()) {
                $("#address_line_two").val(cardInfo.addressLine2);
                $(".add-address-line-two").trigger("click");
            } else {
                $(".second-address-line-link").show();
                $(".input-container-toogle").hide();
                $("#address_line_two").val('');
            }
        }
        $("#nick_name").val(cardInfo.nickName);
        $("#company_name").val(cardInfo.ccCompanyName);
        $("#zipcode").val(cardInfo.zipCode);
        $("#city").val(cardInfo.city);
        $("#state").val(cardInfo.state);
    });

    $(document).on('click', '#card_number, #cvv, #expiration_year, #expiration_month', function () {
        let saveStatus = $("#save_status").val();
        let maskedCreditCardNumber;
        if (window.e383157Toggle) {
            maskedCreditCardNumber = fxoStorage.get("cardNumber");
        } else {
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
        } else {
            $("#expiration_month").next(".error").empty();
            $("#expiration_month, #expiration_year").removeClass("error-text");
        }
        enableOrDisableButton();
    });

    /* Credit Card Save Functionality */
    $(document).on('click', '.shared-credit-card-form-container .btn-submit-container .btn-submit', function () {
        if (!$("#is_term_and_conditions").is(":checked")) {
            $(".error.set-default").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">Please accept the terms and conditions.</span>');
            return false;
        } else {
            $(".error.set-default").html("");
        }
        getEncryptionKey().done(async function() {
            $(".error.nick-name").html("");
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
            let expirationMonth = $("#expiration_month").val();
            let expirationYear = $("#expiration_year").val();
            let cvv = $("#cvv").val();
            let company = $("#company_name").val();
            let streetLineOne = $("#address_line_one").val();
            let streetLineTowo = $("#address_line_two").val();
            let streetLines = '';
            if (streetLineTowo) {
                streetLines = streetLineOne + '||' +streetLineTowo;
            } else {
                streetLines = streetLineOne;
            }
            let postalCode = $("#zipcode").val();
            let city = $("#city").val();
            let stateOrProvinceCode = $("#state").val();
            let countryCode = $("#country").val();
            let primary = false;
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
                    let recaptchaToken = await reCaptcha.generateRecaptchaToken('shared_cc');
                    let saveInfoUrl = url.build('customer/creditcard/savesharedcreditcardinfo');
                    $.ajax({
                        url: saveInfoUrl,
                        type: "POST",
                        data: {
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
                            if (data.status === 'error' || data.status === 'auth_failed') {
                                $(".succ-msg").hide();
                                $(".err-msg .message").text("System error, Please try again.");
                                $(".err-msg").show();
                                $('html, body').animate({
                                    scrollTop: $(".msg-container").offset().top
                                }, 1000);
                            } else if (data.status == 'recaptcha_error') {
                                $(".succ-msg").hide();
                                $(".err-msg").show();
                                $(".err-msg .message").text(data.message);
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

                                if (typeof data.info !== 'undefined' && data.info) {
                                    $(".credit-cart-content").remove();
                                    $(".save-credit-card-changes").after(data.info);
                                    $(".store-credit-card.payment-method-container").show();
                                    $(".credit-cart-content").attr("data", data.ccData);
                                } else {
                                    location.reload(true);
                                }

                                if (data.isPayment) {
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
                                $(".succ-msg").show();
                                $('html, body').animate({
                                    scrollTop: $(".msg-container").offset().top
                                }, 1000);
                            }
                            if(window.e383157Toggle){
                                fxoStorage.set("cardNumber", '');
                            }else{
                                localStorage.setItem("cardNumber", '');
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
            primary = true;
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
    function fetchEncryptedCreditCard(textBlock, publicKey) {
        let newPublicKey = getExtractPublicKey(publicKey);
        const pki = getParsePublicKey(newPublicKey);
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

        return $.ajax({
            url: encryptionkey,
            type: "POST",
            dataType: "json",
            success: function (data) {
                if(data !== null && typeof(data.encryption) != "undefined" && data.encryption !== null) {
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
        if (window.explorersCompanySettingsCustomerAdmin) {
            $(".customer-account-sharedcreditcards .page-main").on("keydown", ".site-level-payments-credit-card-container", function(e) {
                switch (e.which) {
                    case 13: // enter KEY
                        if (e.target.className === 'save-changes-btn'){
                            $(e.target).trigger('click');
                        } else {
                            $(e.target).trigger('click');
                            return false;
                        }
                }
            });
        } else {
            $(".column.main .action .action-edit, .column.main .remove, .column.main .cart-status-make .default, .column.main .checkmark, .column.main .custom-radio-btn label, .column.main .info-icon img, .column.main .shipping-make-default, .column.main .btn-edit, .column.main .remove-link").each(function() {
                $(this).attr('tabindex', '0');
            });
            $(".customer-account-sharedcreditcards .page-main").on("keydown", ".nav.items, .column.main", function(e) {
                switch (e.which) {
                    case 13: // enter KEY
                        $(e.target).trigger('click')
                        break;
                }
            });
        }
    });
    /* End Ada for credit card & account page */

    /**
     * Set Non Editable Payment Method Field Value
     */
    $(document).on('click', '#non-editable-cc-payment', function () {
        let isChecked = $("#non-editable-cc-payment").is(":checked");
        if (isChecked) {
            $("#non-editable-cc-payment").val(1);
        } else {
            $("#non-editable-cc-payment").val(0);
        }
    });

    /**
     * Save Non Editable Payment Method
     */
    $(document).on('click', '.non-editable-payment-method-save', function () {
        let nonEditableCCPaymentMethod = $("#non-editable-cc-payment").val();
        let companyId = $("#company_id").val();

        $.ajax({
            url: url.build('customer/account/setnoneditablepaymentmethod'),
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: {
                non_editable_payment_method: nonEditableCCPaymentMethod,
                company_id : companyId
            },
            success: function (data) {
                if (data.status == 'success') {
                    $(".err-msg").hide();
                    $(".succ-msg").show();
                    $(".succ-msg .message").text(data.message);
                    $(".succ-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 1000);
                    $('#loader').hide();
                } else {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text("System error, Please try again.");
                    $(".err-msg").show();
                    $('html, body').animate({
                       scrollTop: $(".msg-container").offset().top
                     }, 1000);
                }
            }
        });
    });

    $("input:checkbox").focus(function(){
        $(this).siblings(".custom-slider").css("box-shadow", '0 0 3px 1px #00699d');
    });

    $("input:checkbox").blur(function(){
        $(this).siblings(".custom-slider").css("box-shadow", 'unset');
    });

    /**
     * Remove shared credit card info
     */
    $(document).on('click', '.action .remove-shared-credit-card', function () {
        let companyId = $("#company_id").val();
        $.ajax({
            url: url.build('customer/creditcard/removesharedcreditcardinfo'),
            showLoader: true,
            type: "POST",
            dataType: 'json',
            data: {
                companyId: companyId
            },
            success: function (data) {
                if (data.status === 'error') {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text(data.message);
                    $(".err-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 1000);
                    $('#loader').hide();
                } else {
                    $(".store-credit-card").hide();
                    $(".credit-cart-content").remove();
                    $(".shared-credit-card-form-container").show();
                    $("#cc-form").parent(".box-credit-card-form-container").show();
                    $(".site-level-payments-credit-card-container").find(".credit-card-container-section").show();
                    $(".site-level-payments-credit-card-container").find(".box-add-new-card").show();
                    $(".site-level-payments-credit-card-container").find(".credit-card-form-container").hide();
                    $(".err-msg").hide();
                    $(".succ-msg").show();
                    $(".succ-msg .message").text(data.message);
                    $(".succ-msg").show();
                    $('html, body').animate({
                        scrollTop: $(".msg-container").offset().top
                    }, 1000);
                    $('#loader').hide();
                }
            }
        });
    });

/* Start Site Level Payment Changes For Credit Card Section */
if (window.explorersCompanySettingsCustomerAdmin) {
    let boxAddNewCardClass = $(".box-add-new-credit-card"),
        creditCardFormContainerClass = $(".credit-card-form-container"),
        creditCardToggleId = $("#enable-credit-card-toggle"),
        fedexAccountToggleId = $("#enable-fedex-account-toggle"),
        creditCardContainerSection = $(".credit-card-container-section-toggle"),
        boxAddNewCard = $('.box-add-new-card.box-add-new-credit-card');

    $(document).ready(function () {
        $('body').addClass('site-level-payments-body');
    });

    $(document).on('click', function (event) {
        if($(event.target).hasClass('credit-card-edit') ||
            $(event.target).hasClass('site-level-credit-card-edit')) {
            $(creditCardFormContainerClass).addClass('_active');
        }
        if($(event.target).hasClass('action-close')) {
            $(".block-minicart").find("#top-cart-btn-checkout").attr("disabled",false);
        }
    });

    $(document).on('click', creditCardToggleId, function () {
        let isChecked = creditCardToggleId.is(":checked");
        if (isChecked) {
            creditCardToggleId.val(1);
            creditCardContainerSection.show();
            if(boxAddNewCard.hasClass('inactive')) {
                boxAddNewCard.hide();
            }
            if ($(".credit-card-container-section").find(".credit-cart-content").length === 0
                && !$(creditCardFormContainerClass).hasClass('_active')) {
                boxAddNewCard.show();
                boxAddNewCard.removeClass('inactive');
            }
        } else {
            creditCardToggleId.val(0);
            creditCardContainerSection.hide();
            if ($(boxAddNewCardClass).hasClass('_active')) {
                $(boxAddNewCardClass).show();
                $(boxAddNewCardClass).removeClass('_active')
            }
            if ($(creditCardFormContainerClass).hasClass('_active')) {
                $(creditCardFormContainerClass).hide();
                $(creditCardFormContainerClass).removeClass('_active')
            }
        }
    });

    $(".box-add-new-card .box-title a").on('click', function(e) {
        getEncryptionKey();
        e.preventDefault();
        $(boxAddNewCardClass).addClass('_active');
        $(boxAddNewCardClass).hide();
        $(creditCardFormContainerClass).show();
        $(creditCardFormContainerClass).addClass('_active');
        resetCreditCardForm();
    });

    $(document).on('click', ".action .remove-site-level-credit-card", function () {
        $(".credit-cart-content").hide();
        $("#remove_credit_card").val(1);
        if(boxAddNewCard.hasClass('inactive')) {
            boxAddNewCard.removeClass('inactive');
        }
        $(boxAddNewCard).show();
        $(creditCardFormContainerClass).hide();
        $(creditCardFormContainerClass).removeClass('_active');
        resetCreditCardForm();
    });

    $(document).on('click', fedexAccountToggleId, function () {
        let isChecked = fedexAccountToggleId.is(":checked");
        if (isChecked) {
            fedexAccountToggleId.val(1);
        } else {
            fedexAccountToggleId.val(0);
        }
    });

    $(document).on('click', "#ship_account_number_editable", function () {
        let isChecked = $(this).is(":checked");
        if (isChecked) {
            $(this).val(1);
        } else {
            $(this).val(0);
        }
    });

    $(document).on('click', "#print_account_number_editable", function () {
        let isChecked = $(this).is(":checked");
        if (isChecked) {
            $(this).val(1);
        } else {
            $(this).val(0);
        }
    });

    $(document).on('click', '#save-site-level-payment', function (e) {
        e.preventDefault();
        let getTargetUrl = fxoStorage.get("targetUrl");
        getEncryptionKey().done(async function() {
            let saveDataParams = [],
                creditCardDataParams = [],
                validationErrorFlag = false,
                isCreditCardChecked = creditCardToggleId.is(":checked"),
                isFedexAccountChecked = fedexAccountToggleId.is(":checked"),
                saveSettingUrl = url.build('customer/account/savesitelevelpayments');
            if (isCreditCardChecked &&
                $(creditCardFormContainerClass).hasClass('_active')) {
                let profileCreditCardId = $("#profile_credit_card_id").val(),
                    nameOnCardId = $('#name_card'),
                    nameOnCard = nameOnCardId.val(),
                    maskedCreditCardNumber,
                    cardNumberId = $('#card_number'),
                    cardNumber = cardNumberId.val(),
                    expirationMonthId = $("#expiration_month"),
                    expirationMonth = expirationMonthId.val(),
                    expirationYearId = $("#expiration_year"),
                    expirationYear = expirationYearId.val(),
                    cvvId = $("#cvv"),
                    cvv = cvvId.val(),
                    countryCode = $("#country").val(),
                    companyName = $("#company_name").val(),
                    addressLineOneId = $("#address_line_one"),
                    addressLineOne = addressLineOneId.val(),
                    zipcodeId = $("#zipcode"),
                    zipcode = zipcodeId.val(),
                    cityId = $("#city"),
                    city = cityId.val(),
                    stateOrProvinceCodeId = $("#state"),
                    stateOrProvinceCode = stateOrProvinceCodeId.val(),
                    isTermAndConditions = $('#is_term_and_conditions'),
                    nonEditableCcPayment = $('#non-editable-cc-payment').val();

                if (nameOnCard === '') {
                    $(nameOnCardId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Please enter a name.</span>');
                    $(nameOnCardId).addClass("error-text");
                    $(nameOnCardId).focus();
                    validationErrorFlag = true;
                }
                if (cardNumber === '') {
                    $(cardNumberId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Please enter a card number.</span>');
                    $(cardNumberId).addClass("error-text");
                    $(cardNumberId).focus();
                    validationErrorFlag = true;
                }
                if (isNaN(expirationMonth) || isNaN(expirationYear)
                    || expirationMonth === 0 || expirationYear === 0
                    || expirationMonth === null || expirationYear === null
                ) {
                    $(expirationMonthId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">Please enter a valid expiration date.</span>');
                    $(expirationMonthId).addClass("error-text");
                    $(expirationYearId).addClass("error-text");
                    $(expirationMonthId).focus();
                    validationErrorFlag = true;
                }
                if (cvv === '') {
                    $(cvvId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">Please enter CVV.</span>');
                    $(cvvId).addClass("error-text");
                    $(cvvId).focus();
                    validationErrorFlag = true;
                }
                if(addressLineOne === ''){
                    $(addressLineOneId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Address is required.</span>');
                    $(addressLineOneId).addClass("error-text");
                    $(addressLineOneId).focus();
                    validationErrorFlag = true;
                }
                if (zipcode === '') {
                    $(zipcodeId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> Zip Code is required.</span>');
                    $(zipcodeId).addClass("error-text");
                    $(zipcodeId).focus();
                    validationErrorFlag = true;
                }
                if (city === '') {
                    $(cityId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> City is required.</span>');
                    $(cityId).addClass("error-text");
                    $(cityId).focus();
                    validationErrorFlag = true;
                }
                if (stateOrProvinceCode === '') {
                    $(stateOrProvinceCodeId).next(".error").html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> State is required.</span>');
                    $(stateOrProvinceCodeId).addClass("error-text");
                    $(stateOrProvinceCodeId).focus();
                    validationErrorFlag = true;
                }
                if (!$(isTermAndConditions).is(":checked")) {
                    $('.is-term-and-conditions-error.error').html('<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text">Please accept the terms and conditions.</span>');
                    $(isTermAndConditions).addClass("error-text");
                    $(isTermAndConditions).focus();
                    validationErrorFlag = true;
                }
                if(validationErrorFlag === true) {
                    return false;
                } else {
                    if (window.e383157Toggle) {
                        maskedCreditCardNumber = fxoStorage.get("cardNumber").replace(/ /g, '');
                    } else {
                        maskedCreditCardNumber = localStorage.getItem("cardNumber").replace(/ /g, '');
                    }
                    let lastFiveCreditCard = maskedCreditCardNumber.substr(-5),
                    creditCardLabel = '',
                    lastNo = maskedCreditCardNumber.substr(-5),
                    creditCardType = getCardType(maskedCreditCardNumber),
                    streetLineOne = addressLineOne,
                    streetLineTowo = $("#address_line_two").val(),
                    streetLines = '',
                    year = expirationYear.substring(2, 4),
                    manualCC = 'M' + maskedCreditCardNumber + '=' + year + expirationMonth + ':' + cvv;
                    let pubKey;
                    creditCardLabel = getCardType(maskedCreditCardNumber) + '_' + lastNo;
                    if (streetLineTowo) {
                        streetLines = streetLineOne + '||' +streetLineTowo;
                    } else {
                        streetLines = streetLineOne;
                    }
                    if(window.e383157Toggle){
                        pubKey = fxoStorage.get('ccEncryptedKey');
                    }else{
                        pubKey = localStorage.getItem('ccEncryptedKey');
                    }
                    if (pubKey) {
                        let encryptedCreditCard = fetchEncryptedCreditCard(manualCC, pubKey);
                        if (encryptedCreditCard) {
                            creditCardDataParams = {
                                profileCreditCardId: profileCreditCardId,
                                cardHolderName: nameOnCard,
                                maskedCreditCardNumber: lastFiveCreditCard,
                                creditCardLabel: creditCardLabel,
                                creditCardType: creditCardType,
                                expirationMonth: expirationMonth,
                                expirationYear: expirationYear,
                                company: companyName,
                                streetLines: streetLines,
                                postalCode: zipcode,
                                city: city,
                                stateOrProvinceCode: stateOrProvinceCode,
                                countryCode: countryCode,
                                primary: false,
                                encryptedData: encryptedCreditCard,
                                nonEditableCcPayment: nonEditableCcPayment
                            };
                            $("#remove_credit_card").val(0);
                        }
                    }
                }
            }
            saveDataParams = {
                creditCardToggle: creditCardToggleId.val(),
                fedexAccountToggle: fedexAccountToggleId.val(),
                shipFedexAccountToggle: $('#ship_account_number_editable').val(),
                printFedexAccountToggle: $('#print_account_number_editable').val(),
                shipFedexAccount: $('#ship_account_number').val(),
                printFedexAccount: $('#print_account_number').val(),
                discountFedexAccount: fedexDiscountAccount,
                removeCreditCard: $('#remove_credit_card').val(),
                removeShipFedexAccount: $('#remove_ship_account').val(),
                removePrintFedexAccount: $('#remove_print_account').val(),
                creditCardDataParams: creditCardDataParams
            };
            $.ajax({
                url: saveSettingUrl,
                type: "POST",
                data: saveDataParams,
                dataType: "json",
                showLoader: true,
                success: function (data){
                    if (data.status === 'error') {
                        if (data.info.errors[0].code == 'SPOS.CREDITCARDTOKEN.200') {
                            $(".err-msg span.message").text("Check that your credit card and billing details are accurate and try again.\n Transaction ID: " + data.info.transactionId);
                            $(".err-msg").show();
                            $(".succ-msg").hide();
                            $(".img-close-msg").trigger('focus');
                            return false;
                        }
                    }
                    if (data.error || data.status === 'recaptcha_error') {
                        $(".err-msg span.message").text(data.message);
                        $(".err-msg").show();
                        $(".succ-msg").hide();
                        $(".img-close-msg").trigger('focus');
                    } else {
                        $(".succ-msg span.message").text(data.message);
                        $('.site-level-warning-popup').find('.action-close').trigger('click');
                        if(data.info){
                            $(".credit-cart-content").remove();
                            $(data.info).insertBefore(boxAddNewCard);
                            $(boxAddNewCard).hide();
                            $(boxAddNewCard).addClass('inactive');
                            $(".credit-cart-content").attr("data", data.ccData);
                        }
                        $(creditCardFormContainerClass).hide();
                        $(creditCardFormContainerClass).removeClass('_active');
                        $(".succ-msg").show();
                        $(".err-msg").hide();
                        $('html, body').animate({
                            scrollTop: $(".msg-container").offset().top
                        }, 500, function(){
                            setTimeout(function() {
                                let urlRedirect = fxoStorage.get("targetUrl");
                                if (urlRedirect !== '' && urlRedirect !== null) {
                                    window.location.replace(urlRedirect);
                                    fxoStorage.set('targetUrl', '');
                                } else if(getTargetUrl !== '' && getTargetUrl !== null) {
                                    window.location.replace(getTargetUrl);
                                    fxoStorage.set('targetUrl', '');
                                }
                                fxoStorage.set("isPaymentSettingEditable", false);
                            }, 1000);
                        });
                    }
                }
            });
        });
    });

    $(document).on('click', '.remove-account-link', function () {
        let _this = this;
        let accountType = $(_this).attr("data-account-type");
        var removeFedexId = $(_this).attr("data-remove-id");
        $('#'+removeFedexId).addClass('hide-remove-div').hide();
        if (accountType === 'Ship') {
            window.fedexShipAccount = '';
            isFedexShipAccount = false;
            $("#remove_ship_account").val(1);
        }
        if (accountType === 'Print') {
            window.fedexPrintAccount = '';
            isFedexPrintAccount = false;
            $("#remove_print_account").val(1);
        }
        if ($(".fedex-account-lists-section").find(".payment-account-list").length ===
            $(".fedex-account-lists-section").find(".hide-remove-div").length) {
            $(".fedex-account-list-container").hide();
        }
    });

    // B-2085008 :: POD2.0: Add Modals for FedEx account scenarios
    $(document).on('click', '.add-new-account-button', async function () {
        let accountNumber = $("#fedex_account_number").val();
        let recaptchaToken = await reCaptcha.generateRecaptchaToken('profile_fedex_account');
        let validateAccountUrl = url.build('customer/account/validateaccount');
        if (accountNumber) {
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
                    $(".new-fedex-account-error").html("");
                    if (data.status === 'recaptcha_error') {
                        $(".new-fedex-account-error").html('<span class="fedex-icon-error-text">' + data.message + '</span>');
                        $("#fedex_account_number").trigger('focus');
                        $(".add-new-account-button").css('margin-bottom', '24px');
                        return false;
                    }
                    if (data.status === false) {
                        $(".succ-msg").hide();
                        $(".err-msg .message").text("System error, Please try again.");
                        $(".err-msg").show();
                        $(".img-close-msg").trigger('focus');
                        return false;
                    }
                    let accountStatus = data.info.account_status.toUpperCase();
                    let accountType = data.info.account_type.toUpperCase();
                    if (accountStatus != 'ACTIVE' && accountType == "") {
                        var invalidAccountMsg = window.tiger_e486666 ? 'Enter a Valid Print Account Number' : 'The account number entered is invalid.';
                        $(".new-fedex-account-error").html('<span class="fedex-icon-error-text"> '+invalidAccountMsg+'</span>');
                        $("#fedex_account_number").trigger('focus');
                        $(".add-new-account-button").css('margin-bottom', '24px');
                    } else {
                        $(".add-new-account-button").css('margin-bottom', '');
                        if (typeof accountType != undefined || accountType != "" || accountType != null) {
                            let saveFlag = false;
                            if(accountType === "SHIPPING") {
                                $("#account_type").val('ship');
                                saveFlag = true;
                            }
                            if(accountType === "PAYMENT") {
                                $("#account_type").val('print');
                                saveFlag = true;
                                isFedexDiscountAccount = false;
                            }
                            if(accountType === "DISCOUNT") {
                                $("#account_type").val('print');
                                saveFlag = true;
                                isFedexDiscountAccount = true;
                            }
                            if((isFedexShipAccount && accountType === "SHIPPING") || (isFedexPrintAccount && (accountType === "PAYMENT" || accountType === "DISCOUNT"))) {
                                openAccountValidationModal(accountType);
                            } else {
                                if (saveFlag === true) {
                                    let recaptchaToken = await reCaptcha.generateRecaptchaToken('checkout_fedex_account');
                                    saveFedexAccount(recaptchaToken);
                                } else {
                                    $(".new-fedex-account-error").html('<span class="fedex-icon-error-text"> The account type is invalid.</span>');
                                    $("#fedex_account_number").trigger('focus');
                                    $(".add-new-account-button").css('margin-bottom', '24px');
                                }
                            }
                        } else {
                            $(".new-fedex-account-error").html('<span class="fedex-icon-error-text"> The account type is invalid.</span>');
                            $("#fedex_account_number").trigger('focus');
                            $(".add-new-account-button").css('margin-bottom', '24px');
                        }
                    }
                }
            });
        } else {
            $(".new-fedex-account-error").html('<span class="fedex-icon-error-text"> This is required field.</span>');
            $("#fedex_account_number").trigger('focus');
            $(".add-new-account-button").css('margin-bottom', '24px');
        }
    });

    $(document).on('click', '.fedex-account-cancel', function (e) {
        $(fedexAccountPopupModel).modal(
            fedexAccountPopupOptions,
            $(fedexAccountPopupModel)).modal('closeModal');
            e.preventDefault();
    });

    /**
     * Account Validation Modal
     *
     * @param {String} accountType - The accountType to validate
     * @return boolean
     */
    function openAccountValidationModal(accountType) {
        if (accountType == "SHIPPING") {
            $('.modal-content-fedex-account-form-sub-heading').text('Adding a new ship account will replace the existing ship account on file');
        } else {
            $('.modal-content-fedex-account-form-sub-heading').text('Adding a new print account will replace the existing print account on file');
        }
        $(fedexAccountPopupModel).modal(
            fedexAccountPopupOptions,
            $(fedexAccountPopupModel)).modal('openModal');
    }

    $(document).on('click', '#fedex-account-popup-save', async function () {
        let recaptchaToken = await reCaptcha.generateRecaptchaToken('checkout_fedex_account');
        saveFedexAccount(recaptchaToken);
    });
    function saveFedexAccount(recaptchaToken) {
        let accountNumber = $("#fedex_account_number").val(),
            accountType = $("#account_type").val(),
            companyId = $("#company_id").val(),
            lastNo = accountNumber.substr(-4),
            nickName = 'FedEx Account ' + lastNo,
            billingReference = "NULL",
            isNickName = false,
            saveInfoUrl = url.build('customer/account/siteleveladdnewaccount');
        $.ajax({
            url: saveInfoUrl,
            type: "POST",
            data: {
                userProfileId: window.userProfileId,
                accountNumber: accountNumber,
                nickName: nickName,
                billingReference: billingReference,
                isPrimary: false,
                isNickName: isNickName,
                fromProfile: true,
                'g-recaptcha-response': recaptchaToken,
                accountType: accountType,
                companyId: companyId
            },
            dataType: 'json',
            showLoader: true,
            success: function (data) {
                $(".fedex-account-cancel").trigger('click');
                if (data.status == 'recaptcha_error') {
                    $(".succ-msg").hide();
                    $(".err-msg .message").text(data.message);
                    $(".err-msg").show();
                    $(".img-close-msg").trigger('focus');
                } else if ((typeof (data.errors) != 'undefined' || typeof (data.error) != 'undefined') && (data.errors || data.error)) {
                    $(".succ-msg").hide();
                    if (data.errors) {
                        if (data.errors[0].code == 'REQUEST.ACCOUNTNUMBER.ALREADYEXISTS') {
                            $(".new-fedex-account-error").html('<span class="fedex-icon-error-text">This account already exists in your profile.</span>');
                            $("#fedex_account_number").trigger('focus');
                            $(".add-new-account-button").css('margin-bottom', '24px');
                            return false;
                        } else {
                            $(".err-msg .message").text('System error, Please try again.');
                            console.error('Saving credit card Fedex account error details[' +
                                saveInfoUrl +
                                ']: ', data.errors);
                        }
                    }
                    if (data.error) {
                        $(".err-msg .message").text(data.error);
                    }
                    $(".err-msg").show();
                    $(".img-close-msg").trigger('focus');
                } else {
                    if (data.status == true) {
                        $(".fedex-account-list-container").show();
                        $("#"+data.accountTypeDiv).remove();
                        $(".fedex-account-list-container .fedex-account-lists-section").prepend(data.info);
                    }
                    if (accountType === 'ship') {
                        window.fedexShipAccount = accountNumber;
                        isFedexShipAccount = true;
                        $("#remove_ship_account").val(0);
                    }
                    if (accountType === 'print') {
                        window.fedexPrintAccount = accountNumber;
                        isFedexPrintAccount = true;
                        $("#remove_print_account").val(0);
                    }
                    if(accountType === 'print' && isFedexDiscountAccount) {
                        fedexDiscountAccount = accountNumber;
                    } else {
                        fedexDiscountAccount = '';
                    }
                    $('#fedex_account_number').val('');
                    $(".fedex-account-lists-section").focus();
                }
            }
        });
    }
    function resetCreditCardForm() {
        $('#name_card').val('');
        $('#card_number').val('');
        $("#expiration_month").val('');
        $("#expiration_year").val('');
        $("#cvv").val('');
        $("#company_name").val('');
        $("#address_line_one").val('');
        $("#address_line_two").val('');
        $("#zipcode").val('');
        $("#city").val('');
        $("#state").val('');
        $('#is_term_and_conditions').prop('checked', false);
        $('#non-editable-cc-payment').val(1).prop('checked', true);
    }
    /* End Site Level Payment Changes For Credit Card Section */

    /* start add warning popup for site level payment settings */
        $(document).ready(function () {
            $('.site-level-payments-credit-card-container').find(':input').each(function (index, value) {
                fxoStorage.set("isPaymentSettingEditable", false);
                fxoStorage.set("targetUrl", '');
            });

            $(document).on('keydown keypress', '.site-level-action-secondary', function (e) {
                if (e.which === 9) { // Tab key
                    $('.site-level-action-secondary').css('box-shadow', '');
                }
            });

            $('.site-level-payments-credit-card-container').on('change paste', ':input', function (e) {
                fxoStorage.set("isPaymentSettingEditable", true);
            });

            $('.site-level-payments-credit-card-container').on('click', '.remove-site-level-click', function (e) {
                fxoStorage.set("isPaymentSettingEditable", true);
            });

            $(document).on('click', function (event) {
                let isPaymentSettingEditable = fxoStorage.get("isPaymentSettingEditable");
                var container = $(".site-level-payments-credit-card-container");
                let targetUrl = '',
                    menuTags = event.target.parentNode,
                    showModalPopupFlag = false;
                if (menuTags.tagName == 'A' ||
                    event.target.tagName == 'A' ||
                    $(event.target).hasClass('checkout')
                ) {
                    showModalPopupFlag = true;
                }
                if (showModalPopupFlag === true && $('.modal-popup.site-level-warning-popup._show').length == 0) {
                    if (isPaymentSettingEditable
                        && !container.is(event.target)
                        && container.has(event.target).length === 0
                        && event.target.className !== 'site-level-action-primary'
                        && event.target.className !== 'site-level-action-secondary'
                        && event.target.className !== 'action-close'
                    ) {
                        event.preventDefault();
                        let alertIconImage = typeof (window.checkout.alert_icon_image) != 'undefined' && window.checkout.alert_icon_image != null ? window.checkout.alert_icon_image : '';
                        let contentDetails = '<div class="site-level-warning-popup-content"><h3 class="site-level-warning-title">Want to save your changes?</h3><p class="site-level-warning-description">The changes you have made have not been saved, and will be lost.</p></div>';
                        confirm({
                            buttons: [
                                {
                                    text: $.mage.__("DON'T SAVE"),
                                    class: 'site-level-action-secondary',
                                    click: function () {
                                        fxoStorage.set("isPaymentSettingEditable", false);
                                        $("#cc-form").trigger("reset");
                                        $(".credit-cart-content").show();
                                        $(".payment-account-list").show();
                                        $("#remove_print_account").val(0);
                                        $("#remove_ship_account").val(0);
                                        $("#remove_credit_card").val(0);
                                        if ($(".credit-cart-content").length) {
                                            $(".box-add-new-card.box-add-new-credit-card").hide();
                                        }
                                        if ($(".fedex-account-lists-section").find(".hide-remove-div").length) {
                                            $(".fedex-account-list-container").show();
                                        }
                                        $(".site-level-warning-popup .modal-inner-wrap .action-close").trigger("click");
                                        setTimeout(function() {
                                            let urlRedirect = fxoStorage.get("targetUrl");
                                            if (urlRedirect !== '' && urlRedirect !== null) {
                                                window.location.replace(urlRedirect);
                                                fxoStorage.set('targetUrl', '');
                                            } else {
                                                $(event.target)[0].click();
                                            }
                                        }, 1000);
                                    }
                                },
                                {
                                    text: $.mage.__('SAVE'),
                                    class: 'site-level-action-primary',
                                    click: function () {
                                        $("#save-site-level-payment").trigger("click");
                                        targetUrl = event.target.getAttribute('href');
                                        fxoStorage.set("targetUrl", targetUrl);
                                    }
                                }
                            ],
                            modalClass: 'site-level-warning-popup',
                            title: '<img src="' + alertIconImage + '" class="site-level-warning-icon-img" aria-label="delete_image" />',
                            content: contentDetails,
                            focus: '.site-level-action-secondary'
                        });
                        $(".site-level-warning-popup .site-level-action-secondary, .site-level-warning-popup .site-level-action-primary, .site-level-warning-popup .action-close").each(function() {
                            $(this).attr('tabindex', '0');
                        });
                        if (event.screenX === 0 && event.screenY === 0) {
                            $(".site-level-action-secondary").css('box-shadow', '0 0 3px 1px #00699d');
                        }
                    }
                }
            });
        });
    // end code
}
});
