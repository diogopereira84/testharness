/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
  "jquery",
  'Fedex_ExpressCheckout/js/fcl-profile-session',
  "mage/url",
  "Magento_Ui/js/lib/view/utils/dom-observer",
  "fedex/storage"
], function ($, profileSessionBuilder, urlBuilder, $dom, fxoStorage) {

  /*Set flag for express checkout*/
  $(".cart-summary .express-checkout-cart-button").on("click", function () {
    profileSessionBuilder.setRemoveExpressStorage();
  });

  /*
   * Set flag for express checkout from minicart
   */
  $(document).on('click', '#mini-cart-express-checkout', function () {
    profileSessionBuilder.setRemoveExpressStorage();
    window.location.href = urlBuilder.build("checkout");
  });

  /*Unset flag of express checkout*/
  $(".cart-summary .non-express-checkout-cart").on("click", function () {
      if(window.e383157Toggle){
          fxoStorage.delete("express-checkout");
      }else{
          localStorage.removeItem("express-checkout");
      }
  });

  /*Remove pickup zipcode from storage*/
  $(document).ready(function () {
    let url = window.location.pathname;
    if (url.indexOf("ordersuccess") > -1) {
        if(window.e383157Toggle){
            fxoStorage.delete("pickupZipcode");
        }else{
            localStorage.removeItem("pickupZipcode");
        }
    }
  });

  /*Remove all data from storage after logout*/
  $(document).ajaxComplete(function(event,xhr,settings) {
    if (settings.url.indexOf("fcl/customer/logout") > -1) {
        if(window.e383157Toggle){
            fxoStorage.clearAll();
        }else{
            localStorage.clear();
        }
    }
  });

  /**
   * Prevent redirect to next step after press enter key in shipping account field
   *
   * @return boolean
   */
   $(document).on('keyup keypress', '#co-shipping-method-form #fedExAccountNumber' , function(e) {
    let keyCode = e.keyCode || e.which;
    if (keyCode === 13) {
      e.preventDefault();
      $('#co-shipping-method-form #fedExAccountNumber').trigger('blur');
      $('#co-shipping-method-form #addFedExAccountNumberButton').trigger('click');

      return false;
    }
  });
});
