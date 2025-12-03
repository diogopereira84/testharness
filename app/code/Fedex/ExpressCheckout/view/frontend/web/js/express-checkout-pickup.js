/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
   "jquery",
   "Magento_Ui/js/lib/view/utils/dom-observer",
   "mage/url",
   "Magento_Checkout/js/model/quote",
   "Magento_Catalog/js/price-utils",
   'Fedex_ExpressCheckout/js/fcl-profile-session',
   "fedexAccountCheckout",
   "shippingFormAdditionalScript",
   "mage/translate",
   "Fedex_MarketplaceCheckout/js/mirakl/model/quote-helper",
   "fedex/storage",
], function (
   $,
   $do,
   urlBuilder,
   quote,
   priceUtils,
   profileSessionBuilder,
   fedexAccountDiscount,
   shippingFormAdditionalScript,
   $t,
   marketplaceQuoteHelper,
   fxoStorage
) {

   /**
    * For skip step
    *
    * @returns void
    */
   function skipStep(index, code, scrollToElementId, hash) {
      $do.get('.opc-progress-bar', function (elem) {
         let registry = require('uiRegistry');
         let progressBar = registry.get('index = progressBar');
         progressBar.navigateTo(code, scrollToElementId);
         progressBar.steps()[index].isVisible(true);
         window.location.hash = "#"+code;
      });
   }

   /**
    * Allow City Characters i.e. Alphabets, Numbers, Single Quotes, Hyphen and Spaces.
    *
    * @param {String|null} cityValue
    * @return {String|null}
    */
   function allowCityCharacters(cityValue) {
      if (typeof (cityValue) != 'undefined' && cityValue != null) {
         return cityValue.replace(/[^A-Za-z0-9-' \d]/gi, '');
      }
      return cityValue;
   }

   /**
    * Implement to identify skip step
    *
    * @returns boolean
    */
   async function isSkipStepAllowed() {

       let skipDelivery,skipPayment,editActionOnExpress;
       if(window.e383157Toggle){
           skipDelivery = fxoStorage.get("skipDelivery");
           skipPayment = fxoStorage.get("skipPayment");
           editActionOnExpress = fxoStorage.get("editActionOnExpress");
       }else{
           skipDelivery = window.localStorage.getItem("skipDelivery");
           skipPayment = window.localStorage.getItem("skipPayment");
           editActionOnExpress = window.localStorage.getItem("editActionOnExpress");
       }

      if ((skipDelivery == 'true' && skipPayment == 'true') || (skipDelivery == 'true' && skipPayment == 'false') && !editActionOnExpress) {

          if(window.e383157Toggle){
              fxoStorage.set("shipkey", false);
              fxoStorage.set("pickupkey", true);
          }else{
              window.localStorage.setItem("shipkey", false);
              window.localStorage.setItem("pickupkey", true);
          }

         $(".opc-progress-bar li:first-child span").html('Delivery method');
         callFXORate();
      } else {
         $(".checkout-index-index #checkout").show();
      }
   }

   /**
    * Call FXO Rate
    *
    * @returns boolean
    */
   function callFXORate() {
       let primaryPickUpData;
       if (window.e383157Toggle) {
           primaryPickUpData = fxoStorage.get('primaryPickUpData');
       } else {
           primaryPickUpData = JSON.parse(localStorage.getItem('primaryPickUpData'));
       }
       let profileInfo = profileSessionBuilder.getProfileAddress();
       let paymentInfo = profileSessionBuilder.getPreferredPaymentMethod();
       let fedexAccount = typeof (profileInfo.fedexAccount) !== "undefined" && profileInfo.fedexAccount !== null ? profileInfo.fedexAccount : false;
       let accountNumber = '';
       if (fedexAccount && fedexAccount.accountValid) {
           accountNumber = fedexAccount.accountNumber;
       }
       let fxoRateUrl = urlBuilder.build("express-checkout/index/updateFXORate");

       $.ajax({
           url: fxoRateUrl,
           showLoader: true,
           type: "POST",
           data: {
               locationId: primaryPickUpData.location.id,
               fedexAccount: accountNumber
           },
           dataType: "json",
           success: function (response) {
               let rateResponse = typeof (response.output) !== "undefined" && response.output !== null ? response.output : false;
               if (rateResponse) {
                   createPickupQuote(rateResponse, primaryPickUpData, profileInfo, paymentInfo);
               } else {
                   if (window.e383157Toggle) {
                       fxoStorage.set("skipDelivery", false);
                       fxoStorage.set("skipPayment", false);
                   } else {
                       localStorage.setItem("skipDelivery", false);
                       localStorage.setItem("skipPayment", false);
                   }
                   $(".checkout-index-index #checkout").show();
               }
           }
       });
   }

   /**
    * Create pickup quote
    *
    * @returns void
    */
   function createPickupQuote(rateResponse, primaryPickUpData, profileInfo, paymentInfo) {
      rateResponse.rate = rateResponse.rateQuote;
      let rateQuoteResponse = typeof (rateResponse.rate) !== "undefined" && rateResponse.rate !== null ? rateResponse.rate : false;
      if(rateQuoteResponse) {
         $("#rateApiResponse").val(JSON.stringify(rateQuoteResponse));
         let pickUpJsonData = preparePickUpJsonData(profileInfo, primaryPickUpData);
         if(window.e383157Toggle){
             fxoStorage.set('locationId', primaryPickUpData.location.id);
             fxoStorage.set('pickupData', pickUpJsonData);
         }else{
             localStorage.setItem('locationId', primaryPickUpData.location.id);
             localStorage.setItem('pickupData', JSON.stringify(pickUpJsonData));
         }
         pickUpJsonData.addressInformation.pickup_location_name = encodeURIComponent(pickUpJsonData.addressInformation.pickup_location_name);

         pickUpJsonData.addressInformation.pickup_location_street = encodeURIComponent(pickUpJsonData.addressInformation.pickup_location_street);

         let pickupPostData = JSON.stringify(pickUpJsonData).replaceAll('&', encodeURIComponent('&'));
         let createQuoteUrl =  urlBuilder.build("delivery/quote/createpost");

         $.ajax({
            url: createQuoteUrl,
            type: "POST",
            data: "data=" + pickupPostData,
            dataType: "json",
            showLoader: true,
            async: true,
            complete: function () { },
         }).done(function (resData) {
            localStorage.setItem("pickupDateTime", primaryPickUpData.estimatedDeliveryLocalTimeShow);
            let paymenJsonData = preparePaymentJsonData(paymentInfo, profileInfo);

            if (marketplaceQuoteHelper.isFullFirstPartyQuote()) {

               window.dispatchEvent(new Event('express_checkout_full_1p_pickup'));

               if (paymenJsonData != null) {
                   if(window.e383157Toggle){
                       fxoStorage.set("paymentData", paymenJsonData);
                   }else{
                       localStorage.setItem("paymentData", JSON.stringify(paymenJsonData));
                   }
                  skipStep(2, "payment", "paymentStep", "payment");
               } else {
                  skipStep(1, "step_code", "paymentStep", "step_code");
                  triggerDefaultPayment(paymentInfo);
               }
            }

            setOrderSummary(rateQuoteResponse);
            $(".checkout-index-index #checkout").show();
         });
      }
   }

   /**
    * Set payment in quote
    *
    * @returns boolean
    */
   async function updateQuotePayment(preferredPayment, profileAddress) {
      if (profileAddress !== null && preferredPayment !== null) {
         let paymentMethod = null;
         if (preferredPayment == "CREDIT_CARD") {
            if (profileAddress.creditCard !== null) {
               paymentMethod = "fedexccpay";
            }
         } else if (preferredPayment == "ACCOUNT") {
            if(profileAddress.fedexAccount !== null) {
               paymentMethod = "fedexaccount";
            }
         }

         if ( paymentMethod !== null && marketplaceQuoteHelper.isFullFirstPartyQuote() ) {
             if(window.e383157Toggle){
                 fxoStorage.set("skipPayment", true);
             }else{
                 localStorage.setItem("skipPayment", true);
             }
         }
      }
   }

   /**
    * Call delivery option
    *
    * @returns boolean
    */
   async function callDeliveryOption(preferredDelivery, profileAddress) {
      if (profileAddress !== null && preferredDelivery !== null) {
         if (preferredDelivery.delivery_method == "PICKUP" && preferredDelivery.locationId !== null && preferredDelivery.postalCode) {
            let postalCode = preferredDelivery.postalCode;
            let locationId = preferredDelivery.locationId;
            let requestUrl = urlBuilder.build("delivery/index/getpickup");
            await $.ajax({
               url: requestUrl,
               showLoader: true,
               type: "POST",
               data: {
                  zipcode: postalCode, city: '', stateCode: '', radius: '100'
               },
               dataType: "json",
               success: function (deliveryResponse) {
                  callDeliveryOptionSuccess(profileAddress, deliveryResponse, locationId);
               }
            });
         }
      }
   }

   /**
    * Call delivery option
    *
    * @returns boolean
    */
   function callDeliveryOptionSuccess(profileAddress, deliveryResponse, locationId) {
      if (deliveryResponse && deliveryResponse.length > 0 && !deliveryResponse.hasOwnProperty("errors")) {
         deliveryResponse.forEach(function (element) {
             if (locationId == element.location.id) {
                 if (window.e383157Toggle) {
                     fxoStorage.set('primaryPickUpData', element);
                 } else {
                     localStorage.setItem('primaryPickUpData', JSON.stringify(element));
                 }
                 // Handles the Express Checkout InStorePickupShippingCombo for mixed carts
                 if (isMixedQuote()) {
                     if (window.e383157Toggle) {
                         fxoStorage.set("skipDelivery", false);
                     } else {
                         window.localStorage.setItem("skipDelivery", false);
                     }
                     window.dispatchEvent(new Event('express_checkout_mixed_cart_pickup_shipping_combo'));

                     return;
                 }

                 if (localStorage.getItem("chosenDeliveryMethod") === 'shipping') {
                     if (window.e383157Toggle) {
                         fxoStorage.set("skipDelivery", false);
                     } else {
                         window.localStorage.setItem("skipDelivery", false);
                     }
                     return;
                 }
                 if (window.e383157Toggle) {
                     fxoStorage.set("skipDelivery", true);
                 } else {
                     window.localStorage.setItem("skipDelivery", true);
                 }
             }
         });
      }
   }

   /**
    * @returns Bool
    */
   function isMixedQuote() {
      return marketplaceQuoteHelper.isMixedQuote();
   }

   /**
    * Price format with currency
    *
    * @return string
    */
   function priceFormatWithCurrency(price) {
      let formattedPrice = '';
      if (typeof (price) == 'string') {
         formattedPrice = price.replaceAll('$', '').replaceAll(',', '').replaceAll('(', '').replaceAll(')', '');
         formattedPrice = priceUtils.formatPrice(formattedPrice, quote.getPriceFormat());
      } else {
         formattedPrice = priceUtils.formatPrice(price, quote.getPriceFormat());
      }

      return formattedPrice;
   }

   /**
    * Get credit card image
    *
    * @return string
    */
   function getImageForCard(cardType) {
      cardType = cardType.toLowerCase();
      let cardImage = window.checkoutConfig.media_url + "/Generic.png";
      if (cardType == "visa") {
         cardImage = window.checkoutConfig.media_url + "/Visa.png";
      } else if (cardType == "mastercard") {
         cardImage = window.checkoutConfig.media_url + "/MasterCard.png";
      } else if (cardType == "amex") {
         cardImage = window.checkoutConfig.media_url + "/Amex.png";
      } else if (cardType == "discover") {
         cardImage = window.checkoutConfig.media_url + "/Discover.png";
      } else if (cardType == "diners") {
         cardImage = window.checkoutConfig.media_url + "/Diners-Club.png";
      }

      return cardImage;
   }

   /**
    * Prepare pickup json data
    *
    * @return JSON
    */
   function preparePickUpJsonData(profileInfo, primaryPickUpData) {
      let pickUpJsonData = {
         contactInformation: {
            contact_fname: profileInfo.firstName,
            contact_lname: profileInfo.lastName,
            contact_email: profileInfo.email,
            contact_number: profileInfo.phoneNumber,
            contact_number_pickup: profileInfo.phoneNumber+' ',
            contact_ext: "",
            alternate_fname: "",
            alternate_lname: "",
            alternate_email: "",
            alternate_number: "",
            alternate_ext: "",
            isAlternatePerson: false,
         },
         addressInformation: {
            pickup_location_name: primaryPickUpData.location.name,
            pickup_location_street: primaryPickUpData.location.address.streetLines[0],
            pickup_location_city: primaryPickUpData.location.address.city,
            pickup_location_state: primaryPickUpData.location.address.stateOrProvinceCode,
            pickup_location_zipcode: primaryPickUpData.location.address.postalCode,
            pickup_location_country: primaryPickUpData.location.address.countryCode,
            pickup_location_date: primaryPickUpData.estimatedDeliveryLocalTime,
            pickup: true,
            shipping_address: "",
            billing_address: "",
            shipping_method_code: "PICKUP",
            shipping_carrier_code: "fedexshipping",
            shipping_detail: {
               carrier_code: "fedexshipping",
               method_code: "PICKUP",
               carrier_title: "Fedex Store Pickup",
               method_title: primaryPickUpData.location.id,
               amount: 0,
               base_amount: 0,
               available: true,
               error_message: "",
               price_excl_tax: 0,
               price_incl_tax: 0,
            },
         },
         rateapi_response: $('#rateApiResponse').val(),
         orderNumber: null,
      }

      return pickUpJsonData;
   }

   /**
    * Prepare payment json data
    *
    * @return JSON or Boolean
    */
   function preparePaymentJsonData(paymentInfo, profileInfo) {
      let paymentJsonData = null;
      if(paymentInfo == 'CREDIT_CARD') {
         let creditCard = typeof (profileInfo.creditCard) !== "undefined" && profileInfo.creditCard !== null ? profileInfo.creditCard : false;
         if(creditCard) {
            paymentJsonData = prepareCreditCardJson(creditCard);
         }
      } else {
         let fxoAccountNumber = null;
          if (window.e383157Toggle) {
              if (fxoStorage.get('selectedfedexAccount')) {
                  fxoAccountNumber = fxoStorage.get('selectedfedexAccount');
                  let maskedAccountNumber = fxoAccountNumber.length > 4 ? "*" + fxoAccountNumber.substr(-4) : fxoAccountNumber;
                  paymentJsonData = prepareFXOAccountJson(fxoAccountNumber);
                  fxoStorage.set('selectedfedexAccount', fxoAccountNumber);
                  fxoStorage.set('fedexAccount', fxoAccountNumber);
                  fedexAccountDiscount.prototype.isFedexAccount(true);
                  fedexAccountDiscount.prototype.fedexAccountNumber(maskedAccountNumber);
                  fedexAccountDiscount.prototype.showFedexAccount(false);
                  fedexAccountDiscount.prototype.fedexAccountAppliedNumber(fxoAccountNumber);
                  fxoStorage.set('isFedexAccountFieldVisible', true);
              } else {
                  let fedexAccount = typeof (profileInfo.fedexAccount) !== "undefined" && profileInfo.fedexAccount !== null ? profileInfo.fedexAccount : false;
                  if (fedexAccount) {
                      fxoAccountNumber = fedexAccount.accountNumber;
                      paymentJsonData = prepareFedexAccountJson(fedexAccount);
                      fxoStorage.set('selectedfedexAccount', fedexAccount.accountNumber);
                      fxoStorage.set('fedexAccount', fedexAccount.accountNumber);
                      fedexAccountDiscount.prototype.isFedexAccount(true);
                      fedexAccountDiscount.prototype.fedexAccountNumber(fedexAccount.maskedAccountNumber);
                      fedexAccountDiscount.prototype.showFedexAccount(false);
                      fedexAccountDiscount.prototype.fedexAccountAppliedNumber(fedexAccount.accountNumber);
                  }
              }
          } else {
              if (localStorage.getItem('selectedfedexAccount')) {
                  fxoAccountNumber = localStorage.getItem('selectedfedexAccount');
                  let maskedAccountNumber = fxoAccountNumber.length > 4 ? "*" + fxoAccountNumber.substr(-4) : fxoAccountNumber;
                  paymentJsonData = prepareFXOAccountJson(fxoAccountNumber);
                  localStorage.setItem('selectedfedexAccount', fxoAccountNumber);
                  localStorage.setItem('fedexAccount', fxoAccountNumber);
                  fedexAccountDiscount.prototype.isFedexAccount(true);
                  fedexAccountDiscount.prototype.fedexAccountNumber(maskedAccountNumber);
                  fedexAccountDiscount.prototype.showFedexAccount(false);
                  fedexAccountDiscount.prototype.fedexAccountAppliedNumber(fxoAccountNumber);
                  localStorage.setItem('isFedexAccountFieldVisible', true);
              } else {
                  let fedexAccount = typeof (profileInfo.fedexAccount) !== "undefined" && profileInfo.fedexAccount !== null ? profileInfo.fedexAccount : false;
                  if (fedexAccount) {
                      fxoAccountNumber = fedexAccount.accountNumber;
                      paymentJsonData = prepareFedexAccountJson(fedexAccount);
                      localStorage.setItem('selectedfedexAccount', fedexAccount.accountNumber);
                      localStorage.setItem('fedexAccount', fedexAccount.accountNumber);
                      fedexAccountDiscount.prototype.isFedexAccount(true);
                      fedexAccountDiscount.prototype.fedexAccountNumber(fedexAccount.maskedAccountNumber);
                      fedexAccountDiscount.prototype.showFedexAccount(false);
                      fedexAccountDiscount.prototype.fedexAccountAppliedNumber(fedexAccount.accountNumber);
                  }
              }
          }
         if (fxoAccountNumber) {
            let applyAccountUrl =  urlBuilder.build("pay/index/payrateapishipandpick");
            $.ajax({
               url: applyAccountUrl,
               type: "POST",
               data: {fedexAccount: fxoAccountNumber},
               dataType: "json",
               showLoader: true,
               async: true,
               complete: function () { },
            }).done(function (response) {
               if (response && typeof response !== 'undefined' && response.length > 0) {
                  var baseUrl = window.BASE_URL;
                  var orderConfirmationUrl = baseUrl + "submitorder/index/ordersuccess";
                  if (response.hasOwnProperty("errors") && typeof response.errors.is_timeout != 'undefined' && response.errors.is_timeout != null) {
                     window.location.replace(orderConfirmationUrl)
                  }  else if (typeof response.is_timeout != 'undefined' && response.is_timeout != null) {
                     window.location.replace(orderConfirmationUrl);
                  }
               }
            });
         }
      }

      return paymentJsonData;
   }

   /**
    * Prepare credit card json
    *
    * @return JSON
    */
   function prepareCreditCardJson(creditCard) {
      let creditCardJson = null;
      if(!creditCard.tokenExpired) {
         let fedexAccountNumber = typeof (fedexAccountDiscount.prototype.fedexAccountAppliedNumber()) !== "undefined" && fedexAccountDiscount.prototype.fedexAccountAppliedNumber() !== null ?
         fedexAccountDiscount.prototype.fedexAccountAppliedNumber() : null;
         let isFedexAccountApplied = fedexAccountDiscount.prototype.isFedexAccount();
         creditCardJson = {
            profileCreditCardId: creditCard.profileCreditCardId,
            paymentMethod: 'cc',
            number: creditCard.maskedCreditCardNumber,
            isBillingAddress: true,
            isFedexAccountApplied: isFedexAccountApplied,
            fedexAccountNumber: fedexAccountNumber,
            creditCardType: getImageForCard(creditCard.creditCardType),
            billingAddress: {
               state: creditCard.billingAddress.stateOrProvinceCode,
               company: creditCard.billingAddress.company.name ? creditCard.billingAddress.company.name : '',
               address: creditCard.billingAddress.streetLines[0] ? creditCard.billingAddress.streetLines[0] : '',
               addressTwo: creditCard.billingAddress.streetLines[1] ? creditCard.billingAddress.streetLines[1] : '',
               city: allowCityCharacters(creditCard.billingAddress.city),
               zip: creditCard.billingAddress.postalCode
            }
         };
      }

      return creditCardJson;
   }

   /**
    * Prepare fedex account json
    *
    * @return JSON
    */
   function prepareFedexAccountJson(fedexAccount) {
      let accountValid = typeof (fedexAccount.accountValid) !== "undefined" && fedexAccount.accountValid !== null ? fedexAccount.accountValid : false;
      let fedexAccountJson = null;
      if(accountValid) {
         fedexAccountJson = {
            paymentMethod: 'fedex',
            fedexAccountNumber: fedexAccount.accountNumber,
            poReferenceId: null
         };
      }

      return fedexAccountJson;
   }

   /**
    * Prepare FXO account json
    *
    * @return JSON
    */
   function prepareFXOAccountJson(fxoAccountNumber) {
      let fedexAccountJson = {
         paymentMethod: 'fedex',
         fedexAccountNumber: fxoAccountNumber,
         poReferenceId: null
      };

      return fedexAccountJson;
   }

   /**
    * Set order summary
    *
    * @return void
    */
   function setOrderSummary(rateQuoteResponse) {
      let shippingAmount = 0;
      let grossAmount = 0;
      let totalDiscountAmount = 0;
      let totalNetAmount = 0;
      let estimatedShippingTotal = $t('TBD');
      rateQuoteResponse.rateDetails = rateQuoteResponse.rateQuoteDetails;
      if (typeof (rateQuoteResponse) != "undefined" && typeof (rateQuoteResponse.rateDetails) != "undefined") {
         rateQuoteResponse.rateDetails.forEach((rateDetail) => {
            setHcoPrice(rateDetail);
            if (typeof rateDetail.deliveryLines != "undefined") {
               rateDetail.deliveryLines.forEach((deliveryLine) => {
                  let deliveryLinePrice = 0;
                     let deliveryRetailPrice = deliveryLine.deliveryRetailPrice;
                     // alert(deliveryRetailPrice);
                     if (typeof deliveryRetailPrice == 'string') {
                        deliveryLinePrice = parseFloat(deliveryRetailPrice.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                     } else {
                        deliveryLinePrice = parseFloat(deliveryRetailPrice);
                     }
                  shippingAmount = shippingAmount + deliveryLinePrice;
               });
            }

            if (typeof rateDetail.productLines != "undefined") {
               rateDetail.productLines.forEach((productLine) => {
                  let productLinePrice = 0;
                     let productRetailPrice = productLine.productRetailPrice;
                     if (typeof productRetailPrice == 'string') {
                           productLinePrice = parseFloat(productRetailPrice.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                     } else {
                           productLinePrice = parseFloat(productRetailPrice);
                     }
                  grossAmount += productLinePrice;
               });
            }

            if (typeof rateDetail.discounts != "undefined") {
               rateDetail.discounts.forEach((discount) => {
                  let totalDiscountPrice = 0;
                        let totalDiscountAmount = discount.amount;
                        if (typeof totalDiscountAmount == 'string') {
                           totalDiscountPrice = parseFloat(totalDiscountAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
                        } else {
                           totalDiscountPrice = parseFloat(totalDiscountAmount);
                        }

                  totalDiscountAmount += totalDiscountPrice;
               });
            }

            if (typeof rateDetail.totalAmount != "undefined") {
               let amountDetails = getAmountDetails(rateDetail, estimatedShippingTotal, totalNetAmount);
               totalNetAmount = amountDetails.totalNetAmount;
               estimatedShippingTotal = amountDetails.estimatedShippingTotal;
            }

         });
      }

      shippingAmount = priceUtils.formatPrice(shippingAmount, quote.getPriceFormat());
      totalNetAmount = priceFormatWithCurrency(totalNetAmount);
      totalDiscountAmount = priceFormatWithCurrency(totalDiscountAmount);
      grossAmount = priceFormatWithCurrency(grossAmount);
      let taxAmount = priceFormatWithCurrency(rateQuoteResponse.rateDetails[0].taxAmount);
      if(window.e383157Toggle){
          fxoStorage.set("TaxAmount", taxAmount);
          fxoStorage.set("EstimatedTotal", totalNetAmount);
      }else{
          localStorage.setItem("TaxAmount", taxAmount);
          localStorage.setItem("EstimatedTotal", totalNetAmount);
      }
      $(".grand.totals.incl .price").text(totalNetAmount);
      $(".grand.totals .amount .price").text(totalNetAmount);
      $(".totals.sub .amount .price").text(grossAmount);
      $(".totals.shipping.excl .price").text(shippingAmount);
      $(".grand.totals.excl .amount .price").text(shippingAmount);
      $(".totals.discount.excl .amount .price").text(totalDiscountAmount);
      $(".totals.fedexDiscount .amount .price").text(totalDiscountAmount);
      $(".totals-tax .price").text(taxAmount);
      $(".opc-block-summary .table-totals").show();

      shippingFormAdditionalScript.handleEstimatedShippingTotal(estimatedShippingTotal);
   }

   /**
    * Set HCO price
    *
    * @return void
    */
   function setHcoPrice(rateDetail) {
      if (window.checkoutConfig.hco_price_update && typeof rateDetail.productLines != "undefined") {
         let productLines = rateDetail.productLines;
         productLines.forEach((productLine) => {
            let instanceId = productLine.instanceId;
            let itemRowPrice = productLine.productRetailPrice;
            itemRowPrice = priceFormatWithCurrency(itemRowPrice);
            $(".subtotal." + instanceId + " .cart-price .price").html(itemRowPrice);
            $(".subtotal-instance").show();
            $(".checkout-normal-price").hide();
         })
      }
   }

   /**
    * Trigger default payment
    *
    * @return void
    */
   function triggerDefaultPayment(paymentInfo) {
      if(paymentInfo == 'CREDIT_CARD') {
         $(".select-credit-card").trigger('click');
      } else if (paymentInfo == 'ACCOUNT') {
         $(".select-fedex-acc").trigger('click');
      }
   }

   /**
    * Get amount details
    *
    * @return JSON
    */
   function getAmountDetails(rateDetail, estimatedShippingTotal, totalNetAmount) {
      let rateDetailTotalAmount = 0;
         rateDetailTotalAmount = rateDetail.totalAmount;
         if (typeof rateDetailTotalAmount == 'string') {
               rateDetailTotalAmount = parseFloat(rateDetailTotalAmount.replaceAll('$', "").replaceAll(',', "").replaceAll('(', "").replaceAll(')', ""));
         } else {
               rateDetailTotalAmount = parseFloat(rateDetailTotalAmount);
         }
      if (typeof rateDetail.estimatedVsActual != "undefined" && rateDetail.estimatedVsActual == 'ESTIMATED') {
         estimatedShippingTotal = rateDetailTotalAmount;
      } else {
         totalNetAmount += rateDetailTotalAmount;
      }
      return {totalNetAmount: totalNetAmount, estimatedShippingTotal: estimatedShippingTotal};
   }

   /**
    * Return function
    */
   return {
      callDeliveryOption: callDeliveryOption,
      updateQuotePayment: updateQuotePayment,
      isSkipStepAllowed: isSkipStepAllowed,
      preparePaymentJsonData: preparePaymentJsonData,
      skipStep: skipStep,
      setOrderSummary: setOrderSummary,
      triggerDefaultPayment: triggerDefaultPayment
   };
});
