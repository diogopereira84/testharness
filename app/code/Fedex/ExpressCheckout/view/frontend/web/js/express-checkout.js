/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
   'jquery',
   'Fedex_ExpressCheckout/js/express-checkout-pickup',
   'Fedex_ExpressCheckout/js/fcl-profile-session',
   'fedex/storage'
], function (
   $,
   expressCheckoutPickup,
   profileSessionBuilder,
   fxoStorage
) {
   'use strict';

   /**
    * Show review order button on first step of chekout
    *
    */
   $(document).on('click', "#co-shipping-method-form .row", function() {
      let _this = this;
      let expressCheckout = window.localStorage.getItem("express-checkout");
      let preferredPayment = profileSessionBuilder.getPreferredPaymentMethod();
      let profileAddress = profileSessionBuilder.getProfileAddress();
      expressCheckoutPickup.updateQuotePayment(preferredPayment, profileAddress);
      let skipPayment;
      if(window.e383157Toggle){
          skipPayment = fxoStorage.get("skipPayment");
      }else{
          skipPayment = localStorage.getItem("skipPayment");
      }
      if (preferredPayment && expressCheckout && skipPayment == 'true') {
         $(_this).children(".col-method").children(".radio").prop("checked", true);
         if (!$('.button.create_quote_review_order').length > 0 && $(_this).children(".col-method").children(".radio").is(':checked')) {
            $('.button.create_quote').after('<button type="button" class="button action continue primary create_quote_review_order"><span>Review Order</span></button>');
         }
         $('button.create_quote').hide();
         $('button.create_quote_review_order').show();
      }
   });

   /**
    * Implement express checkout for pickup
    *
    * @returns void
    */
   function pickupExpressCheckout(preferredDelivery, preferredPayment, profileAddress) {

      expressCheckoutPickup.callDeliveryOption(preferredDelivery, profileAddress).then((response) => {
         expressCheckoutPickup.updateQuotePayment(preferredPayment, profileAddress).then((response) => {
            expressCheckoutPickup.isSkipStepAllowed();
         });
      });
   }

   /**
    * Implement Express Checkout functionality
    *
    * @returns void
    */
   return function (config, element) {
       let isExpressCheckout;
       if(window.e383157Toggle){
           isExpressCheckout = fxoStorage.get("express-checkout");
       }else{
           isExpressCheckout = localStorage.getItem("express-checkout");
       }
       if (isExpressCheckout) {
          if(window.e383157Toggle){
              fxoStorage.set("skipDelivery", false);
              fxoStorage.set("skipPayment", false);
          }else{
              localStorage.setItem("skipDelivery", false);
              localStorage.setItem("skipPayment", false);
          }
         let preferredDelivery = profileSessionBuilder.getPreferredDeliveryMethod();
         let preferredPayment = profileSessionBuilder.getPreferredPaymentMethod();
         let profileAddress = profileSessionBuilder.getProfileAddress();
         let isOutSource = typeof (window.checkoutConfig.is_out_sourced) !== "undefined" && window.checkoutConfig.is_out_sourced !== null ? window.checkoutConfig.is_out_sourced : false;
         if (preferredPayment && preferredDelivery != null && preferredDelivery.delivery_method == "DELIVERY") {
            $(".checkout-index-index #checkout").show();
         } else if (preferredDelivery !== null && !isOutSource) {
            pickupExpressCheckout(preferredDelivery, preferredPayment, profileAddress);
         } else {
            $(".checkout-index-index #checkout").show();
         }
         $(".express-checkout-loader-mask").hide();
      }
   }
});
