/**
* Copyright © Fedex, Inc. All rights reserved.
* See COPYING.txt for license details.
*/

define([
    'jquery',
    'uiComponent',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'fedex/storage'
], function ($, Component, url, customerData, fxoStorage) {
    'use strict';

    return Component.extend({

        /** @inheritdoc */
        initialize: function () {

            if (!window.checkout) {
                return;
            }

            if (window.checkout.is_retail != undefined && !window.checkout.is_retail) {
                return;
            }

            this._super();
            let _this = this;
            _this.getProfileInfoForMobile();
            $(window).on('resize', function () {
                _this.getProfileInfoForMobile();
            });
            $('#store\\.links').remove();
        },

        getProfileInfoForMobile: function () {
            var customloginmobile =  url.build('fcl/index/customloginmobile');
            var customSession =  url.build('fcl/customer/login');
            if ($(window).width() < 1024) {
                // Guest vs Authenticated code
                let checkoutDirection;
                if(window.e383157Toggle){
                    checkoutDirection = fxoStorage.get('checkoutredirection');
                }else{
                    checkoutDirection = localStorage.getItem('checkoutredirection');
                }
                if (checkoutDirection){
                    $('body').trigger('processStart');
                }
                window.loginCall = $.ajax({
                    type: "POST",
                    enctype: "multipart/form-data",
                    url: customSession,
                    data: [],
                    processData: false,
                    contentType: false,
                    cache: false,
                    beforeSend:function(){
                        window.loginInProcess = true;
                        window.loginCalled = false;
                    }
                }).done(function (data) {
                    window.loginInProcess = false;
                    window.loginCalled = true;
                    var successError = data.success;
                    var loginMessage = data.message;
                    $.ajax({
                        type: "POST",
                        enctype: "multipart/form-data",
                        url: customloginmobile,
                        data: [],
                        processData: false,
                        contentType: false,
                        cache: false
                    }).done(function (data) {
                        $(".fcl-login-mobile-toggle").html(data);
                        if (successError == false) {
                            $(".fcl-login-error-popup").show();
                                // Guest vs Authenticated code
                                $('body').trigger('processStop');
                        } else if (successError == 'expired') {
                            try {
                                let fclCookieConfigVal = typeof (window.checkout.fcl_cookie_config_value) !== "undefined" && window.checkout.fcl_cookie_config_value !== null ? window.checkout.fcl_cookie_config_value : 'fdx_login';
                                document.cookie = fclCookieConfigVal +'=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                                document.cookie = 'fdx_cbid' +'=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                            } catch (err) {
                                console.log(err);
                            }
                            return false;
                        }
                        // Guest vs Authenticated code
                            let checkoutDirection;
                            if(window.e383157Toggle){
                                checkoutDirection = fxoStorage.get('checkoutredirection');
                            }else{
                                checkoutDirection = localStorage.getItem('checkoutredirection');
                            }
                            if (checkoutDirection && (loginMessage === "Login Success" || loginMessage  === "Already Login With Customer Session")) {
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
                                if (isOutSourced) {
                                    $('.anchor-ship').trigger("click");
                                } else {
                                    if(window.e383157Toggle){
                                        fxoStorage.set('autopopup', 'true');
                                        fxoStorage.set('pickupkey', 'true');
                                        fxoStorage.set('shipkey', 'false');
                                        fxoStorage.delete('checkoutredirection');
                                    }else{
                                        localStorage.setItem('autopopup', 'true');
                                        localStorage.setItem('pickupkey', 'true');
                                        localStorage.setItem('shipkey', 'false');
                                        localStorage.removeItem('checkoutredirection');
                                    }
                                    window.location.href = url.build('checkout');
                                }
                                $('body').trigger('processStop');
                                if(window.e383157Toggle){
                                    fxoStorage.delete('checkoutredirection');
                                }else{
                                    localStorage.removeItem('checkoutredirection');
                                }
                            } else if (loginMessage === "Already Login With Customer Session" || successError === true) {
                                $('body').trigger('processStop');
                            }
                            if (loginMessage === "Login Success" || loginMessage === "Already Login With Customer Session") {
                                $(document).trigger("loginSuccess");
                            }
                    });
                });
            }
        }
    });
});
