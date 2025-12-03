define([
    'jquery',
    'fedex/storage'
], function ($, fxoStorage) {
    'use strict';

    return function initBundleSetupCompleted(config, element) {
        let hideTimer = null;
        const TOAST_DURATION_MS = 5000;

        function showToast() {
           $(element).addClass('show');
           if (hideTimer) {
               clearTimeout(hideTimer);
           }
           hideTimer = setTimeout(function () {
               $(element).removeClass('show');
           }, TOAST_DURATION_MS);
        }

        $(element).on('click', '.close-icon', function (e) {
            e.preventDefault();
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            $(element).removeClass('show');
        });

        if (config.isBundleProductsSetupCompleted === '1' && config.hasBundleProductInCart === '1' && fxoStorage.get(config.storageKey) !== true) {
            showToast();
            fxoStorage.set(config.storageKey, true);
        }

        if(config.hasBundleProductInCart === '1' && config.isBundleProductsSetupCompleted !== '1') {
            fxoStorage.set(config.storageKey, false);
        }

        return {
            show: showToast,
        };
    };
});
