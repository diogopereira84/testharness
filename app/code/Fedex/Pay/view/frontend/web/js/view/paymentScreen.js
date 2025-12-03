/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'ko',
    "jquery",
    'uiComponent',
    'underscore',
    "Fedex_Pay/js/view/card-validator",
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/form/form',
    'Magento_Checkout/js/model/step-navigator',
    "Magento_Checkout/js/model/quote",
    "Magento_Catalog/js/price-utils",
    "mage/url",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Checkout/js/model/error-processor",
    "Fedex_Pay/js/view/ans1",
    "Fedex_Pay/js/view/rsaes-oaep",
    "shippingFormAdditionalScript",
    "fedexAccountCheckout",
    "mage/translate",
    "Fedex_ExpressCheckout/js/fcl-fedex-account-list",
    "Fedex_ExpressCheckout/js/view/checkout/fcl-credit-card-list",
    "Magento_Customer/js/customer-data",
    "rateResponseHandler",
    "rateQuoteAlertsHandler",
    "checkout-common",
    "Fedex_MarketplaceUi/js/view/manage_toast_messages",
    "marketplace-delivery-toast-messages",
    "Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper",
    "Fedex_Recaptcha/js/reCaptcha",
    'rateQuoteErrorsHandler',
    "checkoutAdditionalScript",
    "fedex/storage",
    'Fedex_Delivery/js/model/toggles-and-settings',
 ], function (
    ko,
    $,
    Component,
    _,
    cardValidator,
    customer,
    form,
    stepNavigator,
    quote,
    priceUtils,
    urlBuilder,
    shippingService,
    errorProcessor,
    ans,
    rsa,
    shippingFormAdditionalScript,
    fedexAccountDiscount,
    $t,
    fclFedexAccountList,
    fclCreditCardList,
    customerData,
    rateResponseHandler,
    rateQuoteAlertsHandler,
    marketplaceCheckoutCommon,
    marketplaceToastMessages,
    marketplaceDeliveryToast,
    marketplaceQuoteHelper,
    reCaptcha,
    rateQuoteErrorsHandler,
    checkoutAdditionalScript,
    fxoStorage,
    togglesAndSettings
    ) {
    'use strict';

    var Year = function (text, value) {
        this.text = text;
        this.value = value;
    };

    var isSelfRegCustomer = window.checkoutConfig.is_selfreg_customer;
    var isSelfRegFclCustomer = window.checkoutConfig.is_self_reg_fcl_customer;

    /**
     * Checks if current user is FCL or not
     */
    var isLoggedIn = window.checkoutConfig.is_logged_in;

    /**
     * Checks outsourced product
     */
    var hidePicupStore = true;
    var isOutSourced = window.checkoutConfig.is_out_sourced;
    var isLoggedIns = window.checkoutConfig.is_logged_in;
    if (isOutSourced && !isLoggedIns) {
        hidePicupStore = false;
    }

    /**
     * Checks outsourced product
     */
    var hidePoReferenceId = false;
    var isSdeStore = shippingFormAdditionalScript.isSdeStore();

    // B-1501794
    if ( !isSdeStore && !isLoggedIns) {
        hidePoReferenceId = true;
    }

    var siteConfiguredFedExAccount = shippingFormAdditionalScript.getCompanyFedExAccountNumber();
    let isCheckoutConfigAvaliable = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;

    var isFclCustomer = false;
    var retailProfileSession = false;
    var istermsAndConditionUrl = false;
    var isSelfregCustomerAdminUser = false;
    var isNonEditableCompanyCcPayment = false;
    var creditCardPaymentMethod = '';
    var availablePaymentMethodsValue = '';
    var availableCompanyCreditCardDetails = '';
    var loginValidationKey = '';
    let mazeGeeksD187301Toggle= false;
    let techTitanB2020963Toggle  = false;
    let formFieldCleansingForCCNameToggle = false;
    let techTitansB203420Toggle   = false;
    let explorersD179523Toggle = false;
    let isD193257ToggleEnable = false;
    let isFuseBidding = false;
    let isD195387ToggleEnable = false;
    let techTitansProductionLocationFix = false;
    let techTitansBillingFieldsLengthFix = false;
    let isD217133ToggleEnable = false;
    let  isD238830ToggleEnable = false;
    let isD238086ToggleEnable = false;

    if (isCheckoutConfigAvaliable) {
        /**
         * Checks if current logged in user is FCL or not
         */
        isFclCustomer = typeof (window.checkoutConfig.is_fcl_customer) !== "undefined" && window.checkoutConfig.is_fcl_customer !== null ? window.checkoutConfig.is_fcl_customer : false;

        /**
         * Check if Retail Profile session toggle enable or disable
         */
        retailProfileSession = typeof (window.checkoutConfig.retail_profile_session) !== "undefined" && window.checkoutConfig.retail_profile_session !== null ? window.checkoutConfig.retail_profile_session : false;

        /**
         * Check if Terms and condition Url toggle enable or disable
         */
        istermsAndConditionUrl = typeof (window.checkoutConfig.is_terms_and_condition_url) !== "undefined" && window.checkoutConfig.is_terms_and_condition_url !== null ? window.checkoutConfig.is_terms_and_condition_url : false;


        /**
         * Check if Selfreg customer admin user account
         */
        isSelfregCustomerAdminUser = typeof (window.checkoutConfig.is_selfreg_customer_admin_user) !== "undefined" && window.checkoutConfig.is_selfreg_customer_admin_user !== null ? window.checkoutConfig.is_selfreg_customer_admin_user : false;

        /**
         * Check if Non Editable Selfreg Customer Admin User Credit Card Payment
         */
        isNonEditableCompanyCcPayment = typeof (window.checkoutConfig.is_non_editable_company_cc_payment) !== "undefined" && window.checkoutConfig.is_non_editable_company_cc_payment !== null ? window.checkoutConfig.is_non_editable_company_cc_payment : false;

        /**
         * Check if credit card payment method identifier available
         */
        creditCardPaymentMethod = typeof window.checkoutConfig.credit_card_payment_method_identifier !== '' ? window.checkoutConfig.credit_card_payment_method_identifier : '';

        /**
         * Check if payment methods available
         */
        availablePaymentMethodsValue = typeof window.checkoutConfig.available_payment_method_value !== '' ? window.checkoutConfig.available_payment_method_value : '';

        loginValidationKey = typeof window.checkoutConfig.login_validation_key !== '' ? window.checkoutConfig.login_validation_key : '';


        /**
         * Check if credit card payment method identifier available
         */
        creditCardPaymentMethod = typeof window.checkoutConfig.credit_card_payment_method_identifier !== '' ? window.checkoutConfig.credit_card_payment_method_identifier : '';

        /**
         * Check if credit card name is valid
         */
        mazeGeeksD187301Toggle =  typeof (window.checkoutConfig.mazegeeks_d187301_fix) != "undefined" && window.checkoutConfig.mazegeeks_d187301_fix!=null
            ?window.checkoutConfig.mazegeeks_d187301_fix
            :false;
        techTitansB203420Toggle = typeof (window.checkoutConfig.tech_titans_d_203420) !== "undefined" && window.checkoutConfig.tech_titans_d_203420 !== null
            ? window.checkoutConfig.tech_titans_d_203420
            : false;

        techTitanB2020963Toggle = typeof (window.checkoutConfig.tech_titans_b_2020963) !== "undefined" && window.checkoutConfig.tech_titans_b_2020963 !== null
            ? window.checkoutConfig.tech_titans_b_2020963
            : false;

        /**
         * Explorers D-179523 Fix
         */
        explorersD179523Toggle = typeof (window.checkoutConfig.explorers_d_179523_fix) !== "undefined" && window.checkoutConfig.explorers_d_179523_fix !== null
            ? window.checkoutConfig.explorers_d_179523_fix
            : false;

        isD193257ToggleEnable = typeof window.checkoutConfig.explorers_d_193257_fix != 'undefined' ? window.checkoutConfig.explorers_d_193257_fix : false;

        /**
         * Mazegeeks fuse bidding toggle check
         */
        isFuseBidding = typeof (window.checkoutConfig.is_fusebid_toggle_enabled) !== "undefined" && window.checkoutConfig.is_fusebid_toggle_enabled !== null ? window.checkoutConfig.is_fusebid_toggle_enabled : false;

        /**
         * Tiger D-195387 Selfreg: Second custom billing filed data as PO Number when first custom billing field data as blank in checkout page
         */
        isD195387ToggleEnable = typeof (window.checkoutConfig.tiger_d195387) !== "undefined" && window.checkoutConfig.tiger_d195387 !== null ? window.checkoutConfig.tiger_d195387 : false;

        techTitansProductionLocationFix = typeof (window.checkoutConfig.tech_titans_d_205447_fix) != 'undefined' && window.checkoutConfig.tech_titans_d_205447_fix != null ? window.checkoutConfig.tech_titans_d_205447_fix : false;

        techTitansBillingFieldsLengthFix = typeof (window.checkoutConfig.tech_titans_d_214912) != 'undefined' && window.checkoutConfig.tech_titans_d_214912 != null ? window.checkoutConfig.tech_titans_d_214912 : false;

        isD217133ToggleEnable = typeof (window.checkoutConfig.tiger_d217133) != 'undefined' && window.checkoutConfig.tiger_d217133 != null ? window.checkoutConfig.tiger_d217133 : false;

        /**
         * Tech Titans - D-238830 Invisible Billing Reference Fields Not Displaying On Invoice Correctly
         */
        isD238830ToggleEnable = typeof (window.checkoutConfig.tech_titans_D_238830) != 'undefined' && window.checkoutConfig.tech_titans_D_238830 != null ? window.checkoutConfig.tech_titans_D_238830 : false;
        /**
         * Tech Titans - D-238086 Profile-saved Account Number selection during checkout is NOT persisting to order submission.
         */
        isD238086ToggleEnable = typeof (window.checkoutConfig.tech_titans_D_238086) != 'undefined' && window.checkoutConfig.tech_titans_D_238086 != null ? window.checkoutConfig.tech_titans_D_238086 : false;
    }

    if (availablePaymentMethodsValue && (typeof availablePaymentMethodsValue[creditCardPaymentMethod] !== 'undefined' || typeof availablePaymentMethodsValue['nameOnCard'] !== 'undefined')) {
        availableCompanyCreditCardDetails = availablePaymentMethodsValue[creditCardPaymentMethod] !== undefined ? availablePaymentMethodsValue[creditCardPaymentMethod] : availablePaymentMethodsValue;
    }

    var baseUrl = window.BASE_URL;
    var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";

    var isCCFormValid = true;

    function fieldValidation(input, e = null) {
        const errorMessage = input.data('error-message');
        const requiredMessage = input.data('required-message');
        const mask = input.attr('pattern');
        const value = input.val();

        if (techTitansB203420Toggle) {
            if (requiredMessage && value.trim() === '') {
                if (e && e.type === 'blur') {
                    input.addClass('error-highlight').siblings('.error-message').text(requiredMessage || '');
                } else {
                    input.addClass('error-no-highlight');
                }
                return false;
            } else if (mask != '' && value.trim() !== '') {
                let regex = new RegExp(mask);
                let isValid = regex.test(value);
                if (!isValid) {
                    input.addClass('error-highlight').siblings('.error-message').text(errorMessage);
                    return false;
                } else {
                    input.removeClass('error-highlight error-no-highlight').siblings('.error-message').text('');
                    return true;
                }
            } else {
                input.removeClass('error-highlight error-no-highlight').siblings('.error-message').text('');
                return true;
            }
        } else {
            if (requiredMessage && value.trim() === '') {
                if (e && e.type === 'blur') {
                    input.addClass('error-highlight').siblings('.error-message').text(requiredMessage || '');
                } else {
                    input.addClass('error-no-highlight');
                }
                return false;
            } else if (mask != '' && value.trim() !== '') {
                let regex = new RegExp(mask);
                let isValid = regex.test(value);

                if (!isValid) {
                    input.addClass('error-highlight').siblings('.error-message').text(errorMessage);
                    return false;
                } else {
                    input.removeClass('error-highlight error-no-highlight').siblings('.error-message').text('');
                    return true;
                }
            } else {
                input.removeClass('error-highlight error-no-highlight').siblings('.error-message').text('');
                return true;
            }
        }
    }


    function checkToDisableReviewBtn (fieldType) {
        let validate = true;
        const reviewButton = $('.fedex-account-number-review-button, .credit-card-review-button');
        if(fieldType === 'creditcard'){

            // Find all the fields with error class
            if ($('.credit-card-form').find('.error-highlight, .error-no-highlight, .contact-error:visible').length > 0 ) {
                validate = false
                reviewButton.prop('disabled', true);
            }

            validate = validate && ( isCCFormValid || $('.credit-cart-content').length);
        }
        else {
            $('.fedex-account-form .custom-billing-input').each(function() {
                if($(this).hasClass('error-highlight') || $(this).hasClass('error-no-highlight')){
                    reviewButton.prop('disabled', true);
                    return validate = false;
                }
            });
        }

        if(techTitansB203420Toggle){
            reviewButton.prop('disabled', !validate).removeClass('place-pickup-order-disabled');
        } else {
            if(validate){
                reviewButton.prop('disabled', false).removeClass('place-pickup-order-disabled');
            }
        }
    }

    return Component.extend({
        defaults: {
            template: 'Fedex_Pay/paymentStep'
        },

        isVisible: ko.observable(),
        isCustomerLoggedIn: ko.observable(isLoggedIn),
        isFclCustomer: ko.observable(isFclCustomer),
        isSdeStore: ko.observable(isSdeStore),
        getFedexAccountListWithHtml: ko.observable(fclFedexAccountList.getFedexAccountListWithHtml()),
        isHidePicupStore: ko.observable(hidePicupStore),
        isHidePoReferenceId: ko.observable(hidePoReferenceId),
        isCreditCardSelected: ko.observable(),
        isPaymentOptionSelected: ko.observable(false),
        years: ko.observableArray([]),
        states: ko.observableArray([]),
        selectedYear: ko.observable(),
        selectedState: ko.observable(null),
        isFedexAccount: ko.observable(false),
        isBillingAddress: ko.observable(false),
        showBillingCheckbox: ko.observable(true),
        isFedexAccountApplied: ko.observable(false),
        isDelivery: ko.observable(),
        fedexAccountNumber: ko.observable(''),
        poReferenceId: ko.observable(null),
        creditCardNumber: ko.observable(''),
        greenCheckUrl: ko.observable(window.checkoutConfig.media_url + "/Generic.png"),
        cardIcon: ko.observable('fa fa-credit-card'),
        isExpDateValid: ko.observable(false),
        crossUrl: ko.observable(window.checkoutConfig.media_url + "/circle-times.png"),
        checkIcon: ko.observable(window.checkoutConfig.media_url + "/check-icon.png"),
        expressInfoIcon : ko.observable(window.checkoutConfig.media_url + "/express-info.png"),
        crossIcon: ko.observable(window.checkoutConfig.media_url + "/close-button.png"),
        checkoutPaymentPageTitle: ko.observable("Checkout"),
        checkoutPaymentFormTitle: ko.observable("Payment"),
        // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
        isCompanyCreditCardAvailable: ko.observable(false),
        companyCreditCardData: ko.observableArray([]),
        companyFedexAccount: ko.observableArray(''),
        isCompanyFedexAccountAvailable: ko.observable(false),
        showCardNameField: ko.observable(true),
        showCardNumberField: ko.observable(true),
        showExpCvvFields: ko.observable(true),
        techTitanB2020963Toggle: ko.observable(techTitanB2020963Toggle),
        techTitansB203420Toggle : ko.observable(techTitansB203420Toggle),
        showCcPayment: ko.observable(true),
        showAccountPayment: ko.observable(true),
        companyCreditCard: ko.observable(false),
        termsAndConditionUrl: ko.observable(istermsAndConditionUrl),

        /*
         * ###############################################################
         *                   Start | Marketplace Section
         * ###############################################################
        **/

        marketplaceInStorePickupShippingCombo: ko.observable(false),

        /*
         * ###############################################################
         *                   End | Marketplace Section
         * ###############################################################
        **/

        isCommercialCustomer: ko.observable(false),
        customBillingInvoiced: ko.observableArray(window.checkoutConfig.custom_billing_invoiced),
        customBillingCreditCard: ko.observableArray(window.checkoutConfig.custom_billing_credit_card),
        chosenDeliveryMethod: ko.observable(window.e383157Toggle ?
            (fxoStorage.get('chosenDeliveryMethod') || 'shipping') :
            (localStorage.getItem('chosenDeliveryMethod') || 'shipping')),
        loginValidationKey: ko.observable(loginValidationKey),

        /**
         * @returns {*}
         */
        initialize: function () {
            this._super();
            var self = this;
            var yearsArray = self.getYears();
            self.years(yearsArray);
            self.states(self.getStates());
            self.checkoutPaymentFormTitle();

            self.chosenDeliveryMethod.subscribe((newMethod) => {
                if (['shipping', 'pick-up'].includes(newMethod)) {
                    if (window.e383157Toggle) {
                        fxoStorage.set('chosenDeliveryMethod', newMethod);
                    } else {
                        localStorage.setItem('chosenDeliveryMethod', newMethod);
                    }
                    window.dispatchEvent(new Event('on_change_delivery_method'));
                }
            });

            window.addEventListener('on_change_delivery_method', () => {
                this.chosenDeliveryMethod(window.e383157Toggle ? fxoStorage.get('chosenDeliveryMethod') : localStorage.getItem('chosenDeliveryMethod'));
            });

            stepNavigator.registerStep(
                'step_code',
                null,
                isLoggedIn && !isSelfRegCustomer ? 'Submit Order' : 'Payment',
                this.isVisible,

                _.bind(this.navigate, this),
                15
            );
            $(window).on('load',function() {
                let isReview = window.location.hash;
                if(isReview == '#payment'){
                    $(".opc-block-summary .table-totals .incl .isnot_review_page").hide();
                    $(".opc-block-summary .table-totals .incl .is_review_page").show();
                }
                $('div.block.items-in-cart.active').attr('aria-busy','true');
                $('.checkout-index-index .checkout-breadcrumb >li').removeAttr("role");
            });
            $( document ).ajaxStop(function() {
                $("#discdrop").on("keypress", function(event) {
                    if (event.keyCode === 13) {
                        $('.toggle-discount .arrow').toggleClass("up");
                        $('.discount_breakdown').slideToggle(100);
                    }
                    event.stopImmediatePropagation();
                });
                $('div.block.items-in-cart.active').attr('aria-busy','true');
                $('.checkout-index-index .checkout-breadcrumb >li').removeAttr("role");
                let isReview = window.location.hash;
                if(isReview != '#payment'){
                    $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                    $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                }
            });

            $(window).on('popstate', function() {
                let isReview = window.location.hash;
                if(isReview == '#step_code' || isReview == '#shipping'){
                    $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                    $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                }
                if(isReview == '#payment'){
                    $(".opc-block-summary .table-totals .incl .isnot_review_page").hide();
                    $(".opc-block-summary .table-totals .incl .is_review_page").show();
                }
            });

            // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
            self.companyCreditCard.subscribe(function (value) {
                if (value && self.isCompanyCreditCardAvailable()) {
                    // D-101910:RT-ECVS-SDE-Credit card billing address is not correct when CC is prepopulated in Site Admin
                    /** These classes assignemnt should be removed once toggle explorers_d_164473_fix would cleaned **/
                    var parentClass = 'cms-sde-home';
                    if(isSelfRegCustomer){
                        parentClass = 'wlgn-enable-page-header';
                    }
                    parentClass = 'commercial-store-home';
                    if (!$('.'+ parentClass +' .billing-checkbox').is(':checked')) {
                        $('.'+ parentClass +' .billing-checkbox').prop('checked', true).trigger('change');
                    }
                    self.showCompanyCreditCardInfo(self.companyCreditCardData());
                } else if ($('.card-list:not(.last,.company-card)').length === 0) {
                    self.showCreditCardForm();
                }
            });

            self.isVisible.subscribe(function (visibleFlag) {
                if (visibleFlag && window.FDXPAGEID) {
                    var gdlPageId = window.FDXPAGEID + '/step-code';
                    window.FDX.GDL.push(['event:publish', ['page', 'pageinfo', {
                        pageId: gdlPageId
                    }]]);
                }

                // D-192487 Billing Address not retaining the original address when Shipping address is changed
                if (visibleFlag && window.checkoutConfig?.techtitans_D_192487 === true) {
                    let isShip, isPick;
                        isShip = fxoStorage.get("shipkey");
                        isPick = fxoStorage.get("pickupkey");

                    if (isShip === 'true' && isPick === 'false') {
                        if (!$('.billing-checkbox').is(':checked')) {
                            $('.billing-address-form-container').show();
                            $('.shipping-address:not(.site-credit-card)').hide();

                            if (typeof self !== 'undefined' && typeof self.isBillingAddress === 'function') {
                                self.isBillingAddress(true);
                            }
                            if (typeof self !== 'undefined' && typeof self.validateBillingForm === 'function') {
                                self.validateBillingForm();
                            }
                        }
                    }
                }
            });

            /*Resolve submit order button hide issue in mobile*/
            if ($(window).width() < 770) {
                $('.checkout-index-index .opc-sidebar.opc-summary-wrapper').removeClass('custom-slide');
            }

            /*End of resolve submit order button hide issue in mobile*/
            if (isSdeStore === true) {
                self.checkoutPaymentPageTitle("Payment");
                self.checkoutPaymentFormTitle("Payment Information");
                self.isHidePicupStore(false);
            }

            if(isSelfRegCustomer){
                self.checkoutPaymentPageTitle("Payment");
                self.checkoutPaymentFormTitle("Payment Information");
                if(window.e383157Toggle){
                    fxoStorage.set('removedFedexAccount', "false");
                }else{
                    localStorage.setItem('removedFedexAccount', "false");
                }
            }

            window.addEventListener('resize', function (event) {
                if ($(window).width() < 700) {
                    $('.opc-summary-wrapper').addClass('order-summary-identifier');
                    $('.order-summary-identifier').removeClass('modal-custom');
                    $('.order-summary-identifier').removeClass('opc-sidebar');
                    $('.order-summary-identifier').removeClass('custom-slide');
                    $('.modal-header').css('display', 'none');
                    $('.showcart').css('display', 'none');
                } else {
                    $('.order-summary-identifier').addClass('opc-summary-wrapper');
                    $('.opc-summary-wrapper').addClass('modal-custom');
                    $('.opc-summary-wrapper').addClass('opc-sidebar');
                    $('.opc-summary-wrapper').addClass('custom-slide');
                    $('.order-summary-identifier').removeClass('order-summary-identifier');
                    $('.modal-header').css('display', 'block');
                    $('.showcart').css('display', 'block');
                }
                if ($(window).width() < 800) {
                    $('.checkout-index-index .opc-sidebar.opc-summary-wrapper').removeClass('custom-slide');
                } else {
                    $('.checkout-index-index .opc-sidebar.opc-summary-wrapper').addClass('custom-slide');
                }
            }, true);

            $(document).on('click', '.img-close-pop', function (e) {
                $('.error-container').addClass('api-error-hide');
            });

            /**
             * Trigger select credit card when enter or space key is pressed
             */
            $(document).on('keypress', '.select-credit-card', function (e) {
                let keycode = (e.keyCode ? e.keyCode : e.which);
                if (keycode  == 13 || keycode  == 32) {
                    $('.select-credit-card').trigger('click');
                }
            });

            $(document).on("change", "#expiration-year, #expiration-month", function(e){
                if($(this).val()) {
                    $(this).addClass("selected")
                } else {
                    $(this).removeClass("selected")
                }
            })

            var billingArray = [];
            $(document).on('click', '.select-credit-card', function () {
                /**
                 * Generate Credit Card HTML for FCL
                */
                if (isFclCustomer) {
                    fclCreditCardList.generateCreditCardHtml();
                    if (isSdeStore || isSelfRegCustomer) {
                        self.selectDefinitiveCc();
                    }
                }
                let isShip,isPick;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }
                if (isShip === 'true' && isPick === 'false') {

                    let shippingAddressFromData = typeof (customerData.get('checkout-data')().shippingAddressFromData) !== "undefined" && customerData.get('checkout-data')().shippingAddressFromData !== null ? customerData.get('checkout-data')().shippingAddressFromData : false;

                    if (shippingAddressFromData) {
                        self.displayBillingSameShipping(shippingAddressFromData);
                    }
                    // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
                    self.displayBillingAddress();
                }

                if (isShip === 'false' && isPick === 'true' && isFclCustomer) {
                    $('.go-to-pickup').show();
                    $('.go-to-ship').css('display', 'none');
                    self.isDelivery(false);
                    self.showBillingCheckbox(false);
                    self.isBillingAddress(true);
                }

                // B-1216115 : When the CC is configured in Admin, Payment screen prepopulate the CC details
                if (self.isCompanyCreditCardAvailable()) {
                    self.companyCreditCard(true);
                    self.showCompanyCreditCardInfo(self.companyCreditCardData());
                    // B-1730450 : RT-ECVS- Once Company admin set as non editable payment method can not be change on storefront
                    if (isNonEditableCompanyCcPayment && availableCompanyCreditCardDetails) {
                        self.showOnlyNonEditableCompanyCreditCardInfo(availableCompanyCreditCardDetails);
                    }
                } else if (availableCompanyCreditCardDetails) {
                    self.companyCreditCardData(availableCompanyCreditCardDetails);
                    self.companyCreditCard(true);
                    self.isCompanyCreditCardAvailable(true);
                    self.showCompanyCreditCardInfo(self.companyCreditCardData());
                    // B-1730450 : RT-ECVS- Once Company admin set as non editable payment method can not be change on storefront
                    if (isNonEditableCompanyCcPayment) {
                        self.showOnlyNonEditableCompanyCreditCardInfo(availableCompanyCreditCardDetails);
                    }
                } else if ($('.card-list:not(.last,.company-card)').length !== 0) {
                    var primaryCC = $('.card-list:not(.last,.company-card)[data-primary="true"]');
                    if (primaryCC.length) {
                        primaryCC.click();
                    } else {
                        $('.card-list:not(.last,.company-card)').first().click();
                    }
                    $('.billing-checkbox').prop('checked', false).change();
                }
                if (isShip === 'false' && isPick === 'true') {
                    self.checkoutPaymentFormTitle("Payment Information");
                } else if (isShip === 'true' && isPick === 'false') {
                    $('.billing-checkbox').prop('checked', true).change();
                    self.checkoutPaymentFormTitle("Payment Information");
                }
                self.isCreditCardSelected(true);
                if (!self.isPaymentOptionSelected()) {
                    self.isPaymentOptionSelected(true);
                }

                $('.select-credit-card').addClass('selected-paymentype');
                $('.select-fedex-acc').removeClass('selected-paymentype');
                $("span.checkSymbol1").css("display", "block");
                $("span.checkSymbol2").css("display", "none");

                var fedexAccountNo = $('.account-number').val();
                if (fedexAccountNo.length == 0) {
                    self.fedexAccountNumber('');
                    if(window.e383157Toggle){
                        fxoStorage.set('fedexAccount',"");
                    }else{
                        localStorage.setItem('fedexAccount',"");
                    }
                    self.validateAccount();
                    self.isFedexAccountApplied(false);
                }

                let isBillingAddressInStorage = false;
                let paymentData;
                if(window.e383157Toggle){
                    paymentData = fxoStorage.get("paymentData");
                }else{
                    paymentData = JSON.parse(localStorage.getItem('paymentData'));
                }
                if (paymentData) {
                    isBillingAddressInStorage = typeof (paymentData.isBillingAddress) !== "undefined" && paymentData.isBillingAddress !== null ? paymentData.isBillingAddress : false;
                }
                if (isBillingAddressInStorage) {
                    let billingAddress = typeof (paymentData.billingAddress) !== "undefined" && paymentData.billingAddress !== null ? paymentData.billingAddress : false;
                    if ($("#company-name").val() == '') {
                        $("#company-name").val(billingAddress.company);
                    }
                    if ($("#address-one").val() == '') {
                        $("#address-one").val(billingAddress.address);
                    }
                    if ($("#address-two").val() == '') {
                        $("#address-two").val(billingAddress.addressTwo);
                    }
                    if ($("#add-city").val() == '') {
                        $("#add-city").val(checkoutAdditionalScript.allowCityCharacters(billingAddress.city));
                    }
                    if ($("#add-state").val() == '') {
                        $("#add-state").val(billingAddress.state);
                        $('#add-state').trigger('change');
                    }
                    if ($("#add-zip").val() == '') {
                        $("#add-zip").val(billingAddress.zip);
                    }
                    if ($("#address-one").val() != '' && $("#add-city").val() != '' && $("#add-state").val() != '' && $("#add-zip").val() != '') {
                        self.validateCCForm();
                    }
                } else {
                    var ajaxUrl = urlBuilder.build('fcl/index/customerbillingaddress');
                    $.ajax({
                        type: "POST",
                        enctype: "multipart/form-data",
                        url: ajaxUrl,
                        data: [],
                        processData: false,
                        contentType: false,
                        cache: false
                    }).done(function (data) {
                        if (data != '') {
                            if (data.status == 'success') {
                                if ($("#company-name").val() == '') {
                                    $("#company-name").val(data.company);
                                }
                                if ($("#address-one").val() == '') {
                                    $("#address-one").val(data.street);
                                }
                                if ($("#add-city").val() == '') {
                                    $("#add-city").val(checkoutAdditionalScript.allowCityCharacters(data.city));
                                }
                                if ($("#add-state").val() == '') {
                                    $("#add-state").val(data.region);
                                    $('#add-state').trigger('change');
                                }
                                if ($("#add-zip").val() == '') {
                                    $("#add-zip").val(data.postcode);
                                }
                                if ($("#address-one").val() != '' && $("#add-city").val() != '' && $("#add-state").val() != '' && $("#add-zip").val() != '') {
                                    self.validateCCForm();
                                }
                            } else {
                                console.log(data);
                            }
                        }
                    });
                }

                if (isSelfRegCustomer || isSdeStore) {

                    if (self.customBillingCreditCard().length > 0) {
                        $('p.custom-billing-heading-cc').css('visibility', 'visible')
                        self.isCommercialCustomer(true);
                        billingArray = self.customBillingCreditCard();
                        self.createBillingFields(billingArray,'creditcard');
                    }
                    self.removeAdditionalBillingInfo();
                }
            });

            $(document).on('click', '.place-pickup-order', function () {

                /**
                 * ###############################################################
                 *                   Start | Marketplace Section
                 * ###############################################################
                **/

                let isPickupShippingCombo;
                if(window.e383157Toggle){
                    isPickupShippingCombo = (fxoStorage.get('pickupShippingComboKey') === 'true');
                }else{
                    isPickupShippingCombo = (localStorage.getItem('pickupShippingComboKey') === 'true');
                }
                self.marketplaceInStorePickupShippingCombo(isPickupShippingCombo);

                /**
                 * ###############################################################
                 *                   End | Marketplace Section
                 * ###############################################################
                **/
                var isShip,isPick;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }
                if ((isShip === 'true' && isPick === 'false') || isSdeStore) {
                    if (window.e383157Toggle) {
                        fxoStorage.set('shipkey', 'true');
                        fxoStorage.set('pickupkey', 'false');
                    } else {
                        window.localStorage.setItem('shipkey', true);
                        window.localStorage.setItem('pickupkey', false);
                    }
                    self.isDelivery(true);
                    self.showBillingCheckbox(true);
                    self.isBillingAddress(false);
                    self.checkoutPaymentFormTitle("Payment Information");
                } else if ((isShip === 'false' && isPick === 'true') || !isSdeStore) {
                    if (window.e383157Toggle) {
                        fxoStorage.set('shipkey', 'false');
                        fxoStorage.set('pickupkey', 'true');
                    } else {
                        window.localStorage.setItem('shipkey', false);
                        window.localStorage.setItem('pickupkey', true);
                    }
                    $('.go-to-pickup').show();
                    $('.go-to-ship').css('display', 'none');
                    self.isDelivery(false);
                    self.showBillingCheckbox(false);
                    self.isBillingAddress(true);
                    self.checkoutPaymentFormTitle("Payment Information");
                }
                if (self.isPaymentOptionSelected()) {
                    self.selectDefinitiveCc();
                }

                window.dispatchEvent(new Event('on_change_delivery_method'));
            });

            $(document).on('click', '.create_quote', function () {

                /**
                 * ###############################################################
                 *                   Start | Marketplace Section
                 * ###############################################################
                **/

                let pickupShippingComboKey;
                if(window.e383157Toggle){
                    pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
                }else{
                    pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
                }
                let isPickupShippingCombo = (
                    pickupShippingComboKey === 'true'
                );

                self.marketplaceInStorePickupShippingCombo(isPickupShippingCombo);

                /**
                 * ###############################################################
                 *                   End | Marketplace Section
                 * ###############################################################
                **/

                let isShip,isPick;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }

                if (isShip === 'false' && isPick === 'true') {
                    self.checkoutPaymentFormTitle("Payment Information");
                } else if (isShip === 'true' && isPick === 'false') {
                    self.checkoutPaymentFormTitle("Payment Information");
                }
                if (isShip === 'true' && isPick === 'false') {
                    self.isDelivery(true);
                    self.showBillingCheckbox(true);
                    self.isBillingAddress(false);
                    let shippingAddressFromData = typeof (customerData.get('checkout-data')().shippingAddressFromData) !== "undefined" && customerData.get('checkout-data')().shippingAddressFromData !== null ? customerData.get('checkout-data')().shippingAddressFromData : false;

                    if (shippingAddressFromData) {
                        self.displayBillingSameShipping(shippingAddressFromData);
                    }
                    // D-101635:RT-ECVS- SDE-Billing address is not prepopulated correctly in payment screen
                    self.displayBillingAddress();
                } else if (isShip === 'false' && isPick === 'true') {
                    self.isDelivery(false);
                    self.showBillingCheckbox(false);
                    self.isBillingAddress(true);
                }
                if (self.isPaymentOptionSelected()) {
                    self.selectDefinitiveCc();
                }
            });

            $(document).on('blur', '.account-number', function () {
                var acc = $('.account-number').val();
                if (acc.length > 1) {
                    self.fedexAccountNumber(acc);
                    var masked = acc.length > 4 ? "*" + acc.substr(-4) : acc;
                    $('.account-number').val(masked);
                }
            });

            $(document).on('keypress', '.account-number, .fedex-account-number', function (e) {
                if (e.which == 32) {
                    return false;
                }
            });

            $(document).on('input', '.account-number, .fedex-account-number', function (e) {
                $(this).val($(this).val().replace(/ /g, ""));
                return false;
            });

            $(document).on('blur', '.fedex-account-number', function () {
                var fedexAcc = $('.fedex-account-number').val();
                self.fedexAccountNumber(fedexAcc);
                if (fedexAcc.length > 8) {
                    var masked = "*" + fedexAcc.substr(-4);
                    $('.fedex-account-number').val(masked);
                }
            });

            $(document).on('focus', '.fedex-account-number', function () {
                let shippFedexAccNum = $('.fedex-account-number').val();
                var acc = shippFedexAccNum ? self.fedexAccountNumber() : self.fedexAccountNumber('');
                if (acc.length > 0) {
                    $('.fedex-account-number').val(self.fedexAccountNumber());
                }
            });

            $(document).on('keyup blur', '.account-number', function () {
                var value1 = self.fedexAccountNumber().trim();
                var value2 = $('.account-number').val().trim();
                if (value1.length == 0 && value2.length == 0) {
                    $(".invalid-account-error").show();
                    $(".invalid-account-error").html('This field is required.');
                } else if (isNaN(value1)) {
                    $(".invalid-account-error").show();
                    $(".credit-card-review-button").attr('disabled', 'disabled');
                    $(".apply-account").attr('disabled', 'disabled');
                    $(".invalid-account-error").html('Please enter valid account number.');
                } else {
                    $(".apply-account").prop("disabled", false);
                    $(".invalid-account-error").hide();
                    $(".invalid-account-error").empty();
                }

                let validate = true;
                $('.custom-billing-input:visible').each(function() {
                    if($(this).hasClass('error-highlight')){
                        $(".fedex-account-number-review-button").prop('disabled', true);
                        return validate = false;
                    }
                });
                if(validate){
                    $(".fedex-account-number-review-button").prop('disabled', false);
                }
            });

            $(document).on('focus', '.account-number', function () {
                var acc = self.fedexAccountNumber();
                if (acc.length > 0) {
                    $('.account-number').val(self.fedexAccountNumber());
                }
            });

            $(document).on('blur', '.card-number', function () {
                var card = $('.card-number').val().replaceAll(' ', '');
                self.creditCardNumber(card);
                if (card.length > 9) {
                    if (card.length > 13) {
                        $('.card-number').removeClass('contact-error');
                        $('.card-number-error').hide();
                    }

                    self.creditCardNumber(card);
                    var masked = "*" + card.substr(-4);
                    $('.card-number').val(masked);
                }
            });

            $(document).on('focus', '.card-number', function () {
                var acc = self.creditCardNumber();
                if (acc.length > 0) {
                    $('.card-number').val(self.creditCardNumber());
                }
            });
            let isShip,isPick;
            if(window.e383157Toggle){
                isShip = fxoStorage.get("shipkey");
                isPick = fxoStorage.get("pickupkey");
            }else{
                isShip = localStorage.getItem("shipkey");
                isPick = localStorage.getItem("pickupkey");
            }
            if (availableCompanyCreditCardDetails) {
                self.companyCreditCardData(availableCompanyCreditCardDetails);
                self.isCompanyCreditCardAvailable(true);
            }
            if (isShip === 'true' && isPick === 'false') {
                self.showBillingCheckbox(true);
                self.isBillingAddress(false);
            } else if (isShip === 'false' && isPick === 'true') {
                self.showBillingCheckbox(false);
                self.isBillingAddress(true);
            }

            /**
             * Trigger Fedex Account once enter or space key is pressed
             */
            $(document).on('keypress', '.select-fedex-acc', function (e) {
                let keycode = (e.keyCode ? e.keyCode : e.which);
                if (keycode  == 13 || keycode  == 32) {
                    $('.select-fedex-acc').trigger('click');
                }
            });

            /**
             * Click on review order button from pickup/shipping page then focus goes to footer section on Review and submit page D-128154 .
             */
            $(document).on('click', '.credit-card-review-button', function () {
                $(window).scrollTop(0);
            });

            var discountAccountNumber = window.checkoutConfig.fedex_account_number_discount;
            var fxoAccountNumber = window.checkoutConfig.fedex_account_number && window.checkoutConfig.fedex_account_number != discountAccountNumber ? window.checkoutConfig.fedex_account_number : null;
            $(document).on('click', '.select-fedex-acc', function () {
                // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
                if (self.isCompanyFedexAccountAvailable()) {
                    self.prefilFedExAccount(self.companyFedexAccount());
                }
                self.isCreditCardSelected(false);
                if (!self.isPaymentOptionSelected()) {
                    self.isPaymentOptionSelected(true);
                }

                $('.select-fedex-acc').addClass('selected-paymentype');
                $('.select-credit-card').removeClass('selected-paymentype');
                $("span.checkSymbol1").css("display", "none");
                $("span.checkSymbol2").css("display", "block");

                var fedexAccountNo = $('.fedex-account-number').val();
                if (fedexAccountNo.length > 0) {
                    if (!isSdeStore || !isSelfRegCustomer) {
                        $(".fedex-account-checkbox").trigger("click");
                    }
                } else if (fedexAccountNo.length == 0) {
                    self.fedexAccountNumber('');
                    if(window.e383157Toggle){
                        fxoStorage.set('fedexAccount',"");
                    }else{
                        localStorage.setItem('fedexAccount',"");
                    }
                    self.validateAccount();
                    self.isFedexAccountApplied(false);
                }
                let isShip,isPick;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }
                if (isShip === 'false' && isPick === 'true') {
                    self.checkoutPaymentFormTitle("Payment Information");
                } else if (isShip === 'true' && isPick === 'false') {
                    self.checkoutPaymentFormTitle("Payment Information");
                }

                var fedexPrepopulatedNumDefault = typeof (window.checkoutConfig.fedex_account_number) !== "undefined" &&
                    window.checkoutConfig.fedex_account_number !== null &&
                    window.checkoutConfig.fedex_account_number != discountAccountNumber &&
                    window.checkoutConfig.fedex_account_nubmer != window.checkoutConfig.company_discount_account_number ?

                    window.checkoutConfig.fedex_account_number : null;

                var fedexPrepopulatedNum = typeof (window.checkoutConfig.fedex_account_number) !== "undefined" &&
                    window.checkoutConfig.fedex_account_number !== null ? window.checkoutConfig.fedex_account_number : null;

                if (fedexPrepopulatedNum === window.checkoutConfig.company_discount_account_number) {
                    fedexPrepopulatedNum = null;
                }

                let fedexAccountIsEditable = window.checkoutConfig.fxo_account_number_editable;
                let fxoAccountNumberField = $('input.fedex-account-number');
                if (fedexPrepopulatedNum) {

                    if ((isSdeStore || isSelfRegCustomer) && fedexAccountIsEditable === "1") {
                        fxoAccountNumberField.prop('disabled', false);
                        $(".fedex-account-number-review-button").removeClass("fedex-account-number-review-button-disabled");
                    }

                    fxoAccountNumberField.val(fedexPrepopulatedNum);
                    fxoAccountNumberField.trigger('blur');
                    //B-1294484 Make fedex account number field disabled for company fedex account
                    if (siteConfiguredFedExAccount == fedexPrepopulatedNum && !isFclCustomer && fedexAccountIsEditable !== "1") {
                        fxoAccountNumberField.prop('disabled', true);
                    }
                }
                let isFedexAccountList;
                if(window.e383157Toggle){
                    isFedexAccountList = fxoStorage.get("isFedexAccountList");
                }else{
                    isFedexAccountList = localStorage.getItem("isFedexAccountList");
                }
                if (isFclCustomer && isFedexAccountList) {

                    let fedexPrepopulatedNum = null;
                    let fedexAccountNumberShow = null;
                    let selectedfedexAccount;
                    if(window.e383157Toggle){
                        selectedfedexAccount = fxoStorage.get("selectedfedexAccount");
                    }else{
                        selectedfedexAccount = localStorage.getItem("selectedfedexAccount");
                    }
                    if (selectedfedexAccount) {
                        fedexPrepopulatedNum = selectedfedexAccount;
                        fedexPrepopulatedNumDefault = selectedfedexAccount;
                        fedexAccountNumberShow = $(".fedex-account-value[data-value='"+selectedfedexAccount+"']").length
                            ? $(".fedex-account-value[data-value='"+selectedfedexAccount+"']").first().text()
                            : $(".fedex-account-value[data-value='"+selectedfedexAccount+"']").text();

                        if (window.e383157Toggle) {
                            if (!fedexAccountNumberShow && !fxoStorage.get('isFedexAccountFieldVisible')) {
                                fedexAccountNumberShow = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().text();
                            } else if (!fedexAccountNumberShow && fxoStorage.get('isFedexAccountFieldVisible')) {
                                fedexAccountNumberShow = $(".fedex-account-value[data-value='other']").text();
                                $(".selected-fedex-account .fedex-account-show").text(fedexAccountNumberShow);
                                $(".account-num-container").show();
                                $(".save-fedex-account-chk-container").show();
                                fedexPrepopulatedNum = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().attr("data-value");
                                fedexAccountNumberShow = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().text();
                            }
                        } else {
                            if (!fedexAccountNumberShow && !localStorage.getItem('isFedexAccountFieldVisible')) {
                                fedexAccountNumberShow = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().text();
                            } else if (!fedexAccountNumberShow && localStorage.getItem('isFedexAccountFieldVisible')) {
                                fedexAccountNumberShow = $(".fedex-account-value[data-value='other']").text();
                                $(".selected-fedex-account .fedex-account-show").text(fedexAccountNumberShow);
                                $(".account-num-container").show();
                                $(".save-fedex-account-chk-container").show();
                                fedexPrepopulatedNum = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().attr("data-value");
                                fedexAccountNumberShow = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().text();
                            }
                        }
                    } else {
                        fedexPrepopulatedNum = $(".fedex-account-value[data-selected='1']").attr("data-value");
                        if (fedexPrepopulatedNum) {
                            fedexAccountNumberShow = $(".fedex-account-value[data-selected='1']").text();
                            if (!isD238086ToggleEnable) {
                                fedexPrepopulatedNum = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().attr("data-value");
                            }
                        } else {
                            if (fedexPrepopulatedNumDefault != fedexPrepopulatedNum) {
                                fedexAccountNumberShow = $(".fedex-account-value[data-value='other']").text();
                                $(".selected-fedex-account .fedex-account-show").text(fedexAccountNumberShow);
                                $(".account-num-container").show();
                                $(".save-fedex-account-chk-container").show();
                                fedexPrepopulatedNum = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().attr("data-value");
                                fedexAccountNumberShow = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().text();
                            } else {
                                fedexPrepopulatedNum = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().attr("data-value");
                                fedexAccountNumberShow = $(".custom-fedex-account-list-container ul li.fedex-account-value").first().text();
                            }
                        }
                    }
                    if (fedexPrepopulatedNum) {
                        $('input.fedex-account-number').val(fedexPrepopulatedNum);
                        $('input.fedex-account-number').trigger('blur');
                    }
                    $(".account-num-container").hide();
                    $(".account-num-container").addClass("account-num-with-list");
                    if (fedexPrepopulatedNum) {
                        $(".selected-fedex-account .fedex-account-show").text(fedexAccountNumberShow);
                        $(".save-fedex-account-chk-container").hide();
                    } else if (selectedfedexAccount) {
                        fedexAccountNumberShow = $(".fedex-account-value[data-value='other']").text();
                        $(".selected-fedex-account .fedex-account-show").text(fedexAccountNumberShow);
                        $(".account-num-container").show();
                        $(".save-fedex-account-chk-container").show();
                    }
                    if ($(".fedex-account-value").length == 1) {
                        $(".fedex-account-value").trigger('click');
                        $(".fcl-fedex-account-list").hide();
                    }
                }
                if (isSelfRegCustomer || isSdeStore) {
                    //D-156981 - Updating a Commercial FedEx Payment Account in Checkout
                    var isEditable = window.checkoutConfig.fxo_account_number_editable;
                    var companyFxoFedexAccount = window.checkoutConfig.company_fxo_account_number;
                    var toggleD194434 =  typeof (window.checkoutConfig.techtitans_d194434) !== "undefined" && window.checkoutConfig.techtitans_d194434 !== null
                        ? window.checkoutConfig.techtitans_d194434
                        : false;

                    var toggleD198167 =  typeof (window.checkoutConfig.techtitans_d198167) !== "undefined" && window.checkoutConfig.techtitans_d198167 !== null
                        ? window.checkoutConfig.techtitans_d198167 : false;

                    if(companyFxoFedexAccount === null) {
                        isEditable = "1";
                    }

                    if (isEditable === "0" && window.checkoutConfig.fedex_account_number !== null) {
                        isFclCustomer ? $(".fedex-account-list-inner-container .selected-fedex-account").addClass('dropdown-disabled') : null;
                        $('input.fedex-account-number').prop('disabled', true);
                    };

                    if (toggleD194434) {
                        if (companyFxoFedexAccount) {
                            $(".account-num-container").addClass("account-num-with-list");
                            $('input.fedex-account-number').val(companyFxoFedexAccount);
                            $('input.fedex-account-number').trigger('blur');
                            $(".selected-fedex-account .fedex-account-show").text(companyFxoFedexAccount);
                            $(".save-fedex-account-chk-container").hide();

                            if ($(".fedex-account-value").length == 1) {
                                $(".fedex-account-value").trigger('click');
                                $(".fcl-fedex-account-list").hide();
                            }
                        }
                    }


                    if(toggleD198167 ) {

                        if(companyFxoFedexAccount != null) {
                            $('input.fedex-account-number').val(companyFxoFedexAccount);
                            $('input.fedex-account-number').trigger('blur');
                            var maskedFedexAccountShow = "Fedex Account *" + companyFxoFedexAccount.substr(-4);
                            $(".selected-fedex-account .fedex-account-show").text(maskedFedexAccountShow);
                        }
                    }


                    if (self.customBillingInvoiced().length > 0) {
                        $(".po-reference-label.fedex-acc, .po-reference-id.fedex-acc").remove();
                        billingArray = self.customBillingInvoiced();
                        self.createBillingFields(billingArray,'invoiced');
                    }
                    self.removeAdditionalBillingInfo();
                };
            });

            $(document).on('paste', '.card-number', function (e) {
                e.preventDefault();
            });

            $(document).on('keypress change', '.card-number', function () {
                var value = $(".card-number").val();
                if (value[0] == "4") {
                    self.cardIcon("fa fa-cc-visa");
                    self.greenCheckUrl(window.checkoutConfig.media_url + "/Visa.png");
                } else if (value[0] == "5") {
                    self.cardIcon("fa fa-cc-mastercard");
                    self.greenCheckUrl(window.checkoutConfig.media_url + "/MasterCard.png");
                } else if ((value[0] == "3" && value[1] == "4") || (value[0] == "3" && value[1] == "7")) {
                    self.cardIcon("fa fa-cc-amex");
                    self.greenCheckUrl(window.checkoutConfig.media_url + "/Amex.png");
                } else if (value[0] == "6") {
                    self.cardIcon("fa fa-cc-discover");
                    self.greenCheckUrl(window.checkoutConfig.media_url + "/Discover.png");
                } else if (value[0] == "3" && value[1] == "8" || (value[0] == "3" && value[1] == "6")) {
                    self.cardIcon("fa fa-cc-diners-club");
                    self.greenCheckUrl(window.checkoutConfig.media_url + "/Diners-Club.png");
                } else {
                    self.cardIcon("fa fa-credit-card");
                    self.greenCheckUrl(window.checkoutConfig.media_url + "/Generic.png");
                }

                $(this).val(function (index, value) {
                    return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
                });
            });

            $(document).on('change', '.add-state', function () {
                self.selectedState();
            });

            $(document).on('change', '.fedex-account-checkbox', function () {
                self.fedexAccountNumber('');
                $('.account-number').val('');
                if(window.e383157Toggle){
                    fxoStorage.set('fedexAccount',"");
                }else{
                    localStorage.setItem('fedexAccount',"");
                }
                self.validateAccount();
                self.isFedexAccountApplied(false);
                $(".account-number").prop("disabled", false);
                $(".apply-account").prop("disabled", false);
                if (isSdeStore) {
                    self.isFedexAccount(false);
                } else {
                    self.isFedexAccount(!self.isFedexAccount());
                }
            });

            $(document).on('click change', '.billing-address-checkbox', function () {
                let sameAsShippingChecked = $('.billing-checkbox').is(':checked'),
                    currentCreditCard = false,
                    currentToken = $('.card-list.selected-card').data('token');

                let isShip,isPick;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }

                self.isBillingAddress(!sameAsShippingChecked);
                self.isBillingAddress.valueHasMutated();
                retailProfileSession?.output?.profile?.creditCards?.forEach((creditCard) => {
                    if (creditCard.profileCreditCardId == currentToken) {
                        currentCreditCard = creditCard;
                    }
                })

                if (isShip === 'true'){
                    if(!sameAsShippingChecked && currentCreditCard && currentCreditCard.billingAddress) {
                        let billingAddress = currentCreditCard.billingAddress;
                        billingAddress.street = [];
                        billingAddress.street.push(typeof (billingAddress.streetLines[0]) != 'undefined' ? billingAddress.streetLines[0] : '');
                        billingAddress.street.push(typeof (billingAddress.streetLines[1]) != 'undefined' ? billingAddress.streetLines[1] : '');
                        billingAddress.firstname = typeof (currentCreditCard.cardHolderName) != 'undefined' ? currentCreditCard.cardHolderName : '';
                        billingAddress.lastname = '';
                        self.fillBilling(billingAddress);
                    } else if (sameAsShippingChecked) {
                        let shippingAddressFromData = typeof (customerData.get('checkout-data')().shippingAddressFromData) !== "undefined" && customerData.get('checkout-data')().shippingAddressFromData !== null ? customerData.get('checkout-data')().shippingAddressFromData : false;
                        if (shippingAddressFromData) {
                            self.displayBillingSameShipping(shippingAddressFromData);
                        }
                    }
                } else if (isPick === 'true') {
                    if (currentCreditCard) {
                        let billingAddress = currentCreditCard.billingAddress;
                        billingAddress.street = [];
                        billingAddress.street.push(typeof (billingAddress.streetLines[0]) != 'undefined' ? billingAddress.streetLines[0] : '');
                        billingAddress.street.push(typeof (billingAddress.streetLines[1]) != 'undefined' ? billingAddress.streetLines[1] : '');
                        billingAddress.firstname = typeof (currentCreditCard.cardHolderName) != 'undefined' ? currentCreditCard.cardHolderName : '';
                        billingAddress.lastname = '';
                        self.fillBilling(billingAddress);
                    }
                }
                self.validateBillingForm();
            });

            $(document).on('keyup blur', '.name-card', function () {
                self.validateCardName();
            });

            $(document).on('keyup blur', '.card-number', function () {
                self.validateCardNumber();
            });

            // D-172367 :: POD2.0/SDE_able to enter special character(hyphen '-') in CVV field
            $(document).on('keypress', '.cvv-number', function (e) {
                if ((e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) || $(e.target).val().length == 5) {
                    return false;
                }
            });

            $(document).on('keyup blur', '.cvv-number', function () {
                self.validateCvvNumber();
            });

            $(document).on('keyup blur', '.address-one', function () {
                self.validateAddNumber();
            });

            $(document).on('keyup blur', '.address-two', function () {
                self.validateAddTwoNumber();
            });

            $(document).on('keyup blur', '.add-city', function () {
                self.validateCity();
            });

            $(document).on('keyup blur', '.add-state', function () {
                self.validateState();
            });

            self.selectedYear.subscribe(function (year) {
              self.isExpDateValid(self.validateYear());
              if(!year) {
                $('#expiration-year').addClass('contact-error');
              } else {
                $('#expiration-year').removeClass('contact-error');
                $('.exp-year-error').text("");
              }
            })

            // D-201896 : Zip code is allowing more than 9 characters
            $(document).on('input', '.add-zip', function (e) {
                var x = e.target.value.replace(/\D/g, '').match(/^(\d{0,5})(\d{0,4})/);
                e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2];
            });

            $(document).on('keyup blur', '.add-zip', function () {
                self.validateZip();
            });

            $(document).on('change input', '.cc-form-container', function () {
                self.validateBillingForm();
            });

            $(document).on('change', '#expiration-year', function () {
                if ($('#expiration-year').val() == new Date().getFullYear().toString() && parseInt($("#expiration-month").val()) < (new Date().getMonth() + 1)) {
                    $('.exp-date-error').text("Date occurs in the past");
                    $('#expiration-month').addClass('contact-error');
                    self.isExpDateValid(false);
                } else if ($('#expiration-year').val() && $("#expiration-month").val()) {
                    $('.exp-date-error').text("");
                    $('#expiration-month').removeClass('contact-error');
                    self.isExpDateValid(true);
                }
            });

            $(document).on('change', '#expiration-month', function () {
                if ($('#expiration-year').val() == new Date().getFullYear().toString() && parseInt($("#expiration-month").val()) < (new Date().getMonth() + 1)) {
                    $('.exp-date-error').text("Date occurs in the past");
                    $('#expiration-month').addClass('contact-error');
                    self.isExpDateValid(false);
                } else if ($('#expiration-year').val() && $("#expiration-month").val()) {
                    $('.exp-date-error').text("");
                    $('#expiration-month').removeClass('contact-error');
                    self.isExpDateValid(true);
                }
            });

            $(document).on('click', '.credit-card-review-button', async function () {
                jQuery('#warning-message-box').hide();
                var fedexAccountNumber = null;
                if ($(".applied-fedex-container").is(":visible") === true) {
                    fedexAccountNumber = $('.account-number').val().length > 0 ? self.fedexAccountNumber() : null;
                }
                var isFedexAccountApplied = self.isFedexAccountApplied();

                fedexAccountNumber = typeof (fedexAccountDiscount.prototype.fedexAccountAppliedNumber()) !== "undefined" && fedexAccountDiscount.prototype.fedexAccountAppliedNumber() !== null ?
                    fedexAccountDiscount.prototype.fedexAccountAppliedNumber() : null;
                isFedexAccountApplied = fedexAccountDiscount.prototype.isFedexAccount();

                // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
                if(window.e383157Toggle){
                    fxoStorage.set("useSiteCreditCard", false);
                }else{
                    localStorage.setItem("useSiteCreditCard", false);
                }
                // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
                if (self.isCompanyCreditCardAvailable() && self.companyCreditCard()) {
                    let obj = {
                        loginValidationKey: $('#validation_key').val(),
                        paymentMethod: self.isCreditCardSelected() ? 'cc' : 'fedex',
                        nameOnCard: self.companyCreditCardData()['nameOnCard'],
                        number: self.companyCreditCardData()['ccNumber'],
                        isBillingAddress: true,
                        isFedexAccountApplied: isFedexAccountApplied,
                        fedexAccountNumber: fedexAccountNumber,
                        creditCardType: self.greenCheckUrl(),
                        //  D-101910 : CC billing address is not correct in Order confirmation, Order history, Order history print preview pages when CC is pre-populated from site Admin.
                        billingAddress: {
                            state: self.companyCreditCardData()['state'],
                            company: '',
                            address: self.companyCreditCardData()['addressLine1'],
                            addressTwo: self.companyCreditCardData()['addressLine2'],
                            city: checkoutAdditionalScript.allowCityCharacters(self.companyCreditCardData()['city']),
                            zip: self.companyCreditCardData()['zipCode']
                        }
                    };
                    if(window.e383157Toggle){
                        fxoStorage.set("paymentData", obj);
                    }else{
                        localStorage.setItem("paymentData", JSON.stringify(obj));
                    }
                    // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
                    if(window.e383157Toggle){
                        fxoStorage.set("useSiteCreditCard", true);
                    }else{
                        localStorage.setItem("useSiteCreditCard", true);
                    }
                } else {
                    if(window.e383157Toggle){
                        fxoStorage.delete('isCardwarningMessage');
                        fxoStorage.delete('isCardSuccMessage');
                        fxoStorage.delete('isCardTokenExpaired');
                    }else{
                        localStorage.removeItem('isCardwarningMessage');
                        localStorage.removeItem('isCardSuccMessage');
                        localStorage.removeItem('isCardTokenExpaired');
                    }
                    let shippingAddress = quote.shippingAddress();
                    let state;
                    if(window.e383157Toggle){
                        state = fxoStorage.get('stateOrProvinceCode');
                    }else{
                        state = localStorage.getItem('stateOrProvinceCode');
                    }
                    if (self.isMixedQuote()) {
                        shippingAddress.address = shippingAddress.street[0];
                        shippingAddress.addressTwo = shippingAddress.street[1] ? shippingAddress.street[1] : '';
                        shippingAddress.zip = shippingAddress.postcode;
                        shippingAddress.state = state;
                    }

                    /**
                     * Set Credit Card Droupdown Information
                     */
                    if (isFclCustomer && fclCreditCardList.getCreditCardInfo("is_card")) {
                        self.creditCardNumber("");
                        self.getImageClassForCard(fclCreditCardList.getCreditCardInfo("type").toUpperCase());
                        let obj = {
                            loginValidationKey: $('#validation_key').val(),
                            profileCreditCardId: fclCreditCardList.getCreditCardInfo("token"),
                            paymentMethod: self.isCreditCardSelected() ? 'cc' : 'fedex',
                            number: fclCreditCardList.getCreditCardInfo("number"),
                            nameOnCard: fclCreditCardList.getCreditCardInfo("nameOnCard"),
                            isBillingAddress: self.isBillingAddress(),
                            isFedexAccountApplied: isFedexAccountApplied,
                            fedexAccountNumber: fedexAccountNumber,
                            creditCardType: self.greenCheckUrl(),
                            billingAddress: self.isBillingAddress() ? {
                                state: self.selectedState(),
                                company: $('.company-name').val(),
                                address: $('.address-one').val(),
                                addressTwo: $('.address-two').val(),
                                city: checkoutAdditionalScript.allowCityCharacters($('.add-city').val()),
                                zip: $('.add-zip').val()
                            } : shippingAddress
                        };

                        if(window.e383157Toggle){
                            fxoStorage.set("paymentData", obj);
                            if (fclCreditCardList.getCreditCardInfo("is_token_expaired") === 'true') {
                                fxoStorage.set('isCardTokenExpaired', true);
                            } else {
                                fxoStorage.delete('isCardTokenExpaired');
                            }
                        }else{
                            localStorage.setItem("paymentData", JSON.stringify(obj));
                            if (fclCreditCardList.getCreditCardInfo("is_token_expaired") === 'true') {
                                localStorage.setItem('isCardTokenExpaired', true);
                            } else {
                                localStorage.removeItem('isCardTokenExpaired');
                            }
                        }


                    } else {
                        let obj = {
                            loginValidationKey: $('#validation_key').val(),
                            paymentMethod: self.isCreditCardSelected() ? 'cc' : 'fedex',
                            year: self.selectedYear(),
                            expire: $('.expiration-month').val(),
                            nameOnCard: $('.name-card').val(),
                            number: $('.card-number').val().replaceAll(' ', ''),
                            cvv: $('.cvv-number').val(),
                            isBillingAddress: self.isBillingAddress(),
                            isFedexAccountApplied: isFedexAccountApplied,
                            fedexAccountNumber: fedexAccountNumber,
                            creditCardType: self.greenCheckUrl(),
                            billingAddress: self.isBillingAddress() ? {
                                state: self.selectedState(),
                                company: $('.company-name').val(),
                                address: $('.address-one').val(),
                                addressTwo: $('.address-two').val(),
                                city: checkoutAdditionalScript.allowCityCharacters($('.add-city').val()),
                                zip: $('.add-zip').val()
                            } : shippingAddress
                        };

                        obj.year = obj.year.toString();
                        const yr = obj.year.substring(2, 4);

                        const expirelength = obj.expire.length;
                        if (expirelength == 1) {
                            obj.expire = '0' + obj.expire;
                        }
                        const manualCC = 'M' + self.creditCardNumber() + '=' + yr + obj.expire + ':' + obj.cvv;
                        var pubKey;
                        if(window.e383157Toggle){
                            pubKey = fxoStorage.get('encryptedKey');
                            fxoStorage.set("paymentData", obj);
                        }else{
                            pubKey = localStorage.getItem('encryptedKey');
                            localStorage.setItem("paymentData", JSON.stringify(obj));
                        }

                        if (explorersD179523Toggle) {
                            window.location.hash = '';
                            window.location.hash = 'payment';
                        }

                        var encryptedCreditCard = self.fetchEncryptedCreditCard(manualCC, pubKey);
                        if(window.e383157Toggle){
                            if (isFclCustomer) {
                                fxoStorage.set("creditCardNumber", self.creditCardNumber());
                            }
                            fxoStorage.set("encryptedPaymentData", encryptedCreditCard);
                        }else{
                            if (isFclCustomer) {
                                localStorage.setItem("creditCardNumber", self.creditCardNumber());
                            }
                            localStorage.setItem("encryptedPaymentData", encryptedCreditCard);
                        }
                    }
                }

                if (isFclCustomer) {
                    $(".express-msg-outer-most-credit.auth-failed-error").remove();
                    $(".card-number, .name-card, .expiration-month, .expiration-year, .cvv-number").removeClass("contact-error");
                    $(".term-condition-error").remove();
                    $('.credit-card-info').html('');
                    if ($('.set-credit-card-checkbox').is(':checked')) {
                        if ($('.terms-and-condition-checkbox').is(':checked')) {
                            let recaptchaToken = await reCaptcha.generateRecaptchaToken('checkout_cc');

                            let cardType = fclCreditCardList.getCardType(self.creditCardNumber());
                            let status = fclCreditCardList.saveCreditCardInfo(encryptedCreditCard, cardType, recaptchaToken);
                            if (status === false) {
                                return false;
                            } else {
                                let isExpressCheckout = window.e383157Toggle ? fxoStorage.get('express-checkout') : localStorage.getItem('express-checkout')
                                if ($(".card-list").length == 1 && isExpressCheckout) {
                                    $(".credit-card-dropdown").show();
                                }
                            }
                        } else {
                            $(".checkmark-terms-condition-container").after('<p class="term-condition-error">This field is required.</p>');
                            return false;
                        }
                    }
                }

                window.dispatchEvent(new Event('closeNonCombinableDiscount'));
                window.dispatchEvent(new Event('closeMarketplaceDisclaimer'));
                window.location.hash = '';
                window.location.hash = 'payment';
                let isReview = window.location.hash;

                if(isReview == '#payment'){
                    $(".opc-block-summary .table-totals .incl .isnot_review_page").hide();
                    $(".opc-block-summary .table-totals .incl .is_review_page").show();
            	}
            });

            $(document).on('keyup blur', '.fedex-account-number', function () {
                var value1 = self.fedexAccountNumber().trim();
                var value2 = $('.fedex-account-number').val().trim();

                if (value1.length == 0 && value2.length == 0) {
                    $('.fedex-account-number').addClass('contact-error');
                    $(".fedex-account-number-error").css("display", "flex");
                    $(".fedex-account-number-error").html('<span class="fedex-icon-error"></span>This field is required.');
                    $(".fedex-account-number-review-button").attr('disabled', 'disabled');
                    $(".fedex-account-number-review-button").addClass("fedex-account-number-review-button-disabled");
                    $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                    $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                } else if (isNaN(value1)) {
                    $('.fedex-account-number').addClass('contact-error');
                    $(".fedex-account-number-error").show();
                    $(".fedex-account-number-error").html('Please enter valid account number.');
                    $(".fedex-account-number-review-button").attr('disabled', 'disabled');
                    $(".fedex-account-number-review-button").addClass("fedex-account-number-review-button-disabled");
                    $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                    $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                } else {
                    $('.fedex-account-number').removeClass('contact-error');
                    $(".fedex-account-number-error").hide();
                    $(".fedex-account-number-error").empty();
                    $(".fedex-account-number-review-button").prop("disabled", false);
                    $(".fedex-account-number-review-button").removeClass("fedex-account-number-review-button-disabled");
                }

                let validate = true;
                $('.custom-billing-input:visible').each(function() {
                    if($(this).hasClass('error-highlight')){
                        $(".fedex-account-number-review-button").prop('disabled', true);
                        return validate = false;
                    }
                });
            });

            if (self.isHidePoReferenceId()) {
                $(document).on('keypress', '.po-reference-id', function (e) {
                    if (e.which == 32) {
                        return false;
                    }
                });
            };

            $(document).on('click', '.fedex-account-number-review-button', function () {
                // B-1294428 : CC payment details to be passed in Order Submit call when the CC id configured in Admin
                if(window.e383157Toggle){
                    fxoStorage.set("useSiteCreditCard", false);
                }else{
                    localStorage.setItem("useSiteCreditCard", false);
                }
                if (self.isHidePoReferenceId()) {
                    if ($(".additional-billing-info").is(":visible") === true) {
                        var referenceId = $('.po-reference-id').val();
                        if (!referenceId) {
                            referenceId = null;
                        }
                        self.poReferenceId(referenceId);
                    }
                }
                var fedexAccountNumber = self.fedexAccountNumber();
                var obj = {
                    loginValidationKey: $('#validation_key').val(),
                    paymentMethod: self.isCreditCardSelected() ? 'cc' : 'fedex',
                    fedexAccountNumber: fedexAccountNumber,
                    poReferenceId: self.poReferenceId()
                };
                if(window.e383157Toggle){
                    fxoStorage.set("paymentData", obj);
                }else{
                    localStorage.setItem("paymentData", JSON.stringify(obj));
                }
                self.validateFedexAccount();
                $(".opc-block-summary .table-totals .incl .isnot_review_page").hide();
                $(".opc-block-summary .table-totals .incl .is_review_page").show();
            });

            $(document).on('click', '.apply-account', function () {
                if(window.e383157Toggle){
                    fxoStorage.set('removedFedexAccount', "false");
                }else{
                    localStorage.setItem('removedFedexAccount', "false");
                }
                if ($('.account-number').val().length > 0) {
                    self.validateAccount();
                } else {
                    $(".invalid-account-error").show();
                    $(".invalid-account-error").html('This field is required.');
                }
            });

            $(document).on('click', '.fedex-acc-remove-button', function () {
                if(window.e383157Toggle){
                    fxoStorage.set('removedFedexAccount', "true");
                }else{
                    localStorage.setItem('removedFedexAccount', "true");
                }
                self.fedexAccountNumber('');
                $('.account-number').val('');
                if(window.e383157Toggle){
                    fxoStorage.set('fedexAccount',"null");
                }else{
                    localStorage.setItem('fedexAccount',"null");
                }
                self.validateAccount();
                self.isFedexAccountApplied(false);
                $(".account-number").prop("disabled", false);
                $(".apply-account").prop("disabled", false);
                $(".credit-card-review-button").attr('disabled', 'disabled');
                $(".credit-card-review-button").addClass("place-pickup-order-disabled");
            });

            $(document).ready(function () {
                var totalNetAmount;
                if(window.e383157Toggle){
                    totalNetAmount = fxoStorage.get("EstimatedTotal");;
                }else{
                    totalNetAmount = localStorage.getItem("EstimatedTotal");
                }
                if (totalNetAmount) {
                    setTimeout(() => {
                        $(".grand.totals.incl .price").text(totalNetAmount);
                        $(".grand.totals .amount .price").text(totalNetAmount);
                    }, 5000);
                }
            });

            $(document).on('click', '.card-list', function () {
                let isShip,isPick;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }
                $('.card-list').removeClass('selected-card');
                $(this).addClass('selected-card');
                if ($(this).hasClass('company-card')) {
                    self.isCompanyCreditCardAvailable(true);
                    self.displayBillingAddress();
                    self.companyCreditCard(true);
                    self.showBillingCheckbox(true);
                    $('.billing-address-checkbox-container').hide();
                    $('.billing-checkbox').prop('checked', true).change();
                } else if ($(this).hasClass('last')) {
                    $('.billing-checkbox').prop('checked', false).change();
                    self.isCompanyCreditCardAvailable(false);
                    if (isShip === 'true') {
                        $('.billing-checkbox').prop('checked', true).change();
                        $('.billing-address-checkbox-container').show();
                    } else if (isPick === 'true') {
                        $('.billing-address-checkbox-container').hide();
                    }
                } else {
                    self.isCompanyCreditCardAvailable(false);
                    $('.billing-checkbox').prop('checked', false).change();
                    if (isShip === 'true') {
                        $('.billing-checkbox').prop('checked', true).change();
                        self.showBillingCheckbox(true);
                    } else if (isPick === 'true') {
                        self.showBillingCheckbox(false);
                    }
                    if (typeof (retailProfileSession.output.profile.creditCards) != 'undefined') {
                        let currentCreditCard = false;
                        let currentToken = $(this).data('token');
                        retailProfileSession.output.profile.creditCards.forEach((creditCard) => {
                            if (creditCard.profileCreditCardId == currentToken) {
                                currentCreditCard = creditCard;
                            }
                        })
                        if (currentCreditCard.billingAddress) {
                            let billingAddress = currentCreditCard.billingAddress;
                            billingAddress.street = [];
                            billingAddress.street.push(typeof (billingAddress.streetLines[0]) != 'undefined' ? billingAddress.streetLines[0] : '');
                            billingAddress.street.push(typeof (billingAddress.streetLines[1]) != 'undefined' ? billingAddress.streetLines[1] : '');
                            billingAddress.firstname = typeof (currentCreditCard.cardHolderName) != 'undefined' ? currentCreditCard.cardHolderName : '';
                            billingAddress.lastname = '';
                            if (isShip === 'true') {
                                if (!$('.billing-checkbox').is(':checked')) {
                                    self.fillBilling(billingAddress);
                                } else {
                                    let shippingAddressFromData = typeof (customerData.get('checkout-data')().shippingAddressFromData) !== "undefined" && customerData.get('checkout-data')().shippingAddressFromData !== null ? customerData.get('checkout-data')().shippingAddressFromData : false;
                                    if (shippingAddressFromData) {
                                        self.displayBillingSameShipping(shippingAddressFromData);
                                    }
                                }
                                $('.billing-address-checkbox-container').show();
                            } else if (isPick === 'true') {
                                self.fillBilling(billingAddress);
                            }
                            self.companyCreditCard(false);

                            self.showCardNameField(false);
                            self.showCardNumberField(false);
                            self.showExpCvvFields(false);
                            $(".credit-card-review-button").prop("disabled", false);
                            $(".credit-card-review-button").removeClass("place-pickup-order-disabled");
                            isCCFormValid = true;
                            // Make sure to test custom billing fields
                            checkToDisableReviewBtn('creditcard');
                        }
                    }
                }
            })
            window.dispatchEvent(new Event('on_change_delivery_method'));
            return this;
        },

        goToCart: function () {
            window.location.href = urlBuilder.build('checkout/cart/');
        },

        goToShipping: function () {
            window.dispatchEvent(new Event('breadcrumb_go_to_shipping'));
        },

        goToPickup: function () {
            window.dispatchEvent(new Event('breadcrumb_go_to_pickup'));
        },

        validateAccount: function () {
            var self = this;
            $('.account-number').removeClass('contact-error');
            $(".invalid-account-error").text("");
            $(".invalid-account-error").hide();
            var account = self.fedexAccountNumber();
            if(window.e383157Toggle){
                fxoStorage.set("TaxAmount", '');
                fxoStorage.set("EstimatedTotal", '');
            }else{
                localStorage.setItem("TaxAmount", '');
                localStorage.setItem("EstimatedTotal", '');
            }
            let pickupDateTimeForApi;
            if(window.e383157Toggle){
                pickupDateTimeForApi = fxoStorage.get("pickupDateTimeForApi");
            }else{
                pickupDateTimeForApi = localStorage.getItem("pickupDateTimeForApi");
            }
            let selectedProductionId = null;
            if (techTitansProductionLocationFix && fxoStorage.get("selected_production_id") !== undefined && fxoStorage.get("selected_production_id") !== '') {
                selectedProductionId = fxoStorage.get("selected_production_id");
            }
            let requestUrl = "pay/index/payrateapishipandpick";
            var removedFedexAccount;
            if(window.e383157Toggle){
                removedFedexAccount = fxoStorage.get("removedFedexAccount");
            }else{
                removedFedexAccount = localStorage.getItem("removedFedexAccount");
            }
            var fedexAccountShipping;
            if(window.e383157Toggle){
                fedexAccountShipping = fxoStorage.get('shipping_account_number') && fxoStorage.get('shipping_account_number') !== '' ? fxoStorage.get('shipping_account_number') : '';
            }else{
                fedexAccountShipping = localStorage.getItem('shipping_account_number') && localStorage.getItem('shipping_account_number') !== '' ? localStorage.getItem('shipping_account_number') : '';
            }
            $.ajax({
                url: urlBuilder.build(
                    requestUrl
                ),
                type: "POST",
                data: { fedexAccount: account, shippingAccount: fedexAccountShipping, removedFedexAccount: removedFedexAccount, requestedPickupLocalTime: pickupDateTimeForApi, selectedProductionId: selectedProductionId },//B-1501795
                dataType: "json",
                showLoader: true,
                async: true,
                success: function (data) {
                    if (typeof data !== 'undefined' && data.length < 1) {
                        $('.error-container').removeClass('api-error-hide');
                        $('.loadersmall').hide();
                        return true;
                    } else if (data.hasOwnProperty("errors")) {
                        $('.error-container').removeClass('api-error-hide');
                        return true;
                    } else if (!data.hasOwnProperty("errors") || data.hasOwnProperty("alerts")){
                        $('.error-container').addClass('api-error-hide');
                        if ($(".selected-paymentype").hasClass("select-credit-card")) {
                            $(".credit-card-review-button").show();
                        } else {
                            $(".fedex-account-number-review-button").show();
                        }
                    }

                    if (data && data.alerts && data.alerts.length > 0) {
                        $('.account-number').addClass('contact-error');
                        $(".invalid-account-error").text("The account number entered is invalid.");
                        $(".invalid-account-error").show();
                    } else {
                        if (self.fedexAccountNumber().length > 0) {
                            self.isFedexAccountApplied(true);
                            if(window.e383157Toggle){
                                fxoStorage.set('fedexAccount',account);
                            }else{
                                localStorage.setItem('fedexAccount',account);
                            }
                            $('.applied-fedex-value').text("Ending in *" + account.substring(account.length - 4));
                            $(".account-number").attr('disabled', 'disabled');
                            $(".apply-account").attr('disabled', 'disabled');
                            self.validateBillingForm();
                        }
                    }
                }
            }).done(function (response) {
                rateQuoteErrorsHandler.errorHandler(response, false);
                if (typeof response !== 'undefined' && response.length < 1) {
                    $('.error-container').removeClass('api-error-hide');
                    $('.loadersmall').hide();
                    return true;
                } else if (response.hasOwnProperty("errors")) {
                    $('.error-container').removeClass('api-error-hide');
                    if (
                        typeof response.errors.is_timeout != 'undefined' &&
                        response.errors.is_timeout != null
                        ) {
                        window.location.replace(orderConfirmationUrl);
                    }
                    return true;
                } else if (!response.hasOwnProperty("errors") || response.hasOwnProperty("alerts")){
                    $('.error-container').addClass('api-error-hide');
                    if ($(".selected-paymentype").hasClass("select-credit-card")) {
                        $(".credit-card-review-button").show();
                    } else {
                        $(".fedex-account-number-review-button").show();
                    }
                }

                if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                    window.location.replace(orderConfirmationUrl);
                }
                response.rate = response.rateQuote;
                response.rate.rateDetails = response.rateQuote.rateQuoteDetails;
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
                shippingAmount = 0;
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

                if (typeof (response) != "undefined" && typeof (response?.rate?.rateDetails) != "undefined") {
                    if (window.checkoutConfig.hco_price_update && response.rate.rateDetails[0].productLines != undefined) {
                        var productLines = response.rate.rateDetails[0].productLines;
                        productLines.forEach((productLine) => {
                            var instanceId = productLine.instanceId;
                            var itemRowPrice = productLine.productRetailPrice;
                            itemRowPrice = self.priceFormatWithCurrency(itemRowPrice);
                            $(".subtotal." + instanceId + " .cart-price .price").html(itemRowPrice);
                            $(".subtotal-instance").show();
                            $(".checkout-normal-price").hide();
                        });
                    }

                    response.rate.rateDetails.forEach((rateDetail) => {
                        if (typeof rateDetail.deliveryLines != "undefined") {
                            rateDetail.deliveryLines.forEach((deliveryLine) => {
                                if(typeof deliveryLine.deliveryLineDiscounts != "undefined"){
                                    var shippingDiscountPrice = 0;
                                    deliveryLine.deliveryLineDiscounts.forEach((deliveryLineDiscount) => {
                                        if (deliveryLineDiscount['type'] == 'COUPON' ||
                                            ((window.checkout.mazegeek_b2352379_discount_breakdown === true || window.checkoutConfig.mazegeek_b2352379_discount_breakdown === true) &&
                                                deliveryLineDiscount['type'] == 'CORPORATE')) {
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
                            } else if (shippingDiscountAmount>0) {
                                promoDiscountAmount = (discountResult['promoDiscountAmount'] > 0) ? discountResult['promoDiscountAmount']-shippingDiscountAmount : 0;
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
                    } else {
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
                    sortedAmounts.forEach(function(amount,index){
                        if(amount.price){
                        accountDiscountHtml = '<tr class="'+amount.type+' discount"><th class="mark" scope="row">'+amount.label+'</th><td class="amount"><span class="price">-'+ self.priceFormatWithCurrency(amount.price); +'</span></td></tr>';
                            $(".discount_breakdown tbody").append(accountDiscountHtml);
                            if($('.toggle-discount th #discbreak').length == 0){
                                $('.toggle-discount th').append('<span id="discbreak" tabindex="0" class="arrow down"></span>');
                            }
                        } else {
                            $(".discount_breakdown tbody tr."+amount.type).remove();
                        }
                    });
                shippingFormAdditionalScript.handleEstimatedShippingTotal(estimatedShippingTotal);
            });
        },

        /**When review order button is clicked */
        validateFedexAccount: function () {
            var self = this;
            $('.fedex-account-number').removeClass('contact-error');
            $(".fedex-account-number-error").text("");
            $(".fedex-account-number-error").hide();

            var fedexAccountNumber = self.fedexAccountNumber();
            if(window.e383157Toggle){
                fxoStorage.set("TaxAmount", '');
                fxoStorage.set("EstimatedTotal", '');
            }else{
                localStorage.setItem("TaxAmount", '');
                localStorage.setItem("EstimatedTotal", '');
            }
            let requestUrl = "pay/index/payrateapishipandpick";
            let pickupDateTimeForApi;
            if(window.e383157Toggle){
                pickupDateTimeForApi = fxoStorage.get("pickupDateTimeForApi");
            }else{
                pickupDateTimeForApi = localStorage.getItem("pickupDateTimeForApi");
            }
            let selectedProductionId = null;
            if (techTitansProductionLocationFix && fxoStorage.get("selected_production_id") !== undefined && fxoStorage.get("selected_production_id") !== '') {
                selectedProductionId = fxoStorage.get("selected_production_id");
            }

            $.ajax({
                url: urlBuilder.build(
                    requestUrl
                ),
                type: "POST",
                data: { fedexAccount: fedexAccountNumber, requestedPickupLocalTime: pickupDateTimeForApi, selectedProductionId: selectedProductionId },
                dataType: "json",
                showLoader: true,
                async: true,
                success: async function (data) {
                    if (typeof data !== 'undefined' && data.length < 1) {
                        $('.error-container').removeClass('api-error-hide');
                        $('.loadersmall').hide();
                        return true;
                    } else if (data.hasOwnProperty("errors")) {
                        $('.error-container').removeClass('api-error-hide');
                        $('.loadersmall').hide();
                        if (
                            typeof data.errors.is_timeout != 'undefined' &&
                            data.errors.is_timeout != null
                        ) {
                            var baseUrl = window.BASE_URL;
                            var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";
                            window.location.replace(orderConfirmationUrl);
                        }
                        return true;
                    }

                    let responseWarnings = false;
                    if (rateQuoteAlertsHandler.warningHandler(data)) {
                        responseWarnings = true;
                    }

                    if (data && data.alerts && data.alerts.length > 0 && responseWarnings) {
                        if (typeof data.is_timeout != 'undefined' && data.is_timeout != null) {
                            window.location.replace(orderConfirmationUrl);
                        }
                        data.rate = data.rateQuote;
                        data.rate.rateDetails = data.rateQuote.rateQuoteDetails;

                        let accountDiscount = false;
                        let couponDiscount = false;
                        data.rate.rateDetails.forEach((rateDetail) => {
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

                        if (accountDiscount || couponDiscount) {

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
                                    window.dispatchEvent(new Event('nonCombinableDiscount'));
                                    if(window.e383157Toggle){
                                        fxoStorage.set('coupon_code','');
                                    }else{
                                        localStorage.setItem('coupon_code','');
                                    }
                                }
                                $(".fedex-account-block-applied").show();
                                $(".fedex-account-block").hide();
                                $(".fedex-account-title.discount-title").hide();
                                $(".fedex-account-number-review-button").prop("disabled", false);
                                $(".fedex-account-number-review-button").removeClass("fedex-account-number-review-button-disabled");

                                var masked = fedexAccountNumber.length > 4 ? "*" + fedexAccountNumber.substr(-4) : fedexAccountNumber;
                                $(".applied-account-four-digit").text(masked);
                                if ($(".applied-account-four-digit").text()) {
                                    if(window.e383157Toggle){
                                        fxoStorage.set('fedexAccount',fedexAccountNumber);
                                    }else{
                                        localStorage.setItem('fedexAccount',fedexAccountNumber);
                                    }
                                    fedexAccountDiscount.prototype.isFedexAccount(true);
                                    fedexAccountDiscount.prototype.fedexAccountNumber(masked);
                                    fedexAccountDiscount.prototype.showFedexAccount(false);
                                    fedexAccountDiscount.prototype.fedexAccountAppliedNumber(fedexAccountNumber);
                                }
                                if(window.e383157Toggle){
                                    fxoStorage.set('fedexAccountApplied',true);
                                }else{
                                    localStorage.setItem('fedexAccountApplied',true);
                                }
                            } else {
                                if (!$(".account-num-container").is(":visible")) {
                                    $(".fedex-account-list-error").text("Selected account number is invalid.");
                                    $(".fedex-account-list-error").show();
                                    $('.selected-fedex-account').addClass('contact-error');
                                    $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                                    $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                                }
                                $('.fedex-account-number').addClass('contact-error');
                                $(".fedex-account-number-error").text("The account number entered is invalid.");
                                $(".fedex-account-number-error").show();
                                $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                                $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                            }
                        } else {
                            if (!$(".account-num-container").is(":visible")) {
                                $(".fedex-account-list-error").text("Selected account number is invalid.");
                                $(".fedex-account-list-error").show();
                                $('.selected-fedex-account').addClass('contact-error');
                                $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                                $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                            }
                            $('.fedex-account-number').addClass('contact-error');
                            $(".fedex-account-number-error").text("The account number entered is invalid.");
                            $(".fedex-account-number-error").show();
                            $(".opc-block-summary .table-totals .incl .isnot_review_page").show();
                            $(".opc-block-summary .table-totals .incl .is_review_page").hide();
                        }
                    } else {
                        var masked = fedexAccountNumber.length > 4 ? "*" + fedexAccountNumber.substr(-4) : fedexAccountNumber;
                        $(".applied-account-four-digit").text(masked);
                        if ($(".applied-account-four-digit").text()) {
                            if(window.e383157Toggle){
                                fxoStorage.set('fedexAccount',fedexAccountNumber);
                            }else{
                                localStorage.setItem('fedexAccount',fedexAccountNumber);
                            }
                            fedexAccountDiscount.prototype.isFedexAccount(true);
                            fedexAccountDiscount.prototype.fedexAccountNumber(masked);
                            fedexAccountDiscount.prototype.showFedexAccount(false);
                            fedexAccountDiscount.prototype.fedexAccountAppliedNumber(fedexAccountNumber);
                        }
                        let isFedexAccountList;
                        if(window.e383157Toggle){
                            isFedexAccountList = fxoStorage.get("isFedexAccountList");
                        }else{
                            isFedexAccountList = localStorage.getItem("isFedexAccountList");
                        }
                        if (isFclCustomer && isFedexAccountList) {
                            if(window.e383157Toggle){
                                fxoStorage.set('selectedfedexAccount', fedexAccountNumber);
                            }else{
                                localStorage.setItem('selectedfedexAccount', fedexAccountNumber);
                            }
                            if ($('#save_fedex_account_number').is(":checked")) {
                                let manualFedexAccountNumber = $("#fedex-account-number").val();
                                if (retailProfileSession && manualFedexAccountNumber.trim()) {
                                    let userProfileId = typeof (retailProfileSession.output.profile.userProfileId) !== "undefined" && retailProfileSession.output.profile.userProfileId !== null ? retailProfileSession.output.profile.userProfileId : false;
                                    if (userProfileId) {
                                        let lastNo = fedexAccountNumber.substr(-4);
                                        let nickName = 'FedEx Account ' + lastNo;
                                        let billingReference = "NULL";
                                        let isPrimary = '';
                                        if ($(".fedex-account-value").length == 1) {
                                            isPrimary = true;
                                        }
                                        let recaptchaToken = await reCaptcha.generateRecaptchaToken("checkout_fedex_account");
                                        let addFedexAccountResponse = fclFedexAccountList.addFedexAccountToProfile(userProfileId, fedexAccountNumber, nickName, billingReference, isPrimary, recaptchaToken);
                                        let msgHtml = null;
                                        if ((typeof (addFedexAccountResponse.errors) != 'undefined' || typeof (addFedexAccountResponse.error) != 'undefined' || typeof (addFedexAccountResponse.status) != 'undefined')
                                            && (addFedexAccountResponse.errors || addFedexAccountResponse.error || addFedexAccountResponse.status === 'recaptcha_error')) {
                                            if(window.e383157Toggle){
                                                fxoStorage.set('isShowErrMessage', true);
                                                fxoStorage.delete('isShowSuccMessage');
                                            }else{
                                                localStorage.setItem('isShowErrMessage', true);
                                                localStorage.removeItem('isShowSuccMessage');
                                            }
                                            let errorMsg = 'Your fedex account could not be saved at this time, but you can continue checking out.';
                                            msgHtml = '<div class="fedex-account-err-msg"><span><img src="' + self.expressInfoIcon() + '"/></span>' + errorMsg + '</div>';

                                            if ($(".fedex-account-err-msg")) {
                                                $(".fedex-account-err-msg").remove();
                                            }
                                            $(".pay-by-account-container").append(msgHtml);
                                        } else {
                                            $(".fcl-fedex-account-list").show();
                                            $(".selected-fedex-account .fedex-account-show").text(nickName + ' - ' + lastNo);
                                            $(".custom-fedex-account-list-container ul").prepend('<li class="fedex-account-value" data-value="' + fedexAccountNumber + '" data-selected="0">' + nickName + ' - ' + lastNo + ' </li>');
                                            $(".account-num-container").hide();
                                            $(".save-fedex-account-chk-container").hide();
                                            $("#save_fedex_account_number").prop('checked', false);
                                            if (window.e383157Toggle) {
                                                fxoStorage.delete("isShowErrMessage");
                                                fxoStorage.set('isShowSuccMessage', true);
                                            } else {
                                                localStorage.removeItem('isShowErrMessage');
                                                localStorage.setItem('isShowSuccMessage', true);
                                            }
                                            let successMsg = 'Payment method successfully saved to your profile.';
                                            msgHtml = '<div class="express-msg-outer-most"><div class="express-msg-outer-container"><div class="express-succ-msg-container"><span class="icon-container"><img class="img-check-icon" alt="Check icon" src="' + self.checkIcon() + '"></span><span class="message">' + successMsg + '</span><img id="express_msg_close" class="img-close-msg" alt="close icon" src="' + self.crossIcon() + '" tabindex="0"></div> </div></div>';
                                            if ($(".express-msg-outer-most")) {
                                                $(".express-msg-outer-most").remove();
                                            }
                                            $(msgHtml).insertAfter(".opc-progress-bar");
                                        }
                                    }
                                }
                            }
                        }
                        if (isSdeStore || isSelfRegCustomer) {
                            stepNavigator.next();
                        } else {
                            window.location.hash = '';
                            window.location.hash = 'payment';
                        }
                    }
                }
            }).done(function (response) {

                rateQuoteErrorsHandler.errorHandler(response, false);
                if (typeof response !== 'undefined' && response.length < 1) {
                    $('.error-container').removeClass('api-error-hide');
                    $('.loadersmall').hide();
                    return true;
                } else if (response.hasOwnProperty("errors")) {
                    $('.error-container').removeClass('api-error-hide');
                    $('.loadersmall').hide();
                    if (
                        typeof response.errors.is_timeout != 'undefined' &&
                        response.errors.is_timeout != null
                        ) {
                        window.location.replace(orderConfirmationUrl);
                    }
                    return true;
                }

                // new code
                if (response == 'discountExist') {
                    $("#warning-message-box").show();
                    return false;
                } else {
                    $("#warning-message-box").hide();
                }
                if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                    window.location.replace(orderConfirmationUrl);
                }
                response.rate = response.rateQuote;
                response.rate.rateDetails = response.rateQuote.rateQuoteDetails;
                // new code
                const calculate = shippingService.calculateDollarAmount(
                    response.rate
                );
                var shippingAmount = shippingService.getShippingLinePrice(
                    response.rate
                );

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
                shippingAmount = 0;
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
                    response.rate.rateDetails.forEach((rateDetail) => {
                        if (window.checkoutConfig.hco_price_update && response.rate.rateDetails[0].productLines != undefined) {
                            var productLines = response.rate.rateDetails[0].productLines;
                            productLines.forEach((productLine) => {
                                var instanceId = productLine.instanceId;
                                var itemRowPrice = productLine.productRetailPrice;
                                itemRowPrice = self.priceFormatWithCurrency(itemRowPrice);
                                $(".subtotal." + instanceId + " .cart-price .price").html(itemRowPrice);
                                $(".subtotal-instance").show();
                                $(".checkout-normal-price").hide();
                            });
                        }

                        if (typeof rateDetail.deliveryLines != "undefined") {
                            rateDetail.deliveryLines.forEach((deliveryLine) => {
                                if(typeof deliveryLine.deliveryLineDiscounts != "undefined"){
                                    var shippingDiscountPrice = 0;
                                    deliveryLine.deliveryLineDiscounts.forEach((deliveryLineDiscount) => {
                                        if (deliveryLineDiscount['type'] == 'COUPON' || ((window.checkout.mazegeek_b2352379_discount_breakdown === true || window.checkoutConfig.mazegeek_b2352379_discount_breakdown === true) &&
                                            deliveryLineDiscount['type'] == 'CORPORATE')) {
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
                            } else if (shippingDiscountAmount>0) {
                                promoDiscountAmount = (discountResult['promoDiscountAmount'] > 0) ? discountResult['promoDiscountAmount'] - shippingDiscountAmount : 0;
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
                    } else {
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
                shippingFormAdditionalScript.handleEstimatedShippingTotal(estimatedShippingTotal);
                let accountDiscountHtml = '';

                    if(accountDiscountAmount || volumeDiscountAmount || bundleDiscountAmount || promoDiscountAmount || shippingDiscountAmount){
                        $(".discount_breakdown tbody tr.discount").remove();
                    }
                    if(accountDiscountAmount == 0 && volumeDiscountAmount == 0 && bundleDiscountAmount == 0 && promoDiscountAmount ==0 && shippingDiscountAmount == 0){
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
                    sortedAmounts.forEach(function(amount,index){
                        if(amount.price){
                        accountDiscountHtml = '<tr class="'+amount.type+' discount"><th class="mark" scope="row">'+amount.label+'</th><td class="amount"><span class="price">-'+ self.priceFormatWithCurrency(amount.price); +'</span></td></tr>';
                            $(".discount_breakdown tbody").append(accountDiscountHtml);
                            if($('.toggle-discount th #discbreak').length == 0){
                                $('.toggle-discount th').append('<span id="discbreak" tabindex="0" class="arrow down"></span>');
                            }
                        } else {
                            $(".discount_breakdown tbody tr."+amount.type).remove();
                        }
                    });

            });
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

        validateCCForm: function () {
            var self = this;
            if ($(".name-card").val().length > 0 &&
                (($(".card-number").val().length == 5 && $(".card-number").val().includes("*")) || $(".card-number").val().length >= 17) &&
                $(".cvv-number").val().length >= 3 && self.isExpDateValid()) {
                $(".credit-card-review-button").prop("disabled", false);
                $(".credit-card-review-button").removeClass("place-pickup-order-disabled");
                isCCFormValid = true;
            } else {
                if (isFclCustomer) {
                    if (!fclCreditCardList.getCreditCardInfo("is_card")) {
                        $(".credit-card-review-button").attr('disabled', 'disabled');
                        $(".credit-card-review-button").addClass("place-pickup-order-disabled");
                    } else {
                        $(".credit-card-review-button").prop("disabled", false);
                        $(".credit-card-review-button").removeClass("place-pickup-order-disabled");
                        isCCFormValid = true;
                    }
                } else {
                    $(".credit-card-review-button").attr('disabled', 'disabled');
                    $(".credit-card-review-button").addClass("place-pickup-order-disabled");
                }
            }
        },

        validateYear: function () {
            var minYear = new Date().getFullYear();
            var ExpYear = parseInt($('#expiration-year').val(), 10);

            if (parseInt($("#expiration-month").val()) < (new Date().getMonth() + 1)) {
                if (ExpYear < minYear || ExpYear == minYear) {
                    return false;
                } else if (ExpYear >= minYear) {
                    $('.exp-date-error').text('');
                    $('.exp-year-error').text("");
                    $('#expiration-month').removeClass('contact-error');
                    $('#expiration-year').removeClass('contact-error');
                    return true;
                }
            }

            if ($('#expiration-year').val() == '' || $('.expiration-month').val() == null) {
                if ($('.expiration-month').val() == null) {
                    $('.exp-date-error').text('Please enter valid date.');
                    $('#expiration-month').addClass('contact-error');
                } else {
                    $('#expiration-year').addClass('contact-error');
                    $('.exp-year-error').css('display', 'block');
                    $('.exp-date-error').text('');
                    $('#expiration-month').removeClass('contact-error');
                    $('.exp-year-error').text("Year is required.");
                }

                $(".credit-card-review-button").attr('disabled', 'disabled');
                $(".credit-card-review-button").addClass("place-pickup-order-disabled");

                return false;
            } else {
                $('.exp-year-error').text("");
                $('#expiration-year').removeClass('contact-error');

                return true;
            }
        },

        validateBillingForm: function () {
            var self = this;
            let cardNumber = '';
            let validateLength = null;
            const regex = /^[0-9\s]*$/;
            const card = $(".card-number").val().trim().includes("*") ? self.creditCardNumber().trim() : $(".card-number").val().trim();
            const creditCardReviewBtn = $(".credit-card-review-button");
            const customBillingInputVisible = $('.custom-billing-input:visible');
            isCCFormValid = true;
            validateLength = 14;
            cardNumber = $(".card-number").val().replaceAll(' ', '');

            if ($(".billing-address-form-container").css('display') == 'none') {
                if ($(".name-card").val().length > 0 &&
                    (($(".card-number").val().length == 5 && $(".card-number").val().includes("*")) || cardNumber.length >= validateLength) &&
                    regex.test(card) &&
                    $(".cvv-number").val().length >= 3 &&
                    self.isExpDateValid()) {
                        creditCardReviewBtn.prop("disabled", false);
                        creditCardReviewBtn.removeClass("place-pickup-order-disabled");
                        isCCFormValid = true;
                        if (isSelfRegCustomer || isSdeStore) {
                            customBillingInputVisible.each(function() {
                                $(this).hasClass('error-highlight') ? creditCardReviewBtn.prop("disabled", true) : null
                            });
                        }
                    } else {
                    if (isFclCustomer) {
                        if (!fclCreditCardList.getCreditCardInfo("is_card")) {
                            creditCardReviewBtn.attr('disabled', 'disabled');
                            creditCardReviewBtn.addClass("place-pickup-order-disabled");
                            isCCFormValid = false;
                        } else {
                            creditCardReviewBtn.prop("disabled", false);
                            creditCardReviewBtn.removeClass("place-pickup-order-disabled");
                            isCCFormValid = true;
                        }
                    } else {
                        creditCardReviewBtn.attr('disabled', 'disabled');
                        creditCardReviewBtn.addClass("place-pickup-order-disabled");
                        isCCFormValid = false;
                    }
                }
            } else if (($(".account-number-input-container").css('display') != 'none') && $(".account-number").val().length == 0) {
                if (isFclCustomer) {
                    if (!fclCreditCardList.getCreditCardInfo("is_card")) {
                        creditCardReviewBtn.attr('disabled', 'disabled');
                        creditCardReviewBtn.addClass("place-pickup-order-disabled");
                        isCCFormValid = false;
                    } else {
                        creditCardReviewBtn.prop("disabled", false);
                        creditCardReviewBtn.removeClass("place-pickup-order-disabled");
                        isCCFormValid = true;
                    }
                } else {
                    creditCardReviewBtn.attr('disabled', 'disabled');
                    creditCardReviewBtn.addClass("place-pickup-order-disabled");
                    isCCFormValid = false;
                }
            } else {

                // D-201896 : Zip code is allowing more than 9 characters
                let ccState = $(".add-state").val() != undefined ? $(".add-state").val() : '';
                if ($(".name-card").val().length > 0 &&
                    (($(".card-number").val().length == 5 && $(".card-number").val().includes("*")) || cardNumber.length >= validateLength) &&
                    regex.test(card) &&
                    $(".cvv-number").val().length >= 3 &&
                    $(".address-one").val().length > 0 &&
                    $(".add-city").val().length > 0 &&
                    // D-201896 : Zip code is allowing more than 9 characters
                    ccState.length > 0 &&
                    $(".expiration-year").val().length > 0 &&
                    $(".add-zip").val().length >= 5 &&
                    // D-201896 : Zip code is allowing more than 9 characters
                    self.validateZip() &&
                    self.isExpDateValid()) {
                        creditCardReviewBtn.prop("disabled", false);
                        creditCardReviewBtn.removeClass("place-pickup-order-disabled");
                        isCCFormValid = true;
                    if (isSelfRegCustomer || isSdeStore) {
                        customBillingInputVisible.each(function() {
                            $(this).hasClass('error-highlight') ? creditCardReviewBtn.prop("disabled", true) : null
                        });
                    }
                } else {
                    isCCFormValid = false;
                    // B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
                    if (isFclCustomer) {
                        if (!self.isCompanyCreditCardAvailable()) {
                            if (!fclCreditCardList.getCreditCardInfo("is_card")) {
                                creditCardReviewBtn.attr('disabled', 'disabled');
                                creditCardReviewBtn.addClass("place-pickup-order-disabled");
                                isCCFormValid = false;
                            } else {
                                creditCardReviewBtn.prop("disabled", false);
                                creditCardReviewBtn.removeClass("place-pickup-order-disabled");
                                isCCFormValid = true;
                            }
                        }
                    } else {
                        if (!self.isCompanyCreditCardAvailable()) {
                            creditCardReviewBtn.attr('disabled', 'disabled');
                            creditCardReviewBtn.addClass("place-pickup-order-disabled");
                            isCCFormValid = false;
                        }
                    }
                }
            }

            if ($(".account-number-container").is(":visible") === true) {
                $('.account-number').val().trim();
                if ($(".fedex-acc-remove-button").is(":visible") === false) {
                    creditCardReviewBtn.attr('disabled', 'disabled');
                    creditCardReviewBtn.addClass("place-pickup-order-disabled");
                    isCCFormValid = false;
                }
            }

            var cardNumberLength = $(".card-number").val().length;
            if (cardNumberLength > 0) {
                self.validateCardNumber(true);
            }


            if(!techTitansB203420Toggle){
                checkToDisableReviewBtn('creditcard');
            }

        },

        validateZip: function () {
            let zip = $('.add-zip').val().trim();
            // D-201896 : Zip code is allowing more than 9 characters
            const zipRegex = /^\d{5}(-\d{4})?$/;
            if (zip.length === 0) {
                $('.add-zip').addClass('contact-error');
                $('.add-zip-error').show();
                $('.add-zip-error').html("Zip Code is required.");
                return false;
            } else if (!zipRegex.test(zip)) {
                $('.add-zip').addClass('contact-error');
                $('.add-zip-error').show();
                $('.add-zip-error').html("Zip Code is invalid.");
                return false;
            } else {
                $('.add-zip').removeClass('contact-error');
                $('.add-zip-error').hide();
                return true;
            }
        },

        validateState: function () {
            let state = $('.add-state').val();
            if (state.length === 0) {
                $('.add-state').addClass('contact-error');
                $('.add-state-error').show();
                $('.add-state-error').html("State is required.");
                return false;
            } else {
                $('.add-state').removeClass('contact-error');
                $('.add-state-error').hide();
                return true;
            }
        },

        validateCity: function () {
            let city = checkoutAdditionalScript.allowCityCharacters($('.add-city').val());
            if (city.length === 0) {
                $('.add-city').addClass('contact-error');
                $('.add-city-error').show();
                $('.add-city-error').html("City is required.");
                return false;
            } else {
                $('.add-city').removeClass('contact-error');
                $('.add-city-error').hide();
                return true;
            }
        },

        validateAddNumber: function () {
            let address = $('.address-one').val();
            if (address.length === 0) {
                $('.address-one').addClass('contact-error');
                $('.address-one-error').show();
                $('.address-one-error').html("Address is required.");
                return false;
            } else {
                if(isD193257ToggleEnable) {
                    let nameRegex = /[/\\]+/;
                    if (nameRegex.test(address)) {
                        $('.address-one').addClass('contact-error');
                        $('.address-one-error').show();
                        $('.address-one-error').html("Special characters are not allowed.");
                        return false;
                    }
                }
                $('.address-one').removeClass('contact-error');
                $('.address-one-error').hide();
                return true;
            }
        },

        validateAddTwoNumber: function () {
            let address = $('.address-two').val();
            let nameRegex = /[/\\]+/;
            if(isD193257ToggleEnable && nameRegex.test(address)) {
                  $('.address-two').addClass('contact-error');
                  $('.address-two-error').show();
                  $('.address-two-error').html("Special characters are not allowed.");
                  return false;
            } else {
                $('.address-two').removeClass('contact-error');
                $('.address-two-error').hide();
                return true;
            }
        },

        validateCvvNumber: function () {
            let cvv = $('.cvv-number').val();
            if (cvv.length === 0) {
                $('.cvv-number').addClass('contact-error');
                $('.cvv-card-error').show();
                $('.cvv-card-error').html("Please enter CVV");
                return false;
            } else {
                $('.cvv-number').removeClass('contact-error');
                $('.cvv-card-error').hide();
                return true;
            }
        },

        validateCardNumber: function (viaBillingForm = false) {
            var self = this;
            let cardNumber = '';
            let validateLength = '';
            validateLength = 14;
            cardNumber = $(".card-number").val().replaceAll(' ', '');
            const regex = /^[0-9\s]*$/;
            let tempNumber = $('.card-number').val().trim().replace("*", "");
            if (cardNumber.length === 0 || !regex.test(tempNumber)) {
                $('.card-number').addClass('contact-error');
                $('.card-number-error').show();
                $('.card-number-error').html(!regex.test(tempNumber) ? "Please enter a valid card number" : "Please enter a card number");
                return false;
            } else if (cardNumber.length < validateLength) {
                if ((cardNumber.length != 5 && cardNumber.includes("*") != true)) {
                    $('.card-number').addClass('contact-error');
                    $('.card-number-error').show();
                    $('.card-number-error').html("The credit card number is not valid.");
                    return false;
                }
            } else {
                $('.card-number').removeClass('contact-error');
                $('.card-number-error').hide();
                return true;
            }
            if (viaBillingForm && self.creditCardNumber().length < validateLength) {
                $(".credit-card-review-button").attr('disabled', 'disabled');
                $(".credit-card-review-button").addClass("place-pickup-order-disabled");
            }
        },

        validateCardName: function () {
            let nameOnCard = $('.name-card').val();
            const errorMessage = cardValidator.cardNameValidator(nameOnCard);
                if (errorMessage) {
                    $('.name-card').addClass('contact-error');
                    $('.name-card-error').show();
                    $('.name-card-error').html(`<span class="fedex-icon-error"></span> <span class="fedex-icon-error-text"> ${errorMessage} </span>`);;
                } else {
                    $('.name-card').removeClass('contact-error');
                    $('.name-card-error').hide();
                }
                return true;
        },

        getYears: function () {
            var years = [];
            var current = new Date().getFullYear();
            years.push(new Year(current.toString(), current));
            for (var i = 0; i < 10; i++) {
                current = current + 1;
                years.push(new Year(current.toString(), current));
            }
            return years;
        },

        getStates: function () {
                return window.checkoutConfig.payment.pay.usstates['pay'];
        },

        /**
         * Fetch public key from encryption service response
         *
         * @param publicKey
         */
        extractPublicKey: function (publicKey) {
            // check for framing ("----BEGIN x-----"..."-----END x-----")
            // ie 11 does not allow some spesial charecter in session storage,
            // thus we should use encodeURI and decodeURI method on the result of above encryption;

            const pem_re = /-----BEGIN (?:[\w\s]+)-----\s*([0-9A-Za-z+/=\s]+)-----END/;
            const pem_result = pem_re.exec(publicKey);
            if (pem_result != null) {
                publicKey = pem_result[1];
            }
            const tempPublicKey = publicKey.replace(/\n/g, '');

            return window.atob(tempPublicKey);
        },

        /**
         * Fetch modulus and exponent
         */
        fetchModulusExponent: function (pubkey) {
            const pubkey_tree = ans.decode(pubkey);
            const n_raw = pubkey_tree.sub[1].sub[0].sub[0].rawContent();
            const e_raw = pubkey_tree.sub[1].sub[0].sub[1].rawContent();
            const n = n_raw;

            let e = 0;
            for (let i = 0; i < e_raw.length; i++) {
                e = (e << 8) | e_raw.charCodeAt(i);
            }
            return [n, e];
        },

        /**
         * Parse public key returned by EncryptionService
         *
         * @param publickey
         */
        parsePublicKey: function (publickey) {
            return this.fetchModulusExponent(publickey);
        },

        /**
         * Get encryptedCreditCard string
         */
        fetchEncryptedCreditCard: function (textBlock, publicKey) {
            publicKey = this.extractPublicKey(publicKey);
            const pki = this.parsePublicKey(publicKey);
            const chdkey_modulus = pki[0];
            const chdkey_exponent = pki[1];
            var rsaObj = new rsa(chdkey_modulus, chdkey_exponent);
            var encryptedCreditCard = rsaObj.encrypt(textBlock);

            return encryptedCreditCard;
        },

        /**
         * The navigate() method is responsible for navigation between checkout steps
         * during checkout. You can add custom logic, for example some conditions
         * for switching to your custom step
         * When the user navigates to the custom step via url anchor or back button we_must show step manually here
         */
        navigate: function (step) {
            step && step.isVisible(true);
        },

        changeToPickup: function (toastMsgId = '') {

            stepNavigator.navigateTo('shipping', 'opc-shipping_method');

            this.chosenDeliveryMethod('pick-up');

            window.dispatchEvent(new Event('on_change_delivery_method'));
        },

        changeToDelivery: function (toastMsgId = '') {
            if(window.e383157Toggle){
                fxoStorage.delete('pickupData');
            }else{
                localStorage.removeItem('pickupData');
            }
            if (this.marketplaceInStorePickupShippingCombo) {
                if(window.e383157Toggle){
                    fxoStorage.set('pickupShippingComboKey', String(false));
                }else{
                    localStorage.setItem('pickupShippingComboKey', false);
                }
            }

            stepNavigator.navigateTo('shipping', 'opc-shipping_method');

            this.chosenDeliveryMethod('shipping');

            window.dispatchEvent(new Event('on_change_delivery_method'));
        },

        /**
         * @returns void
         */
        navigateToNextStep: function () {
            stepNavigator.next();
        },

        /**
         * Method which gets triggered after template is loaded
         * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
         *
         * @return void
         */
        afterTemplateRender: function () {
            let preselectPaymentMethod = typeof window.checkoutConfig.preselect_payment_method !== 'undefined' ? window.checkoutConfig.preselect_payment_method : '';
            let defaultPaymentMethod = typeof window.checkoutConfig.default_payment_method !== 'undefined' && window.checkoutConfig.default_payment_method !== '' ? window.checkoutConfig.default_payment_method : preselectPaymentMethod;
            let defaultPaymentMethodAllowed = this.isPaymentAllowed(defaultPaymentMethod);
            let fedexAccountNumberPaymentMethod = window.checkoutConfig.fedex_account_payment_method_identifier;
            let paymentInfo = typeof window.checkoutConfig.default_payment_method_value !== 'undefined' ? window.checkoutConfig.default_payment_method_value : '';

            if (paymentInfo === window.checkoutConfig?.company_discount_account_number) {
                paymentInfo = '';
            }
            if (isSdeStore || isSelfRegCustomer) {
                if (defaultPaymentMethod != '' && defaultPaymentMethod == fedexAccountNumberPaymentMethod && paymentInfo != '') {
                    this.isCreditCardSelected(false);
                    this.isPaymentOptionSelected(true);
                    paymentInfo = paymentInfo[fedexAccountNumberPaymentMethod] !== undefined ? paymentInfo[fedexAccountNumberPaymentMethod] : paymentInfo;
                    this.prefilFedExAccount(paymentInfo);
                    this.isCompanyFedexAccountAvailable(true);
                    this.companyFedexAccount(paymentInfo);
                    $('.select-fedex-acc').trigger('click');
                } else if (defaultPaymentMethod != '' && defaultPaymentMethod == creditCardPaymentMethod && paymentInfo != '' && availableCompanyCreditCardDetails) {
                    this.isCreditCardSelected(true);
                    this.isPaymentOptionSelected(true);
                    paymentInfo = paymentInfo[creditCardPaymentMethod] !== undefined ? paymentInfo[creditCardPaymentMethod] : paymentInfo;
                    this.showCompanyCreditCardInfo(paymentInfo);
                    this.companyCreditCardData(paymentInfo);
                    this.isCompanyCreditCardAvailable(true);
                    this.companyCreditCard(true);
                    $('.select-credit-card').trigger('click');
                } else if (defaultPaymentMethod != '' && defaultPaymentMethod == fedexAccountNumberPaymentMethod) {
                    if (defaultPaymentMethodAllowed) {
                        this.isCreditCardSelected(false);
                        this.isPaymentOptionSelected(true);
                        $('.select-fedex-acc').trigger('click');
                    } else {
                        this.isCreditCardSelected(true);
                        this.isPaymentOptionSelected(true);
                        $('.select-credit-card').trigger('click');
                        this.selectDefinitiveCc();
                    }
                } else if (defaultPaymentMethod != '' && defaultPaymentMethod == creditCardPaymentMethod) {
                    if (defaultPaymentMethodAllowed) {
                        this.isCreditCardSelected(true);
                        this.isPaymentOptionSelected(true);
                        $('.select-credit-card').trigger('click');
                        this.selectDefinitiveCc();
                    } else {
                        this.isCreditCardSelected(false);
                        this.isPaymentOptionSelected(true);
                        $('.select-fedex-acc').trigger('click');
                    }
                } else if (defaultPaymentMethod == '' && paymentInfo != '') {
                    if (typeof paymentInfo[fedexAccountNumberPaymentMethod] !== 'undefined') {
                        this.isCompanyFedexAccountAvailable(true);
                        this.companyFedexAccount(paymentInfo[fedexAccountNumberPaymentMethod]);
                    }
                    if (typeof paymentInfo[creditCardPaymentMethod] !== 'undefined') {
                        this.isCompanyCreditCardAvailable(true);
                        this.companyCreditCardData(paymentInfo[creditCardPaymentMethod]);
                        this.companyCreditCard(true);
                    }
                }
            } else if (isFclCustomer && defaultPaymentMethod != '') {
                if (defaultPaymentMethod == fedexAccountNumberPaymentMethod) {
                    this.isCreditCardSelected(false);
                    this.isPaymentOptionSelected(true);
                    $('.select-fedex-acc').trigger('click');
                } else if (defaultPaymentMethod == creditCardPaymentMethod) {
                    this.isCreditCardSelected(true);
                    this.isPaymentOptionSelected(true);
                    $('.select-credit-card').trigger('click');
                }
            }
            let companyPaymentsAllowed =
                typeof window.checkoutConfig.company_payment_methods_allowed !== 'undefined' && typeof window.checkoutConfig.company_payment_methods_allowed !== false
                    ? Object.keys(window.checkoutConfig.company_payment_methods_allowed)
                    : [];
            if (companyPaymentsAllowed.length !== 0) {
                if (!companyPaymentsAllowed.includes('fedexaccountnumber') && !companyPaymentsAllowed.includes('custom_fedex_account')) {
                    this.showAccountPayment(false);
                    //Select CC because Account is not allowed
                    this.isCreditCardSelected(true);
                    this.isPaymentOptionSelected(true);
                    $('.select-credit-card').trigger('click');
                    this.selectDefinitiveCc();
                }

                if (!companyPaymentsAllowed.includes('creditcard')) {
                    this.showCcPayment(false);
                    //Select Account because CC is not allowed
                    this.isCreditCardSelected(false);
                    this.isPaymentOptionSelected(true);
                    $('.select-fedex-acc').trigger('click');
                }
            }
        },

        /**
         * Method to prefill FedEx account field
         * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
         *
         * @param {*} fedexAccountNumber
         */
        prefilFedExAccount: function (fedexAccountNumber) {
            $('input.fedex-account-number').val(fedexAccountNumber);
            $('input.fedex-account-number').trigger('blur');
            //B-1294484 Make fedex account number field disabled for company fedex account
            if (siteConfiguredFedExAccount == fedexAccountNumber && !isFclCustomer) {
                $('input.fedex-account-number').prop('disabled', true);
            }
        },

        /**
         * Method to display site configured credit card info
         * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
         *
         * @param {*} paymentInfo
         */
        showCompanyCreditCardInfo: function (paymentInfo) {
            let ccType = paymentInfo['ccType'] ? paymentInfo['ccType'] : '';
            let imageClass = this.getImageClassForCard(ccType);
            let ccNumber = paymentInfo['ccNumber'];
            ccNumber = ccNumber ? ccNumber.replace(/^./g, '*') : '';
            let ccExpiryMonth = paymentInfo['ccExpiryMonth'];
            let ccExpiryYear = paymentInfo['ccExpiryYear'];
            ccExpiryYear = ccExpiryYear ? ccExpiryYear.substring(2, 4) : '';

            if (isSelfRegFclCustomer || (isSdeStore  && isFclCustomer)) {
                let ccToken = paymentInfo['token'];
                let siteName = window.checkoutConfig.company_name;
                let imageUrl = window.checkoutConfig.media_url+'/'+ccType.toLowerCase()+'.png';
                let creditCardHtml = '<li class="fedex-card-title"><span>Saved Card ('+siteName+')</span></li>';
                creditCardHtml += '<li class="card-list company-card" data-token="'+ccToken+'" data-number="'+ccNumber+'" data-type="'+ccType+'" data-tokenexpired="" tabindex="0"><img class="card-icon" src="'+imageUrl+'" alt="'+ccType+'"/><span class="card-mid-content">'+ccType+'</span><span class="card-last-content"> ending in '+ccNumber+'</span></li>';
                if ($('li.card-list.company-card').length === 0) {
                    $('ul.credit-card-dropdown-content').prepend(creditCardHtml);
                    $('.card-list.company-card').click();
                } else if ($('li.card-list.company-card').length === 1) {
                    $('.card-list.company-card').click();
                }
            } else {
                var html = '<div class="credit-cart-content"><div class="credit-card-head">';
                html += '<div class="head-left"><div class="left"><div id="cc-image" class="' + imageClass + '"></div></div>';
                html += '<div class="right"><div class="card-type"><span>' + ccType + '</span></div>';
                html += '<div class="card-number"><span>ending in ' + ccNumber + '</span></div></div></div>';
                html += '<div class="head-mid"><div class="card-expires"><span>Expires ' + ccExpiryMonth + '/' + ccExpiryYear + '</span >';
                html += '</div></div><div class="head-right"><div class="cart-status-make-content"><div class="cart-status-make">';
                html += '</div></div></div></div>';

                /** These classes assignemnt should be removed once toggle explorers_d_164473_fix would cleaned **/
                var parentClass = 'cms-sde-home';
                if(isSelfRegCustomer){
                    parentClass = 'wlgn-enable-page-header';
                }
                parentClass = 'commercial-store-home';
                $('.'+ parentClass + ' .cc-form-container .credit-cart-content').remove();
                $('.'+ parentClass +' .cc-form-container').prepend(html);
            }
            this.showCardNameField(false);
            this.showCardNumberField(false);
            this.showExpCvvFields(false);
            $('.billing-address-checkbox-container').hide();
            $(".credit-card-review-button").prop("disabled", false);
            $(".credit-card-review-button").removeClass("place-pickup-order-disabled");
            isCCFormValid = true;
        },

        /**
         * Method to display only company level credit card info which is not editable payment information
         * B-1730450 : RT-ECVS- Once Company admin set as non editable payment method can not be change on storefront
         *
         * @param {*} availableCompanyCreditCardDetails
         */
        showOnlyNonEditableCompanyCreditCardInfo: function (availableCompanyCreditCardDetails) {
            $(".credit-card-dropdown").css("pointer-events", "none");
            if ($("#address-one").val() == '') {
                $("#address-one").val(availableCompanyCreditCardDetails.addressLine1);
            }
            $("#address-one").prop("readonly", true);
            if ($("#address-two").val() == '') {
                $("#address-two").val(availableCompanyCreditCardDetails.addressLine2);
            }
            $("#address-two").prop("readonly", true);
            if ($("#add-city").val() == '') {
                $("#add-city").val(checkoutAdditionalScript.allowCityCharacters(availableCompanyCreditCardDetails.city));
            }
            $("#add-city").prop("readonly", true);
            if ($("#add-state").val() == '') {
                $("#add-state").val(availableCompanyCreditCardDetails.state);
                $('#add-state').trigger('change');
            }
            $("#add-state").attr("style", "pointer-events: none;");
            if ($("#add-zip").val() == '') {
                $("#add-zip").val(availableCompanyCreditCardDetails.zipCode);
            }
            $("#add-zip").prop("readonly", true);
        },

        /**
         * Get image class based on the credit card type
         * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
         *
         * @param {*} cardType
         */
        getImageClassForCard: function (cardType) {
            if (cardType == "VISA") {
                this.greenCheckUrl(window.checkoutConfig.media_url + "/Visa.png");
                return 'visa';
            } else if (cardType == "MASTERCARD") {
                this.greenCheckUrl(window.checkoutConfig.media_url + "/MasterCard.png");
                return 'mastercard';
            } else if (cardType == "AMEX") {
                this.greenCheckUrl(window.checkoutConfig.media_url + "/Amex.png");
                return 'amex';
            } else if (cardType == "DISCOVER") {
                this.greenCheckUrl(window.checkoutConfig.media_url + "/Discover.png");
                return 'discover';
            } else if (cardType == "DINERS") {
                this.greenCheckUrl(window.checkoutConfig.media_url + "/Diners-Club.png");
                return 'diners-club';
            } else {
                this.greenCheckUrl(window.checkoutConfig.media_url + "/Generic.png");
                return 'generic';
            }
        },

        /**
         * Method to display credit card form
         * B-1216115 : When the CC is configured in Admin , Payment screen prepopulate the CC details
         */
        showCreditCardForm: function () {
            /** These classes assignemnt should be removed once toggle explorers_d_164473_fix would cleaned **/
            var parentClass = 'cms-sde-home';
            if(isSelfRegCustomer){
                parentClass = 'wlgn-enable-page-header';
            }
                parentClass = 'commercial-store-home';
                let isShip;
                let isPick;
                if(window.e383157Toggle){
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                }else{
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }
                if (isPick == 'true' && isShip == 'false') {
                    setTimeout(() => {
                        if ($(".billing-address-checkbox-container").is(":visible")) {
                            $(".billing-address-checkbox-container").hide();
                        }
                    }, 100);
                }
            $('.'+ parentClass +' .cc-form-container .credit-cart-content').remove();
            this.showCardNameField(true);
            this.showCardNumberField(true);
            this.showExpCvvFields(true);
            if ($('.card-number').val().length == 0) {
                this.greenCheckUrl(window.checkoutConfig.media_url + "/Generic.png");
            }
            $('.billing-address-checkbox-container').show();
            $(".credit-card-review-button").attr('disabled', 'disabled');
            $(".credit-card-review-button").addClass("place-pickup-order-disabled");
        },

        /**
         * Display billing address
         *
         * D-101635:RT-ECVS- SDE-Billing address is not prepopulated correctly in payment screen
         */
        displayBillingAddress: function () {
            if (this.isCompanyCreditCardAvailable()) {
                let addressInfo = this.companyCreditCardData();
                let streetLineOne = '';
                let streetLineTwo = '';
                let city = '';
                let state = '';
                let streetLines = '';
                let addressInfoCity = '';

                if (typeof addressInfo['addressLine1'] !== 'undefined') {
                    streetLineOne = addressInfo['addressLine1'];
                }

                if (typeof addressInfo['addressLine2'] !== 'undefined') {
                    streetLineTwo = addressInfo['addressLine2'];
                }

                addressInfoCity = checkoutAdditionalScript.allowCityCharacters(addressInfo['city']);
                if (typeof addressInfoCity !== 'undefined') {
                    city = addressInfoCity + ",";
                }

                if (typeof addressInfo['state'] !== 'undefined') {
                    state = addressInfo['state'];
                }

                if (streetLineTwo) {
                    streetLines = streetLineOne + ', ' + streetLineTwo;
                } else {
                    streetLines = streetLineOne;
                }

                $(".site-credit-card .shipping-address-line-one").text(streetLines);
                $(".site-credit-card .shipping-city").text(checkoutAdditionalScript.allowCityCharacters(city));
                $(".site-credit-card .shipping-postal").text(state);
                $(".site-credit-card .shipping-lastname").text('');

                return true;
            }

            return false;
        },

        /**
         * Display billing address same as shipping address
         *
         * @return void
         */
        displayBillingSameShipping: function (shippingAddressFromData) {
            $(".shipping-address:not(.site-credit-card) .shipping-address-line-one").text(shippingAddressFromData.street[0]);
            $(".shipping-address:not(.site-credit-card) .shipping-address-line-two").text(shippingAddressFromData.street[1]);
            $(".shipping-address:not(.site-credit-card) .shipping-city").text(checkoutAdditionalScript.allowCityCharacters(shippingAddressFromData.city) + ",");
            let state;
            if(window.e383157Toggle){
                state = fxoStorage.get('stateOrProvinceCode');
            }else{
                state = localStorage.getItem('stateOrProvinceCode');
            }
            $(".shipping-address:not(.site-credit-card) .shipping-postal").text(state);
            if (!formFieldCleansingForCCNameToggle) {
                $(".shipping-address:not(.site-credit-card) .shipping-firstname").text(shippingAddressFromData.firstname);
                $(".shipping-address:not(.site-credit-card) .shipping-lastname").text(shippingAddressFromData.lastname);
            }
        },

        /**
         * Display billing address same as shipping address
         *
         * @return void
         */
        fillBilling: function (billingAddressFromData) {
            // $("#company-name").val(billingAddressFromData.company);
            $("#address-one").val(billingAddressFromData.street[0]);
            $("#address-two").val(billingAddressFromData.street[1]);
            $("#add-city").val(checkoutAdditionalScript.allowCityCharacters(billingAddressFromData.city));
            $("#add-state").val(billingAddressFromData.stateOrProvinceCode).trigger('change');
            $("#add-zip").val(billingAddressFromData.postalCode);
        },

        /*
         * ###############################################################
         *                   Start | Marketplace Section
         * ###############################################################
         */

        /**
         * @param void
         * @returns Bool
         */
        isMixedQuote: function() {
            return marketplaceQuoteHelper.isMixedQuote();
        },

        /**
         * @param void
         * @return String
         */
        getChangeToPickupLabel: function () {
            return $t('CHANGE TO IN-STORE PICKUP');
        },

        /**
         * @param (event, null)
         * @return String
         */
        getDeliveryMethodToast: function (toastMsgId = '') {
            let message = marketplaceDeliveryToast.getDeliveryMethodMessageObj();

            if(this.isVisible() && message[toastMsgId] && message[toastMsgId] !== '') {
                message.text = message[toastMsgId];
                marketplaceToastMessages.addMessage(JSON.stringify(message));
            }
        },

        /**
         * @returns Bool
         */
        isFullMarketplaceCart: function () {
            return marketplaceQuoteHelper.isFullMarketplaceQuote();
        },

        /**
         * @returns Bool
         */
        isFullFirstPartyQuote: function () {
            return marketplaceQuoteHelper.isFullFirstPartyQuote();
        },

        /*
         * ###############################################################
         *                   End | Marketplace Section
         * ###############################################################
         */


        /**
        * B-1685646 - Commercial Custom Billing Fields
        */

        removeAdditionalBillingInfo: function() {
            if(mazeGeeksD187301Toggle){
            $('.additional-billing-info').show();
            }
            else{
            $(".custom-billing-fields").length <= 0 ? $('.additional-billing-info').hide() : $('.additional-billing-info').show();
            }
        },

        /**
         * Return true if non pricable products are added in the cart
         *
         * @return Bool
         */
        isCheckoutQuotePriceDashable: function () {
            return typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
        },

        createBillingFields: function (billingArray, fieldType) {
            const parent = fieldType === 'creditcard' ? '.credit-card-form' : '.fedex-account-form';
            const container = $(parent+' .custom-billing-fields-container');

            // Check if the current fieldtype tab is active
            const isTabActive = function() {
                return fieldType === 'creditcard' ? $('.select-credit-card').hasClass('selected-paymentype') : $('.select-fedex-acc').hasClass('selected-paymentype');
            }

            // Only render the custom billing fields once.
            if(!container.is(':empty')) {
                checkToDisableReviewBtn(fieldType);
                return;
            }

            var isProfilePreference = typeof (window.checkoutConfig.retail_profile_session) !== 'undefined' && window.checkoutConfig.retail_profile_session != null && typeof (window.checkoutConfig.retail_profile_session.output) !== 'undefined' && window.checkoutConfig.retail_profile_session.output != null && typeof (window.checkoutConfig.retail_profile_session.output.profile) !== 'undefined' && window.checkoutConfig.retail_profile_session.output.profile != null && typeof (window.checkoutConfig.retail_profile_session.output.profile.preferences) !== 'undefined' && window.checkoutConfig.retail_profile_session.output.profile != null;

            var billingFieldCounter = 0;
            var hiddenBillingField = [];
            var adminBillingFieldCounter = 0;

            var fieldLength = 20;
            if (techTitansBillingFieldsLengthFix) {
                fieldLength = 30;
            }

            billingArray.forEach(function (field) {

                var preferenceValue = null;
                adminBillingFieldCounter++;
                if (isProfilePreference && fieldType == "invoiced") {
                    var profilePreferenceData = window.checkoutConfig.retail_profile_session.output.profile.preferences;

                    var validNames = {
                        "1": ["billing_1", "YOUR_REFERENCE"],
                        "2": ["billing_2", "DEPARTMENT_NUMBER"],
                        "3": ["billing_3", "PURCHASE_ORDER_NUMBER"],
                        "4": ["billing_4", "SHIPPER_ID_1"],
                        "5": ["billing_5", "SHIPPER_ID_2"],
                        "6": ["billing_6", "BILL_OF_LADING_NUMBER"],
                    };

                    profilePreferenceData.forEach(function (profilePreferenceField) {
                        if (!profilePreferenceField || !profilePreferenceField.name) return;

                        if (validNames[adminBillingFieldCounter] && validNames[adminBillingFieldCounter].includes(profilePreferenceField.name)) {
                            var preferenceValues = profilePreferenceField.values;

                            if (preferenceValues && preferenceValues[0] && preferenceValues[0].name && profilePreferenceField.name.toUpperCase() !== 'INVOICE_NUMBER' && preferenceValues[0].value !== undefined) {
                                field.default = preferenceValues[0].value;
                                preferenceValue = preferenceValues[0].value;
                            }
                        }
                    });
                }

                let isFieldVisible = field.visible === '1';
                let isFieldDisabled = field.editable === '0';
                let isFieldRequired = field.required === '1';
                let requiredMessage = 'This is a required field';
                let labelClass = isFieldRequired ? 'required-field' : '';

                if (isFieldVisible) {
                    let template = `
                        <div class="custom-billing-fields"
                            data-testid="commercial-custom-billing-fields">
                                <label class="custom-billing-label ${labelClass}">${field.field_label}</label>
                                <input class="custom-billing-input"
                                    aria-label="Custom Billing Field"
                                    type="text"
                                    maxlength="${fieldLength}"
                                    name="${field.field_label}"
                                    value = "${field.default}"
                                    ${isFieldDisabled ? 'disabled' : ''}
                                    ${field.custom_mask ? `pattern="${field.custom_mask}"` : ''}
                                    ${field.error_message ? `data-error-message="${field.error_message}"` : ''}
                                    ${isFieldRequired ? `data-required-message="${requiredMessage}"` : ''}/>
                                <span class="error-message"></span>
                            </div>
                        `;
                        container.append(template);
                    } else {
                        if (isD217133ToggleEnable && field.visible === "0" && field.default !== "") {
                            hiddenBillingField.push({
                                fieldName: field.field_label,
                                value: field.default
                            });
                        } else {
                            if (isProfilePreference && fieldType == "invoiced" && preferenceValue) {
                                hiddenBillingField.push({
                                    fieldName: field.field_label,
                                    value: field.default
                                });
                            }
                        }
                    }
                });

                const customBillingInput = $('.custom-billing-input');
                const reviewButton = $('.fedex-account-number-review-button, .credit-card-review-button');

                $('.custom-billing-input').on('input', function(e) {
                    if(techTitansB203420Toggle){
                        fieldValidation($(this), e);
                        checkToDisableReviewBtn();
                    }
                });

                if(customBillingInput.length > 0){
                    customBillingInput.each(function() {
                        fieldValidation($(this));
                    });
                    checkToDisableReviewBtn(fieldType);
                }

                $(window).on( 'hashchange', function(e) {
                    customBillingInput.each(function() {
                        fieldValidation($(this));
                    });
                    checkToDisableReviewBtn(fieldType);
                });

                $(document).on('click', '.select-credit-card, .select-fedex-acc', function () {
                    if(!isTabActive()) {
                        return;
                    }
                    $(parent+' .custom-billing-input').each(function() {
                        fieldValidation($(this));
                    });
                    checkToDisableReviewBtn(fieldType);
                });

                customBillingInput.on('blur', function (e) {
                    if(!isTabActive()) {
                        return;
                    }
                    fieldValidation($(this), e);
                    checkToDisableReviewBtn(fieldType);
                });

                reviewButton.on('click', function () {
                    let inputData = [];
                    let paymentField = this;

                    // D-238830 Invisible Billing Reference Fields Not Displaying On Invoice Correctly
                    if(isD238830ToggleEnable) {
                        let visibleFieldMap = {};

                        $('.custom-billing-input').each(function (index) {
                            let fieldName = $(this).attr('name');
                            let value = $(this).val();
                            const isFedexAccountReviewButton = $(paymentField).hasClass('fedex-account-number-review-button');
                            const isFirstField = index === 0;

                            visibleFieldMap[fieldName] = {
                                value: value,
                                ...(isD195387ToggleEnable && isFedexAccountReviewButton && isFirstField ? { first_field: true } : {})
                            };
                        });

                        billingArray.forEach(function (field) {
                            if (visibleFieldMap[field.field_label]) {
                                inputData.push({
                                    fieldName: field.field_label,
                                    ...visibleFieldMap[field.field_label]
                                });
                            } else {
                                let hiddenField = hiddenBillingField.find(h => h.fieldName === field.field_label);
                                if (hiddenField) {
                                    inputData.push({
                                        fieldName: hiddenField.fieldName,
                                        value: hiddenField.value
                                    });
                                }
                            }
                        });
                    } else {
                        $('.custom-billing-input:visible').each(function (index) {
                            let fieldName = $(this).attr('name');
                            let value = $(this).val();
                            const isFedexAccountReviewButton = $(paymentField).hasClass('fedex-account-number-review-button');
                            const isFirstField = index === 0;

                            inputData.push({
                                fieldName: fieldName,
                                value: value,
                                ...(isD195387ToggleEnable && isFedexAccountReviewButton && isFirstField ? { first_field: true } : {})
                            });
                        });

                        if (hiddenBillingField.length > 0) {
                            hiddenBillingField.forEach(function (field) {
                                inputData.push({
                                    fieldName: field.fieldName,
                                    value: field.value
                                });
                            });
                        }
                    }

                    let inputDataJSON = JSON.stringify(inputData);
                    if(window.e383157Toggle){
                        fxoStorage.set('customBillingData', inputDataJSON);
                    }else{
                        localStorage.setItem('customBillingData', inputDataJSON);
                    }
            });
        },

        handleBillingCheckbox: function(isShip, isPick) {
            let shouldCheckBilling = false;

            if (isShip === 'true') {
                shouldCheckBilling = true;
            } else if (isPick === 'true') {
                shouldCheckBilling = false;
            }

            $('.billing-checkbox').prop('checked', shouldCheckBilling).change();
            this.showBillingCheckbox(shouldCheckBilling);
        },

        selectDefinitiveCc: function () {
            let isShip, isPick;

            isShip = fxoStorage.get("shipkey");
            isPick = fxoStorage.get("pickupkey");
            if ($('.card-list.company-card').length !== 0) {
                $('.card-list.company-card').click();
                this.handleBillingCheckbox(isShip, isPick);
            } else if ($('.card-list:not(.last,.company-card)').length !== 0) {
                var primaryCC = $('.card-list:not(.last,.company-card)[data-primary="true"]');
                if (primaryCC.length) {
                    primaryCC.click();
                } else {
                    $('.card-list:not(.last,.company-card)').first().click();
                }
                this.handleBillingCheckbox(isShip, isPick);
            } else if ($('.card-list:not(.last)').length == 0 && this.isCreditCardSelected()) {
                $('.card-list.last').click();
            }
        },

        isPaymentAllowed: function (paymentMethod) {
            let companyPaymentsAllowed =
                typeof window.checkoutConfig.company_payment_methods_allowed !== 'undefined' && typeof window.checkoutConfig.company_payment_methods_allowed !== false
                    ? Object.keys(window.checkoutConfig.company_payment_methods_allowed)
                    : [];

            return companyPaymentsAllowed.length !== 0 && companyPaymentsAllowed.includes(paymentMethod);
        },

        renderReCaptcha: function (elementId) {
            reCaptcha.renderReCaptcha(elementId);
        },

        /**
         * Hide breadcrumb if Fuse Bidding Flow
         */
        isFuseBidding: function() {
            let is_bid;
            if (window.e383157Toggle) {
                is_bid = fxoStorage.get('qouteLocationDetails') ? JSON.parse(fxoStorage.get('qouteLocationDetails')) : null;
            } else {
                is_bid = localStorage.getItem('qouteLocationDetails') ? JSON.parse(localStorage.getItem('qouteLocationDetails')) : null;
            }

            return isFuseBidding && is_bid;
        }

        });
    });
