define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/step-navigator'
], function (ko, $, Component, stepNavigator) {
    'use strict';

    let isCheckoutConfig = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? true : false;
    let isMarketingOptInToggle = false;
    let isFirstTimeChecked = false;

    if (isCheckoutConfig) {
        isMarketingOptInToggle = typeof (window.checkoutConfig.marketing_opt_in_toggle) !== "undefined" && window.checkoutConfig.marketing_opt_in_toggle !== null ? window.checkoutConfig.marketing_opt_in_toggle : false;
    }

    $(document).on('keypress', '.checkout-agreements', function (e) {
        const keycode = (e.keyCode ? e.keyCode : e.which);
        if(keycode  === 13 || keycode  === 32){
          e.preventDefault();
          $(this).find(".checkmark").trigger('click');
        }
    });

    return Component.extend({
        defaults: {
            template: 'Fedex_Customer/checkout/marketing-opt-in'
        },
        isVisible: isMarketingOptInToggle,

        initialize: function () {
            this._super();

            this.showOptIn = ko.computed(function () {
                if (stepNavigator.isProcessed('payment')
                    || window.location.href.includes('checkout#payment')
                    || window.location.href.includes('checkout/#payment')
                ) {
                    $("#marketing-opt-in").show();
                    return true;
                } else {
                    $("#marketing-opt-in").hide();
                    return false;
                }
            }, this);
        },

        /**
         * Checks if Marketing Opt-In is enabled or disabled
         *
         * @returns Bool
         */
        isMarketingOptInToggleStatus: function () {

            return isMarketingOptInToggle;
        },

        checkInput: function () {
            if ($("#marketing-opt-in-checkbox").length && !isFirstTimeChecked) {
                $("#marketing-opt-in-checkbox").prop("checked",true);
                isFirstTimeChecked = true;
            }
        }
    });
});
