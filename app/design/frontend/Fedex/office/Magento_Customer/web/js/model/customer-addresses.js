/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'ko',
    './customer/address'
], function ($, ko, Address) {
    'use strict';

    var isLoggedIn = ko.observable(window.isCustomerLoggedIn);

    return {
        /**
         * @return {Array}
         */
        getAddressItems: function () {
            var items = [],
            customerData = window.customerData;
            var loggedAsCustomerCustomerId = window.checkoutConfig.loggedAsCustomerCustomerId;
            var mazegeeksCtcAdminImpersonator = window.checkoutConfig.mazegeeks_ctc_admin_impersonator;
            if (isLoggedIn()) {
                if (Object.keys(customerData).length) {
                    if(loggedAsCustomerCustomerId < 1 || !mazegeeksCtcAdminImpersonator) {
                        $.each(customerData.addresses, function (key, item) {
                            items.push(new Address(item));
                        });
                    }
                }
            }

            return items;
        }
    };
});
