/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    'jquery',
    'uiComponent'
], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_Cart/summary/promo-account-container'
        },
        initialize: function () {
            this._super();
        },
        isCustomerShippingAccount3PEnabled: window.checkoutConfig.isCustomerShippingAccount3PEnabled || false,
        closeDiscountMessage: function() {
            $('.shipping-message-container.message-block').fadeOut();
        },
        closeShippingDisclaimerMessage: function() {
            if(!window.checkoutConfig.isCustomerShippingAccount3PEnabled) {
                $('.shipping-disclaimer-container.message-block').addClass('hide');
            }
        }
    });
});
