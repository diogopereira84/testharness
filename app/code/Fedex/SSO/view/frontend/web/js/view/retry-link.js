define([
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'mage/cookies',
    'jquery-ui-modules/widget',
    'Fedex_SSO/js/fclloginpoup'
], function($, url, customerData) {
    'use strict';

    $.widget('mage.SSORetryLink', {
        options: {
            login: '#login-register-popup .btn-popup-login',
            sso: customerData.get('sso_section')
        },
        /**
         * Bind handlers to events
         */
        _create: function() {
            this._on({
                'click': $.proxy(this._retryLogin, this)
            });
        },
        _retryLogin: function() {
            let fclCookieConfigVal = typeof (window.checkout.fcl_cookie_config_value) !== "undefined" && window.checkout.fcl_cookie_config_value !== null ? window.checkout.fcl_cookie_config_value : 'fdx_login';
            if ($.cookie(fclCookieConfigVal)) {
                location.reload(true);
            } else {
                var url = $(this.options.login).attr("href");
                window.location.href = url;
            }
        }
    });

    return $.mage.SSORetryLink;
});
