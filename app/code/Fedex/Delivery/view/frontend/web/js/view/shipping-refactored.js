/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "underscore",
    "Magento_Ui/js/form/form",
    "ko",
    "Magento_Customer/js/model/customer",
    "Magento_Customer/js/model/address-list",
    "Magento_Checkout/js/model/address-converter",
    "Magento_Checkout/js/model/quote",
    "Magento_Catalog/js/price-utils",
    "Magento_Checkout/js/action/create-shipping-address",
    "Magento_Checkout/js/action/select-shipping-address",
    "Magento_Checkout/js/model/shipping-rates-validator",
    "Magento_Checkout/js/model/shipping-address/form-popup-state",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Checkout/js/action/select-shipping-method",
    "Magento_Checkout/js/model/shipping-rate-registry",
    "Magento_Checkout/js/action/set-shipping-information",
    "Magento_Checkout/js/model/step-navigator",
    "Magento_Ui/js/modal/modal",
    "Magento_Checkout/js/model/checkout-data-resolver",
    "Magento_Checkout/js/checkout-data",
    "uiRegistry",
    "mage/translate",
    "mage/url",
    "Magento_Customer/js/customer-data",
    "Magento_Checkout/js/model/error-processor",
    "Magento_Ui/js/model/messageList",
    "shippingModal",
    "checkoutAdditionalScript",
    "Fedex_ExpressCheckout/js/view/checkout/fcl-shipping-account-list",
    "shippingFormAdditionalScript",
    "personalAddressBook",
    "Magento_Checkout/js/model/shipping-rate-service",
    "replaceAllPolyfill",
    'Fedex_ExpressCheckout/js/fcl-profile-session',
    'Fedex_ExpressCheckout/js/fcl-profile-pickup-edit',
    "pickupSearch",
    "Fedex_ExpressCheckout/js/express-checkout-shipto",
    "gdlEvent",
    "rateResponseHandler",
    "checkout-common",
    "Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper",
    'rateQuoteAlertsHandler',
    "Fedex_MarketplaceUi/js/view/manage_toast_messages",
    "marketplace-delivery-toast-messages",
    "resetQuoteAddress",
    "Magento_Ui/js/lib/view/utils/dom-observer",
    "uploadToQuoteCheckout",
    "rateQuoteErrorsHandler",
    "fedex/storage",
    "Fedex_Delivery/js/view/google-places-api",
    "Fedex_Recaptcha/js/reCaptcha",
    "Fedex_Delivery/js/model/toggles-and-settings",
    "ajaxUtils",
    "Fedex_Delivery/js/model/pickup-data",
    "Fedex_Delivery/js/model/campaign-ad-disclosure"
], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressList,
    addressConverter,
    quote,
    priceUtils,
    createShippingAddress,
    selectShippingAddress,
    shippingRatesValidator,
    formPopUpState,
    shippingService,
    selectShippingMethodAction,
    rateRegistry,
    setShippingInformationAction,
    stepNavigator,
    modal,
    checkoutDataResolver,
    checkoutData,
    registry,
    $t,
    urlBuilder,
    customerData,
    errorProcessor,
    messageList,
    shippingModal,
    checkoutAdditionalScript,
    fclShippingAccountList,
    shippingFormAdditionalScript,
    personalAddressBook,
    shippingRateService,
    replaceAllPolyfill,
    profileSessionBuilder,
    profilePickEditBuilder,
    pickupSearch,
    expressCheckoutShiptoBuilder,
    gdlEvent,
    rateResponseHandler,
    checkoutCommon,
    marketplaceQuoteHelper,
    rateQuoteAlertsHandler,
    marketplaceToastMessages,
    marketplaceDeliveryToast,
    resetQuoteAddress,
    $do,
    uploadToQuoteCheckout,
    rateQuoteErrorsHandler,
    fxoStorage,
    googlePlacesApi,
    reCaptcha,
    togglesAndSettings,
    ajaxUtils,
    pickupData,
    disclosureModel
) {
    "use strict";

    let popUp = null;
    let indexValue = null;
    let onEnterSearch = true;
    let isFirstnameValid = false;
    let isLastnameValid = false;
    let isPhonenameValid = false;
    let isEmailValid = false;
    let isalternate = false;
    let shipAccountNumber = '';
    let telephoneRegex = /^(\([0-9]{3}\) |[0-9]{3}-)[0-9]{3}-[0-9]{4}$/;
    let nameRegex = /[$/@*()^!~\\]+/;
    let addAddressbookData = null;
    let isSaveAddressbook = true;

    const PRIORITY_PRINT_PICKUP = 'Priority Print Pickup';
    const STANDARD_PICKUP = 'Standard Pickup';

    /**
     * Variable to hold location & map data
     */
    let lat = null;
    let lng = null;
    let map = null;
    let selected_icon = null;
    let default_icon = null;
    let markers = [];
    let selectedMarkerColor = '#4d148c';
    let defaultMarkerColor = '#54646b';
    let markerGlyphColor = '#ffffff';
    let markerScale = 1.35;

    /**
     * var Interval to clear the setInterval function
     */
    let intervalId = null;
    let hasDefaultShippingAccountNumber = false;
    let hidePicupStore = true;
    const isLoggedIn = togglesAndSettings.isLoggedIn;
    const isOutSourced = togglesAndSettings.isOutSourced;
    const explorersD169768Fix = togglesAndSettings.explorersD169768Fix;
    const explorersRestrictedAndRecommendedProduction = togglesAndSettings.explorersRestrictedAndRecommendedProduction;
    const isFclCustomer = togglesAndSettings.isFclCustomer;
    const isSelfRegCustomer = togglesAndSettings.isSelfRegCustomer;
    const isSdeStore = shippingFormAdditionalScript.isSdeStore();
    const fixShippingResults = togglesAndSettings.fixShippingResults;
    const callRateApiShippingAccountValidation = togglesAndSettings.callRateApiShippingAccountValidation;
    const isCheckoutQuotePriceDashable = togglesAndSettings.isCheckoutQuotePriceDashable;
    const checkIcon = togglesAndSettings.checkIcon;
    const crossIcon = togglesAndSettings.crossIcon;
    const infoIcon = togglesAndSettings.infoUrl;
    const orderConfirmationUrl = window.BASE_URL + "submitorder/index/ordersuccess";
    const customBillingFieldsToggleOn = togglesAndSettings.customBillingFieldsToggleOn === true;
    const isEproUploadtoQuoteToggle = togglesAndSettings.isEproUploadtoQuoteToggle;
    const isFuseBidding = togglesAndSettings.isFuseBidding;
    let shippingLocationCancelButton = 'shipping-location-cancel';
    let shippingLocationSaveButton = 'shipping-location-continue';
    var isRestrictedProductionLocationFlag = false;
    let isRecommendedProductionLocationFlag = false;
    let isProductionLocationAutomaticallySelected = false;
    var isExplorersAddressClassificationFixToggleEnable = false;
    let isPersonalAddressBook = window?.checkoutConfig?.is_personal_address_book;

    let isD180202ToggleEnable = window?.checkoutConfig?.isD180202ToggleEnable;
    let explorersProductionLocationFix = typeof (window.checkoutConfig.explorers_d188299_production_location_fix) != 'undefined' && window.checkoutConfig.explorers_d188299_production_location_fix != null ? window.checkoutConfig.explorers_d188299_production_location_fix : false;
    let techTitansProductionLocationFix = typeof (window.checkoutConfig.tech_titans_d_205447_fix) != 'undefined' && window.checkoutConfig.tech_titans_d_205447_fix != null ? window.checkoutConfig.tech_titans_d_205447_fix : false;

    let isD193257ToggleEnable = typeof window.checkoutConfig.explorers_d_193257_fix != 'undefined' ? window.checkoutConfig.explorers_d_193257_fix : false;
    let maegeeks_pobox_validation = typeof window.checkoutConfig.maegeeks_pobox_validation != 'undefined' ? window.checkoutConfig.maegeeks_pobox_validation : false;
    let tiger_d203990 = typeof window.checkoutConfig.tiger_d203990 != 'undefined' ? window.checkoutConfig.tiger_d203990 : false;
    let explorers_e450676_personal_address_book = typeof window.checkoutConfig.explorers_e_450676_personal_address_book != 'undefined' ? window.checkoutConfig.explorers_e_450676_personal_address_book : false;
    let tiger_d213977 = typeof window.checkoutConfig.tiger_d213977 != 'undefined' ? window.checkoutConfig.tiger_d213977 : false;

    let emptyPickUpOrShipping, isShipping, isPickUp, pickShowVal, shipShowVal, switchLabel;
    if (togglesAndSettings.mixedCart) {
        switchLabel = true;
    }

    // fix the order success page image icon breaking issue
    if (window.e383157Toggle) {
        fxoStorage.set("infoUrl", infoIcon);
        fxoStorage.set("product_image_data", togglesAndSettings.productImageData);
    } else {
        localStorage.setItem("infoUrl", infoIcon);
        localStorage.setItem("product_image_data", JSON.stringify(togglesAndSettings.productImageData));
    }

    if (explorersRestrictedAndRecommendedProduction || togglesAndSettings.mazegeeksE482379AllowCustomerToChooseProductionLocationUpdates) {
        shippingLocationCancelButton = 'shipping-location-popup-cancel';
        shippingLocationSaveButton = 'shipping-location-popup-continue';
    }

    isRestrictedProductionLocationFlag = typeof (window.checkoutConfig.is_restricted_store_production_location_option) != 'undefined' && window.checkoutConfig.is_restricted_store_production_location_option != null ? window.checkoutConfig.is_restricted_store_production_location_option : false;
    isRecommendedProductionLocationFlag = window.checkoutConfig?.recommended_production_location;
    isExplorersAddressClassificationFixToggleEnable = typeof (window.checkoutConfig.explorers_address_classification_fix) != 'undefined' && window.checkoutConfig.explorers_address_classification_fix != null ? window.checkoutConfig.explorers_address_classification_fix : false;
    isPersonalAddressBook = typeof (window.checkoutConfig.is_personal_address_book) != 'undefined' && window.checkoutConfig.is_personal_address_book != null ? window.checkoutConfig.is_personal_address_book : false;
    explorers_e450676_personal_address_book = typeof window.checkoutConfig.explorers_e_450676_personal_address_book != 'undefined' ? window.checkoutConfig.explorers_e_450676_personal_address_book : false;

    /**
     * Checks outsourced product
     */
    if (isOutSourced && !isLoggedIn || !togglesAndSettings.mixedCart) {
        hidePicupStore = false;
    }

    /**
     * Check for user details error
     */
    var resetGrandTotals = setInterval(function () {
        if ($(".grand.totals.incl .price").length > 0) {
            try {
                let cartData = customerData.get('cart-data')();
                let grandTotalsAmount = cartData['totals']['grand_total'] || 0;
                grandTotalsAmount = priceUtils.formatPrice(grandTotalsAmount, quote.getPriceFormat());
                $(".grand.totals.incl .price").text(grandTotalsAmount);
            } catch (err) {
                console.log(err);
                clearInterval(resetGrandTotals);
            }
            clearInterval(resetGrandTotals);
        }
    }, 10);

    if (window.e383157Toggle) {
        emptyPickUpOrShipping = (undefined == fxoStorage.get("pickupkey")) && (undefined == fxoStorage.get("shipkey"));
        isShipping = (fxoStorage.get("shipkey") == "true") && (fxoStorage.get("pickupkey") == "false");
        isPickUp = (fxoStorage.get("shipkey") == "false") && (fxoStorage.get("pickupkey") == "true");
    } else {
        emptyPickUpOrShipping = (undefined == localStorage.getItem("pickupkey")) && (undefined == localStorage.getItem("shipkey"));
        isShipping = (localStorage.getItem("shipkey") == "true") && (localStorage.getItem("pickupkey") == "false");
        isPickUp = (localStorage.getItem("shipkey") == "false") && (localStorage.getItem("pickupkey") == "true");
    }
    if (emptyPickUpOrShipping) {
        if (window.e383157Toggle) {
            fxoStorage.set("pickupkey", false);
            fxoStorage.set("shipkey", true);
        } else {
            localStorage.setItem("pickupkey", false);
            localStorage.setItem("shipkey", true);
        }
    }
    if (togglesAndSettings.mixedCart) {
        if (isPickUp) {
            pickShowVal = true;
            shipShowVal = false;
        }
        else {
            pickShowVal = false;
            shipShowVal = true;
        }
        switchLabel = true;
        $(".opc-block-shipping-information .alertbox").hide();
    } else {
        pickShowVal = togglesAndSettings.pickShowVal;
        shipShowVal = togglesAndSettings.shipShowVal;
        switchLabel = false;
    }
    if (isShipping && !isLoggedIn) {
        $("body").addClass("shipkey");
    }

    function updateHCPrice(productLines) {
        const firstPartyProducts = productLines.filter((productLine) => productLine.type !== 'THIRD_PARTY');
        if (firstPartyProducts.length > 0) {
            const quoteTotals = quote.getTotals()();
            const updatedItems = quoteTotals.items.map((quoteItem) => {
                let lineItem = firstPartyProducts.find((product) => quoteItem.item_id == product.instanceId);
                if (lineItem) {
                    quoteItem.row_total = lineItem.productRetailPrice.toString().replace('$', '');
                    quoteItem.base_row_total = lineItem.productRetailPrice.toString().replace('$', '');
                }
                return quoteItem;
            });
            quoteTotals.items = updatedItems;
            quote.setTotals(quoteTotals);
        }
    }

    return Component.extend({
        defaults: {
            template: "Magento_Checkout/shipping",
            shippingFormTemplate: "Magento_Checkout/shipping-address/form",
            shippingMethodListTemplate: "Magento_Checkout/shipping-address/shipping-method-list",
            shippingMethodItemTemplate: "Magento_Checkout/shipping-address/shipping-method-item",
            uploadToQuoteTemplate: "Fedex_UploadToQuote/upload-to-quote",
        },
        visible: ko.observable(),
        errorValidationMessage: ko.observable(false),
        pickUpJson: ko.observableArray([]),
        recommendedLocationJson: ko.observableArray([]),
        showShippingContent: ko.observable(shipShowVal),
        showPickupContent: ko.observable(pickShowVal),
        showSwitchLabel: ko.observable(switchLabel),
        onclickTriggerPickupShow: ko.observable(pickShowVal),
        onclickTriggerShipShow: ko.observable(shipShowVal),
        isCustomerLoggedIn: ko.observable(isLoggedIn && !isSelfRegCustomer),
        isHidePicupStore: ko.observable(hidePicupStore),
        isFormPopUpVisible: formPopUpState.isVisible,
        isFormInline: isFclCustomer ? true : addressList().length === 0,
        isNewAddressAdded: ko.observable(false),
        saveInAddressBook: 1,
        quoteIsVirtual: quote.isVirtual(),
        infoUrl: ko.observable(togglesAndSettings.infoUrl),
        shippingAccountNumber: ko.observable(""),
        shippingAccountNumberPlaceHolder: ko.observable(""),
        timeSlots: ko.observableArray([]),
        selectedTimeSlots: ko.observable(null),
        crossUrl: ko.observable(togglesAndSettings.crossUrl),
        crossIcon: ko.observable(togglesAndSettings.crossIcon),
        locationIcon: ko.observable(togglesAndSettings.locationIcon),
        hcoinfoIcon: ko.observable(togglesAndSettings.hcoinfoIcon),
        alertIcon: ko.observable(togglesAndSettings.alertIcon),
        limitedTimeIconUrl: ko.observable(togglesAndSettings.limitedTimeIcon),
        customBillingShipping: ko.observableArray(togglesAndSettings.custom_billing_shipping),
        isExpectedDeliveryDateEnabled: ko.observable(window?.checkoutConfig?.isExpectedDeliveryDateEnabled),
        isRetailFlow: ko.observable(window?.checkoutConfig?.isRetailCustomer),
        isPriorityPrintLimitedTimeToggle: ko.observable(togglesAndSettings.isPriorityPrintLimitedTimeToggle),
        isPromiseTimePickupOptionsToggle: ko.observable(togglesAndSettings.isPromiseTimePickupOptionsToggle),
        currentPickupLaterRadio: ko.observable(null),
        openPickupTimeModal: ko.observable(false),
        isD207891Toggle: ko.observable(window?.checkoutConfig?.d207891_toggle),
        isRestrictedProductionLocation: ko.observable(isRestrictedProductionLocationFlag),
        isRecommendedProductionLocation: ko.observable(isRecommendedProductionLocationFlag),
        koStandardDeliveryLocalTime: ko.observable(null),
        disclosureModel: disclosureModel,
        disableCreateQuoteBtn: ko.observable(false),
        // B-2616598 | Observable for production location selection status
        hasProductionLocationSelected: ko.observable(window.checkoutConfig.has_selected_prod_loc || false),
        isD236651Enabled: ko.observable(togglesAndSettings.sgc_D_236651),


        /**
         * Calender icon path to be used for date input field
         */
        calenderIcon: ko.observable(togglesAndSettings.calenderIcon),
        /**
         * Checks center detils is to be shown or hidden for pickup locations
         */
        showCenter: ko.observable(false),
        /**
         * Observable to hold center details for a pickup location
         */
        center: ko.observable(),
        /**
         * Observable to hold Preferred DateTime for pickup location
         */
        koPickupDate: ko.observable(null),
        koPickupDateHidden: ko.observable(null),
        koPickupName: ko.observable(null),
        koPickupAddress: ko.observable(null),
        koPickupId: ko.observable(null),
        koPickupTimeAvailability: ko.observableArray([]),
        koPickupRegion: ko.observable(null),
        koEarliestPickupDateTime: ko.observable(null),
        koClosestPickupLabelIndex: ko.observable(null),
        koFormattedPickupLaterDate: ko.observable(null),
        koSelectedPickupLaterDate: ko.observable(null),
        koSelectedDate: ko.observable(null),
        koSelectedTimeRange: ko.observable(null),
        koFlagIsToday: ko.observable(false),
        koFlagIsFutureEarlist: ko.observable(false),
        koEarliestHour: ko.observable(null),
        koEarliestDate: ko.observable(null),
        koEarliestDayFormat: ko.observable(null),
        availableTimeslotOptions: ko.observableArray([]),
        selectedTimeslot: ko.observable(null),
        optTimeslot: ko.observable(null),
        koEarliestPickupLabelIndex: ko.observable(null),
        enableEarlyShippingAccountIncorporation: ko.observable(true),
        isSde: ko.observable(false),
        isLocalDelivery: ko.observable(false),
        inBranchInCheckout: ko.observable(false),
        koAssignedPickupLabelIndex: ko.observable(false),
        koStandardPickupTime: ko.observable(null),
        /**
         * generateShippingAccount execution check
         */
        isgenerateShippingAccountExecuted: ko.observable(0),
        //koUpdatePickupDateTime: ko.observable(null),

        /**
         * isAutopopulate
         */
        isAutopopulate: ko.observable(true),

        /**
         * isMaskonAutopopulate
         */
        isMaskonAutopopulate: ko.observable(false),

        /**
         * Observable to check if contact information form is hidden or to be shown
         */
        showContact: ko.observable(false),

        /**
         * Observable to check if alternateContact information form is hidden or to be shown
         */
        isAlternateContact: ko.observable(false),

        /**
         * Observable for checkout page title
         */
        checkoutTitle: ko.observable("Checkout"),

        /**
         * Observable for shipping form title
         */
        checkoutShippingFormTitle: ko.observable("Shipping address"),

        checkoutShippingMethodTitle: ko.observable("Shipping Methods"),

        checkoutPickupFormTitle: ko.observable("Pick up location"),

        /** zipCode Title based on Epro Upload to Quote Toggle */
        zipCodePickupTitle: ko.observable(isEproUploadtoQuoteToggle ? "Enter Zip Code" : "Enter Recipient Address or Zip Code"),

        /**
         * ###############################################################
         *                   Start | Marketplace Section
         * ###############################################################
         */
        thirdPartyShippingMethods: ko.observableArray([]),
        firstPartyShippingMethods: ko.observableArray([]),
        firstPartyMethod: ko.observable(''),
        thirdPartyMethod: ko.observable(''),
        isPickupFormFilled: ko.observable(false),
        chosenDeliveryMethod: ko.observable(window.e383157Toggle ? fxoStorage.get('chosenDeliveryMethod') : localStorage.getItem('chosenDeliveryMethod')),
        pickupShippingComboKey: ko.observable(false),
        /**
         * ###############################################################
         *                   End | Marketplace Section
         * ###############################################################
         */

        /**
         * @return {exports}
         */
        initialize: function () {
            let self = this,
                hasNewAddress,
                fieldsetName = "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset";

            if (this.isSimplifiedProductionLocationOn()) {
                setTimeout(function () {
                    self.checkAndRestoreProductionLocation();
                }, 1000);
            }

            this.isToggleEnabled = togglesAndSettings.isToggleEnabled;

            if (window.checkoutConfig?.isUploadToQuote) {
                uploadToQuoteCheckout.removeUploadToQuoteLocalStorage();
                uploadToQuoteCheckout.autoFillContactFormUploadToQuote(isFclCustomer);
            }
            self.visible.subscribe(function (visibleFlag) {
                if (visibleFlag && window.FDXPAGEID) {
                    window.FDX.GDL.push(['event:publish', ['page', 'pageinfo', {
                        pageId: window.FDXPAGEID + '/shipping'
                    }]]);
                }
            });

            self.chosenDeliveryMethod.subscribe((newMethod) => {
                if (['shipping', 'pick-up'].includes(newMethod)) {
                    let chosenDeliveryMethod;
                    if (window.e383157Toggle) {
                        chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                    } else {
                        chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                    }
                    if (togglesAndSettings.isSdeStore && !chosenDeliveryMethod) {
                        if (togglesAndSettings.pickShowVal) {
                            newMethod = 'pick-up';
                        } else {
                            newMethod = 'shipping';
                        }
                    }
                    if (window.e383157Toggle) {
                        fxoStorage.set('chosenDeliveryMethod', newMethod);
                    } else {
                        localStorage.setItem('chosenDeliveryMethod', newMethod);
                    }
                    window.dispatchEvent(new Event('on_change_delivery_method'));
                }
            });
            self.pickupShippingComboKey.subscribe((newKey) => {
                if (window.e383157Toggle) {
                    fxoStorage.set('pickupShippingComboKey', String(newKey));
                } else {
                    localStorage.setItem('pickupShippingComboKey', newKey);
                }
            });

            if (togglesAndSettings.profileSession) {
                let profilePreferredDeliveryMethod = profileSessionBuilder.getPreferredDeliveryMethod();
                if (typeof profilePreferredDeliveryMethod === 'object' && profilePreferredDeliveryMethod !== null) {
                    let preferredDeliveryMethod = profileSessionBuilder.getPreferredDeliveryMethod().delivery_method;
                    preferredDeliveryMethod = preferredDeliveryMethod === 'DELIVERY' ? 'shipping' : 'pick-up';
                    let isChosenDeliveryMethod = window.e383157Toggle ? fxoStorage.get('chosenDeliveryMethod') : localStorage.chosenDeliveryMethod;
                    if (!isChosenDeliveryMethod) {
                        this.chosenDeliveryMethod(preferredDeliveryMethod);
                    }
                    if (window.e383157Toggle) {
                        fxoStorage.set('preferredDeliveryMethod', preferredDeliveryMethod);
                    } else {
                        localStorage.setItem('preferredDeliveryMethod', preferredDeliveryMethod);
                    }
                }
            }

            if (this.rates().length == 1) {
                this.firstPartyMethod(this.rates()[0].carrier_code + '_' + this.rates()[0].method_code);
            }

            self.rates.subscribe((newRates) => {
                if (newRates.length) {
                    const filteredFirstPartyRates = newRates.filter(rate => rate.carrier_code === "fedexshipping");
                    self.firstPartyShippingMethods(filteredFirstPartyRates);
                    const checkedRadio = window.checkoutConfig.tiger_shipping_methods_display
                        ? jQuery("#onepage-checkout-shipping-method-additional-load > table > tbody .row input[type=radio]:checked")
                        : jQuery("#onepage-checkout-shipping-method-additional-load > table > tbody > tr.row input[type=radio]:checked");

                    $(checkedRadio).trigger("click");
                    let thirdPartySellers = [];
                    newRates.forEach(shippingMethod => {
                        if (shippingMethod.seller_id) {
                            let sellerIndex = -1;
                            let sellerCounter = 1;
                            for (let seller = 0; seller < thirdPartySellers.length; seller++) {
                                if (thirdPartySellers[seller].seller_id === shippingMethod.seller_id) {
                                    sellerIndex = seller;
                                    break;
                                }
                                sellerCounter++
                            }
                            if (sellerIndex === -1) {
                                let is_accordion_expanded = false;
                                if (!window.checkoutConfig.tiger_shipping_methods_display) {
                                    if (sellerCounter == 1 && self.isFullMarketplaceQuote() || (self.pickupShippingComboKey() && self.chosenDeliveryMethod() === 'pick-up')) {
                                        is_accordion_expanded = true;
                                    }
                                }

                                thirdPartySellers.push({
                                    seller_id: shippingMethod.seller_id,
                                    seller_name: shippingMethod.seller_name,
                                    selected_shipping_method: ko.observable(''),
                                    shipping_methods: [shippingMethod],
                                    show_expand_button: false,
                                    is_accordion_expanded: ko.observable(is_accordion_expanded)
                                });
                            } else {
                                thirdPartySellers[sellerIndex].shipping_methods.push(shippingMethod);
                                thirdPartySellers[sellerIndex].show_expand_button = true;
                            }
                        }
                    });
                    if (window.checkoutConfig.tiger_shipping_methods_display) {
                        thirdPartySellers.forEach(seller => {
                            const sortedMethods = self.sortShippingMethods(seller.shipping_methods);
                            seller.shipping_methods = [
                                ...sortedMethods.topTwoMethods,
                                ...sortedMethods.otherMethods
                            ];
                        });
                    }
                    self.thirdPartyShippingMethods(thirdPartySellers);
                    $(checkedRadio).trigger("click");
                    if (isD180202ToggleEnable && togglesAndSettings.isCommercial) {
                        self.waitForElementAndSelectFirstMethod();
                    }
                    this.showFirstPartyAccordionButton(newRates.filter((method) => !method.marketplace).length > 1)
                    this.showThirdPartyAccordionButton(newRates.filter((method) => method.marketplace).length > 1)
                }
            });

            self.sortedShippingMethods = ko.computed(function () {
                return self.sortShippingMethods(self.firstPartyShippingMethods());
            });

            self.waitForElementAndSelectFirstMethod = function () {
                const checkExist = setInterval(() => {
                    const shippingOptionElement = window.checkoutConfig.tiger_shipping_methods_display
                        ? jQuery('#onepage-checkout-shipping-method-additional-load > table > tbody .row input[type=radio]')
                        : jQuery('#onepage-checkout-shipping-method-additional-load > table > tbody > tr.row input[type=radio]');
                    if (shippingOptionElement.length < 2 && shippingOptionElement.is(':checked')) {
                        clearInterval(checkExist);
                        shippingOptionElement.trigger("click");
                    }
                }, 100);
            };

            this.shippingMethodsSelected = ko.computed(() => {
                let firstPartyMethodsAvailable = this.getDeliveryMethodsQty('not-marketplace') > 0,
                    thirdPartyMethodsAvailable = this.getDeliveryMethodsQty('marketplace') > 0;

                if (firstPartyMethodsAvailable && thirdPartyMethodsAvailable) {
                    return !!this.firstPartyMethod() && !!this.thirdPartyMethod()
                }

                if (firstPartyMethodsAvailable) {
                    return !!this.firstPartyMethod()
                }

                if (thirdPartyMethodsAvailable) {
                    return !!this.thirdPartyMethod()
                }
            });

            this.continuneToPaymentButtonEnable = ko.computed(() => {
                // Retire this logic when E-513778 toggle is removed
                if (disclosureModel.isCampaingAdDisclosureToggleEnable) {
                    return !this.disableCreateQuoteBtn() && (disclosureModel.isCampaignAdDisclosureComplete() || !disclosureModel.shouldDisplayInlineEproQuestionnaire());
                } else {
                    return this.shippingMethodsSelected();
                }
            }, this);

            this.shippingAccountNumber.subscribe((newValue) => {
                window.dispatchEvent(new CustomEvent('fedexShippingAccountAddedManually', { detail: newValue }));
            });

            self.visible(!quote.isVirtual());
            this._super();
            self.getEncryptedKey();
            if (isFclCustomer) {
                self.setCustomerShippingAddess();
            }
            if (isSdeStore === true && isFclCustomer == false) {
                self.checkoutTitle("Shipping location");
                self.checkoutShippingFormTitle("Shipping address");
                self.isHidePicupStore(false);
                var defaultShippingAccountNumber = shippingFormAdditionalScript.getDefaultShippingAccountNumber();
                if (defaultShippingAccountNumber != '') {
                    self.shippingAccountNumber(defaultShippingAccountNumber);
                    hasDefaultShippingAccountNumber = true;
                }
            }

            let isSde = togglesAndSettings.isSde != undefined ? togglesAndSettings.isSde : false;
            self.isSde(isSde);

            //B-1517822 | Allow Shipping account number for SelfReg
            if (isSelfRegCustomer === true) {
                var defaultShippingAccountNumber = shippingFormAdditionalScript.getDefaultShippingAccountNumber();
                if (defaultShippingAccountNumber != '') {
                    self.shippingAccountNumber(defaultShippingAccountNumber);
                    hasDefaultShippingAccountNumber = true;
                }
            }
            if (self.enableEarlyShippingAccountIncorporation()) {
                shippingFormAdditionalScript.onloadMaskShippingAccountNumber(self);
            }
            $(window).on('load', function () {
                let isShip;
                if (window.e383157Toggle) {
                    isShip = fxoStorage.get('shipkey');
                } else {
                    isShip = window.localStorage.shipkey;
                }
                if (isShip === "false") {
                    self.shippingShippingExclShippingMessageHide();
                }
                if (self.enableEarlyShippingAccountIncorporation()) {
                    self.showHideLocalDeliveryToastMessage();
                    let isAutopopulate = shippingFormAdditionalScript.autoPopulateShippingAccountNumber(self);
                    self.isAutopopulate(isAutopopulate);
                }
            });
            $(window).ajaxStop(function () {
                let isShip;
                if (window.e383157Toggle) {
                    isShip = fxoStorage.get('shipkey');
                } else {
                    isShip = window.localStorage.shipkey;
                }
                if (isShip == "false") {
                    self.shippingShippingExclShippingMessageHide();
                }
                if (self.enableEarlyShippingAccountIncorporation()) {
                    self.showHideLocalDeliveryToastMessage();
                    let isAutopopulate = shippingFormAdditionalScript.autoPopulateShippingAccountNumber(self);
                    self.isAutopopulate(isAutopopulate);
                    self.hideremoveShippingAccountNumberwhennull();
                    $(this).unbind("ajaxStop");
                }
            });

            shippingModal.openShipPickPopup(self);

            let zipValue = null;
            let city = null;
            let stateCode = null;
            let pinCode = null;

            $(document).on("keypress keyup", 'input[name="custom_attributes[ext]"]', function (e) {
                this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
            });
            if (window.e383157Toggle) {
                fxoStorage.delete('gdl-event-added');
            } else {
                localStorage.removeItem('gdl-event-added');
            }
            let flagData = false;
            var inBranchdata = customerData.get('inBranchdata')();
            $(document).on("keyup keypress click", ".zipcode-container .zipcodePickup", function (e) {

                // Disable legacy events when new component is initialized
                if ($('.new-pickup-selector').length > 0) {
                    $(document).off("keyup keypress click", ".zipcode-container .zipcodePickup");
                    return;
                }

                if ($(".zipcode-container .zipcodePickup").val() == '') {
                    $("#search-pickup").prop("disabled", true);
                }

                if (togglesAndSettings.explorersD174773Fix) {
                    if (e.keyCode === 13) {
                        $('#geocoder-results').hide();
                    } else {
                        $('#geocoder-results').show();
                    }
                }

                let input = document.querySelector('.zipcode-container').getElementsByTagName('input')[0];

                if (input.value.length >= 2) {
                    googlePlacesApi.loadAutocompleteService(input.value, self, e);
                } else {
                    googlePlacesApi.resetResults();
                }

                if (jQuery('input#zipcodePickup').val().length > 4) {
                    if ((typeof inBranchdata === 'undefined' || inBranchdata === null) || !(inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart)) {
                        $("#search-pickup").attr("disabled", false);
                    }
                } else {
                    $("#search-pickup").attr("disabled", true);
                }

                if (e.which == 13 && zipValue != '') {
                    // On enter Enter key it is calling for search store pickup address
                    if (onEnterSearch) {
                        self.geoCoderFindAddress(city, stateCode, pinCode).then(function (res) {
                            city = res.city;
                            stateCode = res.stateCode;
                            pinCode = res.pinCode;
                            self.getPickupAddress(city, stateCode, pinCode);
                        });
                        onEnterSearch = false;
                    } else {
                        onEnterSearch = true;
                    }
                }
            });

            /**
             * Space not allowing in shipping account feild
             */
            $(document).on('keypress', '.fedex_account_number-field', function (e) {
                if (e.which == 32) {
                    return false;
                }
            });
            $(document).on('input', '.fedex_account_number-field, .account-number, .fedex-account-number', function (e) {
                $(this).val($(this).val().replace(/ /g, ""));
                return false;
            });

            $(document).on('input', 'input[name=postcode]', function (e) {
                // Only numbers will be allowed
                // If the user types 5 digits, the mask should be XXXXX
                // If user types more than 5 digits, the mask should be XXXXX-XXXX
                var value = e.target.value.replace(/\D/g, '').match(/(\d{0,5})(\d{0,4})/);

                if (value[2]) {
                    e.target.value = value[1] + '-' + value[2];
                } else {
                    e.target.value = value[1];
                }
            });

            $(document).ready(function () {
                self.setDefaultShipping();
                if (self.isCustomerLoggedIn() == true) {
                    $("#checkout-button-title, #checkout-ship-button-title").text("SUBMIT ORDER");
                } else {
                    $("#checkout-button-title, #checkout-ship-button-title").text("CONTINUE TO PAYMENT");
                }
            });

            $(document).on('blur', 'input[name=firstname], input[name=lastname]', function () {
                // Trigger the validation on blur.
                let componentName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.' + $(this).attr('name');
                let component = registry.get(componentName);
                component.validate();
            });

            $(document).on('keyup focus', 'div[name="shippingAddress.custom_attributes.ext"] .input-text', function () {
                //D-172420 Phone Number Ext Field Type Not to change
                if (!window.d172420_extNumberTypeChange) {
                    $('div[name="shippingAddress.custom_attributes.ext"] .input-text').prop('type', 'number');
                }
                $('div[name="shippingAddress.custom_attributes.ext"] .input-text').prop('maxlength', '4');
            });

            $(document).on('click', '.place-pickup-order', function () {
                $('.error-container').addClass('api-error-hide');
                $('.change-deliv-method-msg-outer-most').hide();
            });

            $(document).on('click', '#checkout-ship-button', function () {
                $('.error-container').addClass('api-error-hide');
                $('.change-deliv-method-msg-outer-most').hide();
            });

            $(document).on('click', '#place-pickup-order-mobile', function () {
                self.placePickupOrder();
                $(this).removeClass("btn-xs-mdevice").addClass("btn-xs-tdevice").css("display", "none");
            });

            $(document).on('click', '.checkout-shipping-method .opc-free-ground-promo-box .fa-times', function () {
                $('.opc-free-ground-promo-box').hide();
            });

            $(document).on('click', '.checkout-shipping-method .opc-local-delivery-method-box .fa-times', function () {
                $('.opc-local-delivery-method-box').hide();
            });

            $(document).on('click', '.img-close-pop', function (e) {
                $('.error-container').addClass('api-error-hide');
            });

            $(document).on('click', '.checkout-shipping-method .covid-19-message-promo-box .fa-times', function () {
                $('.covid-19-message-promo-box').hide();
            });

            $(document).on('click', '#checkout-ship-button', function () {
                $(this).removeClass("btn-xs-mdevice").addClass("btn-xs-tdevice");
                $("#shipping-method-sidebar-container").css("display", "none");
                let expressCheckout;
                if (window.e383157Toggle) {
                    expressCheckout = fxoStorage.get("express-checkout");
                } else {
                    expressCheckout = localStorage.getItem("express-checkout");
                }
                if (!self.pickupShippingComboKey() && self.isMixedQuote() && expressCheckout === 'false') {
                    self.setShippingInformation(false);
                } else {
                    self.setShippingInformation();
                }
            });

            // B-2616589 Set default production location if available
            $(window).on('load', function () {
                if (!togglesAndSettings.mazegeeksE482379AllowCustomerToChooseProductionLocationUpdates) {
                    $('.checkout-delivery-locations').show();
                    
                    var oldUiRestrictedData = togglesAndSettings?.restrictedProductionLocation;
                    
                    if (oldUiRestrictedData) {
                        try {
                            oldUiRestrictedData = JSON.parse(oldUiRestrictedData);
                            if (oldUiRestrictedData && oldUiRestrictedData[0]) {
                                // Check if user already has a stored selection
                                let selectedProductionId;
                                if (window.e383157Toggle) {
                                    selectedProductionId = fxoStorage.get('selected_production_id');
                                } else {
                                    selectedProductionId = localStorage.getItem('selected_production_id');
                                }
                                
                                // Verify if the stored location is in the restricted list
                                let isLocationValid = false;
                                if (selectedProductionId && selectedProductionId !== "") {
                                    // Compare as strings since location_id might be string or number
                                    isLocationValid = oldUiRestrictedData.some(loc => String(loc.location_id) === String(selectedProductionId));
                                }
                                
                                // Clear invalid cache or set default if no location selected
                                if (!selectedProductionId || selectedProductionId === "" || !isLocationValid) {
                                    if (window.e383157Toggle) {
                                        fxoStorage.delete('selected_production_id');
                                        fxoStorage.delete('selected_production_locationname');
                                        fxoStorage.delete('selected_production_locationadress');
                                        fxoStorage.delete('user_selected_prod_location');
                                        fxoStorage.delete('pl_nearest_location');
                                        fxoStorage.delete('product_location_option');
                                    } else {
                                        localStorage.removeItem('selected_production_id');
                                        localStorage.removeItem('selected_production_locationname');
                                        localStorage.removeItem('selected_production_locationadress');
                                        localStorage.removeItem('user_selected_prod_location');
                                        localStorage.removeItem('pl_nearest_location');
                                        localStorage.removeItem('product_location_option');
                                    }
                                }
                                
                                $('.checkout-delivery-locations .prod-location-item[value="choose_self"]').prop('checked', true).trigger('click');
                            }
                        } catch (e) {
                            console.warn('Failed to parse restrictedProductionLocation:', e);
                        }
                    }
                    
                    return;  
                }

                let restrictedStoreData = togglesAndSettings?.restrictedProductionLocation;

                // Check if restricted store data exists and is not empty
                if (restrictedStoreData) {
                    try {
                        restrictedStoreData = JSON.parse(restrictedStoreData);
                        
                        if (restrictedStoreData && restrictedStoreData[0]) {
                            // Check if user manually selected a location
                            let userSelectedLocationData;
                            if (window.e383157Toggle) {
                                userSelectedLocationData = fxoStorage.get("user_selected_prod_location");
                            } else {
                                userSelectedLocationData = localStorage.getItem("user_selected_prod_location");
                            }
                            
                            let locationId, locationName, locationFullAddress;
                            
                            // If user has a manual selection, use the exact saved data
                            if (userSelectedLocationData) {
                                try {
                                    let savedData = JSON.parse(userSelectedLocationData);
                                    let locationExists = restrictedStoreData.find(loc => loc.location_id === savedData.locationId);
                                    
                                    if (locationExists) {
                                        locationId = savedData.locationId;
                                        locationName = savedData.locationName;
                                        locationFullAddress = savedData.locationFullAddress;
                                        
                                        // update the old storage keys so checkAndRestoreProductionLocation uses correct data
                                        if (window.e383157Toggle) {
                                            fxoStorage.set('selected_production_id', locationId);
                                            fxoStorage.set('selected_production_locationname', locationName);
                                            fxoStorage.set('selected_production_locationadress', locationFullAddress);
                                        } else {
                                            localStorage.setItem('selected_production_id', locationId);
                                            localStorage.setItem('selected_production_locationname', locationName);
                                            localStorage.setItem('selected_production_locationadress', locationFullAddress);
                                        }
                                    }
                                } catch (e) {
                                    console.warn('Failed to parse user_selected_prod_location:', e);
                                }
                            }
                            
                            // If no manual selection or it doesn't exist, use default
                            if (!locationId) {
                                let selectedLocation = restrictedStoreData[0];
                                locationId = selectedLocation.location_id;

                                // Format location name with ID
                                if (explorersRestrictedAndRecommendedProduction) {
                                    locationName = selectedLocation.location_name + ' (' + selectedLocation.location_id + ')';
                                } else {
                                    locationName = selectedLocation.location_name;
                                }
                                
                                // Use full address format
                                locationFullAddress = selectedLocation.address1 + ', ' + selectedLocation.city + ', ' +
                                    selectedLocation.state + ', ' + selectedLocation.postcode;
                            }

                            if (window.e383157Toggle) {
                                fxoStorage.set("pl_nearest_location", locationId);
                                // store in the old keys for checkAndRestoreProductionLocation
                                fxoStorage.set('selected_production_id', locationId);
                                fxoStorage.set('selected_production_locationname', locationName);
                                fxoStorage.set('selected_production_locationadress', locationFullAddress);
                            } else {
                                localStorage.setItem("pl_nearest_location", locationId);
                                // store in the old keys for checkAndRestoreProductionLocation
                                localStorage.setItem('selected_production_id', locationId);
                                localStorage.setItem('selected_production_locationname', locationName);
                                localStorage.setItem('selected_production_locationadress', locationFullAddress);
                            }

                            // Hide default description and show selected location block
                            $(".simplified-production-location .prodloc-desc").hide();
                            $(".simplified-production-location .prodloc-selected").show();

                            self.setLocationData(locationId, locationName, locationFullAddress);
                            return;
                        }
                    } catch (e) {
                        console.warn('Failed to parse restrictedProductionLocation for new UI:', e);
                    }
                }
            });

            $(document).on('click', '.checkout-delivery-locations .prod-location-item', function () {
                $(".choose_self_container").hide();
                var selectedOption = $(this).val();
                if (window.e383157Toggle) {
                    fxoStorage.set('product_location_option', selectedOption);
                } else {
                    localStorage.setItem('product_location_option', selectedOption);
                }
                if (selectedOption == 'choose_self') {
                    let selectedProductionId;
                    if (window.e383157Toggle) {
                        selectedProductionId = fxoStorage.get('selected_production_id');
                    } else {
                        selectedProductionId = localStorage.getItem('selected_production_id');
                    }
                    if (selectedProductionId == "" ||
                        selectedProductionId == null) {

                        var shipmentzipcode = $('input[name="postcode"]').val();
                        /* D-101459 */
                        if (quote.shippingAddress()['postcode'] != undefined) {
                            shipmentzipcode = quote.shippingAddress()['postcode'];
                        }
                        var nearestLocation;
                        if (window.e383157Toggle) {
                            nearestLocation = fxoStorage.get("pl_nearest_location");
                        } else {
                            nearestLocation = localStorage.getItem("pl_nearest_location");
                        }

                        /** D-169768 | Selfreg_Shipping flow_Able to see text overlap and invalid data displaying after selecting Production Location**/

                        if (typeof (nearestLocation) != 'string' && shipmentzipcode != "" && nearestLocation != "" && nearestLocation != null) {
                            nearestLocation = JSON.parse(nearestLocation);
                            var locationFullAddress = nearestLocation.address1 + ', ' + nearestLocation.city + ', ' +
                                nearestLocation.state + ', ' + nearestLocation.postcode;
                            if (window.e383157Toggle) {
                                fxoStorage.set("pl_nearest_location", nearestLocation.location_id);
                            } else {
                                localStorage.setItem("pl_nearest_location", nearestLocation.location_id);
                            }
                            self.setLocationData(nearestLocation.location_id, nearestLocation.location_name, locationFullAddress);
                        } else {
                            var restrictedStoreData = togglesAndSettings?.restrictedProductionLocation;
                            if (restrictedStoreData) {
                                restrictedStoreData = JSON.parse(restrictedStoreData);
                            }

                            if (restrictedStoreData[0] != undefined && restrictedStoreData[0] != null) {
                                nearestLocation = restrictedStoreData[0];
                                var locationFullAddress = nearestLocation.address1 + ', ' + nearestLocation.city + ', ' +
                                    nearestLocation.state + ', ' + nearestLocation.postcode;
                                if (window.e383157Toggle) {
                                    fxoStorage.set("pl_nearest_location", nearestLocation.location_id);
                                } else {
                                    localStorage.setItem("pl_nearest_location", nearestLocation.location_id);
                                }
                                self.setLocationData(nearestLocation.location_id, nearestLocation.location_name, locationFullAddress);
                            }
                        }
                    }
                    $(".choose_self_container, .default-p-location, .change-p-location").show();
                }
            });
            $(document).on('change', "#shipping-new-address-form input[name^='company']", function () {
                if ($(".table-checkout-shipping-method").is(":visible")) {
                    self.triggerShippingResults();
                }
            });

            $(document).on('change', "#shipping-new-address-form .address-field .form-input", function () {
                var shippingMethodSection = $('div.checkout-shipping-method');
                if (shippingMethodSection.css('display') === 'block') {
                    shippingMethodSection.hide();
                }
            });

            $(document).on('keypress', ".alternate-check-container .checkmark", function (e) {
                let keycode = (e.keyCode ? e.keyCode : e.key);
                if (keycode === 13 || keycode === 32) {
                    $('.alternate-checkbox-container .alternate-pickup-checkbox').trigger('click');
                }
            });

            $(document).ready(function () {
                setTimeout(() => {
                    jQuery("input[name='custom_attributes[residence_shipping]']").prop("checked", false);
                }, 3000);
                if (isPersonalAddressBook) {
                    setTimeout(() => {
                        jQuery("input[name='custom_attributes[save_addressbook]']").prop("checked", false);
                    }, 3000);
                }
            });

            $(document).on('keypress', ".choice.field  input[name='custom_attributes[residence_shipping]']", function (e) {
                if (e.type === "keypress" && (e.which === 13 || e.which === 32)) {
                    e.preventDefault();
                    $(this).prop("checked", !$(this).prop("checked"));
                }
            });

            // B-2263187 :: POD2.0: Save data to address book from checkout page
            $(document).on('keypress', ".choice.field  input[name='custom_attributes[save_addressbook]']", function (e) {
                if (isPersonalAddressBook) {
                    if (e.type === "keypress" && (e.which === 13 || e.which === 32)) {
                        e.preventDefault();
                        $(this).prop("checked", !$(this).prop("checked"));
                    }
                }
            });

            // B-2263103 :: POD2.0: Design select address modal in checkout page
            $(document).on('click', ".add-from-addressbook-btn", function (e) {
                e.preventDefault();
                personalAddressBook.openPersonalAddressBookModal();
                if (parseInt($(window).width()) >= 375 && parseInt($(window).width()) <= 767) {
                    var requestUrl = urlBuilder.build("personaladdressbook/index/addressbookpage");
                    var searchHtml = '';
                    var tbodyHtml = '';
                    $.ajax({
                        url: requestUrl,
                        type: "POST",
                        data: { pageSize: 10, currentPage: 1, setPageSize: true },
                        showLoader: true,
                        dataType: 'json',
                        success: function (response) {
                            if (response.error_msg || response.errors) {
                                $(".succ-msg").hide();
                                $(".err-msg .message").text("System error, Please try again.");
                                $(".err-msg").show();
                            } else {
                                $(".addressbookheader").hide();
                                tbodyHtml = '<tr><td colspan="9">No Record Found.</td></tr>';
                                $('tbody.addressbookdatacheckout').html(tbodyHtml);
                                var responseData = response.data;
                                if (typeof responseData !== 'undefined' && responseData.length) {
                                    responseData.forEach(function (item, index) {
                                        var contactID = typeof item.contactID !== 'undefined' ? item.contactID : 0;
                                        var firstName = typeof item.firstName !== 'undefined' ? item.firstName : '';
                                        var lastName = typeof item.lastName !== 'undefined' ? item.lastName : '';
                                        var fullName = lastName + ', ' + firstName;
                                        var companyName = typeof item.companyName !== 'undefined' ? item.companyName : '';
                                        var streetLines = typeof item.address.streetLines[0] !== 'undefined' ? item.address.streetLines[0] : '';
                                        var stateOrProvinceCode = typeof item.address.stateOrProvinceCode !== 'undefined' ? item.address.stateOrProvinceCode : '';
                                        var city = typeof item.address.city !== 'undefined' ? item.address.city : '';
                                        var postalCode = typeof item.address.postalCode !== 'undefined' ? item.address.postalCode : '';
                                        var address = streetLines + '</br>' + city + ',' + stateOrProvinceCode + '' + postalCode;
                                        searchHtml += '<tr>';
                                        searchHtml += '<td>';
                                        searchHtml += '<table class="addressbook-inner-table"><tr><td class="data-grid-radio-cell"><label class="data-grid-radio-cell-inner"><input class="admin__control-radio custom_row_radio" type="radio" data-action="select-row" id="idscheck' + contactID + '" value="' + contactID + '"><input id="contactID" type="hidden" name="contactIDs[]" value="' + contactID + '"></label></td></tr><tr><td><span class="addressbook-inner-head">First Name</span></td><td><span>' + firstName + '</span></td></tr>';
                                        searchHtml += '<tr><td><span class="addressbook-inner-head">Last Name</span></td><td><span>' + lastName + '</span></td></tr>';

                                        searchHtml += '<tr><td><span class="addressbook-inner-head">Compay</span></td><td><span>' + companyName + '</span></td></tr>';

                                        searchHtml += '<tr><td><span class="addressbook-inner-head">Address</span></td><td><span>' + streetLines + '</span></td></tr>';

                                        searchHtml += '<tr><td><span class="addressbook-inner-head">CITY</span></td><td><span>' + city + '</span></td></tr>';

                                        searchHtml += '<tr><td><span class="addressbook-inner-head">STATE</span></td><td><span>' + stateOrProvinceCode + '</span></td></tr>';
                                        searchHtml += '<tr><td><span class="addressbook-inner-head">ZIP</span></td><td><span>' + postalCode + '</span></td></tr>';
                                        searchHtml += '</table></td>';
                                        searchHtml += '</tr>';
                                    });
                                } else {
                                    searchHtml += '<tr><td colspan="9">No Record Found.</td></tr>';
                                }
                                $('tbody.addressbookdatacheckout').html(searchHtml);
                            }
                        }
                    });
                }
            });

            //B-2263196 :: POD2.0: Update checkout shipping form from selected address from address book
            $(document).on('click', ".add-address-book-popup-select", function (e) {
                e.preventDefault();
                $(".choice.field").has("input[name='custom_attributes[save_addressbook]']").hide();
                personalAddressBook.setDataFromPersonalAddressBook(addAddressbookData);
            });

            $(document).on('change', ".custom_row_radio", function (e) {
                e.preventDefault();
                $(".add-address-book-popup-select").removeClass('disabled');
                $(".add-address-book-popup-select").css("background", "#FF6200");
                $(".add-address-book-popup-select").css("border", "2px solid #FF6200");
                $(".add-address-book-popup-select").css("color", "#FFFFFF");
                let city = null,
                    stateCode = null,
                    pinCode = null,
                    company = null,
                    firstName = null,
                    lastName = null,
                    street1 = '';
                let contactId = $(this).val();
                let idSelector = "#tr-" + contactId;
                var dataRow = $(idSelector);
                let name = dataRow.find('td[data-th="FIRST NAME"] .data-grid-cell-content').text().trim() || null;
                let nameParts = name.split(',');
                firstName = nameParts[1];
                lastName = nameParts[0];
                company = dataRow.find('td[data-th="COMPANY"]').text().trim() || null;
                let addressOne = dataRow.find('td[data-th="ADDRESS"] .data-grid-cell-content').html().replace(/<br>/g, ', ').trim();
                let addressTwo = dataRow.find('td[data-th="ADDRESS"] .data-grid-cell-content').text().trim() || null;
                let addressPart2 = addressTwo.split(',');
                let addressPart1 = addressOne.split(',');
                street1 = addressPart1[0].trim();
                city = addressPart1[1].trim();
                let stateZipcode = addressPart2.slice(1).join(', ').trim();
                let stateZipcodeArray = stateZipcode.split(' ');
                stateCode = stateZipcodeArray[0].trim();
                pinCode = stateZipcodeArray[1].trim();
                addAddressbookData =
                {
                    firstName: firstName,
                    lastName: lastName,
                    street1: street1,
                    company: company,
                    phoneNumber: contactId,
                    city: city,
                    pinCode: pinCode,
                    stateCode: stateCode
                };
            });

            $(document).on('click', "#personal", function (e) {
                e.preventDefault();
                personalAddressBook.openTab(e, 'Personal');
            });

            //B-2263203 :: POD2.0: Pagination Implement on Checkout-Address book page
            $(document).on('click', ".next-page", function (e) {
                e.preventDefault();
                let resultsPerPage = $('#resultsPerPage').val();
                let currentPage = parseInt($('#currentPage').val(), 10);
                let nextPage = currentPage + 1;
                $('#currentPage').val(nextPage);
                $('#currentPage').trigger("change");
                personalAddressBook.appyPaginationOnAddressBookPopup(resultsPerPage, nextPage, 'next');
            });

            $(document).on('click', ".prev-page", function (e) {
                e.preventDefault();
                let resultsPerPage = $('#resultsPerPage').val();
                let currentPage = parseInt($('#currentPage').val(), 10);
                let prevPage = currentPage - 1;
                $('#currentPage').val(prevPage);
                $('#currentPage').trigger("change");
                personalAddressBook.appyPaginationOnAddressBookPopup(resultsPerPage, prevPage, 'prev');
            });

            $(document).on('change', "#resultsPerPage", function (e) {
                let resultsPerPage = $(this).val();
                let currentPage = 1;
                let totalRecords = $('#totalRecords').val();
                personalAddressBook.resetTotalPages(resultsPerPage, totalRecords);
                personalAddressBook.appyPaginationOnAddressBookPopup(resultsPerPage, currentPage, 'rows_change');
            });
            // B-2263103 :: end

            $(document).on('click', '#change-p-location', function (e) {
                e.preventDefault();
                
                var options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: 'Choose Production Location',
                    modalClass: 'production-location-popup',
                    buttons: [{
                        text: $.mage.__('Cancel'),
                        class: shippingLocationCancelButton,
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        text: $.mage.__('Save Changes'),
                        class: shippingLocationSaveButton,
                        click: function () {
                            this.closeModal();
                        }
                    }
                    ]
                };

                $('.delivery-locations-info').modal(options).modal('openModal');
            });

            $(document).on('keyup onkeypress', 'input[name="popup-zipcode"]', function () {
                if (event.keyCode === 8 || event.charCode >= 48 && event.charCode <= 57) {
                    return false;
                }
                if ($(this).val().length == 5) {
                    self.showRecommendedLocationForShipment();
                } else if ($(this).val().length > 5) {
                    var zipval = $(this).val();
                    $(this).val(zipval.substr(0, 5));
                }
            });
            $(document).on('click', '.delivery-locations-info .toggle-outter .toggle-switch', function () {
                var mapShowOption = $(this).find('.toggle-input');
                if (mapShowOption.prop('checked') == true) {
                    $(this).parents('.delivery-locations-info').find('.map-canvas').show();
                } else {
                    $(this).parents('.delivery-locations-info').find('.map-canvas').hide();
                }
            });

            self.setTimeSlots();

            let isCheckoutConfig = typeof (window.checkoutConfig) !== 'undefined' && window.checkoutConfig !== null ? true : false;
            let isXmenD177346Fix = false;
            if (isCheckoutConfig) {
                isXmenD177346Fix = typeof (window.checkoutConfig.xmen_D177346_fix) != 'undefined' && window.checkoutConfig.xmen_D177346_fix != null ? window.checkoutConfig.xmen_D177346_fix : false;
            }
            if (isXmenD177346Fix) {
                $(document).on('click', '.continue-payment-btn', function () {
                    // B-2263187 :: POD2.0: Save data to address book from checkout page
                    if (explorers_e450676_personal_address_book) {
                        var checkoutShippingDetails = customerData.get('checkout-data')();
                        var shippingFormDetails = checkoutShippingDetails.shippingAddressFromData;
                        var isResidenceShipping = false;
                        if (typeof shippingFormDetails !== 'undefined' && shippingFormDetails != null && typeof shippingFormDetails.custom_attributes !== 'undefined' && shippingFormDetails.custom_attributes != null && shippingFormDetails.custom_attributes !== '' && typeof shippingFormDetails.custom_attributes.save_addressbook !== 'undefined') {
                            isSaveAddressbook = shippingFormDetails.custom_attributes.save_addressbook;
                        }
                        if (typeof shippingFormDetails !== 'undefined' && shippingFormDetails != null && typeof shippingFormDetails.custom_attributes !== 'undefined' && shippingFormDetails.custom_attributes != null && shippingFormDetails.custom_attributes !== '' && typeof shippingFormDetails.custom_attributes.residence_shipping !== 'undefined') {
                            isResidenceShipping = shippingFormDetails.custom_attributes.residence_shipping;
                        }
                        if (isSaveAddressbook) {
                            var firstName = shippingFormDetails.firstname;
                            var lastName = shippingFormDetails.lastname;
                            var streetLines = shippingFormDetails.street;
                            var city = shippingFormDetails.city;
                            var zipCode = shippingFormDetails.postcode;
                            var company = shippingFormDetails.company;
                            var telephone = shippingFormDetails.telephone;
                            var stateCode = $('select[name="region_id"] option[value=\"' + shippingFormDetails.region_id + '\"]').data('title');
                            let requestUrl = urlBuilder.build("personaladdressbook/index/saveaddressbook");
                            var postData = {
                                nickName: firstName + '' + lastName,
                                firstName: firstName,
                                lastName: lastName,
                                localNumber: telephone,
                                streetLines: streetLines,
                                city: city,
                                stateOrProvinceCode: stateCode,
                                postalCode: zipCode,
                                countryCode: "US",
                                residential: isResidenceShipping,
                                type: "FAX",
                                companyName: company,
                                opCoTypeCD: "EXPRESS_AND_GROUND",
                                isSaveForEdit: 0,
                                contactID: 0
                            };
                            $.ajax({
                                url: requestUrl,
                                type: "POST",
                                data: postData,
                                showLoader: true,
                                dataType: 'json',
                                success: function (response) {
                                    if (response.error_msg || response.errors) {
                                        personalAddressBook.showWarning("Your address could not be saved to your address book at this time.You can continue to check out");
                                    } else {
                                        personalAddressBook.showToast("Address has been successfully added to PersonalAddressBook.");
                                    }
                                }
                            });
                        }
                    }
                    let isAlternateFlag = $('.alternate-contact-form').css('display') == 'none' ? false : true;
                    if (window.e383157Toggle) {
                        fxoStorage.set('isAlternateFlag', isAlternateFlag);
                    } else {
                        localStorage.setItem('isAlternateFlag', isAlternateFlag);
                    }
                });
            }

            $(document).on('change', 'input[name=contact-form]:radio', function (e) {
                let value = e.target.value.trim();
                switch (value) {
                    case 'isSame':
                        $('.alternate-contact-form').hide();
                        isalternate = false

                        break;
                    case 'isNotsame':
                        $('.alternate-contact-form').show();
                        isalternate = true

                        break;
                    default:
                        break;
                }
                self.isContactFormValid();
            });

            $(document).on('input', 'input[name=telephone]', function (e) {
                var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
            });

            $(document).on('input', '.contact-number', function (e) {
                var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
            });

            $(document).on('input', '.alternate-number', function (e) {
                var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
            });

            $(document).on('input', '#alternate_phonenumber', function (e) {
                var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
                e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
            });
            /**
             * Review Order Edit Flow jQuery listeners
             */
            $(document).on('click', '.checkout-sub.edit_pickup', function () {
                self.onclickTriggerShip(null, null, true);
            });
            $(document).on('click', '.checkout-sub.edit_ship', function () {
                self.onclickTriggerPickup(null, null, true);
            });

            /**
             * D-164105 : Removed the condition isFclCustomer as this is expected for all
             */
            $(document).on('click', '.go-to-pickup', function () {
                $(".opc-progress-bar li").eq(0).find("span").trigger("click");
                return false;
            });

            $(document).on('click', '.edit_shipping_method', function () {
                var elmnt = document.getElementById("opc-shipping_method");
                elmnt.scrollIntoView();
            });

            $(document).on('keyup blur', 'input[name=alternate_firstname]', function (e) {
                let isValid, errorMessage;
                // Tech Titans - D-221338 fix Toggle
                if (window?.checkoutConfig?.tech_titans_d221338) {
                    [isValid, errorMessage] = self.validateInputNameField(e.target, e);
                } else {
                    [isValid, errorMessage] = this.validateInputNameField(e.target, e);
                }

                isFirstnameValid = isValid;

                if (isFirstnameValid) {
                    $("#firstname_validate").empty();
                } else {
                    $("#firstname_validate").html(errorMessage);
                }

                self.isContactFormValid();
            });

            $(document).on('keyup blur', 'input[name=alternate_lastname]', function (e) {
                let isValid, errorMessage;
                // Tech Titans - D-221338 fix Toggle
                if (window?.checkoutConfig?.tech_titans_d221338) {
                    [isValid, errorMessage] = self.validateInputNameField(e.target, e);

                } else {
                    [isValid, errorMessage] = this.validateInputNameField(e.target, e);
                }

                isLastnameValid = isValid;

                if (isLastnameValid) {
                    $("#lastname_validate").empty();
                } else {
                    $("#lastname_validate").html(errorMessage);
                }

                self.isContactFormValid();
            });

            $(document).on('keyup blur', 'input[name=alternate_phonenumber]', function (e) {
                let value = e.target.value.trim();
                if (value.length == 0) {
                    $("#phonenumber_validate").html('Phone Number is required.');
                    isPhonenameValid = false;
                } else if (value.length === 14 && isNaN(value)) {
                    $("input[name=alternate_phonenumber]").attr("maxlength", "14");
                    $("#phonenumber_validate").empty();
                    isPhonenameValid = true;
                } else if (value.length < 14 || isNaN(value)) {
                    $("#phonenumber_validate").html('Please enter a valid Phone Number.');
                    isPhonenameValid = false;
                } else {
                    $("#phonenumber_validate").empty();
                    isPhonenameValid = true;
                }
                self.isContactFormValid();
            });

            $(document).on('keyup blur', 'input[name=alternate_email]', function (e) {
                let inputText = e.target.value.trim();
                let pattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
                if (inputText.length == 0) {
                    $("#email_validate").html('Email id is required.');
                    isEmailValid = false
                } else if (!inputText.match(pattern)) {
                    $("#email_validate").html('Please enter a valid email address.');
                    isEmailValid = false;
                } else if (inputText.length > 150) {
                    $("#email_validate").html('Email address should not be greater than 150 characters.');
                    isEmailValid = false;
                } else {
                    $("#email_validate").empty();
                    isEmailValid = true;
                }
                self.isContactFormValid();
            });
            if (window.e383157Toggle) {
                fxoStorage.set('isAddressValidated', false);
            } else {
                localStorage.setItem('isAddressValidated', false);
            }
            window.addressValidated = false;
            $(document).on("click", "#get-Shipping-result", async function (e) {
                var tigerE486666 = togglesAndSettings['tiger_e486666'] !== undefined ? togglesAndSettings['tiger_e486666'] : false;
                if (window.checkoutConfig.tigerteamE469373enabled && window.addressValidated && !tigerE486666) {
                    const shippingAccountNumber = self.shippingAccountNumber();
                    if (shippingAccountNumber) {
                        if (window.e383157Toggle) {
                            fxoStorage.set('shipping_account_number', shippingAccountNumber);
                        } else {
                            localStorage.setItem('shipping_account_number', shippingAccountNumber);
                        }
                        const validationResult = await self.validateShippingAccountNumber(shippingAccountNumber);
                        if (validationResult?.status?.error === false) {
                            window.checkoutConfig.isValidShippingAccount = true;
                            $("#fedExAccountNumber_validate").removeClass('error-icon').text('');
                        } else {
                            window.checkoutConfig.isValidShippingAccount = false;
                            if (validationResult?.status?.error === true && validationResult?.status?.message !== undefined) {
                                $("#fedExAccountNumber_validate").addClass('error-icon').text('Unable to validate due to system error. Please try again.');
                            } else if (validationResult?.status?.error === true && validationResult?.status?.message === undefined) {
                                $("#fedExAccountNumber_validate").addClass('error-icon').text('Please enter a valid shipping account number.');
                            }
                        }
                    }
                }
                if (window.d196640_toggle) {
                    if (window.addressValidated == true) {
                        fxoStorage.set('isAddressValidated', window.addressValidated);
                        window.addressValidated = false;
                    } else {
                        fxoStorage.set('isAddressValidated', window.addressValidated);
                    }
                }

                if (!self.validateShippingAccountAcknowledgementCheckbox()) {
                    return;
                }

                self.firstPartyMethod('');
                self.thirdPartyMethod('');
                let event = e.originalEvent;
                $("#shipping-method-buttons-container").hide();
                if (!self.checkIsCommercialCustomer() || isSdeStore || isSelfRegCustomer) {
                    var unsetDeliveryOptionSession = urlBuilder.build('delivery/quote/unsetdeliveryoptionsession');
                    $.ajax({
                        type: "POST",
                        url: unsetDeliveryOptionSession,
                        data: [],
                        cache: false,
                        showLoader: true
                    }).done(function (data) {
                        if (data == true) {
                            $('.error-container').addClass('api-error-hide');
                            let shipInfoValidate = self.shippingInformationValidation();
                            if (self.enableEarlyShippingAccountIncorporation()) {
                                shippingFormAdditionalScript.shipInfoValidate(self, shipInfoValidate);
                            }
                            /** D-96383 **/
                            if ($('.shipping-address-item').length) {
                                selectShippingAddress(quote.shippingAddress());
                            }
                        } else {
                            return false;
                        }
                    });
                } else {
                    $('.error-container').addClass('api-error-hide');
                    let shipInfoValidate = self.shippingInformationValidation();
                    if (self.enableEarlyShippingAccountIncorporation()) {
                        shippingFormAdditionalScript.shipInfoValidate(self, shipInfoValidate);
                    }
                    /** D-96383 **/
                    if ($('.shipping-address-item').length) {
                        selectShippingAddress(quote.shippingAddress());
                    }
                }
                /** Address Validation Popup */
                var requestUrl = urlBuilder.build("shippingaddressvalidation/index/addressvalidate");
                var googleSuggestedAddress = shippingFormAdditionalScript.getGoogleSuggestedShippingAddress();
                var checkoutData = customerData.get('checkout-data')();
                var shippingFormData = checkoutData.shippingAddressFromData;
                shippingFormData = shippingFormAdditionalScript.getValidFormData(shippingFormData, googleSuggestedAddress);

                if (!self.pickupShippingComboKey()) {
                    disclosureModel.setShouldDisplayQuestionnaire(shippingFormData.region);
                }

                if (!togglesAndSettings.isRecipientAddressEnable && typeof event !== 'undefined') {
                    shippingFormAdditionalScript.getAddress(requestUrl, shippingFormData, false, function (response) {
                        if (typeof response == 'object') {
                            let validatedAddress;
                            if (window.e383157Toggle) {
                                fxoStorage.set('validatedAddress', response);
                                fxoStorage.set('shippingFormAddress', shippingFormData);
                                validatedAddress = fxoStorage.get("validatedAddress");
                            } else {
                                localStorage.setItem('validatedAddress', JSON.stringify(response));
                                localStorage.setItem('shippingFormAddress', JSON.stringify(shippingFormData));
                                validatedAddress = localStorage.getItem("validatedAddress");
                                validatedAddress = JSON.parse(validatedAddress);
                            }
                            if (validatedAddress != null && typeof validatedAddress.output != 'undefined') {
                                shippingFormAdditionalScript.openAddressValidationModal();
                            }
                        }
                    });

                    if (self.isSimplifiedProductionLocationOn() && !self.hasSelectedProdLoc()) {
                        let restrictedStoreData = togglesAndSettings?.restrictedProductionLocation;
                        let recommendedStoreData = window.checkoutConfig?.recommended_production_location;
                        let storeData = false;
                        if (restrictedStoreData) {
                            storeData = JSON.parse(restrictedStoreData);
                        } else if (recommendedStoreData) {
                            storeData = JSON.parse(recommendedStoreData);
                        }

                        if (storeData[0] !== undefined && storeData[0] !== null) {
                            let nearestLocation = storeData[0];
                            let locationFullAddress = nearestLocation.address1 + ', ' + nearestLocation.city + ', ' +
                                nearestLocation.state + ', ' + nearestLocation.postcode;
                            self.persistSelectedProductionLocation(nearestLocation.location_id, nearestLocation.location_name, locationFullAddress);
                        }
                        isProductionLocationAutomaticallySelected = true;
                    }
                }
                if (togglesAndSettings.isRecipientAddressEnable) {
                    if (window.e383157Toggle) {
                        fxoStorage.set('isAddressValidated', true);
                    } else {
                        localStorage.setItem('isAddressValidated', true);
                    }
                }
            });
            $(document).on('blur', '.fedex_account_number-field', function () {
                var acc = this.value.trim();
                self.shippingAccountNumber(acc);
                // Mask shipping account number in field
                shippingFormAdditionalScript.maskShippingAccountNumber(acc);
                if (acc.length) {
                    self.firstPartyMethod('');
                    self.thirdPartyMethod('');
                    $("#shipping-method-buttons-container").hide();
                }
            });

            $(document).on('focus', '.fedex_account_number-field', function () {
                let shippFedexAccNum = $('.fedex_account_number-field').val();
                var acc = shippFedexAccNum ? self.shippingAccountNumber() : self.shippingAccountNumber('');
                if (acc.length > 0 && self.isMaskonAutopopulate() === false) {
                    var acc = $('.fedex_account_number-field').val(self.shippingAccountNumber());
                }
                let isSelfRegSDE = (isSdeStore || isSelfRegCustomer) ? true : false;
                if (self.enableEarlyShippingAccountIncorporation() && self.isMaskonAutopopulate() && isSelfRegSDE) {
                    shippingFormAdditionalScript.maskShippingAccountNumber(shippFedexAccNum);
                }
            });

            $(document).on('change input', '.fedex_account_number-field', function (e) {
                if (e.target.value.length) {
                    $("#addFedExAccountNumberButton").prop("disabled", false);
                } else {
                    $("#addFedExAccountNumberButton").prop("disabled", true);
                }
            });

            $(document).on("keyup", '.fedex_account_number-field', function (e) {
                let keyCode = e.keyCode || e.which;
                // B-1517822 | Allow Shipping account number for SelfReg
                if (isSdeStore || isSelfRegCustomer) {
                    if ($(".fedex_account_number-field").val() == '') {
                        $("#fedExAccountNumber_validate").html('This is a required field.');
                    } else {
                        if (window.checkoutConfig.tigerteamE469373enabled) {
                            $("#fedExAccountNumber_validate").removeClass('error-icon');
                        }
                        $("#fedExAccountNumber_validate").empty();
                    }
                }
                if ($(".fedex_account_number-field").val().length == 0) {
                    disclosureModel.isCampaingAdDisclosureToggleEnable
                        ? self.disableCreateQuoteBtn(false)
                        : $(".create_quote").prop("disabled", false);
                    if (keyCode != 13) {
                        if (window.checkoutConfig.tigerteamE469373enabled) {
                            $("#fedExAccountNumber_validate").removeClass('error-icon');
                        }
                        $("#fedExAccountNumber_validate").empty();
                    }
                }
            });

            /**
             * Trigger apply fedex account once enter or space key is pressed
             */
            $(document).on('keypress', '#addFedExAccountNumberButton', function (e) {
                let keycode = (e.keyCode ? e.keyCode : e.which);
                if (keycode == 13 || keycode == 32) {
                    $('#addFedExAccountNumberButton').trigger('click');
                }
            });

            $(document).on("click", "#addFedExAccountNumberButton", function (f) {
                let applyRemove = 'apply';
                if (self.shippingAccountNumber().length == 0 || $('.fedex_account_number-field').val().length == 0) {
                    if (!self.enableEarlyShippingAccountIncorporation()) {
                        $("#fedExAccountNumber_validate").html('Fedex account number is required.');
                    } else {
                        if (window.e383157Toggle) {
                            fxoStorage.delete('isLocalDeliveryMethod');
                        } else {
                            localStorage.removeItem('isLocalDeliveryMethod');
                        }
                    }
                    return false;
                } else {
                    if (isFclCustomer) {
                        if (window.e383157Toggle) {
                            fxoStorage.set('shipping_account_number', self.shippingAccountNumber());
                        } else {
                            localStorage.setItem('shipping_account_number', self.shippingAccountNumber());
                        }
                    }
                    self.isApplyShippingAccountNumber(self.shippingAccountNumber(), applyRemove);
                }
            });

            /**
             * Trigger change delivery method toast message removal
             */
            $(document).on('click', '.img-close-msg', function () {
                $(".checkout-success-close-trigger-class").remove();
            });

            /**
             * Trigger remove local delivery toast message removal
             */
            $(document).on('keypress', '#closeLocalDeliveryMessage', function (e) {
                let keycode = (e.keyCode ? e.keyCode : e.which);
                if (keycode == 13 || keycode == 32) {
                    $('#closeLocalDeliveryMessage').trigger('click');
                }
            });

            $(document).on("click", "#closeLocalDeliveryMessage", function (f) {
                $(".modal-container.local-delivery-message").hide();
            });

            $(document).on("click", "#removeFedExAccountNumberButton", function (e) {
                let fedexAccountNumber = '';
                let applyRemove = 'remove';
                if (self.enableEarlyShippingAccountIncorporation()) {
                    $('.checkout-shipping-method').hide();
                    $('#closeLocalDeliveryMessage').trigger('click');
                    if (window.e383157Toggle) {
                        fxoStorage.delete('isLocalDeliveryMethod');
                    } else {
                        localStorage.removeItem('isLocalDeliveryMethod');
                    }
                    self.shippingAccountNumber(fedexAccountNumber);
                    if (window.checkoutConfig?.tiger_team_B_2429967) {
                        self.shippingAccountNumberPlaceHolder('');
                    }
                    $('#addFedExAccountNumberButton').prop("disabled", true);
                }
                if (isFclCustomer) {
                    if (window.e383157Toggle) {
                        fxoStorage.set('shipping_account_number', null);
                    } else {
                        localStorage.setItem('shipping_account_number', null);
                    }
                }
                self.isApplyShippingAccountNumber(fedexAccountNumber, applyRemove);
            });

            /**
             * Trigger remove fedex account once enter or space key is pressed
             */
            $(document).on('keypress', '#removeFedExAccountNumberButton', function (e) {
                let keycode = (e.keyCode ? e.keyCode : e.which);
                if (keycode == 13 || keycode == 32) {
                    $('#removeFedExAccountNumberButton').trigger('click');
                }
            });

            $(document).on('keyup', '#contact-ext,#alternate-ext,#alternate_ext', function (e) {
                let value = $(this).val().replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
                $(this).val(value);
            });


            if (self.isPromiseTimePickupOptionsToggle()) {
                $(document).on('change', 'label.custom-radio-btn.pick-up-button input[name="radio-button"]', function () {
                    // Selecting Standard Pickup radio button if no other Available Pickup Time radio buttons are selected
                    const selectedPickupLocation = $('label.custom-radio-btn.pick-up-button input[name="radio-button"]:checked');
                    const selectedPickupLocationContainer = selectedPickupLocation.parent().parent();
                    const selectedPickupRegionCode = $(selectedPickupLocationContainer).find('.pickup-location-state:eq(0)').text();

                    if (selectedPickupRegionCode) {
                        self.koPickupRegion(selectedPickupRegionCode);
                        !self.isMixedQuote() && disclosureModel.setShouldDisplayQuestionnaire(selectedPickupRegionCode);
                    }

                    if (selectedPickupLocationContainer.find('.pickup-option-type-button:checked').length === 0) {
                        const stdPickupRadioButton = selectedPickupLocationContainer.find('.standard-pickup-button');
                        $(stdPickupRadioButton).prop('checked', true);
                        $(stdPickupRadioButton).trigger('click');
                        // Remove selected Pickup Later radio button from prior pickup location if necessary
                        if (self.currentPickupLaterRadio()) {
                            self.currentPickupLaterRadio().siblings('#pickup_later_date').hide();
                            self.currentPickupLaterRadio(null);
                            self.koSelectedPickupLaterDate(null);
                        }
                    }
                });

                $(document).on('click', 'input[name="pickup_option_type"]', function () {
                    const selectedPickUpType = $('.pickup-option-type-button:checked');
                    const selectedPickupLocationLabel = selectedPickUpType.parent().parent().parent().siblings('label');
                    const selectedPickupLocation = selectedPickupLocationLabel.find('input[name="radio-button"]');
                    const isPickupLaterOption = selectedPickUpType.hasClass('pickup-later-button');
                    self.koFormattedPickupLaterDate(null);
                    // Handle Pickup Later radio button being selected for a prior location
                    if (isPickupLaterOption) {
                        if (self.currentPickupLaterRadio() && !self.currentPickupLaterRadio().is(selectedPickUpType)) {
                            self.currentPickupLaterRadio().siblings('#pickup_later_date').hide();
                            self.koSelectedPickupLaterDate(null);
                        }
                        self.currentPickupLaterRadio(selectedPickUpType);
                        self.koStandardDeliveryLocalTime($(this).val());
                    } else if (self.currentPickupLaterRadio()) {
                        self.currentPickupLaterRadio().siblings('#pickup_later_date').hide();
                        self.currentPickupLaterRadio(null);
                        self.koSelectedPickupLaterDate(null);
                    }
                    // Handle new pickup location being selected
                    if (!$(selectedPickupLocation).is(':checked')) {
                        if (isPickupLaterOption) {
                            self.openPickupTimeModal(true);
                        }
                        $(selectedPickupLocationLabel).trigger('click');
                        $(selectedPickupLocationLabel).trigger('change');
                    } else if (isPickupLaterOption) {
                        self.showPickupTimeModal();
                    }
                    if (self.isPromiseTimePickupOptionsToggle()) {
                        var locationId = selectedPickupLocationLabel.find('.pickup-location-id').text();
                        var location = self.pickUpJson().find(function (element) {
                            return element.location.id == locationId;
                        });
                        if (location && location.standardPriorityPickup && location.standardPriorityPickup.estimatedPickupLocalTime) {
                            self.koStandardPickupTime(location.standardPriorityPickup.estimatedPickupLocalTime);
                            self.koStandardDeliveryLocalTime(location.standardPriorityPickup.estimatedDeliveryLocalTime);
                        } else {
                            self.koStandardPickupTime(null);
                            self.koStandardDeliveryLocalTime(null);
                        }
                        // Store the selected date/time
                        let selectedDateTime;
                        let selectedDeliveryTimeForApi;
                        if (isPickupLaterOption) {
                            const formattedDate = self.koFormattedPickupLaterDate();
                            const pickupDate = self.koPickupDate();
                            selectedDateTime = formattedDate || pickupDate;
                            selectedDeliveryTimeForApi = pickupDate;
                        } else {
                            selectedDateTime = selectedPickUpType.closest('li').find('.pick-up-time-description span').first().text().trim();
                            selectedDeliveryTimeForApi = $(this).val() || selectedPickUpType.data('priority-print-time');
                        }
                        if (selectedDateTime) {
                            if (window.e383157Toggle) {
                                fxoStorage.set("pickupDateTime", selectedDateTime);
                                fxoStorage.set("pickupDateTimeForApi", selectedDeliveryTimeForApi);
                                fxoStorage.set("updatedChangedPickupDateTime", selectedDeliveryTimeForApi);
                            } else {
                                localStorage.setItem("pickupDateTime", selectedDateTime);
                                localStorage.setItem("pickupDateTimeForApi", selectedDeliveryTimeForApi);
                                localStorage.setItem("updatedChangedPickupDateTime", selectedDeliveryTimeForApi);
                            }
                        }
                    }
                });
                $(document).on('click', '.pickup-later-change-time', function () {
                    self.showPickupTimeModal();
                });
            }

            let selectedShippingId;
            if (window.e383157Toggle) {
                selectedShippingId = localStorage.getItem("selectedRadioShipping");
            } else {
                selectedShippingId = localStorage.getItem("selectedRadioShipping");
            }
            if (selectedShippingId) {
                window.addEventListener('popstate', function () {
                    // Shipping option not selected issue fix start
                    checkoutAdditionalScript.selectedDeliveryOptionChecked();
                    // Shipping option not selected issue fix stop
                }, true);

                intervalId = window.setInterval(function () {
                    // Shipping option not selected issue fix
                    checkoutAdditionalScript.selectedDeliveryOptionChecked();
                    $('input[value="' + selectedShippingId + '"]').trigger('click');
                    // Shipping option not selected issue fix start
                }, 1000);
            }


            $(document).ready(function () {
                self.setDefaultShipping();
                if (self.isCustomerLoggedIn()) {
                    $("#checkout-button-title, #checkout-ship-button-title").text("SUBMIT ORDER");
                } else {
                    $("#checkout-button-title, #checkout-ship-button-title").text("CONTINUE TO PAYMENT");
                }

                var restrictedStoreData = togglesAndSettings?.restrictedProductionLocation;
                if (restrictedStoreData) {
                    restrictedStoreData = JSON.parse(restrictedStoreData);
                }
                var ziplat, ziplng, nearestLocation = "", shippingZipcode = "";
                var nearestDistance = 9999999999999999;

                function getNearestDistance(postalcode) {

                    shippingZipcode = parseInt(postalcode);
                    nearestDistance = 9999999999999999;

                    var lengthZip = shippingZipcode.toString().length;

                    if (lengthZip == 5 && Math.floor(shippingZipcode) == shippingZipcode && restrictedStoreData) {

                        var geocoder = new google.maps.Geocoder();

                        geocoder.geocode({ 'address': shippingZipcode.toString() }, function (results, status) {

                            if (status == google.maps.GeocoderStatus.OK) {
                                ziplat = results[0].geometry.location.lat();
                                ziplng = results[0].geometry.location.lng();

                                if (ziplat != "" && ziplng != "") {

                                    restrictedStoreData.forEach(function (location) {

                                        var distance = shippingService.distance(ziplat, ziplng,
                                            location.lat,
                                            location.long, 'M').toFixed(2);
                                        if (nearestDistance > distance) {

                                            nearestDistance = distance;
                                            nearestLocation = location;
                                            if (window.e383157Toggle) {
                                                fxoStorage.set("pl_nearest_location", nearestLocation);
                                            } else {
                                                localStorage.setItem("pl_nearest_location", JSON.stringify(nearestLocation));
                                            }
                                        }

                                    });

                                    let selectedProductionId;
                                    if (window.e383157Toggle) {
                                        selectedProductionId = fxoStorage.get("selected_production_id");
                                    } else {
                                        selectedProductionId = localStorage.getItem("selected_production_id");
                                    }
                                    if (selectedProductionId == "" ||
                                        selectedProductionId == null &&
                                        nearestLocation != "" && nearestLocation != null) {
                                        var locationFullAddress = nearestLocation.address1 + ', ' + nearestLocation.city + ', ' +
                                            nearestLocation.state + ', ' + nearestLocation.postcode;
                                        if (window.e383157Toggle) {
                                            fxoStorage.set("pl_nearest_location", nearestLocation.location_id);
                                        } else {
                                            localStorage.setItem("pl_nearest_location", nearestLocation.location_id);
                                        }
                                        self.setLocationData(nearestLocation.location_id, nearestLocation.location_name, locationFullAddress);
                                    }
                                }

                            }
                        });

                    }
                }
                var intervalforNearestDis = setInterval(function () {
                    let selectedProductionId;
                    if (window.e383157Toggle) {
                        selectedProductionId = fxoStorage.get("selected_production_id");
                    } else {
                        selectedProductionId = localStorage.getItem("selected_production_id");
                    }
                    if ((selectedProductionId == "" || selectedProductionId == null) &&
                        $('.shipping-address-item.selected-item').length > 0 &&
                        quote.shippingAddress()['postcode'] != undefined) {

                        var quoteZipcode = quote.shippingAddress()['postcode'];

                        getNearestDistance(quoteZipcode);
                        clearInterval(intervalforNearestDis);
                    }

                }, 500);
                $(document).on('keyup', 'input[name="postcode"]', function () {
                    if ($('.checkout-delivery-locations .prod-location-item').length > 0) {
                        var quoteZipcode = $(this).val();
                        getNearestDistance(quoteZipcode);
                    }

                });

                let product_location_option;
                if (window.e383157Toggle) {
                    product_location_option = fxoStorage.get("product_location_option");
                } else {
                    product_location_option = localStorage.getItem("product_location_option");
                }
                if (product_location_option != "" && product_location_option != null) {
                    var findProductionSelector = setInterval(function () {
                        if ($('.checkout-delivery-locations .prod-location-item').length > 0) {
                            $('.checkout-delivery-locations .prod-location-item').each(function () {
                                if ($(this).val() == product_location_option) {

                                    if (product_location_option == 'choose_self') {
                                        let selectedProductionId;
                                        if (window.e383157Toggle) {
                                            selectedProductionId = fxoStorage.get("selected_production_id");
                                        } else {
                                            selectedProductionId = localStorage.getItem("selected_production_id");
                                        }
                                        if (selectedProductionId == "" ||
                                            selectedProductionId == null) {

                                            var nearestLocation;
                                            if (window.e383157Toggle) {
                                                nearestLocation = fxoStorage.get("pl_nearest_location");
                                            } else {
                                                nearestLocation = localStorage.getItem("pl_nearest_location");
                                            }
                                            /** D-169768 | Selfreg_Shipping flow_Able to see text overlap and invalid data displaying after selecting Production Location**/

                                            if (typeof (nearestLocation) != 'string' && nearestLocation != null && nearestLocation != "") {
                                                nearestLocation = JSON.parse(nearestLocation);

                                                var locationFullAddress = nearestLocation.address1 + ', ' + nearestLocation.city + ', ' +
                                                    nearestLocation.state + ', ' + nearestLocation.postcode;
                                                if (window.e383157Toggle) {
                                                    fxoStorage.set("pl_nearest_location", nearestLocation.location_id);
                                                } else {
                                                    localStorage.setItem("pl_nearest_location", nearestLocation.location_id);
                                                }
                                                self.setLocationData(nearestLocation.location_id, nearestLocation.location_name, locationFullAddress);
                                            } else {
                                                var restrictedStoreData = togglesAndSettings?.restrictedProductionLocation;
                                                if (restrictedStoreData) {
                                                    restrictedStoreData = JSON.parse(restrictedStoreData);
                                                }

                                                if (restrictedStoreData[0] != undefined && restrictedStoreData[0] != null) {

                                                    nearestLocation = restrictedStoreData[0];
                                                    var locationFullAddress = nearestLocation.address1 + ', ' + nearestLocation.city + ', ' +
                                                        nearestLocation.state + ', ' + nearestLocation.postcode;
                                                    if (window.e383157Toggle) {
                                                        fxoStorage.set("pl_nearest_location", nearestLocation.location_id);
                                                    } else {
                                                        localStorage.setItem("pl_nearest_location", nearestLocation.location_id);
                                                    }
                                                    self.setLocationData(nearestLocation.location_id, nearestLocation.location_name, locationFullAddress);
                                                }
                                            }
                                        }
                                        $(".choose_self_container, .default-p-location, .change-p-location").show();
                                    }
                                    let selectedProductLocationName;
                                    if (window.e383157Toggle) {
                                        selectedProductLocationName = fxoStorage.get("selected_production_locationname");
                                    } else {
                                        selectedProductLocationName = localStorage.getItem("selected_production_locationname");
                                    }
                                    if (product_location_option == 'choose_self' &&
                                        selectedProductLocationName != "" &&
                                        selectedProductLocationName != null
                                    ) {
                                        let locationId, locationName, locationFullAddress;
                                        if (window.e383157Toggle) {
                                            locationId = fxoStorage.get('selected_production_id');
                                            locationName = fxoStorage.get('selected_production_locationname');
                                            locationFullAddress = fxoStorage.get('selected_production_locationadress');
                                        } else {
                                            locationId = window.localStorage.getItem('selected_production_id');
                                            locationName = window.localStorage.getItem('selected_production_locationname');
                                            locationFullAddress = window.localStorage.getItem('selected_production_locationadress');
                                        }
                                        self.setLocationData(locationId, locationName, locationFullAddress);
                                        $(".default-p-location").show();

                                    } else if (product_location_option != 'choose_self') { /* D-101459 */

                                        $(".choose_self_container").show();
                                    }
                                    $(this).attr('checked', 'checked');
                                }
                            });
                            clearInterval(findProductionSelector);
                        }
                    }, 500);

                } else {

                    var findProductionSelector = setInterval(function () {
                        if ($('.checkout-delivery-locations .prod-location-item').length > 0) {
                            $('.checkout-delivery-locations .prod-location-item').each(function () {
                                if ($(this).val() == "choose_from_fedex_office") {
                                    $(this).attr('checked', 'checked');
                                    clearInterval(findProductionSelector);
                                }
                            });
                        }
                    }, 500);
                }

                let totalNetAmount;
                if (window.e383157Toggle) {
                    totalNetAmount = fxoStorage.get("EstimatedTotal");
                } else {
                    totalNetAmount = localStorage.getItem("EstimatedTotal");
                }
                if (totalNetAmount) {
                    setTimeout(() => {
                        $(".grand.totals.incl .price").text(totalNetAmount);
                        $(".grand.totals .amount .price").text(totalNetAmount);
                    }, 3000);
                }

            });

            if (!quote.isVirtual()) {
                var shipLabel = 'Delivery method';
                if (self.onclickTriggerPickupShow() == true) {
                    var shipLabel = 'Delivery method';
                }
                stepNavigator.registerStep(
                    "shipping",
                    "",
                    $t(shipLabel),
                    this.visible,
                    _.bind(this.navigate, this),
                    this.sortOrder
                );
                $(document).on("click", ".pickup-title-checkout .checkout-sub, .shipping-title-checkout .checkout-sub", function () {
                    var shippingStepTitle = 'Delivery method';
                    $(".opc-progress-bar li:first-child span").html(shippingStepTitle);
                });
                $(document).on("click", ".message-content a", function () {
                    $(".opc-progress-bar li:first-child span").html("Delivery method");
                });
            }
            checkoutDataResolver.resolveShippingAddress();

            hasNewAddress = addressList.some(function (address) {
                return address.getType() == "new-customer-address"; //eslint-disable-line eqeqeq
            });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                }
            });

            quote.shippingMethod.subscribe(function () {
                self.errorValidationMessage(false);
            });

            $(document).ajaxComplete(function () {
                if (isFclCustomer || isSelfRegCustomer) {
                    self.setCustomerShippingAddess();
                    var defaultShippingAddress = togglesAndSettings.fclCustomerDefaultShippingData;
                    var defaultRegionId = defaultShippingAddress['region'];
                    var region_id = $(".form-shipping-address select[name^='region_id']").val();
                    if (defaultRegionId && region_id == 1) {
                        $(".form-shipping-address select[name^='region_id']").val(defaultRegionId);
                    }
                }
                if (self.enableEarlyShippingAccountIncorporation()) {
                    self.showHideLocalDeliveryToastMessage();
                    self.hideremoveShippingAccountNumberwhennull();
                }
            });

            registry.async("checkoutProvider")(function (checkoutProvider) {
                var shippingAddressData = checkoutData.getShippingAddressFromData();

                if (shippingAddressData) {
                    checkoutProvider.set(
                        "shippingAddress",
                        $.extend(
                            true, {},
                            checkoutProvider.get("shippingAddress"),
                            shippingAddressData
                        )
                    );
                }
                checkoutProvider.on(
                    "shippingAddress",
                    function (shippingAddrsData) {
                        checkoutData.setShippingAddressFromData(
                            shippingAddrsData
                        );
                    }
                );
                shippingRatesValidator.initFields(fieldsetName);
            });

            $do.get('#step_code .payment-container .method-selection-container', function () {
                let expressCheckout;
                if (window.e383157Toggle) {
                    expressCheckout = fxoStorage.get("express-checkout");
                } else {
                    expressCheckout = localStorage.getItem("express-checkout");
                }
                if (expressCheckout === 'true') {
                    let preferredPaymentMethod = profileSessionBuilder.getPreferredPaymentMethod();

                    if (preferredPaymentMethod) {
                        if (preferredPaymentMethod === 'CREDIT_CARD') {
                            $('#step_code .payment-container .select-credit-card').trigger('click');
                        }
                        if (preferredPaymentMethod === 'ACCOUNT') {
                            $('#step_code .payment-container .select-fedex-acc').trigger('click');
                        }
                    }
                }
            });
            /**
            * ###############################################################
            *                   Start | InBranch Section
            * ###############################################################
            */
            let isPickUp;
            if (window.e383157Toggle) {
                isPickUp = (fxoStorage.get("shipkey") == "false") && (fxoStorage.get("pickupkey") == "true");
            } else {
                isPickUp = (localStorage.getItem("shipkey") == "false") && (localStorage.getItem("pickupkey") == "true");
            }
            if (
                togglesAndSettings.mixedCart && isPickUp
            ) {
                this.inBranchDocSectionCall();
            }
            /**
             * ###############################################################
             *                   End | InBranch Section
             * ###############################################################
             */
            /**
             * ###############################################################
             *                   Start | Marketplace Section
             * ###############################################################
             */
            this.showMarketplaceShippingContent();

            if (!togglesAndSettings.isCustomerShippingAccount3PEnabled && !this.isMarketplaceSellerUsingShippingAccount()) {
                this.enableEarlyShippingAccountIncorporation(false);
            }

            this.checkoutShippingFormTitle("Shipping address");
            this.pickupShippingComboKey(false);

            // If customer is viewing the shipping form on 1P only cart
            if (this.isFullFirstPartyQuote() && this.showShippingContent()) {
                this.isPickupFormFilled(false);
            }

            if (this.isMixedQuote()) {

                let expressCheckout, chosenDeliveryMethod;
                if (window.e383157Toggle) {
                    expressCheckout = fxoStorage.get("express-checkout");
                    chosenDeliveryMethod = fxoStorage.get("chosenDeliveryMethod");
                } else {
                    expressCheckout = localStorage.getItem("express-checkout");
                    chosenDeliveryMethod = localStorage.getItem("chosenDeliveryMethod");
                }
                if (chosenDeliveryMethod === 'pick-up' && expressCheckout) {
                    self.onclickTriggerShip(null, null, true);
                }

                // Handles the Express Checkout InStorePickupShippingCombo for mixed carts
                window.addEventListener('express_checkout_mixed_cart_pickup_shipping_combo', () => {
                    if (chosenDeliveryMethod === 'pick-up') {
                        this.isPickupFormFilled(true);
                        this.placePickupOrder(false);
                        this.pickupShippingComboKey(true);
                        this.goToShippingAfterPickup();
                    }
                });

                if (chosenDeliveryMethod === 'pick-up' && expressCheckout) {
                    $do.get('.pickup-address', function () {
                        window.dispatchEvent(new Event('express_checkout_mixed_cart_pickup_shipping_combo'));
                    });
                    $do.off('.pickup-address');
                }

                this.visible.subscribe(function () {
                    /*
                    |------------------------------------------------------
                    | Pickup check in other checkout steps via localStorage
                    |------------------------------------------------------
                    | This section monitors the state of the shipping.js
                    | "visible" observable and uses it as a trigger to
                    | check if the pickup delivery method was unselected
                    | in the payment or in any other checkout step.
                    |
                    */
                    let isPickupDataInLocalStorage;
                    if (window.e383157Toggle) {
                        isPickupDataInLocalStorage = Boolean(fxoStorage.get("pickupData"));
                    } else {
                        isPickupDataInLocalStorage = Boolean(localStorage.getItem('pickupData'));
                    }
                    if (self.pickupShippingComboKey()) {
                        self.isPickupFormFilled(isPickupDataInLocalStorage);
                    }
                });
            }

            window.addEventListener('displayShippingAccountAcknowledgementError', () => {
                self.hideShippingMethodsSection();
            });

            window.addEventListener('resetShippingMethodsSection', () => {
                self.hideShippingMethodsSection();
            });

            /**
             * ###############################################################
             *                   End | Marketplace Section
             * ###############################################################
             */

            window.addEventListener('breadcrumb_go_to_pickup', () => {
                this.goToPickupBreadcrumb();
            });

            window.addEventListener('breadcrumb_go_to_shipping', () => {
                this.goToShippingBreadcrumb();
            });

            // Pickup Data
            pickupData.selectedPickupLocation.subscribe(function () {
                self.validateContactForm();
            });

            return this;
        },

        persistSelectedProductionLocation: function (selectedProductionId, selectedProductionName, selectedProductionAddress) {
            if (window.e383157Toggle) {
                fxoStorage.set("pl_nearest_location", selectedProductionId);
                fxoStorage.set('selected_production_id', selectedProductionId);
                fxoStorage.set('selected_production_locationname', selectedProductionName);
                fxoStorage.set('selected_production_locationadress', selectedProductionAddress);
            } else {
                localStorage.setItem("pl_nearest_location", selectedProductionId);
                localStorage.setItem('selected_production_id', selectedProductionId);
                localStorage.setItem('selected_production_locationname', selectedProductionName);
                localStorage.setItem('selected_production_locationadress', selectedProductionAddress);
            }
            this.setLocationData(selectedProductionId, selectedProductionName, selectedProductionAddress);
        },

        validateShippingAccountNumber: async function (shippingAccountNumber) {

            let responseData = { status: false };
            let fedexShippingAccountPayload = { fedexShippingAccountNumber: shippingAccountNumber };

            await reCaptcha.addRecaptchaTokenToPayload(fedexShippingAccountPayload, 'checkout_shipping_account_validation');

            try {
                responseData.status = await $.ajax({
                    url: urlBuilder.build('accountvalidationapi/index/index'),
                    type: 'POST',
                    data: fedexShippingAccountPayload,
                    showLoader: true
                });
            } catch (error) {
                responseData.error = true;
            }
            return responseData;
        },

        getFastestDeliveryMethodIndex: function (methods) {
            const parseDate = (dateString) => {

                try {

                    if (!dateString || typeof dateString !== 'string') {
                        throw new Error('Invalid date string format');
                    }

                    // Expected format: "Day, Month Day, Time" (e.g., "Thursday, November 14, 4:00pm")
                    const dateFormatRegex = /^[A-Za-z]+,\s+[A-Za-z]+\s+\d{1,2},\s+\d{1,2}(?::\d{2})?(?:am|pm|AM|PM)$/;
                    if (!dateFormatRegex.test(dateString)) {
                        throw new Error('Date string does not match expected format');
                    }

                    // Remove the day of the week
                    const dateWithoutDay = dateString.replace(/^[^,]+, /, '');

                    // Parse the date string
                    const [month, day, time] = dateWithoutDay.split(' ');
                    const [hour, minutePart] = time.split(':');

                    const minute = minutePart ? parseInt(minutePart.slice(0, -2), 10) : 0;
                    const period = minutePart ? minutePart.slice(-2).toLowerCase() : 'am';

                    // Convert to 24-hour format
                    let hour24 = parseInt(hour, 10);
                    if (period === 'pm' && hour24 !== 12) {
                        hour24 += 12;
                    } else if (period === 'am' && hour24 === 12) {
                        hour24 = 0;
                    }

                    // Create a new Date object for the current year
                    const year = new Date().getFullYear();
                    return new Date(`${month} ${day}, ${year} ${hour24}:${minute}`);
                } catch (error) {
                    return null;
                }
            };

            // Filter out methods that don't have a valid date
            const validMethods = methods
                .map((method, index) => ({
                    date: parseDate(method.method_title),
                    index: index
                }))
                .filter(method => method.date !== null);

            if (validMethods.length === 0) {
                // If no valid methods, return the first method index
                return 0;
            }

            return validMethods.reduce((fastest, current) => {
                return current.date < fastest.date ? current : fastest;
            }).index;
        },

        areMethodsEqual: function (method1, method2) {
            // Validate both methods exist
            if (!method1 || !method2) {
                return false;
            }

            // Compare all relevant properties
            return (
                method1.carrier_code === method2.carrier_code &&
                method1.method_code === method2.method_code &&
                method1.method_title === method2.method_title &&
                method1.amount === method2.amount &&
                method1.base_amount === method2.base_amount &&
                method1.extension_attributes?.cheapest === method2.extension_attributes?.cheapest &&
                method1.extension_attributes?.fastest === method2.extension_attributes?.fastest
            );
        },

        sortShippingMethods: function (methods) {
            if (!Array.isArray(methods)) {
                console.error("Invalid input: methods is not an array", methods);
                return {
                    topTwoMethods: [],
                    otherMethods: []
                };
            }
            // Filter out marketplace methods
            const cheapestMethod = methods.find(method => method.extension_attributes && method.extension_attributes.cheapest);
            const fastestMethod = methods.find(method => method.extension_attributes && method.extension_attributes.fastest);

            // Create an array for the first two methods
            let topTwoMethods = [];
            let otherMethods = [];

            if (this.areMethodsEqual(cheapestMethod, fastestMethod) && cheapestMethod) {
                // If both cheapest and fastest are the same method, add it to the topTwoMethods
                topTwoMethods.push(cheapestMethod);

                // Add the next method from the other list as the second method
                otherMethods = methods.filter(method => !this.areMethodsEqual(method, cheapestMethod));
                if (otherMethods.length > 0) {
                    let fastestMethodIndex = this.getFastestDeliveryMethodIndex(otherMethods);
                    topTwoMethods.push(otherMethods[fastestMethodIndex]);
                    otherMethods.splice(fastestMethodIndex, 1);
                }
            } else {
                // Add cheapest method if it exists
                if (cheapestMethod) topTwoMethods.push(cheapestMethod);

                // Add fastest method if it exists
                if (fastestMethod) topTwoMethods.push(fastestMethod);

                // Remove cheapest and fastest methods from the other methods list
                otherMethods = methods.filter(
                    method => method !== cheapestMethod && method !== fastestMethod
                );
            }
            return {
                topTwoMethods: topTwoMethods,
                otherMethods: otherMethods
            };
        },

        renderCustomShippingFields: function () {
            if ((isSdeStore || isSelfRegCustomer || togglesAndSettings.isEpro) && togglesAndSettings.customBillingShipping?.length > 0) {
                const shippingArray = togglesAndSettings.customBillingShipping[0];
                const shippingReferenceContainer = $('.shipping_ref_container');
                const fedexAccountShippingNumber = $('.fedex_account_number-box .fedex_account_number-field');
                const shippingReferenceLabel = $('.shipping_ref_container .fedex_account_number_label');
                const shippingReferenceInput = $('.shipping_ref_container .fedex_account_number-ref-item');

                const isFieldVisible = shippingArray.visible === '1';
                const isFieldDisabled = shippingArray.editable === '0';
                const isFieldRequired = shippingArray.required === '1';
                const isShippingAccEditable = window.checkoutConfig.shipping_account_number_editable === '1';

                // Adds Shipping Reference ID field custom label and value
                shippingReferenceLabel.text(shippingArray.field_label);
                shippingReferenceInput.val(shippingArray.default);

                // Hides Shipping Reference ID field
                isFieldVisible ? shippingReferenceContainer.show() : shippingReferenceContainer.hide();

                // Sets Shipping Reference ID field as mandatory
                isFieldRequired ? shippingReferenceLabel.addClass('required-field') : shippingReferenceLabel.removeClass('required-field');

                // Disables Shipping Account Number field
                fedexAccountShippingNumber.prop('disabled', !isShippingAccEditable);

                // Disables Shipping Reference ID field
                shippingReferenceInput.prop('disabled', isFieldDisabled);

                // Validations and error messages for masks and required state - Shipping Reference ID field
                function fieldValidation(input) {
                    const shippingReferenceInput = $('.shipping_ref_container .fedex_account_number-ref-item');
                    input = shippingReferenceInput;
                    var errorMessage = shippingArray.error_message;
                    var requiredMessage = 'This is a required field';
                    var pattern = shippingArray.custom_mask;
                    var value = input.val();

                    if (shippingReferenceLabel.hasClass('required-field') && value.trim() === '') {
                        input.addClass('error-highlight');
                        $('.error-message').text(requiredMessage || '');
                        $('.button.action.continue').prop('disabled', true);
                    } else if (pattern) {
                        var regex = new RegExp(pattern);
                        var isValid = regex.test(value);

                        if (!isValid) {
                            input.addClass('error-highlight');
                            $('.error-message').text(errorMessage);
                            $('.button.action.continue').prop('disabled', true);
                        } else {
                            input.removeClass('error-highlight');
                            $('.error-message').text('');
                            $('.button.action.continue').prop('disabled', false);
                        }
                    } else {
                        input.removeClass('error-highlight');
                        $('.error-message').text('');
                        $('.button.action.continue').prop('disabled', false);
                    }
                };

                fieldValidation($(this));

                $('.custom-shipping-reference-id').on("blur", function () {
                    fieldValidation($(this));
                });

                $('body').on('click', '.button.action.continue', function () {
                    fieldValidation($(this));
                });

            };
        },

        checkCustomShippingEditable: function () {
            const shippingAccInput = $(".early-shipping-account-number .fedex_account_number-field");
            const removeAccBtn = $('.shipping_account_number.child-box').find('.fedex_account_number_remove');
            shippingAccInput.prop('disabled', true);
            if (togglesAndSettings.isShippingAccEditable === '0') {
                if ((isSelfRegCustomer && shippingAccInput.hasClass("ship-not-prepopulated")) || (isSdeStore && this.isAutopopulate())) {
                    removeAccBtn.show();
                } else {
                    removeAccBtn.hide();
                }
            }
            else if (togglesAndSettings.isShippingAccEditable === '1') {
                removeAccBtn.show();
            }
        },

        showUploadToQuoteContactForm: function () {
            if (window.checkoutConfig?.tiger_team_E_469378_u2q_pickup) {
                const shouldShowContactFrom = pickupData.selectedPickupLocation() || this.isRestrictedProductionLocation();
                if (togglesAndSettings.isToggleEnabled('tiger_d238132') && shouldShowContactFrom) {
                    window.dispatchEvent(new Event('uploadToQuoteFormLoaded'));
                }

                // Check if the user selected a pickup location
                return shouldShowContactFrom;
            }

            if (togglesAndSettings.isToggleEnabled('tiger_d238132')) {
                window.dispatchEvent(new Event('uploadToQuoteFormLoaded'));
            }

            return true;
        },

        inBranchDocSectionCall: function () {
            // Tech Titans - D-221338 fix Toggle
            if (window?.checkoutConfig?.tech_titans_d221338) {
                var self = this;
            }
            if (!(window.checkout?.is_retail || window.checkoutConfig?.isRetailCustomer)) {
                customerData.reload(['inBranchdata'], true);
            }
            var inBranchdata = customerData.get('inBranchdata')();

            //E-442091 CR Logic
            var isRetailCustomer = (window.checkoutConfig.isRetailCustomer) ?? false;
            var CRStoreCode = window.checkoutConfig.CRStoreCode ?? '';
            var CRLocationCode = window.checkoutConfig.CRLocationCode ?? '';
            var isCRLocationApply = (isRetailCustomer && CRStoreCode != '' && CRLocationCode != '');
            //E-442091 CR Logic

            if ((inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart) || (isCRLocationApply)) {
                if ((inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart)) {
                    self.inBranchInCheckout(true);
                    var inBranchdata = customerData.get('inBranchdata')();
                    var locationId = inBranchdata.isInBranchDataInCart;
                } else {
                    var locationId = CRStoreCode;
                }

                $.ajax({
                    url: urlBuilder.build(
                        "delivery/index/centerDetails"
                    ),
                    type: "POST",
                    data: { locationId: locationId },
                    dataType: "json",
                    showLoader: false,
                    success: function (data) {
                        if (data.Id != '' && data.Id != undefined) {
                            $("#zipcodePickup").val(data.address.postalCode);
                            var city = data.address.city;
                            var stateCode = data.address.stateOrProvinceCode;
                            var pinCode = data.address.postalCode;

                            self.geoCoderFindAddress(city, stateCode, pinCode).then(function (res) {
                                city = res.city;
                                stateCode = res.stateCode;
                                pinCode = res.pinCode;
                                self.getPickupAddress(city, stateCode, pinCode);
                            });
                            $('.in-branch.message-block.message-info').show();
                            $("#zipcodePickup").attr('disabled', 'disabled');
                            $(".input-filed-container").addClass("disabled-box");
                            $(".pickup-search-selector").addClass("disabled-dropdown");
                            if (isCRLocationApply) {
                                $(".shipping-title-checkout .modal-container.location-message").hide();
                            }

                            checkLocationRadioBtn();
                        } else if (data.hasOwnProperty("alerts")) {
                            $(".in-branch-location.message-block").css("display", "flex");
                            $("#zipcodePickup").attr('disabled', 'disabled');
                            $(".input-filed-container").addClass("disabled-box");
                            $(".pickup-search-selector").addClass("disabled-dropdown");
                            if (isCRLocationApply) {
                                $(".shipping-title-checkout .modal-container.location-message").hide();
                            }
                        }
                        if (data.hasOwnProperty("errors")) {
                            $('.error-container').removeClass('api-error-hide');
                            return true;
                        }
                    }
                }).done(function (response) {
                    if (response.hasOwnProperty("errors")) {
                        $('.error-container').removeClass('api-error-hide');
                        return true;
                    }
                });

                function checkLocationRadioBtn() {
                    $(document).ajaxStop(function () {
                        if (!$("label.custom-radio-btn.pick-up-button input[name='radio-button']").length) {
                            return;
                        }
                        $(".custom-radio-btn.pick-up-button > input[type='radio']").prop("checked", true);
                        $("recommended-store-label-pickup").hide();
                    });
                }
            }
        },

        closeInBranchMsg: function () {
            $('.in-branch.message-block.message-info').fadeOut();
        },

        closeInvalidLocationMsg: function () {
            $('.in-branch-location.message-block').fadeOut();
        },

        inBranchMessage: function () {
            var inBranchMessage = '';
            var inBranchdata = customerData.get('inBranchdata')();
            if (inBranchdata.isInBranchDataInCart && inBranchdata.isInBranchUser) {
                inBranchMessage = inBranchdata.inBranchMessage;
            }
            return inBranchMessage;
        },

        onClickSearchLocation: function () {
            let city = null,
                stateCode = null,
                populatePickup = null,
                pinCode = null;
            if (window.e383157Toggle) {
                populatePickup = fxoStorage.get("fcl-populate-pickup");
            } else {
                populatePickup = localStorage.getItem("fcl-populate-pickup");
            }
            if (populatePickup) {
                if (window.e383157Toggle) {
                    city = fxoStorage.get("fcl-populate-pickup-city");
                    stateCode = fxoStorage.get("fcl-populate-pickup-state");
                    pinCode = fxoStorage.get("fcl-populate-pickup-zipcode");
                } else {
                    city = localStorage.getItem("fcl-populate-pickup-city");
                    stateCode = localStorage.getItem("fcl-populate-pickup-state");
                    pinCode = localStorage.getItem("fcl-populate-pickup-zipcode");
                }
            } else {
                this.geoCoderFindAddress(city, stateCode, pinCode).then(function (res) {
                    city = res.city;
                    stateCode = res.stateCode;
                    pinCode = res.pinCode;
                });
            }

            this.getPickupAddress(city, stateCode, pinCode);
            if (window.e383157Toggle) {
                fxoStorage.delete("fcl-populate-pickup");
                fxoStorage.delete("fcl-populate-pickup-city");
                fxoStorage.delete("fcl-populate-pickup-state");
                fxoStorage.delete("fcl-populate-pickup-zipcode");
            } else {
                localStorage.removeItem("fcl-populate-pickup");
                localStorage.removeItem("fcl-populate-pickup-city");
                localStorage.removeItem("fcl-populate-pickup-state");
                localStorage.removeItem("fcl-populate-pickup-zipcode");
            }

        },
        onRadiusChange: function () {
            let distance = $('select[name="pickup-search-radius"]').val();
            let pickupSerachContainerElement = $('.pickup-search-radius-container');
            let windowWidth = $(window).width();
            if (distance == 100) {
                $('.location-message').hide();
                if (windowWidth > 767 && windowWidth < 1201) {
                    pickupSerachContainerElement.addClass('medium-screen-container');
                }
            } else {
                $('.location-message').show();
                $('.no-location-message').hide();
                if (windowWidth > 767 && windowWidth < 1200) {
                    pickupSerachContainerElement.removeClass('medium-screen-container');
                }
            }
        },

        isFclCustomerLoggedIn: function () {
            return isFclCustomer;
        },

        setCustomerShippingAddess: function () {

            var customerInfo = customerData.get('checkout-data')();
            var defaultShippingAddress = togglesAndSettings.fclCustomerDefaultShippingData;
            var customerFirstName;
            var customerLastName;
            var customerCompany;
            var customerStreetOne;
            var customerStreetTwo;
            var customerCity;
            var customerPostcode;
            var customerRegionId;
            var customerTelephone;
            var customerEmailId;
            var customerExt;
            if (defaultShippingAddress['status'] == 'success') {
                if (customerInfo.shippingAddressFromData) {
                    customerFirstName = customerInfo.shippingAddressFromData.firstname;
                    customerLastName = customerInfo.shippingAddressFromData.lastname;
                    customerCompany = customerInfo.shippingAddressFromData.company;
                    customerStreetOne = customerInfo.shippingAddressFromData.street[0];
                    customerStreetTwo = customerInfo.shippingAddressFromData.street[1];
                    customerCity = checkoutAdditionalScript.allowCityCharacters(customerInfo.shippingAddressFromData.city);
                    customerPostcode = customerInfo.shippingAddressFromData.postcode;
                    customerRegionId = customerInfo.shippingAddressFromData.region_id;
                    customerTelephone = customerInfo.shippingAddressFromData.telephone;
                    customerEmailId = customerInfo.shippingAddressFromData.custom_attributes['email_id'];
                    customerExt = customerInfo.shippingAddressFromData.custom_attributes['ext'];
                }
                if (typeof (customerFirstName) === 'undefined' || customerFirstName == '') {
                    customerFirstName = defaultShippingAddress['firstname'];
                }
                if (typeof (customerLastName) === 'undefined' || customerLastName == '') {
                    customerLastName = defaultShippingAddress['lastname'];
                }
                if (typeof (customerCompany) === 'undefined' || customerCompany == '') {
                    customerCompany = defaultShippingAddress['company'];
                }
                if (typeof (customerStreetOne) === 'undefined' || customerStreetOne == '') {
                    customerStreetOne = defaultShippingAddress['streetOne'];
                }
                if (typeof (customerStreetTwo) === 'undefined' || customerStreetTwo == '') {
                    customerStreetTwo = defaultShippingAddress['streetTwo'];
                }
                if (typeof (customerCity) === 'undefined' || customerCity == '') {
                    customerCity = checkoutAdditionalScript.allowCityCharacters(defaultShippingAddress['city']);
                }
                if (typeof (customerPostcode) === 'undefined' || customerPostcode == '') {
                    customerPostcode = defaultShippingAddress['postcode'];
                    customerRegionId = defaultShippingAddress['region'];
                }
                if (typeof (customerTelephone) == 'undefined' || customerTelephone == '') {
                    customerTelephone = defaultShippingAddress['telephone'];
                }
                if (customerTelephone == '(111) 111-1111') {
                    customerTelephone = '';
                }
                if (typeof (customerEmailId) === 'undefined' || customerEmailId == '') {
                    customerEmailId = defaultShippingAddress['email'];
                }
                if (typeof (customerExt) === 'undefined' || customerExt == '') {
                    customerExt = defaultShippingAddress['ext'];
                }
                var checkoutData = customerData.get('checkout-data')();
                var loggedAsCustomerCustomerId = window.checkoutConfig.loggedAsCustomerCustomerId;
                var mazegeeksCtcAdminImpersonator = window.checkoutConfig.mazegeeks_ctc_admin_impersonator;

                if (loggedAsCustomerCustomerId < 1 || !mazegeeksCtcAdminImpersonator) {
                    checkoutData.shippingAddressFromData = {
                        firstname: customerFirstName,
                        lastname: customerLastName,
                        company: customerCompany,
                        street: { 0: customerStreetOne, 1: customerStreetTwo },
                        city: customerCity,
                        postcode: customerPostcode,
                        region_id: customerRegionId,
                        telephone: customerTelephone,
                        custom_attributes: { email_id: customerEmailId, ext: customerExt }
                    };
                }

                customerData.set('checkout-data', checkoutData);
            }
        },

        initObservable: function () {
            this._super();
            shippingService.initializeGroundPromoMessage($);
            return this;
        },

        isProductionLocationOn: function () {

            return togglesAndSettings.isProductionLocation;
        },

        isSimplifiedProductionLocationOn: function () {
            return window.checkoutConfig.is_simplified_production_location;
        },

        hasSelectedProdLoc: function () {
            return this.hasProductionLocationSelected();
        },

        /**
         * Check and restore saved production location data
         */
        checkAndRestoreProductionLocation: function () {
            var self = this;
            var product_location_option, locationId, locationName, locationFullAddress;

            if (window.e383157Toggle) {
                product_location_option = fxoStorage.get('product_location_option');
                locationId = fxoStorage.get('selected_production_id');
                locationName = fxoStorage.get('selected_production_locationname');
                locationFullAddress = fxoStorage.get('selected_production_locationadress');
            } else {
                product_location_option = localStorage.getItem('product_location_option');
                locationId = localStorage.getItem('selected_production_id');
                locationName = localStorage.getItem('selected_production_locationname');
                locationFullAddress = localStorage.getItem('selected_production_locationadress');
            }

            if (product_location_option === 'choose_self' && locationId && locationName && locationFullAddress) {
                self.setLocationData(locationId, locationName, locationFullAddress);
                self.hasProductionLocationSelected(true);

                $('.default-p-location').show();
                if (self.isSimplifiedProductionLocationOn()) {
                    $('.prodloc-desc').hide();
                    $('.prodloc-selected').show();
                }
            }
        },


        isPersonalAddressbookOn: function () {
            return isPersonalAddressBook;
        },

        /**
         * Checks if restricted option is configured from company.
         */
        isRestrictedProductionLocationFlagOn: function () {
            if (isRestrictedProductionLocationFlag) {
                $(".message-icon-restricted").hide();
                $(".message-content-restricted").hide();
                $(".searchByRadiusBox").css('width', '65%');
            }
            return isRestrictedProductionLocationFlag;
        },

        checkIsCommercialCustomer: function () {
            return togglesAndSettings.isCommercial;
        },

        checkIsPeakSeasonMessageShowing: function () {
            return togglesAndSettings.isCovidPeakSeason;
        },

        /**
         * Navigator change hash handler.
         *
         * @param {Object} step - navigation step
         */
        navigate: function (step) {
            step && step.isVisible(true);
        },

        goToCart: function () {
            window.location.href = urlBuilder.build('checkout/cart/');
        },

        /**
         * Function to set time slots to be filled inside pickup date and time picker
         */

        formatDate: function (date) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2)
                month = '0' + month;
            if (day.length < 2)
                day = '0' + day;

            return [year, month, day].join('-');
        },

        calculatePickupIndex: function (pickUpResponseData, pickupIndex, element) {
            const firstNonRecommendedIndex = pickUpResponseData.findIndex(locationObj => locationObj?.is_recommended === false);
            pickUpResponseData.splice(pickupIndex, 1);
            pickUpResponseData.splice(firstNonRecommendedIndex, 0, element);
            return firstNonRecommendedIndex;
        },

        getPickupAddress: function (city, stateCode, pinCode, lat = null, lng = null) {

            var self = this;
            if (!lat) {
                let geocoder = new google.maps.Geocoder();
                geocoder.geocode({ 'address': $(".zipcodePickup").val() }, function (results, status) {
                    if (status == "") {
                        city = stateCode = pinCode = null;
                    }
                    if (status == google.maps.GeocoderStatus.OK) {
                        lat = results[0].geometry.location.lat();
                        lng = results[0].geometry.location.lng();
                    } else {
                        console.log("Couldn't get your location");
                    }
                });
            }

            if (self.isEmpty(city) && self.isEmpty(stateCode) && self.isEmpty(pinCode)) {
                pinCode = $(".zipcodePickup").val();
            }

            let radius = null;
            if ($(".zipcodePickup").val()) {
                var requestUrl = urlBuilder.build("delivery/index/getpickup");
                self.isCheckPickup(false);
                $(".contact-from-container").hide();
                $(".place-pickup-order").hide();
                radius = $('#pickup-search-radius').val();
                expressCheckoutShiptoBuilder.hideReviewButtonForPickup();
                if (tiger_d203990) {
                    this.unsetDeliveryCall();
                }
                const pickUpPayLoad = {
                    zipcode: pinCode,
                    city: city,
                    stateCode: stateCode,
                    radius: radius,
                    isCalledForPickup: true
                };
                ajaxUtils.post(requestUrl, {}, pickUpPayLoad, true, 'json', function (data) {
                    if (data.errors?.length > 0) {
                        const hasSessionExpired = data.errors.some(error => error.code === "SESSION_EXPIRED");
                        if (hasSessionExpired) {
                            $('#fcl-session-modal').trigger('timeout');
                            return;
                        }
                    }
                    if ((Array.isArray(data) && data.length === 0) || (data.errors?.length > 0 && !self.isFullMarketplaceQuote())) {
                        let pickupErrorMessage = window.checkoutConfig.pickup_search_error_description || '';
                        if (data.transactionId) {
                            pickupErrorMessage = pickupErrorMessage + "<br/>" + "Transaction ID: " + data.transactionId;
                        }
                        $('.pickup-error-hide').addClass('error-modal').removeClass('api-error-hide').children('.message-container').html(pickupErrorMessage);
                        return;
                    }
                    if (data.errors) {
                        if (data.errors.length > 0 && (data.errors[0].code === "HOLDUNTILDATE.EARLIER.THAN.ORDERREADYDATE" || data.errors[0].code === "HOLD_UNTIL_DATE_EARLIER_THAN_ORDER_READY_DATE")) {
                            $('.pickup-error-hide').removeClass('api-error-hide error-modal').children('.message-container').html('<p>System error, Please try again.</p>');
                            return;
                        }
                        if (data.noLocation) {
                            if (radius == 100) {
                                $('.no-location-message').show();
                            }
                        } else {
                            $('.pickup-error-hide').removeClass('api-error-hide error-modal').children('.message-container').html('<p>System error, Please try again.</p>');
                        }
                        self.isCheckPickup(false);
                        //D-72340 | Pickup Map should be hidden after error
                        if (window.e383157Toggle) {
                            fxoStorage.set('errorInPickup', true);
                        } else {
                            localStorage.setItem('errorInPickup', true);
                        }
                        return;
                    }
                    //B-865761 Sanchit Bhatia Ability to show recommended/preferred locations on checkout  when Default Recommended production is false(Pickup)
                    $('.pickup-error-hide').removeClass('error-modal').children('.message-container').html('<p>System error, Please try again.</p>');
                    $('.no-location-message').hide();
                    //D-72340 | Pickup Map should be hidden after error
                    self.isCheckPickup(true);
                    if (window.e383157Toggle) {
                        fxoStorage.set('errorInPickup', false);
                    } else {
                        localStorage.setItem('errorInPickup', false);
                    }
                    $(".pickup-location-list-container").css('display', 'block');
                    var pickUpResponceData = data;
                    var pickUpResponceDataPaged = pickUpResponceData;
                    let earliestPickupIndex = -1;
                    let closestPickupIndex = -1;
                    let closestDistance = Infinity;
                    var sections = ['inBranchdata'];
                    var locationKey = "";
                    var boolSkipZerothLocation = "";
                    if (!(window.checkout?.is_retail || window.checkoutConfig?.isRetailCustomer)) {
                        customerData.reload(['inBranchdata'], true);
                    }
                    pickUpResponceDataPaged.forEach(function (element, index) {
                        //E-442091 CR Logic
                        var isRetailCustomer = (window.checkoutConfig.isRetailCustomer) ?? false;
                        var CRStoreCode = window.checkoutConfig.CRStoreCode ?? '';
                        var CRLocationCode = window.checkoutConfig.CRLocationCode ?? '';
                        var isCRLocationApply = (isRetailCustomer && CRStoreCode != '' && CRLocationCode != '');
                        //E-442091 CR Logic
                        //inBranch section
                        var inBranchdata = customerData.get('inBranchdata')();
                        if ((inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart) || (isCRLocationApply)) {
                            var locationId;
                            if (inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart) {
                                var locationId = inBranchdata.isInBranchDataInCart;
                            } else {
                                var locationId = CRStoreCode;
                            }

                            let locationZipCode = pickUpResponceDataPaged[0]?.location.id;
                            if (locationZipCode == locationId) {
                                boolSkipZerothLocation = true;
                            }
                            if (!boolSkipZerothLocation) {
                                if (element.location.id != locationId) {
                                    pickUpResponceDataPaged.splice(index, 1);
                                } else {
                                    locationKey = index;
                                }
                            } else {
                                if (element.location.id != locationId) {
                                    pickUpResponceDataPaged.splice(index);
                                }
                            }
                            self.koAssignedPickupLabelIndex(true);
                        }
                        //inbranch section
                        shippingService.getDates(element.estimatedDeliveryLocalTime);
                        element.date = element.estimatedDeliveryLocalTimeShow;
                        element.datehidden = element.estimatedDeliveryLocalTime;
                        if (index == 0) {
                            if (self.isPromiseTimePickupOptionsToggle()) {
                                earliestPickupIndex = index;
                            }
                            self.koEarliestPickupLabelIndex(index);
                        }

                        if (self.isPromiseTimePickupOptionsToggle()) {
                            let distance = shippingService.distance(
                                lat,
                                lng,
                                element.location.geoCode.latitude,
                                element.location.geoCode.longitude,
                                'M'
                            ).toFixed(2);
                            element.distance = lat ? distance.toString() + ' mi' : "";

                            if (distance < closestDistance) {
                                closestDistance = distance;
                                closestPickupIndex = index;
                                self.koClosestPickupLabelIndex(index);
                            }
                        }
                    });

                    if (self.isD236651Enabled()) {
                        var len = 0;
                        let earliestDate = 0;
                        let earliestPickupId = -1;

                        pickUpResponceDataPaged.forEach(function (element, index) {
                            var distance = shippingService.distance(
                                lat,
                                lng,
                                element.location.geoCode.latitude,
                                element.location.geoCode.longitude, 'M').toFixed(2);
                            element.distance = lat ? distance.toString() + ' mi' : "";
                            len++;

                            let currentDate = new Date(element.datehidden);
                            if (!earliestDate) {
                                earliestDate = currentDate;
                                earliestPickupId = element.location?.id;
                            } else if (currentDate < earliestDate) {
                                earliestDate = currentDate;
                                earliestPickupId = element.location?.id;
                            }
                        });

                        pickUpResponceDataPaged.sort(function (a, b) {
                            return parseFloat(a.distance) - parseFloat(b.distance);
                        });

                        if (earliestPickupId >= 0) {
                            let earliestPickupIndex = pickUpResponceDataPaged.findIndex(item => item.location?.id === earliestPickupId);
                            if (earliestPickupIndex)
                                self.koEarliestPickupLabelIndex(earliestPickupIndex);
                        }
                    }

                    if (self.isPromiseTimePickupOptionsToggle()) {
                        let sortedData = [...pickUpResponceDataPaged];
                        sortedData.sort((a, b) => new Date(a.datehidden) - new Date(b.datehidden));
                        const earliestElement = sortedData[0];
                        sortedData.sort((a, b) => {
                            const distanceA = shippingService.distance(
                                lat, lng, a.location.geoCode.latitude, a.location.geoCode.longitude, 'M'
                            );
                            const distanceB = shippingService.distance(
                                lat, lng, b.location.geoCode.latitude, b.location.geoCode.longitude, 'M'
                            );
                            return distanceA - distanceB;
                        });
                        const closestElement = sortedData[0];
                        const isEarliestAlsoClosest = earliestElement.location?.id === closestElement.location?.id;
                        let earliestPickupIndex = pickUpResponceDataPaged.findIndex(locationObj => locationObj.location?.id === earliestElement.location?.id);
                        let closestPickupIndex = pickUpResponceDataPaged.findIndex(locationObj => locationObj.location?.id === closestElement.location?.id);
                        let hasClosestPickupChanged = false;
                        if (isEarliestAlsoClosest) {
                            if (!earliestElement?.is_recommended) {
                                earliestPickupIndex = self.calculatePickupIndex(pickUpResponceDataPaged, earliestPickupIndex, earliestElement);
                            }
                            self.koClosestPickupLabelIndex(earliestPickupIndex);
                        } else {
                            if (!closestElement?.is_recommended) {
                                hasClosestPickupChanged = closestPickupIndex > earliestPickupIndex;
                                closestPickupIndex = self.calculatePickupIndex(pickUpResponceDataPaged, closestPickupIndex, closestElement);
                            }
                            if (!earliestElement?.is_recommended) {
                                if (hasClosestPickupChanged)
                                    earliestPickupIndex++;
                                earliestPickupIndex = self.calculatePickupIndex(pickUpResponceDataPaged, earliestPickupIndex, earliestElement);
                                if (!closestElement?.is_recommended)
                                    closestPickupIndex++;
                            }
                            self.koClosestPickupLabelIndex(closestPickupIndex);
                        }
                        self.koEarliestPickupLabelIndex(earliestPickupIndex);

                        $(document).on("click", ".pick-up-button", function () {
                            const isPickUpLocationChecked = $(this).find('input[type="radio"]').is(':checked');
                            if (!self.isPromiseTimePickupOptionsToggle() || (self.isPromiseTimePickupOptionsToggle() && !isPickUpLocationChecked)) {
                                var selectedPickupContainer = $(this).parents('.pickup-location-container'),
                                    pickUpDate = selectedPickupContainer.find('span.pickup-date').text(),
                                    pickUpDateHidden = selectedPickupContainer.find('span.pickup-date-hidden').text(),
                                    prefPickupLocationAddress = selectedPickupContainer.find('span.pickup-location-address-data').text(),
                                    prefPickupLocationName = selectedPickupContainer.find('span.radio-label').text(),
                                    selectedPickupId = selectedPickupContainer.find('span.pickup-location-id').text();
                                selectedPickupId = selectedPickupId.substr(0, 4);

                                $('.pickup-location-container').find('span.pickup-date').removeClass('selected-pickuplocation');
                                selectedPickupContainer.find('span.pickup-date').addClass('selected-pickuplocation');
                                $('.pickup-location-container').find('div.pickup-address').removeClass('selected-item');
                                selectedPickupContainer.find('div.pickup-address').addClass('selected-item');

                                self.koPickupTimeAvailability([]);
                                self.getPickupLocationTimeSlots(selectedPickupId);

                                self.koPickupId(selectedPickupId);
                                self.koPickupDate(pickUpDate);
                                self.koPickupDateHidden(pickUpDateHidden);
                                self.koPickupName(prefPickupLocationName);
                                self.koPickupAddress(prefPickupLocationAddress);
                                if (window.e383157Toggle) {
                                    fxoStorage.set("updatedChangedPickupDateTime");
                                    fxoStorage.set("pickupDateTime", pickUpDate);
                                    fxoStorage.set('pickupDateTimeForApi', pickUpDateHidden);
                                } else {
                                    localStorage.removeItem("updatedChangedPickupDateTime");
                                    localStorage.setItem("pickupDateTime", pickUpDate);
                                    localStorage.setItem('pickupDateTimeForApi', pickUpDateHidden);
                                }
                                self.koEarliestPickupDateTime(pickUpDate);
                            }
                        });
                    }

                    if (!boolSkipZerothLocation && locationKey != "") {
                        pickUpResponceDataPaged.splice((locationKey + 1));
                        if (locationKey > 0) {
                            pickUpResponceDataPaged.splice(0, (locationKey));
                        }
                        self.koEarliestPickupLabelIndex(0);
                    }
                    $(document).on("click", ".pick-up-button", function () {
                        let selectedPickupContainer = $(this).parents('.pickup-location-container'),
                            pickUpDate = selectedPickupContainer.find('span.pickup-date').text(),
                            pickUpDateHidden = selectedPickupContainer.find('span.pickup-date-hidden').text(),
                            prefPickupLocationAddress = selectedPickupContainer.find('span.pickup-location-address-data').text(),
                            prefPickupLocationName = selectedPickupContainer.find('span.radio-label').text(),
                            selectedPickupId = selectedPickupContainer.find('span.pickup-location-id').text();
                        selectedPickupId = selectedPickupId.substr(0, 4);

                        const selectedPickupRegionCode = $(selectedPickupContainer).find('.pickup-location-state:eq(0)').text();

                        if (selectedPickupRegionCode) {
                            self.koPickupRegion(selectedPickupRegionCode);
                            !self.isMixedQuote() && disclosureModel.setShouldDisplayQuestionnaire(selectedPickupRegionCode);
                        }

                        $('.pickup-location-container').find('span.pickup-date').removeClass('selected-pickuplocation');
                        selectedPickupContainer.find('span.pickup-date').addClass('selected-pickuplocation');
                        $('.pickup-location-container').find('div.pickup-address').removeClass('selected-item');
                        selectedPickupContainer.find('div.pickup-address').addClass('selected-item');

                        self.koPickupTimeAvailability([]);
                        self.getPickupLocationTimeSlots(selectedPickupId);

                        self.koPickupId(selectedPickupId);
                        self.koPickupDate(pickUpDate);
                        self.koPickupDateHidden(pickUpDateHidden);
                        self.koPickupName(prefPickupLocationName);
                        self.koPickupAddress(prefPickupLocationAddress);
                        if (window.e383157Toggle) {
                            fxoStorage.set("updatedChangedPickupDateTime");
                            fxoStorage.set("pickupDateTime", pickUpDate);
                            fxoStorage.set('pickupDateTimeForApi', pickUpDateHidden);
                        } else {
                            localStorage.removeItem("updatedChangedPickupDateTime");
                            localStorage.setItem("pickupDateTime", pickUpDate);
                            localStorage.setItem('pickupDateTimeForApi', pickUpDateHidden);
                        }
                        self.koEarliestPickupDateTime(pickUpDate);
                    });
                    if (!self.isD236651Enabled()) {
                        var len = 0;
                        pickUpResponceDataPaged.forEach(function (element) {
                            var distance = shippingService.distance(
                                lat,
                                lng,
                                element.location.geoCode.latitude,
                                element.location.geoCode.longitude, 'M').toFixed(2);
                            element.distance = lat ? distance.toString() + ' mi' : "";
                            len++;
                        });
                    }
                    //D-95209-Extra footer line coming on Checkout page in case of pickup
                    $('.pickup-location-item-container').attr('style', 'border-bottom: 0.1rem solid #d3d3d3 !important');
                    /*  D-77777 - Remove gap if only 2 locations are slected */
                    if (len == '1') {
                        $('.pickup-location-item-container').attr('style', 'height: 15rem !important');
                    }
                    if (len == '2') {
                        $('.pickup-location-item-container').attr('style', 'height: 30rem !important');
                    }

                    /* B-1299551 toggle clean up start end */

                    var arr = [];
                    var i = 0;
                    pickUpResponceDataPaged.forEach(function (element) {
                        if (window.tiger_d195327_toggle) {
                            if (element.location.premium === true) {
                                arr[i++] = element.location.id;
                            }
                        } else {
                            if (element.location.type == 'HOTEL_CONVENTION') {
                                arr[i++] = element.location.id;
                            }
                        }
                    });
                    $(".hco-info-msg").hide();
                    if (window.e383157Toggle) {
                        fxoStorage.delete('pickup_hco_location_select');
                    } else {
                        localStorage.removeItem('pickup_hco_location_select');
                    }

                    if (self.isPromiseTimePickupOptionsToggle()) {
                        // Check if the priority print pickup is enabled for each location
                        for (let i = 0; i < pickUpResponceDataPaged.length; i++) {
                            let location = pickUpResponceDataPaged[i];
                            location.priorityPrintPickup = null;
                            location.standardPriorityPickup = null;

                            for (let j = 0; j < location.availableOrderPriorities.length; j++) {
                                let priority = location.availableOrderPriorities[j];

                                if (priority.orderPriorityText === PRIORITY_PRINT_PICKUP) {
                                    location.priorityPrintPickup = priority;
                                }
                                if (priority.orderPriorityText === STANDARD_PICKUP) {
                                    location.standardPriorityPickup = priority;
                                }
                            }
                        }
                    }

                    self.pickUpJson(pickUpResponceDataPaged);

                    $(".pickup-heading").show();
                    //add show more locations button
                    $('show-more-locations').remove();
                    $('.pickup-location-item-container').append('<div class="show-more-locations fedex-medium"> <button title="Show More Locations">Show More Locations</button> </div>');
                    pickupSearch.showMoreLocations();
                    if (!togglesAndSettings.isProductionLocation && !explorersRestrictedAndRecommendedProduction) {
                        $(".recommended-store-label-pickup").hide();
                    }
                    /* B-1112171 - Display 'Hotel & Convention Location' tag on checkout screen for pickup if center is marked as HCO */
                    /* B-1299551 toggle clean up start end */

                    let pickupRequestExecution = true;
                    if (window.e383157Toggle) {
                        fxoStorage.set("TaxAmount", '');
                        fxoStorage.set("EstimatedTotal", '');
                    } else {
                        localStorage.setItem("TaxAmount", '');
                        localStorage.setItem("EstimatedTotal", '');
                    }

                    /**
                     * Google maps Integration
                     */
                    try {
                        let googleMapsLoad = true;
                        if (googleMapsLoad) {
                            var mapProp = {
                                center: { lat: parseFloat(self.pickUpJson()[0].location.geoCode.latitude), lng: parseFloat(self.pickUpJson()[0].location.geoCode.longitude) },
                                zoom: 12,
                                mapId: "DEMO_MAP_ID"
                            };

                            map = new google.maps.Map(document.getElementById("googleMap"), mapProp);

                            var marker;
                            markers = [];
                            var infowindow = new google.maps.InfoWindow();
                            self.pickUpJson().forEach(function (element, index) {

                                default_icon = new google.maps.marker.PinElement({
                                    glyph: `${index + 1}`,
                                    background: defaultMarkerColor,
                                    borderColor: defaultMarkerColor,
                                    glyphColor: markerGlyphColor,
                                    scale: markerScale
                                });

                                selected_icon = new google.maps.marker.PinElement({
                                    glyph: `${index + 1}`,
                                    background: selectedMarkerColor,
                                    borderColor: selectedMarkerColor,
                                    glyphColor: markerGlyphColor,
                                    scale: markerScale
                                });

                                marker = new google.maps.marker.AdvancedMarkerElement({
                                    map,
                                    position: { lat: parseFloat(element.location.geoCode.latitude), lng: parseFloat(element.location.geoCode.longitude) },
                                    content: index === 0 ? selected_icon.element : default_icon.element
                                });
                                markers.push(marker);
                                google.maps.event.addListener(marker, 'click', (function (marker, i) {
                                    return function () {
                                        infowindow.setContent(element.location.name);
                                        infowindow.open(map, marker);
                                    }
                                })(marker, index));
                            });

                            $('.toggle-input').on('change', function (e) {
                                e.stopImmediatePropagation();
                                if (self.showPickupContent()) {
                                    $('.map-canvas').toggle();
                                }
                            });
                            googleMapsLoad = false;
                        }
                    } catch (err) {
                        $(".map-canvas").hide();
                        console.log("Google MAP Error" + err);
                    }

                    if (pickupRequestExecution) {
                        $(".pick-up-button").on('change', function () {
                            let pickupPageLocation = false;
                            var $this = $(this);
                            $this.closest(".box-container").find(".pickup-address").addClass("selected-item").siblings().removeClass("selected-item");
                            if ($this.closest(".box-container").find('.pickupPageLocation').length && !togglesAndSettings.isEpro) {
                                pickupPageLocation = true;
                            }
                            if (window.e383157Toggle) {
                                fxoStorage.set('pickupPageLocation', pickupPageLocation);
                            } else {
                                localStorage.setItem("pickupPageLocation", pickupPageLocation);
                            }
                            /**
                             * Autofill pickup contact form detail after login from FCL
                             * B-1242868
                             */
                            if (isFclCustomer) {
                                if (!($(".pickup-location-list-container .contact-from-container .contact-first-name .contact-fname").val())) {
                                    var fclFirstName = togglesAndSettings.fclFirstName;
                                    $(".pickup-location-list-container .contact-from-container .contact-first-name .contact-fname").val(fclFirstName);
                                }

                                if (!($(".pickup-location-list-container .contact-from-container .contact-last-name .contact-lname").val())) {
                                    var fclLastName = togglesAndSettings.fclLastName;
                                    $(".pickup-location-list-container .contact-from-container .contact-last-name .contact-lname").val(fclLastName);
                                }

                                if (!($(".pickup-location-list-container .contact-from-container .contact-phone-no .contact-number").val())) {
                                    var fclContactNumber = togglesAndSettings.fclContactNumber;
                                    $(".pickup-location-list-container .contact-from-container .contact-phone-no .contact-number").val(fclContactNumber);

                                }
                                if (!($(".pickup-location-list-container .contact-from-container .contact-phone-ext .contact-ext").val())) {
                                    var fclExtNumber = togglesAndSettings.fclExtNumber;
                                    $(".pickup-location-list-container .contact-from-container .contact-phone-ext .contact-ext").val(togglesAndSettings.fclExtNumber);
                                }

                                if (!($(".pickup-location-list-container .contact-from-container .contact-email-container .contact-email").val())) {
                                    var fclEmailAddress = togglesAndSettings.fclEmailAddress;
                                    $(".pickup-location-list-container .contact-from-container .contact-email-container .contact-email").val(fclEmailAddress);
                                }

                                $(".pickup-location-list-container .contact-from-container .contact-phone-no .contact-number").trigger("input").trigger("change");
                            }

                            /**
                             * Show contact information form when user slects
                             * a pickup location
                             */
                            $(".contact-from-container").show();
                            var locationId = $this.find(".pickup-location-id").text();
                            /**
                             * B-1038368 - Display Info msg on slection of HCO location in pickup
                             *
                             */
                            /* B-1299551 toggle clean up start end */

                            if ($.inArray(locationId, arr) != -1) {
                                $(".hco-info-msg").show();
                                if (window.e383157Toggle) {
                                    fxoStorage.set('pickup_hco_location_select', true);
                                } else {
                                    localStorage.setItem("pickup_hco_location_select", true);
                                }
                            } else {
                                $(".hco-info-msg").hide();
                                if (window.e383157Toggle) {
                                    fxoStorage.delete('pickup_hco_location_select');
                                } else {
                                    localStorage.removeItem('pickup_hco_location_select');
                                }
                            }
                            var location = self.pickUpJson().find(function (element) {
                                return element.location.id == locationId;
                            });
                            try {
                                map.setCenter({ lat: parseFloat(location.location.geoCode.latitude), lng: parseFloat(location.location.geoCode.longitude) });
                                for (let i = 0; i < markers.length; i++) {
                                    markers[i].setMap(null);
                                }
                                map.setZoom(15);
                                self.pickUpJson().forEach(function (element, index) {

                                    default_icon = new google.maps.marker.PinElement({
                                        glyph: `${index + 1}`,
                                        background: defaultMarkerColor,
                                        borderColor: defaultMarkerColor,
                                        glyphColor: markerGlyphColor,
                                        scale: markerScale
                                    });

                                    selected_icon = new google.maps.marker.PinElement({
                                        glyph: `${index + 1}`,
                                        background: selectedMarkerColor,
                                        borderColor: selectedMarkerColor,
                                        glyphColor: markerGlyphColor,
                                        scale: markerScale
                                    });

                                    var temp = {
                                        map,
                                        position: { lat: parseFloat(element.location.geoCode.latitude), lng: parseFloat(element.location.geoCode.longitude) },
                                        content: locationId === element.location.id ? selected_icon.element : default_icon.element
                                    };

                                    new google.maps.marker.AdvancedMarkerElement(temp);
                                });
                            } catch (err) {
                                console.log("Google MAP set center error" + err);
                            }
                            let requestUrl = urlBuilder.build("delivery/index/deliveryrateapishipandpickup");
                            let fedexAccountData;
                            if (window.e383157Toggle) {
                                fedexAccountData = fxoStorage.get('fedexAccount') && fxoStorage.get('fedexAccount') !== "null" ? fxoStorage.get('fedexAccount') : '';
                            } else {
                                fedexAccountData = localStorage.getItem('fedexAccount') && localStorage.getItem('fedexAccount') !== "null" ? localStorage.getItem('fedexAccount') : '';
                            }
                            $.ajax({
                                url: requestUrl,
                                type: "POST",
                                data: {
                                    locationId: locationId,
                                    pickupPageLocation: pickupPageLocation,
                                    fedexAccount: fedexAccountData,
                                },
                                dataType: "json",
                                showLoader: true,
                                async: true,
                                complete: function () { },
                            }).done(function (response) {
                                if (typeof response !== 'undefined' && response.length < 1) {
                                    $('.pickup-error-hide').removeClass('api-error-hide');
                                    $(".place-pickup-order").hide();
                                    $('.loadersmall').hide();
                                    return true;
                                } else if (!response.hasOwnProperty("errors") || response.hasOwnProperty("alerts")) {
                                    $('.pickup-error-hide').addClass('api-error-hide');
                                    $(".place-pickup-order").show();
                                    $(".pickup-location-list-container").trigger('pickup-loaded');
                                }

                                if (response.hasOwnProperty("alerts") && response.alerts.length > 0) {
                                    rateQuoteAlertsHandler.warningHandler(response, true);
                                    $('.loadersmall').hide();
                                }
                                if (response.hasOwnProperty("errors")) {
                                    const transactionId = response?.errors?.errors?.transactionId;
                                    if (typeof transactionId !== 'undefined') {
                                        $('<p>Transaction ID: ' + transactionId + '</p>').css({ 'font-family': 'Fedex Sans', 'color': '#2f4047' }).insertAfter(".pickup-error-hide .message-container p");
                                    }
                                    $('.pickup-error-hide').removeClass('api-error-hide');
                                    $(".place-pickup-order").hide();

                                    if (typeof response.errors.is_timeout != 'undefined' && response.errors.is_timeout != null) {
                                        window.location.replace(orderConfirmationUrl);
                                    }
                                    return true;
                                }

                                if (!togglesAndSettings.isEpro) {
                                    if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                                        window.location.replace(orderConfirmationUrl);
                                    }
                                    response = response.rateQuote;
                                } else {
                                    response = response.rate;
                                }
                                if (window.e383157Toggle) {
                                    fxoStorage.set('locationId', locationId);
                                } else {
                                    localStorage.setItem('locationId', locationId);
                                }
                                $(".place-pickup-order").show();
                                if ($(".in-branch.pick-message").length) {
                                    $(".in-branch.pick-message").show();
                                }
                                expressCheckoutShiptoBuilder.addReviewOrderButtonForPickup();
                                // B-1126844 | update cart items price
                                $("#rateApiResponse").val(JSON.stringify(response));

                                const stringToFloat = function (stringAmount) {
                                    return parseFloat(stringAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                                };

                                const discountCalculate = function (discountValue) {
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
                                var discountResult = [];
                                var promoDiscountAmount = 0;
                                var accountDiscountAmount = 0;
                                var volumeDiscountAmount = 0;
                                var bundleDiscountAmount = 0;
                                var shippingDiscountAmount = 0.00;

                                if (!togglesAndSettings.isEpro) {
                                    response.rateDetails = response.rateQuoteDetails;
                                }
                                if (typeof (response) != "undefined" && typeof (response.rateDetails) != "undefined") {
                                    response.rateDetails.forEach((rateDetail) => {
                                        /* update item row total B-1105765 */

                                        if (togglesAndSettings.hcoPriceUpdate && typeof rateDetail.productLines != "undefined") {
                                            const productLines = rateDetail.productLines;
                                            if (window.checkoutConfig.tiger_D193772_fix) {
                                                updateHCPrice(productLines);
                                            } else {
                                                productLines.forEach((productLine) => {
                                                    var instanceId = productLine.instanceId;
                                                    var itemRowPrice = productLine.productRetailPrice;
                                                    itemRowPrice = priceFormatWithCurrency(itemRowPrice);
                                                    $(".product-item-details[data-item-id='" + instanceId + "'] .subtotal").html(itemRowPrice);
                                                    $(".subtotal." + instanceId + " .cart-price .price").html(itemRowPrice);
                                                    $(".subtotal-instance").show();
                                                    $(".checkout-normal-price").hide();
                                                })
                                            }
                                        }
                                        if (typeof rateDetail.deliveryLines != "undefined") {
                                            rateDetail.deliveryLines.forEach((deliveryLine) => {
                                                if (typeof deliveryLine.deliveryLineDiscounts != "undefined") {
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
                                            } else if (shippingDiscountAmount > 0) {
                                                promoDiscountAmount = discountResult['promoDiscountAmount'] - shippingDiscountAmount;
                                            }
                                        }
                                        if (typeof rateDetail.totalAmount != "undefined") {
                                            totalNetAmount = rateResponseHandler.getTotalAmount(rateDetail, totalNetAmount);
                                        }
                                        if (rateDetail.deliveriesTotalAmount) {
                                            shippingAmount = rateDetail.deliveriesTotalAmount;
                                        }
                                    });
                                    if (shippingAmount) {
                                        if (window.e383157Toggle) {
                                            fxoStorage.set("marketplaceShippingPrice", shippingAmount);
                                        } else {
                                            localStorage.setItem('marketplaceShippingPrice', shippingAmount);
                                        }
                                        var formattedshippingAmount = priceUtils.formatPrice(shippingAmount, quote.getPriceFormat());
                                        $(".totals.shipping.excl .price").text(formattedshippingAmount);
                                        $(".grand.totals.excl .amount .price").text(formattedshippingAmount);
                                    } else {
                                        if (window.e383157Toggle) {
                                            fxoStorage.delete("marketplaceShippingPrice");
                                        } else {
                                            localStorage.removeItem('marketplaceShippingPrice');
                                        }
                                    }
                                }
                                totalNetAmount = priceFormatWithCurrency(totalNetAmount);
                                grossAmount = priceFormatWithCurrency(grossAmount);
                                var taxAmount = priceFormatWithCurrency(response.rateDetails[0].taxAmount);
                                if (window.e383157Toggle) {
                                    fxoStorage.set("TaxAmount", taxAmount);
                                    fxoStorage.set("EstimatedTotal", totalNetAmount);
                                } else {
                                    localStorage.setItem("TaxAmount", taxAmount);
                                    localStorage.setItem("EstimatedTotal", totalNetAmount);
                                }
                                $(".grand.totals.incl .price").text(totalNetAmount);
                                $(".grand.totals .amount .price").text(totalNetAmount);
                                $(".totals.sub .amount .price").text(grossAmount);

                                if (totalDiscountAmount) {
                                    totalDiscountAmount = priceFormatWithCurrency(totalDiscountAmount);
                                    $(".totals.discount.excl .amount .price").text('-' + totalDiscountAmount);
                                    $(".totals.fedexDiscount .amount .price").text('-' + totalDiscountAmount);
                                } else {
                                    $(".totals.fedexDiscount .amount .price").text('-');
                                    $(".totals.discount.excl .amount .price").text('-');
                                }

                                $(".totals-tax .price").text(taxAmount);
                                let accountDiscountHtml = '';

                                if (accountDiscountAmount || volumeDiscountAmount || bundleDiscountAmount || promoDiscountAmount || shippingDiscountAmount) {
                                    $(".discount_breakdown tbody tr.discount").remove();
                                }
                                if (accountDiscountAmount == 0 && volumeDiscountAmount == 0 && bundleDiscountAmount == 0 && promoDiscountAmount == 0 && shippingDiscountAmount == 0) {
                                    $('.toggle-discount th #discbreak').remove();
                                }
                                let discountAmounts = [{
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
                                }];
                                let sortedAmounts = discountAmounts.sort((p1, p2) => (p1.price < p2.price) ? 1 : (p1.price > p2.price) ? -1 : 0);
                                sortedAmounts.forEach(function (amount, index) {
                                    if (amount.price) {
                                        accountDiscountHtml = '<tr class="' + amount.type + ' discount"><th class="mark" scope="row">' + amount.label + '</th><td class="amount"><span class="price">-' + priceFormatWithCurrency(amount.price); +'</span></td></tr>';
                                        $(".discount_breakdown tbody").append(accountDiscountHtml);
                                        if ($('.toggle-discount th #discbreak').length == 0) {
                                            $('.toggle-discount th').append('<span id="discbreak" tabindex="0" class="arrow down"></span>');
                                        }
                                    } else {
                                        $(".discount_breakdown tbody tr." + amount.type).remove();
                                    }
                                });

                                $(".opc-block-summary .table-totals").show();
                            });
                        });
                        pickupRequestExecution = false;

                        $(".pickup-location-list-container").trigger('rendered');
                    }

                    /**
                     * Show alternate pikcup person form if the user clicks on the checkbox
                     * for it.
                     */
                    let alternateToggle = true;
                    if (alternateToggle) {
                        $(".contact-from-container .alternate-checkbox-container").on('change', function () {
                            if ($('.contact-from-container .alternate-check-container .alternate-checkbox-container input').is(':checked')) {
                                $(".alternate-from-container").show();
                            } else {
                                $(".alternate-from-container").hide();
                            }
                            /**
                             * Show Proceed to payment button for
                             * Guest(Non-Logged In) users
                             */
                            if (!isLoggedIn) {
                                $(".proceed-to-payment").show();
                            }
                            self.isAlternateContact(!self.isAlternateContact());
                            /**
                             * Enable form validation for alternate pickup person form
                             * if user opts for it.
                             */
                            if (self.isAlternateContact()) {
                                self.validateContactForm();
                            }
                        });
                        alternateToggle = false;
                    }

                    /**
                     * Call Center Details API (locations API) on click of show details button
                     */
                    let centerDetailsRequest = true;
                    if (centerDetailsRequest) {
                        $(".show-details-button").on('click', function () {
                            var $this = $(this);
                            var locationId = $this
                                .find(".pickup-location-id")
                                .text();
                            $(".hide-details-button").hide();
                            $.ajax({
                                url: urlBuilder.build(
                                    "delivery/index/centerDetails"
                                ),
                                type: "POST",
                                data: { locationId: locationId },
                                dataType: "json",
                                showLoader: true,
                                async: true,
                                success: function (data) {
                                    if (data.hasOwnProperty("errors")) {
                                        $('.pickup-error-hide').removeClass('api-error-hide');
                                        return true;
                                    }
                                    data.hoursOfOperation = shippingService.getHoursOfFirstWeek(data.hoursOfOperation);
                                    self.center(data);
                                    self.showCenter(true);
                                    $this.closest(".pickup-location-item-container").find(".center-details").show();
                                    $this.closest(".box-container").find(".hide-details-button").show();
                                    $(".show-details-button").show();
                                    $this.hide();
                                }
                            }).done(function (response) {
                                if (response.hasOwnProperty("errors")) {
                                    $('.pickup-error-hide').removeClass('api-error-hide');
                                    return true;
                                }
                            });
                        });
                        centerDetailsRequest = false;
                    }

                    /**
                     * Hide center details from selected pikcup location
                     * on click on hide details button
                     */
                    var hideCenterDetails = true;
                    if (hideCenterDetails) {
                        $(".hide-details-button").on('click', function () {
                            var $this = $(this);
                            $this.closest(".pickup-location-item-container").find(".center-details").hide();
                            $(".show-details-button").show();
                            self.showCenter(false);
                            $this.hide();

                        });
                        hideCenterDetails = false;
                    }

                    /**
                     * Enable alternate pickup person form and contact person form
                     * form validations
                     */
                    let validattionContactForm = true;
                    if (validattionContactForm) {
                        $(".contact-fname").on('keyup blur', function (e) {
                            self.validateContactFirstName(e);
                        });
                        $(".contact-lname").on('keyup blur', function (e) {
                            self.validateContactLastName(e);
                        });
                        $(".contact-number").on('keyup blur', function () {
                            self.validateContactNumber();
                        });
                        $(".contact-email").on('keyup blur', function () {
                            self.validateContactEmail();
                        });
                        /**
                         * Alternate Contact
                         */
                        $(".alternate-fname").on('keyup blur', function (e) {
                            self.validateAlternateFirstName(e);
                        });
                        $(".alternate-lname").on('keyup blur', function (e) {
                            self.validateAlternateLastName(e);
                        });
                        $(".alternate-number").on('keyup blur', function () {
                            self.validateAlternateNumber();
                        });
                        $(".alternate-email").on('keyup blur', function () {
                            self.validateAlternateEmail();
                        });
                        $(".alternate-from-container").on('change textInput input', function () {
                            self.validateContactForm();
                        });

                        $(".contact-from-container").on('change textInput input', function () {
                            self.validateContactForm();
                        });
                        validattionContactForm = false;
                    }
                    //E-442091 CR Logic
                    var isRetailCustomer = (window.checkoutConfig.isRetailCustomer) ?? false;
                    var CRStoreCode = window.checkoutConfig.CRStoreCode ?? '';
                    var CRLocationCode = window.checkoutConfig.CRLocationCode ?? '';
                    var isCRLocationApply = isRetailCustomer && CRLocationCode != '' && CRStoreCode != '';
                    //E-442091 CR Logic
                    //inbranch section
                    var inBranchdata = customerData.get('inBranchdata')();
                    if ((inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart) || (isCRLocationApply)) {
                        $('.pick-up-button').trigger('click');
                    }
                    //inbranch section
                });
            }
        },

        /**
         * Show Preferred pickup time Modal
         */
        isPreferredPickupDateTimeEnabled: function () {
            return !togglesAndSettings.isEpro;
        },

        removeBrTag: function (dateTimeEarliest) {
            return dateTimeEarliest.replace("</br>", " ");
        },

        getPickupLocationAndAddress: function () {
            return this.koPickupName() + ' located at ' + this.koPickupAddress();
        },

        getPickupDateTime: function () {
            let standardPickupTime = this.koStandardPickupTime();
            return '<b>Earliest standard pickup: </b>' + (standardPickupTime || '');
        },

        savePickupInformation: function (triggeredByButton = false) {

            /**
             * ###############################################################
             *                   Start | Marketplace Section
             * ###############################################################
             */

            if (this.isMixedQuote()) {
                this.pickupShippingComboKey(true);
                this.isPickupFormFilled(true);
                this.showPickupContent(false);
            }

            /**
             * ###############################################################
             *                   End | Marketplace Section
             * ###############################################################
             */

            let information, isPickup, expressCheckout;
            if (window.e383157Toggle) {
                information = fxoStorage.get('pickupData');
                isPickup = fxoStorage.get('preferredDeliveryMethod') === 'pick-up';
                expressCheckout = fxoStorage.get('express-checkout');
            } else {
                information = JSON.parse(localStorage.getItem('pickupData'));
                isPickup = localStorage.getItem('preferredDeliveryMethod') === 'pick-up';
                expressCheckout = localStorage.getItem('express-checkout');
            }

            if (this.chosenDeliveryMethod() === 'pick-up' && isPickup && expressCheckout && !information && !triggeredByButton) {
                let primaryPickUpData;
                if (window.e383157Toggle) {
                    primaryPickUpData = fxoStorage.get('primaryPickUpData');
                } else {
                    primaryPickUpData = JSON.parse(localStorage.getItem('primaryPickUpData'));
                }
                const profileInfo = profileSessionBuilder.getProfileAddress();
                information = {
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
                    rateapi_response: $('#rateApiResponse').val() || '',
                    orderNumber: null
                };
            }
            else if (triggeredByButton) {
                const contactInformationForm = $('.contact-from-container');
                const alternateInformationForm = $('.alternate-from-container');
                const selectedPickUpAddress = $(".pickup-address.selected-item");
                const contact_number = contactInformationForm.find('.contact-number').val().replace(/[\s()\-]/g, '');
                const contact_ext = contactInformationForm.find('.contact-ext').val();
                const contact_number_pickup = contact_number + ' ' + contact_ext;
                const isAlternatePerson = alternateInformationForm.css('display') === 'none' ? false : true;
                let updatedChangedPickupDateTime;
                if (window.e383157Toggle) {
                    updatedChangedPickupDateTime = fxoStorage.get("updatedChangedPickupDateTime");
                } else {
                    updatedChangedPickupDateTime = localStorage.getItem("updatedChangedPickupDateTime");
                }
                const pickup_location_date = (updatedChangedPickupDateTime == null) ? selectedPickUpAddress.children(".pickup-location-estimate-date").text() : updatedChangedPickupDateTime;
                information = {
                    contactInformation: {
                        contact_fname: contactInformationForm.find('.contact-fname').val(),
                        contact_lname: contactInformationForm.find('.contact-lname').val(),
                        contact_email: contactInformationForm.find('.contact-email').val(),
                        contact_number: contact_number,
                        contact_ext: contact_ext,
                        contact_number_pickup: contact_number_pickup,
                        alternate_fname: alternateInformationForm.find('.alternate-fname').val(),
                        alternate_lname: alternateInformationForm.find('.alternate-lname').val(),
                        alternate_email: alternateInformationForm.find('.alternate-email').val(),
                        alternate_number: alternateInformationForm.find('.alternate-number').val().replace(/[\s()\-]/g, ''),
                        alternate_ext: alternateInformationForm.find('.alternate-ext').val(),
                        isAlternatePerson: isAlternatePerson,
                    },
                    addressInformation: {
                        pickup_location_name: selectedPickUpAddress.children(".pickup-location-name").text(),
                        pickup_location_street: selectedPickUpAddress.children(".pickup-location-street").text(),
                        pickup_location_city: selectedPickUpAddress.children(".pickup-location-city").text(),
                        pickup_location_state: selectedPickUpAddress.children(".pickup-location-state").text(),
                        pickup_location_zipcode: selectedPickUpAddress.children(".pickup-location-zipcode").text(),
                        pickup_location_country: selectedPickUpAddress.children(".pickup-location-country").text(),
                        pickup_location_date: pickup_location_date,
                        pickup: true,
                        shipping_address: "",
                        billing_address: "",
                        shipping_method_code: "PICKUP",
                        shipping_carrier_code: "fedexshipping",
                        shipping_detail: {
                            carrier_code: "fedexshipping",
                            method_code: "PICKUP",
                            carrier_title: "Fedex Store Pickup",
                            method_title: selectedPickUpAddress.children(".pickup-location-id").text(),
                            amount: 0,
                            base_amount: 0,
                            available: true,
                            error_message: "",
                            price_excl_tax: 0,
                            price_incl_tax: 0,
                        },
                    },
                    rateapi_response: contactInformationForm.find('#rateApiResponse').val()
                };
            }
            if (window.e383157Toggle) {
                fxoStorage.set('pickupData', information);
            } else {
                localStorage.setItem('pickupData', JSON.stringify(information));
            }
            return information;
        },

        /**
         * Function to place and save quote for pikcup flow
         */
        placePickupOrder: function (goToNextStep = true, triggeredByButton = false, updatePickInformation = true) {
            jQuery('#warning-message-box').hide();
            $('head').append(window.checkoutConfig.gdlScript);

            let pickupData;
            if (window.e383157Toggle) {
                pickupData = fxoStorage.get('pickupData');
            } else {
                pickupData = JSON.parse(localStorage.getItem('pickupData'));
            }

            var c_payload;

            if (updatePickInformation) {
                c_payload = this.savePickupInformation(triggeredByButton);
            }
            else {
                if (pickupData) {
                    c_payload = pickupData;
                }
            }

            if (!c_payload) {
                return;
            }

            var pickup_location_id = c_payload.addressInformation.shipping_detail.method_title;
            let self = this;

            var isShip, isPick;
            if (window.e383157Toggle) {
                isShip = fxoStorage.get("shipkey");
                isPick = fxoStorage.get("pickupkey");
            } else {
                isShip = localStorage.getItem("shipkey");
                isPick = localStorage.getItem("pickupkey");
            }

            if ((isShip === 'false' && isPick === 'true') && c_payload) {
                c_payload.addressInformation.pickup_location_name = encodeURIComponent(c_payload.addressInformation.pickup_location_name);
                c_payload.addressInformation.pickup_location_street = encodeURIComponent(c_payload.addressInformation.pickup_location_street);
            }
            var pickupPostData = null;
            pickupPostData = JSON.stringify(c_payload).replaceAll('&', encodeURIComponent('&'));


            let third_party_carrier_code = null;
            let third_party_method_code = null;

            window.dispatchEvent(new Event('toast_messages'));

            if (this.isMixedQuote()) {
                let selectedShippingMethodsStorage;
                if (window.e383157Toggle) {
                    fxoStorage.set('pickupPostData', pickupPostData);
                    selectedShippingMethodsStorage = fxoStorage.get('selectedShippingMethods');
                } else {
                    localStorage.setItem('pickupPostData', pickupPostData);
                    selectedShippingMethodsStorage = localStorage.getItem('selectedShippingMethods');
                }
                selectedShippingMethodsStorage = typeof selectedShippingMethodsStorage === 'string'
                    ? JSON.parse(selectedShippingMethodsStorage)
                    : (selectedShippingMethodsStorage || '');

                if (selectedShippingMethodsStorage && selectedShippingMethodsStorage !== '') {
                    const thirdPartyShippingMethod = selectedShippingMethodsStorage.find(method => method.carrier_code !== 'fedexshipping');

                    if (thirdPartyShippingMethod) {
                        third_party_carrier_code = thirdPartyShippingMethod.carrier_code;
                        third_party_method_code = thirdPartyShippingMethod.method_code;
                    }

                    pickupPostData = JSON.parse(pickupPostData);
                    pickupPostData = JSON.stringify({
                        ...pickupPostData,
                        third_party_carrier_code,
                        third_party_method_code,
                    });
                }
            }

            $.ajax({
                url: urlBuilder.build(
                    "delivery/quote/createpost"
                ),
                type: "POST",
                data: "data=" + pickupPostData,
                dataType: "json",
                showLoader: true,
                async: true,
                complete: function () { },
            }).done(function (resData) {
                $('.message-container').parent().removeClass('error-modal');
                if (isLoggedIn && !isSelfRegCustomer) {
                    if (resData.url.length > 0) {
                        let estimatedTotal;
                        if (window.e383157Toggle) {
                            estimatedTotal = fxoStorage.get("EstimatedTotal");
                        } else {
                            estimatedTotal = localStorage.getItem("EstimatedTotal");
                        }
                        if (estimatedTotal) {
                            let orderSalePrice = estimatedTotal.replace("$", "");
                            gdlEvent.appendGDLScript(orderSalePrice);
                        }
                        var sections = ["cart"];
                        customerData.invalidate(sections);
                        customerData.invalidate([
                            "customer",
                        ]);
                        var cxmlResponse = atob(
                            resData.notification
                        );
                        if (cxmlResponse.startsWith("<html", 0)) {
                            cxmlResponse = cxmlResponse.replace("<form", '<form class="cxmlResponseForm"');
                            document.body.innerHTML = cxmlResponse;
                            $(".cxmlResponseForm").submit();
                        } else {
                            window.location.href = resData.url;
                        }
                    } else {
                        $('.error-container').removeClass('api-error-hide');
                        window.reload();
                    }
                } else {
                    let expressCheckout;
                    if (window.e383157Toggle) {
                        expressCheckout = fxoStorage.get("express-checkout");
                    } else {
                        expressCheckout = localStorage.getItem("express-checkout");
                    }
                    if (isFclCustomer && expressCheckout) {
                        if (!profileSessionBuilder.getPreferredDeliveryMethod()) {
                            let pickupLocationId = "";
                            if (typeof pickup_location_id === "undefined") {
                                let selectedItem = $(".pickup-address.selected-item");
                                if (selectedItem.length === 1) {
                                    pickupLocationId = selectedItem.find(".pickup-location-id").text();
                                }
                            } else {
                                pickupLocationId = pickup_location_id;
                            }
                            expressCheckoutShiptoBuilder.setPrefferedDeliveryMethod('PICKUP', pickupLocationId);
                        }

                        expressCheckoutShiptoBuilder.setPaymentData();

                        // Needed to prevent the express-checkout to skip the shipping form on mixed carts

                        if (self.isMixedQuote()) {
                            self.goToShippingAfterPickup();

                            if (goToNextStep) {
                                stepNavigator.next();
                            }

                            return;
                        }

                        $(".opc-progress-bar li:nth-child(1)").attr("data-active", true);

                    } else {
                        if (self.isMixedQuote()) {
                            self.goToShippingAfterPickup();
                        }
                        else {
                            stepNavigator.next();
                        }
                    }
                }
            });

            let paymentData;
            if (window.e383157Toggle) {
                if (isFclCustomer && fxoStorage.get('paymentData')) {
                    paymentData = fxoStorage.get('paymentData');
                    profilePickEditBuilder.autofillPaymentDetails(paymentData);
                }
            } else {
                if (isFclCustomer && localStorage.getItem('paymentData')) {
                    paymentData = JSON.parse(localStorage.getItem('paymentData'));
                    profilePickEditBuilder.autofillPaymentDetails(paymentData);
                }
            }

            if (marketplaceQuoteHelper.isMixedQuote()) {
                this.setShippingInformation(false);
            }

            window.dispatchEvent(new Event('on_change_delivery_method'));
        },

        /**
         * @return {*}
         */
        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [{
                    text: buttons.save.text ?
                        buttons.save.text : $t("Save Address"),
                    class: buttons.save.class ?
                        buttons.save.class : "action primary action-save-address",
                    click: self.saveNewAddress.bind(self),
                },
                {
                    text: buttons.cancel.text ?
                        buttons.cancel.text : $t("Cancel"),
                    class: buttons.cancel.class ?
                        buttons.cancel.class : "action secondary action-hide-popup",

                    /** @inheritdoc */
                    click: this.onClosePopUp.bind(this),
                },
                ];

                /** @inheritdoc */
                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(
                    this
                );
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this),
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // Store temporary address for revert action in case when user click cancel action
                    self.temporaryAddress = $.extend(
                        true, {},
                        checkoutData.getShippingAddressFromData()
                    );
                };
                popUp = modal(
                    this.popUpForm.options,
                    $(this.popUpForm.element)
                );
            }
            return popUp;
        },

        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            checkoutData.setShippingAddressFromData(
                $.extend(true, {}, this.temporaryAddress)
            );
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
            $(".action-save-address").on('click', function () {
                $("#shipping-method-buttons-container").hide();
            });
        },

        /**
         * Save new shipping address
         */
        saveNewAddress: function () {
            var addressData, newShippingAddress;
            this.source.set("params.invalid", false);
            this.triggerShippingDataValidateEvent();
            if (!this.source.get("params.invalid")) {
                addressData = this.source.get("shippingAddress");

                // Set the goggle suggested address on shipping address validation toggle
                var googleSuggestedAddress = shippingFormAdditionalScript.getGoogleSuggestedShippingAddress();
                if (typeof googleSuggestedAddress != 'undefined' && googleSuggestedAddress != null) {
                    addressData.city = googleSuggestedAddress.city;
                    addressData.postcode = googleSuggestedAddress.zipcode;
                    addressData.street = googleSuggestedAddress.streetLines;
                    addressData.region = googleSuggestedAddress.stateCode;
                    addressData.region_id = googleSuggestedAddress.regionId;
                }

                // if user clicked the checkbox, its value is true or false. Need to convert.
                addressData["save_in_address_book"] = this.saveInAddressBook ? 1 : 0;

                // New address must be selected as a shipping address
                newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(
                    newShippingAddress.getKey()
                );
                checkoutData.setNewCustomerShippingAddress(
                    $.extend(true, {}, addressData)
                );
                this.getPopUp().closeModal();

                //D-107018 | Add new address and Remove both options are coming after adding and removing address 2 times
                if (togglesAndSettings.isCommercial) {
                    $('.checkout-shipping-address .new-address-popup button').hide();
                }
                this.isNewAddressAdded(true);
            }
            $('.shipping-address-item.selected-item').show();
        },

        /**
         * Shipping Method View
         */
        rates: shippingService.getShippingRates(),
        isLoading: shippingService.isLoading,
        isSelected: ko.computed({
            read: function () {
                return quote.shippingMethod() ?
                    quote.shippingMethod()["carrier_code"] +
                    "_" +
                    quote.shippingMethod()["method_code"] +
                    "-" +
                    indexValue :
                    null;
            },
            write: function (index) {
                indexValue = index;
            },
        }),

        /**
         * Fetches and processes all the necessary data
         * to process the chosen shipping method and set
         * the shipping for a new order or quote
         *
         * @param {Object} shippingMethod - The selected shipping method.
         * @param {Object} event - The click event from the radio button.
         * @return {Boolean}
         */
        selectShippingMethod: function (shippingMethod, event) {
            let self = this;
            $('.message-container').parent().removeClass('error-modal');
            let selectedRadioBtn, shipfedexAccountNumber = '';
            if (window.checkoutConfig.tiger_shipping_methods_display) {
                selectedRadioBtn = $(event.target).closest(".row").find(".radio");
            } else {
                selectedRadioBtn = $(event.target).parents("tr.row").find(".radio");
            }
            selectedRadioBtn.prop("checked", true);
            clearInterval(intervalId);
            $('.error-container').addClass('api-error-hide');
            selectShippingMethodAction(shippingMethod);
            if (window.e383157Toggle) {
                fxoStorage.set("TaxAmount", '');
                fxoStorage.set("EstimatedTotal", '');
            } else {
                localStorage.setItem("TaxAmount", '');
                localStorage.setItem("EstimatedTotal", '');
            }

            if (this.pickupShippingComboKey() && disclosureModel.isCampaingAdDisclosureToggleEnable) {
                // If its a mixed cart and disclosure is enabled, we need verify if we should display the disclosure form in review page
                disclosureModel.setShouldDisplayQuestionnaire(self.koPickupRegion(), quote.shippingAddress()['region']);
            }

            const radioBtnId = checkoutAdditionalScript.checkShippingOptionId(event);

            if (window.e383157Toggle) {
                fxoStorage.set("selectedRadioShipping", radioBtnId);
            } else {
                localStorage.setItem("selectedRadioShipping", radioBtnId);
            }
            checkoutData.setSelectedShippingRate(shippingMethod["carrier_code"] + "_" + shippingMethod["method_code"]);
            jQuery(".loading-mask").show();
            let postCode = quote.shippingAddress()["postcode"];
            let regionCode = quote.shippingAddress()['regionId'];
            let city = checkoutAdditionalScript.allowCityCharacters(quote.shippingAddress()['city']);
            let street = quote.shippingAddress()['street'];
            let company = quote.shippingAddress()['company'];
            let isLocalDeliverySelected = false;
            if (window.e383157Toggle) {
                fxoStorage.set('selectedShipFormData', isLocalDeliverySelected);
            } else {
                localStorage.setItem("selectedShipFormData", isLocalDeliverySelected);
            }
            if ((shippingMethod["method_code"].indexOf('LOCAL_DELIVERY') > -1)) {
                shipfedexAccountNumber = '';
                if (shipAccountNumber != "") {
                    isLocalDeliverySelected = true;
                    $(".modal-container.local-delivery-message").show();
                } else {
                    $('#closeLocalDeliveryMessage').trigger('click');
                }
                if (window.e383157Toggle) {
                    fxoStorage.set('isLocalDeliveryMethod', isLocalDeliverySelected);
                } else {
                    localStorage.setItem('isLocalDeliveryMethod', isLocalDeliverySelected);
                }
            } else {
                let fedexAccountShipping;
                if (window.e383157Toggle) {
                    fedexAccountShipping = fxoStorage.get('shipping_account_number');
                } else {
                    fedexAccountShipping = localStorage.getItem('shipping_account_number');
                }
                if (shipAccountNumber) {
                    shipfedexAccountNumber = shipAccountNumber;
                } else if (fedexAccountShipping) {
                    shipfedexAccountNumber = fedexAccountShipping;
                }
                $('#closeLocalDeliveryMessage').trigger('click');
                if (window.e383157Toggle) {
                    fxoStorage.set('isLocalDeliveryMethod', isLocalDeliverySelected);
                } else {
                    localStorage.setItem('isLocalDeliveryMethod', isLocalDeliverySelected);
                }
            }

            var isResidenceShipping = null;
            if (isExplorersAddressClassificationFixToggleEnable) {
                if (tiger_d213977) {
                    var address = quote.shippingAddress();
                    isResidenceShipping = address?.customAttributes?.find(attr => attr.attribute_code === "residence_shipping")?.value || false;
                } else {
                    isResidenceShipping = false;
                    var checkoutShippingDetails = customerData.get('checkout-data')();
                    var shippingFormDetails = checkoutShippingDetails.shippingAddressFromData;
                    if (typeof shippingFormDetails !== 'undefined' && shippingFormDetails != null && typeof shippingFormDetails.custom_attributes !== 'undefined' && shippingFormDetails.custom_attributes != null && shippingFormDetails.custom_attributes !== '' && typeof shippingFormDetails.custom_attributes.residence_shipping !== 'undefined') {
                        isResidenceShipping = shippingFormDetails.custom_attributes.residence_shipping;
                    }
                }
            }

            var shipFormData = {
                ship_method: shippingMethod["method_code"],
                zipcode: postCode,
                region_id: regionCode,
                city: city,
                street: street,
                shipfedexAccountNumber: shipAccountNumber,
                is_residence_shipping: isResidenceShipping
            };
            if (window.e383157Toggle) {
                fxoStorage.set('selectedShipFormData', shipFormData);
            } else {
                localStorage.setItem('selectedShipFormData', JSON.stringify(shipFormData));
            }
            var firstname = $(".form-shipping-address input[name^='firstname']").val();
            var lastname = $(".form-shipping-address input[name^='lastname']").val();
            var email = $(".form-shipping-address input[name^='custom_attributes[email_id]']").val();
            var telephone = $(".form-shipping-address input[name^='telephone']").val();
            if (typeof (telephone) != 'undefined') {
                telephone = $(".form-shipping-address input[name^='telephone']").val().replace(/\D/g, '');
            }

            let ship_method_data = null;
            if ((marketplaceQuoteHelper.isMixedQuote() || marketplaceQuoteHelper.isFullMarketplaceQuote())) {
                var shippingAddressForMirakl = quote.shippingAddress();
                shippingAddressForMirakl["telephone"] = shippingAddressForMirakl["telephone"].replace(" ", "").replace("(", "").replace(")", "").replace("-", "");
                var shippingMethodWithAddress = shippingMethod;
                shippingMethodWithAddress['address'] = shippingAddressForMirakl;
                ship_method_data = JSON.stringify(shippingMethodWithAddress);
            } else {
                ship_method_data = JSON.stringify(shippingMethod);
            }

            if (window.e383157Toggle) {
                fxoStorage.set('ship_method_data', shippingMethod);
            } else {
                localStorage.setItem('ship_method_data', ship_method_data);
            }
            if (regionCode != 'undefined' && regionCode != '') {

                let third_party_carrier_code = null;
                let third_party_method_code = null;
                let first_party_carrier_code = null;
                let first_party_method_code = null;
                var location_id = null;
                let selectedShippingMethodsStorage;
                if (window.e383157Toggle) {
                    selectedShippingMethodsStorage = fxoStorage.get('selectedShippingMethods');
                } else {
                    selectedShippingMethodsStorage = localStorage.getItem('selectedShippingMethods');
                }
                const newShippingData = JSON.parse(ship_method_data);
                if (typeof selectedShippingMethodsStorage === 'string') {
                    selectedShippingMethodsStorage = JSON.parse(selectedShippingMethodsStorage);
                }

                if (!selectedShippingMethodsStorage) {
                    selectedShippingMethodsStorage = [];
                }

                const existingMethodIndex = selectedShippingMethodsStorage.findIndex((method) => {
                    if (newShippingData.marketplace && method.carrier_title === newShippingData.carrier_title) {
                        return method;
                    }
                    if (!newShippingData.marketplace && method.carrier_code === newShippingData.carrier_code) {
                        return method;
                    }
                });

                if (existingMethodIndex !== -1) {
                    selectedShippingMethodsStorage[existingMethodIndex] = newShippingData;
                }

                if (existingMethodIndex === -1) {
                    selectedShippingMethodsStorage.push(newShippingData);
                }
                if (window.e383157Toggle) {
                    fxoStorage.set('selectedShippingMethods', selectedShippingMethodsStorage);
                } else {
                    localStorage.setItem('selectedShippingMethods', JSON.stringify(selectedShippingMethodsStorage));
                }
                let chosenDeliveryMethod;
                if (window.e383157Toggle) {
                    chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                } else {
                    chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                }
                if (chosenDeliveryMethod === 'shipping') {
                    let firstPartyShippingData = selectedShippingMethodsStorage.find(method => method.carrier_code === 'fedexshipping');
                    let thirdPartyShippingData = selectedShippingMethodsStorage.find(method => method.carrier_code !== 'fedexshipping');

                    third_party_carrier_code = thirdPartyShippingData !== undefined ? thirdPartyShippingData.carrier_code : '';
                    third_party_method_code = thirdPartyShippingData !== undefined ? thirdPartyShippingData.method_code : '';
                    first_party_carrier_code = firstPartyShippingData !== undefined ? firstPartyShippingData.carrier_code : '';
                    first_party_method_code = firstPartyShippingData !== undefined ? firstPartyShippingData.method_code : '';

                    if (explorersProductionLocationFix) {
                        if (window.e383157Toggle) {
                            location_id = fxoStorage.get('pl_nearest_location') || '';
                            if (fxoStorage.get('selected_production_id')) {
                                location_id = fxoStorage.get('selected_production_id');
                            }
                            // if allow production location is on at company level
                            if ((!window.checkoutConfig.is_production_location || fxoStorage.get("product_location_option") !== 'choose_self') && location_id) {
                                location_id = '';
                                // D-205447 Fix
                                if (techTitansProductionLocationFix) {
                                    fxoStorage.set('selected_production_id', location_id);
                                }
                            }
                        } else {
                            location_id = localStorage.getItem('pl_nearest_location') || '';
                            if (localStorage.getItem('selected_production_id')) {
                                location_id = localStorage.getItem('selected_production_id');
                            }
                            if ((!window.checkoutConfig.is_production_location || localStorage.getItem("product_location_option") !== 'choose_self') && location_id) {
                                location_id = '';
                            }
                        }
                    }
                } else if (chosenDeliveryMethod === 'pick-up') {
                    if (window.e383157Toggle) {
                        location_id = fxoStorage.get('locationId') || '';
                    } else {
                        location_id = localStorage.getItem('locationId') || '';
                    }
                }

                let selectedCarriersData = [
                    third_party_carrier_code,
                    third_party_method_code,
                    first_party_carrier_code,
                    first_party_method_code
                ];
                if (window.e383157Toggle) {
                    fxoStorage.set('selectedCarriersData', selectedCarriersData);
                } else {
                    localStorage.setItem('selectedCarriersData', JSON.stringify(selectedCarriersData));
                }
                let requestUrl = urlBuilder.build("delivery/index/deliveryrateapishipandpickup");
                let deliveryRateApiPayLoad = {
                    firstname: firstname,
                    lastname: lastname,
                    email: email,
                    telephone: telephone,
                    ship_method: shippingMethod["method_code"],
                    zipcode: postCode,
                    region_id: regionCode,
                    city: city,
                    street: street,
                    company: company,
                    is_residence_shipping: isResidenceShipping,
                    ship_method_data: ship_method_data,
                    third_party_carrier_code,
                    third_party_method_code,
                    first_party_carrier_code,
                    first_party_method_code,
                    location_id
                };
                if (!window.checkoutConfig.tigerteamE469373enabled || window.checkoutConfig.isValidShippingAccount) {
                    deliveryRateApiPayLoad.fedEx_account_number = shipfedexAccountNumber;
                }
                $.ajax({
                    url: requestUrl,
                    type: "POST",
                    data: deliveryRateApiPayLoad,
                    dataType: "json",
                    showLoader: true,
                    async: true,
                    complete: function () { },
                }).done(function (response) {
                    $(".shipping-message-container").hide();
                    if (typeof response !== 'undefined' && response.length < 1) {
                        selectedRadioBtn.prop("checked", false);
                        // Retire this logic when E-513778 toggle is removed
                        disclosureModel.isCampaingAdDisclosureToggleEnable
                            ? self.disableCreateQuoteBtn(true)
                            : $(".create_quote").prop("disabled", true);
                        $('.error-container').removeClass('api-error-hide');
                        $('.loadersmall').hide();
                        return true;
                    }
                    if (response.hasOwnProperty("alerts") && response.alerts.length > 0) {
                        rateQuoteAlertsHandler.warningHandler(response, true);
                        $('.loadersmall').hide();
                    }
                    if (response.hasOwnProperty("errors")) {
                        rateQuoteErrorsHandler.errorHandler(response, false);
                        // Retire this logic when E-513778 toggle is removed
                        disclosureModel.isCampaingAdDisclosureToggleEnable
                            ? self.disableCreateQuoteBtn(true)
                            : $(".create_quote").prop("disabled", true);
                        selectedRadioBtn.prop("checked", false);
                        const transactionId = response?.errors?.errors?.transactionId;
                        if (typeof transactionId !== 'undefined') {
                            $(".error-container .message-container p:first-child").text('System error, Please try again.').show();
                            $(".error-container .message-container p.message").text('Transaction ID: ' + transactionId).css({ 'font-family': 'Fedex Sans', 'color': '#2f4047' });
                        }
                        $('.error-container').removeClass('api-error-hide').show();
                        if (typeof response.errors.is_timeout != 'undefined' && response.errors.is_timeout != null) {
                            window.location.replace(orderConfirmationUrl);
                        }
                        return true;
                    }
                    if (response.hasOwnProperty("free_shipping") && response.free_shipping.show_free_shipping_message) {
                        $(".shipping-message-container").show();
                        $(".message-text > .discount-message").text(response.free_shipping.free_shipping_message);
                    }
                    if (!window.checkoutConfig.is_epro) {
                        if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                            window.location.replace(orderConfirmationUrl);
                        }
                        response = response.rateQuote;
                    } else {
                        response = response.rate;
                    }

                    //B-1126844 | update cart items price
                    $("#rateApiResponseShipment").val(JSON.stringify(response));

                    const calculate = shippingService.calculateDollarAmount(response);

                    var shippingAmount = shippingService.getShippingLinePrice(response);
                    $(".opc-block-summary .table-totals .totals.shipping.excl").show();

                    const stringToFloat = function (stringAmount) {
                        return parseFloat(stringAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                    };

                    const discountCalculate = function (discountValue) {
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

                    const allShippingMethodsSelected = function () {
                        // Get all options available
                        const methods = $('.table-checkout-shipping-method').find('tbody');
                        let isAllOptionsSelected = true;

                        for (let i = 0; i < methods.length; i++) {
                            const currentDiv = $(methods[i]);
                            const anyChecked = currentDiv.find('input[type="radio"]:checked').length > 0;
                            if (!anyChecked) {
                                isAllOptionsSelected = false;
                            }
                        }
                        return isAllOptionsSelected;
                    }

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
                    if (!window.checkoutConfig.is_epro) {
                        response.rateDetails = response.rateQuoteDetails;
                    }
                    if (typeof (response) != "undefined" && typeof (response.rateDetails) != "undefined") {
                        response.rateDetails.forEach((rateDetail) => {
                            /* update item row total B-1105765 */
                            if (window.checkoutConfig.hco_price_update && typeof rateDetail.productLines != "undefined") {
                                const productLines = rateDetail.productLines;
                                if (window.checkoutConfig.tiger_D193772_fix) {
                                    updateHCPrice(productLines);
                                } else {
                                    productLines.forEach((productLine) => {
                                        var instanceId = productLine.instanceId;
                                        var itemRowPrice = productLine.productRetailPrice;
                                        itemRowPrice = priceFormatWithCurrency(itemRowPrice);
                                        $(".subtotal." + instanceId + " .cart-price .price").html(itemRowPrice);
                                        $(".subtotal-instance").show();
                                        $(".checkout-normal-price").hide();
                                    })
                                }
                            }
                            if (typeof rateDetail.deliveryLines != "undefined") {
                                rateDetail.deliveryLines.forEach((deliveryLine) => {
                                    if (typeof deliveryLine.deliveryLineDiscounts != "undefined") {
                                        var shippingDiscountPrice = 0;
                                        deliveryLine.deliveryLineDiscounts.forEach((deliveryLineDiscount) => {
                                            if (deliveryLineDiscount['type'] == 'COUPON' ||
                                                ((window.checkout.mazegeek_b2352379_discount_breakdown === true || window.checkoutConfig.mazegeek_b2352379_discount_breakdown === true) &&
                                                    deliveryLineDiscount['type'] == 'CORPORATE')) {
                                                shippingDiscountPrice += discountCalculate(deliveryLineDiscount['amount']);
                                            }
                                        });

                                        if (chosenDeliveryMethod === 'shipping' && deliveryLine.deliveryLineType === "SHIPPING" && deliveryLine.deliveryLineDiscounts.length > 0) {
                                            let selectedFirstPartyShippingMethod = selectedShippingMethodsStorage.find(method => method.carrier_code === 'fedexshipping');
                                            if (selectedFirstPartyShippingMethod) {
                                                selectedFirstPartyShippingMethod.deliveryRetailPrice = deliveryLine.deliveryRetailPrice;
                                                if (window.e383157Toggle) {
                                                    fxoStorage.set('selectedShippingMethods', selectedShippingMethodsStorage);
                                                } else {
                                                    localStorage.setItem('selectedShippingMethods', JSON.stringify(selectedShippingMethodsStorage));
                                                }
                                            }
                                        }

                                        shippingDiscountAmount += shippingDiscountPrice;
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
                                    promoDiscountAmount = (discountResult['promoDiscountAmount'] > 0) ? discountResult['promoDiscountAmount'] - shippingDiscountAmount : 0;
                                }
                            }
                            //@todo estimated total and estimated shipping total should be implemented based on response from rate api
                            //this should for all stores, if shipping account number is applied, we need to show the split total
                            if (typeof rateDetail.totalAmount != "undefined") {
                                netAmountResult = rateResponseHandler.getTotalNetAmount(rateDetail, totalNetAmount, estimatedShippingTotal, netAmountResult);
                                totalNetAmount = netAmountResult['totalNetAmount'];
                                estimatedShippingTotal = netAmountResult['estimatedShippingTotal'];
                            }
                            if (rateDetail.deliveriesTotalAmount) {
                                shippingAmount = rateDetail.deliveriesTotalAmount;
                                if (window.e383157Toggle) {
                                    fxoStorage.set("marketplaceShippingPrice", shippingAmount);
                                } else {
                                    localStorage.setItem('marketplaceShippingPrice', shippingAmount);
                                }
                            } else {
                                if (window.checkout.mazegeek_b2352379_discount_breakdown === true || window.checkoutConfig.mazegeek_b2352379_discount_breakdown === true) {
                                    if (typeof rateDetail.deliveryLines[0] != "undefined" && typeof (rateDetail.deliveryLines[0].deliveryRetailPrice) != "undefined") {
                                        shippingAmount = rateDetail.deliveryLines[0].deliveryRetailPrice;
                                    }
                                } else {
                                    if (typeof rateDetail.deliveryLines[0] != "undefined" && typeof (rateDetail.deliveryLines[0].deliveryLinePrice) != "undefined") {
                                        shippingAmount = rateDetail.deliveryLines[0].deliveryLinePrice;
                                    }
                                }
                                if (typeof shippingAmount != "undefined" && typeof (shippingAmount) == 'string') {
                                    shippingAmount = shippingAmount.replaceAll('$', '').replaceAll(',', '').replaceAll('(', '').replaceAll(')', '');
                                }
                            }
                        });
                        if (!shippingAmount) {
                            if (window.e383157Toggle) {
                                fxoStorage.delete("marketplaceShippingPrice");
                            } else {
                                localStorage.removeItem('marketplaceShippingPrice');
                            }
                        }
                        if (allShippingMethodsSelected() && shippingAmount) {
                            var formattedshippingAmount = priceUtils.formatPrice(shippingAmount, quote.getPriceFormat());
                            $(".totals.shipping.excl .price").text(formattedshippingAmount);
                            $(".grand.totals.excl .amount .price").text(formattedshippingAmount);
                        }
                    }

                    if (window.e383157Toggle) {
                        fxoStorage.set("TaxAmount", calculate("taxAmount"));
                    } else {
                        localStorage.setItem("TaxAmount", calculate("taxAmount"));
                    }
                    totalNetAmount = priceFormatWithCurrency(totalNetAmount);
                    grossAmount = priceFormatWithCurrency(grossAmount);
                    var taxAmount = priceFormatWithCurrency(calculate("taxAmount"));
                    if (window.e383157Toggle) {
                        fxoStorage.set("EstimatedTotal", totalNetAmount);
                    } else {
                        localStorage.setItem("EstimatedTotal", totalNetAmount);
                    }
                    $(".grand.totals .amount .price").text(totalNetAmount);
                    $(".grand.totals.incl .price").text(totalNetAmount);
                    $(".totals.sub .amount .price").text(grossAmount);

                    if (totalDiscountAmount) {
                        totalDiscountAmount = priceFormatWithCurrency(totalDiscountAmount);
                        $(".totals.discount.excl .amount .price").text('-' + totalDiscountAmount);
                        $(".totals.fedexDiscount .amount .price").text('-' + totalDiscountAmount);
                    } else {
                        $(".totals.fedexDiscount .amount .price").text('-');
                        $(".totals.discount.excl .amount .price").text('-');
                    }

                    $(".totals-tax .price").text(taxAmount);

                    let accountDiscountHtml = '';

                    if (accountDiscountAmount || volumeDiscountAmount || bundleDiscountAmount || promoDiscountAmount || shippingDiscountAmount) {
                        $(".discount_breakdown tbody tr.discount").remove();
                    }
                    if (accountDiscountAmount == 0 && volumeDiscountAmount == 0 && bundleDiscountAmount == 0 && promoDiscountAmount == 0 && shippingDiscountAmount == 0) {
                        $('.toggle-discount th #discbreak').remove();
                    }
                    let discountAmounts = [{
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
                    }, { "type": "shipping_discount", "price": shippingDiscountAmount, "label": "Shipping Discount" }];
                    let sortedAmounts = discountAmounts.sort((p1, p2) => (p1.price < p2.price) ? 1 : (p1.price > p2.price) ? -1 : 0);
                    sortedAmounts.forEach(function (amount, index) {
                        if (amount.price) {
                            accountDiscountHtml = '<tr class="' + amount.type + ' discount"><th class="mark" scope="row">' + amount.label + '</th><td class="amount"><span class="price">-' + priceFormatWithCurrency(amount.price); +'</span></td></tr>';
                            $(".discount_breakdown tbody").append(accountDiscountHtml);
                            if ($('.toggle-discount th #discbreak').length == 0) {
                                $('.toggle-discount th').append('<span id="discbreak" tabindex="0" class="arrow down"></span>');
                            }
                        } else {
                            $(".discount_breakdown tbody tr." + amount.type).remove();
                        }
                    });

                    $(".opc-block-summary .table-totals").show();
                    var parent = $('.methods-shipping .table-checkout-shipping-method tbody');
                    var allParentsHaveCheckedRadio = true;
                    parent.each(function () {
                        var currentDiv = $(this);
                        var anyChecked = currentDiv.find('input[type="radio"]:checked').length > 0;
                        if (!anyChecked) {
                            allParentsHaveCheckedRadio = false;
                        }
                    });
                    if (marketplaceQuoteHelper.isFullFirstPartyQuote() && !$('.table-checkout-shipping-method .fedex-delivery-methods .radio:checked').length) {
                        allParentsHaveCheckedRadio = false;
                    }
                    if (allParentsHaveCheckedRadio) {
                        $("#shipping-method-buttons-container").show();
                        if ($(".in-branch.shipping-message").length) {
                            $(".in-branch.shipping-message").show();
                        }
                        $(".contact-person-container").removeClass('hide');
                    }
                    else {
                        $(".contact-person-container").addClass('hide');
                    }
                    //handle estimated shipping total display
                    shippingFormAdditionalScript.handleEstimatedShippingTotal(estimatedShippingTotal);
                    if (!togglesAndSettings.isCustomerShippingAccount3PEnabled) {
                        if (estimatedShippingTotal != "TBD" && estimatedShippingTotal != "$0.00" && marketplaceQuoteHelper.isMixedQuote()) {
                            $(".shipping-disclaimer-container .disclaimer-message").text(window.checkoutConfig.shippingAccountMessage);
                            $(".shipping-disclaimer-container").removeClass('hide');
                        } else {
                            $(".shipping-disclaimer-container").addClass('hide');
                        }
                    }

                    if ((shippingMethod["method_code"].indexOf('LOCAL_DELIVERY') > -1)) {
                        $(".opc-local-delivery-method-box").show();
                        $(".opc-shipping-account-number").hide();
                    } else {
                        $(".opc-shipping-account-number").show();
                        if (hasDefaultShippingAccountNumber) {
                            // Apply default shipping account number for company
                            shippingFormAdditionalScript.applyDefaultShippingAccountNumber();
                        }
                        if (shipfedexAccountNumber != "") {
                            $(".shipping_account_number").show();
                        }

                        $(".opc-local-delivery-method-box").hide();
                    }

                    if (isalternate == true) {
                        if (isFirstnameValid && isLastnameValid && isPhonenameValid && isEmailValid) {
                            // Retire this logic when E-513778 toggle is removed
                            disclosureModel.isCampaingAdDisclosureToggleEnable
                                ? self.disableCreateQuoteBtn(false)
                                : $(".create_quote").prop("disabled", false);
                        } else {
                            // Retire this logic when E-513778 toggle is removed
                            disclosureModel.isCampaingAdDisclosureToggleEnable
                                ? self.disableCreateQuoteBtn(true)
                                : $(".create_quote").prop("disabled", true);
                        }
                    } else {
                        // Retire this logic when E-513778 toggle is removed
                        disclosureModel.isCampaingAdDisclosureToggleEnable
                            ? self.disableCreateQuoteBtn(false)
                            : $(".create_quote").prop("disabled", false);
                    }
                    //enable disable continue to payment button
                    shippingFormAdditionalScript.continueToPaymentButtonHandler();
                    window.dispatchEvent(new Event('shipping_method'));
                    /** MARKETPLACE END */
                });

                let restrictedStoreData = togglesAndSettings?.restrictedProductionLocation;
                let recommendedStoreData = window.checkoutConfig?.recommended_production_location;
                if (self.isSimplifiedProductionLocationOn() && isProductionLocationAutomaticallySelected && !restrictedStoreData && !recommendedStoreData) {
                    let productionLocationId = shippingMethod?.extension_attributes?.production_location;
                    if (productionLocationId) {

                        $.ajax({
                            url: urlBuilder.build(
                                "delivery/index/centerDetails"
                            ),
                            type: "POST",
                            data: { locationId: productionLocationId },
                            dataType: "json",
                            async: true,
                            success: function (data) {
                                if (data.hasOwnProperty("errors")) {
                                    $('.error-container').removeClass('api-error-hide');
                                    return true;
                                }
                                let locationFullAddress = data?.address?.address1 + ', ' + data?.address?.city + ', ' +
                                data?.address?.stateOrProvinceCode + ', ' + data?.address?.postalCode;
                                let locationName = data?.name;
                                self.persistSelectedProductionLocation(productionLocationId, locationName, locationFullAddress);
                            }
                        });
                    }
                }
            } else {
                messageList.addErrorMessage({
                    message: "Region code is not available.",
                });
            }
            return true;
        },

        /**
         * Set shipping information handler
         */
        setShippingInformation: function (gotToNextStep = true) {
            jQuery('#warning-message-box').hide();
            var self = this;
            if (this.validateShippingInformation()) {
                quote.billingAddress(null);

                let altFirstName = document.getElementById("alternate_firstname");
                let altLastName = document.getElementById("alternate_lastname");
                let altPhoneNumber = document.getElementById("alternate_phonenumber");
                let altEmail = document.getElementById("alternate_email");
                let altPhoneNumberext = document.getElementById("alternate_ext");
                let fedExShipReferenceAccountNumber = document.getElementById("fedExReferenceAccountNumber") !== null ? document.getElementById("fedExReferenceAccountNumber").value : '';

                quote.shippingAddress()['altFirstName'] = altFirstName?.value;
                quote.shippingAddress()['altLastName'] = altLastName?.value;
                quote.shippingAddress()['altPhoneNumber'] = altPhoneNumber?.value.replace(" ", "").replace("(", "").replace(")", "").replace("-", "");
                quote.shippingAddress()['altEmail'] = altEmail?.value;
                quote.shippingAddress()['altPhoneNumberext'] = altPhoneNumberext?.value;
                quote.shippingAddress()['is_alternate'] = isalternate;
                quote.shippingMethod()['fedexShipAccountNumber'] = shipAccountNumber.length > 0 ? shipAccountNumber : fxoStorage.get('shipping_account_number');
                quote.shippingMethod()['fedexShipReferenceId'] = fedExShipReferenceAccountNumber;

                let pickupData;
                if (window.e383157Toggle) {
                    pickupData = fxoStorage.get('pickupData');
                } else {
                    pickupData = JSON.parse(localStorage.getItem('pickupData'));
                }

                if (this.isMixedQuote() && this.pickupShippingComboKey() && pickupData) {
                    let pickupData;
                    if (window.e383157Toggle) {
                        pickupData = fxoStorage.get('pickupData');
                    } else {
                        pickupData = JSON.parse(localStorage.getItem('pickupData'));
                    }
                    quote.shippingAddress()['altFirstName'] = pickupData?.contactInformation?.alternate_fname;
                    quote.shippingAddress()['altLastName'] = pickupData?.contactInformation?.alternate_lname;
                    quote.shippingAddress()['altPhoneNumber'] = pickupData?.contactInformation?.alternate_number;
                    quote.shippingAddress()['altEmail'] = pickupData?.contactInformation?.alternate_email;
                    quote.shippingAddress()['altPhoneNumberext'] = pickupData?.contactInformation?.alternate_ext;
                    quote.shippingAddress()['is_alternate'] = pickupData?.contactInformation?.isAlternatePerson;

                    let quoteShippingAddress = quote.shippingAddress();
                }

                /* D-101459 */
                quote.shippingMethod()['productionLocation'] = "";
                if ($('#select_production_location').length > 0 && $('#select_production_location').val() > 0) {
                    quote.shippingMethod()['productionLocation'] = $('#select_production_location').val();
                }

                checkoutDataResolver.resolveBillingAddress();
                registry.async("checkoutProvider")(function (checkoutProvider) {
                    var shippingAddressData = checkoutData.getShippingAddressFromData();

                    if (shippingAddressData) {
                        checkoutProvider.set(
                            "shippingAddress",
                            $.extend(
                                true, {},
                                checkoutProvider.get("shippingAddress"),
                                shippingAddressData
                            )
                        );
                    }
                });
                setShippingInformationAction().done(function () {
                    if (!self.checkIsCommercialCustomer() || isSdeStore || isSelfRegCustomer) {
                        let expressCheckout;
                        if (window.e383157Toggle) {
                            expressCheckout = fxoStorage.get("express-checkout");
                        } else {
                            expressCheckout = localStorage.getItem("express-checkout");
                        }

                        if (!self.validateShippingAccountAcknowledgementCheckbox(true)) {
                            return;
                        }

                        if (isFclCustomer && expressCheckout) {
                            if (!profileSessionBuilder.getPreferredDeliveryMethod()) {
                                expressCheckoutShiptoBuilder.setPrefferedDeliveryMethod('DELIVERY', 'NULL');
                            }
                            if (gotToNextStep && !expressCheckoutShiptoBuilder.setPaymentData()) {
                                stepNavigator.next();
                            }
                        } else if (gotToNextStep) {
                            stepNavigator.next();
                        }
                        //Since continue to payment will be disabled once navigated enable it
                        shippingFormAdditionalScript.continueToPaymentButtonHandler();
                    }
                });
            }
        },

        /**
         * @return {Boolean}
         */
        validateShippingInformation: function () {
            var shippingAddress,
                addressData,
                loginFormSelector = "form[data-role=email-with-possible-login]",
                emailValidationResult = isLoggedIn,
                field,
                country = registry.get(
                    this.parentName +
                    ".shippingAddress.shipping-address-fieldset.country_id"
                ),
                countryIndexedOptions = country.indexedOptions,
                option =
                    countryIndexedOptions[quote.shippingAddress().countryId],
                messageContainer = registry.get("checkout.errors")
                    .messageContainer;

            if (!quote.shippingMethod()) {
                this.errorValidationMessage(
                    $t("The shipping method is missing. Select the shipping method and try again.")
                );
                return false;
            }

            if (!isLoggedIn) {
                emailValidationResult = true;
            }

            if (this.isFormInline) {
                this.source.set("params.invalid", false);
                this.triggerShippingDataValidateEvent();

                if (
                    (emailValidationResult &&
                        this.source.get("params.invalid")) ||
                    !quote.shippingMethod()["method_code"] ||
                    !quote.shippingMethod()["carrier_code"]
                ) {
                    this.focusInvalid();
                    return false;
                }

                shippingAddress = quote.shippingAddress();
                addressData = addressConverter.formAddressDataToQuoteAddress(
                    this.source.get("shippingAddress")
                );

                //Copy form data to quote shipping address object
                for (field in addressData) {
                    if (
                        addressData.hasOwnProperty(field) && //eslint-disable-line max-depth
                        shippingAddress.hasOwnProperty(field) &&
                        typeof addressData[field] != "function" &&
                        _.isEqual(shippingAddress[field], addressData[field])
                    ) {
                        shippingAddress[field] = addressData[field];
                    } else if (
                        typeof addressData[field] != "function" &&
                        !_.isEqual(shippingAddress[field], addressData[field])
                    ) {
                        shippingAddress = addressData;
                        break;
                    }
                }

                if (shippingAddress["regionId"] !== 'undefined' && shippingAddress !== "") {
                    var shippingRegionIdOpt = $('select[name="region_id"] option[value=\"' + shippingAddress["regionId"] + '\"]').data('title')
                    if (shippingRegionIdOpt && shippingAddress["region"] != shippingRegionIdOpt) {
                        shippingAddress["region"] = shippingRegionIdOpt;
                    }
                    if (shippingRegionIdOpt && shippingAddress["regionCode"] != shippingRegionIdOpt) {
                        shippingAddress["regionCode"] = shippingRegionIdOpt;
                    }
                }

                if (isLoggedIn) {
                    shippingAddress["save_in_address_book"] = 1;
                }
                selectShippingAddress(shippingAddress);
            } else if (
                isLoggedIn &&
                option &&
                option["is_region_required"] &&
                !quote.shippingAddress().region
            ) {
                messageContainer.addErrorMessage({
                    message: $t(
                        "Please specify a regionId in shipping address."
                    ),
                });

                return false;
            }
            if (!emailValidationResult) {
                $(loginFormSelector + " input[name=username]").trigger('focus');
                return false;
            }

            return true;
        },

        /**
         * Trigger Shipping data Validate Event.
         */
        triggerShippingDataValidateEvent: function () {
            this.source.trigger("shippingAddress.data.validate");

            if (this.source.get("shippingAddress.custom_attributes")) {
                this.source.trigger(
                    "shippingAddress.custom_attributes.data.validate"
                );
            }
        },

        isCheckShipping: function (data) {
            this.showShippingContent(data);
        },

        isCheckPickup: function (data) {
            this.showPickupContent(data);
        },

        onclickTriggerShip: function (config, event, saveDeliveryMethod = false, showToastMessage = true) {
            $(".root-container").removeClass("edit-pickup-step-section");
            $(".shipping-content-checkout").removeClass("edit-pickup-step-section");

            if (this.disclosureModel.isCampaingAdDisclosureToggleEnable) {
                this.disclosureModel.clearAllData();
                this.disclosureModel.shouldDisplayInlineEproQuestionnaire(false);
            }

            $(".img-close-pop").trigger("click");
            this.shippingShippingExclShippingMessageHide();
            if (this.enableEarlyShippingAccountIncorporation()) {
                this.hideremoveShippingAccountNumberwhennull()
            }
            if (isLoggedIn) {
                $("body").removeClass("shipkey");
            }

            if (window.e383157Toggle) {
                fxoStorage.set("shipkey", 'false');
                fxoStorage.set("pickupkey", 'true');
            } else {
                localStorage.setItem("shipkey", 'false');
                localStorage.setItem("pickupkey", 'true');
            }

            this.onclickTriggerShipShow(false);
            this.onclickTriggerPickupShow(true);
            this.isCheckShipping(false);
            //D-72340 | Pickup Map should be hidden after error
            let errorInPickup;
            if (window.e383157Toggle) {
                fxoStorage.set('changeDelMethodShowSuccMessage', "pickup");
                errorInPickup = fxoStorage.get("errorInPickup");
            } else {
                localStorage.setItem('changeDelMethodShowSuccMessage', "pickup");
                errorInPickup = localStorage.getItem("errorInPickup");
            }
            if (errorInPickup == 'false') {
                this.isCheckPickup(true);
            }
            this.inBranchDocSectionCall();
            /* update item row total B-1105765 */
            if (togglesAndSettings.hcoPriceUpdate) {
                $(".subtotal-instance").hide();
                $(".checkout-normal-price").show();
                $(".pickup-location-list-container").css('display', 'none');
                $(".contact-from-container").css('display', 'none');
                $(".place-pickup-order").css('display', 'none');
            }
            let expressCheckout;
            if (window.e383157Toggle) {
                expressCheckout = fxoStorage.get('express-checkout');
            } else {
                expressCheckout = localStorage.getItem('express-checkout');
            }
            if (isFclCustomer && expressCheckout) {
                let preferredDelivery = profileSessionBuilder.getPreferredDeliveryMethod();
                let profileAddress = profileSessionBuilder.getProfileAddress();
                profilePickEditBuilder.autofillPickupAddress(preferredDelivery, profileAddress);
            }

            /**
             * ###############################################################
             *                   Start | Marketplace Section
             * ###############################################################
             */

            this.checkoutTitle($t('In-store pickup'));

            if (saveDeliveryMethod) {
                this.chosenDeliveryMethod('pick-up');

                if (this.isMixedQuote() && !this.pickupShippingComboKey()) {
                    this.isPickupFormFilled(false);
                }
                window.dispatchEvent(new Event('on_change_delivery_method'));
            }

            if (showToastMessage) {
                this.showPopupDeliveryMethodChange();
            }

            /**
             * ###############################################################
             *                   End | Marketplace Section
             * ###############################################################
             */
            this.inBranchDocSectionCall();
            this.handleSDEstorePickupVisibility()
        },

        setDefaultShipping: function () {
            const self = this;

            if ($('#shipping_account_number_acknowledgement').length > 0) {
                return;
            }

            console.log("setDefaultShipping called here...!!");
            let fcl_shipping_data_status = togglesAndSettings?.fclCustomerDefaultShippingData?.status ?? 'Failure';
            console.log("fcl_shipping_data_status", fcl_shipping_data_status);

            if (isSelfRegCustomer && togglesAndSettings.fclCustomerDefaultShippingData !== undefined && togglesAndSettings.fclCustomerDefaultShippingData != "" && fcl_shipping_data_status != "Failure") {
                var customerInfo = customerData.get('checkout-data')();
                let setDefaultInterval = setInterval(function () {
                    if ($(".form-shipping-address input[name^='firstname']").length > 0) {
                        var firstname = $(".form-shipping-address input[name^='firstname']").val();
                        if (typeof (firstname) != 'undefined' || firstname == '') {
                            if (typeof customerInfo.shippingAddressFromData.firstname !== 'undefined' && customerInfo.shippingAddressFromData.firstname !== null) {
                                $(".form-shipping-address input[name^='firstname']").val(customerInfo.shippingAddressFromData.firstname);
                            }
                        }

                        var lastname = $(".form-shipping-address input[name^='lastname']").val();
                        if (typeof (lastname) != 'undefined' || lastname == '') {
                            if (typeof customerInfo.shippingAddressFromData.lastname !== 'undefined' && customerInfo.shippingAddressFromData.lastname !== null) {
                                $(".form-shipping-address input[name^='lastname']").val(customerInfo.shippingAddressFromData.lastname);
                            }
                        }

                        var email = $(".form-shipping-address input[name^='custom_attributes[email_id]']").val();
                        if (typeof (email) != 'undefined' || email == '') {
                            if (typeof customerInfo.shippingAddressFromData.custom_attributes['email_id'] !== 'undefined' && customerInfo.shippingAddressFromData.custom_attributes['email_id'] !== null) {
                                $(".form-shipping-address input[name^='custom_attributes[email_id]']").val(customerInfo.shippingAddressFromData.custom_attributes['email_id']);
                            }
                        }

                        var company = $(".form-shipping-address input[name^='company']").val();
                        if (typeof (company) != 'undefined' || company == '') {
                            if (typeof customerInfo.shippingAddressFromData.company !== 'undefined' && customerInfo.shippingAddressFromData.company !== null) {
                                $(".form-shipping-address input[name^='company']").val(customerInfo.shippingAddressFromData.company);
                            }
                        }

                        var street_0 = $(".form-shipping-address input[name^='street[0]']").val();
                        if (typeof (street_0) != 'undefined' || street_0 == '') {
                            if (typeof customerInfo.shippingAddressFromData.street[0] !== 'undefined' && customerInfo.shippingAddressFromData.street[0] !== null) {
                                $(".form-shipping-address input[name^='street[0]']").val(customerInfo.shippingAddressFromData.street[0]);
                            }
                        }

                        var street_1 = $(".form-shipping-address input[name^='street[1]']").val();
                        if (typeof (street_1) != 'undefined' || street_1 == '') {
                            if (typeof customerInfo.shippingAddressFromData.street[1] !== 'undefined' && customerInfo.shippingAddressFromData.street[1] !== null) {
                                $(".form-shipping-address input[name^='street[1]']").val(customerInfo.shippingAddressFromData.street[1]);
                            }
                        }

                        var city = '';
                        city = checkoutAdditionalScript.allowCityCharacters($(".form-shipping-address input[name^='city']").val());
                        if (typeof (city) != 'undefined' || street_1 == '') {
                            if (typeof customerInfo.shippingAddressFromData.city !== 'undefined' && customerInfo.shippingAddressFromData.city !== null) {
                                $(".form-shipping-address input[name^='city']").val(checkoutAdditionalScript.allowCityCharacters(customerInfo.shippingAddressFromData.city));
                            }
                        }

                        var region_id = $(".form-shipping-address select[name^='region_id']").val();
                        if (typeof (region_id) != 'undefined' || region_id == '') {
                            if (typeof customerInfo.shippingAddressFromData.region_id !== 'undefined' && customerInfo.shippingAddressFromData.region_id !== null) {
                                $(".form-shipping-address select[name^='region_id']").val(customerInfo.shippingAddressFromData.region_id);
                            }
                        }

                        var postcode = $(".form-shipping-address input[name^='postcode']").val();
                        if (typeof (postcode) != 'undefined' || postcode == '') {
                            if (typeof customerInfo.shippingAddressFromData.postcode !== 'undefined' && customerInfo.shippingAddressFromData.postcode !== null) {
                                $(".form-shipping-address input[name^='postcode']").val(customerInfo.shippingAddressFromData.postcode);
                            }
                        }

                        var telephone = $(".form-shipping-address input[name^='telephone']").val();
                        if (typeof (telephone) != 'undefined' || telephone == '') {
                            if (typeof customerInfo.shippingAddressFromData.telephone !== 'undefined' && customerInfo.shippingAddressFromData.telephone !== null) {
                                var customerPhoneNumber = customerInfo.shippingAddressFromData.telephone;
                                if (customerPhoneNumber == '(111) 111-1111') {
                                    customerPhoneNumber = '';
                                }
                                $(".form-shipping-address input[name^='telephone']").val(customerPhoneNumber);
                            }
                        }

                        var ext = $(".form-shipping-address input[name^='custom_attributes[ext]']").val();
                        if (typeof (ext) != 'undefined' || ext == '') {
                            if (typeof customerInfo.shippingAddressFromData.custom_attributes['ext'] !== 'undefined' && customerInfo.shippingAddressFromData.custom_attributes['ext'] !== null) {
                                $(".form-shipping-address input[name^='custom_attributes[ext]']").val(customerInfo.shippingAddressFromData.custom_attributes['ext']);
                            }
                        }
                        $(".form-shipping-address input").trigger('change');
                        self.triggerShippingResults();
                        clearInterval(setDefaultInterval);
                    }
                }, 500);
            }
        },

        onclickTriggerPickup: function (config, event, saveDeliveryMethod = false, showToastMessage = true) {
            if (event && event.currentTarget.id === 'change2shipping') {
                this.isPickupFormFilled(false);
            }
            $(".img-close-pop").trigger("click");

            if (this.disclosureModel.isCampaingAdDisclosureToggleEnable) {
                this.disclosureModel.clearAllData();
                this.disclosureModel.shouldDisplayInlineEproQuestionnaire(false);
            }
            if (isSelfRegCustomer || (isFclCustomer == true && isLoggedIn == false)) {
                var defaultShippingAddress = togglesAndSettings.fclCustomerDefaultShippingData;
                var customerInfo = customerData.get('checkout-data')();

                const populateShippingFormFields = function () {
                    if (!customerInfo.shippingAddressFromData) {
                        return true;
                    }

                    var firstname = $(".form-shipping-address input[name^='firstname']").val();
                    if (typeof (firstname) != 'undefined' || firstname == '') {
                        if (typeof customerInfo.shippingAddressFromData.firstname !== 'undefined' && customerInfo.shippingAddressFromData.firstname !== null) {
                            $(".form-shipping-address input[name^='firstname']").val(customerInfo.shippingAddressFromData.firstname);
                        }
                    }

                    var lastname = $(".form-shipping-address input[name^='lastname']").val();
                    if (typeof (lastname) != 'undefined' || lastname == '') {
                        if (typeof customerInfo.shippingAddressFromData.lastname !== 'undefined' && customerInfo.shippingAddressFromData.lastname !== null) {
                            $(".form-shipping-address input[name^='lastname']").val(customerInfo.shippingAddressFromData.lastname);
                        }
                    }

                    var email = $(".form-shipping-address input[name^='custom_attributes[email_id]']").val();
                    if (typeof (email) != 'undefined' || email == '') {
                        if (typeof customerInfo.shippingAddressFromData.custom_attributes['email_id'] !== 'undefined' && customerInfo.shippingAddressFromData.custom_attributes['email_id'] !== null) {
                            $(".form-shipping-address input[name^='custom_attributes[email_id]']").val(customerInfo.shippingAddressFromData.custom_attributes['email_id']);
                        }
                    }

                    var company = $(".form-shipping-address input[name^='company']").val();
                    if (typeof (company) != 'undefined' || company == '') {
                        if (typeof customerInfo.shippingAddressFromData.company !== 'undefined' && customerInfo.shippingAddressFromData.company !== null) {
                            $(".form-shipping-address input[name^='company']").val(customerInfo.shippingAddressFromData.company);
                        }
                    }

                    var street_0 = $(".form-shipping-address input[name^='street[0]']").val();
                    if (typeof (street_0) != 'undefined' || street_0 == '') {
                        if (typeof customerInfo.shippingAddressFromData.street[0] !== 'undefined' && customerInfo.shippingAddressFromData.street[0] !== null) {
                            $(".form-shipping-address input[name^='street[0]']").val(customerInfo.shippingAddressFromData.street[0]);
                        }
                    }

                    var street_1 = $(".form-shipping-address input[name^='street[1]']").val();
                    if (typeof (street_1) != 'undefined' || street_1 == '') {
                        if (typeof customerInfo.shippingAddressFromData.street[1] !== 'undefined' && customerInfo.shippingAddressFromData.street[1] !== null) {
                            $(".form-shipping-address input[name^='street[1]']").val(customerInfo.shippingAddressFromData.street[1]);
                        }
                    }

                    var city = '';
                    city = checkoutAdditionalScript.allowCityCharacters($(".form-shipping-address input[name^='city']").val());
                    if (typeof (city) != 'undefined' || street_1 == '') {
                        if (typeof customerInfo.shippingAddressFromData.city !== 'undefined' && customerInfo.shippingAddressFromData.city !== null) {
                            $(".form-shipping-address input[name^='city']").val(checkoutAdditionalScript.allowCityCharacters(customerInfo.shippingAddressFromData.city));
                        }
                    }

                    var region = $(".form-shipping-address select[name^='region_id']").val();
                    if (typeof (region) != 'undefined' || region == '') {
                        if (typeof customerInfo.shippingAddressFromData.region_id !== 'undefined' && customerInfo.shippingAddressFromData.region_id !== null) {
                            $(".form-shipping-address select[name^='region_id']").val(customerInfo.shippingAddressFromData.region_id);
                        }
                    }

                    var postcode = $(".form-shipping-address input[name^='postcode']").val();
                    if (typeof (postcode) != 'undefined' || postcode == '') {
                        if (typeof customerInfo.shippingAddressFromData.postcode !== 'undefined' && customerInfo.shippingAddressFromData.postcode !== null) {
                            $(".form-shipping-address input[name^='postcode']").val(customerInfo.shippingAddressFromData.postcode);
                        }
                    }

                    var telephone = $(".form-shipping-address input[name^='telephone']").val();
                    if (typeof (telephone) != 'undefined' || telephone == '') {
                        if (typeof customerInfo.shippingAddressFromData.telephone !== 'undefined' && customerInfo.shippingAddressFromData.telephone !== null) {
                            var customerPhoneNumber = customerInfo.shippingAddressFromData.telephone;
                            if (customerPhoneNumber == '(111) 111-1111') {
                                customerPhoneNumber = '';
                            }
                            $(".form-shipping-address input[name^='telephone']").val(customerPhoneNumber);
                        }
                    }

                    var ext = $(".form-shipping-address input[name^='custom_attributes[ext]']").val();
                    if (typeof (ext) != 'undefined' || ext == '') {
                        if (typeof customerInfo.shippingAddressFromData.custom_attributes['ext'] !== 'undefined' && customerInfo.shippingAddressFromData.custom_attributes['ext'] !== null) {
                            $(".form-shipping-address input[name^='custom_attributes[ext]']").val(customerInfo.shippingAddressFromData.custom_attributes['ext']);
                        }
                    }
                };

                $do.get(".form-shipping-address select[name^='region_id']", function () {
                    populateShippingFormFields();
                });
            }
            $(".opc-block-summary .table-totals .totals.shipping.excl").show();
            if (isLoggedIn) {
                $("body").addClass("shipkey");
            }

            $(".root-container").removeClass("edit-pickup-step-section");
            $(".shipping-content-checkout").removeClass("edit-pickup-step-section");

            if (window.e383157Toggle) {
                fxoStorage.set("shipkey", 'true');
                fxoStorage.set("pickupkey", 'false');
            } else {
                localStorage.setItem("shipkey", true);
                localStorage.setItem("pickupkey", false);
            }

            this.onclickTriggerShipShow(true);
            this.onclickTriggerPickupShow(false);
            this.isCheckShipping(true);
            this.isCheckPickup(false);

            if (window.e383157Toggle) {
                fxoStorage.set('changeDelMethodShowSuccMessage', "shipping");
                if (fxoStorage.get('pickupShippingComboKey') != "true") {
                    this.pickUpJson([]);
                }
            } else {
                localStorage.setItem('changeDelMethodShowSuccMessage', "shipping");
                if (window.localStorage.pickupShippingComboKey != "true") {
                    this.pickUpJson([]);
                }
            }

            if (isSdeStore) {
                this.checkoutTitle($t('Shipping Location'));
            }

            /* update item row total B-1105765 */
            if (togglesAndSettings.hcoPriceUpdate) {
                $(".subtotal-instance").hide();
                $(".checkout-normal-price").show();
                $("#co-shipping-form").trigger("reset");
                $("#opc-shipping_method .checkout-shipping-method").css('display', 'none');
                $("#shipping-method-buttons-container").css('display', 'none');
                $(".place-pickup-order").css('display', 'none');
            }

            if (isSelfRegCustomer || (isFclCustomer == true && isLoggedIn == false)) {
                var defaultShippingAddress = togglesAndSettings.fclCustomerDefaultShippingData;
                var customerInfo = customerData.get('checkout-data')();
                var firstname = $(".form-shipping-address input[name^='firstname']").val();
                if (typeof (firstname) != 'undefined' || firstname == '') {
                    firstname = customerInfo.shippingAddressFromData.firstname;
                    if (typeof (firstname) != 'undefined' || firstname == '') {
                        firstname = defaultShippingAddress['firstname'];
                    }
                    $(".form-shipping-address input[name^='firstname']").val(firstname);
                }
                var lastname = $(".form-shipping-address input[name^='lastname']").val();
                if (typeof (lastname) != 'undefined' || lastname == '') {
                    lastname = customerInfo.shippingAddressFromData.lastname;
                    if (typeof (lastname) != 'undefined' || lastname == '') {
                        lastname = defaultShippingAddress['lastname'];
                    }
                    $(".form-shipping-address input[name^='lastname']").val(lastname);
                }
                var email = $(".form-shipping-address input[name^='custom_attributes[email_id]']").val();
                if (typeof (email) != 'undefined' || email == '') {
                    email = customerInfo.shippingAddressFromData.custom_attributes['email_id'];
                    if (typeof (email) != 'undefined' || email == '') {
                        email = defaultShippingAddress['email'];
                    }
                    $(".form-shipping-address input[name^='custom_attributes[email_id]']").val(email);
                }
                var company = $(".form-shipping-address input[name^='company']").val();
                if (typeof (company) != 'undefined' || company == '') {
                    company = customerInfo.shippingAddressFromData.company;
                    if (typeof (company) != 'undefined' || company == '') {
                        company = defaultShippingAddress['company'];
                    }
                    $(".form-shipping-address input[name^='company']").val(company);
                }
                var street_0 = $(".form-shipping-address input[name^='street[0]']").val();
                if (typeof (street_0) != 'undefined' || street_0 == '') {
                    street_0 = customerInfo.shippingAddressFromData.street[0];
                    if (typeof (street_0) != 'undefined' || street_0 == '') {
                        street_0 = defaultShippingAddress['streetOne'];
                    }
                    $(".form-shipping-address input[name^='street[0]']").val(street_0);
                }
                var street_1 = $(".form-shipping-address input[name^='street[1]']").val();
                if (typeof (street_1) != 'undefined' || street_1 == '') {
                    street_1 = customerInfo.shippingAddressFromData.street[1];
                    if (typeof (street_1) != 'undefined' || street_1 == '') {
                        street_1 = defaultShippingAddress['streetTwo'];
                    }
                    $(".form-shipping-address input[name^='street[1]']").val(street_1);
                }

                var city = '';
                city = checkoutAdditionalScript.allowCityCharacters($(".form-shipping-address input[name^='city']").val());
                if (typeof (city) != 'undefined' || city == '') {
                    city = checkoutAdditionalScript.allowCityCharacters(customerInfo.shippingAddressFromData.city);
                    if (typeof (city) != 'undefined' || city == '') {
                        city = checkoutAdditionalScript.allowCityCharacters(defaultShippingAddress['city']);
                    }
                    $(".form-shipping-address input[name^='city']").val(city);
                }

                var region_id = $(".form-shipping-address select[name^='region_id']").val();
                if (typeof region_id != 'undefined' || region_id == '') {
                    region_id = customerInfo.shippingAddressFromData.region_id;
                    if (typeof region_id != 'undefined' || region_id == '') {
                        region_id = defaultShippingAddress['region'];
                    }
                    $(".form-shipping-address select[name^='region_id']").val(region_id);
                }

                var postcode = $(".form-shipping-address input[name^='postcode']").val();
                if (typeof (postcode) != 'undefined' || postcode == '') {
                    postcode = customerInfo.shippingAddressFromData.postcode;
                    if (typeof (postcode) != 'undefined' || postcode == '') {
                        postcode = defaultShippingAddress['postcode'];
                    }
                    $(".form-shipping-address input[name^='postcode']").val(postcode);
                }
                var telephone = $(".form-shipping-address input[name^='telephone']").val();
                if (typeof (telephone) != 'undefined' || telephone == '') {
                    var customerPhoneNumber = customerInfo.shippingAddressFromData.telephone;
                    if (typeof (customerPhoneNumber) != 'undefined' || customerPhoneNumber == '') {
                        customerPhoneNumber = defaultShippingAddress['telephone'];
                    }
                    if (customerPhoneNumber == '(111) 111-1111') {
                        customerPhoneNumber = '';
                    }
                    $(".form-shipping-address input[name^='telephone']").val(customerPhoneNumber);
                }
                var ext = $(".form-shipping-address input[name^='custom_attributes[ext]']").val();
                if (typeof (ext) != 'undefined' || ext == '') {
                    if (typeof customerInfo.shippingAddressFromData.custom_attributes['ext'] != 'undefined' || customerInfo.shippingAddressFromData.custom_attributes['ext'] == '') {
                        $(".form-shipping-address input[name^='custom_attributes[ext]']").val(customerInfo.shippingAddressFromData.custom_attributes['ext']);
                    }
                }
                var acc = $(".fedex_account_number-field").val();
                if (acc?.length) {
                    shippingFormAdditionalScript.maskShippingAccountNumber(acc);
                }
                $(".form-shipping-address input,select").trigger("change");

                this.triggerShippingResults();
            }

            /**
             * ###############################################################
             *                   Start | Marketplace Section
             * ###############################################################
             */

            this.checkoutTitle($t('Shipping'));

            if (saveDeliveryMethod) {
                this.chosenDeliveryMethod('shipping');

                if (this.isMixedQuote() && this.isPickupFormFilled()) {
                    this.pickupShippingComboKey(true);
                }

                window.dispatchEvent(new Event('on_change_delivery_method'));
            }

            if (this.isMixedQuote() && !this.isPickupFormFilled()) {
                this.pickupShippingComboKey(false);
            }

            if (showToastMessage) {
                this.showPopupDeliveryMethodChange();
            }

            /**
             * ###############################################################
             *                   End | Marketplace Section
             * ###############################################################
             */
        },

        showPopupDeliveryMethodChange: function () {
            let deliveryMethod;
            if (window.e383157Toggle) {
                deliveryMethod = fxoStorage.get('changeDelMethodShowSuccMessage');
            } else {
                deliveryMethod = localStorage.getItem('changeDelMethodShowSuccMessage');
            }
            if (deliveryMethod && (typeof deliveryMethod !== 'undefined')) {
                const messageTitle = togglesAndSettings.toastTitle;
                const pickupMessage = togglesAndSettings.toastPickupContent;
                const deliveryMessage = togglesAndSettings.toastShippingContent;

                let successMsg = "";
                let outermostDivHtml = $(".change-deliv-method-msg-outer-most");

                if (deliveryMethod == "pickup") {
                    successMsg = pickupMessage;
                } else if (deliveryMethod == "shipping") {
                    successMsg = deliveryMessage;
                }

                let msgHtml = '<div class="change-deliv-method-msg-outer-most checkout-success-close-trigger-class"><div class="express-msg-outer-container"><div class="express-succ-msg-container"><span class="icon-container"><img class="img-check-icon" alt="Check icon" src="' + checkIcon + '"></span><p class="mb-0">' + messageTitle + '</p><span class="message fedex-regular">' + successMsg + '</span><img id="express_msg_close" class="img-close-msg" alt="close icon" src="' + crossIcon + '" tabindex="0"></div> </div></div>';

                if (outermostDivHtml) {
                    outermostDivHtml.remove();
                }
                $(msgHtml).insertAfter(".opc-progress-bar");
                if (window.e383157Toggle) {
                    fxoStorage.delete('changeDelMethodShowSuccMessage');
                } else {
                    localStorage.removeItem('changeDelMethodShowSuccMessage');
                }

            }
        },

        triggerPickupPagination: function (pickUpResponceData) {
            var pickUpResponceDataPaged = pickUpResponceData.slice(0, 3);
            self.pickUpJson(pickUpResponceDataPaged);
        },

        isContactFormValid: function () {
            let createQuote = $('.create_quote');
            if (isalternate) {
                let createQuote = $('.create_quote');
                if (isFirstnameValid && isLastnameValid && isPhonenameValid && isEmailValid) {
                    // Retire this logic when E-513778 toggle is removed
                    disclosureModel.isCampaingAdDisclosureToggleEnable
                        ? this.disableCreateQuoteBtn(false)
                        : createQuote.prop("disabled", false);
                }
                else {
                    // Retire this logic when E-513778 toggle is removed
                    disclosureModel.isCampaingAdDisclosureToggleEnable
                        ? this.disableCreateQuoteBtn(true)
                        : createQuote.prop("disabled", true);
                }
            }
            else {
                // Retire this logic when E-513778 toggle is removed
                disclosureModel.isCampaingAdDisclosureToggleEnable
                    ? this.disableCreateQuoteBtn(false)
                    : createQuote.prop("disabled", false);
            }
            shippingFormAdditionalScript.continueToPaymentButtonHandler();
        },

        /**
         * call Validate shipping information Form
         */
        shippingInformationValidation: function () {
            if (this.enableEarlyShippingAccountIncorporation()) {
                $('.checkout-shipping-method').show();
            }
            var shippingAddress,
                addressData,
                loginFormSelector = "form[data-role=email-with-possible-login]",
                emailValidationResult = isLoggedIn,
                field,
                country = registry.get(this.parentName + ".shippingAddress.shipping-address-fieldset.country_id"),
                countryIndexedOptions = country.indexedOptions,
                option = countryIndexedOptions[quote.shippingAddress().countryId],
                messageContainer = registry.get("checkout.errors").messageContainer;

            if (!isLoggedIn) {
                emailValidationResult = true;
            }

            /** B-878274 | show shipping methods when, production location is selected only**/
            if (!explorersRestrictedAndRecommendedProduction) {
                if ($('input:radio[name="production-location"]').length > 0 && !$('input:radio[name="production-location"]').is(':checked')) {
                    messageContainer.addErrorMessage({ message: $t("Choose a production location first.") });
                    return false;
                }
            }

            if (this.isFormInline) {
                this.source.set("params.invalid", false);
                this.triggerShippingDataValidateEvent();

                if (this.source.get("params.invalid")) {
                    this.focusInvalid();
                    return false;
                }

                shippingAddress = quote.shippingAddress();
                addressData = this.source.get("shippingAddress");
                for (const key in addressData.custom_attributes) {
                    if (addressData.custom_attributes[key] === 0 || addressData.custom_attributes[key] === 1) {
                        addressData.custom_attributes[key] = !!addressData.custom_attributes[key];
                    }
                }
                addressData = addressConverter.formAddressDataToQuoteAddress(addressData);

                //Copy form data to quote shipping address object
                for (field in addressData) {
                    if (
                        addressData.hasOwnProperty(field) && //eslint-disable-line max-depth
                        shippingAddress.hasOwnProperty(field) &&
                        typeof addressData[field] != "function" &&
                        _.isEqual(shippingAddress[field], addressData[field])
                    ) {
                        shippingAddress[field] = addressData[field];
                    } else if (
                        typeof addressData[field] != "function" &&
                        !_.isEqual(shippingAddress[field], addressData[field])
                    ) {
                        shippingAddress = addressData;
                        break;
                    }
                }

                if (shippingAddress["regionId"] !== 'undefined' && shippingAddress !== "") {
                    var shippingRegionIdOpt = $('select[name="region_id"] option[value=\"' + shippingAddress["regionId"] + '\"]').data('title')
                    if (shippingRegionIdOpt && shippingAddress["region"] != shippingRegionIdOpt) {
                        shippingAddress["region"] = shippingRegionIdOpt;
                    }
                    if (shippingRegionIdOpt && shippingAddress["regionCode"] != shippingRegionIdOpt) {
                        shippingAddress["regionCode"] = shippingRegionIdOpt;
                    }
                }

                if (isLoggedIn) {
                    shippingAddress["save_in_address_book"] = 1;
                }

                let freightComponent = registry.get(this.parentName +
                    '.shippingAddress.before-shipping-form-submit.shipping-freight');

                if (freightComponent && _.isNull(freightComponent.hasLiftGate())) {
                    messageContainer.addErrorMessage({
                        message: $t(
                            "Please specify if there is a raised loading dock at your delivery address"
                        ),
                    });
                    return false;
                }

                $("#get-Shipping-result").prop("disabled", false);
                selectShippingAddress(shippingAddress);

            } else if (
                isLoggedIn &&
                option &&
                option["is_region_required"] &&
                !quote.shippingAddress().region
            ) {
                messageContainer.addErrorMessage({
                    message: $t("Please specify a regionId in shipping address."),
                });

                return false;
            }

            if (!emailValidationResult) {
                $(loginFormSelector + " input[name=username]").trigger('focus');
                return false;
            }

            return true;
        },

        /**
         * Apply fedex shipping account Number
         */
        isApplyShippingAccountNumber: function (fedExAccountNumber, applyRemove) {
            jQuery(".loading-mask").show();
            let self = this;
            let shipMethod = "";
            const createQuoteButton = $(".create_quote");
            const errorContainer = $('.error-container');
            const checkoutShippingMethod = $('.checkout-shipping-method');
            const formShippingAddress = $(".form-shipping-address");
            let shippingAddress = quote.shippingAddress();
            let street = shippingAddress['street'];
            let company = shippingAddress['company'];
            let postCode = shippingAddress["postcode"];
            let regionCode = shippingAddress['regionId'];
            let isRateApi = callRateApiShippingAccountValidation || false;
            let city = checkoutAdditionalScript.allowCityCharacters(shippingAddress['city']);
            const removeAccBtn = $('.shipping_account_number.child-box').find('.fedex_account_number_remove');
            let defaultShippingAccountNumber = shippingFormAdditionalScript.getDefaultShippingAccountNumber();

            let firstname = formShippingAddress.find("input[name='firstname']").val();
            let lastname = formShippingAddress.find("input[name='lastname']").val();
            let email = formShippingAddress.find("input[name='custom_attributes[email_id]']").val();
            let telephone = formShippingAddress.find("input[name='telephone']").val();
            if (typeof (telephone) !== 'undefined') {
                telephone = telephone.replace(/\D/g, '');
            }
            var isResidenceShipping = null;
            if (isExplorersAddressClassificationFixToggleEnable) {
                if (tiger_d213977) {
                    var address = quote.shippingAddress();
                    isResidenceShipping = address?.customAttributes?.find(attr => attr.attribute_code === "residence_shipping")?.value || false;
                } else {
                    isResidenceShipping = false;
                    var checkoutShippingDetails = customerData.get('checkout-data')();
                    var shippingFormDetails = checkoutShippingDetails.shippingAddressFromData;
                    if (typeof shippingFormDetails !== 'undefined' && shippingFormDetails != null && typeof shippingFormDetails.custom_attributes !== 'undefined' && shippingFormDetails.custom_attributes != null && shippingFormDetails.custom_attributes !== '' && typeof shippingFormDetails.custom_attributes.residence_shipping !== 'undefined') {
                        isResidenceShipping = shippingFormDetails.custom_attributes.residence_shipping;
                    }
                }
            }
            if (self.enableEarlyShippingAccountIncorporation()) {
                let isEpro = togglesAndSettings.isEpro;
                if (quote.shippingMethod() != null && !isRateApi) {
                    shipMethod = quote.shippingMethod()["method_code"];
                } else if ((isSdeStore || isLoggedIn == false || isEpro) && defaultShippingAccountNumber == '') {
                    shipMethod = 'GROUND_US';
                } else if (defaultShippingAccountNumber == '') {
                    shipMethod = 'FEDEX_HOME_DELIVERY';
                }
            } else if (quote.shippingMethod() != null) {
                shipMethod = quote.shippingMethod()["method_code"];
            }
            if (window.e383157Toggle) {
                fxoStorage.set("shipping_account_number", fedExAccountNumber);
            } else {
                localStorage.setItem("shipping_account_number", fedExAccountNumber);
            }

            const shippingAccountCheckboxValue = fxoStorage.get('shippingAccountCheckboxValue');
            if (shippingAccountCheckboxValue !== undefined && shippingAccountCheckboxValue === false && applyRemove !== 'remove') {
                $('.loading-mask').hide();
                return true;
            }

            if (regionCode != 'undefined' && regionCode != '') {
                const requestUrl = urlBuilder.build("delivery/index/deliveryrateapishipandpickup");
                $.ajax({
                    url: requestUrl,
                    type: "POST",
                    data: {
                        firstname: firstname,
                        lastname: lastname,
                        email: email,
                        telephone: telephone,
                        ship_method: shipMethod,
                        zipcode: postCode,
                        region_id: regionCode,
                        city: city,
                        street: street,
                        company: company,
                        fedEx_account_number: fedExAccountNumber,
                        isRateApi: isRateApi,
                        is_residence_shipping: isResidenceShipping
                    },
                    dataType: "json",
                    showLoader: true
                }).done(function (response) {
                    if ($('#fedExAccountNumber_validate').length > 0) {
                        if (window.checkoutConfig.tigerteamE469373enabled) {
                            $("#fedExAccountNumber_validate").removeClass('error-icon');
                        }
                        $("#fedExAccountNumber_validate").empty();
                    }
                    if (typeof response !== 'undefined' && response.length < 1) {
                        errorContainer.removeClass('api-error-hide');

                        // Retire this logic when E-513778 toggle is removed
                        disclosureModel.isCampaingAdDisclosureToggleEnable
                            ? self.disableCreateQuoteBtn(true)
                            : createQuote.prop("disabled", true);

                        $('.loadersmall').hide();
                        return true;
                    } else if (!response.hasOwnProperty("errors") || response.hasOwnProperty("alerts")) {
                        errorContainer.addClass('api-error-hide');
                    }

                    if (!response.hasOwnProperty('errors')) {
                        if (!window.checkoutConfig.is_epro && !isRateApi) {
                            if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                                window.location.replace(orderConfirmationUrl);
                            }
                            response = response.rateQuote;
                        } else {
                            response = response.rate;
                        }
                        if (self.enableEarlyShippingAccountIncorporation()) {
                            if (applyRemove != 'remove') {
                                if (defaultShippingAccountNumber == '') {
                                    self.triggerShippingResults();
                                }
                                checkoutShippingMethod.show()
                            } else {
                                checkoutShippingMethod.hide();
                            }
                            if (self.isAutopopulate()) {
                                removeAccBtn.show();
                                if (isSdeStore || isSelfRegCustomer) {
                                    const shippingAccEditable = window.checkoutConfig.shipping_account_number_editable;
                                    $('.fedex_account_number-field').prop('disabled', true);
                                    if (shippingAccEditable === '1') {
                                        removeAccBtn.show();
                                    }
                                }
                            }
                        }
                    }
                    // Retire this logic when E-513778 toggle is removed    
                    disclosureModel.isCampaingAdDisclosureToggleEnable
                        ? self.disableCreateQuoteBtn(false)
                        : createQuote.prop("disabled", false);

                    let expressCheckout;
                    if (window.e383157Toggle) {
                        expressCheckout = fxoStorage.get('express-checkout');
                    } else {
                        expressCheckout = localStorage.getItem('express-checkout');
                    }
                    if (isFclCustomer && expressCheckout && $('button.create_quote_review_order').length > 0) {
                        $('button.create_quote_review_order').prop("disabled", false);
                    }
                    if (response.hasOwnProperty("errors")) {
                        if (isFclCustomer && expressCheckout && $('button.create_quote_review_order').length > 0) {
                            $('button.create_quote_review_order').prop("disabled", true);
                        }
                        if (isFclCustomer) {
                            if (window.e383157Toggle) {
                                fxoStorage.set('shipping_account_number', null);
                            } else {
                                localStorage.setItem('shipping_account_number', null);
                            }
                        }
                        if (self.enableEarlyShippingAccountIncorporation()) {
                            checkoutShippingMethod.hide();
                        }
                        if (applyRemove != 'remove') {
                            removeAccBtn.hide();
                        }

                        // Retire this logic when E-513778 toggle is removed
                        disclosureModel.isCampaingAdDisclosureToggleEnable
                            ? self.disableCreateQuoteBtn(true)
                            : createQuote.prop("disabled", true);

                        errorContainer.removeClass('api-error-hide');
                        let errorResponse = response.errors;
                        if (errorResponse.errors[0].code !== undefined && errorResponse.errors[0].code != null) {
                            shipAccountNumber = '';
                            if (window.e383157Toggle) {
                                fxoStorage.set("shipping_account_number", shipAccountNumber);
                            } else {
                                localStorage.setItem("shipping_account_number", shipAccountNumber);
                            }
                            if (errorResponse.errors[0].code == "INVALID_FEDEXACCOUNTNUMBER_IN_SHIPMENTDELIVERY" || errorResponse.errors[0].code == "SHIPMENTDELIVERY.FEDEXACCOUNTNUMBER.INVALID") {
                                $("#fedExAccountNumber_validate").html("The account number entered is invalid.");
                                $("#fedExAccountNumber").prop("disabled", false);
                                $("#addFedExAccountNumberButton").prop("disabled", false);
                                $("#addFedExAccountNumberButton").removeClass('disabled');
                            } else if (errorResponse.errors[0].code == "INTERNAL.SERVER.FAILURE") {
                                $('.message-container').text('Internal server failure. Please try again.');
                                errorContainer.removeClass('api-error-hide');
                            } else {
                                $('.message-container').text('System error. Please try again.');
                                errorContainer.removeClass('api-error-hide');
                            }
                        }
                        if (typeof response.errors.is_timeout != 'undefined' && response.errors.is_timeout != null) {
                            window.location.replace(orderConfirmationUrl);
                        }
                        return true;
                    }

                    const calculate = shippingService.calculateDollarAmount(response, isRateApi);

                    const stringToFloat = function (stringAmount) {
                        return parseFloat(stringAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                    };

                    const discountCalculate = function (discountValue) {
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
                    if (!window.checkoutConfig.is_epro && !isRateApi) {
                        response.rateDetails = response.rateQuoteDetails;
                    }
                    if (typeof (response) != "undefined" && typeof (response.rateDetails) != "undefined") {
                        response.rateDetails.forEach((rateDetail) => {
                            if (typeof rateDetail.deliveryLines != "undefined") {
                                rateDetail.deliveryLines.forEach((deliveryLine) => {
                                    if (typeof deliveryLine.deliveryLineDiscounts != "undefined") {
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
                                    promoDiscountAmount = discountResult['promoDiscountAmount'] - shippingDiscountAmount;
                                }
                            }
                            //@todo estimated total and estimated shipping total should be implemented based on response from rate api
                            //this should for all stores, if shipping account number is applied, we need to show the split total
                            if (typeof rateDetail.totalAmount != "undefined") {
                                netAmountResult = rateResponseHandler.getTotalNetAmount(rateDetail, totalNetAmount, estimatedShippingTotal, netAmountResult);
                                if (!self.enableEarlyShippingAccountIncorporation()) {
                                    totalNetAmount = netAmountResult['totalNetAmount'];
                                    estimatedShippingTotal = netAmountResult['estimatedShippingTotal'];
                                }
                            }
                            if (rateDetail.deliveriesTotalAmount) {
                                shippingAmount = rateDetail.deliveriesTotalAmount;
                            }
                        });
                    }
                    var NetTotalAmount = shippingFormAdditionalScript.priceFormatWithCurrency(calculate("netAmount"));
                    $(".grand.totals .amount .price").text(NetTotalAmount);

                    if (self.enableEarlyShippingAccountIncorporation()) {
                        let taxAmountEarly = parseFloat(calculate("taxAmount").replace('$', ''));
                        totalNetAmount = grossAmount + taxAmountEarly - totalDiscountAmount;
                        totalNetAmount = shippingFormAdditionalScript.priceFormatWithCurrency(totalNetAmount);
                        $(".grand.totals.incl .price").text(totalNetAmount);
                        $(".totals.shipping.excl .price").text('TBD');
                    } else {
                        totalNetAmount = shippingFormAdditionalScript.priceFormatWithCurrency(totalNetAmount);
                        if (shippingAmount) {
                            if (window.e383157Toggle) {
                                fxoStorage.set("marketplaceShippingPrice", shippingAmount);
                            } else {
                                localStorage.setItem('marketplaceShippingPrice', shippingAmount);
                            }
                            var formattedshippingAmount = priceUtils.formatPrice(shippingAmount, quote.getPriceFormat());
                            $(".totals.shipping.excl .price").text(formattedshippingAmount);
                            $(".grand.totals.excl .amount .price").text(formattedshippingAmount);
                        } else {
                            if (window.e383157Toggle) {
                                fxoStorage.delete('marketplaceShippingPrice');
                            } else {
                                localStorage.removeItem('marketplaceShippingPrice');
                            }
                        }
                        $(".grand.totals.incl .price").text(totalNetAmount);
                    }
                    var taxAmount = shippingFormAdditionalScript.priceFormatWithCurrency(calculate("taxAmount"));
                    grossAmount = shippingFormAdditionalScript.priceFormatWithCurrency(grossAmount);
                    $(".totals.sub .amount .price").text(grossAmount);
                    if (totalDiscountAmount) {
                        totalDiscountAmount = shippingFormAdditionalScript.priceFormatWithCurrency(totalDiscountAmount);
                        $(".totals.discount.excl .amount .price").text('-' + totalDiscountAmount);
                        $(".totals.fedexDiscount .amount .price").text('-' + totalDiscountAmount);
                    } else {
                        $(".totals.fedexDiscount .amount .price").text('-');
                        $(".totals.discount.excl .amount .price").text('-');
                    }

                    //handle estimated shipping total display
                    shippingFormAdditionalScript.handleEstimatedShippingTotal(estimatedShippingTotal);
                    if (!togglesAndSettings.isCustomerShippingAccount3PEnabled && (estimatedShippingTotal === "TBD" || estimatedShippingTotal === "$0.00") && marketplaceQuoteHelper.isMixedQuote()) {
                        $(".shipping-disclaimer-container").addClass('hide');
                    }

                    $(".totals-tax .price").text(taxAmount);
                    let accountDiscountHtml = '';

                    if (accountDiscountAmount || volumeDiscountAmount || bundleDiscountAmount || promoDiscountAmount || shippingDiscountAmount) {
                        $(".discount_breakdown tbody tr.discount").remove();
                    }
                    if (accountDiscountAmount == 0 && volumeDiscountAmount == 0 && bundleDiscountAmount == 0 && promoDiscountAmount == 0 && shippingDiscountAmount == 0) {
                        $('.toggle-discount th #discbreak').remove();
                    }
                    let discountAmounts = [{ "type": "promo_discount", "price": promoDiscountAmount, "label": "Promo Discount" }, { "type": "account_discount", "price": accountDiscountAmount, "label": "Account Discount" }, { "type": "volume_discount", "price": volumeDiscountAmount, "label": "Volume Discount" }, { "type": "shipping_discount", "price": shippingDiscountAmount, "label": "Shipping Discount" }];
                    if (togglesAndSettings.isToggleEnabled('tiger_e468338')) {
                        discountAmounts = [{
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
                        }];
                    }
                    let sortedAmounts = discountAmounts.sort((p1, p2) => (p1.price < p2.price) ? 1 : (p1.price > p2.price) ? -1 : 0);
                    sortedAmounts.forEach(function (amount, index) {
                        if (amount.price) {
                            accountDiscountHtml = '<tr class="' + amount.type + ' discount"><th class="mark" scope="row">' + amount.label + '</th><td class="amount"><span class="price">-' + shippingFormAdditionalScript.priceFormatWithCurrency(amount.price); + '</span></td></tr>';
                            $(".discount_breakdown tbody").append(accountDiscountHtml);
                            if ($('.toggle-discount th #discbreak').length == 0) {
                                $('.toggle-discount th').append('<span id="discbreak" tabindex="0" class="arrow down"></span>');
                            }
                        } else {
                            $(".discount_breakdown tbody tr." + amount.type).remove();
                        }
                    });
                    $(".opc-block-summary .table-totals").show();
                    $("#fedExAccountNumber_validate").empty();

                    if (applyRemove === 'apply') {
                        let lastfedex_account_number = fedExAccountNumber.slice(fedExAccountNumber.length - 4);
                        $("#account_number").html('*' + lastfedex_account_number);
                        $("#addFedExAccountNumberButton").prop("disabled", true);

                        //B-1517822 | Allow Shipping account number for SelfReg
                        if (isSdeStore === true || isSelfRegCustomer === true) {
                            $("#addFedExAccountNumberButton").addClass('disabled');
                        }
                        $("#fedExAccountNumber").prop("disabled", true);
                        $('.shipping_account_number').show();

                        if (isFclCustomer && $('#shipping-account-list').length > 0) {
                            if ($('.shipping-account-list .custom-option[data-value="manual"]').hasClass('selected')) {
                                $('.shipping_account_number').show();
                            } else {
                                if ($('.shipping_account_number').css("display") == 'none') {
                                    $(".opc-shipping-account-number .container").css('flex-wrap', 'wrap');
                                } else {
                                    $(".opc-shipping-account-number .container").css('flex-wrap', '');
                                    $('.fedex_account_number-box').hide();
                                }
                            }
                        }
                    } else {
                        $('.shipping_account_number').hide();
                        $("#account_number").empty();
                        $("#addFedExAccountNumberButton").prop("disabled", false);
                        let fedExAccountNumber = document.getElementById("fedExAccountNumber")
                        if (fedExAccountNumber) {
                            fedExAccountNumber.value = "";
                        }
                        $("#fedExAccountNumber").prop("disabled", false);

                        //B-1517822 | Allow Shipping account number for SelfReg
                        if (isSelfRegCustomer === true) {
                            $("#addFedExAccountNumberButton").removeClass('disabled');
                        }

                        if (isFclCustomer && $('#shipping-account-list').length > 0) {
                            $(".opc-shipping-account-number .container").css('flex-wrap', 'wrap');
                            $(".fedex_account_number-box").show();
                            fclShippingAccountList.applyShippingAccountNumber('manual');
                        }
                    }
                    let shipMethod = "";
                    if (quote.shippingMethod() != null) {
                        shipMethod = quote.shippingMethod()["method_code"];
                    }

                    shipAccountNumber = fedExAccountNumber;
                    var shipFormData = {
                        ship_method: shipMethod,
                        zipcode: postCode,
                        region_id: regionCode,
                        city: city,
                        street: street,
                        shipfedexAccountNumber: fedExAccountNumber,
                        is_residence_shipping: isResidenceShipping
                    };
                    if (window.e383157Toggle) {
                        fxoStorage.set('selectedShipFormData', shipFormData);
                    } else {
                        localStorage.setItem('selectedShipFormData', JSON.stringify(shipFormData));
                    }
                    //enable disable continue to payment button
                    shippingFormAdditionalScript.continueToPaymentButtonHandler();

                    if (shipAccountNumber) {
                        $(".free-shipping").text(shippingAmount).addClass("ground-shipping-with-account");
                    }
                    if ($("#fedExAccountNumber").prop("disabled") === false) {
                        $(".free-shipping").text("FREE").removeClass("ground-shipping-with-account");
                    }
                });
                return true;
            }
        },

        /**
         * get public Encryption Key
         */
        getEncryptedKey: function () {
            $.ajax({
                url: urlBuilder.build(
                    "delivery/index/encryptionkey"
                ),
                type: "GET",
                dataType: "json",
                showLoader: true,
                async: true,
                success: function (data) {
                    if (data.hasOwnProperty("errors") || data.hasOwnProperty("error")) {
                        $('.error-container').removeClass('api-error-hide');
                        return true;
                    }
                    if (window.e383157Toggle) {
                        fxoStorage.set('encryptedKey', data.encryption.key);
                    } else {
                        localStorage.setItem('encryptedKey', data.encryption.key);
                    }
                }
            }).done(function (response) {
                if (response.hasOwnProperty("errors") || response.hasOwnProperty("error")) {
                    $('.error-container').removeClass('api-error-hide');
                    return true;
                }
            });
        },


        /** B-878274 | show recommended production location **/

        setLocationData: function (locationId, locationName, locationFullAddress) {
            // Update hidden field and all UI elements
            $('input[name="location-id"]').val(locationId);

            $('#selectd-production-location-name span').html(locationName);
            $('#selectd-production-location-address span').html(locationFullAddress);
            $('#selectd-production-location-name-popup span').html(locationName);
            $('#selectd-production-location-address-popup span').html(locationFullAddress);

            // Update the observable to trigger Knockout UI refresh
            this.hasProductionLocationSelected(!!locationId);

            if (this.isSimplifiedProductionLocationOn() && locationId) {
                $('.prodloc-desc').hide();
                $('.prodloc-selected').show();
                $('.default-p-location').show();
            }
        },

        showRecommendedLocationForShipment: function () {
            var postalCode = $('input[name="popup-zipcode"]').val(); //$('input[name="postcode"]').val();
            if (postalCode && postalCode != undefined && postalCode != '') {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ 'address': postalCode }, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        lat = results[0].geometry.location.lat();
                        lng = results[0].geometry.location.lng();
                    } else {
                        console.log("Couldn't get your location");
                    }
                });
                expressCheckoutShiptoBuilder.hideReviewButtonForPickup();
                var linkUrl = urlBuilder.build("delivery/index/getpickup");
                var self = this;
                $.ajax({
                    url: linkUrl,
                    showLoader: true,
                    type: "POST",
                    data: {
                        zipcode: postalCode,
                        fromProductionLocation: true,
                    },
                    dataType: "json",
                    success: function (data) {
                        if (data.hasOwnProperty("errors") || data.length === 0) {
                            if (data &&
                                data.errors &&
                                data.errors.length > 0 &&
                                (data.errors[0] && data.errors[0].code === "HOLDUNTILDATE.EARLIER.THAN.ORDERREADYDATE" ||
                                    data.errors[0] && data.errors[0].code === "HOLD_UNTIL_DATE_EARLIER_THAN_ORDER_READY_DATE")) {
                                $('.error-container').removeClass('api-error-hide');
                                $('.message-container').text('System error, Please try again.');
                                return;
                            }
                            $('.message-container').text('System error, Please try again.');
                            $('.error-container').removeClass('api-error-hide');
                            /** D-78791  */
                            $('.delivery-locations-info .map-canvas').hide();
                            $('.delivery-locations-info .pickup-location-item-container').hide();
                            return;
                        }
                        /** D-78791  */
                        $('.error-container').addClass('api-error-hide');
                        $('.delivery-locations-info .map-canvas').show();
                        $('.delivery-locations-info .pickup-location-item-container').show();

                        var locationResponceData = data;
                        var locationResponceDataPaged = locationResponceData;
                        locationResponceDataPaged.forEach(function (element) {
                            element.date = element.estimatedDeliveryLocalTimeShow;
                            element.datehidden = element.estimatedDeliveryLocalTime;
                            if (window.e383157Toggle) {
                                fxoStorage.get("locationDateTime", element.estimatedDeliveryLocalTimeShow);
                            } else {
                                localStorage.setItem("locationDateTime", element.estimatedDeliveryLocalTimeShow);
                            }
                        });
                        locationResponceDataPaged.forEach(function (element) {
                            var distance = shippingService.distance(lat, lng,
                                element.location.geoCode.latitude,
                                element.location.geoCode.longitude, 'M').toFixed(2);
                            element.distance = lat ? distance.toString() + ' mi' : "";
                            if (explorersRestrictedAndRecommendedProduction) {
                                var locationName = element.location.name + ' (' + element.location.id + ')';
                                element.location.name = locationName;
                            }
                        });

                        self.recommendedLocationJson(locationResponceDataPaged);

                        /**
                         * Google maps Integration
                         */
                        let googleMapsLoad = true;
                        if (googleMapsLoad) {
                            var mapProp = {
                                center: { lat: parseFloat(self.recommendedLocationJson()[0].location.geoCode.latitude), lng: parseFloat(self.recommendedLocationJson()[0].location.geoCode.longitude) },
                                zoom: 12,
                                mapId: "DEMO_MAP_ID"
                            };

                            map = new google.maps.Map(document.getElementById("googleMap2"), mapProp);

                            var marker;
                            markers = [];
                            var infowindow = new google.maps.InfoWindow();
                            self.recommendedLocationJson().forEach(function (element, index) {

                                default_icon = new google.maps.marker.PinElement({
                                    glyph: `${index + 1}`,
                                    background: defaultMarkerColor,
                                    borderColor: defaultMarkerColor,
                                    glyphColor: markerGlyphColor,
                                    scale: markerScale
                                });

                                selected_icon = new google.maps.marker.PinElement({
                                    glyph: `${index + 1}`,
                                    background: selectedMarkerColor,
                                    borderColor: selectedMarkerColor,
                                    glyphColor: markerGlyphColor,
                                    scale: markerScale
                                });

                                marker = new google.maps.marker.AdvancedMarkerElement({
                                    map,
                                    position: { lat: parseFloat(element.location.geoCode.latitude), lng: parseFloat(element.location.geoCode.longitude) },
                                    content: index === 0 ? selected_icon.element : default_icon.element
                                });
                                markers.push(marker);
                                google.maps.event.addListener(marker, 'click', (function (marker, i) {
                                    return function () {
                                        infowindow.setContent(element.location.name);
                                        infowindow.open(map, marker);
                                    }
                                })(marker, index));
                            });

                            googleMapsLoad = false;
                        }

                        /**
                         * Show alternate pikcup person form if the user clicks on the checkbox
                         * for it.
                         */
                        let alternateToggle = true;
                        if (alternateToggle) {
                            $(".alternate-checkbox-container").on('change', function () {
                                $(".alternate-from-container").toggle();
                                /**
                                 * Show Proceed to payment button for
                                 * Guest(Non-Logged In) users
                                 */
                                if (!isLoggedIn) {
                                    $(".proceed-to-payment").show();
                                }
                                self.isAlternateContact(!self.isAlternateContact());
                                /**
                                 * Enable form validation for alternate pickup person form
                                 * if user opts for it.
                                 */
                                if (self.isAlternateContact()) {
                                    self.validateContactForm();
                                }
                            });
                            alternateToggle = false;
                        }

                        let recommendedLocationExecution = true;
                        if (recommendedLocationExecution) {
                            /**** atul start *********/
                            $(document).on('click', '.show-details-button-popup', function () {
                                var $this = $(this);
                                var locationId = $this
                                    .find(".pickup-location-id")
                                    .text();
                                $(".hide-details-button").hide();
                                $.ajax({
                                    url: urlBuilder.build(
                                        "delivery/index/centerDetails"
                                    ),
                                    type: "POST",
                                    data: { locationId: locationId },
                                    dataType: "json",
                                    showLoader: true,
                                    async: true,
                                    success: function (data) {
                                        if (data.hasOwnProperty("errors")) {
                                            $('.error-container').removeClass('api-error-hide');
                                            return true;
                                        }
                                        data.hoursOfOperation = shippingService.getHoursOfFirstWeek(data.hoursOfOperation);
                                        self.center(data);
                                        self.showCenter(true);
                                        $this.closest(".pickup-location-item-container").find(".center-details").show();
                                        $this.closest(".box-container").find(".hide-details-button").show();
                                        $(".show-details-button").show();
                                        $this.hide();
                                    }
                                }).done(function (response) {
                                    if (response.hasOwnProperty("errors")) {
                                        $('.error-container').removeClass('api-error-hide');
                                        return true;
                                    }
                                });
                            });

                            var hideCenterDetailsPopup = true;
                            if (hideCenterDetailsPopup) {
                                $(".delivery-locations-info .pickup-location-container .hide-details-button").on('click', function () {
                                    var $this = $(this);
                                    $this.closest(".pickup-location-item-container").find(".center-details").hide();
                                    $(".show-details-button-popup").show();
                                    self.showCenter(false);
                                    $this.hide();

                                });
                                hideCenterDetailsPopup = false;
                            }
                            $(document).on('click', '.delivery-locations-info .pickup-location-item-container input[type="radio"]', function () {
                                $('.pickup-location-item-container .pickup-location-container').removeClass('active-location');
                                $(this).parents('.pickup-location-container').addClass('active-location');
                            });
                            /***** atul end **********/
                            var saveChangesSelector = '.' + shippingLocationSaveButton + '';
                            $(document).on('click', saveChangesSelector, function () {
                                var $this = $('.delivery-locations-info input[type="radio"]:checked').parent('.recommended-location-button');
                                $this = $this.parent('.box-container');
                                var locationId = $this.find('.recommended-location-id').text();
                                var locationName = $this.find('.recommended-location-name').text();
                                var locationStreet = $this.find('.pickup-location-street:first').text();
                                var locationCity = $this.find('.pickup-location-city:first').text();
                                var locationState = $this.find('.pickup-location-state:first').text();
                                var locationZipcode = $this.find('.pickup-location-zipcode:first').text();
                                var locationFullAddress = locationStreet + ', ' + locationCity + ', ' +
                                    locationState + ', ' + locationZipcode;
                                // Save complete user selection data for exact restoration
                                var userSelectionData = {
                                    locationId: locationId,
                                    locationName: locationName,
                                    locationFullAddress: locationFullAddress
                                };
                                
                                if (window.e383157Toggle) {
                                    fxoStorage.set('selected_production_id', locationId);
                                    fxoStorage.set('selected_production_locationname', locationName);
                                    fxoStorage.set('selected_production_locationadress', locationFullAddress);
                                    fxoStorage.set('product_location_option', 'choose_self');
                                    // Save user's manual selection with full details to persist across page refreshes
                                    fxoStorage.set('user_selected_prod_location', JSON.stringify(userSelectionData));
                                } else {
                                    localStorage.setItem('selected_production_id', locationId);
                                    localStorage.setItem('selected_production_locationname', locationName);
                                    localStorage.setItem('selected_production_locationadress', locationFullAddress);
                                    localStorage.setItem('product_location_option', 'choose_self');
                                    // Save user's manual selection with full details to persist across page refreshes
                                    localStorage.setItem('user_selected_prod_location', JSON.stringify(userSelectionData));
                                }
                                self.setLocationData(locationId, locationName, locationFullAddress);
                                self.hasProductionLocationSelected(true);
                                $('.default-p-location').show();
                                isProductionLocationAutomaticallySelected = false;
                            });
                            recommendedLocationExecution = false;
                        }
                    }
                })
            }
        },

        /**
         * Check if signature message can be shown
         * Only show the message when store is SDE and shipping methods are available
         *
         * @returns bool
         */
        canShowSignatureMessage: function () {
            return (isSdeStore === true && this.rates().length) ? true : false;
        },

        firstPartyShippingMethodsVisibility: ko.observable(false),
        thirdPartyShippingMethodsVisibility: ko.observable(false),
        showFirstPartyAccordionButton: ko.observable(false),
        showThirdPartyAccordionButton: ko.observable(false),
        toggleFirstPartyAccordion: function () {
            var previousState = this.firstPartyShippingMethodsVisibility();
            this.firstPartyShippingMethodsVisibility(!previousState);
        },
        toggleThirdPartyAccordion: function () {
            var previousState = this.thirdPartyShippingMethodsVisibility();
            this.thirdPartyShippingMethodsVisibility(!previousState);
        },
        setAccordianBasedOnShipping: function () {
            if (maegeeks_pobox_validation) {
                let street1 = registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.0');
                let street2 = registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.1');
                let poboxpattern1 = /\bP(ost|ostal)?([ \.\-]*(O|0)(ffice)?)?([ \.\-]*(box|bx|bo|b))\b/i;
                let poboxpattern2 = /\bpostal[ \.]*office\b/i;
                if (poboxpattern1.test(street1.value()) || poboxpattern2.test(street1.value())) {
                    street1.error("PO boxes not allowed");
                }
                if (poboxpattern1.test(street2.value()) || poboxpattern2.test(street2.value())) {
                    street2.error("PO boxes not allowed");
                }
                if (street1.error() || street2.error()) {
                    return false;
                }
            }
            if (window.d196640_toggle) {
                window.callDeliveryOptions = true;
            }
            if (this.isFullMarketplaceQuote()) {
                this.thirdPartyShippingMethodsVisibility(true);
                this.enableEarlyShippingAccountIncorporation(togglesAndSettings.isCustomerShippingAccount3PEnabled);
            } else if (this.isFullFirstPartyQuote()) {
                if (!window.checkoutConfig.tiger_shipping_methods_display) {
                    this.firstPartyShippingMethodsVisibility(true);
                }
                this.enableEarlyShippingAccountIncorporation(true);
            } else if (this.isMixedQuote() && this.pickupShippingComboKey()) {
                this.firstPartyShippingMethodsVisibility(true);
                this.thirdPartyShippingMethodsVisibility(true);
                this.enableEarlyShippingAccountIncorporation(togglesAndSettings.isCustomerShippingAccount3PEnabled);
            } else {
                this.firstPartyShippingMethodsVisibility(false);
                this.thirdPartyShippingMethodsVisibility(false);
                this.enableEarlyShippingAccountIncorporation(true);
            }
        },
        toggleShippingAccordion: function (parentIndex) {
            const currentExpandedState = this.thirdPartyShippingMethods()[parentIndex].is_accordion_expanded();
            this.thirdPartyShippingMethods()[parentIndex].is_accordion_expanded(!currentExpandedState);
        },
        /**
         * Get direct signature message saved for sde in the configuration
         *
         * @returns string|null
         */
        getSdeDirectSignatureMessage: function () {
            let signatureMessage = null;
            if (typeof togglesAndSettings.sdeSignatureMessage != "undefined") {
                signatureMessage = togglesAndSettings.sdeSignatureMessage;
            }

            return signatureMessage;
        },

        /**
         * Open & close radius dropdown
         *
         * @returns void
         */
        openCloseRadiusDropdown: function (data, event) {
            let target = document.getElementsByClassName('pickup-search-selector')[0];
            pickupSearch.openCloseRadiusDropdown(target);
        },

        /**
         * Select radius option
         *
         * @returns void
         */
        selectRadiusOption: function (data, event) {
            pickupSearch.selectRadiusOption(event.target);
        },

        /**
         * Map marker icon
         *
         * @returns string
         */
        mapMarkerIcon: function () {
            return window.mediaJsImgpath + 'wysiwyg/images/location-icon.png';
        },

        /**
         * Check is Empty
         *
         * @returns bool
         */
        isEmpty: function (value) {
            return (value == null || value.length === 0);
        },

        shippingShippingExclShippingMessageHide: function () {
            $(".opc-block-summary .table-totals .totals.shipping.excl").hide();
            $(".estimated.totals.estimated-shipping-total").addClass("hide-estimated-shipping-total");
            $(".estimated-shipping-message").addClass("hide-estimated-shipping-total");
        },

        /**
         * Check if Shipping Account number is null
         *
         * @returns void
         */
        hideremoveShippingAccountNumberwhennull: function () {
            const shippingAccEditable = togglesAndSettings.isShippingAccEditable;
            const shippingAccInput = $(".early-shipping-account-number .fedex_account_number-field");
            const removeAccBtn = $('.shipping_account_number.child-box').find('.fedex_account_number_remove');

            if (isSdeStore && this.shippingAccountNumber() !== "") {
                this.checkCustomShippingEditable();

            } else if (this.shippingAccountNumber() !== "" && this.isAutopopulate()) {
                removeAccBtn.show();
                if (window.checkoutConfig?.tiger_team_B_2429967) {
                    if (!isSdeStore && window.checkoutConfig?.is_commercial) {
                        this.checkCustomShippingEditable();
                    }
                } else {
                    if (!isSdeStore && isSelfRegCustomer) {
                        this.checkCustomShippingEditable();
                    }
                }
            } else {
                if (window.checkoutConfig?.tiger_team_B_2429967) {
                    if (isSelfRegCustomer || window.checkoutConfig?.is_epro) {
                        shippingAccInput.prop('disabled', false);
                        shippingAccEditable === "0" ? shippingAccInput.addClass("ship-not-prepopulated") : null;
                    } else if (isSdeStore) {
                        shippingAccEditable === "0" ? shippingAccInput.prop('disabled', false) : null;
                    }
                } else {
                    if (isSelfRegCustomer) {
                        shippingAccInput.prop('disabled', false);
                        shippingAccEditable === "0" ? shippingAccInput.addClass("ship-not-prepopulated") : null;
                    } else if (isSdeStore) {
                        shippingAccEditable === "0" ? shippingAccInput.prop('disabled', false) : null;
                    }
                }
                $(".checkout-index-index .early-shipping-account-number .fedex_account_number-field").removeClass("ship_not_null");
                $(".checkout-index-index .early-shipping-account-number .container .shipping-account-list-container .shipping-account-list .custom-select").removeClass("ship_not_null");
            }
            if (!togglesAndSettings.tiger_e486666 && isFclCustomer && this.isgenerateShippingAccountExecuted() < 1) {
                fclShippingAccountList.generateShippingAccountListHtml();
                const classLength = document.getElementsByClassName('shipping-account-list-container').length;
                this.isgenerateShippingAccountExecuted(classLength);
            }
        },

        /**
         * Show/Hide Delivery toast message
         *
         * @returns void
         */
        showHideLocalDeliveryToastMessage: function () {
            let isLocalMethod;
            if (window.e383157Toggle) {
                isLocalMethod = fxoStorage.get('isLocalDeliveryMethod') !== undefined ? fxoStorage.get('isLocalDeliveryMethod') : false;
            } else {
                isLocalMethod = window.localStorage.isLocalDeliveryMethod !== undefined ? window.localStorage.isLocalDeliveryMethod : false;
            }
            this.isLocalDelivery(isLocalMethod);
            if (this.isLocalDelivery() == 'true' && this.shippingAccountNumber() != "") {
                $(".modal-container.local-delivery-message").show();
            } else {
                $('#closeLocalDeliveryMessage').trigger('click');
            }
        },

        /**
         * GeoCoder Find Address
         *
         * @param {string}
         * @param {string}
         * @param {string}
         *
         * @returns {object}
         */
        geoCoderFindAddress: async function (city, stateCode, pinCode) {
            let searchAddress = document.querySelector('.zipcode-container').getElementsByTagName('input')[0].value;
            let geocoder = new google.maps.Geocoder();
            await geocoder.geocode({ 'address': searchAddress }, function (results, status) {
                if (status != google.maps.GeocoderStatus.OK) {
                    city = null;
                    stateCode = null;
                    pinCode = null;
                }
                if (status == google.maps.GeocoderStatus.OK) {
                    let googleAddress = results[0].address_components;
                    city = null;
                    stateCode = null;
                    pinCode = null;
                    googleAddress.forEach(function (component) {
                        let types = component.types;
                        if (types.indexOf('locality') > -1) {
                            city = component.long_name;
                        }
                        if (types.indexOf('administrative_area_level_1') > -1) {
                            stateCode = component.short_name;
                        }
                        if (types.indexOf('postal_code') > -1) {
                            pinCode = component.long_name;
                        }
                    });
                }
            });

            return {
                city: city,
                stateCode: stateCode,
                pinCode: pinCode
            };
        },

        deliveryOptionsStorePickup: ko.observable(togglesAndSettings.pickShowVal),

        /**
         * Gets the pickup visibility status "this.deliveryOptionsStorePickup"
         * from the Delivery Methods section from a company within admin.
         *
         * Checks if the product is virtual,
         * and therefore able for pickup delivery.
         *
         * Sets the visibility of the ko.observable used for the
         * pickup "this.visible(true)" and shipping "this.showShippingContent(true)" section
         * using the Pickup and isVirtual status.
         *
         * @returns Bool
         */
        handleSDEstorePickupVisibility: function () {
            let flag = true, preferredDelivery = profileSessionBuilder.getPreferredDeliveryMethod();
            if (isOutSourced && preferredDelivery) {
                preferredDelivery.delivery_method = 'DELIVERY';
            }
            if (isSdeStore) {
                if (window.e383157Toggle) {
                    if (fxoStorage.get("pickupkey") === 'true') {
                        this.checkoutTitle($t('In-store pickup'));
                    }
                } else {
                    if (localStorage.getItem("pickupkey") === 'true') {
                        this.checkoutTitle($t('In-store pickup'));
                    }
                }

                if (!quote.isVirtual() && this.deliveryOptionsStorePickup()) {
                    if (window.location.hash != "#step_code" && window.location.hash != "#payment") {
                        this.visible(true);
                    }
                }

                let isShip;
                if (window.e383157Toggle) {
                    isShip = fxoStorage.get("shipkey") === 'true';
                } else {
                    isShip = localStorage.getItem("shipkey") === 'true';
                }
                if (!this.deliveryOptionsStorePickup() || isShip) {
                    this.showShippingContent(true);
                    this.onclickTriggerShipShow(true); // Shows Shipping Title and Breadcrumbs
                    this.checkoutTitle($t('Shipping Location'));
                    flag = false;
                }
            } else if (this.chosenDeliveryMethod()) {
                if (this.chosenDeliveryMethod() === 'shipping') {
                    this.showShippingContent(true);
                    this.onclickTriggerShipShow(true); // Shows Shipping Title and Breadcrumbs
                    this.checkoutTitle($t('Shipping'));
                    flag = false;
                }
                else if (this.chosenDeliveryMethod() === 'pick-up') {
                    this.checkoutTitle($t('In-store pickup'));
                    if (window.location.hash != "#step_code" && window.location.hash != "#payment") {
                        this.visible(true);
                    }
                }
            } else if (preferredDelivery) {
                if (preferredDelivery.delivery_method === 'DELIVERY') {
                    this.showShippingContent(true);
                    this.onclickTriggerShipShow(true); // Shows Shipping Title and Breadcrumbs
                    this.checkoutTitle($t('Shipping Location'));
                    flag = false;
                }
                else if (preferredDelivery.delivery_method === 'PICKUP') {
                    this.checkoutTitle($t('In-store pickup'));
                    if (window.location.hash != "#step_code" && window.location.hash != "#payment") {
                        this.visible(true);
                    }
                }
            }
            return flag; // Added in order to not affect the checkout behavior from other store views
        },

        /*
         * ###############################################################
         *                   Start | Marketplace Section
         * ###############################################################
         */

        /**
         * @params  String
         * @returns String
         */
        getShippingStepKoTemplate: function (filePath = '') {
            return 'Fedex_Delivery/checkout/shipping_step/' + filePath;
        },

        /**
         * Shows the checkout shipping step form,
         * as choosen by the user at the cart page,
         * which could be the shipping or pick-up form.
         *
         * @returns Void
         */
        showMarketplaceShippingContent: function () {
            if (isOutSourced || this.chosenDeliveryMethod() === 'shipping'
                || this.isFullMarketplaceQuote() || (window.checkoutConfig.is_pickup == "0" && window.checkoutConfig.is_delivery == "1")) {
                // Shows Shipping Form
                this.onclickTriggerPickup(null, null, true);
                return;
            }

            // Shows Pick-Up Form
            this.onclickTriggerShip(null, null, true);
        },

        goToShippingAfterPickup: function () {
            this.displayShippingAccountAcknowledgementError(false);
            this.onclickTriggerPickup(null, null, false, false);
            this.showPickupContent(false);
        },

        /**
         * @returns Bool
         */
        isMarketplaceSellerUsingShippingAccount: function () {
            return marketplaceQuoteHelper.checkIfSomeSellerIsUsingShippingAccount();
        },

        /**
         * @returns Bool
         */
        isFullMarketplaceQuote: function () {
            return marketplaceQuoteHelper.isFullMarketplaceQuote();
        },

        /**
         * @returns Bool
         */
        isFullFirstPartyQuote: function () {
            if (!this.isMixedQuote() && !this.isFullMarketplaceQuote()) {
                return true;
            }
        },

        /**
         * @returns Bool
         */
        isMixedQuote: function () {
            return marketplaceQuoteHelper.isMixedQuote();
        },

        /**
         * Return true if non pricable items are added in the cart
         *
         * @return Bool
         */
        isCheckoutQuotePriceDashable: function () {
            return isCheckoutQuotePriceDashable;
        },

        /**
         * Enable save button if value added in zipcode
         *
         * @return void
         */
        enableSaveLocationPickup: function () {
            let zipCodeValue = $("#zipcodeLocation").val();
            uploadToQuoteCheckout.enableSaveButtonByZipcode(zipCodeValue);
            uploadToQuoteCheckout.locationSearchAutocomplete();
            this.validateContactForm();
        },

        /**
         * Save location code
         *
         * @return void
         */
        onClickSaveLocationCode: function () {
            uploadToQuoteCheckout.saveLocationCode();
        },

        /**
         * Submit quote
         *
         * @return void
         */
        onClickSubmitQuote: function () {
            let self = this;
            let validateContactFormQuoteMsg = self.validateContactFirstName() || self.validateContactLastName() ||
                self.validateContactEmail() || self.validateContactNumber();
            let validateContactFormQuote = self.validateContactFirstName() && self.validateContactLastName() &&
                self.validateContactEmail() && self.validateContactNumber();
            if (validateContactFormQuoteMsg && validateContactFormQuote) {
                let locationCodeValidation;
                if (window.e383157Toggle) {
                    locationCodeValidation = fxoStorage.get("uploadtoquote_location_code_validation");
                    if (!locationCodeValidation) {
                        uploadToQuoteCheckout.saveLocationCode().then(function (res) {
                            if (res.uploadToQuoteLocationCodeValidation === true) {
                                uploadToQuoteCheckout.submitUploadToQuote();
                            }
                        });
                    } else {
                        uploadToQuoteCheckout.submitUploadToQuote();
                    }
                } else {
                    locationCodeValidation = localStorage.getItem("uploadtoquote_location_code_validation");
                    if (!locationCodeValidation) {
                        uploadToQuoteCheckout.saveLocationCode().then(function (res) {
                            if (res.uploadToQuoteLocationCodeValidation == "true") {
                                uploadToQuoteCheckout.submitUploadToQuote();
                            }
                        });
                    } else {
                        uploadToQuoteCheckout.submitUploadToQuote();
                    }
                }
            } else {
                self.validateContactForm();
            }
        },

        getDeliveryMethodsQty: function (type = 'all') {
            let mktMethodsCount = 0;
            let notMktMethodsCount = 0;
            let allMethodsCount = 0;
            let deliveryMethods = this.rates();
            if (deliveryMethods.length > 0) {
                deliveryMethods.forEach(function (method) {
                    if (method.marketplace) {
                        mktMethodsCount += 1;
                    }
                    else {
                        notMktMethodsCount += 1;
                    }
                    allMethodsCount += 1;
                });
            }
            switch (type) {
                case 'marketplace':
                    return mktMethodsCount;

                case 'not-marketplace':
                    return notMktMethodsCount;

                default:
                    return allMethodsCount;
            }
        },

        /**
         * @param (event, null)
         * @return Void
         */
        getDeliveryMethodToast: function (event = null) {
            let message = marketplaceDeliveryToast.getDeliveryMethodMessageObj();
            if (event != null) {
                for (let property in message) {
                    if (message[property] === null) {
                        return;
                    }
                }
                let formId = event.target.attributes.id.value;
                message.text = message[formId];
                marketplaceToastMessages.addMessage(JSON.stringify(message));
            }
        },

        /**
         * @param (Config, Event, Bool)
         * @return Void
         */
        inStorePickupPlusShippingCombo: function (config, event, change1PproductsToShipping = false) {
            if (change1PproductsToShipping) {
                if (window.e383157Toggle) {
                    fxoStorage.delete('pickupData');
                } else {
                    localStorage.removeItem('pickupData');
                }
                this.pickupShippingComboKey(false);
                if (window.e383157Toggle) {
                    fxoStorage.set('chosenDeliveryMethod', 'shipping');
                } else {
                    localStorage.setItem('chosenDeliveryMethod', 'shipping');
                }
                this.isPickupFormFilled(false);
                window.dispatchEvent(new Event('on_change_delivery_method'));
            }

        },

        /**
         * @param {void}
         * @return {String}
         */
        getDeliveryMethodsTooltip: function () {
            return typeof togglesAndSettings?.checkoutDeliveryMethodsTooltip === 'string'
                ? togglesAndSettings.checkoutDeliveryMethodsTooltip.toString()
                : '';
        },

        getSellerName: function () {
            const thirdPartyItem = _.find(quote.getItems(), function (item) {
                return item.mirakl_offer_id;
            })

            return thirdPartyItem.seller_name;
        },

        /**
         * @param {string}
         * @return {number}
         */
        getTotalCartItemsByParty: function (party) {
            const quoteItems = quote.getItems();
            let first_party_counter = 0;
            let third_party_counter = 0;
            quoteItems.forEach(function (item, index) {
                if (Boolean(item.isMarketplaceProduct)) {
                    third_party_counter++
                }
                else {
                    first_party_counter++
                }
            });
            let qtyItemsObj = {
                first_party: first_party_counter,
                third_party: third_party_counter
            }
            return '(' + qtyItemsObj[party] + (qtyItemsObj[party] > 1 ? ' items' : ' item') + ')';
        },

        shouldShowShippingMessage: function () {
            if (this.isPromiseTimePickupOptionsToggle()) {
                const quoteItems = quote.getItems();
                const has1PItems = quoteItems.some(item => !item.isMarketplaceProduct);
                const has3PItems = quoteItems.some(item => item.isMarketplaceProduct);

                if (!has1PItems && has3PItems) {
                    return false;
                }
                const chosenDeliveryMethod = this.chosenDeliveryMethod();
                if (has1PItems && has3PItems && chosenDeliveryMethod === 'pick-up') {
                    return false;
                }
                return true;
            }
            return false;
        },

        getTotalCartItemsBySeller: function (seller_name) {
            const sellerItems = quote.getItems().filter((item) => item.seller_name === seller_name);
            return '(' + sellerItems.length + (sellerItems.length > 1 ? ' items' : ' item') + ')';
        },

        hasPreferredPaymentOnExpressCheckout: function () {
            let profileSession = togglesAndSettings.profileSession;
            let isExpressCheckout;
            if (window.e383157Toggle) {
                isExpressCheckout = fxoStorage.get('express-checkout');
            } else {
                isExpressCheckout = localStorage.getItem('express-checkout');
            }
            if (isExpressCheckout && profileSession) {

                let preferredPaymentMethod = profileSessionBuilder.getPreferredPaymentMethod();
                let defaultCreditCard = null;
                let defaultFedexAccount = null;

                // Checks if there is a default Fedex Account set
                if (typeof (profileSession.output.profile.accounts) != 'undefined') {
                    let accountsList = profileSession.output.profile.accounts;
                    accountsList.forEach(function (element) {
                        if (element.accountValid && element.primary) {
                            defaultFedexAccount = true;
                        }
                    })
                }

                // Checks if there is a default credit card set
                if (typeof (profileSession.output.profile.creditCards) != 'undefined') {
                    let creditCards = profileSession.output.profile.creditCards;
                    creditCards.forEach(function (element) {
                        if (element.primary) {
                            defaultCreditCard = true;
                        }
                    })
                }
                if (preferredPaymentMethod) {
                    if (preferredPaymentMethod === 'CREDIT_CARD' && defaultCreditCard != null) {
                        return true;
                    }
                    if (preferredPaymentMethod === 'ACCOUNT' && defaultFedexAccount != null) {
                        return true;
                    }
                }
            }
            return false;
        },

        skipCheckoutStep: function (index, code, scrollToElementId, hash) {
            $do.get('.opc-progress-bar', function (elem) {
                const uiRegistry = require('uiRegistry');
                const progressBar = uiRegistry.get('index = progressBar');
                progressBar.navigateTo(code, scrollToElementId);
                progressBar.steps()[index].isVisible(true);
                window.location.hash = "#" + code;
            });
        },

        goToReviewStep: function () {
            let preferredPaymentMethod = profileSessionBuilder.getPreferredPaymentMethod();
            if ((this.pickupShippingComboKey() && this.isMixedQuote()) || (this.isFullFirstPartyQuote() && this.chosenDeliveryMethod() === 'pick-up')) {
                this.placePickupOrder(false, false, false);
            }
            if (this.chosenDeliveryMethod() === 'shipping' || this.isMixedQuote() || this.isFullMarketplaceQuote()) {
                this.setShippingInformation(false);
            }

            if (preferredPaymentMethod) {
                let creditCardReviewBtn = $('button.credit-card-review-button');
                let fedexAccountReviewBtn = $('button.fedex-account-number-review-button');
                let creditCardPaymentTypeElement = $('.select-credit-card');
                let fedexAccountPaymentTypeElement = $('.select-fedex-acc');

                let paymentTypeSelectedClass = 'selected-paymentype';

                if (preferredPaymentMethod === 'CREDIT_CARD') {
                    if (!creditCardPaymentTypeElement.hasClass(paymentTypeSelectedClass)) {
                        creditCardPaymentTypeElement.trigger('click');
                    }
                    creditCardReviewBtn.trigger('click');
                }
                if (preferredPaymentMethod === 'ACCOUNT') {
                    if (!fedexAccountPaymentTypeElement.hasClass(paymentTypeSelectedClass)) {
                        fedexAccountPaymentTypeElement.trigger('click');
                    }
                    fedexAccountReviewBtn.trigger('click');
                }
            }
            return false;
        },

        getShippingButtonId: function () {
            if (this.showShippingContent()) {
                return "checkout-ship-button";
            }
        },

        /*
         * ###############################################################
         *                   End | Marketplace Section
         * ###############################################################
         */

        goToPickupBreadcrumb: function () {
            stepNavigator.navigateTo('shipping', 'opc-shipping_method');

            this.isCheckShipping(false);
            this.onclickTriggerShipShow(false);

            this.onclickTriggerPickupShow(true);
            this.showPickupContent(true);

            let shippingComponent = registry.get('checkout.steps.shipping-step.shippingAddress');
            shippingComponent.checkoutTitle($t('In-store pickup'));

            $(".place-pickup-order").show();
        },

        goToShippingBreadcrumb: function () {
            stepNavigator.navigateTo('shipping', 'opc-shipping_method');

            this.isCheckShipping(true);
            this.onclickTriggerShipShow(true);

            this.onclickTriggerPickupShow(false);
            this.showPickupContent(false);

            $(".place-pickup-order").hide();
        },

        /**
         * Hide breadcrumb if Fuse Bidding Flow
         */
        isFuseBidding: function () {
            let is_bid;
            if (window.e383157Toggle) {
                is_bid = fxoStorage.get('qouteLocationDetails') ? JSON.parse(fxoStorage.get('qouteLocationDetails')) : null;
            } else {
                is_bid = localStorage.getItem('qouteLocationDetails') ? JSON.parse(localStorage.getItem('qouteLocationDetails')) : null;
            }
            return isFuseBidding && is_bid;
        },
        disableShippingResult: function () {
            if (maegeeks_pobox_validation) {
                let street1 = registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.0');
                let street2 = registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.1');
                if (street1.error() != '') {
                    street1.focused(true);
                    return 'disabled'
                }
                if (street2.error() != '') {
                    street2.focused(true);
                    return 'disabled'
                }
            }
            return false;
        },
        unsetDeliveryCall: function () {
            var unsetDeliveryOptionSession = urlBuilder.build('delivery/quote/unsetdeliveryoptionsession');
            $.ajax({
                type: "POST",
                url: unsetDeliveryOptionSession,
                data: [],
                cache: false,
                showLoader: true
            }).done(function (data) {
                if (data == true) {
                    $('.error-container').addClass('api-error-hide');
                } else {
                    return false;
                }
            });
        },

        hideShippingMethodsSection: function () {
            const shippingMethodSection = $('div.checkout-shipping-method');

            if (shippingMethodSection.css('display') === 'block') {
                shippingMethodSection.hide();
            }
        },

        triggerShippingResults: function () {
            if (fxoStorage.get('displayShippingAccountCheckbox')) return;
            $("#get-Shipping-result").trigger("click");
        },

        displayShippingAccountAcknowledgementError: function (show) {
            window.dispatchEvent(new CustomEvent('displayShippingAccountAcknowledgementError', { detail: show }));
        },

        resetShippingAccountAcknowledgementData: function () {
            window.dispatchEvent(new CustomEvent('resetShippingAccountAcknowledgementData'));
        },

        validateShippingAccountAcknowledgementCheckbox: function (reset = false) {
            const shippingAccountCheckboxValue = fxoStorage.get('shippingAccountCheckboxValue');
            if (shippingAccountCheckboxValue !== undefined && shippingAccountCheckboxValue === false) {
                this.displayShippingAccountAcknowledgementError(true);
                this.hideShippingMethodsSection();
                return false;
            }

            this.displayShippingAccountAcknowledgementError(false);

            if (reset) {
                this.resetShippingAccountAcknowledgementData();
            }

            return true;
        },
    });
});
