/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/shipping-save-processor/payload-extender',
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/step-navigator',
    'gdlEvent',
    'shippingModal',
    'checkout-common',
    "Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper",
    "marketplace-delivery-toast-messages",
    "fedex/storage",
    'Fedex_Delivery/js/model/campaign-ad-disclosure'
], function (
    ko,
    quote,
    resourceUrlManager,
    storage,
    paymentService,
    methodConverter,
    errorProcessor,
    fullScreenLoader,
    selectBillingAddressAction,
    payloadExtender,
    $,
    urlBuilder,
    customerData,
    customer,
    stepNavigator,
    gdlEvent,
    shippingModal,
    checkoutCommon,
    marketplaceQuoteHelper,
    marketplaceDeliveryToast,
    fxoStorage,
    campaignAdDisclosureModel
) {
    'use strict';

    /**
     * Checks if current user is FCL or not
     */
    let isLoggedIn = window.checkoutConfig.is_logged_in;
    let isSdeStore = window.checkoutConfig.is_sde_store != undefined ? Boolean(window.checkoutConfig.is_sde_store) : false;

    return {

        isCustomerLoggedIn: customer.isLoggedIn(),

        chosenDeliveryMethod: window.e383157Toggle ?
            (fxoStorage.get('chosenDeliveryMethod') || 'shipping') :
            (localStorage.getItem('chosenDeliveryMethod') || 'shipping'),

        isMixedQuote: function() {
            return marketplaceQuoteHelper.isMixedQuote();
        },

        isFullMarketplace: function() {
            return marketplaceQuoteHelper. isFullMarketplace();
        },

        /**
         * @return {jQuery.Deferred}
         */
        saveShippingInformation: function () {
            let shippingAccountNumber = quote.shippingMethod()['fedexShipAccountNumber'];
            if ((isSdeStore === true) && quote.shippingMethod()['method_code'] != 'LOCAL_DELIVERY') {
                if (shippingAccountNumber.length == 0) {
                    $("#fedExAccountNumber_validate").html('Fedex account number is required.');
                    $(".loading-mask").hide();
                    $('html, body').animate({
                        scrollTop: $(".fedex_account_number-field").offset().top - 50
                    }, 500);
                }
            }

            $(".create_quote").prop("disabled", true);

            if (!quote.billingAddress() && quote.shippingAddress().canUseForBilling()) {
                selectBillingAddressAction(quote.shippingAddress());
            }

            let payload = null;
            payload = {
                addressInformation: {
                    'shipping_address': quote.shippingAddress(),
                    'billing_address': quote.billingAddress(),
                    'shipping_method_code': quote.shippingMethod()['method_code'],
                    'shipping_carrier_code': quote.shippingMethod()['carrier_code']
                }
            };

            payloadExtender(payload);

            //B-1126844 | update cart items price
            let rateApiResponse = $('#rateApiResponseShipment').val();

            let c_payload = {
                addressInformation: {
                    'shipping_address': quote.shippingAddress(),
                    'billing_address': quote.billingAddress(),
                    'shipping_method_code': quote.shippingMethod()['method_code'],
                    'shipping_carrier_code': quote.shippingMethod()['carrier_code'],
                    'shipping_detail': quote.shippingMethod()
                },
                rateapi_response: rateApiResponse //B-1126844 | update cart items price
            }
            c_payload.addressInformation.shipping_address.telephone = c_payload.addressInformation.shipping_address.telephone.replace(" ", "").replace("(", "").replace(")", "").replace("-", "");
            c_payload.addressInformation.billing_address.telephone = c_payload.addressInformation.billing_address.telephone.replace(" ", "").replace("(", "").replace(")", "").replace("-", "");

            if (c_payload.addressInformation.shipping_address["regionId"] !== 'undefined' && c_payload.addressInformation.shipping_address !== "") {
                var shippingRegionIdOpt = $('select[name="region_id"] option[value=\"'+c_payload.addressInformation.shipping_address["regionId"]+'\"]').data('title')
                if (shippingRegionIdOpt && c_payload.addressInformation.shipping_address["region"] != shippingRegionIdOpt) {
                    c_payload.addressInformation.shipping_address["region"] = shippingRegionIdOpt;
                }
                if (shippingRegionIdOpt && c_payload.addressInformation.shipping_address["regionCode"] != shippingRegionIdOpt) {
                    c_payload.addressInformation.shipping_address["regionCode"] = shippingRegionIdOpt;
                }
            }
            if (c_payload.addressInformation.billing_address["regionId"] !== 'undefined' && c_payload.addressInformation.billing_address !== "") {
                var billingRegionIdOpt = $('select[name="region_id"] option[value=\"'+c_payload.addressInformation.shipping_address["regionId"]+'\"]').data('title')
                if (billingRegionIdOpt && c_payload.addressInformation.billing_address["region"] != billingRegionIdOpt) {
                    c_payload.addressInformation.billing_address["region"] = billingRegionIdOpt;
                }
                if (billingRegionIdOpt && c_payload.addressInformation.billing_address["regionCode"] != billingRegionIdOpt) {
                    c_payload.addressInformation.billing_address["regionCode"] = billingRegionIdOpt;
                }
            }

            if(window.e383157Toggle){
                fxoStorage.set('shippingData',c_payload);
            }else{
                localStorage.setItem('shippingData', JSON.stringify(c_payload));
            }

            let shippingPostData = null;
            let deliveryData = null;

            let first_party_carrier_code = null;
            let first_party_method_code = null;
            let third_party_carrier_code = null;
            let third_party_method_code = null;


            if(campaignAdDisclosureModel.isCampaingAdDisclosureToggleEnable && campaignAdDisclosureModel.shouldSendPayloadOnSubmit() === true) {
                c_payload.political_campaign_disclosure = {
                    candidate_pac_ballot_issue: campaignAdDisclosureModel.candidatePacBallotIssue(),
                    election_date: campaignAdDisclosureModel.electionDate(),
                    sponsoring_committee: campaignAdDisclosureModel.sponsoringCommittee(),
                    address_street_lines: campaignAdDisclosureModel.addressLine1() + ' ' + campaignAdDisclosureModel.addressLine2(),
                    city: campaignAdDisclosureModel.city(),
                    zip_code: campaignAdDisclosureModel.zipCode(),
                    region_id: campaignAdDisclosureModel.state(),
                    email: customerData.get('checkout-data')()['shippingAddressFromData']?.custom_attributes?.email_id ||
                        fxoStorage.get('pickupData')?.contactInformation?.contact_email
                }
            }

            shippingPostData = JSON.stringify(c_payload).replaceAll('&', encodeURIComponent('&'));

            window.dispatchEvent(new Event('toast_messages'));

            if (this.isMixedQuote()) {
                let selectedShippingMethods;
                if (window.e383157Toggle) {
                    fxoStorage.set('shippingPostData', shippingPostData);
                    selectedShippingMethods = fxoStorage.get('selectedShippingMethods');
                } else {
                    localStorage.setItem('shippingPostData', shippingPostData);
                    selectedShippingMethods = JSON.parse(localStorage.getItem('selectedShippingMethods'));
                }
                if (selectedShippingMethods !== null) {
                    const firstPartyMethod = selectedShippingMethods.find(method => method.carrier_code === 'fedexshipping');
                    const thirdPartyMethod = selectedShippingMethods.find(method => method.carrier_code !== 'fedexshipping');

                    if (this.chosenDeliveryMethod === 'shipping' && firstPartyMethod) {
                        first_party_carrier_code = firstPartyMethod.carrier_code;
                        first_party_method_code = firstPartyMethod.method_code;
                    }

                    third_party_method_code = thirdPartyMethod.method_code;
                    third_party_carrier_code = thirdPartyMethod.carrier_code;

                    shippingPostData = JSON.parse(shippingPostData);

                    deliveryData = {
                        ...shippingPostData,
                        first_party_carrier_code,
                        first_party_method_code,
                        third_party_carrier_code,
                        third_party_method_code,
                        firstPartyMethod,
                        thirdPartyMethod
                    };
                }
            }

            if (deliveryData === null) {
                deliveryData = shippingPostData;
            }

            const regionCode = c_payload?.addressInformation?.shipping_address?.regionCode;
            if (regionCode) {
                if (window.e383157Toggle) {
                    fxoStorage.set("stateOrProvinceCode", regionCode);
                } else {
                    localStorage.setItem("stateOrProvinceCode", regionCode);
                }
            }
            deliveryData = typeof deliveryData === 'string' ? deliveryData : JSON.stringify(deliveryData);

            return $.ajax({
                url: urlBuilder.build('delivery/quote/create'),
                type: 'POST',
                data: 'data=' + deliveryData,
                dataType: "json",
                showLoader: false,
                async: true,
                complete: function () {
                }
            }).done(function (resData) {
                $('.message-container').parent().removeClass('error-modal');
                if (isLoggedIn) {
                    $(".create_quote").prop("disabled", true);
                    if (resData.error == "1") {
                        $(".create_quote").prop("disabled", false);
                        $('.error-container').removeClass('api-error-hide');

                        document.querySelector('.error-container').scrollIntoView({
                            behavior: 'smooth'
                        });
                        return true;
                    } else if (resData.url.length > 0 && resData.url != '') {
                        let estimatedTotal;
                        if(window.e383157Toggle){
                            estimatedTotal = fxoStorage.get("EstimatedTotal");
                        }else{
                            estimatedTotal = localStorage.getItem("EstimatedTotal");
                        }
                        if(estimatedTotal){
                            let orderSalePrice = estimatedTotal.replace("$","");
                            gdlEvent.appendGDLScript(orderSalePrice);
                        }
                        if(window.e383157Toggle){
                            fxoStorage.set("stateOrProvinceCode", resData.stateOrProvinceCode);
                        }else{
                            localStorage.setItem("stateOrProvinceCode", resData.stateOrProvinceCode);
                        }
                        let sections = ['cart'];
                        customerData.invalidate(sections);
                        customerData.invalidate(['customer']);
                        let cxmlResponse = atob(resData.notification);
                        if (cxmlResponse.startsWith("<html", 0)) {
                            cxmlResponse = cxmlResponse.replace('<form', '<form class="cxmlResponseForm"');
                            document.body.innerHTML = cxmlResponse;
                            $(".cxmlResponseForm").submit();
                        } else {
                        campaignAdDisclosureModel.clearStorage();
                            window.location.href = resData.url;
                            return false;
                        }
                    } else {
                        errorProcessor.process('Unable to process your request');
                        window.reload();
                    }
                } else {
                    $(".create_quote").prop("disabled", false);
                    if(window.e383157Toggle){
                        fxoStorage.set("stateOrProvinceCode", resData.stateOrProvinceCode);
                    }else{
                        localStorage.setItem("stateOrProvinceCode", resData.stateOrProvinceCode);
                    }
                }
            }).fail(
                function (response) {
                    console.log(response);
                    errorProcessor.process('Unable to process your request');
                    return false;
                }
            );
        }
    };
});
