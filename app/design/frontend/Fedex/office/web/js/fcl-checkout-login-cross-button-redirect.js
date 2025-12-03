define([
    'jquery',
    'mage/url',
    'fedex/storage',
    'domReady!'
], function ($, urlBuilder,fxoStorage) {
    'use strict';
    var options2 = {
        type: 'popup',
        modalClass: 'pickup-shipup-model',
        buttons: [{
            class: 'mymodal1',
            click: function () {
                this.closeModal();
            }
        }]
    };

    $(document).ready(function () {
        $(document).on('click', '.checkout-login-poup-model .action-close', function () {
            if(window.e383157Toggle){
                fxoStorage.set('disabled-checkout-modal-popup', true);
            }else{
                localStorage.setItem('disabled-checkout-modal-popup', true);
            }
        });
    });

    $(document).on('click', '.anchor-pickup', function (e) {
        if(window.e383157Toggle){
            fxoStorage.set("pickupkey", true);
            fxoStorage.set("shipkey", false);
        }else{
            localStorage.setItem("pickupkey", true);
            localStorage.setItem("shipkey", false);
        }

        // E-427430 save warning popup redirect url after click proceed to checkout on minicart
        if (window.explorersCompanySettingsCustomerAdmin) {
            let isCompanySettingEditable = fxoStorage.get("isCompanySettingEditable");
            let isPaymentSettingEditable = fxoStorage.get("isPaymentSettingEditable");
            if (isPaymentSettingEditable) {
                fxoStorage.set("targetUrl", urlBuilder.build('checkout'));
            } else if (isCompanySettingEditable) {
                $('#companySettingRedirectUrl').text(urlBuilder.build('checkout'));
            } else {
                location.href = urlBuilder.build('checkout');
            }
        } else {
            location.href = urlBuilder.build('checkout');
        }
    });

    $(document).on('click', '.anchor-ship', function (e) {
        var isSdeStore = false;
        if ((typeof window.checkout !== 'undefined' && Boolean(window.checkout.is_sde_store) === true) ||
            (typeof window.checkoutConfig !== 'undefined' && Boolean(window.checkoutConfig.is_sde_store) === true)) {
            isSdeStore = true;
        }

        if (window.e383157Toggle) {
            fxoStorage.set("pickupkey", false);
            fxoStorage.set("shipkey", true);
        } else {
            localStorage.setItem("pickupkey", false);
            localStorage.setItem("shipkey", true);
        }

        // E-427430 save warning popup redirect url after click proceed to checkout on minicart
        if (window.explorersCompanySettingsCustomerAdmin) {
            let isCompanySettingEditable = fxoStorage.get("isCompanySettingEditable");
            let isPaymentSettingEditable = fxoStorage.get("isPaymentSettingEditable");
            if (isPaymentSettingEditable) {
                fxoStorage.set("targetUrl", urlBuilder.build('checkout'));
            } else if (isCompanySettingEditable) {
                $('#companySettingRedirectUrl').text(urlBuilder.build('checkout'));
            } else {
                if (isSdeStore) {
                    location.href = urlBuilder.build('checkout/');
                } else {
                    location.href = urlBuilder.build('checkout');
                }
            }
        } else {
            // D-92464 : SDE : Issue when navigating back with checkout breadcrumb and Edit button
            if (isSdeStore) {
                location.href = urlBuilder.build('checkout/');
            } else {
                location.href = urlBuilder.build('checkout');
            }
        }
    });

    $(document).on('input', '.city-field-validation .input-text, .city-field-validation', function () {
        $(this).val(function(i, v) {
            return v.replace(/[^A-Za-z0-9-' \d]/gi, '');
        });
    });
});
