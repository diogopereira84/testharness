/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
  "jquery",
  "Magento_Ui/js/lib/view/utils/dom-observer",
  "fedex/storage"
], function (
  $,
  $do,
  fxoStorage
) {

  /**
   * Autofill pickup address
   *
   * @return Boolean
   */
  function autofillPickupAddress(preferredDelivery, profileAddress) {
    if (profileAddress !== null && preferredDelivery !== null) {
      if (preferredDelivery.delivery_method == "PICKUP") {
        let pickupData;
        if (window.e383157Toggle) {
          pickupData = fxoStorage.get("pickupData");
        } else {
          pickupData = localStorage.getItem("pickupData");
        }
        if (pickupData) {
          let pickUpData;
          if (window.e383157Toggle) {
              pickUpData = fxoStorage.get("pickupData");
          } else {
              pickUpData = JSON.parse(localStorage.getItem("pickupData"));
          }
          let zipcode = pickUpData.addressInformation.pickup_location_zipcode;
          let locationId = pickUpData.addressInformation.shipping_detail.method_title;
          $do.get('.pickup-search-form-container #zipcodePickup', function(elem){
            $(".pickup-search-form-container #zipcodePickup").val(zipcode);
            $(".pickup-search-form-container #zipcodePickup").trigger("keypress");
          });
          $do.get('.pickup-search-form-container #search-pickup', function(elem){
            $(".pickup-search-form-container #search-pickup").trigger("click");
          });
          triggerPreferredLocation(locationId);
        } else {
          let zipcode = preferredDelivery.postalCode;
          $do.get('.pickup-search-form-container #zipcodePickup', function(elem){
            $(".pickup-search-form-container #zipcodePickup").val(zipcode);
            $(".pickup-search-form-container #zipcodePickup").trigger("keypress");
          });
          $do.get('.pickup-search-form-container #search-pickup', function(elem){
            $(".pickup-search-form-container #search-pickup").trigger("click");
          });
          if (preferredDelivery.locationId !== null) {
            let locationId = preferredDelivery.locationId;
            triggerPreferredLocation(locationId);
          }
        }
        return true;
      }
    }
    return false;
  }

  /**
   * Autofill payment details
   *
   * @return Boolean
   */
  function autofillPaymentDetails(paymentData) {
      if (paymentData.paymentMethod == 'cc') {
          if (!$(".payment-forms-container").is(':visible')) {
              $(".select-credit-card").trigger("click");
              let dataToken;
              if(window.e383157Toggle){
                  dataToken = fxoStorage.get("dataToken");
              }else{
                  dataToken = localStorage.getItem('dataToken');
              }
              $('[data-token="' + dataToken + '"]').trigger("click");
              if (dataToken == 'NEWCREDITCARD') {
                  $("#name-card").val(paymentData.nameOnCard);
                  let creditCardNumber;
                  if (window.e383157Toggle) {
                      creditCardNumber = fxoStorage.get("creditCardNumber");
                  } else {
                      creditCardNumber = localStorage.getItem("creditCardNumber");
                  }
                  $("#card-number").val(creditCardNumber);
                  $("#expiration-month").val(paymentData.expire.replace(/^0+/, ''));
                  $("#expiration-year").val(paymentData.year);
                  $("#cvv-number").val(paymentData.cvv);
                  $("#card-number").change();
                  $("#expiration-year").change();
                  $(".credit-card-review-button").removeAttr("disabled");
                  if(window.e383157Toggle){
                      fxoStorage.set("creditCardNumber", '');
                  }else{
                      localStorage.setItem("creditCardNumber", '');
                  }
              }
          }
          if (paymentData.isBillingAddress === true && $(".billing-address-checkbox-container").is(":visible") && $('.billing-checkbox').is(':checked')) {
              $(".billing-checkbox").trigger('click');
          } else if (paymentData.isBillingAddress === true && $(".billing-address-checkbox-container").is(":visible") && !$('.billing-checkbox').is(':checked')) {
              $(".billing-checkbox").trigger('click');
              $('.billing-checkbox').prop('checked', false);
              $(".billing-address-form-container").show();
              $(".shipping-address").hide();
          }
          return true;
      } else if (paymentData.paymentMethod == 'fedex') {
          $(".select-fedex-acc").trigger('click');
          return true;
      }
      return false;
  }

  /**
   * Trigger preferred location
   *
   * @return Void
   */
  function triggerPreferredLocation(locationId) {
    let triggerCount = 0;
    $do.get('.pickup-location-container div.box-container', function(elem) {
      if(triggerCount == 0) {
        let pickupButtonElement = $('.pickup-location-container div.box-container').find('.pick-up-button[data-location-id=' + locationId + ']');
        pickupButtonElement.trigger('click');
        triggerCount = triggerCount + 1;
      }
    });
    $(".opc-progress-bar li:nth-child(1)").attr("data-active", true);
  }

  /**
   * Return function
   */
  return {
    autofillPickupAddress: autofillPickupAddress,
    autofillPaymentDetails: autofillPaymentDetails
  };
});
