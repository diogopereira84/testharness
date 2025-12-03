/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/totals',
    'uiComponent',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Customer/js/customer-data',
    'fedex/storage',
    'Fedex_Delivery/js/model/toggles-and-settings'
], function ($, ko, totals, Component, stepNavigator, quote, priceUtils, customerData, fxoStorage, togglesAndSettings) {
    'use strict';

    const LOCAL_DELIVERY_IDENTIFIER = 'LOCAL_DELIVERY';
    var useQty = window.checkoutConfig.useQty;
    const isTigerE486666Enabled = togglesAndSettings.tiger_e486666;

    //Fix to prevent the pre-selection and wrong shipping price
    if(window.e383157Toggle){
        fxoStorage.delete('selectedShippingMethods');
        fxoStorage.delete('selectedRadioShipping');
        fxoStorage.delete('marketplaceShippingPrice');
    }else{
        localStorage.removeItem('selectedShippingMethods');
        localStorage.removeItem('selectedRadioShipping');
        localStorage.removeItem('marketplaceShippingPrice');
    }
    return Component.extend({
        defaults: {
            template: 'Fedex_MarketplaceCheckout/checkout/summary/cart-items',
            imports: {
                chosenDeliveryMethod: 'checkout.steps.shipping-step.shippingAddress:checkoutTitle',
                shippingAccountNumber: 'checkout.steps.shipping-step.shippingAddress:shippingAccountNumber'
            }
        },
        chosenDeliveryMethodStorage: localStorage.getItem("chosenDeliveryMethod"),
        totals: totals.totals(),
        items: ko.observable([]),
        selectedShippingMethods: window.e383157Toggle ? (fxoStorage.get('selectedShippingMethods') ?? []) :
            (localStorage.getItem('selectedShippingMethods')
                ? JSON.parse(localStorage.getItem('selectedShippingMethods'))
                : [])
        ,
        maxCartItemsToDisplay: window.checkoutConfig.maxCartItemsToDisplay,
        cartUrl: window.checkoutConfig.cartUrl,
        sellersInCart: ko.observableArray([]),
        FEDEX_SELLER_NAME: 'FedEx Office',
        shippingAccountDisclaimerEnable: window.checkoutConfig.tiger_B2027702_vendor_shipping_account_enable,
        shouldDisplayShippingAccountDisclaimerFor3P: ko.observable(false),

        /**
         * @deprecated Please use observable property (this.items())
         */
        getItems: totals.getItems(),

        /**
         * Returns cart items qty
         *
         * @returns {Number}
         */
        getItemsQty: function () {
            return parseFloat(this.totals['items_qty']);
        },

        /**
         * Returns count of cart line items
         *
         * @returns {Number}
         */
        getCartLineItemsCount: function () {
            return parseInt(totals.getItems()().length, 10);
        },

        /**
         * Returns shopping cart items summary (includes config settings)
         *
         * @returns {Number}
         */
        getCartSummaryItemsCount: function () {
            return useQty ? this.getItemsQty() : this.getCartLineItemsCount();
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            // Set initial items to observable field
            this.setItems(totals.getItems()());
            // Subscribe for items data changes and refresh items in view
            totals.getItems().subscribe(function (items) {
                this.setItems(items);
            }.bind(this));

            const cartData = customerData.get('cart');

            cartData.subscribe(function (cartData) {
                this.setItems(totals.getItems()());
            }, this);

            this.vendorShippingAccountDisclaimer = window.checkoutConfig.tiger_B2027702_vendor_shipping_account_disclaimer;
            this.isExpectedDeliveryDateEnabled = window?.checkoutConfig?.isExpectedDeliveryDateEnabled;
            this.isMarketplaceFreightShippingEnabled = window?.checkoutConfig?.marketplace_freight_shipping_enabled;
            this.marketplaceFreightSurchargeText = window?.checkoutConfig?.marketplace_freight_surcharge_text;
            const self = this;

            window.addEventListener('on_change_delivery_method', function () {
                let chosenDeliveryMethod;
                if(window.e383157Toggle){
                    chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                }else{
                    chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                }
                self.chosenDeliveryMethodStorage = chosenDeliveryMethod;

                self.setItems(totals.getItems()());
            });

            window.addEventListener('shipping_method', () => {
                let selectedShippingMethods;
                if(window.e383157Toggle){
                    selectedShippingMethods = fxoStorage.get('selectedShippingMethods');
                }else{
                    selectedShippingMethods = JSON.parse(localStorage.getItem('selectedShippingMethods'));
                }
                if (selectedShippingMethods && selectedShippingMethods.length > 0) {
                    self.selectedShippingMethods = selectedShippingMethods;
                    self.setItems(totals.getItems()());
                }
            })

            window.addEventListener('displayShippingAccountDisclaimerFor3P', (event) => {
                self.shouldDisplayShippingAccountDisclaimerFor3P(event.detail);
            });

            this.hasLocalDeliveryMethod = ko.computed(() => {
                if(quote.shippingMethod()) {
                   return quote.shippingMethod().method_code.includes(LOCAL_DELIVERY_IDENTIFIER);
                }
                return false;
            });
        },

        /**
         * Set items to observable field
         *
         * @param {Object} items
         */
        setItems: function (items) {
            const self = this;
            let itemsToBePrintend = [];
            const cartDataItems = customerData.get('cart')().items;

            if (items && items.length > 0) {
                items = items.slice(parseInt(-this.maxCartItemsToDisplay, 10));
                const quoteItems = quote.getItems();
                items.forEach(function (item, index) {
                    let quoteItem = quoteItems.find((itemInQuote) => itemInQuote.item_id === item.item_id);

                    item = self.setMarketplaceAttributes(item, quoteItem);
                });
                this.sellersInCart().forEach(function (sellerName) {
                    let itemData = {
                        name: sellerName,
                        shipping_method: '',
                        qtyPerSeller: 0,
                        products: [],
                        shipping: '',
                        shippingPrice: 'TBD',
                        expectedDelivery: '',
                        defaultExpectedDelivery: 'TBD',
                        seller_ship_account_enabled: false,
                        surcharge: ''
                    };
                    items.forEach(function (item, index) {
                        if (item.marketplace_seller == sellerName) // To be replaced with the condition to check if the item seller is the same
                        {
                            itemData.products.push(item);
                            itemData.qtyPerSeller += 1;
                            itemData.shipping_method = item.shipping_method;
                            itemData.expectedDelivery = item.expectedDeliveryDate;
                            if (item.shipping) {
                                itemData.shipping = item.shipping + '®';
                            } else {
                                itemData.shipping = item.shipping;
                            }
                            itemData.shippingPrice = item.shippingPrice;
                            itemData.expectedDelivery = item.expectedDelivery;
                            itemData.defaultExpectedDelivery = item.defaultExpectedDelivery;

                            // Check if quoteItemData has seller_ship_account_enabled
                            let quoteItemData = window.checkoutConfig.quoteItemData.find(
                                (quoteItem) => quoteItem.item_id === item.item_id
                            );
                            if (quoteItemData && quoteItemData.seller_ship_account_enabled) {
                                itemData.seller_ship_account_enabled = true;
                            }

                            let cartItem = cartDataItems ? cartDataItems.find((cartItem) => cartItem.item_id === item.item_id) : "";

                            if (item.shipping && cartItem && cartItem.surcharge !== "") {
                                itemData.surcharge = cartItem.surcharge;
                            }
                        }
                    });
                    itemsToBePrintend.push(itemData);
                });
            }
            itemsToBePrintend = this.sortCartItemsBySeller(itemsToBePrintend);
            this.items(itemsToBePrintend);
        },

        /**
         * Set 3P item attributes
         *
         * @param {Object} item
         * @param {Object} additionalData
         * @param {Object} quoteItem
         *
         * @returns {Object} item
         */
        setMarketplaceAttributes: function (item, quoteItem) {
            let itemAttributes = item;
            itemAttributes.additional_data = quoteItem.additional_data;
            itemAttributes.expectedDeliveryDate = 'TBD';
            if (quoteItem.mirakl_offer_id) {
                if (typeof item.additional_data === 'string') {
                    item.additional_data = JSON.parse(item.additional_data);
                }
                const {
                    marketplace_name,
                    quantity,
                    unit_price,
                    total,
                    image,
                    isMarketplaceProduct
                } = item.additional_data;

                itemAttributes.name = marketplace_name;
                itemAttributes.qty = quantity;
                itemAttributes.price = unit_price;
                itemAttributes.row_total = total;
                itemAttributes.row_total_incl_tax = total;
                itemAttributes.seller_image = image;
                itemAttributes.is_marketplace_product = isMarketplaceProduct;
                itemAttributes.marketplace_seller = quoteItem.seller_name;
                itemAttributes.shipping_method = 'shipping';

                if(this.selectedShippingMethods.length > 0) {
                    const selectedShippingMethod = this.selectedShippingMethods
                        .find((shippingMethod) => shippingMethod.title === quoteItem.mirakl_shop_name);

                    if (selectedShippingMethod) {
                        itemAttributes.shipping = selectedShippingMethod.method_title;
                        itemAttributes.expectedDeliveryDate = selectedShippingMethod.deliveryDateText;
                        const zeroCostText = this.enableShippingFreeText() ? 'FREE' : 'TBD';
                        const shippingPrice = selectedShippingMethod.base_amount || zeroCostText;
                        if (shippingPrice === zeroCostText) {
                            itemAttributes.shippingPrice = shippingPrice;
                        } else {
                            itemAttributes.shippingPrice = this.formatPrice(shippingPrice);
                        }

                        const expectedDelivery =  selectedShippingMethod.deliveryDateText || selectedShippingMethod.method_title || '';

                        if (expectedDelivery) {
                            itemAttributes.expectedDelivery = expectedDelivery;
                            itemAttributes.defaultExpectedDelivery = '';
                        } else {
                            itemAttributes.defaultExpectedDelivery = 'TBD';
                        }
                    }
                }
                if (!this.sellersInCart().includes(itemAttributes.marketplace_seller)) {
                    this.sellersInCart.push(itemAttributes.marketplace_seller);
                }
            }
            else {
                itemAttributes.marketplace_seller = this.FEDEX_SELLER_NAME;
                itemAttributes.shipping_method = this.chosenDeliveryMethodStorage ? this.chosenDeliveryMethodStorage.toLowerCase() : false;

                if(this.selectedShippingMethods.length > 0) {
                    const selectedShippingMethod = this.selectedShippingMethods
                        .find((shippingMethod) => shippingMethod.carrier_code === 'fedexshipping');

                    if (selectedShippingMethod) {
                        itemAttributes.shipping = selectedShippingMethod.carrier_title;
                        itemAttributes.expectedDeliveryDate = selectedShippingMethod.method_title;
                        const shippingPrice = selectedShippingMethod.base_amount || selectedShippingMethod.deliveryRetailPrice || 'TBD';
                        if (shippingPrice === 'TBD') {
                            itemAttributes.shippingPrice = shippingPrice;
                        } else {
                            itemAttributes.shippingPrice = this.formatPrice(shippingPrice);
                        }

                        const expectedDelivery = selectedShippingMethod.method_title || '';

                        if (expectedDelivery) {
                            itemAttributes.expectedDelivery = expectedDelivery;
                            itemAttributes.defaultExpectedDelivery = '';
                        } else {
                            itemAttributes.defaultExpectedDelivery = 'TBD';
                        }
                    }
                }
            }

            if (!this.sellersInCart().includes(itemAttributes.marketplace_seller)) {
                this.sellersInCart.push(itemAttributes.marketplace_seller);
            }

            if (!itemAttributes.shipping) {
                itemAttributes.shipping = '';
            }

            if (!itemAttributes.shippingPrice) {
                itemAttributes.shippingPrice = 'TBD';
            }

            if (!itemAttributes.expectedDelivery) {
                itemAttributes.expectedDelivery = '';
                itemAttributes.defaultExpectedDelivery = 'TBD';
            }

            return itemAttributes;
        },

        /**
         * Returns an array of items where the 'FedEx Office' seller will be the first item
         *
         * @param {Array} cartItems
         *
         * @returns {Array}
         */
        sortCartItemsBySeller: function(cartItems) {
            return cartItems.sort((prevSeller, nextSeller) => prevSeller.name === this.FEDEX_SELLER_NAME ? -1 : 1);
        },

        formatPrice: function(value) {
            return priceUtils.formatPrice(value, quote.getPriceFormat());
        },

        /**
         * Returns bool indicating if the cart has items with specific delivery method
         *
         * @param {String} deliveryMethod
         *
         * @returns {Bool}
         */
        hasSpecificDeliveryMethodProducts: function(deliveryMethod) {
            let hasTheDeliveryMethod = false;

            const sellersWithDeliveryMethod = this.items().filter(seller => {
                return seller.shipping_method === deliveryMethod;
            });

            if(sellersWithDeliveryMethod.length > 0) {
                hasTheDeliveryMethod = true;
            }

            return hasTheDeliveryMethod;
        },

        /**
         * Get quote price dashable
         *
         * @return {string}
         */
        isQuotePriceDashable: function () {
            return typeof(window.checkoutConfig.is_quote_price_is_dashable) != 'undefined' && typeof(window.checkoutConfig.is_quote_price_is_dashable) != null ? window.checkoutConfig.is_quote_price_is_dashable : false;
        },

        /**
         * Is Essendant toggle enabled
         *
         * @return {string}
         */
        isEssendantEnabled: function () {
            return !!window.checkoutConfig?.tiger_enable_essendant;
        },

        /**
         * Is CBB toggle enabled
         *
         * @return {string}
         */
        isCBBEnabled: function () {
            return !!window.checkoutConfig?.tiger_enable_cbb;
        },

        /**
         * Is free text enabled
         *
         * @return {string}
         */
        enableShippingFreeText: function () {
            return this.isEssendantEnabled() || this.isCBBEnabled();
        },

        /**
         * Get shipping cost class
         * @param shippingPrice
         * @returns {string}
         */
        shippingCostClass: function (shippingPrice) {
            return shippingPrice === 'FREE' ? 'fedex-bold weight-700 cl-green' : 'price weight-300 fs-0 fs-16 fedex-light';
        },

        /**
         * Returns bool value for items block state (expanded or not)
         *
         * @returns {*|Boolean}
         */
        isItemsBlockExpanded: function () {
            return quote.isVirtual() || stepNavigator.isProcessed('shipping');
        },

        hasFedexShippingAccountSet: function () {
            if(window.checkoutConfig.tiger_team_D_216029) {
                if(typeof this.shippingAccountNumber === 'undefined' || this.shippingAccountNumber.length === 0) {
                    return false;
                }

                let maskedAccountNumber = Number(String(this.shippingAccountNumber).slice(-4));
                $(".masked-shipping-account-number").text(maskedAccountNumber);
                return true;
            }

            let fedexAccountNumber = $(".fedex_account_number-field").val();

            if (typeof fedexAccountNumber !== 'undefined' && fedexAccountNumber.length > 0)
            {
                let shippingAccountNumber = isTigerE486666Enabled ? fedexAccountNumber
                    : window.e383157Toggle ? fxoStorage.get("shipping_account_number")
                    : localStorage.getItem("shipping_account_number");
                let maskedAccountNumber = Number(String(shippingAccountNumber).slice(-4));

                $(".masked-shipping-account-number").text(maskedAccountNumber);

                return true;
            };

            return false;
        },
        isLocalDeliveryShippingAccount: function () {
            if(window.checkoutConfig.tiger_team_D_216029) {
                return !this.hasLocalDeliveryMethod() && this.hasFedexShippingAccountSet();
            }

            let isLocalMethod = false;
            if(window.e383157Toggle) {
                isLocalMethod = fxoStorage.get('isLocalDeliveryMethod') === true;
            } else {
                isLocalMethod = localStorage.getItem('isLocalDeliveryMethod') === 'true';
            }
            return !isLocalMethod && this.hasFedexShippingAccountSet();
        },
    });
});
