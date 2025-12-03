/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/summary',
    'mage/url',
    'Magento_Checkout/js/model/step-navigator',
    'shippingFormAdditionalScript',
    'checkout-common',
    'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
    'Fedex_Customer/js/checkout/model/marketing-opt-in',
    'Magento_Customer/js/customer-data',
    'fedex/storage',
    "Fedex_Recaptcha/js/reCaptcha",
    'Magento_Ui/js/modal/modal',
    'Fedex_Delivery/js/model/campaign-ad-disclosure'
], function (
    $,
    ko,
    Component,
    urlBuilder,
    stepNavigator,
    shippingFormAdditionalScript,
    checkoutCommon,
    quoteHelper,
    marketingOptInBuilder,
    customerData,
    fxoStorage,
    reCaptcha,
    modal,
    campaignAdDisclosureModel
) {
    'use strict';

    let isCheckoutConfig = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;

    let explorersD193256Fix = false;
    let techTitansB2179775 = false;
    let techTitansProductionLocationFix = false;
    let tigerB2384493 = false;

    if(isCheckoutConfig) {
        explorersD193256Fix = typeof (window.checkoutConfig.explorers_d_193256_fix) !== "undefined" && window.checkoutConfig.explorers_d_193256_fix !== null ? window.checkoutConfig.explorers_d_193256_fix : false;
        techTitansB2179775 = typeof (window.checkoutConfig.tech_titans_b_2179775) !== "undefined" && window.checkoutConfig.tech_titans_b_2179775 !== null ? window.checkoutConfig.tech_titans_b_2179775 : false;
        techTitansProductionLocationFix = typeof (window.checkoutConfig.tech_titans_d_205447_fix) != 'undefined' && window.checkoutConfig.tech_titans_d_205447_fix != null ? window.checkoutConfig.tech_titans_d_205447_fix : false;
        tigerB2384493 = typeof (window.checkoutConfig.tiger_b2384493) != 'undefined' && window.checkoutConfig.tiger_b2384493 != null ? window.checkoutConfig.tiger_b2384493 : false;
    }

    async function addRecaptchaToken(c_payload, actionName) {
        if (tigerB2384493) {
            let recaptchaToken = await reCaptcha.generateRecaptchaToken(actionName);
            c_payload['g-recaptcha-response'] = recaptchaToken;
        }
    }

    return Component.extend({
        defaults: {
            template: 'Fedex_SubmitOrderSidebar/summary',
            imports: {
                chosenDeliveryMethod: "checkout.steps.shipping-step.shippingAddress:chosenDeliveryMethod"
            }
        },

        isVisible: function () {
            return stepNavigator.isProcessed('step_code');
        },

        isMixedQuote: function() {
            return quoteHelper.isMixedQuote();
        },

        initialize: function () {
            this._super();
            var self = this;
            var isSdeStore = shippingFormAdditionalScript.isSdeStore();
            var isSelfregCustomer = window.checkoutConfig.is_selfreg_customer;

            // B-1415208 : Make terms and conditions mandatory
            $(document).on('click', '.checkout-agreements input.agreement_enable', function () {
                shippingFormAdditionalScript.hasAgreedToTermsAndConditions();
            });

            var isCheckoutConfig = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;
            var isTermsAndConditionsStatus = false;
            if (isCheckoutConfig) {
                isTermsAndConditionsStatus = typeof (window.checkoutConfig.terms_and_conditions_enabled) !== "undefined" && window.checkoutConfig.terms_and_conditions_enabled !== null ? window.checkoutConfig.terms_and_conditions_enabled : false;
            }

            // Submit order on button click
            $(document).on('click', '.action.primary.submit-btn', function () {
                $('.checkout-error').addClass('api-error-hide');
                if (isTermsAndConditionsStatus) {
                    // B-1415208 : Make terms and conditions mandatory
                    if (shippingFormAdditionalScript.hasAgreedToTermsAndConditions() === true) {
                        self.placeOrderSubmit();
                    }
                } else {
                    self.placeOrderSubmit();
                }
            });

            $(document).on('click', '.edit_buttton', function () {
                $('.checkout-error').addClass('api-error-hide');
            });

            $(window).on("load", function () {
                if (window.e383157Toggle) {
                    fxoStorage.delete('orderInProgress');
                } else {
                    localStorage.removeItem('orderInProgress');
                }
            });

            $(document).ready(function () {
                var totalNetAmount;
                if(window.e383157Toggle){
                    fxoStorage.delete('orderInProgress');
                    totalNetAmount= fxoStorage.get("EstimatedTotal");
                }else{
                    localStorage.removeItem('orderInProgress');
                    totalNetAmount = localStorage.getItem("EstimatedTotal");
                }
                if (totalNetAmount) {
                    setTimeout(() => {
                        $(".grand.totals.incl .price").text(totalNetAmount);
                        $(".grand.totals .amount .price").text(totalNetAmount);
                    }, 5000);
                }
            });

            return this;
        },

        // send payload by hitting controller with ajax
        placeOrderSubmit: async function () {
            $('.action.primary.submit-btn').attr('disabled', 'true');
            let paymentData;
            let paymentDataParsed;
            if(window.e383157Toggle){
                paymentDataParsed = fxoStorage.get("paymentData");
                paymentData = JSON.stringify(paymentDataParsed);
            } else {
                paymentData = localStorage.getItem('paymentData');
                paymentDataParsed = JSON.parse(paymentData);
            }
            if (typeof (paymentDataParsed.paymentMethod) != 'undefined'
                && paymentDataParsed.paymentMethod != null
                && paymentDataParsed.paymentMethod == 'cc') {
                paymentDataParsed.billingAddress.company = encodeURIComponent(paymentDataParsed.billingAddress.company);
                //D-96366 Billing address street shows as undefined when same as shipping is selected
                if (typeof (paymentDataParsed.billingAddress.address) != 'undefined') {
                    paymentDataParsed.billingAddress.address = encodeURIComponent(paymentDataParsed.billingAddress.address);
                } else if (typeof (paymentDataParsed.billingAddress.street[0]) != 'undefined') {
                    paymentDataParsed.billingAddress.address = paymentDataParsed.billingAddress.street[0];
                } else {
                    paymentDataParsed.billingAddress.address = null;
                }
                if (typeof (paymentDataParsed.billingAddress.addressTwo) != 'undefined') {
                    paymentDataParsed.billingAddress.addressTwo = encodeURIComponent(paymentDataParsed.billingAddress.addressTwo);
                } else if (typeof (paymentDataParsed.billingAddress.street[1]) != 'undefined') {
                    paymentDataParsed.billingAddress.addressTwo = paymentDataParsed.billingAddress.street[1];
                } else {
                    paymentDataParsed.billingAddress.addressTwo = null;
                }
                paymentDataParsed.billingAddress.city = encodeURIComponent(paymentDataParsed.billingAddress.city);
                paymentDataParsed.nameOnCard = encodeURIComponent(paymentDataParsed.nameOnCard);
                paymentData = JSON.stringify(paymentDataParsed);
            }

            var isShip,isPick,estimatedPickupTime,pickupDetails,useSiteCreditCard,customBillingFields,encPaymentData,altContactInfo;
            if(window.e383157Toggle){
                encPaymentData = fxoStorage.get('encryptedPaymentData');
                isShip = fxoStorage.get("shipkey");
                isPick = fxoStorage.get("pickupkey");
                pickupDetails = fxoStorage.get('pickupData');
                estimatedPickupTime = fxoStorage.get('pickupDateTime');
                useSiteCreditCard = fxoStorage.get('useSiteCreditCard');
                customBillingFields = fxoStorage.get('customBillingData');
                if(explorersD193256Fix) {
                    altContactInfo = fxoStorage.get("altContactInfo");
                }

            }else{
                encPaymentData = localStorage.getItem('encryptedPaymentData');
                isShip = localStorage.getItem("shipkey");
                isPick = localStorage.getItem("pickupkey");
                pickupDetails = JSON.parse(localStorage.getItem('pickupData'));
                estimatedPickupTime = localStorage.getItem('pickupDateTime');
                // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
                useSiteCreditCard = localStorage.getItem('useSiteCreditCard');
                customBillingFields = localStorage.getItem('customBillingData');
                if(explorersD193256Fix) {
                    altContactInfo = localStorage.getItem("altContactInfo");
                }
            }

            let orderInProgress;
            if(window.e383157Toggle){
                orderInProgress = fxoStorage.get("orderInProgress");
            }else{
                orderInProgress = localStorage.getItem("orderInProgress");
            }

            if (orderInProgress != 'true' || orderInProgress != true) {
                if (window.e383157Toggle) {
                    fxoStorage.set("orderInProgress", true);
                } else {
                    localStorage.setItem("orderInProgress", true);
                }
                if (this.isMixedQuote() && this.chosenDeliveryMethod === 'pick-up') {
                    isPick = true;
                    isShip = false;
                }

                var tiger_203990 = typeof window.checkoutConfig.tiger_d203990 != 'undefined' ? window.checkoutConfig.tiger_d203990 : false;
                var url = (isShip === 'true' && isPick === 'false') ? "submitorder/quote/submitorderoptimized?pickstore=0" : "submitorder/quote/submitorderoptimized?pickstore=1";

                if ((isShip === 'false' && isPick === 'true')) {
                    pickupDetails.addressInformation.pickup_location_name = encodeURIComponent(pickupDetails.addressInformation.pickup_location_name);
                    pickupDetails.addressInformation.pickup_location_street = encodeURIComponent(pickupDetails.addressInformation.pickup_location_street);
                    pickupDetails.addressInformation.estimate_pickup_time = estimatedPickupTime;
                    let pickupDateTimeForApi;
                    if(window.e383157Toggle){
                        pickupDateTimeForApi = fxoStorage.get("pickupDateTimeForApi");
                    }else{
                        pickupDateTimeForApi = localStorage.getItem("pickupDateTimeForApi");
                    }
                    let updatedChangedPickupDateTime;
                    if(window.e383157Toggle){
                        updatedChangedPickupDateTime = fxoStorage.get("updatedChangedPickupDateTime");
                    }else{
                        updatedChangedPickupDateTime = localStorage.getItem("updatedChangedPickupDateTime");
                    }
                    if(updatedChangedPickupDateTime){
                        pickupDetails.addressInformation.estimate_pickup_time_for_api =  pickupDateTimeForApi;
                    }
                    else{
                        pickupDetails.addressInformation.estimate_pickup_time_for_api =  null;
                    }
                    pickupDetails = JSON.stringify(pickupDetails);
                } else if(tiger_203990 && (isShip === 'true' && isPick === 'false')) {
                    pickupDetails = null;
                }
                let selectedProductionId = null;
                if (techTitansProductionLocationFix && fxoStorage.get("selected_production_id") !== undefined && fxoStorage.get("selected_production_id") !== '') {
                    selectedProductionId = fxoStorage.get("selected_production_id");
                }
                var c_payload = {
                    paymentData: paymentData,
                    encCCData: encodeURIComponent(encPaymentData),
                    pickupData: pickupDetails,
                    useSiteCreditCard: useSiteCreditCard,
                    billingFields: customBillingFields,
                    altContactInfo: explorersD193256Fix ? altContactInfo : null,
                    selectedProductionId: selectedProductionId
                }

                if(campaignAdDisclosureModel.isCampaingAdDisclosureToggleEnable && campaignAdDisclosureModel.shouldSendPayloadOnSubmit() === true) {
                    c_payload['political_campaign_disclosure'] = {
                        'candidate_pac_ballot_issue': campaignAdDisclosureModel.candidatePacBallotIssue(),
                        'election_date': campaignAdDisclosureModel.electionDate(),
                        'sponsoring_committee': campaignAdDisclosureModel.sponsoringCommittee(),
                        'address_street_lines': campaignAdDisclosureModel.addressLine1() + ' ' + campaignAdDisclosureModel.addressLine2(),
                        'city': campaignAdDisclosureModel.city(),
                        'zip_code': campaignAdDisclosureModel.zipCode(),
                        'region_id': campaignAdDisclosureModel.state(),
                        'email': customerData.get('checkout-data')()['shippingAddressFromData']?.custom_attributes?.email_id ||
                            fxoStorage.get("pickupData")?.contactInformation?.contact_email
                    }
                }

                c_payload.marketingOptIn = marketingOptInBuilder.build();

                await addRecaptchaToken(c_payload, 'checkout_order');

                var submitOrderData = null;
                submitOrderData = JSON.stringify(c_payload).replaceAll('&', encodeURIComponent('&'));

                $.ajax({
                    url: urlBuilder.build(
                        url
                    ),
                    type: "POST",
                    data: "data=" + submitOrderData,
                    dataType: "json",
                    showLoader: true,
                    async: true,
                    complete: function () {
                        showLoader: false;
                    }
                }).done(function (response) {
                    if (window.checkoutConfig.promise_time_warning_enabled) {
                        if (response[0] && response[0].error && response[0].estimateTimeMismatch) {
                            $('.updated-time').text(response[0].estimatedDeliveryLocalTime);
                            var modalOptions = {modalClass: 'fedex-promise-time-modal',title: '',buttons: [] };
                            //Handle Pickup Time Mismatch
                            if (response[0].isPick) {
                                $('#time-change-modal-pickup').modal(modalOptions).modal('openModal');
                            } else if (response[0].isShip) {
                                $('#time-change-modal-shipping').modal(modalOptions).modal('openModal');
                            }
                            return true;
                        }
                    }
                    if (tigerB2384493 && response?.status === 'recaptcha_error') {
                        const errorTitle = "Processing Error";
                        const errorMsgDetails = "Payment processing failed. Please try again.";
                        $('#place-order-trigger-wrapper').removeAttr('disabled');
                        $('.action.primary.submit-btn').removeAttr('disabled');
                        showLoader: false;
                        $('.checkout-error .error-title').text(errorTitle);
                        $('.checkout-error .error-details').text(errorMsgDetails);
                        $('.checkout-error').removeClass('hide');
                        $('.checkout-error').removeClass('api-error-hide').find(".error-response").trigger('focus');
                        return true;
                    }
                    if(window.e383157Toggle){
                        fxoStorage.delete('gdl-event-added');
                    }else{
                        localStorage.removeItem('gdl-event-added');
                    }
                    localStorage.setItem('unified_data_layer', JSON.stringify(response.unified_data_layer || {}));
                    var checkoutCcFriendlyMessageToggle = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;

                    if (checkoutCcFriendlyMessageToggle) {
                        checkoutCcFriendlyMessageToggle = typeof (window.checkoutConfig.tiger_customer_friendly_cc_msg_toggle) !== "undefined" && window.checkoutConfig.tiger_customer_friendly_cc_msg_toggle !== null ? window.checkoutConfig.tiger_customer_friendly_cc_msg_toggle : false;
                    }

                    var fedexAccountWarnings = [
                        "PAYMENT.ACCOUNTNUMBER.INACTIVE",
                        "PAYMENT.ACCOUNTNUMBER.INVALID",
                        "PAYMENT.ACCOUNT.INVOICE_NOT_SUPPORTED",
                        "ACCOUNTVALIDATION.SERVICE.BADREQUEST",
                        "ACCOUNTVALIDATION.SERVICE.UNAUTHORIZED",
                        "ACCOUNTVALIDATION.SERVICE.NOTFOUND",
                        "ACCOUNTVALIDATION.SERVICE.READ.TIMEOUT",
                        "ACCOUNTVALIDATION.SERVICE.CONNECTION.TIMEOUT",
                        "ACCOUNTVALIDATION.SERVICE.FAILURE",
                        "ACCOUNTVALIDATION.SERVICE.UNAVAILABLE",
                        "ACCOUNTVALIDATION.SERVICE.ERROR",
                        "PAYMENT.ACCOUNT.INACTIVE", // Not returned by transaction CXS
                        "PAYMENT.ACCOUNT.INVALID",  // Not returned by transaction CXS
                        "PAYMENT.ACCOUNTS.EMPTY",  // Not returned by transaction CXS
                    ];

                    var fedexAccountWarningsMessages = {
                        "PAYMENT.ACCOUNTNUMBER.INACTIVE": 'The account number entered is invalid.',
                        "PAYMENT.ACCOUNTNUMBER.INVALID": 'The account number entered is invalid.',
                        "PAYMENT.ACCOUNT.INVOICE_NOT_SUPPORTED": 'Please try another account number or alternative payment method.'
                    };

                    var cardTenderPosError = null,
                        responseCode = null,
                        accountNumberError = null,
                        errorTitle = "",
                        errorMsgDetails = "",
                        transactionId = '',
                        errorTransaction = '';

                    var fedexTransactionError = [
                        "SPOS.TENDER.200-150",
                        "SPOS.TENDER.200-229",
                        "SPOS.TENDER.200-250",
                        "SPOS.TENDER.200-261",
                        "TIMEOUT"
                    ];

                    var fedexCreditCardError = [
                        "SPOS.TENDER.200-202",
                        "SPOS.TENDER.200-203",
                        "SPOS.TENDER.200-211",
                        "SPOS.TENDER.200-231",
                        "SPOS.TENDER.200-233",
                        "SPOS.TENDER.200-254",
                        "SPOS.TENDER.200-106",
                        "SPOS.TENDER.200-287",
                    ];

                    const fedexTransactionErrorMessage = "Payment processing failed. Submit your transaction again.";
                    const fedexTransactionErrorTitle = "Processing Error";
                    const fedexCreditCardErrorMessage = "Check that your credit card and billing details are accurate and try again.";
                    const fedexCreditCardErrorTitle = "Credit Card Authorization Failed";
                    const fedexCCAVSErrorMessage = "There was an issue processing payment, please verify your information or contact your issuing bank.";


                    if (techTitansB2179775) {
                        if (typeof response[0] !== "undefined" && response[0] !== null) {
                            if (typeof response[0].response !== "undefined" && response[0].response !== null) {
                                if (typeof response[0].response.response !== "undefined" && response[0].response.response !== null) {
                                    let parsedResponse;
                                    let transactionId;

                                    try {
                                        if (response[0].msg === 'timeout' && response[0].error === 1) {
                                            cardTenderPosError = true;
                                            errorTitle = fedexTransactionErrorTitle;
                                            errorMsgDetails = fedexTransactionErrorMessage;
                                        } else {
                                            parsedResponse = response[0].response;
                                            let innerResponse = JSON.parse(parsedResponse.response);

                                            transactionId = innerResponse.transactionId;
                                            if (transactionId) {
                                                errorTransaction = 'Transaction ID: ' + transactionId;
                                            }

                                            if (Array.isArray(innerResponse.errors) && innerResponse.errors[0]?.code) {
                                                const responseCode = innerResponse.errors[0].code;
                                                    // Check for Credit card errors
                                                    if(responseCode.indexOf("SPOS.TENDER") > -1) {

                                                        if (fedexTransactionError.includes(responseCode)) {
                                                            cardTenderPosError = true;
                                                            errorTitle = fedexTransactionErrorTitle;
                                                            errorMsgDetails = fedexTransactionErrorMessage;

                                                        } else {
                                                            cardTenderPosError = true;
                                                            errorTitle = fedexCreditCardErrorTitle;
                                                            errorMsgDetails = fedexCreditCardErrorMessage;

                                                             if (responseCode === "SPOS.TENDER.200-287") {
                                                                cardTenderPosError = true;
                                                                errorTitle = fedexCreditCardErrorTitle;
                                                                errorMsgDetails = fedexCCAVSErrorMessage;
                                                            }
                                                        }
                                                    } else {  // Check for account number errors
                                                             accountNumberError = true;
                                                             const fxoMsg = innerResponse.errors[0]?.message;
                                                            if (fxoMsg) {
                                                                errorTitle = fxoMsg;
                                                            }
                                                            // Set the default message
                                                            errorMsgDetails = fedexAccountWarningsMessages['PAYMENT.ACCOUNT.INVOICE_NOT_SUPPORTED'];
                                                            Object.keys(fedexAccountWarningsMessages).forEach(accountWarningKey => {
                                                                if (responseCode === accountWarningKey) {
                                                                    errorMsgDetails = fedexAccountWarningsMessages[responseCode];
                                                                }
                                                            });
                                                        }
                                                }
                                        }
                                    } catch (error) {
                                        console.error("Invalid JSON in response:", error);
                                        return;
                                    }
                                }
                            }
                        }
                    } else {
                        if (typeof (response[0]) !== "undefined" && response[0] !== null) {
                            if (typeof (response[0].response) !== "undefined" && response[0].response !== null) {
                                if (typeof (response[0].response.response) !== "undefined" && response[0].response.response !== null) {
                                    transactionId = JSON.parse(response[0].response.response).transactionId;
                                    if (transactionId) {
                                        errorTransaction = 'Transaction ID: ' + transactionId;
                                    }
                                    if (Array.isArray(JSON.parse(response[0].response.response).errors) && JSON.parse(response[0].response.response).errors[0].code) {
                                        if (!checkoutCcFriendlyMessageToggle) {
                                            if (JSON.parse(response[0].response.response).errors[0].code == "SPOS.TENDER.200") {
                                                cardTenderPosError = true;
                                            }
                                        } else {
                                            responseCode = JSON.parse(response[0].response.response).errors[0].code;
                                            if (responseCode == "SPOS.TENDER.200-150" || responseCode == "SPOS.TENDER.200-250") {
                                                cardTenderPosError = true;
                                                errorTitle = "Processing Error";
                                                errorMsgDetails = "Payment processing failed. Please try again.";
                                            } else if (responseCode.indexOf("SPOS.TENDER.200") > -1) {
                                                cardTenderPosError = true;
                                                errorTitle = "Credit Card Authorization Failed";
                                                errorMsgDetails = "Please review the fields and check that your credit card details are accurate.";
                                            } else if (responseCode == "SPOS.TENDER.287") {
                                                cardTenderPosError = true;
                                                errorTitle = "Payment Authorization Failed";
                                                errorMsgDetails = "Please review your payment details and retry.";
                                            } else if (fedexAccountWarnings.indexOf(JSON.parse(response[0].response.response).errors[0].code) > -1) {
                                                accountNumberError = true;

                                                var fxoMsg = JSON.parse(response[0].response.response).errors[0].message;
                                                if(fxoMsg) {
                                                    errorTitle = fxoMsg;
                                                }
                                                Object.keys(fedexAccountWarningsMessages).forEach(accountWarningkey => {
                                                    if (responseCode == accountWarningkey) {
                                                        errorMsgDetails = fedexAccountWarningsMessages[responseCode];
                                                    };
                                                });

                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }



                    var ccValidationFlag = false;
                    var orderConfirmationResponse = (typeof (response[0]) !== "undefined" && response[0] !== null) ? response[0][0] : null;

                    campaignAdDisclosureModel.clearStorage();
                    try {
                        if (response[0].error == 2) {
                            var baseUrl = window.BASE_URL;
                            var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";
                            window.location.replace(orderConfirmationUrl);
                        }

                        if (response[0].error == 1) {
                            $('#place-order-trigger-wrapper').removeAttr('disabled');
                            $('.action.primary.submit-btn').removeAttr('disabled');
                        } else {
                            $('#place-order-trigger-wrapper').attr('disabled', 'true');
                        }
                        if (transactionId) {
                            errorTransaction = 'Transaction ID: ' + transactionId;
                        }
                        if (typeof (orderConfirmationResponse) !== "undefined" && orderConfirmationResponse !== null && response[0].error !== 1) {
                            /**
                             * D-180197
                             * Keep "CJTransactionData" in regular local storage
                             * due Commission Junction compatibility.
                             */
                            localStorage.setItem("CJTransactionData", JSON.stringify(orderConfirmationResponse));
                            if(window.e383157Toggle){
                                let cartObject = {}
                                let magentoStorage = localStorage.getItem("mage-cache-storage");
                                if (typeof magentoStorage == 'string') {
                                    magentoStorage = JSON.parse(magentoStorage);
                                }
                                if (typeof (magentoStorage.cart) !== "undefined" && magentoStorage.cart !== null) {
                                    cartObject = magentoStorage.cart;
                                }
                                if (typeof orderConfirmationResponse === 'string') {
                                    orderConfirmationResponse = JSON.parse(orderConfirmationResponse);
                                }
                                fxoStorage.set("orderTransactionData", orderConfirmationResponse);
                                //B-1275188 set rate quote response in localstorage
                                if (typeof response[0].rateQuoteResponse != 'undefined') {
                                    fxoStorage.set("rateQuoteData", response[0].rateQuoteResponse);
                                }
                                //B-1242824: Setting sde store identifier inorder to get the flag in the order confirmation page
                                fxoStorage.set("isSdeStore", shippingFormAdditionalScript.isSdeStore());
                                fxoStorage.set("TaxAmount", '');
                                fxoStorage.set("EstimatedTotal", '');
                                fxoStorage.set("selectedRadioShipping", '');
                                // Set cart item data in localstorage for order success page
                                fxoStorage.set("success-cart",  cartObject);
                            }else{
                                localStorage.setItem("orderTransactionData", JSON.stringify(orderConfirmationResponse));
                                //B-1275188 set rate quote response in localstorage
                                if (typeof response[0].rateQuoteResponse != 'undefined') {
                                    localStorage.setItem("rateQuoteData", JSON.stringify(response[0].rateQuoteResponse));
                                }
                                //B-1242824: Setting sde store identifier inorder to get the flag in the order confirmation page
                                localStorage.setItem("isSdeStore", shippingFormAdditionalScript.isSdeStore());
                                localStorage.setItem("TaxAmount", '');
                                localStorage.setItem("EstimatedTotal", '');
                                localStorage.setItem("selectedRadioShipping", '');
                                // Set cart item data in localstorage for order success page
                                localStorage.setItem("success-mage-cache-storage", localStorage.getItem("mage-cache-storage"));
                            }
                            var baseUrl = window.BASE_URL;
                            var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";
                            showLoader: false;
                            window.location.replace(orderConfirmationUrl);
                        } else if (Array.isArray(response[0].response.errors) && response[0].response.errors[0].code == 'FOCAL.SERVICE.UNAVAILABLE') {
                            errorTitle = "System error, Please try again.";
                            ccValidationFlag = true;
                            showLoader: false;
                            return true;
                        } else if (accountNumberError) {
                            if(errorMsgDetails === "") {
                                errorMsgDetails = "There is an issue with the account number entered. Please use a different account or payment method.";
                            }
                            errorTitle = "Payment Error"
                            ccValidationFlag = true;
                            showLoader: false;
                        } else if (cardTenderPosError) {
                            ccValidationFlag = true;
                            if (!checkoutCcFriendlyMessageToggle) {
                                errorTitle = 'Credit card authorization failed, Please try again.'
                            }
                            showLoader: false;
                            return true;
                        } else if (typeof (response[0].error) !== "undefined" && response[0].error !== null
                        && response[0].error == 1 && typeof (response[0].msg) !== "undefined"
                        && response[0].msg !== null && response[0].msg == 'timeout') {
                            // Set cart item data in localstorage for order success page
                            if(window.e383157Toggle){
                                let cartObject = {}
                                let magentoStorage = localStorage.getItem("mage-cache-storage");
                                if (typeof magentoStorage == 'string') {
                                    magentoStorage = JSON.parse(magentoStorage);
                                }
                                if (typeof (magentoStorage.cart) !== "undefined" && magentoStorage.cart !== null) {
                                    cartObject = magentoStorage.cart;
                                }
                                fxoStorage.set("success-cart",  cartObject);
                            }else{
                                localStorage.setItem("success-mage-cache-storage", localStorage.getItem("mage-cache-storage"));
                            }
                            errorTitle = "System error, Please try again.";
                            ccValidationFlag = true;
                            showLoader: false;
                            return true;
                        } else {
                            errorTitle = "System error, Please try again.";
                            ccValidationFlag = true;
                            showLoader: false;
                            return true;
                        }
                    } catch (err) {
                        errorTitle = "System error, Please try again.";
                        ccValidationFlag = true;
                        showLoader: false;
                        return true;
                    } finally {
                        if (ccValidationFlag) {
                            $('#place-order-trigger-wrapper').removeAttr('disabled');
                            $('.action.primary.submit-btn').removeAttr('disabled');
                            showLoader: false;
                            $('.checkout-error .error-title').text(errorTitle);
                            $('.checkout-error .error-details').text(errorMsgDetails);
                            $('.checkout-error .error-transaction').text(errorTransaction);
                            $('.checkout-error').removeClass('hide');
                            $('.checkout-error').removeClass('api-error-hide').find(".error-response").trigger('focus');
                        }
                    }
                });
            }
        },

        /**
         *  Return true if Order ApprovalB2B toggle is on
         *
         * @return {Boolean}
         */
        isPendingOrderApproval: function () {
            return typeof(window.checkoutConfig.xmen_order_approval_b2b_enabled) != 'undefined' && typeof(window.checkoutConfig.xmen_order_approval_b2b_enabled) != null ? window.checkoutConfig.xmen_order_approval_b2b_enabled : false;
        },

        /**
         * Get Warning icon image
         *
         * @return {string}
         */
        pendingOrderIconImgUrl: function () {
            return typeof(window.checkoutConfig.xmen_order_approval_warning_icon) != 'undefined' && typeof(window.checkoutConfig.xmen_order_approval_warning_icon) != null ? window.checkoutConfig.xmen_order_approval_warning_icon : '';
        },

        /**
         * Get pending order approval msg title
         *
         * @return {string}
         */
        isPendingOrderApprovalMsgTitle: function () {
            return typeof(window.checkoutConfig.xmen_pending_order_approval_msg_title) != 'undefined' && typeof(window.checkoutConfig.xmen_pending_order_approval_msg_title) != null ? window.checkoutConfig.xmen_pending_order_approval_msg_title : 'Pending Approval';
        },

        /**
         * Get pending order approval msg
         *
         * @return {string}
         */
        isPendingOrderApprovalMsg: function () {
            return typeof(window.checkoutConfig.xmen_pending_order_approval_msg) != 'undefined' && typeof(window.checkoutConfig.xmen_pending_order_approval_msg) != null ? window.checkoutConfig.xmen_pending_order_approval_msg : 'This order will require admin approval before we begin processing.The estimated delivery/pickup date and time may vary based on when this order is approved.';
        },

        /**
         * Get quote price dashable
         *
         * @return {string}
         */
        isQuotePriceDashable: function () {
            return typeof(window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && typeof(window.checkoutConfig.is_quote_price_is_dashable) != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
        }
    });
});
