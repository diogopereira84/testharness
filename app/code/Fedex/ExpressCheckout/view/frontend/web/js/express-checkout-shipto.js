/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
   "jquery",
   'Fedex_ExpressCheckout/js/express-checkout-pickup',
   'Fedex_ExpressCheckout/js/fcl-profile-session',
   'mage/url',
   'Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper',
   'fedex/storage'
], function (
   $,
   expressCheckoutPickup,
   profileSessionBuilder,
   urlBuilder,
   marketplaceQuoteHelper,
   fxoStorage
) {

   /**
    * On Review button click
    *
    */
   $(document).on('click', '.create_quote_review_order', function () {
      if ($("button.create_quote").attr("disabled")) {
         $("button.create_quote").removeAttr("disabled");
      }
      $("button.create_quote").trigger('click');
   });

   /**
    * On Review button click for express checkout pickup
    *
    */
   $(document).on('click', '.create_quote_review_order_pickup', function () {
      if ($("button.place-pickup-order").attr("disabled")) {
         $("button.place-pickup-order").removeAttr("disabled");
      }
      $("button.place-pickup-order").trigger('click');
   });

   /**
    * Enable or disable reveiw order button on change
    *
    */
   $(document).on('change', '.alternate-form-title .input-text', function () {
      let _this = this;
      if ($(_this).val() == 'isSame') {
         $(".create_quote_review_order").removeAttr("disabled");
      } else if ($(_this).val() == 'isNotsame' && ($("#alternate_firstname").val() == '' || $("#alternate_lastname").val() == '' || $("#alternate_phonenumber").val() == '' || $("#alternate_email").val() == '' || $(".alternate-contact-form .field-error").text() != '')) {
         $(".create_quote_review_order").attr("disabled", "disabled");
      }
   });

   /**
    * Enable or disable reveiw order button on change and keyup
    *
    */
   $(document).on('keyup change', '#alternate_firstname, #alternate_lastname, #alternate_phonenumber, #alternate_email', function () {
      if ($(".create_quote").attr("disabled")) {
         $(".create_quote_review_order").attr("disabled", "disabled");
      } else {
         $(".create_quote_review_order").removeAttr("disabled");
      }
   });


   /**
    * Set payment data
    *
    * @returns boolean
    */
   function setPaymentData() {
      let preferredPayment = profileSessionBuilder.getPreferredPaymentMethod();
      let profileAddress = profileSessionBuilder.getProfileAddress();
      let profileInfo = profileSessionBuilder.getProfileAddress();
      let paymentInfo = profileSessionBuilder.getPreferredPaymentMethod();
      expressCheckoutPickup.updateQuotePayment(preferredPayment, profileAddress);
      let skipPayment;
      if(window.e383157Toggle){
          skipPayment = fxoStorage.get("skipPayment");
      }else{
          skipPayment = localStorage.getItem("skipPayment");
      }
      if (skipPayment == 'true') {
         let paymenJsonData = expressCheckoutPickup.preparePaymentJsonData(paymentInfo, profileInfo);
         if (paymenJsonData != null) {
            setPaymentDataInLocalStorage(profileInfo, paymentInfo, paymenJsonData);
         } else {
            if (!isMixedQuote()) {
               expressCheckoutPickup.skipStep(1, "step_code", "paymentStep", "step_code");
               expressCheckoutPickup.triggerDefaultPayment(paymentInfo);

               return true;
            }
         }
      } else {
         if (!isMixedQuote()) {
            expressCheckoutPickup.skipStep(1, "step_code", "paymentStep", "step_code");
            expressCheckoutPickup.triggerDefaultPayment(paymentInfo);

            return true;
         }
      }

      return false;
   }

    /**
     * @returns Bool
     */
    function isMixedQuote() {
       return marketplaceQuoteHelper.isMixedQuote();
    }

   /**
    * Set payment data in local storage
    *
    * @returns void
    */
   function setPaymentDataInLocalStorage(profileInfo, paymentInfo, paymenJsonData) {

       let paymenData = null;
       if(window.e383157Toggle){
           if (fxoStorage.get('paymentData')) {
               paymenData = fxoStorage.get('paymentData');
           }
       }else{
           if (localStorage.getItem('paymentData')) {
               paymenData = JSON.parse(localStorage.getItem('paymentData'));
           }
       }

      if (paymentInfo == 'ACCOUNT') {
         setPaymentAccountDataInLocalStorage(profileInfo, paymentInfo, paymenJsonData, paymenData);
      } else {
         if (!paymenData) {
             if(window.e383157Toggle){
                 fxoStorage.set("paymentData", paymenJsonData);
             }else{
                 localStorage.setItem("paymentData", JSON.stringify(paymenJsonData));
             }
         }
         expressCheckoutPickup.skipStep(2, "payment", "paymentStep", "payment");
      }
   }

   /**
    * Set payment account data in local storage
    *
    * @returns void
    */
   function setPaymentAccountDataInLocalStorage(profileInfo, paymentInfo, paymenJsonData, paymenData) {
      let fedexAccount = typeof (profileInfo.fedexAccount) !== "undefined" && profileInfo.fedexAccount !== null ? profileInfo.fedexAccount : false;
      if (fedexAccount) {
         let applyAccountUrl =  urlBuilder.build("pay/index/payrateapishipandpick");
         $.ajax({
            url: applyAccountUrl,
            type: "POST",
            data: {fedexAccount: fedexAccount.accountNumber},
            dataType: "json",
            showLoader: true,
            async: true,
            complete: function () { },
         }).done(function (response) {
            var baseUrl = window.BASE_URL;
            var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";
               if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                  window.location.replace(orderConfirmationUrl);
               }
               response.rate = response.rateQuote;
            if (response.hasOwnProperty("errors")) {
               $('.error-container').removeClass('api-error-hide');
               expressCheckoutPickup.skipStep(1, "step_code", "paymentStep", "step_code");
               expressCheckoutPickup.triggerDefaultPayment(paymentInfo);
               if (
                  typeof response.errors.is_timeout != 'undefined' &&
                  response.errors.is_timeout != null
                  ) {
                  window.location.replace(orderConfirmationUrl);
               }
            } else {
                if(window.e383157Toggle){
                    fxoStorage.set("paymentData", paymenJsonData);
                }else{
                    localStorage.setItem("paymentData", JSON.stringify(paymenJsonData));
                }
               expressCheckoutPickup.setOrderSummary(response.rate);
               expressCheckoutPickup.skipStep(2, "payment", "paymentStep", "payment");
            }
         });
      } else {
         expressCheckoutPickup.skipStep(2, "payment", "paymentStep", "payment");
      }
   }

   /**
    * Disabled review button for pickup
    *
    * @returns void
    */
   function disabledReviewButtonForPickup() {
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
         if (!$('.button.create_quote_review_order_pickup').length > 0) {
            $('button.place-pickup-order').after('<button type="button" class="button action continue primary create_quote_review_order_pickup"><span>Review Order</span></button>');
         }
         $('button.create_quote_review_order_pickup').prop('disabled', true);
      }
   }

   /**
    * Hide review button for pickup
    *
    * @returns void
    */
   function hideReviewButtonForPickup() {
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
         if (!$('.button.create_quote_review_order_pickup').length > 0) {
            $('button.place-pickup-order').after('<button type="button" class="button action continue primary create_quote_review_order_pickup"><span>Review Order</span></button>');
         }
         $('button.create_quote_review_order_pickup').hide();
      }
   }

   /**
    * Enabled review button for pickup
    *
    * @returns void
    */
   function enabledReviewButtonForPickup() {
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
         if (!$('.button.create_quote_review_order_pickup').length > 0) {
            $('button.place-pickup-order').after('<button type="button" class="button action continue primary create_quote_review_order_pickup"><span>Review Order</span></button>');
         }
         $('button.create_quote_review_order_pickup').prop('disabled', false);
      }
   }

   /**
    * Add review button for pickup
    *
    * @returns void
    */
   function addReviewOrderButtonForPickup() {
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
         if (!$('.button.create_quote_review_order_pickup').length > 0) {
            $('button.place-pickup-order').after('<button type="button" class="button action continue primary create_quote_review_order_pickup"><span>Review Order</span></button>');
         }
         $('.place-pickup-order').hide();
         $('button.create_quote_review_order_pickup').show();
      }
   }

   /**
    * Set preffered delivery method
    *
    * @returns void
    */
   function setPrefferedDeliveryMethod(method, preferredStore) {
      let prefferedDeliveryMethodUrl =  urlBuilder.build('customer/account/preferreddeliverymethod');
      let profileInfo = profileSessionBuilder.getProfileAddress();
      let userProfileId = profileInfo.userProfileId;
      $.ajax({
         type: "POST",
         url: prefferedDeliveryMethodUrl,
         data: {
            userProfileId: userProfileId,
            method: method,
            preferredStore: preferredStore
         },
         cache: false,
         success: function (response) {}
      });
   }

   /**
    * Return function
    */
   return {
      setPaymentData: setPaymentData,
      disabledReviewButtonForPickup: disabledReviewButtonForPickup,
      enabledReviewButtonForPickup: enabledReviewButtonForPickup,
      addReviewOrderButtonForPickup: addReviewOrderButtonForPickup,
      hideReviewButtonForPickup: hideReviewButtonForPickup,
      setPrefferedDeliveryMethod: setPrefferedDeliveryMethod
   };
});
