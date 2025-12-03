define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/url',
    'mage/translate', // B-1194040 - $.mage.__ is not a function
    'Magento_Ui/js/modal/modal',
    'fedex/storage',
    'quote-login-modal'
], function ($, customerData, url, $t, modal,fxoStorage,quoteLoginModal) {
    'use strict';
    return function (Component) {
        return Component.extend({

            /**
             * @override
             * Show/Hide Max cart limit Alert pop up.
             */

            initialize: function () {
                var cartData = customerData.get('cart');
                var alertBoxContainer = $('.cart-alertbox');

                if (
                    (typeof(window.checkout.is_retail) != 'undefined' && window.checkout.is_retail)
                    && cartData().items != undefined
                    && window.checkout != undefined
                    && cartData().items.length >= cartData().cartThresholdLimit
                    && cartData().items.length < cartData().maxCartLimit) {

                    let msg = 'You currently have ' + cartData().cartThresholdLimit + '+ items in your cart. You may add up to ' + cartData().maxCartLimit + ' items to the cart per order.';
                    $('.notification-msg').text(msg);

                    this.showAlert(alertBoxContainer);
                }

                if (
                    (typeof(window.checkout.is_retail) != 'undefined' && window.checkout.is_retail)
                    && cartData().items != undefined
                    && window.checkout != undefined
                    && cartData().maxCartLimit > 0
                    && cartData().items.length >= cartData().maxCartLimit) {

                    let msg = 'You may add up to ' + cartData().maxCartLimit + ' items to the cart per order.';

                    $('.notification-msg').text(msg);

                    $('div#pod-upload-warning .note-action').removeAttr("onclick");

                    $('.file-upload-container > button, div#pod-upload-warning .note-action').on('click', function (e) {
                        e.preventDefault();
                        window.location.href = checkout.shoppingCartUrl;
                    });

                    alertBoxContainer.show();
                    this.closeAlert(alertBoxContainer);

                    $('.max-cart-limit-redirect').on('click', function (e) {
                        e.preventDefault();
                        window.location.href = checkout.shoppingCartUrl;
                    });
                }

                if (
                    ((typeof(window.checkout.is_sde_store) != 'undefined' && window.checkout.is_sde_store) ||
                    (typeof(window.checkout.is_retail) != 'undefined' && window.checkout.is_retail))
                    && cartData().items != undefined
                    && window.checkout != undefined
                    && cartData().maxCartLimit > 0
                    && cartData().items.length >= cartData().maxCartLimit) {
                    $('input#search').on("keypress", function () {
                        $('#fedex-cart-modal-box').show();
                        $('#fedex-cart-modal-box').modal('openModal');
                        $(this).val('');
                        return false;
                    }).on('click', function () {
                        $('#fedex-cart-modal-box').show();
                        $('#fedex-cart-modal-box').modal('openModal');
                    });

                    //On click below selectors popup would be popup after reachout the cart line items limit
                    $('.max-cart-limit, .category-item, .item.Catalog, .action.tocart').each(function () {
                        $(this).on('click', function (e) {
                            e.preventDefault();
                            $('.loading-mask').hide();
                            $('#fedex-cart-modal-box').show();
                            $('#fedex-cart-modal-box').modal('openModal');
                        });
                    });

                    $('.max-cart-limit').each(function() {
                            $(this).removeAttr("onclick");
                     });
                }

                return this._super();
            },
            showAlert: function (alertBoxContainer) {
                var pathname = window.location.pathname;
                this.closeAlert(alertBoxContainer);
                if (pathname.match('/checkout/cart/') || !pathname.match(/checkout/g)) {
                    alertBoxContainer.show();
                }
            },
            closeAlert: function (alertBoxContainer) {
                $('.close-icon').on('click', function () {
                    alertBoxContainer.hide();
                });
            },
            getCartParam: function (name) {

                let self = this;
                // Fcl modal redirected on checkout start
                var isSdeStore = this.isSdeStore();
                    var options = {
                        modalClass: 'checkout-login-poup-model',
                        buttons: [],
                        closed: function () {
                            if(window.e383157Toggle){
                                fxoStorage.set('fclPopupDisabled',true);
                            }else{
                                localStorage.setItem('fclPopupDisabled',true);
                            }
                            var productInstance;
                            var isOutSourced = false;
                            if (customerData.get('cart')) {
                                let cartData = customerData.get('cart');
                                if (cartData().items.length !== 0) {
                                    productInstance = cartData().items.find((item) => {
                                        if ((typeof (item.externalProductInstance) !== 'undefined' && item.externalProductInstance !== null && item.externalProductInstance !== "")) {
                                            if (typeof item.externalProductInstance !== 'object' && typeof (JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced']) !== "undefined"
                                                && (JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'] != null)) {
                                                isOutSourced = JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'];
                                            } else if (typeof (item.externalProductInstance['isOutSourced']) !== "undefined"
                                                && (item.externalProductInstance)['isOutSourced'] != null) {
                                                isOutSourced = item.externalProductInstance['isOutSourced'];
                                            }
                                            return isOutSourced;
                                        }
                                    });
                                }
                            }
                            var dsiabledCheckoutModal;
                            if(window.e383157Toggle){
                                dsiabledCheckoutModal = fxoStorage.get('disabled-checkout-modal-popup') ? fxoStorage.get('disabled-checkout-modal-popup') : false;
                            }else{
                                dsiabledCheckoutModal = localStorage.getItem('disabled-checkout-modal-popup') ? localStorage.getItem('disabled-checkout-modal-popup') : false;
                            }
                            // B-1173348 - Fix JS Errors - Cannot read properties of undefined (reading 'is_out_sourced_toggle')
                            if (window.checkout != undefined && isOutSourced && (isLoginRegisterPopupDisabled == 'true' || window.checkout.is_fcl_customer)) {
                                $('.anchor-ship').trigger("click");
                            } else if (dsiabledCheckoutModal) {
                                $('.anchor-pickup').trigger("click");
                            } else {
                                if(window.e383157Toggle){
                                    fxoStorage.set('autopopup', 'true');
                                    fxoStorage.set('pickupkey', 'true');
                                    fxoStorage.set('shipkey', 'false');
                                }else{
                                    localStorage.setItem('autopopup', 'true');
                                    localStorage.setItem('pickupkey', 'true');
                                    localStorage.setItem('shipkey', 'false');
                                }
                                window.location.href = url.build('checkout');
                            }
                            if(window.e383157Toggle){
                                fxoStorage.delete('disabled-checkout-modal-popup');
                            }else{
                                localStorage.removeItem('disabled-checkout-modal-popup');
                            }
                        }
                    };
                // Fcl modal redirected on checkout end

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

                /*
                 * Unset flag of express checkout
                 */
                $('.checkout-mini.non-express-checkout-cart').on('click', function (e) {
                    e.preventDefault();
                    if(window.e383157Toggle){
                        fxoStorage.delete('express-checkout');
                    }else{
                        localStorage.removeItem('express-checkout');
                    }
                });

                $('#cart-to-checkout, #top-cart-btn-checkout').on('click', function (event) {
                    event.preventDefault();
                    var productInstance = null;
                    var isOutSourced = false;
                    if (customerData.get('cart')) {
                        let cartData = customerData.get('cart');
                        if (cartData().items.length !== 0) {
                            productInstance = cartData().items.find((item) => {
                                if ((typeof (item.externalProductInstance) !== 'undefined' && item.externalProductInstance !== null && item.externalProductInstance !== "")) {
                                    if (typeof item.externalProductInstance !== 'object' && typeof (JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced']) !== "undefined"
                                        && (JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'] != null)) {
                                        isOutSourced = JSON.parse(item.externalProductInstance)['fxoProductInstance']['productConfig']['product']['isOutSourced'];
                                    } else if (typeof (item.externalProductInstance['isOutSourced']) !== "undefined"
                                        && (item.externalProductInstance)['isOutSourced'] != null) {
                                        isOutSourced = item.externalProductInstance['isOutSourced'];
                                    }
                                    return isOutSourced;
                                }
                            });
                        }
                    }
                    let isLoginRegisterPopupDisabled;
                    if(window.e383157Toggle){
                        isLoginRegisterPopupDisabled = typeof(fxoStorage.get('fclPopupDisabled')) != 'undefined' && fxoStorage.get('fclPopupDisabled') != null ? fxoStorage.get('fclPopupDisabled'): 'false';
                    }else{
                        isLoginRegisterPopupDisabled = typeof(localStorage.getItem('fclPopupDisabled')) != 'undefined' && localStorage.getItem('fclPopupDisabled') != null ? localStorage.getItem('fclPopupDisabled'): 'false';
                    }
                    var isCommercialLogin = typeof(window.checkout.is_commercial) !== 'undefined' && window.checkout.is_commercial != null ? window.checkout.is_commercial : false;

                    if (!isCommercialLogin) {

                        // B-1173348 - Fix JS Errors - Cannot read properties of undefined (reading 'is_out_sourced_toggle')
                        if (window.checkout != undefined && isOutSourced && (isLoginRegisterPopupDisabled == 'true' || window.checkout.is_fcl_customer)) {
                            if(window.e383157Toggle){
                                fxoStorage.set('pickupkey', 'false');
                                fxoStorage.set('shipkey', 'true');
                            }else{
                                localStorage.setItem('pickupkey', 'false');
                                localStorage.setItem('shipkey', 'true');
                            }
                            $('.anchor-ship').trigger("click");
                        } else {
                            if (isLoginRegisterPopupDisabled == 'true' || window.checkout.is_fcl_customer) {
                                if(window.e383157Toggle){
                                    fxoStorage.set('autopopup', 'true');
                                    fxoStorage.set('pickupkey', 'true');
                                    fxoStorage.set('shipkey', 'false');
                                }else{
                                    localStorage.setItem('autopopup', 'true');
                                    localStorage.setItem('pickupkey', 'true');
                                    localStorage.setItem('shipkey', 'false');
                                }
                                window.location.href = url.build('checkout');
                            } else if (isLoginRegisterPopupDisabled !== 'true') {

                                if(self.isCheckoutQuotePriceDashable && self.isCheckoutQuotePriceDashable()){
                                    quoteLoginModal();
                                    return false;
                                }
                                $('#fcl-checkout-login-popup').modal(options).modal('openModal');
                                $('.checkout-login-poup-model').attr('aria-label','Login');
                                return false;
                            }
                        }
                    } else if (isSdeStore) {
                        $('.anchor-ship').trigger("click");
                    }  else {
                        /* D-84173  ePro- pop up modal is showing up even if only shipping/pickup is selected from admin */
                        var pickupenabled,deliveryenabled;
                        if(window.e383157Toggle){
                            pickupenabled = fxoStorage.get('pickup_enabled');
                            deliveryenabled = fxoStorage.get('delivery_enabled');
                        }else{
                            pickupenabled = localStorage.getItem("pickup_enabled");
                            deliveryenabled = localStorage.getItem("delivery_enabled");
                        }

                        if (deliveryenabled == 1 && pickupenabled == 0){
                            $('.anchor-ship').trigger("click");
                        } else if (deliveryenabled == 0 && pickupenabled == 1) {
                            $('.anchor-pickup').trigger("click");
                        } else {
                            let chosenDeliveryMethod;
                            if(window.e383157Toggle){
                                chosenDeliveryMethod = fxoStorage.get('chosenDeliveryMethod');
                            }else{
                                chosenDeliveryMethod = localStorage.getItem('chosenDeliveryMethod');
                            }
                            if (chosenDeliveryMethod === 'shipping') {
                                $('.anchor-ship').trigger("click");
                            }
                            if (chosenDeliveryMethod === 'pick-up') {
                                $('.anchor-pickup').trigger("click");
                            }
                        }
                    }
                });

                $('[trigger-data="checkout"]').on('click', function (){
                    if(window.e383157Toggle){
                        fxoStorage.set('checkoutredirection', true);
                    }else{
                        localStorage.setItem('checkoutredirection', true);
                    }
                });

                return this._super(name);
            },

            /**
             * Check the store is sde or not
             *
             * @returns bool
             */
             isSdeStore: function () {
                if ((typeof window.checkout !== 'undefined' && Boolean(window.checkout.is_sde_store) === true) ||
                    (typeof window.checkoutConfig !== 'undefined' && Boolean(window.checkoutConfig.is_sde_store) === true)) {
                        return true;
                }

                return false;
            }
        });
    }
});
