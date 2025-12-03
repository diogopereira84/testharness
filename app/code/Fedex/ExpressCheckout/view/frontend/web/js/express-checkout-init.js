/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Fedex_ExpressCheckout/js/fcl-profile-session',
    'fedex/storage'
], function ($, profileSessionBuilder, fxoStorage) {
    'use strict';

    // D-239988 - CSP Bugfix
    // This function is a copy of the original inline script in expressCheckout.phtml, no changes were made.
    function initExpressCheckout() {
        if (window.e383157Toggle) {
            if (fxoStorage.get('express-checkout')) {
                $('.checkout-index-index #checkout').hide();
                $('.express-checkout-loader-mask').show();
                let preferredDelivery = profileSessionBuilder.getPreferredDeliveryMethod();
                let preferredPayment = profileSessionBuilder.getPreferredPaymentMethod();
                let editActionOnExpress = fxoStorage.get('editActionOnExpress');
                let isOutSource = typeof (window.checkoutConfig.is_out_sourced) !== 'undefined'
                    && window.checkoutConfig.is_out_sourced !== null
                    ? window.checkoutConfig.is_out_sourced : false;

                if (preferredPayment && preferredDelivery != null &&
                    preferredDelivery.delivery_method === 'DELIVERY' && !editActionOnExpress) {
                    fxoStorage.set('pickupkey', 'false');
                    fxoStorage.set('shipkey', 'true');
                } else if (preferredDelivery == null && !isOutSource === true) {
                    fxoStorage.set('pickupkey', 'true');
                    fxoStorage.set('shipkey', 'false');
                } else if (preferredDelivery == null && isOutSource === true) {
                    fxoStorage.set('pickupkey', 'false');
                    fxoStorage.set('shipkey', 'true');
                    fxoStorage.delete('autopopup');
                } else if (preferredDelivery != null &&
                    preferredDelivery.delivery_method === 'PICKUP' && isOutSource === true) {
                    fxoStorage.set('pickupkey', 'false');
                    fxoStorage.set('shipkey', 'true');
                    fxoStorage.delete('autopopup');
                } else if (
                    typeof (window.checkoutConfig) !== 'undefined' && preferredDelivery != null &&
                    preferredDelivery.delivery_method === 'PICKUP' && !isOutSource === true
                ) {
                    fxoStorage.set('pickupkey', 'true');
                    fxoStorage.set('shipkey', 'false');
                }
            }
        } else {
            if (window.localStorage.getItem('express-checkout')) {
                $('.checkout-index-index #checkout').hide();
                $('.express-checkout-loader-mask').show();
                let preferredDelivery = profileSessionBuilder.getPreferredDeliveryMethod();
                let preferredPayment = profileSessionBuilder.getPreferredPaymentMethod();
                let editActionOnExpress = window.localStorage.getItem('editActionOnExpress');
                let isOutSource = typeof (window.checkoutConfig.is_out_sourced) !== 'undefined'
                    && window.checkoutConfig.is_out_sourced !== null
                    ? window.checkoutConfig.is_out_sourced : false;

                if (preferredPayment && preferredDelivery != null &&
                    preferredDelivery.delivery_method === 'DELIVERY' && !editActionOnExpress) {
                    localStorage.setItem('pickupkey', false);
                    localStorage.setItem('shipkey', true);
                } else if (preferredDelivery == null && !isOutSource === true) {
                    localStorage.setItem('pickupkey', true);
                    localStorage.setItem('shipkey', false);
                } else if (preferredDelivery == null && isOutSource === true) {
                    localStorage.setItem('pickupkey', false);
                    localStorage.setItem('shipkey', true);
                    localStorage.removeItem('autopopup');
                } else if (preferredDelivery != null &&
                    preferredDelivery.delivery_method === 'PICKUP' && isOutSource === true) {
                    localStorage.setItem('pickupkey', false);
                    localStorage.setItem('shipkey', true);
                    localStorage.removeItem('autopopup');
                } else if (
                    typeof (window.checkoutConfig) !== 'undefined' && preferredDelivery != null &&
                    preferredDelivery.delivery_method === 'PICKUP' && !isOutSource === true
                ) {
                    localStorage.setItem('pickupkey', true);
                    localStorage.setItem('shipkey', false);
                }
            }
        }
    }

    return function () {
        initExpressCheckout();
    };
});
