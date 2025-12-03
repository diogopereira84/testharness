define([
    'jquery',
    'uiComponent',
    'ko',
    'Fedex_ExpressCheckout/js/fcl-profile-session',
    'quote-login-modal'
],
    function ($, Component, ko, profileSessionBuilder, quoteLoginModal) {
    return Component.extend({
        hasDisabledProducts: ko.observable(false),
        isQuoteRequest: false,

        initialize: function () {
            this._super();
            const self = this;

            this.isQuoteRequest = Boolean(parseInt(this.isQuoteRequest));

            window.addEventListener('check_disabled_products', function () {
                const disabledProducts = window.checkout.disabledProducts || [];

                if (disabledProducts.length > 0) {
                    self.hasDisabledProducts(true);
                } else {
                    self.hasDisabledProducts(false);
                }
            });
        },

        goToCheckout: function() {
            if(this.isQuoteRequest && !window.checkoutConfig.isCustomerLoggedIn) {
                quoteLoginModal();
            } else {
                $("#cart-to-checkout").trigger("click");
            }
        },

        goToExpressCheckout: function () {
            profileSessionBuilder.setRemoveExpressStorage();
            $("#cart-to-checkout").trigger("click");
        }
    });
});
