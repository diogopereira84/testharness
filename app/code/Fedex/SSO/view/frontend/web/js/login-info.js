/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'fedex/storage',
    'Magento_Customer/js/customer-data'
], function ($, fxoStorage, customerData) {
    'use strict';

    function toggleLoginBlocks() {
        if ($(window).width() < 1024) {
            $('.globalize-login-mobile .fcl-mobile-login').show();
            $('.globalize-login-mobile .fcl-signup-login').hide();
        } else {
            $('.globalize-login-mobile .right-top-header-links .fcl-signup-login').show();
            $('.globalize-login-mobile .fcl-mobile-login').hide();
        }
    }

    function updateFirstName() {
        customerData.get('customer').subscribe(function (customer) {
            if (customer && customer.firstname) {
                $('#customer-firstname').text('Hi ' + customer.firstname);
            } else {
                $('#customer-firstname').text('Welcome');
            }
        });
    }

    return function () {
        // init on load/resize
        $(window).on('load resize', toggleLoginBlocks);
        // initial call in case we're already loaded
        toggleLoginBlocks();

        // customer name
        updateFirstName();
    };
});