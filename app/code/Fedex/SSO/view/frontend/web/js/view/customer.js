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

            if (window.checkout.is_retail != undefined && !window.checkout.is_retail) {
                return;
            }

            let fclCookieConfigVal = typeof (window.checkout.fcl_cookie_config_value) !== "undefined" && window.checkout.fcl_cookie_config_value !== null ? window.checkout.fcl_cookie_config_value : 'fdx_login';
            this._super();
            //Added to prevent multiple duplicate login calls D-103864
            let remove_duplicate_login_calls = (window.checkout.hasOwnProperty('hawks_remove_duplicate_login_calls') && window.checkout.hawks_remove_duplicate_login_calls);

            if(!remove_duplicate_login_calls){
                var urlCheck = url !== null || typeof (url) !== 'undefined' ? url : '';
                if (urlCheck) {
                    var customLoginInfo = url.build('fcl/index/customloginInfo');
                    var customSession = url.build('fcl/customer/login');
                } else {
                    var customLoginInfo = '';
                    var customSession = '';
                }

                if ($(window).width() >= 320) {
                    // Guest vs Authenticated code
                    let checkoutDirection ;
                    if(window.e383157Toggle){
                        checkoutDirection = fxoStorage.get("checkoutredirection");
                    }else{
                        checkoutDirection = localStorage.getItem('checkoutredirection');
                    }
                    if (checkoutDirection) {
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
                            url: customLoginInfo,
                            data: [],
                            processData: false,
                            contentType: false,
                            cache: false
                        }).done(function (data) {
                            $(".fcl-login").html(data);
                            if (successError == 'error') {
                                $(".fcl-login-error-popup").show();
                                // Guest vs Authenticated code
                                $('body').trigger('processStop');
                            } else if (successError == 'expired') {
                                try {
                                    document.cookie = fclCookieConfigVal + '=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                                    document.cookie = 'fdx_cbid' + '=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                                } catch (err) {
                                    console.log(err);
                                }
                                return false;
                            }

                            // Guest vs Authenticated code
                            if (checkoutDirection && (loginMessage === "Login Success" || loginMessage === "Already Login With Customer Session")) {
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
                                let isOutSource = typeof (window.checkout.is_out_sourced) !== "undefined" && window.checkout.is_out_sourced !== null ? window.checkout.is_out_sourced : false;
                                if (isOutSource) {
                                    if(window.e383157Toggle){
                                        fxoStorage.delete('autopopup');
                                        fxoStorage.set('pickupkey', 'false');
                                        fxoStorage.set('shipkey', 'true');
                                        fxoStorage.delete('checkoutredirection');
                                    }else{
                                        localStorage.removeItem('autopopup');
                                        localStorage.setItem('pickupkey', 'false');
                                        localStorage.setItem('shipkey', 'true');
                                        localStorage.removeItem('checkoutredirection');
                                    }
                                    window.location.href = url.build('checkout');
                                } else if (isOutSourced) {
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
                else {
                    if ($(window).width() >= 1024) {
                        let checkoutDirection ;
                        if(window.e383157Toggle){
                            checkoutDirection = fxoStorage.get("checkoutredirection");
                        }else{
                            checkoutDirection = localStorage.getItem('checkoutredirection');
                        }
                        if (checkoutDirection) {
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
                                url: customLoginInfo,
                                data: [],
                                processData: false,
                                contentType: false,
                                cache: false
                            }).done(function (data) {
                                $(".fcl-login").html(data);
                                if (successError == 'error') {
                                    $(".fcl-login-error-popup").show();
                                    // Guest vs Authenticated code
                                    $('body').trigger('processStop');
                                } else if (successError == 'expired') {
                                    try {
                                        document.cookie = fclCookieConfigVal + '=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                                        document.cookie = 'fdx_cbid' + '=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
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
                                if (checkoutDirection && (loginMessage === "Login Success" || loginMessage === "Already Login With Customer Session")) {
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
                                    let isOutSource = typeof (window.checkout.is_out_sourced) !== "undefined" && window.checkout.is_out_sourced !== null ? window.checkout.is_out_sourced : false;
                                    if (isOutSource) {
                                        if(window.e383157Toggle){
                                            fxoStorage.delete('autopopup');
                                            fxoStorage.set('pickupkey', 'false');
                                            fxoStorage.set('shipkey', 'true');
                                            fxoStorage.delete('checkoutredirection');
                                        }else{
                                            localStorage.removeItem('autopopup');
                                            localStorage.setItem('pickupkey', 'false');
                                            localStorage.setItem('shipkey', 'true');
                                            localStorage.removeItem('checkoutredirection');
                                        }
                                        window.location.href = url.build('checkout');
                                    } else if (isOutSourced) {
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
            }
            else {
                let _this = this;
                _this.getProfileInfoForDesktop();
                $(window).on('resize', function () {
                    _this.getProfileInfoForDesktop();
                });
            }
        },
        getProfileInfoForDesktop : function(){
            var urlCheck = url !== null || typeof (url) !== 'undefined' ? url : '';
            if (urlCheck) {
                var customLoginInfo = url.build('fcl/index/customloginInfo');
                var customSession = url.build('fcl/customer/login');
            } else {
                var customLoginInfo = '';
                var customSession = '';
            }

            let fclCookieConfigVal = typeof (window.checkout.fcl_cookie_config_value) !== "undefined" && window.checkout.fcl_cookie_config_value !== null ? window.checkout.fcl_cookie_config_value : 'fdx_login';
            //Condition added to fix multiple login calls for the defect D-103864
            if ($(window).width() >= 1024) {
                let checkoutDirection;
                if(window.e383157Toggle){
                    checkoutDirection = fxoStorage.get('checkoutredirection');
                }else{
                    checkoutDirection = localStorage.getItem('checkoutredirection');
                }
                if (checkoutDirection) {
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
                        url: customLoginInfo,
                        data: [],
                        processData: false,
                        contentType: false,
                        cache: false
                    }).done(function (data) {
                        $(".fcl-login").html(data);
                        if (successError == 'error') {
                            $(".fcl-login-error-popup").show();
                            // Guest vs Authenticated code
                            $('body').trigger('processStop');
                        } else if (successError == 'expired') {
                            try {
                                document.cookie = fclCookieConfigVal + '=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                                document.cookie = 'fdx_cbid' + '=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
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
                        if (checkoutDirection && (loginMessage === "Login Success" || loginMessage === "Already Login With Customer Session")) {
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
                            let isOutSource = typeof (window.checkout.is_out_sourced) !== "undefined" && window.checkout.is_out_sourced !== null ? window.checkout.is_out_sourced : false;
                            if (isOutSource) {
                                if(window.e383157Toggle){
                                    fxoStorage.delete('autopopup');
                                    fxoStorage.set('pickupkey', 'false');
                                    fxoStorage.set('shipkey', 'true');
                                    fxoStorage.delete('checkoutredirection');
                                }else{
                                    localStorage.removeItem('autopopup');
                                    localStorage.setItem('pickupkey', 'false');
                                    localStorage.setItem('shipkey', 'true');
                                    localStorage.removeItem('checkoutredirection');
                                }
                                window.location.href = url.build('checkout');
                            } else if (isOutSourced) {
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
