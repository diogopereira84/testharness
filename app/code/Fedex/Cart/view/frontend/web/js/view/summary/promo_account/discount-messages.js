define([
    'jquery',
    'uiComponent',
    'ko',
    'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
    'fedex/storage'
], function ($, Component, ko, quoteHelper,fxoStorage) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fedex_Cart/summary/discount-messages.html',
        },

        showDiscountMessage: ko.observable(false),
        showCombinedDiscountMessage: ko.observable(false),
        promoCodeMessage: ko.observable(window.checkoutConfig.promoCodeMessage || ''),
        combinedPromoCodeMessage: ko.observable(window.checkoutConfig.promo_code_combined_discount_message || ''),
        showPromoCodeCombinedDiscountMessage: ko.observable(window.checkoutConfig.show_promo_code_combined_discount_message || ''),
        isMixedQuote: quoteHelper.isMixedQuote(),
        infoUrl: ko.observable(window.checkoutConfig.media_url + "/information.png"),
        promoCodeMessageEnabledToggle: window.checkoutConfig.promoCodeMessageEnabledToggle,

        initialize: function () {
            this._super();

            var self = this;

            addEventListener('promoCode', function () {
                self.handleDiscountMessage();
            });

            addEventListener('closePromoCodeDiscount', function () {
                self.closePromoCodeMessage();
            });

            addEventListener('nonCombinableDiscount', function () {
                self.handleNonCombinableDiscountMessage();
            });

            addEventListener('closeNonCombinableDiscount', function () {
                self.closeCombinedPromoCodeMessage();
            });

            addEventListener('closeMarketplaceDisclaimer', function () {
                self.closeMarketplaceDisclaimerMessage();
            });
        },

        closePromoCodeMessage: function() {
            this.showDiscountMessage(false);
        },

        closeCombinedPromoCodeMessage: function() {
            this.showCombinedDiscountMessage(false);
            let messageContainer = $('.shipping-message-container');
            if(messageContainer.is(':visible')){
                messageContainer.hide();
            }
        },

        closeMarketplaceDisclaimerMessage: function() {
            this.showDiscountMessage(false);
        },

        // 1. Has coupon Code or Fedex Account Number with a mixed quote = Show discount message
        handleDiscountMessage: function() {
            let couponCode,fedexAccountNumber;
            if(window.e383157Toggle){
                couponCode = fxoStorage.get('coupon_code') || '';
                fedexAccountNumber = fxoStorage.get('selectedfedexAccount') || '';
            }else{
                couponCode = localStorage.getItem('coupon_code') || '';
                fedexAccountNumber = localStorage.getItem('selectedfedexAccount') || '';
            }
            if ((couponCode || fedexAccountNumber) && this.isMixedQuote) {
                    this.showDiscountMessage(this.promoCodeMessageEnabledToggle);
            } else {
                this.showDiscountMessage(false);
            }
        },

        // 2. Has a non-combinable coupon Code or Fedex Account Number = Show combined discount message
        handleNonCombinableDiscountMessage: function() {
                this.showCombinedDiscountMessage(this.promoCodeMessageEnabledToggle);
        },
    });
});
