define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Catalog/js/price-utils',
    'Magento_Customer/js/customer-data',
    'previewImg',
    'gdlEvent',
    'mage/url',
    'mage/storage',
    'fedex/storage',
    'Fedex_Delivery/js/model/toggles-and-settings',
], function (
    $,
    Component,
    ko,
    priceUtils,
    customerData,
    previewImg,
    gdlEvent,
    urlBuilder,
    storage,
    fxoStorage,
    togglesAndSettings
) {
    'use strict';

    /**
     * Email duplicacy toggle
     */
    let emailDuplicacyToggle = typeof(window.checkout) !== "undefined" && window.checkout !== null ? true : false;

    if(emailDuplicacyToggle) {
        emailDuplicacyToggle = typeof(window.checkout.xmen_email_duplicacy) !== "undefined" &&
        window.checkout.xmen_email_duplicacy !== null && window.checkout.xmen_email_duplicacy !== false ? window.checkout.xmen_email_duplicacy : false;
    }

    let isCheckoutObjectAvailable = typeof(window.checkout) !== "undefined" && window.checkout !== null ? true : false;
    let isSdeStoreEnable = false;
    let marketingOptInEnabled = false;
    let tiger_enable_essendant = false;
    if (isCheckoutObjectAvailable) {
        isSdeStoreEnable = typeof(window.checkout.is_sde_store) !== "undefined" &&
        window.checkout.is_sde_store !== null && window.checkout.is_sde_store !== false ? window.checkout.is_sde_store : false;

        marketingOptInEnabled = typeof(window.checkout.marketing_opt_in.enabled) !== "undefined" &&
        window.checkout.marketing_opt_in.enabled !== null && window.checkout.marketing_opt_in.enabled !== false ? window.checkout.marketing_opt_in.enabled : false;

        tiger_enable_essendant = typeof(window.checkout.tiger_enable_essendant) !== "undefined" &&
        window.checkout.tiger_enable_essendant !== null && window.checkout.tiger_enable_essendant !== false ? window.checkout.tiger_enable_essendant : false;
    }
    let isXmenOrderConfirmationFix =  typeof (window.checkout.xmen_order_confirmation_fix) !== "undefined" && window.checkout.xmen_order_confirmation_fix !== null ? window.checkout.xmen_order_confirmation_fix : false;
    let enableShippingFreeText = tiger_enable_essendant || window?.checkout?.is_cbb_toggle_enable || false;
    let isToggleD219954Enabled = window?.checkout?.tiger_d219954 || false;

    return Component.extend({
        defaults: {
            template: 'Fedex_SubmitOrderSidebar/order-success',
            activeMethod: ''
        },

        isDelivery: ko.observable(false),
        shippingAddress: ko.observable(null),
        billingAddress: ko.observable(null),
        stateOrProvinceCode: ko.observable(null),
        deliveryMethod: ko.observable(null),
        paymentStepData: ko.observable(null),
        isBillingAddress: ko.observable(false),
        continueShoppingUrl: ko.observable(window.CONTINUE_SHOPPING_CART_URL),
        infoUrl: ko.observable(window.e383157Toggle ? fxoStorage.get("infoUrl") :
            localStorage.getItem("infoUrl")),
        isFedexAccountApplied: ko.observable(false),
        isCreditCardSelected: ko.observable(false),
        isfedexShipAccountNumber: ko.observable(false),
        isfedexAccountNumber: ko.observable(false),
        fedexShipAccountNumber: ko.observable(null),
        fedexAccountNumber: ko.observable(null),
        emailId: ko.observable(null),
        userName: ko.observable(null),
        orderNo: ko.observable(null),
        transactionId: ko.observable(null),
        gtnNumber: ko.observable(null),
        errorIconSrcUrl: ko.observable(null),
        grossAmount: ko.observable(null),
        netAmount: ko.observable(null),
        shippingAmount: ko.observable(null),
        marketplaceShippingAmount: ko.observable(null),
        taxAmount: ko.observable(null),
        totalDiscountAmount: ko.observable(null),
        totalAmount: ko.observable(null),
        productDescription: ko.observable(null),
        qty: ko.observable(null),
        discount: ko.observable(null),
        total: ko.observable(null),
        productTotal: ko.observable(null),
        payBy: ko.observable(null),
        creditCardNumber: ko.observable(null),
        pickupAddressInformation: ko.observable(null),
        pickupContactInformation: ko.observable(null),
        pickupShipMethod: ko.observable(null),
        pickupDateTime: ko.observable(''),
        cartItems: ko.observableArray([]),
        bundleItems: ko.observableArray([]),
        deliveryMethodText: ko.observable("Delivery Method"),
        cartSummaryText: ko.observable("Cart summary"),
        viewDetailsText: ko.observable("VIEW DETAILS"),
        orderRelatedInformation: ko.observable(true),
        isAdditionalDetailsAvailable: ko.observable(false),
        canShowEstimatedShippingTotal: ko.observable(false),
        isSdeStore: ko.observable(isSdeStoreEnable),
        shippingTotalFromTransactionApi: ko.observable(0.00),
        orderTotalFromTransactionApi: ko.observable(0.00),
        isFclCustomer: ko.observable(false),
        isSiteConfiguredCreditCardUsed: ko.observable(false),
        itemCount: ko.observable(false),
        accountDiscountAmount: ko.observable(0.00),
        volumeDiscountAmount: ko.observable(0.00),
        bundleDiscountAmount: ko.observable(0.00),
        promoDiscountAmount: ko.observable(0.00),
        shippingDiscountAmount: ko.observable(0.00),
        isPromoDiscountApplied: ko.observable(false),
        isVolumeDiscountApplied: ko.observable(false),
        isBundleDiscountApplied:ko.observable(false),
        isAccountDiscountApplied: ko.observable(false),
        isShippingDiscountApplied: ko.observable(false),
        isMarketingOptInEnabled: ko.observable(marketingOptInEnabled),
        isEssendantToggleEnabled: ko.observable(tiger_enable_essendant),
        isProductBundlesToggleEnabled: ko.observable(togglesAndSettings.isToggleEnabled('tiger_e468338')),
        isOnlyNonCustomizableCart: ko.observable(window?.checkout?.tiger_enable_essendant && window?.checkout?.only_non_customizable_cart),
        isCBBToggleEnabled: ko.observable(window?.checkout?.is_cbb_toggle_enable ?? false),
        enableShippingFreeText: ko.observable(enableShippingFreeText),
        marketingOptInUrl: ko.observable(),
        carrierTitle: ko.observable(''),
        carrierPrice: ko.observable(''),
        orderConfirmationCancellationMsg: ko.observable('Note: ' + window?.checkout?.order_confirmation_cancellation_message),
        isOrderApprovalEnabled: ko.observable(false),
        expectedDeliveryDate: ko.observable(''),
        isExpectedDeliveryDateEnabled: ko.observable(window?.checkout?.is_expected_delivery_date_enabled),
        customerSupportUrl: urlBuilder.build('contact-support'),

        /**
         * Get the B2B order sucess toast message
         *
         * @return {string}
         */
        b2bPendingOrderSuccessToastMsg: function () {
            return typeof(window.checkout.b2b_order_scucess_toast_msg) != 'undefined' && typeof(window.checkout.b2b_order_scucess_toast_msg) != null ? window.checkout.b2b_order_scucess_toast_msg : null;
        },

        /**
         * Get the toast message info icon url
         *
         * @return {string}
         */
        toastMsgInfoIconUrl: function () {
            return typeof(window.checkout.info_icon_image) != 'undefined' && typeof(window.checkout.info_icon_image) != null ? window.checkout.info_icon_image : null;
        },

        initialize: function (data) {
            var self = this;

            if(typeof data.errorIconImageSrc != 'undefined' && data.errorIconImageSrc != null) {
                this.errorIconSrcUrl(data.errorIconImageSrc);
            }

            $(document).on('click', '.fujitsu-receipt-error-close-icon', function () {
                $('.fujitsu-receipt-error').hide();
            });

            /**
             * Get Transaction response data in case transaction API timeout
             */

            if (isCheckoutObjectAvailable
                && typeof(window.checkout.transaction_response) !== 'undefined'
                && window.checkout.transaction_response !== null
                && window.checkout.transaction_response !== false
                && window.checkout.transaction_response.length > 0) {

                let transactionTimeoutResponse = window.checkout.transaction_response;
                let transactionTimeout = JSON.stringify(transactionTimeoutResponse);
                let transactionResponseData = JSON.parse(transactionTimeout);

                let checkoutResponse = (typeof (transactionResponseData[0]) !== "undefined" && transactionResponseData[0] !== null &&
                    transactionResponseData[0].error == 0) ? JSON.stringify(transactionResponseData[0].response) : null;
                /**
                 * D-180197
                 * Keep "CJTransactionData" in regular local storage
                 * due Commission Junction compatibility.
                 */
                localStorage.setItem("CJTransactionData", JSON.stringify(checkoutResponse));
                if(window.e383157Toggle){
                    if (checkoutResponse !== null) {
                        fxoStorage.delete('orderInProgress');
                        fxoStorage.delete('chosenDeliveryMethod');
                        if(typeof checkoutResponse === 'string'){
                            checkoutResponse = JSON.parse(checkoutResponse);
                        }
                        fxoStorage.set("orderTransactionData", checkoutResponse);
                        //B-1275188 set rate quote response in localstorage
                        if (typeof transactionResponseData[0].rateQuoteResponse != 'undefined') {
                            fxoStorage.set("rateQuoteData", JSON.stringify(transactionResponseData[0].rateQuoteResponse));
                        }
                        //B-1242824: Setting sde store identifier in order to get the flag in the order confirmation page
                        fxoStorage.set("isSdeStore", self.isSdeStore());
                        fxoStorage.set("TaxAmount", '');
                        fxoStorage.set("EstimatedTotal", '');
                        fxoStorage.set("selectedRadioShipping", '');
                    }
                }else{
                    if (checkoutResponse !== null) {
                        localStorage.removeItem('orderInProgress');
                        localStorage.removeItem('chosenDeliveryMethod');
                        localStorage.setItem("orderTransactionData", JSON.stringify(checkoutResponse));
                        //B-1275188 set rate quote response in localstorage
                        if (typeof transactionResponseData[0].rateQuoteResponse != 'undefined') {
                            localStorage.setItem("rateQuoteData", JSON.stringify(transactionResponseData[0].rateQuoteResponse));
                        }
                        //B-1242824: Setting sde store identifier in order to get the flag in the order confirmation page
                        localStorage.setItem("isSdeStore", self.isSdeStore());
                        localStorage.setItem("TaxAmount", '');
                        localStorage.setItem("EstimatedTotal", '');
                        localStorage.setItem("selectedRadioShipping", '');
                    }
                }

            }
            let orderTransactionData;
            if(window.e383157Toggle){
                orderTransactionData = fxoStorage.get("orderTransactionData");
            }else{
                orderTransactionData = JSON.parse(JSON.parse(localStorage.getItem('orderTransactionData')));
            }
            if (typeof orderTransactionData !== 'object') {
                window.location.replace(window.BASE_URL);
            }
            // Fetching order confirmation data from Transaction CXS response.
            let paymentData,orderConfirmationResponse;
            if(window.e383157Toggle){
                paymentData = fxoStorage.get("paymentData");
                orderConfirmationResponse = fxoStorage.get('orderTransactionData');
            }else{
                paymentData = JSON.parse(localStorage.getItem('paymentData'));
                orderConfirmationResponse = JSON.parse(localStorage.getItem('orderTransactionData'));
            }
            let isShip;
            if(window.e383157Toggle){
                isShip = fxoStorage.get("shipkey") === "true";
                self.isSdeStore(fxoStorage.get("isSdeStore") === "true");
            }else{
                isShip = localStorage.getItem("shipkey") === "true";
                self.isSdeStore(localStorage.getItem("isSdeStore") === "true");

            }
            if (isShip) {
                this.isDelivery(true);
            } else {
                this.isDelivery(false);
            }
            if (this.isDelivery()) {
                this.setDeliveryData();
            } else {
                this.setPickupData();
            }
            //B-1326759: Remove billing address name when site configured CC is used for payment
            let useSiteCreditCard;
            if(window.e383157Toggle){
                useSiteCreditCard = fxoStorage.get("useSiteCreditCard") === "true";
            }else{
                useSiteCreditCard = localStorage.getItem("useSiteCreditCard") === "true";
            }
            self.isSiteConfiguredCreditCardUsed(useSiteCreditCard);

            //B-1242824: Order confirmation page label changes for sde
            if (self.isSdeStore()) {
                this.deliveryMethodText("Shipping method");
                this.cartSummaryText("Order summary");
                this.viewDetailsText("SHOW DETAILS");
                // B-1248592 : On Order confirmation the message  cancellation within 15 mins  should not appear
                this.orderRelatedInformation(false);
            }

            if(window.checkout.is_fcl_customer) {
                this.isFclCustomer(true);
            }

            //D-97873 Add show-hide details for sde order summary items
            if (!self.isSdeStore()) {
                $(document).on('click', '.show-details-btn', function () {
                    var indexId = $(this).attr('contentshow');

                    $('tr[content="'+indexId+'"]').show();
                    $('tr[id="'+indexId+'"]').hide();
                    $('[contenthide="'+indexId+'"]').show();
                    $('[contentshow="'+indexId+'"]').hide();
                    if ($(window).width() < 768) {
                        $('tr[content="' + indexId + '-title"]').show();
                    }
                });

                $(document).on('click', '.hide-details-button', function () {
                    var indexId = $(this).attr('contenthide');

                    $('tr[id="'+indexId+'"]').show();
                    $('tr[content="'+indexId+'"]').hide();
                    $('[contenthide="'+indexId+'"]').hide();
                    $('[contentshow="'+indexId+'"]').show();
                    if (indexId.includes('build-child')){
                        $('tr[content="' + indexId + '"] .hide-details-button').hide();
                        $('tr[content="' + indexId + '"] .show-details-btn').show();
                        $('tr[content="' + indexId + '"] + .show-hide-content').hide();

                        if ($(window).width() < 768) {
                            $('tr[content="' + indexId + '-title"]').hide();
                        }
                    }
                });
            } else {
                $(document).on('click', '.show-details-button', function () {
                    const details = $(this).closest(".table-row").find(".details-container");
                    if (details.css('display') == 'none' || detailsCartTable.css('display') == 'none') {
                        $(this).closest(".table-row").find(".show-details-button-text").text("HIDE DETAILS");
                        $(this).closest('.table-row').find(".show-details-button-text").addClass("hide-details-button-text");
                        details.show();
                    } else {
                        $(this).closest('.table-row').find(".show-details-button-text").removeClass("hide-details-button-text");
                        details.hide();
                        $(this).closest(".table-row").find(".show-details-button-text").text(self.viewDetailsText());
                    }
                });
            }

            $(document).on('click', '.order-message-container .fa-times', function () {
                $('.order-message-container .fa-times').parent().parent().hide();
            });

            let transactionResponse = (typeof orderConfirmationResponse == 'string')
                ? JSON.parse(orderConfirmationResponse)
                : orderConfirmationResponse;

            if (!this.isDelivery()) {
                let pickupInfo;
                if (window.e383157Toggle) {
                    pickupInfo = fxoStorage.get("pickupData");
                } else {
                    pickupInfo = JSON.parse(localStorage.getItem("pickupData"));
                }
                this.emailId(pickupInfo.contactInformation.contact_email);
            } else {
                this.emailId(transactionResponse.output.checkout.contact.emailDetail.emailAddress);
            }
            let isOrderApprovalFlag = typeof(transactionResponse.output.checkout.isOrderApprovalEnabled) !== "undefined" &&
            transactionResponse.output.checkout.isOrderApprovalEnabled !== null ? transactionResponse.output.checkout.isOrderApprovalEnabled : false;

            if(isOrderApprovalFlag) {
                this.isOrderApprovalEnabled(true);
            }
            this.userName("Thank you, " + transactionResponse.output.checkout.contact.personName.firstName + " " + transactionResponse.output.checkout.contact.personName.lastName + ".");
            this.orderNo("Order number #" + transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].origin.orderNumber);
            if (!isOrderApprovalFlag) {
                this.transactionId(transactionResponse.output.checkout.transactionHeader.retailTransactionId);
            }
            this.gtnNumber(transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].origin.orderNumber);

            this.showDetails = "Show Details";
            var arrLength = transactionResponse.output.checkout.tenders.length;

            if (this.isDelivery()) {

                let shippingAccountNumber = '';

                if (window.e383157Toggle) {
                    shippingAccountNumber = fxoStorage.get("shipping_account_number");
                } else {
                    shippingAccountNumber = localStorage.getItem("shipping_account_number");
                }

                if (this.deliveryMethod().fedexShipAccountNumber) {
                    shippingAccountNumber = this.deliveryMethod().fedexShipAccountNumber;
                }

                if (!shippingAccountNumber) {
                    this.isfedexShipAccountNumber(false);
                } else {
                    this.isfedexShipAccountNumber(true);
                    this.fedexShipAccountNumber("ending in *" + shippingAccountNumber.substring(shippingAccountNumber.length - 4));
                }
            }

            if (paymentData !== null) {
                if (paymentData.paymentMethod == 'cc') {
                    this.payBy("Pay by credit card");
                    this.isCreditCardSelected(true);
                    this.isBillingAddress(paymentData.isBillingAddress);
                    if (arrLength == 1) {
                        this.creditCardNumber("ending in *" + transactionResponse.output.checkout.tenders[0].creditCard.accountLast4Digits.substring(transactionResponse.output.checkout.tenders[0].creditCard.accountLast4Digits.length - 4));
                    } else {
                        this.creditCardNumber("ending in *" + transactionResponse.output.checkout.tenders[1].creditCard.accountLast4Digits.substring(transactionResponse.output.checkout.tenders[1].creditCard.accountLast4Digits.length - 4));
                    }
                    this.isFedexAccountApplied(paymentData.isFedexAccountApplied);

                    if (paymentData.fedexAccountNumber) {
                        this.isfedexAccountNumber(true)
                        this.fedexAccountNumber("ending in *" + paymentData.fedexAccountNumber.substring(paymentData.fedexAccountNumber.length - 4));
                    } else {
                        this.isfedexAccountNumber(false);
                    }

                } else {
                    this.payBy("Pay by FedEx Office Print Account");
                    this.isfedexAccountNumber(true);
                    this.isCreditCardSelected(false);
                    this.fedexAccountNumber(
                        "ending in *" + paymentData.fedexAccountNumber.substring(paymentData.fedexAccountNumber.length - 4)
                    );
                }
            } else {
                this.paymentStepData(null);
            }
            this.paymentStepData(paymentData);
            this.totalDiscountAmount(this.priceFormatWithCurrency(transactionResponse.output.checkout.transactionTotals.totalDiscountAmount));
            self.discountTypeCalculate(transactionResponse);
            $(window).on('load',function() {
                $("#discdrop").on("keypress", function(event) {
                    if (event.keyCode === 13) {
                        $('.toggle-discount .drop').toggleClass("up");
                        $('.discount_breakdown').slideToggle(100);
                    }
                    event.stopImmediatePropagation();
                });
                let discountAmount = $('.order-success-discount').text();
                discountAmount = self.stringToFloat(discountAmount);
                if(discountAmount == 0){
                    $('.order-success-discount').text('-');
                }
            });
            $(window).ajaxStop(function() {
                $("#discdrop").on("keypress", function(event) {
                    if (event.keyCode === 13) {
                        $('.toggle-discount .drop').toggleClass("up");
                        $('.discount_breakdown').slideToggle(100);
                    }
                    event.stopImmediatePropagation();
                });
                let discountAmount = $('.order-success-discount').text();
                discountAmount = self.stringToFloat(discountAmount);
                if(discountAmount == 0){
                    $('.order-success-discount').text('-');
                }
            });
            var productRetailPrice = 0;
            var shippingPrice = 0;

            const deliveryLines =  transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].deliveryLines.filter(deliveryLine => deliveryLine.deliveryLineType === 'SHIPPING');
            const deliveryLinesPrice = deliveryLines.reduce((accumulator, deliveryLine) => accumulator + (+deliveryLine.deliveryLinePrice), 0);
            shippingPrice = deliveryLinesPrice;

            self.shippingTotalFromTransactionApi(shippingPrice);
            self.orderTotalFromTransactionApi(transactionResponse.output.checkout.transactionTotals.totalAmount);
            // transactionResponse.output.checkout.transactionTotals.currency
            this.grossAmount("$" + transactionResponse.output.checkout.transactionTotals.grossAmount);
            this.netAmount("$" + transactionResponse.output.checkout.transactionTotals.netAmount);


            if(window.mazegeek_B2352379_discount_breakdown) {
                var deliveryLinesArray = null;
                var shippingDiscountPrice = 0.00;
                var shippingDiscountAmount = 0.00;
                if (typeof transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].deliveryLines != "undefined") {
                    deliveryLinesArray = transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].deliveryLines;
                    deliveryLinesArray.forEach((discount) => {
                        if (typeof discount.deliveryLineDiscounts != "undefined") {
                            discount.deliveryLineDiscounts.forEach((nameDiscount) => {
                                if (nameDiscount.type == 'COUPON' || nameDiscount.type == 'CORPORATE') {
                                    shippingDiscountAmount = nameDiscount.amount;
                                    shippingDiscountPrice += self.discountCalculate(shippingDiscountAmount);
                                    shippingDiscountAmount = shippingDiscountPrice;
                                }
                            });
                        } else {
                            if (typeof discount.deliveryDiscountAmount != "undefined") {
                                shippingDiscountPrice += self.discountCalculate(discount.deliveryDiscountAmount);
                                shippingDiscountAmount = shippingDiscountPrice;
                            }
                        }
                    });
                }
                if (shippingDiscountAmount > 0) {
                    shippingPrice = shippingPrice + shippingDiscountAmount;
                }
                this.shippingDiscountAmount(this.priceFormatWithCurrency(shippingDiscountAmount));
            }

            this.shippingAmount(this.priceFormatWithCurrency(shippingPrice));
            this.taxAmount(this.priceFormatWithCurrency(transactionResponse.output.checkout.transactionTotals.taxAmount));
            this.totalAmount(this.priceFormatWithCurrency(transactionResponse.output.checkout.transactionTotals.totalAmount));

            //estimated total and estimated shipping total calculation
            this.calculateEstimatedOrderTotals();

            let orderNumber = null;
            if(typeof(transactionResponse.output.checkout.lineItems[0]) !== "undefined" && typeof(transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].origin.orderNumber) !== "undefined" ) {
                orderNumber = transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].origin.orderNumber;
            }
            let orderSalePrice = transactionResponse.output.checkout.transactionTotals.totalAmount;
            if (!isOrderApprovalFlag && (fxoStorage.get('adobeAnalyticsGDL') === '1' || !isToggleD219954Enabled)) {
                gdlEvent.appendGDLScript(orderSalePrice, orderNumber);
            }
            // merging addition data with transactionResponse data
            //D-97873 Do not show additional details as well as mask image in sde

            if ( !self.isSdeStore() ) {
                this.isAdditionalDetailsAvailable(true);
                this.viewDetailsText("Show Details");
            }
            let arrItemIds = [];
            let arrInstanceIds = [];
            let arrCount = 0;
            let additionalData = this.getAdditionalData();
            transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines.forEach(function (product) {
                additionalData.forEach(function (additionDataItem) {
                    if (isXmenOrderConfirmationFix && product.type != 'EXTERNAL_PRODUCT') {
                        if (additionDataItem.itemId && additionDataItem.itemId === product.instanceId
                            && !arrItemIds.includes(additionDataItem.itemId) && !arrInstanceIds.includes(product.instanceId)) {
                            arrItemIds[arrCount] = additionDataItem.itemId;
                            arrInstanceIds[arrCount] = product.instanceId;
                            product['preview_url'] = additionDataItem['preview_url'];
                            product['features'] = additionDataItem['features'];
                            arrCount = arrCount + 1;
                        }
                    } else {
                        product['features'] = [];
                        if (additionDataItem.itemId && additionDataItem.itemId === product.instanceId) {
                            product['preview_url'] = additionDataItem['preview_url'];
                            product['features'] = additionDataItem['features'];
                        }
                    }
                });
            });
            const transactionProducts = transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines;
            if (Array.isArray(window.bundleItems) && window.bundleItems.length > 0 && this.isProductBundlesToggleEnabled()) {
                let result = this.extractChildrenFromTransaction(transactionProducts, window.bundleItems);
                this.cartItems([...result.parents,...result.instances]);
            } else {
                this.cartItems(transactionProducts);
            }
            this.productDescription(transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines[0].productLineDetails[0].description);
            this.qty(transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines[0].unitQuantity);
            this.discount(transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines[0].productDiscountAmount);
            this.total("$" + transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines[0].productLinePrice);
            if (typeof transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines != "undefined") {
                transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines.forEach((productLine) => {
                    let productRetailsPrice = '';
                    if (isOrderApprovalFlag) {
                        productRetailsPrice = parseFloat(productLine.productRetailPrice);
                    } else {
                        productRetailsPrice = parseFloat(productLine.productRetailPrice.replace('$', "").replace(',', ""));
                    }

                    productRetailPrice += productRetailsPrice;
                });
            }

            productRetailPrice = this.priceFormatWithCurrency(productRetailPrice);

            this.productTotal(productRetailPrice);

            var sections = ['cart'];
            customerData.invalidate(sections);
            customerData.invalidate(['customer']);
            if(window.e383157Toggle){
                fxoStorage.set("successUrl", window.location.href);
            }else{
                localStorage.setItem("successUrl", window.location.href);
            }
            if (self.isMarketingOptInEnabled()) {
                var marketingOptInUrl = typeof(window.checkout.marketing_opt_in.url) !== "undefined" &&
                window.checkout.marketing_opt_in.url !== null && window.checkout.marketing_opt_in.url !== false ? window.checkout.marketing_opt_in.url : '';
                self.marketingOptInUrl(marketingOptInUrl);
            }
            this._super();
        },

        /**
         * Build Order print receipt
         */
        buildOrderPrintReceipt: function () {
            let body = $('body').loader();
            body.loader('show');
            let requestParams = {};
            $('.fujitsu-receipt-error').hide();
            let transactionId = $("#fujitsu-print-receipt").attr("transactionid");
            let gtnNumber = $("#fujitsu-print-receipt").attr("orderid");
            let printReceiptRequestUrl = urlBuilder.build('orderhistory/order/receipt');
            let postData = {'transaction_id': transactionId, 'gtn_number': gtnNumber};
            requestParams = JSON.stringify(postData);
            storage.post(
                printReceiptRequestUrl,
                requestParams,
                false
            ).done(function (response) {
                if(response.status) {
                    body.loader('hide');
                    let printScreen = null;
                    printScreen = window.open(response.response, '_blank');
                    if (typeof(printScreen) != 'undefined' && printScreen != null && typeof(printScreen.window) != 'undefined'
                        && printScreen.window != null) {
                        printScreen.window.print();
                    }
                } else {
                    body.loader('hide');
                    $('.fujitsu-receipt-error').show();
                }
            }).fail(function(response) {
                body.loader('hide');
                $('.fujitsu-receipt-error').show();
            });
        },

        extractChildrenFromTransaction: function (instances, parents) {
            // Create a map for quick parent lookup
            const parentMap = new Map();
            const newParents = parents.map(parent => ({...parent, children: []}));
            let instancesSorted = instances.sort((a, b) => a.instanceId - b.instanceId);
            newParents.forEach(parent => {
                parent.child_ids.forEach(childId => {
                    parentMap.set(childId, parent);
                });
            });

            const remainingInstances = instancesSorted.filter(instance => {
                const parent = parentMap.get(instance.instanceId);

                if (parent) {
                    if (!instance.preview_url) {
                        instance.preview_url = parent['children_data']?.[instance.instanceId]?.['preview_url'] ?? '';
                    }
                    if (!instance.features) {
                        instance.features = [];
                    }
                    parent.children.push(instance);
                    return false;
                }

                return true;
            });

            return {
                instances: remainingInstances,
                parents: newParents
            };
        },

        getExtractedChildrenCount: function(instances, parents) {
            const result = this.extractChildrenFromTransaction(instances, parents);
            return result.parents.length + result.instances.length;
        },

        /**
         * Is Discount Breakdown enable
         *
         * @return {Boolean}
         */
        toggleDiscount: function () {
            $('.toggle-discount .drop').toggleClass("up");
            $('.discount_breakdown').slideToggle(100);
        },

        /**
         * Is Discount Breakdown enable
         *
         * @return {Boolean}
         */
        discountTypeCalculate: function (transactionResponse) {
            var self = this;
            var itemCount = 0;
            var productLinesArray = null;
            var deliveryLinesArray = null;
            var accountDiscountPrice = 0;
            var volumeDiscountPrice = 0;
            var promoDiscountPrice = 0;
            var promoDiscountAmount = 0;
            var volumeDiscountAmount = 0;
            var bundleDiscountAmount = 0;
            var bundleDiscountPrice = 0;
            var accountDiscountAmount = 0;
            var shippingDiscountPrice =0.00;
            var shippingDiscountAmount = 0.00;
            this.totalDiscountAmount("-");

            if(transactionResponse.output.checkout.transactionTotals.totalDiscountAmount > 0) {
                this.totalDiscountAmount("-"+this.priceFormatWithCurrency(transactionResponse.output.checkout.transactionTotals.totalDiscountAmount));
            }
            if (typeof transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].deliveryLines != "undefined") {
                deliveryLinesArray = transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].deliveryLines;
                deliveryLinesArray.forEach((discount) => {
                    if (typeof discount.deliveryLineDiscounts != "undefined") {
                        discount.deliveryLineDiscounts.forEach((nameDiscount) => {
                            if (nameDiscount.type == 'COUPON' || nameDiscount.type == 'CORPORATE') {
                                shippingDiscountAmount = nameDiscount.amount;
                                shippingDiscountPrice += self.discountCalculate(shippingDiscountAmount);
                                shippingDiscountAmount = shippingDiscountPrice;
                            }
                        });
                    } else {
                        if (typeof discount.deliveryDiscountAmount != "undefined") {
                            shippingDiscountPrice += self.discountCalculate(discount.deliveryDiscountAmount);
                            shippingDiscountAmount = shippingDiscountPrice;
                        }
                    }
                });
            }
            if (typeof transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines != "undefined") {
                productLinesArray = transactionResponse.output.checkout.lineItems[0].retailPrintOrderDetails[0].productLines;
                if (Array.isArray(window.bundleItems) && window.bundleItems.length > 0 && this.isProductBundlesToggleEnabled()) {
                    itemCount = this.getExtractedChildrenCount(productLinesArray, window.bundleItems);
                } else {
                    itemCount = productLinesArray.length;
                }
                productLinesArray.forEach((discount) => {
                    if (typeof discount.productLineDiscounts != "undefined") {
                        discount.productLineDiscounts.forEach((nameDiscount) => {
                            if (nameDiscount.type == 'ACCOUNT' || nameDiscount.type == 'CORPORATE' || nameDiscount.type == 'AR_CUSTOMERS') {
                                accountDiscountAmount = nameDiscount.amount;
                                accountDiscountPrice += self.discountCalculate(accountDiscountAmount);
                            }
                            if (nameDiscount.type == 'VOLUME') {
                                if(togglesAndSettings.isToggleEnabled('tiger_e468338') && this.isBundleChildren(discount.instanceId)){
                                    let bundleDiscountAmount = nameDiscount.amount;
                                    bundleDiscountPrice += self.discountCalculate(bundleDiscountAmount);
                                }else{
                                    let volumeDiscountAmount = nameDiscount.amount;
                                    volumeDiscountPrice += self.discountCalculate(volumeDiscountAmount);
                                }
                            }
                            if (nameDiscount.type == 'COUPON') {
                                promoDiscountAmount = nameDiscount.amount;
                                promoDiscountPrice += self.discountCalculate(promoDiscountAmount);
                            }
                            promoDiscountAmount = promoDiscountPrice;
                            volumeDiscountAmount = volumeDiscountPrice;
                            bundleDiscountAmount = bundleDiscountPrice;
                            accountDiscountAmount = accountDiscountPrice;
                        });
                    }
                });
            }
            if(promoDiscountAmount != 0.00) {
                this.isPromoDiscountApplied(true);
            }
            if(volumeDiscountAmount != 0.00) {
                this.isVolumeDiscountApplied(true);
            }
            if(bundleDiscountAmount != 0.00) {
                this.isBundleDiscountApplied(true);
            }
            if(accountDiscountAmount != 0.00) {
                this.isAccountDiscountApplied(true);
            }
            if(shippingDiscountAmount != 0.00) {
                this.isShippingDiscountApplied(true);
            }
            this.itemCount(itemCount);
            this.accountDiscountAmount(accountDiscountAmount);
            this.volumeDiscountAmount(volumeDiscountAmount);
            this.bundleDiscountAmount(bundleDiscountAmount);
            this.promoDiscountAmount(promoDiscountAmount);
            this.shippingDiscountAmount(shippingDiscountAmount);
        },

        discountCalculate: function (discountValue) {
            var self = this;
            let discountPrice = 0.00;
            if (typeof discountValue == 'string') {
                discountPrice += self.stringToFloat(discountValue);
            } else {
                discountPrice += parseFloat(discountValue);
            }
            return discountPrice;
        },

        stringToFloat: function (stringAmount) {
            return parseFloat(stringAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
        },

        isDiscountAvailable: function () {
            return (this.isVolumeDiscountApplied() || this.isBundleDiscountApplied() || this.isAccountDiscountApplied() || this.isPromoDiscountApplied() || this.isShippingDiscountApplied());
        },

        /**
         * Get Sorted Discounts
         *
         * @return {Array}
         */
        getSortedDiscounts: function() {
            let formated_promo_discount = "-"+this.priceFormatWithCurrency(this.promoDiscountAmount());
            let formated_account_discount = "-"+this.priceFormatWithCurrency(this.accountDiscountAmount());
            let formated_volume_discount = "-"+this.priceFormatWithCurrency(this.volumeDiscountAmount());
            let formated_bundle_discount = "-"+this.priceFormatWithCurrency(this.bundleDiscountAmount());
            let formated_shipping_discount = "-"+this.priceFormatWithCurrency(this.shippingDiscountAmount());
            let discountAmounts = [
                {"sort_price":this.promoDiscountAmount(),"type":"promo_discount","price":formated_promo_discount,"label":"Promo Discount","is_active":this.isPromoDiscountApplied()},
                {"sort_price":this.accountDiscountAmount(),"type":"account_discount","price":formated_account_discount,"label":"Account Discount","is_active":this.isAccountDiscountApplied()},
                {"sort_price":this.volumeDiscountAmount(),"type":"volume_discount","price":formated_volume_discount,"label":"Volume Discount","is_active":this.isVolumeDiscountApplied()},
                {"sort_price":this.shippingDiscountAmount(),"type":"shipping_discount","price":formated_shipping_discount,"label":"Shipping Discount","is_active":this.isShippingDiscountApplied()}
            ];
            if (togglesAndSettings.isToggleEnabled('tiger_e468338')) {
                discountAmounts = [
                    {"sort_price":this.promoDiscountAmount(),"type":"promo_discount","price":formated_promo_discount,"label":"Promo Discount","is_active":this.isPromoDiscountApplied()},
                    {"sort_price":this.accountDiscountAmount(),"type":"account_discount","price":formated_account_discount,"label":"Account Discount","is_active":this.isAccountDiscountApplied()},
                    {"sort_price":this.volumeDiscountAmount(),"type":"volume_discount","price":formated_volume_discount,"label":"Volume Discount","is_active":this.isVolumeDiscountApplied()},
                    {"sort_price":this.bundleDiscountAmount(),"type":"bundle_discount","price":formated_bundle_discount,"label":"Bundle Discount","is_active":this.isBundleDiscountApplied()},
                    {"sort_price":this.shippingDiscountAmount(),"type":"shipping_discount","price":formated_shipping_discount,"label":"Shipping Discount","is_active":this.isShippingDiscountApplied()}
                ];
            }
            let sortedAmounts = discountAmounts.sort((p1, p2) => (p1.sort_price < p2.sort_price) ? 1 : (p1.sort_price > p2.sort_price) ? -1 : 0);

            return sortedAmounts;
        },

        isBundleChildren: function (instanceId) {
            const bundleItems = window.bundleItems || [];

            if (!bundleItems || !instanceId) {
                return false;
            }

            return bundleItems.some(item =>
                item.child_ids &&
                item.child_ids.includes(instanceId)
            );
        },

        /**
         * Get title value.
         *
         * @return {String}
         */
        getDiscountTitle: function () {
            let discount_breakdown = this.isDiscountAvailable();
            let discounts = 'Total Discount';
            if(discount_breakdown) {
                discounts = '<span id="discdrop" tabindex="0">Total Discount(s)  <span id="discbreak" class="drop arrow-down"></span></span>';
            }else{
                discounts = 'Total Discount(s)';
            }

            return discounts;
        },

        priceFormatWithCurrency: function (price) {

            let priceForm = {
                decimalSymbol: ".",
                groupLength: 3,
                groupSymbol: ",",
                integerRequired: false,
                pattern: "$%s",
                precision: 2,
                requiredPrecision: 2,
            };

            let formattedPrice = '';

            if (typeof (price) == 'string') {
                formattedPrice = price.replace('$', '').replace(',', '').replace('(', '').replace(')', '');
                formattedPrice = priceUtils.formatPrice(formattedPrice, priceForm);
            } else {

                formattedPrice = priceUtils.formatPrice(price, priceForm);
            }

            return formattedPrice;
        },

        /**
         * Set Checkout Delivery Flow
         */
        setDeliveryData: function () {
            let ShippingInfo,StateOrProvinceCodeInfo;
            if(window.e383157Toggle){
                ShippingInfo = fxoStorage.get("shippingData");
                StateOrProvinceCodeInfo = fxoStorage.get('stateOrProvinceCode');
            }else{
                ShippingInfo = JSON.parse(localStorage.getItem('shippingData'));
                StateOrProvinceCodeInfo = localStorage.getItem('stateOrProvinceCode');
            }
            if (ShippingInfo !== null) {
                if (ShippingInfo.addressInformation.shipping_address.customAttributes[0].attribute_code == 'ext') {

                    var extAttributCode = ShippingInfo.addressInformation.shipping_address.customAttributes[0].attribute_code;
                    var extAttributValue = ShippingInfo.addressInformation.shipping_address.customAttributes[0].value;

                    ShippingInfo.addressInformation.shipping_address.customAttributes[0].attribute_code = ShippingInfo.addressInformation.shipping_address.customAttributes[1].attribute_code;

                    ShippingInfo.addressInformation.shipping_address.customAttributes[0].value = ShippingInfo.addressInformation.shipping_address.customAttributes[1].value;

                    ShippingInfo.addressInformation.shipping_address.customAttributes[1].attribute_code = extAttributCode;

                    ShippingInfo.addressInformation.shipping_address.customAttributes[1].value = extAttributValue;

                }

                // Make sure there is ext property in shipping info.
                let extAttributeObject =
                    ShippingInfo
                        .addressInformation
                        .shipping_address
                        .customAttributes
                        .find(attribute => attribute.attribute_code === 'ext')
                    || { attribute_code: 'no_ext', value: '' };

                ShippingInfo.addressInformation.shipping_address.ext_attribute = extAttributeObject;

                this.shippingAddress(ShippingInfo.addressInformation.shipping_address);
                this.billingAddress(ShippingInfo.addressInformation.billing_address);
                this.deliveryMethod(ShippingInfo.addressInformation.shipping_detail);
                this.carrierTitle(ShippingInfo.addressInformation.shipping_detail.carrier_title);
                this.carrierPrice(ShippingInfo.addressInformation.shipping_detail.base_amount);
                this.expectedDeliveryDate(ShippingInfo.addressInformation.shipping_detail.method_title);
            }
            if (StateOrProvinceCodeInfo != null) {
                this.stateOrProvinceCode(StateOrProvinceCodeInfo);
            }
        },

        /**
         * Set Checkout Pickup Flow
         */
        setPickupData: function () {
            let pickupTime,pickupInfo;
            if(window.e383157Toggle){
                pickupInfo = fxoStorage.get('pickupData');
                pickupTime = fxoStorage.get('pickupDateTime');
            }else{
                pickupInfo = JSON.parse(localStorage.getItem('pickupData'));
                pickupTime = localStorage.getItem('pickupDateTime');
            }
            if (pickupInfo !== null) {
                this.pickupAddressInformation(pickupInfo.addressInformation);
                this.pickupContactInformation(pickupInfo.contactInformation);
                this.pickupShipMethod(pickupInfo.addressInformation.shipping_detail);
                this.pickupDateTime(pickupTime);
            }
        },

        /**
         * Get additional features data
         *
         * @return {object}
         */
        getAdditionalData: function (itemId = null) {
            let self = this;
            let additionalData = [];
            let productData = [];
            let cacheStorage,cart;
            if (window.e383157Toggle) {
                cart = fxoStorage.get("success-cart");
                if (Object.keys(cart).length > 0) {
                    cart.items.find((item, index) => {
                        if (!item.is_third_party_product) {
                            if ((typeof (item.externalProductInstance) !== 'undefined' &&
                                item.externalProductInstance !== null &&
                                item.externalProductInstance !== "")
                            ) {
                                productData = [];
                                if (typeof (item.externalProductInstance.features) !== "undefined"
                                    && item.externalProductInstance.features != null) {
                                    item.externalProductInstance.features.forEach(function (feature, featureIndex) {

                                        let isItemSpecific = (itemId === item.item_id);
                                        let isNotItemSpecific = (itemId === null);

                                        if (isItemSpecific || isNotItemSpecific) {
                                            productData.push({
                                                name: feature.name,
                                                value: feature.choice.name
                                            });
                                        }
                                    });
                                }
                            }
                            if(togglesAndSettings.isToggleEnabled('tiger_e468338')
                                && item.product_type === 'bundle'
                                && item.childrenExternalProductInstance !== 'undefined'
                            ) {

                                Object.entries(item.childrenExternalProductInstance).forEach(([childItemId, childItem]) => {
                                    productData = [];
                                    childItem.features.forEach(function (feature, featureIndex) {
                                        productData.push({
                                            name: feature.name,
                                            value: feature.choice.name
                                        });
                                    });
                                    additionalData.unshift(
                                        {
                                            preview_url: childItem.preview_url,
                                            features: productData,
                                            itemId: childItemId
                                        }
                                    );
                                });
                            } else {

                                additionalData.unshift(
                                    {
                                        preview_url: !self.isSdeStore() ? item.externalProductInstance.preview_url : item.product_image.src,
                                        features: productData,
                                        itemId: item.item_id
                                    }
                                );
                            }
                        }
                    });
                }
            } else {
                cacheStorage = JSON.parse(window.localStorage.getItem('success-mage-cache-storage'));
                if (typeof (cacheStorage.cart) != "undefined" && cacheStorage.cart != null) {
                    if (window.localStorage.getItem('cart-items') && !cacheStorage.cart.items.length && isXmenOrderConfirmationFix) {
                        cacheStorage.cart.items = JSON.parse(window.localStorage.getItem('cart-items'));
                        window.localStorage.setItem('success-mage-cache-storage', JSON.stringify(cacheStorage))
                        window.localStorage.removeItem('cart-items');
                    }
                    cacheStorage.cart.items.find((item, index) => {
                        if (!item.is_third_party_product) {
                            if ((typeof (item.externalProductInstance) !== 'undefined' &&
                                item.externalProductInstance !== null &&
                                item.externalProductInstance !== "")
                            ) {
                                productData = [];
                                if (typeof (item.externalProductInstance.features) !== "undefined"
                                    && item.externalProductInstance.features != null) {
                                    item.externalProductInstance.features.forEach(function (feature, featureIndex) {

                                        let isItemSpecific = (itemId === item.item_id);
                                        let isNotItemSpecific = (itemId === null);

                                        if (isItemSpecific || isNotItemSpecific) {
                                            productData.push({
                                                name: feature.name,
                                                value: feature.choice.name
                                            });
                                        }
                                    });
                                }
                            }
                            let itemImage = item.externalProductInstance.preview_url;
                            if (item.externalProductInstance.preview_url !== 'undefined' ){
                                itemImage = item.product_image.src;
                            }
                            if (!item.externalProductInstance && isXmenOrderConfirmationFix) {
                                productData = [];
                            }
                            additionalData.unshift(
                                {
                                    preview_url: !self.isSdeStore() ? itemImage : item.product_image.src,
                                    features: productData,
                                    itemId: item.item_id
                                }
                            );
                        }
                    });
                }
            }
            return additionalData;
        },

        /*
         * Is alignment Issue fix added class on container
         */
        isAligmentIssueFix: function () {
            return 'order-success-page-fix-alignment-issue';
        },

        /**
         * Request for get image Preview
         *
         * @param {string} contentReferenceId - Conference Reference Id for request
         * @param {object} e - Image Dom Object
         */
        imgLoad: function(contentReferenceId, e, instanceId = null) {
            if(instanceId) {
                let imageData = window.e383157Toggle
                    ? fxoStorage.get('product_image_data')
                    : JSON.parse(localStorage.getItem('product_image_data'));
                if(imageData && imageData[instanceId] !== undefined && imageData[instanceId] != null) {
                    $(e).attr("src",imageData[instanceId]);
                    $(e).attr(
                        "src",
                        imageData[instanceId]
                    );
                    $(e).closest(".prev-img-loader").removeClass("prev-img-loader");
                    $(e).parent().find(".product-loader").remove();
                    return;
                }
            }
            contentReferenceId = String(contentReferenceId);
            if (typeof(previewImg) !== "undefined" && previewImg !== null) {
                // B-2436408 Remove preview api calls
                if (window.checkout?.tech_titans_b_2421984_remove_preview_calls_from_catalog_flow == true) {
                    if (typeof(previewImg) !== "undefined" && previewImg !== null) {
                        setTimeout(() => {
                            previewImg.getPreviewImg(contentReferenceId, e);
                        }, 1000);
                    }
                } else {
                    previewImg.getPreviewImg(contentReferenceId, e);
                }
            }
        },

        /**
         * Get estimated total and estimated shipping total based on rate quote response
         *
         * B-1275188 implement order total based on rate quote response
         *
         * @todo Validate this method once rate quote api changes are implemented
         */
        calculateEstimatedOrderTotals: function () {
            var self = this;
            let rateQuoteData;
            if(window.e383157Toggle){
                rateQuoteData = fxoStorage.get("rateQuoteData");
            }else{
                rateQuoteData = JSON.parse(localStorage.getItem('rateQuoteData'));
            }
            let estimatedShippingTotal = 0.00;
            let estimatedTotal = 0.00;
            let shippingTotalAmt = 0;
            if (rateQuoteData && typeof rateQuoteData.output.rateQuote.rateQuoteDetails != 'undefined') {
                rateQuoteData.output.rateQuote.rateQuoteDetails.forEach((rateDetail) => {
                    if (typeof rateDetail.estimatedVsActual != "undefined" &&
                        rateDetail.estimatedVsActual == 'ESTIMATED'
                    ) {
                        estimatedShippingTotal = rateDetail.totalAmount;
                    } else if (typeof rateDetail.estimatedVsActual != "undefined"
                        && rateDetail.estimatedVsActual == 'ACTUAL'
                    ) {
                        estimatedTotal = rateDetail.totalAmount;
                    }
                    if(rateDetail.deliveriesTotalAmount) {
                        shippingTotalAmt = rateDetail.deliveriesTotalAmount;
                    }
                });
                //we need to show estimated shipping total only if its available in the response
                if(shippingTotalAmt) {
                    self.marketplaceShippingAmount(this.priceFormatWithCurrency(shippingTotalAmt));
                }
                if (estimatedShippingTotal) {
                    self.canShowEstimatedShippingTotal(true);
                    self.shippingAmount(this.priceFormatWithCurrency(estimatedShippingTotal));
                }
                if (estimatedTotal) {
                    self.totalAmount(this.priceFormatWithCurrency(estimatedTotal));
                }
            }
            //if estimated shipping total is not available in rate quote response, we fetch this from transaction
            if (!self.canShowEstimatedShippingTotal() && self.isSdeStore()) {
                self.canShowEstimatedShippingTotal(true);
                self.shippingAmount(this.priceFormatWithCurrency(self.shippingTotalFromTransactionApi()));
                self.totalAmount(this.priceFormatWithCurrency(
                    self.orderTotalFromTransactionApi() - self.shippingTotalFromTransactionApi()
                ));
            }
        },

        /**
         * Get image loader url
         *
         * @return {string} - get image loader url
         */
        imgLoaderUrl: function () {
            return window.LoaderImgUrl;
        },

        /**
         * Check image url with jpg, png and jpeg
         *
         * @param {string} src - src url
         * @return {boolean} true|false
         */
        isCheckImage:  function (src) {

            var srcUrl = String(src) ? String(src) : '';
            var srcConditions = srcUrl.includes('png') || srcUrl.includes('.png') || srcUrl.includes('.jpg') || srcUrl.includes('jpeg');

            return srcConditions;
        },

        toggleAdditionalDataVisibilityOnOrderSuccess: function(config, event) {

            let showHideButton = $(event.target);

            if( showHideButton.hasClass('show-details-button-text') ) {
                showHideButton.text('Hide details');
                showHideButton.removeClass('show-details-button-text');
                showHideButton.addClass('hide-details-button-text');
                showHideButton.closest('table.order-success').find('td.additional-details-container.item-' + config.instanceId).show();
            }
            else {
                showHideButton.text('Show details');
                showHideButton.addClass('show-details-button-text');
                showHideButton.removeClass('hide-details-button-text');
                showHideButton.closest('table.order-success').find('td.additional-details-container.item-' + config.instanceId).hide();
            }

        }
    });
});
