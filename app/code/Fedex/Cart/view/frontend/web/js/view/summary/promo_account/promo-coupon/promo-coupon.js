/**
 * Copyright © Magento, Inc. All rights reserved.
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
    "Fedex_Cart/js/view/summary/promo_account/fedex-account-discount/fedex-account-discount",
    "Magento_Checkout/js/model/step-navigator",
    "rateResponseHandler",
    "rateQuoteAlertsHandler",
    "Fedex_Cart/js/three-pproduct",
    "rateQuoteErrorsHandler",
    "fedex/storage",
    "Fedex_Delivery/js/model/toggles-and-settings",
], function (ko, $, Component, storage, $t, urlBuilder, quote, priceUtils, fedexAccountDiscount, stepNavigator, rateResponseHandler, rateQuoteAlertsHandler, isThreePProduct, rateQuoteErrorsHandler, fxoStorage, togglesAndSettings) {
    'use strict';

    var isExplorersAddressClassificationFixToggleEnable = typeof (window.checkoutConfig.explorers_address_classification_fix) != 'undefined' && window.checkoutConfig.explorers_address_classification_fix != null ? window.checkoutConfig.explorers_address_classification_fix : false;

    var baseUrl = window.BASE_URL;
    var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";

    return Component.extend({
        defaults: {
            template: 'Fedex_Cart/summary/promo-coupon/promo-coupon',
            isCouponCode: ko.observable(false),
            isHideApplyField: ko.observable(false),
            couponCode: ko.observable()
        },

        initialize: function (e) {
            this._super();
            var self = this;
            var quoteData = window.checkoutConfig.quoteData;
            var couponCode = quoteData.coupon_code;
            if (couponCode) {
                this.isCouponCode(true);
                this.couponCode(couponCode, true);
            }
        },
        isOnlyThreeProduct: function () {
            return isThreePProduct.isOnlyThreeProductAvailable();
        },
        /**
         * Hide label and show field to add fedex account number
         *
         * @return void
         */
        showAddPromoCodeField: function (data, event) {

            $(event.target).parent().hide();
            this.isHideApplyField(true);
            //$(e.target).parent().parent().find('.promo-code-block.field-wrap').show();
        },

        /**
         * Get Promo Code Main Label
         *
         * @return {*}
         */
        promoCodeMainLabelText: function () {

            return ('+ Add Promo Code');
        },

        /**
         * Get Promo Code Main Label
         *
         * @return {*}
         */
        label: function () {

            return ('Promo Code');
        },

        /**
         * Get Promo Code Label
         *
         * @return {*}
         */
        inputName: function () {

            return ('coupon_code');
        },

        /**
         * Get field is required
         *
         * @return {*}
         */
        uid: function () {

            return ('coupon_code');
        },

        /**
         * Get field class name
         *
         * @return {*}
         */
        applyButtonLabel: function () {

            return ('Apply');
        },
        applyPromoCode: function (data, event) {
            window.dispatchEvent(new Event('closeNonCombinableDiscount'));

            let nonCombinedMessage = $('.shipping-message-container.message-block');
            if (nonCombinedMessage.length > 0) {
                nonCombinedMessage.fadeOut();
            }
            var self = this;
            let couponCode = $('#coupon_code').val();
            $('#coupon_code').css('box-shadow', '0px 0px 3px white');
            if (typeof (couponCode) != undefined) {
                if(window.e383157Toggle){
                    fxoStorage.set('coupon_code',couponCode);
                }else{
                    localStorage.setItem('coupon_code', couponCode);
                }
                window.dispatchEvent(new Event('promoCode'));
            }

            let removeCoupon = false;
            if (event.target.id == 'removed_promo') {
                removeCoupon = true;
                $('#coupon_code').val('');
                if(window.e383157Toggle){
                    fxoStorage.set('coupon_code','');
                }else{
                    localStorage.setItem('coupon_code', '');
                }
                window.dispatchEvent(new Event('promoCode'));
            }

            if ((couponCode == null || couponCode == '') && event.target.id != 'removed_promo') {
                $(event.target).parents('.discount-actions-toolbar').next('.form-error-message').
                text('This is required field.').
                fadeIn().delay(5000).
                fadeOut();
                $('#coupon_code').parent().addClass('coupon-code-error');
                setTimeout(function () {
                    jQuery('.coupon-code-error').removeClass('coupon-code-error');
                }, 6000);

                return false;
            }

            $(event.target).parents('.discount-actions-toolbar').next('.form-error-message').slideUp();

            let requestUrl = "delivery/index/deliveryrateapishipandpickup";
            requestUrl = urlBuilder.build(requestUrl);
            let isPickup,isShipping;
            if(window.e383157Toggle){
                isPickup = fxoStorage.get('pickupkey') == 'true';
                isShipping = fxoStorage.get('shipkey') === 'true';
            }else{
                isPickup = localStorage.getItem('pickupkey') == 'true';
                isShipping = localStorage.getItem('shipkey') === 'true';
            }
            if (isPickup) {
                let locationId;
                if(window.e383157Toggle){
                    locationId = fxoStorage.get('locationId');
                }else{
                    locationId = localStorage.getItem('locationId');
                }
                let pickupDateTimeForApi,pickupPageLocation;
                if(window.e383157Toggle){
                    pickupDateTimeForApi = fxoStorage.get("pickupDateTimeForApi");
                    pickupPageLocation = fxoStorage.get("pickupPageLocation");
                }else{
                    pickupDateTimeForApi = localStorage.getItem("pickupDateTimeForApi");
                    pickupPageLocation =  localStorage.getItem("pickupPageLocation");
                }
                var payload = {
                    locationId: locationId,
                    requestedPickupLocalTime: pickupDateTimeForApi,
                    pickupPageLocation: pickupPageLocation,
                    coupon_code: couponCode,
                    remove_coupon: removeCoupon,
                    isPickupPage: true,
                    isShippingPage: false,
                    couponAppliedFromSidebar: true
                };
            } else if (isShipping) {

                let selectedShipFormData,selectedShippingMethods,selectedCarriersData;
                if(window.e383157Toggle){
                    selectedShipFormData = fxoStorage.get('selectedShipFormData');
                    selectedShippingMethods = fxoStorage.get('selectedShippingMethods');
                    selectedCarriersData = fxoStorage.get('selectedCarriersData');
                }else{
                    selectedShipFormData = localStorage.getItem('selectedShipFormData');
                    selectedShippingMethods = localStorage.getItem('selectedShippingMethods');
                    selectedCarriersData = localStorage.getItem('selectedCarriersData');
                }
                let shippingMethodData = null;
                if (typeof (selectedShipFormData) != 'undefined') {
                    selectedShipFormData = window.e383157Toggle ? selectedShipFormData : JSON.parse(selectedShipFormData);
                }
                if (typeof (selectedCarriersData) != 'undefined') {
                    selectedCarriersData = window.e383157Toggle ? selectedCarriersData : JSON.parse(selectedCarriersData);
                }
                if (typeof (selectedShippingMethods) != 'undefined') {
                    selectedShippingMethods = window.e383157Toggle ? selectedShippingMethods : JSON.parse(selectedShippingMethods);
                    for (let key in selectedShippingMethods) {
                        if (selectedShippingMethods.hasOwnProperty(key)) {
                            if (selectedShippingMethods[key].carrier_code === "fedexshipping") {
                                shippingMethodData = selectedShippingMethods[key];
                                selectedShipFormData.ship_method = shippingMethodData.method_code;
                            }
                        }
                    }
                }
                let shipMethodData;
                if(window.e383157Toggle){
                    shipMethodData = fxoStorage.get('ship_method_data') || {};
                }else{
                    shipMethodData = JSON.parse(localStorage.getItem('ship_method_data') || '{}');
                }

                var isResidenceShipping = null;
                if (isExplorersAddressClassificationFixToggleEnable) {
                    isResidenceShipping = false;
                    if (typeof (selectedShipFormData) != 'undefined' && selectedShipFormData != null) {
                        isResidenceShipping = selectedShipFormData.is_residence_shipping;
                    }
                }

                var payload = {
                    ship_method: typeof (selectedShipFormData) != 'undefined' && selectedShipFormData != null ? selectedShipFormData.ship_method : null,
                    zipcode: typeof (selectedShipFormData) != 'undefined' && selectedShipFormData != null ? selectedShipFormData.zipcode : null,
                    region_id: typeof (selectedShipFormData) != 'undefined' && selectedShipFormData != null ? selectedShipFormData.region_id : null,
                    city: typeof (selectedShipFormData) != 'undefined' && selectedShipFormData != null ? selectedShipFormData.city : null,
                    street: typeof (selectedShipFormData) != 'undefined' && selectedShipFormData != null ? selectedShipFormData.street : null,
                    shipfedexAccountNumber: typeof (selectedShipFormData) != 'undefined' && selectedShipFormData != null ? selectedShipFormData.shipfedexAccountNumber : null,
                    coupon_code: couponCode,
                    remove_coupon: removeCoupon,
                    isShippingPage: true,
                    isPickupPage: false,
                    is_residence_shipping: isResidenceShipping,
                    couponAppliedFromSidebar: true,
                    ship_method_data: shippingMethodData || shipMethodData,
                    third_party_carrier_code: selectedCarriersData && selectedCarriersData[0]?.length > 0 ? selectedCarriersData[0] : null,
                    third_party_method_code: selectedCarriersData && selectedCarriersData[1]?.length > 0 ? selectedCarriersData[1] : null,
                    first_party_carrier_code: selectedCarriersData && selectedCarriersData[2]?.length > 0 ? selectedCarriersData[2] : null,
                    first_party_method_code: selectedCarriersData && selectedCarriersData[3]?.length > 0 ? selectedCarriersData[3] : null
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
                payload = { locationId, ...payload };
            }

            $(event.target).parents('.discount-actions-toolbar').next('.form-error-message').fadeOut();
            $(event.target).parents('.apply-field-group').find('.loadersmall').show();
            $(event.target).parents('.apply-field-group').find('button.submit.primary span').css('visibility', 'hidden');
            var body = $('body').loader();
            body.loader('show');

            return storage.post(
                requestUrl,
                JSON.stringify(payload),
                false
            ).done(function (response) {

                rateQuoteErrorsHandler.errorHandler(response, true);

                let hasDiscountCombined = false;
                let hasFreeShipping = false;
                if (typeof response !== 'undefined' && response.length < 1) {
                    body.loader('hide');
                    $('.loadersmall').hide();
                    $('.error-container').removeClass('api-error-hide');
                    $(event.target).parents('.apply-field-group').find('.loadersmall').hide();

                    return true;
                } else if (response.hasOwnProperty("errors")) {
                    body.loader('hide');
                    $('.loadersmall').hide();
                    $('.error-container').removeClass('api-error-hide');
                    $(event.target).parents('.apply-field-group').find('.loadersmall').hide();
                    if (
                        typeof response.errors.is_timeout != 'undefined' &&
                        response.errors.is_timeout != null
                        ) {
                        window.location.replace(orderConfirmationUrl);
                    }

                    return true;
                }

                body.loader('hide');
                $('.error-container').hide();
                $(".shipping-message-container").hide();
                $(event.target).parents('.apply-field-group').find('.loadersmall').hide();
                $(event.target).parents('.apply-field-group').find('button.submit.primary span').css('visibility', 'visible');
                if (response) {
                    if(response.hasOwnProperty("free_shipping") && response.free_shipping.show_free_shipping_message){
                        $(".shipping-message-container").show();
                        hasDiscountCombined = true;
                        hasFreeShipping = true;
                        $(".message-text > .discount-message").text(response.free_shipping.free_shipping_message);
                    }

                    if (response.hasOwnProperty("alerts") && response.alerts.length > 0) {
                        response.rate = response.rateQuote;
                        response.rate.rateDetails = response.rateQuote.rateQuoteDetails;
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
                        let fxoAccountApplied;
                        if(window.e383157Toggle){
                            fxoAccountApplied = fxoStorage.get('fedexAccountApplied');
                        }else{
                            fxoAccountApplied = localStorage.getItem('fedexAccountApplied');
                        }
                        if (typeof window.checkoutConfig.warnings_handling != 'undefined' &&
                            window.checkoutConfig.warnings_handling) {
                            let alertMsg = rateQuoteAlertsHandler.warningHandler(response);
                            if (fxoAccountApplied && couponDiscount && !accountDiscount) {
                                $(".promo-code-block").hide();
                                $(".promo-code-block-applied").show();

                                $('.form-error-message-account').html('<span class="checkout-error-cross-icon">X</span>The account number entered is invalid.').fadeIn().delay(5000).fadeOut();
                                $('#fedex_account_no').parent().addClass('fedex-account-error');
                                setTimeout(function () {
                                    jQuery('.fedex-account-error').removeClass('fedex-account-error');
                                }, 5000);
                                $('.loadersmall').hide();
                                $('#fedex_account_no').val('');
                                $(".fedex-account-block-applied").css("display", "none");
                                $('.fedex-account-container .fedex-account-title').css("display", "block");
                                if (window.e383157Toggle) {
                                    fxoStorage.delete('fedexAccountApplied');
                                } else {
                                    localStorage.removeItem('fedexAccountApplied');
                                }
                            }
                            window.dispatchEvent(new Event('nonCombinableDiscount'));

                        }

                        let couponCode = $('#coupon_code'),
                            formErrorMessage = $('.form-error-message'),
                            loaderSmall = $('.loadersmall'),
                            delayInSeconds = 5000;

                        let alertMsg = self.getCouponAlertMsg(response);

                        if (fxoAccountApplied && !couponDiscount && accountDiscount) {
                            formErrorMessage.html('<span class="checkout-error-cross-icon">X</span>' + alertMsg).
                            fadeIn().delay(delayInSeconds).
                            fadeOut();
                            couponCode.css('box-shadow', '0px 0px 3px red');
                            setTimeout(function () {
                                couponCode.css('box-shadow', '0px 0px 3px white');
                            }, delayInSeconds);
                            loaderSmall.hide();
                            couponCode.val('');
                            if(window.e383157Toggle){
                                fxoStorage.set('coupon_code','');
                            }else{
                                localStorage.setItem('coupon_code','');
                            }
                            window.dispatchEvent(new Event('nonCombinableDiscount'));
                            return false;
                        }

                        if (alertMsg) {
                            formErrorMessage.html('<span class="checkout-error-cross-icon">X</span>' + alertMsg).
                            fadeIn().delay(delayInSeconds).
                            fadeOut();
                            couponCode.css('box-shadow', '0px 0px 3px red');
                            setTimeout(function () {
                                couponCode.css('box-shadow', '0px 0px 3px white');
                            }, delayInSeconds);
                            loaderSmall.hide();
                            couponCode.val('');
                            if(window.e383157Toggle){
                                fxoStorage.set('coupon_code','');
                            }else{
                                localStorage.setItem('coupon_code','');
                            }
                            window.dispatchEvent(new Event('promoCode'));
                            return false;
                        }
                    }
                    if ($(".applied-code-text").text()) {
                        $(".promo-code-block").hide();
                        $(".promo-code-block-applied").show();
                    }
                    if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                        window.location.replace(orderConfirmationUrl);
                    }
                    response = response.rateQuote;
                    let isPickup,isShipKey;
                    if(window.e383157Toggle){
                        isPickup = fxoStorage.get('pickupkey') == 'true';
                        isShipKey = fxoStorage.get('shipkey') == 'true'
                    }else{
                        isPickup = localStorage.getItem('pickupkey') == 'true';
                        isShipKey = localStorage.getItem('shipkey') == 'true';
                    }
                    if (isPickup) {
                        $(".place-pickup-order").show();
                        $("#rateApiResponse").val(JSON.stringify(response));
                    } else if (isShipKey) {
                        $("#rateApiResponseShipment").val(JSON.stringify(response));
                    }

                    const stringToFloat = function (stringAmount) {
                        return parseFloat(stringAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                    };

                    const discountCalculate =  function (discountValue) {
                        var self = this;
                        let discountPrice = 0.00;
                        if (typeof discountValue == 'string') {
                            discountPrice += stringToFloat(discountValue);
                        } else {
                            discountPrice += parseFloat(discountValue);
                        }
                        return discountPrice;
                    };

                    const priceFormatWithCurrency = function (price) {
                        let formattedPrice = '';
                        if (typeof (price) == 'string') {
                            formattedPrice = price.replaceAll('$', '').replaceAll(',', '').replaceAll('(', '').replaceAll(')', '');
                            formattedPrice = priceUtils.formatPrice(formattedPrice, quote.getPriceFormat());
                        } else {
                            formattedPrice = priceUtils.formatPrice(price, quote.getPriceFormat());
                        }
                        return formattedPrice;
                    };

                    var shippingAmount = 0;
                    var grossAmount = 0;
                    var totalDiscountAmount = 0;
                    var totalNetAmount = 0;
                    var taxAmount = 0;
                    var totalTaxAmount = 0;
                    var discountResult = [];
                    var promoDiscountAmount = 0;
                    var accountDiscountAmount = 0;
                    var volumeDiscountAmount = 0;
                    var bundleDiscountAmount = 0;
                    var shippingDiscountAmount = 0.00;
                    response.rateDetails = response.rateQuoteDetails;
                    if (typeof (response) != "undefined" && typeof (response.rateDetails) != "undefined") {
                        response.rateDetails.forEach((rateDetail) => {
                            if (rateDetail.taxAmount != undefined) {
                                totalTaxAmount += rateDetail.taxAmount;
                            }
                            if (window.checkoutConfig.hco_price_update && typeof rateDetail.productLines != "undefined") {
                                var productLines = rateDetail.productLines;
                                productLines.forEach((productLine) => {
                                    var instanceId = productLine.instanceId;
                                    var itemRowPrice = productLine.productRetailPrice;
                                    itemRowPrice = priceFormatWithCurrency(itemRowPrice);
                                    $(".subtotal." + instanceId + " .cart-price .price").html(itemRowPrice);
                                    $(".subtotal-instance").show();
                                    $(".checkout-normal-price").hide();
                                })
                            }
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
                                totalNetAmount += rateResponseHandler.getTotalAmount(rateDetail, totalNetAmount);
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

                    if (shippingDiscountAmount > 0 && hasFreeShipping) {
                        removeCoupon = true;
                        $('#coupon_code').val('');
                        if(window.e383157Toggle){
                            fxoStorage.set('coupon_code','');
                        }else{
                            localStorage.setItem('coupon_code','');
                        }
                        $('#coupon_code').parent().addClass('coupon-code-error');
                        setTimeout(function () {
                            jQuery('.coupon-code-error').removeClass('coupon-code-error');
                        }, 6000);
                    }

                    totalNetAmount = priceFormatWithCurrency(totalNetAmount);
                    grossAmount = priceFormatWithCurrency(grossAmount);
                    taxAmount = priceFormatWithCurrency(totalTaxAmount);
                    if(window.e383157Toggle){
                        fxoStorage.set("TaxAmount", taxAmount);
                        fxoStorage.set("EstimatedTotal", totalNetAmount);
                    }else{
                        localStorage.setItem("TaxAmount", taxAmount);
                        localStorage.setItem("EstimatedTotal", totalNetAmount);
                    }
                    $(".grand.totals.incl .price").text(totalNetAmount);
                    $(".grand.totals .amount .price").text(totalNetAmount);
                    $(".totals.sub .amount .price").text(grossAmount);

                    if(totalDiscountAmount){
                        totalDiscountAmount = priceFormatWithCurrency(totalDiscountAmount);
                        $(".totals.discount.excl .amount .price").text('-'+totalDiscountAmount);
                        $(".totals.fedexDiscount .amount .price").text('-'+totalDiscountAmount);
                    } else {
                        $(".totals.fedexDiscount .amount .price").text('-');
                        $(".totals.discount.excl .amount .price").text('-');
                    }
                    $(".totals-tax .price").text(taxAmount);
                    $(".opc-block-summary .table-totals").show();

                    let promoDiscountHtml = '';

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
                            promoDiscountHtml = '<tr class="'+amount.type+' discount"><th class="mark" scope="row">'+amount.label+'</th><td class="amount"><span class="price">-'+ priceFormatWithCurrency(amount.price); +'</span></td></tr>';
                                $(".discount_breakdown tbody").append(promoDiscountHtml);
                                if($('.toggle-discount th #discbreak').length == 0){
                                    $('.toggle-discount th').append('<span id="discbreak" tabindex="0" class="arrow down"></span>');
                                }
                            } else {
                                $(".discount_breakdown tbody tr."+amount.type).remove();
                            }
                        });

                    if (removeCoupon && hasDiscountCombined === true) {
                        return false;
                    }

                    if (event.target.id == 'removed_promo') {
                        self.isCouponCode(false);
                        self.couponCode('');
                        //new code
                        if (stepNavigator.getActiveItemIndex() == 2) {
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
                        }
                    } else {
                        self.isCouponCode(true);
                        self.couponCode(couponCode);
                    }
                    self.isHideApplyField(false);
                }
            }
            ).fail(
                function (response) {
                }
            );
        },

        /**
         * Check and Get coupon alert
         *
         * @return {*}
         */
        getCouponAlertMsg: function (response) {
            let alertMsg = '';
            for (let i = 0; i < response.alerts.length; i++) {
                let alert = response.alerts[i];
                if (alert.code == 'COUPONS.CODE.INVALID') {
                    alertMsg = 'Promo code invalid. Please try again.';
                } else if (alert.code == 'MINIMUM.PURCHASE.REQUIRED') {
                    alertMsg = 'Minimum purchase amount not met.';
                } else if (alert.code == 'INVALID.PRODUCT.CODE') {
                    alertMsg = alert.message;
                } else if (alert.code == 'COUPONS.CODE.EXPIRED') {
                    alertMsg = 'Promo code has expired. Please try again.';
                } else if (alert.code == 'COUPONS.CODE.REDEEMED') {
                    alertMsg = 'Promo code has already been redeemed.';
                }
                if (alertMsg) {
                    break;
                }
            }
            return alertMsg;
        },

        /**
         * Is promo discount enabled
         *
         * @return {boolean}
         */
         isPromoDiscountEnabled: function () {
            let isPromoDiscountEnabled = typeof (window.checkoutConfig.promo_discount_enabled) != 'undefined' &&
            window.checkoutConfig.promo_discount_enabled != null ? window.checkoutConfig.promo_discount_enabled : false;

            return isPromoDiscountEnabled;
        },

        shouldShowPromoCodeField: function () {
                return !window.checkoutConfig.is_commercial || (window.checkoutConfig.is_selfreg_customer && this.isPromoDiscountEnabled());
        },
    });
});
