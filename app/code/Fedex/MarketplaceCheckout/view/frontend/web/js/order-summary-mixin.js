define([
    'jquery',
    'ko',
    'underscore',
    'fedex/storage'
], function ($, ko, _,fxoStorage) {
    'use strict';

    const FIRST_PARTY_CARRIER_CODE = 'fedexshipping';

    return function(orderSummary) {
        let pickupDateTime,orderTransactionData;
        if(window.e383157Toggle){
            pickupDateTime = fxoStorage.get("pickupDateTime");
            orderTransactionData = fxoStorage.get("orderTransactionData");
        }else{
            pickupDateTime = localStorage.getItem('pickupDateTime');
            orderTransactionData = JSON.parse(JSON.parse(localStorage.getItem('orderTransactionData')));
        }
        return orderSummary.extend({
            defaults: {
                template: 'Fedex_SubmitOrderSidebar/order-success',
                activeMethod: ''
            },

            orderTransaction: orderTransactionData,
            isMarketplaceFreightShippingEnabled: orderTransactionData?.output?.checkout?.marketPlace?.marketplace_freight_shipping_enabled,
            marketplaceFreightSurchargeText: orderTransactionData?.output?.checkout?.marketPlace?.marketplace_freight_surcharge_text,
            chosenDeliveryMethod: window.e383157Toggle ?
                (fxoStorage.get('chosenDeliveryMethod') || 'shipping') :
                (localStorage.getItem('chosenDeliveryMethod') || 'shipping'),
            groupedBySellers: ko.observableArray([]),
            thirdPartyProducts: ko.observableArray([]),
            thirdPartyProductsShippingMethod: ko.observable(''),
            firstPartyProductsShippingMethod: ko.observable(''),
            thirdPartyProductsShippingAmount: ko.observable(''),
            firstPartyProductsShippingAmount: ko.observable(''),

            shippingItemsCount: ko.observable(0),

            firstPartyProducts: ko.observable([]),

            pickupData: ko.observable(window.e383157Toggle ? fxoStorage.get("pickupData") : JSON.parse(localStorage.getItem('pickupData'))),
            isPickup: ko.observable(false),
            pickupAddressInformationLine1: ko.observable(''),
            pickupAddressInformationLine2: ko.observable(''),
            pickupDateTime: ko.observable(pickupDateTime),
            pickupContactInformationFullName: ko.observable(''),
            pickupContactInformationEmail: ko.observable(''),
            pickupContactInformationNumber: ko.observable(''),
            isPickupContactInformationAlternatePerson: ko.observable(false),
            pickupContactInformationAlternatePersonFullName: ko.observable(''),
            pickupContactInformationAlternatePersonEmail: ko.observable(''),
            pickupContactInformationAlternatePersonNumber: ko.observable(''),
            isExpectedDeliveryDateEnabled: ko.observable(window?.checkout?.is_expected_delivery_date_enabled),

            initialize: function() {
                this._super();

                this.setPreviewUrl();

                this.formatTelephone();

                this.viewDetailsText('Show details');

                this.setDeliveryMethods();

                const filterThirdPartyFromCartItems = this.cartItems().filter(product => product.type !== 'EXTERNAL_PRODUCT');
                this.cartItems(filterThirdPartyFromCartItems);

                this.isPickup(this.pickupData()?.addressInformation?.pickup || false);

                if(this.isPickup()) {
                    const {
                        pickup_location_city,
                        pickup_location_country,
                        pickup_location_street,
                        pickup_location_state,
                        pickup_location_zipcode,
                        pickup_location_date,
                    } = this.pickupData().addressInformation;

                    this.pickupAddressInformationLine1(
                        `${pickup_location_street}, ${pickup_location_city}, `
                    );

                    this.pickupAddressInformationLine2(
                        `${pickup_location_state} ${pickup_location_zipcode}`
                    );

                    const {
                        contact_fname,
                        contact_lname,
                        contact_email,
                        contact_number,
                    } = this.pickupData().contactInformation;

                    this.pickupContactInformationFullName(`${contact_fname} ${contact_lname}`);
                    this.pickupContactInformationEmail(`${contact_email}`);
                    this.pickupContactInformationNumber(`${contact_number}`);

                    this.isPickupContactInformationAlternatePerson(
                        this.pickupData().contactInformation.isAlternatePerson
                    );

                    if(this.isPickupContactInformationAlternatePerson()) {
                        const {
                            alternate_email,
                            alternate_fname,
                            alternate_lname,
                            alternate_number,
                        } = this.pickupData().contactInformation;
                        this.pickupContactInformationAlternatePersonFullName(`${alternate_fname} ${alternate_lname}`);
                        this.pickupContactInformationAlternatePersonEmail(`${alternate_email}`);
                        this.pickupContactInformationAlternatePersonNumber(`${alternate_number}`);
                    }
                }
            },

            formatShippingMethodName: function (shippingMethod) {
                    if (shippingMethod.includes("_AM")) {
                        shippingMethod = shippingMethod.replace("_AM", "");
                    } else if (shippingMethod.includes("_am")) {
                        shippingMethod = shippingMethod.replace("_am", "");
                    } else if (shippingMethod.includes("_PM")) {
                        shippingMethod = shippingMethod.replace("_PM", "");
                    } else if (shippingMethod.includes("_pm")) {
                        shippingMethod = shippingMethod.replace("_pm", "");
                    }

                    let methodName = {};
                    if (shippingMethod.toLowerCase().indexOf("fedex") === -1) {
                        shippingMethod = "FEDEX_"+shippingMethod;
                        methodName = shippingMethod.toLowerCase().split('_');
                        methodName = methodName.map(word => {
                            return word.charAt(0).toUpperCase() + word.slice(1);
                        })
                    } else {
                        methodName = shippingMethod.toLowerCase().split('_');
                        methodName = methodName.map(word => {
                            return word.charAt(0).toUpperCase() + word.slice(1);
                        })
                    }

                    return methodName.join(' ');
            },

            deliveryIconUrl: function () {
                return window.DeliveryIconUrl;
            },

            storeIconUrl: function () {
                return window.StoreIconUrl;
            },

            infoIconUrl: function () {
                return window.InfoIconUrl;
            },

            formatTelephone: function () {
                const format = (telephoneNumber) => {
                    let telephone = telephoneNumber.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);

                    telephone = `(${telephone[1]}) ${telephone[2]}-${telephone[3]}`;

                    return telephone;
                }

                if(this.shippingAddress()) {
                    let shippingAddressData = this.shippingAddress();

                    shippingAddressData.telephone = format(shippingAddressData.telephone);

                    this.shippingAddress(shippingAddressData);

                    if (shippingAddressData.altPhoneNumber) {
                        shippingAddressData.altPhoneNumber = format(shippingAddressData.altPhoneNumber);
                    }
                }

                if (this.pickupContactInformation()) {
                    let pickupContactInformationData = this.pickupContactInformation();

                    pickupContactInformationData.contact_number = format(pickupContactInformationData.contact_number);;

                    this.pickupContactInformation(pickupContactInformationData);
                }
            },

            getFirstPartyItemsCount: function () {
                const items = this.orderTransaction
                    ?.output?.checkout?.lineItems[0]
                    ?.retailPrintOrderDetails[0]
                    ?.productLines;

                const firstPartyItems = items.filter(products => products.type !== 'EXTERNAL_PRODUCT');

                if (Array.isArray(window.bundleItems) && window.bundleItems.length > 0 && this.isProductBundlesToggleEnabled()) {
                    let result = this.extractChildrenFromTransaction(firstPartyItems, window.bundleItems);
                    return result.instances.length + result.parents.length;
                }

                return firstPartyItems.length;
            },

            showShippingTotal: function () {
                let showShippingTotal = false;

                showShippingTotal = this.chosenDeliveryMethod === 'shipping' || this.thirdPartyProducts().length > 0 ? true : false;

                const shippingItems = this.orderTransaction
                    ?.output?.checkout?.lineItems[0]
                    ?.retailPrintOrderDetails[0]
                    ?.deliveryLines.find(deliveryLine => deliveryLine.deliveryLineType === 'SHIPPING')
                    ?.productAssociation || [];

                this.shippingItemsCount(shippingItems.length);

                return showShippingTotal;
            },

            groupBySellers(lineItems) {

                // Get third party sellers
                const thirdPartySellers = _.uniq(lineItems.map(item => {
                  const additional_data = JSON.parse(item.additional_data);
                  return additional_data.mirakl_shipping_data.seller_name;
                }));

                // Group line items by seller
                const groupedBySellers = thirdPartySellers.map(seller => {
                    const items = lineItems.filter(item => {
                        const additional_data = JSON.parse(item.additional_data);
                        return additional_data.mirakl_shipping_data.seller_name === seller;
                    });

                    const additionalData = JSON.parse(items[0].additional_data);

                    // Grab method_title from addional_data
                    const shippingMethodTitle = additionalData.mirakl_shipping_data.method_title;

                    const shippingPrice = additionalData.mirakl_shipping_data.price_incl_tax;

                    const deliveryDate = additionalData.mirakl_shipping_data.deliveryDate;

                    const surcharge = additionalData.mirakl_shipping_data.surcharge_amount;
                    const surchargeAmount = Number(surcharge);

                    let tooltip;

                    if(window.e383157Toggle){
                      tooltip = fxoStorage.get(`tooltip-${seller}`) || '';
                    }else{
                      tooltip = localStorage.getItem(`tooltip-${seller}`) || '';
                    }

                    return {
                        seller,
                        tooltip,
                        shippingMethodTitle,
                        shippingPrice,
                        deliveryDate,
                        items,
                        surchargeAmount
                    };
                });

                this.groupedBySellers(groupedBySellers);
            },

            updateThirdPartyLineItemPrices: function (lineItems) {
              // Update discount amount and subtotal for each third party line item
              // This is done by getting the price details from orderTransaction
              for(let i = 0; i < lineItems.length; i++) {
                const lineItem = lineItems[i];

                const productLine = this.orderTransaction?.output?.checkout?.lineItems[0]?.retailPrintOrderDetails[0]?.productLines
                    ?.find(productLine => productLine.instanceId === lineItem.quoteItemId)

                const productLineFormattedPrice = productLine?.productLineDetails[0]?.detailPrice
                    ? this.priceFormatWithCurrency(productLine.productLineDetails[0].detailPrice)
                    : 0;

                lineItem.discountAmount = productLine?.productLineDetails[0]?.detailDiscountPrice || 0;
                lineItem.subtotal = productLineFormattedPrice || lineItem.subtotal;
              }
            },

            setDeliveryMethods: function () {
                let firstPartyItems;
                if (Array.isArray(window.bundleItems) && window.bundleItems.length > 0 && this.isProductBundlesToggleEnabled()) {
                    const items = this.orderTransaction
                        ?.output?.checkout?.lineItems[0]
                        ?.retailPrintOrderDetails[0]
                        ?.productLines;

                    firstPartyItems = items.filter(products => products.type !== 'EXTERNAL_PRODUCT');
                } else {
                    firstPartyItems = this.cartItems();
                }

                const firstPartyProduct = _.filter(firstPartyItems, function (item) {
                    return item.vendorReference === undefined;
                })[0];
                const deliveryLines = this.orderTransaction
                    ?.output?.checkout?.lineItems[0]
                    ?.retailPrintOrderDetails[0]?.deliveryLines;

                if (typeof firstPartyProduct !== 'undefined') {
                    const firstPartyDeliveryLine = deliveryLines.find(deliveryLine => {
                        return !!deliveryLine.productAssociation
                            .find(deliveryLineProduct => deliveryLineProduct.productRef === firstPartyProduct.instanceId);
                    })

                    if (firstPartyDeliveryLine && firstPartyDeliveryLine.shipmentDetails) {
                        const firstPartyDeliveryLineName = firstPartyDeliveryLine.shipmentDetails.serviceType;
                        const firstPartyProductsShippingMethodPrice = firstPartyDeliveryLine.deliveryRetailPrice;

                        const firstPartyProductsFormattedShippingMethodName = this.formatShippingMethodName(firstPartyDeliveryLineName);

                        this.firstPartyProductsShippingMethod(firstPartyProductsFormattedShippingMethodName);
                        this.firstPartyProductsShippingAmount(firstPartyProductsShippingMethodPrice);
                    }
                }

                const thirdPartyLineItems = this.orderTransaction?.output?.checkout?.marketPlace?.lineItems || [];

                this.thirdPartyProducts(thirdPartyLineItems);

                // Get the discount amount and subtotal for each third party line item
                this.updateThirdPartyLineItemPrices(thirdPartyLineItems);
                // Group third party products by sellers
                this.groupBySellers(thirdPartyLineItems);

                const thirdPartyProductsShippingMethod = this.orderTransaction?.output?.checkout?.marketPlace?.shipping_method || '';
                const thirdPartyProductsShippingAmount = this.orderTransaction?.output?.checkout?.marketPlace?.shipping_price || '';

                this.thirdPartyProductsShippingMethod(thirdPartyProductsShippingMethod);
                this.thirdPartyProductsShippingAmount(thirdPartyProductsShippingAmount);
            },

            setPreviewUrl: function () {
                let cartItems = this.cartItems();

                cartItems = cartItems.map(item => {
                    if (typeof item.preview_url !== 'string') {
                        item.preview_url = '';
                    }

                    return item;
                })

                this.cartItems(cartItems);
            },

            // Helper to get product count based on context
            _getProductCount: function (marketplace) {
                if (marketplace) {
                    return this.thirdPartyProducts().length;
                }

                return this.cartItems().length;
            },

            pluralize: function (count, singular = 'item', plural = 'items') {
                return `${count} ${count === 1 ? singular : plural}`;
            },

            itemsCount: function (marketplace = false) {
                const count = this._getProductCount(marketplace);
                return this.pluralize(count);
            },

            /**
             * Get first party career title
             *
             * @return {boolean}
             */
            getFirstCarrierTitle: function () {
                let selectedShippingMethodsStorage;
                if(window.e383157Toggle){
                    selectedShippingMethodsStorage = fxoStorage.get('selectedShippingMethods');
                }else{
                    selectedShippingMethodsStorage = localStorage.getItem('selectedShippingMethods');
                }
                if (selectedShippingMethodsStorage) {
                    let selectedShippingMethods;
                    if(window.e383157Toggle){
                        selectedShippingMethods = fxoStorage.get('selectedShippingMethods');
                    }else{
                        selectedShippingMethods = JSON.parse(localStorage.getItem('selectedShippingMethods'));
                    }
                    let firstPartyTitle = false;

                    if(window.tiger_D_215205_shipping_method_and_expected_delivery_fix) {
                        const firstPartyShippingMethod = selectedShippingMethods.filter(item => item.carrier_code === FIRST_PARTY_CARRIER_CODE);
                        firstPartyTitle = firstPartyShippingMethod[0]?.carrier_title || false;
                    } else {
                        firstPartyTitle = typeof (selectedShippingMethods[0].carrier_title) !== "undefined" && selectedShippingMethods[0].carrier_title !== null ? selectedShippingMethods[0].carrier_title : false;
                    }

                    if (!firstPartyTitle) {
                        firstPartyTitle = this.carrierTitle();
                    }

                    return firstPartyTitle;
                }
            },

            /**
             * Get Expected Delivery Date
             *
             * @return {string}
             */
            getExpectedDeliveryDate: function () {
                let selectedShippingMethodsStorage;

                if (window.e383157Toggle) {
                    selectedShippingMethodsStorage = fxoStorage.get('selectedShippingMethods');
                } else {
                    selectedShippingMethodsStorage = localStorage.getItem('selectedShippingMethods');
                }

                if (selectedShippingMethodsStorage) {
                    let selectedShippingMethods;

                    if (window.e383157Toggle) {
                        selectedShippingMethods = fxoStorage.get('selectedShippingMethods');
                    } else {
                        selectedShippingMethods = JSON.parse(localStorage.getItem('selectedShippingMethods'));
                    }

                    let expectedDeliveryDate = false;

                    if(window.tiger_D_215205_shipping_method_and_expected_delivery_fix) {
                        const firstPartyShippingMethod = selectedShippingMethods.filter(item => item.carrier_code === FIRST_PARTY_CARRIER_CODE);
                        expectedDeliveryDate = firstPartyShippingMethod[0]?.method_title || false;
                    } else {
                        expectedDeliveryDate = typeof (selectedShippingMethods[0].method_title) !== "undefined" && selectedShippingMethods[0].method_title !== null ? selectedShippingMethods[0].method_title : false;
                    }

                    if (!expectedDeliveryDate) {
                        expectedDeliveryDate = this.expectedDeliveryDate();
                    }

                    return expectedDeliveryDate;
                }
            }
        })
    }
});
