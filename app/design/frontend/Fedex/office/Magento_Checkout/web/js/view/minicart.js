/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery',
    'ko',
    'underscore',
    'mage/url',
    'fedex/storage',
    'Fedex_Delivery/js/model/toggles-and-settings',
    'sidebar',
    'mage/translate',
    'mage/dropdown'
], function (Component, customerData, $, ko, _, url, fxoStorage, togglesAndSettings) {
    'use strict';

    var sidebarInitialized = false,
        addToCartCalls = 0,
        miniCart;

    miniCart = $('[data-block=\'minicart\']');

    /**
     * @return {Boolean}
     */
    function initSidebar() {
        if (miniCart.data('mageSidebar')) {
            miniCart.sidebar('update');
        }

        if (!$('[data-role=product-item]').length) {
            return false;
        }
        miniCart.trigger('contentUpdated');

        if (sidebarInitialized) {
            return false;
        }
        sidebarInitialized = true;
        miniCart.sidebar({
            'targetElement': 'div.block.block-minicart',
            'url': {
                'checkout': window.checkout.checkoutUrl,
                'update': window.checkout.is_cbb_toggle_enable ?
                    url.build('checkout/cart/updateItemQty') :
                    window.checkout.updateItemQtyUrl,
                'remove': window.checkout.removeItemUrl,
                'loginUrl': window.checkout.customerLoginUrl,
                'isRedirectRequired': window.checkout.isRedirectRequired
            },
            'button': {
                'checkout': '#top-cart-btn-checkout',
                'remove': '.mini-cart a.action.delete',
                'close': '#btn-minicart-close'
            },
            'showcart': {
                'parent': 'span.counter',
                'qty': 'span.counter-number',
                'label': 'span.counter-label'
            },
            'minicart': {
                'list': '#mini-cart',
                'content': '#minicart-content-wrapper',
                'qty': 'div.items-total',
                'subtotal': 'div.subtotal span.price',
                'maxItemsVisible': window.checkout.minicartMaxItemsVisible
            },
            'item': {
                'qty': ':input.cart-item-qty',
                'button': ':button.update-cart-item'
            },
            'confirmMessage': $.mage.__('Are you sure you would like to remove this item from the shopping cart?')
        });
    }

    miniCart.on('dropdowndialogopen', function () {
        initSidebar();
    });

    $('a.action.showcart').on('click', function() {
        $(".minicart-items .product-item .product-item-details .product.actions").addClass("custom-minicar-edit-delete");

        if ($(".action.nav-toggle").hasClass("active")) {
            if ($(window).width() < 768) {
                $(".action.nav-toggle").trigger('click');
            }
        }

        let isQuotePriceable = true;
        let cartData = customerData.get('cart')();
        for (let item of cartData.items) {
            if (item.isItemPriceable === false) {
                isQuotePriceable = false;
                break;
            }
        }
        if (!isQuotePriceable) {
            $(".amount.price-container").html('<span class="price-wrapper"><span class="price">$--.--</span></span>');
        }
    });

    return Component.extend({
        shoppingCartUrl: window.checkout.shoppingCartUrl,
        maxItemsToDisplay: window.checkout.maxItemsToDisplay,
        cart: {},

        // jscs:disable requireCamelCaseOrUpperCaseIdentifiers
        /**
         * @override
         */
        initialize: function () {
            var self = this,
                cartData = customerData.get('cart');

            this.update(cartData());
            cartData.subscribe(function (updatedCart) {
                addToCartCalls--;
                this.isLoading(addToCartCalls > 0);
                sidebarInitialized = false;
                this.update(updatedCart);
                initSidebar();
            }, this);
            $('[data-block="minicart"]').on('contentLoading', function () {
                addToCartCalls++;
                self.isLoading(true);
            });

            if (
                cartData().website_id !== window.checkout.websiteId && cartData().website_id !== undefined ||
                cartData().storeId !== window.checkout.storeId && cartData().storeId !== undefined
            ) {
                customerData.reload(['cart'], false);
            }

            var options2 = {
                type: 'popup',
                modalClass: 'pickup-shipup-model',
                buttons: [{
                    text: $.mage.__('Continue'),
                    class: 'mymodal1',
                    click: function () {
                        this.closeModal();
                    }
                }]
            };
            $('#pickup-shipup-popup-modal').modal(options2);
            $('.pickup-shipup-model').attr('aria-label','Pickup/Delivery');

            if (location.href.indexOf("/cart") > -1) {
                var carturl = true;
                $('.btn-popup-login').on('click', function () {
                    if(window.e383157Toggle){
                        fxoStorage.set('clickonfcl', 1);
                    }else{
                        localStorage.setItem('clickonfcl', 1);
                    }
                });
                $('.btn-popup-create-user').on('click', function () {
                    if(window.e383157Toggle){
                        fxoStorage.set('clickonfcl', 1);
                    }else{
                        localStorage.setItem('clickonfcl', 1);
                    }
                });
            } else {
                var carturl = false;
                $('.btn-popup-login').on('click', function () {
                    if(window.e383157Toggle){
                        fxoStorage.set('clickonfcl', 0);
                    }else{
                        localStorage.setItem('clickonfcl', 0);
                    }
                });
                $('.btn-popup-create-user').on('click', function () {
                    if(window.e383157Toggle){
                        fxoStorage.set('clickonfcl', 0);
                    }else{
                        localStorage.setItem('clickonfcl', 0);
                    }
                });
            }
            var item_count = this.getCartParamUnsanitizedHtml("summary_count");
            var fclaction;
            if(window.e383157Toggle){
                fclaction = fxoStorage.get("clickonfcl");
            }else{
                fclaction = localStorage.getItem("clickonfcl");
            }
            if (document.cookie.match(/^(.*;)?\s*ship-popup\s*=\s*[^;]+(.*)?$/)) {
                var ship_popup = 1;
            } else {
                var ship_popup = 0;
            }
            return this._super();
        },
        //jscs:enable requireCamelCaseOrUpperCaseIdentifiers

        isLoading: ko.observable(false),
        initSidebar: initSidebar,
        fcltoggle: function () {
            return 1;
        },
        fclguest: function () {
            return localStorage.getItem("fcl-guest");
        },
        isCustomerLogged: function () {
            let isFclCustomer = typeof(window.checkout.is_fcl_customer) != 'undefined' && typeof(window.checkout.is_fcl_customer) != null ? window.checkout.is_fcl_customer : false;
            return isFclCustomer;
        },
        getCustomerName: function () {
            var customer = customerData.get('customer');
            if((customer().fullname) && (customer().firstname.length))
            {
                return true;
            }
            return false;
        },

        /**
         * Close mini shopping cart.
         */
        closeMinicart: function () {
            $('[data-block="minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog('close');
        },

        /**
         * @return {Boolean}
         */
        closeSidebar: function () {
            var minicart = $('[data-block="minicart"]');

            minicart.on('click', '[data-action="close"]', function (event) {
                event.stopPropagation();
                minicart.find('[data-role="dropdownDialog"]').dropdownDialog('close');
            });

            return true;
        },

        /**
         * @param {String} productType
         * @return {*|String}
         */
        getItemRenderer: function (productType) {
            return this.itemRenderer[productType] || 'defaultRenderer';
        },

        /**
         * Update mini shopping cart content.
         *
         * @param {Object} updatedCart
         * @returns void
         */
        update: function (updatedCart) {
            _.each(updatedCart, function (value, key) {
                if (!this.cart.hasOwnProperty(key)) {
                    this.cart[key] = ko.observable();
                }
                this.cart[key](value);
            }, this);
        },

        /**
         * Get cart param by name.
         *
         * @param {String} name
         * @return {*}
         */
        getCartParamUnsanitizedHtml: function (name) {
            if (!_.isUndefined(name)) {
                if (!this.cart.hasOwnProperty(name)) {
                    this.cart[name] = ko.observable();
                }
            }

            return this.cart[name]();
        },

        /**
         * @deprecated please use getCartParamUnsanitizedHtml.
         * @param {String} name
         * @returns {*}
         */
        getCartParam: function (name) {
            return this.getCartParamUnsanitizedHtml(name);
        },

        /**
         * Returns array of cart items, limited by 'maxItemsToDisplay' setting
         * @return []
         */
        getCartItems: function () {
            var items = this.getCartParamUnsanitizedHtml('items') || [];
            items = items.slice(parseInt(-this.maxItemsToDisplay, 10));
            return items;
        },

        /**
         * Returns count of cart line items
         * @return {Number}
         */
        getCartLineItemsCount: function () {
            var items = this.getCartParamUnsanitizedHtml('items') || [];

            return parseInt(items.length, 10);
        },

        /**
         * Get Discount Breakdown Class
         * @return {String}
         */
        getDiscountBreakdownClass: function () {
            return 'block-content discount_breakdown_mincart';
        },

        /**
         *  Return true if non pricable products are added in the cart
         *
         * @return {Boolean}
         */
        isCheckoutQuotePriceDashable: function () {
            let isCheckoutQuotePriceDashable = false;
            let cartData = customerData.get('cart')();
            for (let item of cartData.items) {
                if (item.isItemPriceable === false) {
                    isCheckoutQuotePriceDashable = true;
                    break;
                }
            }

            return isCheckoutQuotePriceDashable;
        },
        isProductUnavailable:function(){
            let isProductUnavailable = false;
            let cartData = customerData.get('cart')();
            if(cartData.isE441563ToggleEnabled!==undefined && cartData.isE441563ToggleEnabled==="1"
                && cartData.checkCartHaveUnavailbleProduct!==undefined &&
                cartData.checkCartHaveUnavailbleProduct==true){
                isProductUnavailable = true;
            }
            return isProductUnavailable;
        },

        isLegacyProduct: function () {
            let isLegacyProduct = false;
            let cartData = customerData.get('cart')();
            if (cartData.checkLegacyDocApiOnCartToggle === true && cartData.legacyDocumentStatus) {
                    for (let itemId in cartData.legacyDocumentStatus) {
                        if (cartData.legacyDocumentStatus[itemId] === true) {
                            isLegacyProduct = true;
                            break;
                        }
                    }
            }
            return isLegacyProduct;
        },

        isBundleFullySetup: function () {
            if (!togglesAndSettings.isToggleEnabled('tiger_e468338')) {
                return true;
            }
            const cartData = customerData.get('cart')();
            const bundleItems = cartData.items ? cartData.items.filter(
                item => item.product_type === 'bundle'
            ) : [];
            if (bundleItems.length === 0) {
                return true;
            }
            return bundleItems.every(item => item.isBundleProductSetupCompleted);
        }

    });
});
