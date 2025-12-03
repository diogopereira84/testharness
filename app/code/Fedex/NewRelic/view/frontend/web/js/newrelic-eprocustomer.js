define([
    'jquery'
], function ($) {
    'use strict'

    return function (config, elm) {
        $(document).ready(function() {
            var isEproCustomer = config.isEproCustomer;
            var assignedCompany = config.assignedCompany;
            var isCustomerLoggedIn = config.isCustomerLoggedIn;
            if(isEproCustomer && isCustomerLoggedIn){
                if (typeof newrelic == 'object') {
                    newrelic.setCustomAttribute('eProCustomer', assignedCompany);
                }
            }
        });
    }
});
