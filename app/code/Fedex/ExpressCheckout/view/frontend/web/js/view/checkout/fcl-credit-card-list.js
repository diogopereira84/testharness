/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    "jquery",
    "mage/url",
    "Magento_Checkout/js/model/step-navigator",
    "fedex/storage"
], function ($, url, stepNavigator,fxoStorage) {
    'use strict';

    let checkIcon = window.checkoutConfig.media_url + "/check-icon.png";
    let crossIcon = window.checkoutConfig.media_url + "/close-button.png";
    let circleIcon = window.checkoutConfig.media_url + "/circle-times.png";
    let infoIcon = window.checkoutConfig.media_url + "/information.png";

    /**
     * Show error or success message if page refreshed after click on review order button
     */
    $( window ).on( "load", function() {
        let msgHtml = null;
        let errorMsg = null;
        let isCardTokenExpaired;
        if(window.e383157Toggle){
            isCardTokenExpaired = fxoStorage.get('isCardTokenExpaired');
        }else{
            isCardTokenExpaired = localStorage.getItem('isCardTokenExpaired');
        }
        if (isCardTokenExpaired) {
            let errorMsgHead = 'Review Payment Method.';
            errorMsg = 'For security purposes, please review and re-enter your credit card details.';
            msgHtml = '<div class="express-msg-outer-most-credit error-security"><div class="express-msg-outer-credit-container"><div class="express-error-msg-container"><span class="icon-container"><img class="img-check-icon" alt="Check icon" src="'+ circleIcon +'"></span><span class="message heading">'+errorMsgHead+'</span><span class="message">'+errorMsg+'</span><img id="express_msg_close" class="img-close-msg" alt="close icon" src="'+ crossIcon +'" tabindex="0"></div> </div></div>';
            $(msgHtml).insertAfter(".opc-progress-bar");

            let errorMsgHtml = '<div class="express-msg-outer-most-info"><div class="express-msg-outer-info-container"><div class="express-info-msg-container"><span class="message error">Please review payment method details</span></div> </div></div>';
            $('.credit-card-info').html(errorMsgHtml);
        }

        let isCardSuccMessage,isCardwarningMessage;
        if(window.e383157Toggle){
            isCardSuccMessage = fxoStorage.get("isCardSuccMessage");
            isCardwarningMessage = fxoStorage.get("isCardwarningMessage");
        }else{
            isCardSuccMessage = localStorage.getItem("isCardSuccMessage");
            isCardwarningMessage = localStorage.getItem("isCardwarningMessage");
        }

        if (isCardSuccMessage) {
            errorMsg = 'Payment method successfully saved to your profile.';
            msgHtml = '<div class="express-msg-outer-most-credit error-security"><div class="express-msg-outer-credit-container"><div class="express-succ-msg-container"><span class="icon-container"><img class="img-check-icon" alt="Check icon" src="'+ checkIcon +'"></span><span class="message">'+errorMsg+'</span><img id="express_msg_close" class="img-close-msg" alt="close icon" src="'+ crossIcon +'" tabindex="0"></div> </div></div>';
            $(msgHtml).insertAfter(".opc-progress-bar");
        }

        if (isCardwarningMessage) {
            errorMsg = 'Your credit card could not be saved at this time, but you can continue checking out.';
            msgHtml = '<div class="express-msg-outer-most-info"><div class="express-msg-outer-info-container"><div class="express-info-msg-container"><span class="icon-container"><img class="img-info-icon" alt="Check icon" src="'+ infoIcon +'"></span><span class="message">'+errorMsg+'</span> </div> </div></div>';
            $('.credit-card-info').html(msgHtml);
        }
    });

    let retailProfileSession = typeof (window.checkoutConfig.retail_profile_session) !== "undefined" && window.checkoutConfig.retail_profile_session !== null ? window.checkoutConfig.retail_profile_session : false;

    let explorersD180349Fix = typeof (window.checkoutConfig.explorers_D180349_fix) !== "undefined" && window.checkoutConfig.explorers_D180349_fix !== null ? window.checkoutConfig.explorers_D180349_fix : false;

    let retailData = {};
    retailData['is_card'] = '';
     if (window.e383157Toggle) {
         fxoStorage.set('is_retail_credit_card_data', retailData);
     } else {
         localStorage.setItem('is_retail_credit_card_data', JSON.stringify(retailData));
     }
    /**
     * Custom dropdown hide show & credit card error message hide
     */
    $(document).on('click', '#credit-card-dropdown-content', function () {
        $(".card-number-error, .name-card-error, .exp-year-error, .cvv-card-error, .term-condition-error").hide();
        $(".card-number, .name-card, .exp-year, .cvv-card").removeClass("contact-error");
        $('.credit-card-dropdown-content').toggleClass("active");
    });

    /**
     * Trigger custom dropdown when enter or space key is pressed
     */
    $(document).on('keypress', '#credit-card-dropdown-content', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            e.preventDefault();
            $("#credit-card-dropdown-content").trigger('click');
        }
    });

    /**
     * Trigger card list when enter or space key is pressed
     */
    $(document).on('keypress', '.card-list', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if (keycode  == 13 || keycode  == 32) {
            e.preventDefault();
            $(':focus').trigger('click');
        }
    });

    /**
     * Trigger click for checkbox, when user enter or space key is pressed
     */
    $(document).on('keypress', '.cc-form-container .alternate-checkbox-container .checkmark', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            e.preventDefault();
            $(this).prev().trigger('click');
            return false;
        }
    });

    /**
     * Trigger click for term and condition, when user enter or space key is pressed
     */
     $(document).on('keypress', '.cc-form-container .is-terms-and-conditions', function (e) {
        let keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  == 13 || keycode  == 32){
            e.preventDefault();
            let url = $('.is-terms-and-conditions').attr("href");
            window.open(url, '_blank');
            return false;
        }
    });

    /**
     * Custom dropdown hide, Incase of click in outside contaner
     */
    $(document).on('click', function (e) {
        let dropDownContainer = $(".credit-card-dropdown");
        if ( !dropDownContainer.is(e.target)
            && dropDownContainer.has(e.target).length === 0 ) {
            $(".credit-card-dropdown-content").removeClass("active");
        }
    });

    /**
     * Terms and condition visiblity
     */
    $(document).on('click', '.set-credit-card-checkbox', function () {
        $(".term-condition-error").remove();
        if ($(this).is(":checked")) {
            $(".terms-and-condition-checkbox").removeAttr("disabled");
            $(".terms-and-condition-checkbox").next(".checkmark").removeAttr("disabled");
        } else {
            $(".terms-and-condition-checkbox").attr('disabled', 'disabled');
            $(".terms-and-condition-checkbox").next(".checkmark").attr('disabled', 'disabled');
            $(".terms-and-condition-checkbox").prop( "checked", false );
        }
    });

    /**
     * Terms and condition required message hide/show
     */
    $(document).on('click', '.terms-and-condition-checkbox', function () {
        if ($(this).is(":checked")) {
            $(".term-condition-error").remove();
        } else {
            $(".checkmark-terms-condition-container").after('<p class="term-condition-error">This field  is required.</p>');
        }
    });

    /**
     * Set Credit Card Information which is use for order place & hide show credit card form
     */
    $(document).on('click', '.card-list', function () {
        $(".card-number-error, .name-card-error, .exp-year-error, .cvv-card-error").hide();
        $(".card-number, .name-card, .expiration-month, .expiration-year, .cvv-number").removeClass("contact-error");
        let creditCardToken = $(':focus').attr("data-token") && ($(':focus').attr("data-token") != '') ? $(':focus').attr("data-token") : $(this).attr('data-token');
        if(window.e383157Toggle){
            fxoStorage.set('dataToken', creditCardToken);
        }else{
            localStorage.setItem('dataToken', creditCardToken);
        }
        let cardListHtml = $(this).html();
        let dataToken;
        if(window.e383157Toggle){
            dataToken = fxoStorage.get("dataToken");
        }else{
            dataToken = localStorage.getItem('dataToken');
        }
        if(dataToken == 'NEWCREDITCARD') {
            cardListHtml =  $(this).children(".manual-text").html();
        }
        $("#credit-card-dropdown-content").html(cardListHtml);
        $(".credit-card-dropdown-content").removeClass("active");
        let retailData = {};
        if (creditCardToken == 'NEWCREDITCARD') {
            $(".name-card-container, .card-number-container, .expiration-cvv-container, .profile-terms-condition").show();
            retailData['is_card'] = '';
            retailData['token'] = '';
            retailData['number'] = '';
            retailData['type'] = '';
            retailData['is_token_expaired'] = '';
            $(".credit-card-review-button").attr('disabled', 'disabled');
            $(".credit-card-review-button").addClass("place-pickup-order-disabled");
            let cardGeneric = window.checkoutConfig.media_url + "/Generic.png";
            $(".card-number-container img").attr("src", cardGeneric);
        } else {
            $(".card-number-container .card-number").val('');
            $(".card-number-container .card-number").trigger('blur');
            $(".name-card-container, .card-number-container, .expiration-cvv-container, .profile-terms-condition").hide();
            retailData['is_card'] = true;
            retailData['token'] = creditCardToken;
            retailData['number'] = $(':focus').attr("data-number") && ($(':focus').attr("data-number") != '') ? $(':focus').attr("data-number") : $(this).attr('data-number');
            retailData['type'] = $(':focus').attr("data-type") && ($(':focus').attr("data-type") != '') ? $(':focus').attr("data-type") : $(this).attr('data-type');
            retailData['is_token_expaired'] = $(':focus').attr("data-tokenexpired") && ($(':focus').attr("data-tokenexpired") != '') ? $(':focus').attr("data-tokenexpired") : $(this).attr('data-tokenexpired');
            $(".credit-card-review-button").removeAttr("disabled");
            $(".credit-card-review-button").removeClass("place-pickup-order-disabled");
            $('.card-number').val('');
            $('.name-card').val('');
            $('.cvv-number').val('');
            $('.expiration-year').val('');
            $('.expiration-month').val('');

            $(".set-credit-card-checkbox").prop( "checked", false );
            $(".terms-and-condition-checkbox").prop( "checked", false );
            $(".terms-and-condition-checkbox").next(".checkmark").attr('disabled', 'disabled');
        }
        if (window.e383157Toggle) {
            fxoStorage.set('is_retail_credit_card_data', retailData);
        } else {
            localStorage.setItem('is_retail_credit_card_data', JSON.stringify(retailData));
        }
    });

    /**
     * Success or error message when click on outside of it
     */
    $(document).on('click', function() {
        if (stepNavigator.getActiveItemIndex() != 2) {
            $(".express-msg-outer-most-credit.error-security").remove();

        }
    });

    /**
     * Close success or error message when click on close icon
     */
    $(document).on('click', '.img-close-msg', function () {
        $(".express-msg-outer-most-credit").remove();
    });

    return {
        /**
         * Prepare Credit Card HTML for FCL
         * @return {*}
         */
        generateCreditCardHtml: function () {
            if(!$('.credit-card-dropdown').length) {
                let retailData = {};
                let creditCardList = '';
                if (retailProfileSession) {
                    creditCardList = typeof (retailProfileSession.output.profile.creditCards) !== "undefined" && retailProfileSession.output.profile.creditCards !== null ? retailProfileSession.output.profile.creditCards : false;
                    let primaryCardHtml = '';
                    let count = 0;
                    let isExpressCheckout;
                    if(window.e383157Toggle){
                        isExpressCheckout = fxoStorage.get('express-checkout');
                    }else{
                        isExpressCheckout = localStorage.getItem('express-checkout');
                    }
                    if (creditCardList.length > 0 || isExpressCheckout) {
                        if (creditCardList.length > 0) {
                            creditCardList.forEach(function(list) {
                                let imageUrl = window.checkoutConfig.media_url+'/'+list.creditCardType.toLowerCase()+'.png';
                                if (list.primary) {
                                    primaryCardHtml += '<img class="card-icon" src="'+imageUrl+'" alt="'+list.creditCardType+'"/><span class="card-mid-content">'+list.creditCardType+'</span><span class="card-last-content"> ending in *'+list.maskedCreditCardNumber.slice(-4)+'</span>';
                                    retailData['token'] = list.profileCreditCardId;
                                    retailData['nameOnCard'] = list.cardHolderName;
                                    retailData['number'] = list.maskedCreditCardNumber;
                                    retailData['type'] = list.creditCardType;
                                    retailData['is_token_expaired'] = list.tokenExpired;
                                    count++;
                                }
                            });
                        }
                        if (!count && creditCardList.length > 0) {
                            let list = creditCardList[0];
                            let imageUrl = window.checkoutConfig.media_url+'/'+list.creditCardType.toLowerCase()+'.png';
                            primaryCardHtml += '<img class="card-icon" src="'+imageUrl+'" alt="'+list.creditCardType+'"/><span class="card-mid-content">'+list.creditCardType+'</span><span class="card-last-content"> ending in *'+list.maskedCreditCardNumber.slice(-4)+'</span>';
                            retailData['token'] = list.profileCreditCardId;
                            retailData['nameOnCard'] = list.cardHolderName;
                            retailData['number'] = list.maskedCreditCardNumber;
                            retailData['type'] = list.creditCardType;
                            retailData['is_token_expaired'] = list.tokenExpired;
                        }
                        let creditCardHtml = '<div class="credit-card-dropdown"><div class="pre-select-card" id="credit-card-dropdown-content" tabindex="0">'+primaryCardHtml+'</div><ul class="credit-card-dropdown-content">';
                        if (creditCardList.length > 0) {
                            creditCardHtml += '<li class="fedex-card-title"><span>Saved Card (Personal)</span></li>';
                            creditCardList.forEach(function(list) {
                                let imageUrl = window.checkoutConfig.media_url+'/'+list.creditCardType.toLowerCase()+'.png';
                                creditCardHtml += '<li class="card-list" data-token="'+list.profileCreditCardId+'" data-number="'+list.maskedCreditCardNumber+'" data-type="'+list.creditCardType+'" data-tokenexpired="'+list.tokenExpired+'" tabindex="0"><img class="card-icon" src="'+imageUrl+'" alt="'+list.creditCardType+'"/><span class="card-mid-content">'+list.creditCardType+'</span><span class="card-last-content"> ending in *'+list.maskedCreditCardNumber.slice(-4)+'</span></li>';
                            });
                        }
                        creditCardHtml += '<li class="card-list last" data-token="NEWCREDITCARD"><span class="manual-text" tabindex="0"><i aria-hidden="true" class="fa fa-plus"></i><span class="manual-text">Enter new credit card</span></span></li></ul></div>';
                        $(".cc-form-container").before(creditCardHtml);
                        if (creditCardList.length > 0) {
                            retailData['is_card'] = true;
                        }
                        $(".name-card-container, .card-number-container, .expiration-cvv-container, .profile-terms-condition").hide();
                        $(".credit-card-review-button").attr('disabled', 'disabled');
                        $(".credit-card-review-button").addClass("place-pickup-order-disabled");
                        if ($(".card-list").length == 1 && isExpressCheckout) {
                            $(".card-list.last").trigger('click');
                            $(".credit-card-dropdown").hide();
                        }
                    } else {
                        retailData['is_card'] = '';
                        $(".name-card-container, .card-number-container, .expiration-cvv-container, .profile-terms-condition").show();
                    }
                    if (window.e383157Toggle) {
                        fxoStorage.set('is_retail_credit_card_data', retailData);
                    } else {
                        localStorage.setItem('is_retail_credit_card_data', JSON.stringify(retailData));
                    }
                }
                return creditCardList.length;
            }
        },

        /**
         * Get Credit Card Information Based on Key
         */
        getCreditCardInfo: function(info) {
            let cardInfo;
            if(window.e383157Toggle){
                cardInfo = fxoStorage.get('is_retail_credit_card_data');
            }else{
                cardInfo = JSON.parse(localStorage.getItem('is_retail_credit_card_data'));
            }
            return cardInfo[info];
        },

        /**
         * Save ctedit card information
         */
        saveCreditCardInfo: function(encryptedCreditCard, cardType, reCaptchaToken = false) {
            let self = this;
            $(".express-msg-outer-most-credit").remove();
            $(".card-number, .name-card, .expiration-month, .expiration-year, .cvv-number").removeClass("contact-error");
            let maskedCreditCardNumber;
            if(window.e383157Toggle){
                maskedCreditCardNumber = fxoStorage.get("creditCardNumber").replace(/ /g,'');
            }else{
                maskedCreditCardNumber = localStorage.getItem("creditCardNumber").replace(/ /g,'');
            }
            let lastNo = maskedCreditCardNumber.substr(-5);
            let creditCardLabel = cardType + '_' + lastNo;
            let msgHtml = null;
            let errorMsg = null;
            let errorMsgTransactionID = null;
            let nameOnCard = null;
            let streetLines = null;
            let postalCode = null;
            let stateOrProvinceCode = null;
            let stepStatus = false;
            let paymentData;
            if(window.e383157Toggle){
                paymentData = fxoStorage.get("paymentData");
            }else{
                paymentData = JSON.parse(localStorage.getItem('paymentData'));
            }
            let isShip;
            if(window.e383157Toggle){
                isShip = fxoStorage.get("shipkey") != 'false';
            }else{
                isShip = localStorage.getItem("shipkey") != 'false';
            }
            if (isShip) {
                nameOnCard = paymentData.nameOnCard;
                streetLines = _.get(
                    paymentData,
                    ['billingAddress', 'street'],
                    null
                );

                if (streetLines === null) {
                    streetLines = _.get(
                        paymentData,
                        ['billingAddress', 'address'],
                        null
                    );
                }

                if (streetLines && _.isFunction(streetLines.toString)) {
                    streetLines = streetLines.toString();
                }

                postalCode = paymentData.billingAddress.postcode || paymentData.billingAddress.zip;
                stateOrProvinceCode = paymentData.billingAddress.regionCode || paymentData.billingAddress.state;
            } else {
                if (explorersD180349Fix) {
                    nameOnCard = paymentData.nameOnCard;
                } else {
                    let pickupData;
                    if (window.e383157Toggle) {
                        pickupData = fxoStorage.get("pickupData");
                    } else {
                        pickupData = JSON.parse(localStorage.getItem("pickupData"));
                    }
                    nameOnCard = pickupData.contactInformation.contact_fname + ' ' + pickupData.contactInformation.contact_lname;
                }

                streetLines = paymentData.billingAddress.address;
                postalCode = paymentData.billingAddress.zip;
                stateOrProvinceCode = paymentData.billingAddress.state;
            }
            let isPrimary = false;
            let isExpressCheckout;
            if(window.e383157Toggle){
                isExpressCheckout = fxoStorage.get('express-checkout');
            }else{
                isExpressCheckout = localStorage.getItem('express-checkout');
            }
            if ($(".card-list").length == 1 && isExpressCheckout) {
                isPrimary = true;
            }

            let saveInfoUrl = url.build('express-checkout/creditcard/saveinfo');
            let profileCreditCardId = typeof (retailProfileSession.output.profile.userProfileId) !== "undefined" && retailProfileSession.output.profile.userProfileId !== null ? retailProfileSession.output.profile.userProfileId : false;
            $.ajax({
                url: saveInfoUrl,
                type: "POST",
                data: {
                    loginValidationKey: paymentData.loginValidationKey,
                    cardHolderName: nameOnCard,
                    maskedCreditCardNumber: lastNo,
                    creditCardLabel: creditCardLabel,
                    creditCardType: cardType,
                    expirationMonth: paymentData.expire,
                    expirationYear: paymentData.year,
                    company: paymentData.billingAddress.company,
                    streetLines: streetLines,
                    regionId: paymentData.billingAddress.regionId || '',
                    postalCode: postalCode,
                    city: paymentData.billingAddress.city,
                    stateOrProvinceCode: stateOrProvinceCode || '',
                    countryCode: "US",
                    primary: isPrimary,
                    saveStatus: '',
                    profileCreditCardId: profileCreditCardId,
                    encryptedData: encryptedCreditCard,
                    'g-recaptcha-response': reCaptchaToken
                },
                dataType: 'json',
                showLoader: true,
                success: function (data) {
                      if(data.status === 'auth_failed') {
                        if(window.e383157Toggle){
                            fxoStorage.delete('isCardTokenExpaired');
                            fxoStorage.delete('isCardwarningMessage');
                            fxoStorage.delete('isCardSuccMessage');
                        }else{
                            localStorage.removeItem('isCardTokenExpaired');
                            localStorage.removeItem('isCardwarningMessage');
                            localStorage.removeItem('isCardSuccMessage');
                        }
                        let errorMsgHead = 'Credit Card Authorization Failed.';
                        errorMsg = 'Please review the fields and check that your credit card details are accurate.';
                        if (typeof data.info.transactionId !== 'undefined') {
                            errorMsgTransactionID = "Transaction ID: " + data.info.transactionId;
                        }
                        msgHtml = '<div class="express-msg-outer-most-credit auth-failed-error"><div class="express-msg-outer-credit-container"><div class="express-error-msg-container"><span style="height: 100%;" class="icon-container"><img class="img-check-icon" alt="Check icon" src="'+ circleIcon +'"></span><span class="message heading">'+errorMsgHead+'</span><span class="message">'+errorMsg+'</span> <span class="message">' + errorMsgTransactionID +'</span> <img id="express_msg_close" class="img-close-msg" alt="close icon" src="'+ crossIcon +'" tabindex="0"></div> </div></div>';
                        $(".card-number, .name-card, .expiration-month, .expiration-year, .cvv-number").addClass("contact-error");
                        $(msgHtml).insertAfter(".opc-progress-bar");
                        stepStatus =  false;
                    } else if(data.status === 'error') {
                        if(window.e383157Toggle){
                            fxoStorage.delete('isCardTokenExpaired');
                            fxoStorage.delete('isCardSuccMessage');
                            fxoStorage.set('isCardwarningMessage', true);
                        }else{
                            localStorage.removeItem('isCardTokenExpaired');
                            localStorage.removeItem('isCardSuccMessage');
                            localStorage.setItem('isCardwarningMessage', true);
                        }
                        window.dispatchEvent(new Event('closeNonCombinableDiscount'));
                        window.dispatchEvent(new Event('closeMarketplaceDisclaimer'));

                        if (explorersD180349Fix) {
                            $('.error-container').removeClass('api-error-hide');
                            $('.message-container').text('System error, Please try again.');
                        } else {
                            window.location.href = url.build('checkout#payment');
                        }

                        return true;
                    } else if(data.status === 'recaptcha_error') {
                        if(window.e383157Toggle){
                            fxoStorage.delete('isCardTokenExpaired');
                            fxoStorage.delete('isCardSuccMessage');
                            fxoStorage.set('isCardwarningMessage', true);
                        }else{
                            localStorage.removeItem('isCardTokenExpaired');
                            localStorage.removeItem('isCardSuccMessage');
                            localStorage.setItem('isCardwarningMessage', true);
                        }
                        if (explorersD180349Fix) {
                            $('.error-container').removeClass('api-error-hide');
                            $('.message-container').text('System error, Please try again.');
                        } else {
                            window.location.href = url.build('checkout#payment');
                        }

                        return true;
                    } else {
                        if(window.e383157Toggle){
                            fxoStorage.delete('isCardTokenExpaired');
                            fxoStorage.delete('isCardwarningMessage');
                            fxoStorage.set('isCardSuccMessage', true);
                        }else{
                            localStorage.removeItem('isCardTokenExpaired');
                            localStorage.removeItem('isCardwarningMessage');
                            localStorage.setItem('isCardSuccMessage', true);
                        }
                        errorMsg = 'Payment method successfully saved to your profile.';
                        msgHtml = '<div class="express-msg-outer-most-credit error-security"><div class="express-msg-outer-credit-container"><div class="express-succ-msg-container"><span class="icon-container"><img class="img-check-icon" alt="Check icon" src="'+ checkIcon +'"></span><span class="message">'+errorMsg+'</span><img id="express_msg_close" class="img-close-msg" alt="close icon" src="'+ crossIcon +'" tabindex="0"></div> </div></div>';
                        let isExpressCheckout;
                        if(window.e383157Toggle){
                            isExpressCheckout = fxoStorage.get('express-checkout');
                        }else{
                            isExpressCheckout = localStorage.getItem('express-checkout');
                        }
                        if ($(".card-list").length == 1 && isExpressCheckout) {
                            $(".credit-card-dropdown").show();
                        }

                        // B-1415208 : Checkout page updates
                        // Push the newly added credit card to checkoutconfig
                        if (typeof retailProfileSession.output.profile.creditCards === 'undefined') {
                            retailProfileSession.output.profile.creditCards = [];
                        }
                        if (typeof data.info.creditCardList[0] !== 'undefined') {
                            data.info.creditCardList[0].tokenExpired = false;
                            retailProfileSession.output.profile.creditCards.push(data.info.creditCardList[0]);
                        }
                        // Update checkout config
                        window.checkoutConfig.retail_profile_session = retailProfileSession;
                        // Regenerate credit card list
                        $('.credit-card-dropdown').remove();
                        self.generateCreditCardHtml();
                        // Select credit card from drop down.
                        if (typeof data.info.creditCardList[0] !== 'undefined' &&
                            typeof data.info.creditCardList[0].profileCreditCardId !== 'undefined') {
                            $('.card-list[data-token="' + data.info.creditCardList[0].profileCreditCardId + '"]').trigger('click');
                        }
                        $(msgHtml).insertAfter(".opc-progress-bar");

                        window.dispatchEvent(new Event('closeNonCombinableDiscount'));
                        window.dispatchEvent(new Event('closeMarketplaceDisclaimer'));

                        if (!explorersD180349Fix) {
                            window.location.href = url.build('checkout#payment');
                        }

                        return true;
                    }
                }
            });
            return stepStatus;
        },

        /**
         * Get credit card type
         */
        getCardType: function(cardNumber) {
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
    };
});
