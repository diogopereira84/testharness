define([
    'jquery',
    'ko',
    'checkout-common',
    'Magento_Checkout/js/model/step-navigator',
    'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    "mage/translate",
    'fedex/storage'
], function ($, ko, checkoutCommon, stepNavigator, quoteHelper, quote, registry, $t,fxoStorage) {
    'use strict';

    return function (payment) {
        return payment.extend({
            defaults: {
                template: 'Fedex_MarketplaceCheckout/checkout/submitStep/payment',
                activeMethod: ''
            },

            saveShippingInformationInStorePickupShippingCombo: function () {
                let rateApiResponse = $('#rateApiResponseShipment').val();
                let shipping_address = null;
                let billing_address = null;
                let shipping_method_code = null;
                let shipping_carrier_code = null;
                let shipping_detail = null;

                if (quote.shippingMethod()) {
                    shipping_method_code = quote.shippingMethod()['method_code'];
                    shipping_carrier_code = quote.shippingMethod()['carrier_code'];
                    shipping_detail = quote.shippingMethod();
                }

                if (quote.shippingAddress()) {
                    shipping_address = quote.shippingAddress();
                }

                if (quote.billingAddress()) {
                    billing_address = quote.billingAddress();
                }

                let c_payload = {
                    addressInformation: {
                        'shipping_address': shipping_address,
                        'billing_address': billing_address,
                        'shipping_method_code': shipping_method_code,
                        'shipping_carrier_code': shipping_carrier_code,
                        'shipping_detail': shipping_detail
                    },

                    rateapi_response: rateApiResponse //B-1126844 | update cart items price
                }

                if(window.e383157Toggle){
                    fxoStorage.set('shippingData',c_payload);
                }else{
                    localStorage.setItem('shippingData', JSON.stringify(c_payload));
                }

            },

            isMixedQuote: function () {
                if (quoteHelper.isMixedQuote()) return true;
                else return false;
            },

            isFullMarketplaceQuote: function () {
                return quoteHelper.isFullMarketplaceQuote();
            },

            navigate: function (step) {
                if (window.e383157Toggle) {
                    if (fxoStorage.get("shipkey") === "true") {
                        this.isDelivery(true);
                    } else {
                        this.isDelivery(false);
                    }
                } else {
                    if (localStorage.getItem("shipkey") === "true") {
                        this.isDelivery(true);
                    } else {
                        this.isDelivery(false);
                    }
                }
                this.paymentStepData(null);
                let pickupShippingComboKey;
                if(window.e383157Toggle){
                    pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
                }else{
                    pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
                }
                if (this.isMixedQuote() && pickupShippingComboKey === 'true') {
                    this.saveShippingInformationInStorePickupShippingCombo();
                    this.setDeliveryData();
                    this.setPickupData();
                } else if (this.isDelivery()) {
                    this.setDeliveryData();
                } else {
                    this.setPickupData();
                }

                this.setContactInformation();

                let paymentData;
                if(window.e383157Toggle){
                    paymentData = fxoStorage.get("paymentData");
                }else{
                    paymentData = JSON.parse(localStorage.getItem('paymentData'));
                }

                if (paymentData !== null) {
                    this.paymentMethodStep();
                    step && step.isVisible(true);
                } else {
                    this.paymentStepData(null);
                }
            },

            editContactInformation: ko.observable('shipping'),

            contactInformation: ko.observable({
                firstName: '',
                lastName: '',
                email: '',
                telephone: '',
            }),

            setContactInformation: function () {
                const shippingData = this.shippingAddress();
                const pickupData = this.pickupContactInformation();

                let contactInformation = {
                    firstName: '',
                    lastName: '',
                    email: '',
                    telephone: '',
                };

                if (pickupData) {
                    const {
                        contact_fname,
                        contact_lname,
                        contact_email,
                        contact_number_pickup
                    } = pickupData;

                    contactInformation = {
                        firstName: contact_fname,
                        lastName: contact_lname,
                        email: contact_email,
                        telephone: contact_number_pickup
                    };

                    this.editContactInformation('pickup');

                    this.contactInformation(pickupData);
                    return;
                }

                if (shippingData) {
                    const {firstname, lastname, telephone} = shippingData;

                    const email = shippingData
                        .customAttributes
                        .find(attribute => attribute.attribute_code === 'email_id')
                        .value;

                    contactInformation = {
                        firstName: firstname,
                        lastName: lastname,
                        telephone: telephone,
                        email: email
                    }

                    this.editContactInformation('shipping');

                    this.contactInformation(shippingData);
                    return;
                }

                this.contactInformation(contactInformation);
                return;
            },

            onEditContactInformation: function () {
                if (this.editContactInformation() === 'shipping') {
                    this.onEditShipping();
                    return;
                }
                ;

                this.onEditPickupContact();
            },

            goToPickupBreadcrumb: function () {
                let shippingComponent = registry.get('checkout.steps.shipping-step.shippingAddress');

                stepNavigator.navigateTo('shipping', 'opc-shipping_method');

                shippingComponent.showShippingContent(false);
                shippingComponent.onclickTriggerShipShow(false);
                shippingComponent.onclickTriggerPickupShow(true);
                shippingComponent.showPickupContent(true);
                shippingComponent.checkoutTitle($t('In-store pickup'))

                let isExpress;
                if(window.e383157Toggle){
                    isExpress = fxoStorage.get('express-checkout');
                }else{
                    isExpress = localStorage.getItem('express-checkout');
                }
                if (this.isMixedQuote() || !isExpress || isExpress === 'false') {
                    $(".place-pickup-order").show();
                }

            },

            /**
             * Return true if non pricable item added in cart
             *
             * @return Bool
             */
             isCheckoutQuotePriceDashable: function () {
                return typeof (window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && window.checkoutConfig.is_quote_price_is_dashable != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
            },

            paymentMethodStep: function () {
                this._super();

                // make sure to update customer information
                this.setContactInformation();
            },
        })
    }
});
