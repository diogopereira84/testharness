/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    'jquery',
    'uiComponent',
    'mage/storage',
    'mage/translate',
    'mage/url',
    "Magento_Checkout/js/model/quote",
    "Magento_Catalog/js/price-utils",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Checkout/js/model/step-navigator",
    "Magento_Customer/js/customer-data",
    "Fedex_Cart/js/view/summary/promo_account/promo-coupon/promo-coupon",
    "shippingFormAdditionalScript",
    "rateResponseHandler",
    "Fedex_Cart/js/three-pproduct",
    "fedex/storage",
    "rateQuoteErrorsHandler",
    "Fedex_Delivery/js/model/toggles-and-settings",
], function (
    ko,
    $,
    Component,
    storage,
    $t,
    urlBuilder,
    quote,
    priceUtils,
    shippingService,
    stepNavigator,
    customerData,
    promoCoupon,
    shippingFormAdditionalScript,
    rateResponseHandler,
    isThreePProduct,
    fxoStorage,
    rateQuoteErrorsHandler,
    togglesAndSettings
) {
    'use strict';

    var isSdeStore = shippingFormAdditionalScript.isSdeStore();
     /**
             *  E-390888 - Add FedEx Accounts for CC Commercial sites toggle enable or disable
             */
    var isEnabledFedexAccountCC = typeof (window.checkoutConfig.explorers_enable_disable_fedex_account_cc_commercial) !== "undefined" && window.checkoutConfig.explorers_enable_disable_fedex_account_cc_commercial != null ? window.checkoutConfig.explorers_enable_disable_fedex_account_cc_commercial : false;

    var isApplicablePaymentMethodCCOnly = typeof (window.checkoutConfig.company_payment_method_cc_only) !== "undefined" && window.checkoutConfig.company_payment_method_cc_only != null ? window.checkoutConfig.company_payment_method_cc_only : false;

    var baseUrl = window.BASE_URL;
    var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";
    var isSelfregCustomer = window.checkoutConfig.is_selfreg_customer;
    var shouldSelfregDisplayDiscount3pOnly = window.checkoutConfig.tiger_display_selfreg_cart_fxo_discount_3P_only;

    let isQuotePriceIsDashable = typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;

    return Component.extend({
        defaults: {
            template: 'Fedex_Cart/summary/fedex-account-discount/fedex-account-discount'
        },

        isFedexAccount: ko.observable(false),
        fedexAccountNumber: ko.observable(),
        showFedexAccount: ko.observable(false),
        fedexAccountAppliedNumber: ko.observable(),

        initialize: function (e) {
            this._super();
            var fedexAccountNumber = '';
            if(isEnabledFedexAccountCC && isApplicablePaymentMethodCCOnly) {
                fedexAccountNumber = typeof (window.checkoutConfig.company_discount_account_number) !== "undefined" ? window.checkoutConfig.company_discount_account_number : '';
            } else {
                fedexAccountNumber = window.checkoutConfig.fedex_account_number ? window.checkoutConfig.fedex_account_number : window.checkoutConfig.fedex_account_number_discount;
            }
            if (fedexAccountNumber) {
                var accMasked = fedexAccountNumber.length > 4 ? "*" + fedexAccountNumber.substr(-4) : fedexAccountNumber;
                if(window.e383157Toggle){
                    if (fxoStorage.get('fedexAccount') == '' || localStorage.getItem('fedexAccount') == null) {
                        fxoStorage.set('fedexAccount', fedexAccountNumber);
                    }
                    if (fxoStorage.get('fedexAccountApplied') || fxoStorage.get('fedexAccount')){
                        this.isFedexAccount(true);
                        this.showFedexAccount(false);
                    } else {
                        this.showFedexAccount(true);
                        this.isFedexAccount(false);
                    }
                }else{
                    if (localStorage.getItem('fedexAccount') == '' || localStorage.getItem('fedexAccount') == null) {
                        localStorage.setItem('fedexAccount', fedexAccountNumber);
                    }
                    if (localStorage.getItem('fedexAccountApplied') || localStorage.getItem('fedexAccount')){
                        this.isFedexAccount(true);
                        this.showFedexAccount(false);
                    } else {
                        this.showFedexAccount(true);
                        this.isFedexAccount(false);
                    }
                }
                this.fedexAccountNumber(accMasked);
                this.fedexAccountAppliedNumber(fedexAccountNumber);
            }

        },

        isSelfregCustomer: function () {
          return   isSelfregCustomer && shouldSelfregDisplayDiscount3pOnly;
        },

        isOnlyThreeProduct: function () {
            return isThreePProduct.isOnlyThreeProductAvailable();
        },
        /**
         * Hide label and show field to add fedex account number
         *
         * @return void
         */
        showAddFedexAccountField: function (data, event) {
            $(event.target).parent().hide();
            if (isQuotePriceIsDashable) {
                $(".fedex-account-block").show();
            }
            this.showFedexAccount(true);
        },

        /**
         * Get Promo Code Main Label
         *
         * @return {*}
         */
        fedexNumMainLabelText: function () {

            return ('+ Add Fedex Office Print Account For Discount');
        },

        /**
         * Get fedex office account number Label
         *
         * @return {*}
         */
        label: function () {

            return ('Fedex Office Print Account Number');
        },

        /**
         * Get Promo Code Label
         *
         * @return {*}
         */
        inputName: function () {
            var self = this;
            $('#fedex_account_no').on('focus',function (event) {
                self.onFocusFedexAccount(event);
            });
            $('#fedex_account_no').on("keypress", function (event) {
                self.onKeyPressFedexAccount(event);
            });

            self.showFedexAccount(false);

            return ('fedex_account_no');
        },

        /**
         * Get field is required
         *
         * @return {*}
         */
        uid: function () {

            return ('fedex_account_no');
        },

        /**
         * Get field class name
         *
         * @return {*}
         */
        applyButtonLabel: function () {

            return ('Apply');
        },

        /**
         * Encrypt fedex Account Number on blur
         */
        onBlurFedexAccount: function (data, event) {
            let fedexAccountNum = $(event.target).val();

            if (fedexAccountNum.length > 1) {
                this.fedexAccountNumber(fedexAccountNum);
                this.fedexAccountAppliedNumber(fedexAccountNum);
                var masked = fedexAccountNum.length > 4 ? "*" + fedexAccountNum.substr(-4) : fedexAccountNum;
                $(event.target).val(masked);
            }
            if (fedexAccountNum.length < 1) {
                this.fedexAccountNumber('');
            }
        },

        /**
         * Get decrypt fedex account number on focus
         */
        onFocusFedexAccount: function (event) {
            var self = this;
            let fedexAccountNum = self.fedexAccountNumber();
            $(event.target).val(fedexAccountNum);
        },

        /**
         * Prevent to enter non numeric value
         */
        onKeyPressFedexAccount: function (event) {
            if (event.which !== 8 && event.which !== 0 && event.which < 48 || event.which > 57) {
                event.preventDefault();
            }
        },

        /**
         * Apply FedEx account
         *
         * @param {*} data
         * @param {*} event
         * @returns
         */
        applyFedexAccount: function (data, event) {
            var self = this;

            window.dispatchEvent(new Event('closeNonCombinableDiscount'));

            let fedexAccount = $('#fedex_account_no').val();
            if (isQuotePriceIsDashable) {
                let actionId = event.target.id;
                let uploadToQuoteAppyAccUrl =  urlBuilder.build('uploadtoquote/index/addremoveaccount');
                let appliedFedexAccount = self.fedexAccountNumber();
                if(actionId == 'removed_fedex_account') {
                    appliedFedexAccount = '';
                }
                let postData = {"fedexAccount" : appliedFedexAccount};
                $.ajax({
                    url: uploadToQuoteAppyAccUrl,
                    type: "POST",
                    data: postData,
                    dataType: "json",
                    showLoader: true,
                    async: true
                }).done(function (response) {
                    if (response.isApplied || response.isRemoved) {
                        if(actionId == 'removed_fedex_account') {
                            $(".applied-account-four-digit").text('');
                            $(".fedex-account-block").hide();
                            $(".fedex-account-block-applied").hide();
                            $(".fedex-account-title").show();
                        } else{
                            $(".applied-account-four-digit").text(fedexAccount);
                            $(".fedex-account-block").hide();
                            $(".fedex-account-block-applied").show();
                        }
                    }
                });
                return false;
            }

            let removedFedexAccount = false;
            event.preventDefault();
            var removeFedexAccountId = "removed_fedex_account";
            let nonCombinedMessage = $('.shipping-message-container.message-block');
            if (nonCombinedMessage.length > 0) {
                nonCombinedMessage.fadeOut();
            }

            if (fedexAccount == null || fedexAccount == '' && event.target.id != removeFedexAccountId) {

                $(event.target).parents('.discount-actions-toolbar').next('.form-error-message-account').
                    text('This is required field.').
                    fadeIn().delay(5000).
                    fadeOut();
                $('#fedex_account_no').parent().addClass('fedex-account-error');
                setTimeout(function () {
                    jQuery('.fedex-account-error').removeClass('fedex-account-error');
                }, 5000);

                return false;
            }

            $(event.target).parents('.apply-field-group').find('.loadersmall').show();
            $(event.target).parents('.apply-field-group').find('button.submit.primary span').css('visibility', 'hidden');
            $(event.target).parents('.discount-actions-toolbar').next('.form-error-message-account').fadeOut();

            if (event.target.id == removeFedexAccountId) {
                self.fedexAccountNumber('');
                $('#fedex_account_no').val('');
                removedFedexAccount = true;
            }
            var isShip,isPick;
            if(window.e383157Toggle){
                isShip = fxoStorage.get("shipkey");
                isPick = fxoStorage.get("pickupkey");
                fxoStorage.set("TaxAmount", '');
                fxoStorage.set("EstimatedTotal", '');
            }else{
                isShip = localStorage.getItem("shipkey");
                isPick = localStorage.getItem("pickupkey");
                localStorage.setItem("TaxAmount", '');
                localStorage.setItem("EstimatedTotal", '');
            }
            let requestUrl = "pay/index/payrateapishipandpick";
            fedexAccount = self.fedexAccountNumber();
            var fedexAccountShipping;
            if(window.e383157Toggle){
                fedexAccountShipping = fxoStorage.get('shipping_account_number') && fxoStorage.get('shipping_account_number') !== '' ? fxoStorage.get('shipping_account_number') : '';
            }else{
                fedexAccountShipping = localStorage.getItem('shipping_account_number') && localStorage.getItem('shipping_account_number') !== '' ? localStorage.getItem('shipping_account_number') : '';
            }
            var checkSteps = stepNavigator.getActiveItemIndex() == 0;
            let pickupDateTimeForApi;
            if(window.e383157Toggle){
                pickupDateTimeForApi = fxoStorage.get("pickupDateTimeForApi");
            }else{
                pickupDateTimeForApi = localStorage.getItem("pickupDateTimeForApi");
            }
            if (checkSteps) {
                if (isShip === 'true' && isPick === 'false') {
                    var customerInfo = customerData.get('checkout-data')();
                    var customerCompany = null;
                    var customerCity = null;
                    var customerPostcode = null;
                    var customerRegionId = null;
                    var street = null;

                    var shippingMethodCode = null;
                    if (typeof (quote.shippingMethod._latestValue) !== "undefined" && quote.shippingMethod._latestValue !== null) {
                        shippingMethodCode = quote.shippingMethod._latestValue.method_code;
                    }
                    if (typeof (customerInfo.shippingAddressFromData) !== 'undefined' &&
                        customerInfo.shippingAddressFromData !== null) {
                        customerCompany = customerInfo.shippingAddressFromData.company;
                        customerCity = customerInfo.shippingAddressFromData.city;
                        customerPostcode = customerInfo.shippingAddressFromData.postcode;
                        customerRegionId = customerInfo.shippingAddressFromData.region_id;
                        street = Object.values(customerInfo.shippingAddressFromData.street).toString();
                    }

                    var isShippingOptionsVisible = $('div.checkout-shipping-method').is(":visible");
                    var isNotSelectOption = $('.table-checkout-shipping-method .col-method .radio:checked').val();
                    if (!isShippingOptionsVisible) {
                        shippingMethodCode = null;
                    } else if (isShippingOptionsVisible && typeof (isNotSelectOption) == "undefined" && isNotSelectOption == null) {
                        shippingMethodCode = null;
                    }
                    let shipMethodData;
                    if(window.e383157Toggle){
                        shipMethodData = fxoStorage.get('ship_method_data') || {};
                    }else{
                        shipMethodData = JSON.parse(localStorage.getItem('ship_method_data') || '{}');
                    }
                    var requestData = {
                        fedexAccount: fedexAccount,
                        shippingAccount: fedexAccountShipping,
                        removedFedexAccount: removedFedexAccount,
                        ship_method: shippingMethodCode,
                        zipcode: customerPostcode,
                        region_id: customerRegionId,
                        city: customerCity,
                        street: street,
                        company: customerCompany,
                        isShippingPage: true,
                        account_payment_method: true,
                        ship_method_data: shipMethodData
                    };
                } else {
                    var pickupLocationId = jQuery('label.custom-radio-btn.pick-up-button input[name="radio-button"]:checked').
                        parent().find('span.pickup-location-id').text();
                    var pickupLocationOptionEnabled = $('div.pickup-location-list-container').is(":visible");

                    if (!pickupLocationOptionEnabled) {
                        pickupLocationId = null;
                    } else if (pickupLocationOptionEnabled && pickupLocationId == "") {
                        pickupLocationId = null;
                    }

                    var requestData = {
                        fedexAccount: fedexAccount,
                        shippingAccount: fedexAccountShipping,
                        removedFedexAccount: removedFedexAccount,
                        locationId: pickupLocationId,
                        requestedPickupLocalTime: pickupDateTimeForApi,
                        isPickupPage: true,
                        account_payment_method: true
                    };
                }
            } else {
                var requestData = {
                    fedexAccount: fedexAccount,
                    shippingAccount: fedexAccountShipping,
                    removedFedexAccount: removedFedexAccount,
                    requestedPickupLocalTime: pickupDateTimeForApi
                };
            }
            let pickupShippingComboKey;
            if(window.e383157Toggle){
                pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
            }else{
                pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
            }
            if (pickupShippingComboKey == 'true') {
                let locationId;
                if(window.e383157Toggle){
                    locationId = fxoStorage.get('locationId');
                }else{
                    locationId = localStorage.getItem('locationId');
                }
                requestData = { locationId, ...requestData };
            }

            $.ajax({
                url: urlBuilder.build(
                    requestUrl
                ),
                type: "POST",
                data: requestData,
                dataType: "json",
                showLoader: true,
                async: true
            }).done(function (response) {

                rateQuoteErrorsHandler.errorHandler(response, false);
                if (typeof response !== 'undefined' && response.length < 1) {
                    $('.error-container').removeClass('api-error-hide');
                    $(event.target).parents('.apply-field-group').find('.loadersmall').hide();
                    $(event.target).parents('.apply-field-group').find('button.submit.primary span').css('visibility', 'visible');

                    return true;
                } else if (response.hasOwnProperty("errors")) {
                    $('.error-container').removeClass('api-error-hide');
                    if (
                        typeof response.errors.is_timeout != 'undefined' &&
                        response.errors.is_timeout != null
                        ) {
                        window.location.replace(orderConfirmationUrl);
                    }
                    $(event.target).parents('.apply-field-group').find('.loadersmall').hide();
                    $(event.target).parents('.apply-field-group').find('button.submit.primary span').css('visibility', 'visible');

                    return true;
                }

                if (response.hasOwnProperty("free_shipping") && response.free_shipping.show_free_shipping_message){
                    $(".shipping-message-container").show();
                    $(".message-text > .discount-message").text(response.free_shipping.free_shipping_message);
                }
                    if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                        window.location.replace(orderConfirmationUrl);
                    }
                    response.rate = response.rateQuote;
                    response.rate.rateDetails = response.rateQuote.rateQuoteDetails;
                if (removedFedexAccount) {
                    if (window.e383157Toggle) {
                        fxoStorage.delete('fedexAccountApplied');
                    } else {
                        localStorage.removeItem('fedexAccountApplied');
                    }
                }
                $(event.target).parents('.apply-field-group').find('.loadersmall').hide();
                $(event.target).parents('.apply-field-group').find('button.submit.primary span').css('visibility', 'visible');
                if (response.hasOwnProperty("alerts") && response.alerts.length > 0) {
                    let accountDiscount = false;
                    let couponDiscount = false;
                    response.rate.rateDetails.forEach((rateDetail) => {
                        if (typeof rateDetail.discounts != "undefined") {
                            rateDetail.discounts.forEach((discounts) => {
                                if (typeof discounts.type != undefined && discounts.type == "AR_CUSTOMERS") {
                                    accountDiscount = true;
                                }
                                if (typeof discounts.type != undefined && discounts.type == "CORPORATE") {
                                    accountDiscount = true;
                                }
                                if (typeof discounts.type != undefined && discounts.type == "COUPON") {
                                    couponDiscount = true;
                                }
                            });
                        }
                    });
                    if (accountDiscount) {
                        let couponCode;
                        if(window.e383157Toggle){
                            couponCode = fxoStorage.get('coupon_code');
                        }else{
                            couponCode = localStorage.getItem('coupon_code');
                        }
                        if (!couponDiscount && couponCode) {
                            $('.promo-code-block-applied').hide();
                            $('.promo-code-block').css('display', 'block');
                            $('.form-error-message').css('display', 'block');
                            $('.form-error-message').html('<span class="checkout-error-cross-icon">X</span>Promo code invalid. Please try again.').fadeIn().delay(5000).fadeOut();
                            $('#coupon_code').val('');
                            // $('.warning-message-container').show();
                            if(window.e383157Toggle){
                                fxoStorage.set('coupon_code', '');
                            }else{
                                localStorage.setItem('coupon_code', '');
                            }

                            window.dispatchEvent(new Event('nonCombinableDiscount'));

                        }

                        $(".fedex-account-block-applied").show();
                        $(".fedex-account-block").hide();
                        if (self.fedexAccountNumber().length > 0) {
                            self.isFedexAccount(true);
                            self.showFedexAccount(false);
                            var masked = fedexAccount.length > 4 ? "*" + fedexAccount.substr(-4) : fedexAccount;
                            self.fedexAccountNumber(masked);
                            if(window.e383157Toggle){
                                fxoStorage.set('fedexAccount',fedexAccount);
                                fxoStorage.set('fedexAccountApplied',true);
                            }else{
                                localStorage.setItem('fedexAccount', fedexAccount);
                                localStorage.setItem('fedexAccountApplied', true);
                            }
                            window.dispatchEvent(new Event('promoCode'));
                        }
                    } else {
                        $('.form-error-message-account').html('<span class="checkout-error-cross-icon">X</span>The account number entered is invalid.').
                        fadeIn().delay(5000).
                        fadeOut();
                        self.fedexAccountNumber('');
                        $('#fedex_account_no').parent().addClass('fedex-account-error');
                        setTimeout(function () {
                            jQuery('.fedex-account-error').removeClass('fedex-account-error');
                        }, 5000);
                        $('.loadersmall').hide();
                        $('#fedex_account_no').val('');
                        $(".fedex-account-block-applied").css("display", "none");
                        if(window.e383157Toggle){
                            fxoStorage.delete('fedexAccountApplied');
                        }else{
                            localStorage.removeItem('fedexAccountApplied');
                        }
                        if (!$(".applied-account-four-digit").text()) {
                            $('.fedex-account-container .fedex-account-title').css("display", "block");
                        }
                        if ($(".applied-account-four-digit").text()) {
                            return false;
                        }
                    }
                }

                /**
                 * Checks if current logged in user is FCL or not
                 */
                let isFclCustomer = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? typeof (window.checkoutConfig.is_fcl_customer) !== "undefined" && window.checkoutConfig.is_fcl_customer !== null ? window.checkoutConfig.is_fcl_customer : false : false;

                let isFedexAccountFieldVisible = $(".account-num-container #fedex-account-number").is(':visible');

                if (isFclCustomer) {
                    if (isFedexAccountFieldVisible) {
                        $('input.fedex-account-number').val(fedexAccount);
                        $('input.fedex-account-number').trigger('blur');
                    }
                } else {
                    if (isFedexAccountFieldVisible) {
                        $('input.fedex-account-number').val(fedexAccount);
                        $('input.fedex-account-number').trigger('blur');
                    }
                }

                let ddlFedexAccountText = $(".fedex-account-value[data-value='"+fedexAccount+"']").text();
                if (ddlFedexAccountText) {
                    $(".fedex-account-value[data-value='"+fedexAccount+"']").trigger('click');
                } else {
                    if(isFedexAccountFieldVisible) {
                        $('input.fedex-account-number').val(fedexAccount);
                        $('input.fedex-account-number').trigger('blur');
                    }
                }

                if(ddlFedexAccountText) {
                    $(".fedex-account-show").text(ddlFedexAccountText);
                }
                if(window.e383157Toggle){
                    fxoStorage.set('selectedfedexAccount',fedexAccount);
                }else{
                    localStorage.setItem('selectedfedexAccount',fedexAccount);
                }
                window.checkoutConfig.fedex_account_number = fedexAccount;
                if(window.e383157Toggle){
                    fxoStorage.set('fedexAccount', fedexAccount);
                }else{
                    localStorage.setItem('fedexAccount', fedexAccount);
                }
                window.dispatchEvent(new Event('promoCode'));
                let isPaymentData,paymentData;
                if(window.e383157Toggle){
                    isPaymentData = fxoStorage.get('paymentData') ?? false;
                }else{
                    isPaymentData = (typeof (localStorage.getItem('paymentData')) !== "undefined" &&
                        localStorage.getItem('paymentData') != null);
                }

                if (stepNavigator.getActiveItemIndex() === 2 && isPaymentData) {
                    if(window.e383157Toggle){
                        paymentData = fxoStorage.get('paymentData');
                    }else{
                        paymentData = localStorage.getItem('paymentData');
                        paymentData = JSON.parse(paymentData);
                    }
                    if (paymentData.paymentMethod == "cc" && removedFedexAccount) {
                        paymentData.fedexAccountNumber = "";
                        paymentData.isFedexAccountApplied = false;
                        if(window.e383157Toggle){
                            fxoStorage.set('paymentData', paymentData);
                        }else{
                            localStorage.setItem('paymentData', JSON.stringify(paymentData));
                        }
                        $(".pay-by-card-container .account-number-with-cc").hide();
                    }
                    else if (paymentData.paymentMethod == "cc") {
                        paymentData.fedexAccountNumber = fedexAccount.toString();
                        paymentData.isFedexAccountApplied = true;
                        if(window.e383157Toggle){
                            fxoStorage.set('paymentData', paymentData);
                        }else{
                            localStorage.setItem('paymentData', JSON.stringify(paymentData));
                        }
                        $(".pay-by-card-container .card-number-ending").next("div").show();
                        let maskedAccount = "*" + fedexAccount.substring(fedexAccount.length - 4);
                        $(".pay-by-card-container .account-number-with-cc").children("div").next("div").html(maskedAccount);
                    }
                }
                if (response && response.alerts && response.alerts.length > 0) {
                    $('.account-number').addClass('contact-error');
                    $(".invalid-account-error").text("The account number entered is invalid.");
                    $(".invalid-account-error").show();
                } else {
                    if (self.fedexAccountNumber().length > 0) {
                        self.isFedexAccount(true);
                        self.showFedexAccount(false);
                        var masked = fedexAccount.length > 4 ? "*" + fedexAccount.substr(-4) : fedexAccount;
                        self.fedexAccountNumber(masked);
                        if(window.e383157Toggle){
                            fxoStorage.set('fedexAccount', fedexAccount);
                        }else{
                            localStorage.setItem('fedexAccount', fedexAccount);
                        }
                        window.dispatchEvent(new Event('promoCode'));
                    }

                    if (event.target.id == removeFedexAccountId) {
                        if(window.e383157Toggle){
                            fxoStorage.delete('fedexAccount');
                        }else{
                            localStorage.removeItem('fedexAccount');
                        }
                        self.isFedexAccount(false);
                        self.showFedexAccount(false);
                        self.fedexAccountAppliedNumber('');
                        if (stepNavigator.getActiveItemIndex() == 2 && paymentData.paymentMethod != 'cc') {
                            stepNavigator.navigateTo('step_code', 'paymentStep');
                            // summary button start
                            if (
                                window.checkoutConfig.is_sde_store !== true &&
                                window.checkoutConfig.is_commercial !== true
                            ) {
                                if ($(".credit-card-review-button").is(':visible') && typeof ($(".credit-card-review-button").attr("disabled")) == "undefined") {
                                    $("#credit-card-review-order-button").show().prop("disabled", false);
                                }
                                if ($(".fedex-account-number-review-button").is(':visible') && typeof ($(".fedex-account-number-review-button").attr("disabled")) == "undefined") {
                                    $("#fedex-pay-review-order-button").show().prop("disabled", false);
                                }
                            }
                            // summary button end
                            let expressCheckout;
                            if (window.e383157Toggle) {
                                expressCheckout = fxoStorage.get('express-checkout');
                            } else {
                                expressCheckout = localStorage.getItem("express-checkout")
                            }
                            if (expressCheckout) {
                                if (!$(".select-fedex-acc.pointer").hasClass("selected-paymentype")) {
                                    $(".payment-container .select-fedex-acc.pointer").trigger("click");
                                }
                            }
                        }
                    }
                }

                const calculate = shippingService.calculateDollarAmount(response.rate);
                var shippingAmount = shippingService.getShippingLinePrice(response.rate);

                const stringToFloat = function (stringAmount) {
                    return parseFloat(stringAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                };

                const discountCalculate =  function (discountValue) {
                    let discountPrice = 0.00;
                    if (typeof discountValue == 'string') {
                        discountPrice += stringToFloat(discountValue);
                    } else {
                        discountPrice += parseFloat(discountValue);
                    }
                    return discountPrice;
                };

                var shippingAmount = 0;
                var grossAmount = 0;
                var totalDiscountAmount = 0;
                var totalNetAmount = 0;
                var estimatedShippingTotal = $t('TBD');
                var discountResult = [];
                var netAmountResult = [];
                var promoDiscountAmount = 0;
                var accountDiscountAmount = 0;
                var volumeDiscountAmount = 0;
                var bundleDiscountAmount = 0;
                var shippingDiscountAmount = 0.00;
                if (typeof (response) != "undefined" && typeof (response.rate.rateDetails) != "undefined") {
                    if (window.checkoutConfig.hco_price_update && response.rate.rateDetails[0].productLines != undefined) {
                        var productLines = response.rate.rateDetails[0].productLines;
                        productLines.forEach((productLine) => {
                            var instanceId = productLine.instanceId;
                            var itemRowPrice = productLine.productRetailPrice;
                            itemRowPrice = self.priceFormatWithCurrency(itemRowPrice);
                            $(".subtotal." + instanceId + " .cart-price .price").html(itemRowPrice);
                            $(".subtotal-instance").show();
                            $(".checkout-normal-price").hide();
                        }
                        )
                    }
                    response.rate.rateDetails.forEach((rateDetail) => {
                        if (typeof rateDetail.deliveryLines != "undefined") {
                            rateDetail.deliveryLines.forEach((deliveryLine) => {
                                if(typeof deliveryLine.deliveryLineDiscounts != "undefined"){
                                    var shippingDiscountPrice = 0;
                                    deliveryLine.deliveryLineDiscounts.forEach((deliveryLineDiscount) => {
                                        if (deliveryLineDiscount['type'] == 'COUPON') {
                                            shippingDiscountPrice += discountCalculate(deliveryLineDiscount['amount']);
                                        }
                                    });
                                    shippingDiscountAmount = shippingDiscountPrice;
                                }
                            });
                        }

                        if (typeof rateDetail.productLines != "undefined") {
                            rateDetail.productLines.forEach((productLine) => {
                                grossAmount += rateResponseHandler.getGrossAmount(productLine, grossAmount);
                            });
                        }

                        if (typeof rateDetail.discounts != "undefined") {
                            discountResult = rateResponseHandler.getTotalDiscountAmount(rateDetail, totalDiscountAmount, promoDiscountAmount, accountDiscountAmount, volumeDiscountAmount, bundleDiscountAmount, discountResult);
                            totalDiscountAmount = discountResult['totalDiscountAmount'];
                            accountDiscountAmount = discountResult['accountDiscountAmount'];
                            volumeDiscountAmount = discountResult['volumeDiscountAmount'];
                            bundleDiscountAmount = discountResult['bundleDiscountAmount'];
                            if (shippingDiscountAmount == 0) {
                                promoDiscountAmount = discountResult['promoDiscountAmount'];
                            } else {
                                promoDiscountAmount = discountResult['promoDiscountAmount']-shippingDiscountAmount;
                            }
                        }

                        if (typeof rateDetail.totalAmount != "undefined") {
                            netAmountResult = rateResponseHandler.getTotalNetAmount(rateDetail, totalNetAmount, estimatedShippingTotal, netAmountResult);
                            totalNetAmount = netAmountResult['totalNetAmount'];
                            estimatedShippingTotal = netAmountResult['estimatedShippingTotal'];
                        }
                        if(rateDetail.deliveriesTotalAmount) {
                            shippingAmount = rateDetail.deliveriesTotalAmount;
                        }
                    });
                    if(shippingAmount) {
                        if(window.e383157Toggle){
                            fxoStorage.set("marketplaceShippingPrice",shippingAmount);
                        }else{
                            localStorage.setItem('marketplaceShippingPrice', shippingAmount);
                        }
                        var formattedshippingAmount = priceUtils.formatPrice(shippingAmount, quote.getPriceFormat());
                        $(".totals.shipping.excl .price").text(formattedshippingAmount);
                        $(".grand.totals.excl .amount .price").text(formattedshippingAmount);
                    }
                    //removing marketplaceShippingPrice from local storage
                    else{
                        if(window.e383157Toggle){
                            fxoStorage.delete("marketplaceShippingPrice");
                        }else{
                            localStorage.removeItem('marketplaceShippingPrice');
                        }
                    }
                }

                if(window.e383157Toggle){
                    fxoStorage.set("TaxAmount", calculate("taxAmount"));
                }else{
                    localStorage.setItem("TaxAmount", calculate("taxAmount"));
                }
                totalNetAmount = self.priceFormatWithCurrency(totalNetAmount);
                var cartPrice = self.priceFormatWithCurrency(response.rate.rateDetails[0].grossAmount);
                grossAmount = self.priceFormatWithCurrency(grossAmount);
                var taxAmount = self.priceFormatWithCurrency(calculate("taxAmount"));
                if(window.e383157Toggle){
                    fxoStorage.set("EstimatedTotal", totalNetAmount);
                }else{
                    localStorage.setItem("EstimatedTotal", totalNetAmount);
                }
                $(".grand.totals.incl .price").text(totalNetAmount);
                $(".grand.totals .amount .price").text(totalNetAmount);
                $(".totals.sub .amount .price").text(grossAmount);

                if(totalDiscountAmount){
                    totalDiscountAmount = self.priceFormatWithCurrency(totalDiscountAmount);
                    $(".totals.fedexDiscount .amount .price").text('-'+totalDiscountAmount);
                } else {
                    $(".totals.fedexDiscount .amount .price").text('-');
                }
                $(".totals-tax .price").text(taxAmount);
                let accountDiscountHtml = '';

                if (accountDiscountAmount || volumeDiscountAmount || bundleDiscountAmount || promoDiscountAmount || shippingDiscountAmount) {
                    $(".discount_breakdown tbody tr.discount").remove();
                }
                if (accountDiscountAmount == 0 && volumeDiscountAmount == 0 && bundleDiscountAmount == 0 && promoDiscountAmount == 0 && shippingDiscountAmount == 0) {
                    $('.toggle-discount th #discbreak').remove();
                }

                let discountAmounts = [{"type":"promo_discount","price":promoDiscountAmount,"label":"Promo Discount"},{"type":"account_discount","price":accountDiscountAmount,"label":"Account Discount"},{"type":"volume_discount","price":volumeDiscountAmount,"label":"Volume Discount"},{"type":"shipping_discount","price":shippingDiscountAmount,"label":"Shipping Discount"}];
                if (togglesAndSettings.isToggleEnabled('tiger_e468338')) {
                    discountAmounts = [
                        {
                            "type": "promo_discount",
                            "price": promoDiscountAmount,
                            "label": "Promo Discount"
                        }, {
                            "type": "account_discount",
                            "price": accountDiscountAmount,
                            "label": "Account Discount"
                        }, {
                            "type": "bundle_discount",
                            "price": bundleDiscountAmount,
                            "label": "Bundle Discount"
                        }, {
                            "type": "volume_discount",
                            "price": volumeDiscountAmount,
                            "label": "Volume Discount"
                        }, {
                            "type": "shipping_discount",
                            "price": shippingDiscountAmount,
                            "label": "Shipping Discount"
                        }
                    ];
                }

                let sortedAmounts = discountAmounts.sort((p1, p2) => (p1.price < p2.price) ? 1 : (p1.price > p2.price) ? -1 : 0);
                sortedAmounts.forEach(function (amount, index) {
                    if (amount.price) {
                        accountDiscountHtml = '<tr class="' + amount.type + ' discount"><th class="mark" scope="row">' + amount.label + '</th><td class="amount"><span class="price">-' + self.priceFormatWithCurrency(amount.price);
                        +'</span></td></tr>';
                        $(".discount_breakdown tbody").append(accountDiscountHtml);
                        if ($('.toggle-discount th #discbreak').length == 0) {
                            $('.toggle-discount th').append('<span id="discbreak" tabindex="0" class="arrow down"></span>');
                        }
                    } else {
                        $(".discount_breakdown tbody tr." + amount.type).remove();
                    }
                });

                //D-97499 Estimated shipping total issue fix while applying/removing fedex account number
                //B-1309407 remove sde check and make it as shipping account placement toggle
                shippingFormAdditionalScript.handleEstimatedShippingTotal(estimatedShippingTotal);

            });

            $('.opc-block-summary .table-totals').attr('style', 'display: inline-table;');

            return false;
        },

        priceFormatWithCurrency: function (price) {

            let formattedPrice = '';

            if (typeof (price) == 'string') {
                formattedPrice = price.replaceAll('$', '').replaceAll(',', '').replaceAll('(', '').replaceAll(')', '');
                formattedPrice = priceUtils.formatPrice(formattedPrice, quote.getPriceFormat());
            } else {

                formattedPrice = priceUtils.formatPrice(price, quote.getPriceFormat());
            }

            return formattedPrice;
        },

        /**
         * B-1294484: Can Show the Fedex Account Remove Button
         *
         * @return bool
         */
        canShowFedexAccountRemoveBtn: function () {
            var isCommercial =  window.checkoutConfig.is_commercial;
            var fedexAccountNumber = window.checkoutConfig.fedex_account_number ? window.checkoutConfig.fedex_account_number : window.checkoutConfig.fedex_account_number_discount;
            var companyFxoAccountNumber = window.checkoutConfig.company_fxo_account_number;
            var companyDiscountAccountNumber = window.checkoutConfig.company_discount_account_number;
            if (companyFxoAccountNumber && companyFxoAccountNumber === fedexAccountNumber) {

                return !(isCommercial && window.checkoutConfig.fxo_account_number_editable === "0")
            } else if(companyDiscountAccountNumber && companyDiscountAccountNumber === fedexAccountNumber) {

                return !(isCommercial && window.checkoutConfig.discount_account_number_editable === "0")
            }

            return true;
        },

        /**
         * Is account discount enabled
         *
         * @return {boolean}
         */
        isAccountDiscountEnabled: function () {
            return !!window.checkoutConfig.account_discount_enabled;
        },

        shouldShowAddFedexAccountField: function () {
            const isEligibleCustomer = !this.isOnlyThreeProduct() || this.isSelfregCustomer();
            const shouldShow = !window.checkoutConfig.is_commercial || isEligibleCustomer;
            return shouldShow && this.isAccountDiscountEnabled();
        },
    });
});
