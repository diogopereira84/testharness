/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'mage/translate',
    "mage/url",
    "checkoutAdditionalScript",
    "shippingFormAdditionalScript",
    'Fedex_ExpressCheckout/js/fcl-profile-pickup-edit',
    'checkout-common',
    'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
    'Fedex_ExpressCheckout/js/fcl-profile-session',
    'Fedex_ExpressCheckout/js/express-checkout-pickup',
    'fedex/storage',
    'uiRegistry'
], function (
    $,
    _,
    Component,
    ko,
    quote,
    stepNavigator,
    paymentService,
    methodConverter,
    getPaymentInformation,
    checkoutDataResolver,
    $t,
    urlBuilder,
    checkoutAdditionalScript,
    shippingFormAdditionalScript,
    profilePickEditBuilder,
    marketplaceCheckoutCommon,
    marketplaceQuoteHelper,
    profileSessionBuilder,
    expressCheckoutPickup,
    fxoStorage,
    registry
) {
    'use strict';

    /** Set payment methods to collection */
    paymentService.setPaymentMethods(methodConverter(window.checkoutConfig.paymentMethods));
    var isSdeStore = shippingFormAdditionalScript.isSdeStore();
    let isSelfregCustomer = window.checkoutConfig.is_selfreg_customer;

    /**
     * Checks if current logged in user is FCL or not
     */
    let isFclCustomer = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;

    let explorersD193256Fix = false;

    if (isFclCustomer) {
        explorersD193256Fix = typeof (window.checkoutConfig.explorers_d_193256_fix) !== "undefined" && window.checkoutConfig.explorers_d_193256_fix !== null ? window.checkoutConfig.explorers_d_193256_fix : false;
        isFclCustomer = typeof (window.checkoutConfig.is_fcl_customer) !== "undefined" && window.checkoutConfig.is_fcl_customer !== null ? window.checkoutConfig.is_fcl_customer : false;
    }

    return Component.extend({
        defaults: {
            template: 'Fedex_Pay/payment',
            activeMethod: ''
        },

        isVisible: ko.observable(quote.isVirtual()),
        quoteIsVirtual: quote.isVirtual(),
        isPaymentMethodsAvailable: ko.computed(function () {
            return paymentService.getAvailablePaymentMethods().length > 0;
        }),
        isDelivery: ko.observable(false),
        isCreditCardSelected: ko.observable(false),
        isBillingAddress: ko.observable(false),
        isFedexAccountApplied: ko.observable(false),
        paymentStepData: ko.observable(null),
        creditCardNumber: ko.observable(''),
        fedexAccountNumber: ko.observable(''),
        shippingAddress: ko.observable(null),
        shippingMethod: ko.observable(null),
        stateOrProvinceCode: ko.observable(null),
        pickupAddressInformation: ko.observable(null),
        pickupContactInformation: ko.observable(null),
        hcoLocationSelect: ko.observable(null),
        pickupShipMethod: ko.observable(null),
        pickupDateTime: ko.observable(''),
        greenCheckUrl: ko.observable(window.checkoutConfig.media_url + "/checkgreen.png"),
        cardIcon: ko.observable('fa fa-credit-card'),
        crossUrl: ko.observable(window.checkoutConfig.media_url + "/circle-times.png"),
        crossIcon: ko.observable(window.checkoutConfig.media_url + "/close-button.png"),
        customBillingInvoiced: ko.observableArray(window.checkoutConfig.custom_billing_invoiced),
        customBillingCreditCard: ko.observableArray(window.checkoutConfig.custom_billing_credit_card),
        chosenDeliveryMethod: ko.observable(window.e383157Toggle ? fxoStorage.get('chosenDeliveryMethod')
            : localStorage.getItem('chosenDeliveryMethod')),
        pickupShippingComboKey: ko.observable(window.e383157Toggle ? fxoStorage.get('pickupShippingComboKey')
            : localStorage.getItem('pickupShippingComboKey')),
        isExpectedDeliveryDateEnabled: ko.observable(window?.checkoutConfig?.isExpectedDeliveryDateEnabled),
        onlyNonCustomizableCart: ko.observable(window?.checkoutConfig?.isEssendantEnabled && window?.checkoutConfig?.onlyNonCustomizableCart),
        cancellationMessage: ko.observable(window?.checkoutConfig?.reviewSubmitCancellationMessage),
        isAlternateFlag: ko.observable(false),
        isXmenD177346Fix: ko.observable(window?.checkoutConfig?.xmen_D177346_fix),
        /** @inheritdoc */
        initialize: function () {
            this._super();
            var self = this;
            checkoutDataResolver.resolvePaymentMethod();

            const stepLabel = $t('Review & Submit');

            stepNavigator.registerStep(
                'payment',
                null,
                stepLabel,
                this.isVisible,
                _.bind(this.navigate, this),
                this.sortOrder
            );
            let isShipKey, expressCheckout;
            if (window.e383157Toggle) {
                isShipKey = fxoStorage.get("shipkey") === "true";
                expressCheckout = fxoStorage.get('express-checkout');
            } else {
                isShipKey = localStorage.getItem("shipkey") === "true";
                expressCheckout = localStorage.getItem('express-checkout');
            }
            self.isVisible.subscribe(function (visibleFlag) {
                if (visibleFlag && window.FDXPAGEID) {
                    let gdlPageId = window.FDXPAGEID + '/payment';
                    window.FDX.GDL.push(['event:publish', ['page', 'pageinfo', {
                        pageId: gdlPageId
                    }]]);
                }
                if (visibleFlag && isSdeStore || isSelfregCustomer || expressCheckout) {
                    // Make sure to read shipkey from storage
                    isShipKey = window.e383157Toggle ? fxoStorage.get("shipkey") === "true" : localStorage.getItem("shipkey") === "true";
                    if (isShipKey || isSdeStore) {
                        self.isDelivery(true);
                        if (window.e383157Toggle) {
                            fxoStorage.set('shipkey', 'true');
                            fxoStorage.set('pickupkey', 'false');
                        } else {
                            window.localStorage.setItem('shipkey', true);
                            window.localStorage.setItem('pickupkey', false);
                        }
                        $('.checkout-breadcrumb .go-to-pickup').css('display', 'none');
                        $('.pickup-title-checkout .edit_pickup').show();
                        $('.pickup-title-checkout .edit_ship ').css('display', 'none');
                        $('.checkout-breadcrumb .go-to-ship').show();
                    } else {
                        self.isDelivery(false);
                        if (window.e383157Toggle) {
                            fxoStorage.set('shipkey', 'false');
                            fxoStorage.set('pickupkey', 'true');
                        } else {
                            window.localStorage.setItem('shipkey', false);
                            window.localStorage.setItem('pickupkey', true);
                        }
                        $('.pickup-title-checkout .edit_ship ').show();
                        $('.checkout-breadcrumb .go-to-ship').css('display', 'none');
                        $('.checkout-breadcrumb .go-to-pickup').show();
                        $('.pickup-title-checkout .edit_pickup').css('display', 'none');
                    }
                    self.paymentStepData(null);
                    if (self.isDelivery()) {
                        self.setDeliveryData();
                    } else {
                        self.setPickupData();
                    }
                    let paymentData;
                    if(window.e383157Toggle){
                        paymentData = fxoStorage.get("paymentData");
                    }else{
                        paymentData = JSON.parse(localStorage.getItem('paymentData'));
                    }
                    if (paymentData !== null) {
                        self.paymentMethodStep();
                    } else {
                        self.paymentStepData(null);
                    }
                }
            });
            window.addEventListener('on_change_delivery_method', () => {
                let chosenDeliveryMethod;
                if (window.e383157Toggle) {
                    chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                } else {
                    chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                }
                self.chosenDeliveryMethod(chosenDeliveryMethod);
            });
            // Handles the Express Checkout 1P pickup
            window.addEventListener('express_checkout_full_1p_pickup', () => {
                let chosenDeliveryMethod, expressCheckout, skipDelivery;
                if (window.e383157Toggle) {
                    chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                    expressCheckout = fxoStorage.get('express-checkout');
                    skipDelivery = fxoStorage.get("skipDelivery");
                } else {
                    chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                    expressCheckout = localStorage.getItem('express-checkout');
                    skipDelivery = localStorage.getItem("skipDelivery");
                }
                if (chosenDeliveryMethod === 'pick-up'
                    && expressCheckout
                    && skipDelivery === 'true'
                ) {
                    let pickupTime, pickupInfo;
                    if (window.e383157Toggle) {
                        pickupInfo = fxoStorage.get('pickupData');
                        pickupTime = fxoStorage.get('pickupDateTime');
                    } else {
                        pickupInfo = JSON.parse(localStorage.getItem('pickupData'));
                        pickupTime = localStorage.getItem('pickupDateTime');
                    }
                    this.pickupContactInformation(pickupInfo?.contactInformation);
                    this.pickupAddressInformation(pickupInfo?.addressInformation);
                    this.pickupShipMethod(pickupInfo?.addressInformation?.shipping_detail);
                    let pickupLocationSelect;
                    if (window.e383157Toggle) {
                        pickupLocationSelect = fxoStorage.get("pickup_hco_location_select");
                    } else {
                        pickupLocationSelect = localStorage.getItem("pickup_hco_location_select");
                    }
                    this.hcoLocationSelect(pickupLocationSelect);
                    this.pickupDateTime(pickupTime);
                }
            });

            setTimeout(() => {
                $(document).ready(function () {
                    let isShipKey;
                    if (window.e383157Toggle) {
                        isShipKey = fxoStorage.get("shipkey") === "true";
                    } else {
                        isShipKey = localStorage.getItem("shipkey") === "true";
                    }
                    if (isShipKey || isSdeStore) {
                        if (window.e383157Toggle) {
                            fxoStorage.set('shipkey', 'true');
                            fxoStorage.set('pickupkey', 'false');
                        } else {
                            window.localStorage.setItem('shipkey', true);
                            window.localStorage.setItem('pickupkey', false);
                        }
                        $('.checkout-breadcrumb .go-to-pickup').css('display', 'none');
                        $('.pickup-title-checkout .edit_pickup').show();
                        $('.pickup-title-checkout .edit_ship ').css('display', 'none');
                        $('.checkout-breadcrumb .go-to-ship').show();
                    } else {
                        if (window.e383157Toggle) {
                            fxoStorage.set('shipkey', 'false');
                            fxoStorage.set('pickupkey', 'true');
                        } else {
                            window.localStorage.setItem('shipkey', false);
                            window.localStorage.setItem('pickupkey', true);
                        }
                        $('.pickup-title-checkout .edit_ship ').show();
                        $('.checkout-breadcrumb .go-to-ship').css('display', 'none');
                        $('.checkout-breadcrumb .go-to-pickup').show();
                        $('.pickup-title-checkout .edit_pickup').css('display', 'none');
                    }
                });
            }, 3000);

            /**
             * Get if ship or pickup selected
             */
            let isShip;
            if (window.e383157Toggle) {
                isShip = fxoStorage.get("shipkey") === 'true';
            } else {
                isShip = localStorage.getItem("shipkey") === "true";
            }
            if (isShip) {
                this.isDelivery(true);
            } else {
                this.isDelivery(false);
            }

            return this;
        }
        ,

        /**
         * Navigate method.
         */
        navigate: function (step) {
            let isShipKey;
            if (window.e383157Toggle) {
                isShipKey = fxoStorage.get("shipkey") === "true";
            } else {
                isShipKey = localStorage.getItem("shipkey") === "true";
            }
            if (isShipKey) {
                this.isDelivery(true);
            } else {
                this.isDelivery(false);
            }
            this.paymentStepData(null);
            if (this.isDelivery()) {
                this.setDeliveryData();
            } else {
                this.setPickupData();
            }
            let paymentData
            if (window.e383157Toggle) {
                paymentData = fxoStorage.get("paymentData");
            } else {
                paymentData = JSON.parse(localStorage.getItem('paymentData'));
            }
            if (paymentData !== null) {
                this.paymentMethodStep();
                step && step.isVisible(true);
            } else {
                this.paymentStepData(null);
            }
        },

        /**
         * Set Checkout Delivery Flow
         */
        setDeliveryData: function () {

            let ShippingInfo, StateOrProvinceCodeInfo;
            if (window.e383157Toggle) {
                ShippingInfo = fxoStorage.get("shippingData");
                StateOrProvinceCodeInfo = fxoStorage.get('stateOrProvinceCode');
                this.checkAlternateFlag();
            } else {
                ShippingInfo = JSON.parse(localStorage.getItem('shippingData'));
                StateOrProvinceCodeInfo = localStorage.getItem('stateOrProvinceCode');
                this.checkAlternateFlag();
            }

            if (ShippingInfo !== null) {
                if (ShippingInfo.addressInformation.shipping_address.customAttributes[0].attribute_code == 'ext') {
                    let extAttributCode = ShippingInfo.addressInformation.shipping_address.customAttributes[0].attribute_code;
                    let extAttributValue = ShippingInfo.addressInformation.shipping_address.customAttributes[0].value;
                    ShippingInfo.addressInformation.shipping_address.customAttributes[0].attribute_code = ShippingInfo.addressInformation.shipping_address.customAttributes[1].attribute_code;
                    ShippingInfo.addressInformation.shipping_address.customAttributes[0].value = ShippingInfo.addressInformation.shipping_address.customAttributes[1].value;
                    ShippingInfo.addressInformation.shipping_address.customAttributes[1].attribute_code = extAttributCode;
                    ShippingInfo.addressInformation.shipping_address.customAttributes[1].value = extAttributValue;
                }
                let pickupShippingComboKey;
                if (window.e383157Toggle) {
                    pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
                } else {
                    pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
                }
                if (!(pickupShippingComboKey === 'true')) {
                    let shippingAddress = quote.shippingAddress();

                    ShippingInfo.addressInformation.shipping_address.contact_fname = shippingAddress.firstname;
                    ShippingInfo.addressInformation.shipping_address.contact_lname = shippingAddress.lastname;
                    ShippingInfo.addressInformation.shipping_address.contact_number = shippingAddress.telephone ?
                        shippingAddress.telephone.replace(" ", "").replace("(", "").replace(")", "").replace("-", '') : '';

                    let contact_email = _.find(shippingAddress.customAttributes, function (attr) {
                        return attr.attribute_code == "email_id";
                    });
                    ShippingInfo.addressInformation.shipping_address.contact_email = contact_email ? contact_email.value : '';

                    let contact_ext = _.find(shippingAddress.customAttributes, function (attr) {
                        return attr.attribute_code == "ext";
                    });
                    ShippingInfo.addressInformation.shipping_address.contact_ext = contact_ext ? contact_ext.value : '';
                    if (explorersD193256Fix && ShippingInfo.addressInformation.shipping_address.is_alternate === true) {
                        ShippingInfo.addressInformation.shipping_address.isAlternatePerson = true;
                        this.isAlternateFlag(true);
                        if(ShippingInfo.addressInformation.shipping_address.altFirstName){
                            ShippingInfo.addressInformation.shipping_address.alternate_fname = ShippingInfo.addressInformation.shipping_address.altFirstName;
                        } else {
                            ShippingInfo.addressInformation.shipping_address.alternate_fname = '';
                        }
                        if(ShippingInfo.addressInformation.shipping_address.altLastName){
                            ShippingInfo.addressInformation.shipping_address.alternate_lname = ShippingInfo.addressInformation.shipping_address.altLastName;
                        } else {
                            ShippingInfo.addressInformation.shipping_address.alternate_lname = '';
                        }
                        if(ShippingInfo.addressInformation.shipping_address.altPhoneNumber){
                            ShippingInfo.addressInformation.shipping_address.alternate_number = ShippingInfo.addressInformation.shipping_address.altPhoneNumber;
                        } else {
                            ShippingInfo.addressInformation.shipping_address.alternate_number = '';
                        }
                        if(ShippingInfo.addressInformation.shipping_address.altPhoneNumberext){
                            ShippingInfo.addressInformation.shipping_address.alternate_ext = ShippingInfo.addressInformation.shipping_address.altPhoneNumberext;
                        } else {
                            ShippingInfo.addressInformation.shipping_address.alternate_ext = '';
                        }
                        if(ShippingInfo.addressInformation.shipping_address.altEmail){
                            ShippingInfo.addressInformation.shipping_address.alternate_email = ShippingInfo.addressInformation.shipping_address.altEmail;
                        } else {
                            ShippingInfo.addressInformation.shipping_address.alternate_email = '';
                        }
                        let AltContactInfo = {
                            alternate_fname: ShippingInfo.addressInformation.shipping_address.alternate_fname,
                            alternate_lname: ShippingInfo.addressInformation.shipping_address.alternate_lname,
                            alternate_email: ShippingInfo.addressInformation.shipping_address.alternate_email,
                            alternate_number: ShippingInfo.addressInformation.shipping_address.alternate_number,
                            alternate_ext: ShippingInfo.addressInformation.shipping_address.alternate_ext,
                            isAlternatePerson: true,
                        };
                        const altContactInfoJSON = JSON.stringify(AltContactInfo);
                        if (window.e383157Toggle) {
                            fxoStorage.set('altContactInfo', altContactInfoJSON);
                        } else {
                            localStorage.setItem('altContactInfo', altContactInfoJSON);
                        }
                    } else {
                        ShippingInfo.addressInformation.shipping_address.alternate_fname = $("#alternate_firstname").val();
                        ShippingInfo.addressInformation.shipping_address.alternate_lname = $("#alternate_lastname").val();
                        ShippingInfo.addressInformation.shipping_address.alternate_number = $("#alternate_phonenumber").val() ? $("#alternate_phonenumber").val().replace(" ", "").replace("(", "").replace(")", "").replace("-", '') : '';
                        ShippingInfo.addressInformation.shipping_address.alternate_email = $("#alternate_email").val();
                        ShippingInfo.addressInformation.shipping_address.alternate_ext = $("#alternate_ext").val();

                        if ($("#alternate_firstname").val()) {
                            ShippingInfo.addressInformation.shipping_address.isAlternatePerson = true;
                        }
                    }
                }

                // Make sure there is ext property in shipping info.
                let extAttributeObject =
                    ShippingInfo
                        .addressInformation
                        .shipping_address
                        .customAttributes
                        .find(attribute => attribute.attribute_code === 'ext')
                    || {attribute_code: 'no_ext', value: ''};

                ShippingInfo.addressInformation.shipping_address.ext_attribute = extAttributeObject;

                this.shippingAddress(ShippingInfo.addressInformation.shipping_address);
                this.shippingMethod(ShippingInfo.addressInformation.shipping_detail);
            }
            if (StateOrProvinceCodeInfo != null) {
                this.stateOrProvinceCode(StateOrProvinceCodeInfo);
            }
            let pickupShippingComboKey;
            if (window.e383157Toggle) {
                pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
            } else {
                pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
            }
            this.pickupShippingComboKey(pickupShippingComboKey === 'true');
        }
        ,

        /**
         * Set Checkout Pickup Flow
         */

        setPickupData: function () {

            let pickupInfo;
            let pickupShippingComboKey;
            if (window.e383157Toggle) {
                pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
                pickupInfo = fxoStorage.get('pickupData');
                this.checkAlternateFlag();
            } else {
                pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
                pickupInfo = JSON.parse(localStorage.getItem('pickupData'));
                this.checkAlternateFlag();
            }
            if (typeof pickupShippingComboKey === "string") {
                pickupShippingComboKey = pickupShippingComboKey === "true";
            }
            let pickupTime, primaryPickUpData,expressCheckout;
            if (window.e383157Toggle) {
                primaryPickUpData = fxoStorage.get('primaryPickUpData');
                pickupTime = fxoStorage.get('pickupDateTime');
                expressCheckout = fxoStorage.get('express-checkout');

            } else {
                primaryPickUpData = JSON.parse(localStorage.getItem('primaryPickUpData'));
                pickupTime = localStorage.getItem('pickupDateTime');
                expressCheckout = localStorage.getItem('express-checkout');
            }
            if (pickupInfo !== null) {
                this.pickupAddressInformation(pickupInfo?.addressInformation);
                this.pickupContactInformation(pickupInfo?.contactInformation);
                this.pickupShipMethod(pickupInfo?.addressInformation?.shipping_detail);
                let pickupLocationSelect;
                if (window.e383157Toggle) {
                    pickupLocationSelect = fxoStorage.get("pickup_hco_location_select");
                } else {
                    pickupLocationSelect = localStorage.getItem("pickup_hco_location_select");
                }
                this.hcoLocationSelect(pickupLocationSelect);
                this.pickupDateTime(pickupTime);
            } else if (expressCheckout && primaryPickUpData) {
                let profileInfo = profileSessionBuilder.getProfileAddress();
                let preferredPickupData = this.prepareExpressCheckoutPreferredPickUpJsonData(profileInfo, primaryPickUpData);
                let contactInformation = preferredPickupData?.contactInformation;

                if (!preferredPickupData?.contactInformation?.isAlternatePerson) {
                    contactInformation.isAlternatePerson = pickupInfo.contactInformation.isAlternatePerson;

                    if (contactInformation?.isAlternatePerson) {
                        contactInformation.alternate_fname = pickupInfo.contactInformation.alternate_fname;
                        contactInformation.alternate_lname = pickupInfo.contactInformation.alternate_lname;
                        contactInformation.alternate_email = pickupInfo.contactInformation.alternate_email;
                        contactInformation.alternate_number = pickupInfo.contactInformation.alternate_number;
                        contactInformation.alternate_ext = pickupInfo.contactInformation.alternate_ext;
                    }
                }

                this.pickupAddressInformation(preferredPickupData.addressInformation);
                this.pickupContactInformation(contactInformation);
            }
            if (window.e383157Toggle) {
                pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
            } else {
                pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
            }
            if (pickupShippingComboKey === "false" && this.isMixedQuote()) {
                this.pickupAddressInformation(false);
                this.pickupContactInformation(false);
            }
            if (window.e383157Toggle) {
                pickupShippingComboKey = fxoStorage.get('pickupShippingComboKey');
            } else {
                pickupShippingComboKey = localStorage.getItem('pickupShippingComboKey');
            }
            this.pickupShippingComboKey(pickupShippingComboKey === 'true');
        }
        ,

        prepareExpressCheckoutPreferredPickUpJsonData: function (profileInfo, primaryPickUpData) {
            let pickUpJsonData = {
                contactInformation: {
                    contact_fname: profileInfo.firstName,
                    contact_lname: profileInfo.lastName,
                    contact_email: profileInfo.email,
                    contact_number: profileInfo.phoneNumber,
                    contact_number_pickup: profileInfo.phoneNumber + ' ',
                    contact_ext: "",
                    alternate_fname: "",
                    alternate_lname: "",
                    alternate_email: "",
                    alternate_number: "",
                    alternate_ext: "",
                    isAlternatePerson: false,
                },
                addressInformation: {
                    pickup_location_name: primaryPickUpData.location.name,
                    pickup_location_street: primaryPickUpData.location.address.streetLines[0],
                    pickup_location_city: primaryPickUpData.location.address.city,
                    pickup_location_state: primaryPickUpData.location.address.stateOrProvinceCode,
                    pickup_location_zipcode: primaryPickUpData.location.address.postalCode,
                    pickup_location_country: primaryPickUpData.location.address.countryCode,
                    pickup_location_date: primaryPickUpData.estimatedDeliveryLocalTime,
                    pickup: true,
                    shipping_address: "",
                    billing_address: "",
                    shipping_method_code: "PICKUP",
                    shipping_carrier_code: "fedexshipping",
                    shipping_detail: {
                        carrier_code: "fedexshipping",
                        method_code: "PICKUP",
                        carrier_title: "Fedex Store Pickup",
                        method_title: primaryPickUpData.location.id,
                        amount: 0,
                        base_amount: 0,
                        available: true,
                        error_message: "",
                        price_excl_tax: 0,
                        price_incl_tax: 0,
                    },
                },
                rateapi_response: $('#rateApiResponse').val(),
                orderNumber: null,
            }

            return pickUpJsonData;
        },

        /**
         * @returns Bool
         */
        isMixedQuote: function () {
            return marketplaceQuoteHelper.isMixedQuote();
        }
        ,


        /**
         * Get Payment Method Step Details
         */

        paymentMethodStep: function () {

            let paymentData
            if (window.e383157Toggle) {
                paymentData = fxoStorage.get("paymentData");
            } else {
                paymentData = JSON.parse(localStorage.getItem('paymentData'));
            }
            if (paymentData !== null) {
                if (paymentData.paymentMethod == 'cc') {
                    this.isCreditCardSelected(true);
                    this.isBillingAddress(paymentData.isBillingAddress);
                    this.creditCardNumber("*" + paymentData.number.substring(paymentData.number.length - 4));
                    this.isFedexAccountApplied(paymentData.isFedexAccountApplied);
                    if (paymentData.isFedexAccountApplied) {
                        this.fedexAccountNumber("*" + paymentData.fedexAccountNumber.substring(paymentData.fedexAccountNumber.length - 4));
                    }
                    this.cardIcon(paymentData.creditCardType);
                } else {
                    this.isCreditCardSelected(false);
                    this.fedexAccountNumber("Account ending in *" + paymentData.fedexAccountNumber.substring(paymentData.fedexAccountNumber.length - 4));
                }
            } else {
                this.paymentStepData(null);
            }

            let isCardwarningMessage;
            if (window.e383157Toggle) {
                isCardwarningMessage = fxoStorage.get("isCardwarningMessage");
            } else {
                isCardwarningMessage = localStorage.getItem("isCardwarningMessage");
            }

            if (isCardwarningMessage) {
                let infoIcon = window.checkoutConfig.media_url + "/information.png";
                let errorMsg = 'Your credit card could not be saved at this time, but you can continue checking out.';
                let msgHtml = '<div class="express-msg-outer-most-info"><div class="express-msg-outer-info-container"><div class="express-info-msg-container"><span class="icon-container"><img class="img-info-icon" alt="Check icon" src="' + infoIcon + '"></span><span class="message">' + errorMsg + '</span></div> </div></div>';
                $('.credit-card-info').html(msgHtml);
            }
            let isCardTokenExpaired;
            if (window.e383157Toggle) {
                isCardTokenExpaired = fxoStorage.get('isCardTokenExpaired');
            } else {
                isCardTokenExpaired = localStorage.getItem('isCardTokenExpaired');
            }
            if (isCardTokenExpaired == 'true') {
                let crossIcon = window.checkoutConfig.media_url + "/close-button.png";
                let circleIcon = window.checkoutConfig.media_url + "/circle-times.png";
                if (window.e383157Toggle) {
                    fxoStorage.set('isCardErrMessage', true);
                    fxoStorage.delete('isCardSuccMessage');
                } else {
                    localStorage.setItem('isCardErrMessage', true);
                    localStorage.removeItem('isCardSuccMessage');
                }
                let errorMsgHead = 'Review Payment Method.';
                let errorMsg = 'For security purposes, please review and re-enter your credit card details.';
                let msgHtml = '<div class="express-msg-outer-most-credit error-security"><div class="express-msg-outer-credit-container"><div class="express-error-msg-container"><span class="icon-container"><img class="img-check-icon" alt="Check icon" src="' + circleIcon + '"></span><span class="message heading">' + errorMsgHead + '</span><span class="message">' + errorMsg + '</span><img id="express_msg_close" class="img-close-msg" alt="close icon" src="' + crossIcon + '" tabindex="0"></div> </div></div>';
                $(msgHtml).insertAfter(".opc-progress-bar");
                let errorMsgHtml = '<div class="express-msg-outer-most-info"><div class="express-msg-outer-info-container"><div class="express-info-msg-container"><span class="message error">Please review payment method details</span></div> </div></div>';
                $('.credit-card-info').html(errorMsgHtml);
            }

            this.paymentStepData(paymentData);
        }
        ,

        /**
         * Navigate to location section of pickup
         */
        pickupLocationNavigate: function () {
            let isLocationExist = $('.pickup-location-container').length;

            if (isLocationExist > 0) {
                let shippingComponent = registry.get('checkout.steps.shipping-step.shippingAddress');
                shippingComponent.checkoutTitle( $t('In-store pickup') );
                let pickupCombo;
                if(window.e383157Toggle){
                    pickupCombo = fxoStorage.get('pickupShippingComboKey');
                }else{
                    pickupCombo = localStorage.getItem('pickupShippingComboKey');
                }
                if (pickupCombo  === "true") {
                    $(".root-container").show();
                    $(".root-container").addClass("edit-pickup-step-section");
                    $(".shipping-content-checkout").addClass("edit-pickup-step-section");
                }
                else {
                    $(".root-container").show();
                }

                $('html, body').animate({
                    scrollTop: $(".pickup-location-item-container").offset().top
                }, 100);
            }
        }
        ,

        /**
         * Function to edit pickup location
         */
        onEditPickupLocation: function () {
            var self = this;
            self.onEditPickup();
            let expressCheckout;
            if (window.e383157Toggle) {
                expressCheckout = fxoStorage.get('express-checkout');
            } else {
                expressCheckout = localStorage.getItem('express-checkout');
            }
            if (expressCheckout && isFclCustomer && $(".pickup-location-item-container").is(':empty')) {
                $(document).ajaxComplete(function (event, request, settings) {
                    if (settings.url == urlBuilder.build("delivery/index/centerDetails")) {
                        self.pickupLocationNavigate();
                    }
                });
            } else {
                self.pickupLocationNavigate();
            }
        }
        ,

        /**
         * Navigate to contact form of pickup
         */
        pickupContactFormNavigate: function () {
            let isContactFormExist = $('.contact-from-container').length;
            if (isContactFormExist > 0) {
                $('html, body').animate({
                    scrollTop: $(".contact-from-container").offset().top
                }, 100);
            }
        }
        ,

        /**
         * Function to edit pickup contact
         */
        onEditPickupContact: function () {
            var self = this;
            self.onEditPickup();
            let expressCheckout;
            if (window.e383157Toggle) {
                expressCheckout = fxoStorage.get('express-checkout');
            } else {
                expressCheckout = localStorage.getItem('express-checkout');
            }
            if (expressCheckout && isFclCustomer && $(".pickup-location-item-container").is(':empty')) {
                $(document).ajaxComplete(function (event, request, settings) {
                    if (settings.url == urlBuilder.build("delivery/index/centerDetails")) {
                        self.pickupContactFormNavigate();
                    }
                });
            } else {
                $(".root-container").show();
                self.pickupContactFormNavigate();
            }
        }
        ,

        /**
         * Function to edit shipping method
         */
        onEditShippingMethod: function () {
            this.onEditShipping();
            let isShippingMethodExist = $('#opc-shipping_method').length;
            if (isShippingMethodExist > 0) {
                $('html, body').animate({
                    scrollTop: $("#opc-shipping_method").offset().top
                }, 100);
            }
        }
        ,

        /**
         * Function to go to shipping step on click of edit button
         */
        onEditShipping: function () {
            stepNavigator.navigateTo('shipping', 'opc-shipping_method');

            checkoutAdditionalScript.selectedDeliveryOptionChecked();
            // Shipping option not selected issue fix stop

            // summary button start
            if (
                (isSdeStore !== true || isSelfregCustomer === false) &&
                window.checkoutConfig.is_commercial !== true
            ) {
                var isShip, isPick;
                if (window.e383157Toggle) {
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                } else {
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                }
                if ($(".continue.create_quote").is(':visible') && (isShip === 'true' && isPick === 'false')) {
                    $("#shipping-continue-to-payment-button").show().prop('disabled', false);
                } else if ($(".place-pickup-order").is(":visible") && (isShip === 'false' && isPick === 'true')) {
                    $("#pickup-continue-to-payment-button").show().prop("disabled", false);
                }
            }
            // summary button end
            if (isFclCustomer) {
                if (window.e383157Toggle) {
                    fxoStorage.set('editActionOnExpress', true);
                } else {
                    localStorage.setItem('editActionOnExpress', true);
                }
            }
        }
        ,

        /**
         * Function to go to payment step on click of edit button
         */
        onEditPayment: function () {
            stepNavigator.navigateTo('step_code', 'paymentStep');
            let paymentData;
            if (window.e383157Toggle) {
                paymentData = fxoStorage.get("paymentData");
            } else {
                paymentData = JSON.parse(localStorage.getItem('paymentData'));
            }
            if (isFclCustomer && paymentData) {
                profilePickEditBuilder.autofillPaymentDetails(paymentData);
                if (window.e383157Toggle) {
                    fxoStorage.set('editActionOnExpress', true);
                } else {
                    localStorage.setItem('editActionOnExpress', true);
                }
            }
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
        ,

        /**
         * Function to go to Pickup step on click of edit button
         */
        onEditPickup: function () {
            window.location.hash = "#shipping";
            // summary button start
            if (isSdeStore !== true && window.checkoutConfig.is_commercial !== true) {
                var isShip, isPick,expressCheckout;
                if (window.e383157Toggle) {
                    isShip = fxoStorage.get("shipkey");
                    isPick = fxoStorage.get("pickupkey");
                    expressCheckout = fxoStorage.get('express-checkout');
                } else {
                    isShip = localStorage.getItem("shipkey");
                    isPick = localStorage.getItem("pickupkey");
                    expressCheckout = localStorage.getItem('express-checkout');
                }
                if ($(".continue.create_quote").is(':visible') && (isShip === 'true' && isPick === 'false')) {
                    $("#shipping-continue-to-payment-button").show().prop('disabled', false);
                } else if ($(".place-pickup-order").is(":visible") && (isShip === 'false' && isPick === 'true')) {
                    $("#pickup-continue-to-payment-button").show().prop("disabled", false);
                }
                if (expressCheckout && isFclCustomer && !$(".opc-progress-bar li:nth-child(1)").attr("data-active")) {
                    $("#checkoutSteps .pickup-title-checkout h1").next("a").trigger('click');
                    if (window.e383157Toggle) {
                        fxoStorage.set('editActionOnExpress', true);
                    } else {
                        localStorage.setItem('editActionOnExpress', true);
                    }
                }
            }
            // summary button end
        }
        ,

        /**
         * @return {Boolean}
         */
        hasShippingMethod: function () {
            return window.checkoutConfig.selectedShippingMethod !== null;
        }
        ,

        /**
         * @return {*}
         */
        getFormKey: function () {
            return window.checkoutConfig.formKey;
        }
        ,

        /**
         * Is site credit card used as payment
         *
         * @returns bool
         */
        isSiteCreditCardUsed: function () {
            if (window.e383157Toggle) {
                return fxoStorage.get('useSiteCreditCard') === 'true';
            } else {
                return localStorage.getItem('useSiteCreditCard') === "true";
            }
        }
        ,

        shippingData: ko.observable(function () {
            if (window.e383157Toggle) {
                return fxoStorage.get('shippingData');
            } else {
                return localStorage.getItem('shippingData');
            }
        }),

        pickupData:
            ko.observable(function () {
                if (window.e383157Toggle) {
                    return fxoStorage.get('pickupData');
                } else {
                    return localStorage.getItem('pickupData');
                }
            }),
        editContactInformation:
            ko.observable('shipping'),

        /**
         * @returns Bool
         */
        hasContactInformation:

            function () {
                let contactInformation = false;

                if (this.shippingData() || this.pickupData()) {
                    contactInformation = true;
                }

                return contactInformation;
            }

        ,

        getContactInformation: function () {
            let contactInformation = {
                firstName: '',
                lastName: '',
                email: '',
                telephone: '',
            };

            if (!this.hasContactInformation()) {
                return contactInformation;
            }

            if (this.shippingData()) {
                const shippingData = JSON.parse(this.shippingData());

                const {firstname, lastname, telephone} = shippingData
                    .addressInformation
                    .shipping_address;

                const email = shippingData
                    .addressInformation
                    .shipping_address
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

                return contactInformation;
            }

            const pickupData = JSON.parse(this.pickupData());

            const {
                contact_fname,
                contact_lname,
                contact_email,
                contact_number_pickup
            } = pickupData.contactInformation;

            contactInformation = {
                firstName: contact_fname,
                lastName: contact_lname,
                email: contact_email,
                telephone: contact_number_pickup
            };

            this.editContactInformation('pickup');

            return contactInformation;
        }
        ,

        onEditContactInformation: function () {
            if (this.editContactInformation() === 'shipping') {
                this.onEditShipping();
                return;
            }
            ;

            this.onEditPickupContact();
        }
        ,

        maskTelephoneUsaFormat: function (telephone) {
            let phone = telephone.replace(/\D/g, '').match(/(\d{3})(\d{3})(\d{4})/);
            phone = '(' + phone[1] + ') ' + phone[2] + '-' + phone[3];

            return phone;
        }
        ,

        checkAlternateFlag: function () {
            if (this.isXmenD177346Fix()) {
                if (window.e383157Toggle) {
                    let isAlternateFlag = fxoStorage.get('isAlternateFlag')
                    if (isAlternateFlag === true) {
                        this.isAlternateFlag(true);
                    } else {
                        this.isAlternateFlag(false);
                    }
                } else {
                    let isAlternateFlag = localStorage.getItem('isAlternateFlag');
                    if (isAlternateFlag === true) {
                        this.isAlternateFlag(true);
                    } else {
                        this.isAlternateFlag(false);
                    }
                }
            }
        }

    });
});
