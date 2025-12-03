define([
    'jquery',
    'ko',
    'Fedex_Cart/js/product-engine-check-product-availability',
    'Magento_Customer/js/customer-data',
    'Fedex_Delivery/js/model/toggles-and-settings'
], function ($, ko, peProductAvailability, customerData, togglesAndSettings) {
    'use strict';
    return function (minicart) {
        return minicart.extend({
            FEDEX_SELLER_NAME: 'FedEx Office',

            hasDisabledProducts: ko.observable(false),

            disabledProductsId: ko.observable([]),

            isFclCustomer: ko.observable(
                typeof(window.checkout.is_fcl_customer) != 'undefined' && typeof(window.checkout.is_fcl_customer) != null
                    ? window.checkout.is_fcl_customer
                    : false
            ),

            cartItems: ko.observable([]),

            initialize: function() {
                this._super();
                const self = this,
                cartData = customerData.get('cart');

                this.cartItems(this.getCartItems());
                cartData.subscribe(function () {
                    this.cartItems(this.getCartItems());
                }, this);
                this.disabledProductsId.subscribe(function (newValue) {
                    self.cartItems(self.getCartItems());
                });

                this.hasDisabledProducts(false);

                window.addEventListener('check_disabled_products', function () {
                    const disabledProducts = window.checkout.disabledProducts || [];

                    if (disabledProducts.length > 0) {
                        self.hasDisabledProducts(true);
                        self.disabledProductsId(disabledProducts);
                    } else {
                        self.hasDisabledProducts(false);
                        self.disabledProductsId([]);
                    }
                })
            },

            getCartItems: function () {
                let items = this.getCartParamUnsanitizedHtml('items') || [];
                items = items.slice(parseInt(-this.maxItemsToDisplay, 10));
                items = this.getMarketplaceAttributes(items);
                items = this.setDisabledPropertyForItems(items);
                if(window.tiger_E_478196_dye_sub_pod_2_updates){
                    items = this.setIsDyeSubProductPropertyForItems(items);
                }

                $('.minicart-wrapper').addClass('mkt-minicart');

                let sellers = this.getAllSellers(items);
                sellers = this.filterDuplicateSellers(sellers);

                let organizedItems = this.organizeProductsBySeller(sellers, items);

                organizedItems = this.sortSellers(organizedItems);

                return organizedItems;
            },

            /**
             * Sets the correct values that will be used in the template.
             * @returns Array
             */
            getMarketplaceAttributes: function(items) {
                return items.map(item => {
                    if (item.seller_item_alt_name === null) {
                        item.seller_item_alt_name = this.FEDEX_SELLER_NAME;
                    }

                    item.marketplace_customizable_product = true;
                    if (item.additional_data !== null) {
                        if(typeof item.additional_data === 'string') {
                            item.additional_data = JSON.parse(item.additional_data);
                        }

                        // To facilitate access inside the template.
                        item.mirakl_offer_id = item.additional_data.offer_id;
                        item.product_image.src = item.additional_data.image;
                        item.qty = item.additional_data.quantity;
                        item.cart_quantity_tooltip = item.additional_data.cart_quantity_tooltip;

                        if(item.marketplace_product_subtotal) {
                            item.subtotal = item.marketplace_product_subtotal;
                        }

                        item.product_name = item.additional_data.marketplace_name ? item.additional_data.marketplace_name : item.additional_data.navitor_name;
                        if(item.additional_data.hasOwnProperty('punchout_enabled')) {
                            item.marketplace_customizable_product = item.additional_data.punchout_enabled;
                        }
                        if (!item.marketplace_customizable_product) {
                            item.isSiItemNonEditable = true;
                        }
                    } else {
                        item.is_third_party_product = false;
                    }

                    return item;
                });
            },

            /**
             * @returns Array
             */
            getAllSellers: function(items) {
                return items.map(item => item.seller_item_alt_name);
            },

            /**
             * Checks if seller already exists inside the array.
             * @returns Array
             */
            filterDuplicateSellers: function(sellers) {
                return sellers.filter((seller, index, array) => (
                    index === array.findIndex(item => item === seller)
                ));
            },

            /**
             * Places products in the same object as their seller.
             * @returns Object
             */
            organizeProductsBySeller: function(sellers, items) {
                return sellers.map(seller => {
                    const sellerProducts = items.filter(item => item.seller_item_alt_name === seller);

                    const productsQuantity = sellerProducts.length || 0;

                    const quantityContent = `${seller} (${productsQuantity} item${productsQuantity > 1 ? 's' : ''})`;

                    return { name: seller, products: sellerProducts, quantityContent };
                });
            },

            /**
             * Makes that FedEx Seller be the first seller in the array.
             * @returns Array
             */
            sortSellers: function(sellers) {
                return sellers.sort((a, b) => {
                    if (a.name == this.FEDEX_SELLER_NAME) return -1;
                    return 1;
                });
            },

            checkProductsAvailability: function () {
                const self = this;
                const cartItems = this.getCartItems()

                let disabledProducts = [];

                cartItems.forEach((seller, sellerIndex) => {
                    seller.products.forEach((product, productIndex) => {
                        if (product.seller_item_alt_name !== 'FedEx Office') {
                            return;
                        }

                        const externalProductInstance = JSON.stringify(product.externalProductInstance);

                        let presetId;
                        if( togglesAndSettings.dontUseECMA11_tiger_D144262 ) {
                            presetId = (
                                product &&
                                product.externalProductInstance &&
                                product.externalProductInstance.productConfig &&
                                product.externalProductInstance.productConfig.productPresetId
                            ) || '';
                        }
                        else {
                            /* Uses ECMAScript 11 Optional Chaining "?.""
                             * it was wrapped in a toggle due to errors being triggered in old browsers
                             * without support to ECMAScript 11 (2020) - For more details check: Defect | D-144262
                            **/
                            presetId = product.externalProductInstance.productConfig?.productPresetId || '';
                        }

                        const instanceId = product.id;

                        peProductAvailability.isProductAvailableRequest(externalProductInstance, presetId)
                            .then(
                                (onResolve) => {},
                                (onReject) => {
                                    if ( !disabledProducts.includes(instanceId) ) {
                                        disabledProducts.push(instanceId);
                                    }
                                }
                            )
                            .finally(() => {
                                if (typeof window.checkout !== 'undefined') {
                                    window.checkout.disabledProducts = disabledProducts;
                                }

                                if (typeof window.checkoutConfig !== 'undefined') {
                                    window.checkoutConfig.disabledProducts = disabledProducts;
                                };

                                if (
                                    cartItems.length === (sellerIndex + 1)              // Check if it is the last product from the last seller in the array...
                                    && seller.products.length === (productIndex + 1)    // ...to avoid unnecessary dispatches
                                ) {
                                    window.dispatchEvent(new Event('check_disabled_products'));
                                }
                            });
                    });
                })
            },

            /**
             * @param {Array} items Array of products
             * @returns {Array} Array of products
             */
            setDisabledPropertyForItems: function (items) {
                const self = this;

                return items.map(item => {
                    if ( self.disabledProductsId().includes(item.id) ) {
                        item.disabled = true;
                    } else {
                        item.disabled = false;
                    }

                    return item;
                });
            },
            setIsDyeSubProductPropertyForItems: function (items) {
                return items.map(item => {
                    item.isDyeSubExpired = item.isDyeSubExpired === true || item.isDyeSubExpired === 'true';
                    return item;
                });
            },
        });
    }
});
