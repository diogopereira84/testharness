/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
  "jquery",
  "Magento_Ui/js/lib/view/utils/dom-observer",
  "Fedex_ExpressCheckout/js/fcl-profile-session",
  "Magento_Customer/js/customer-data",
  "fedex/storage",
  "Fedex_Delivery/js/model/pickup-data"
], function ($, $dom, fclProfileSession,customerData,fxoStorage, pickupData) {

  let isFusebidToggleEnabled = typeof (window.checkoutConfig.is_fusebid_toggle_enabled) !== "undefined" && window.checkoutConfig.is_fusebid_toggle_enabled !== null ? window.checkoutConfig.is_fusebid_toggle_enabled : false;
  let isToggleD217535Enabled = typeof (window.checkoutConfig.tiger_d217535) !== "undefined" && window.checkoutConfig.tiger_d217535 !== null ? window.checkoutConfig.tiger_d217535 : false;
  let isEventInitated = false;

  /**
   * Populate pickup zipcode on edit
   *
   * @returns void
   */
  function popultePickupOnEdit(profileAddress, preferredDelivery) {
    $(document).on("click", ".pickup-title-checkout .checkout-sub.edit_ship" , function () {
        if(window.e383157Toggle){
            fxoStorage.set('populate_shipkey', true);
        }else{
            localStorage.setItem('populate_shipkey', true);
        }
    });
    $(document).on("click", ".pickup-title-checkout .checkout-sub" , function () {
        if(window.e383157Toggle){
            if (!fxoStorage.get('populate_shipkey')) {
                fxoStorage.set('populate_pickupkey', true);
                populatePickupAddress(profileAddress, preferredDelivery);
            }
            fxoStorage.delete('populate_shipkey');
        }else{
            if (!localStorage.getItem('populate_shipkey')) {
                localStorage.setItem('populate_pickupkey', true);
                populatePickupAddress(profileAddress, preferredDelivery);
            }
            localStorage.removeItem('populate_shipkey');
        }
    });

    $('#pickup-shipup-popup-modal').on('modalclosed', function() {
      populatePickupAddress(profileAddress, preferredDelivery);
    });

  }

  /**
   * Populate pickup zipcode
   *
   * @returns void
   */
  function populateZipcode(zipcode, city, state, pickupAddress) {
      if (window.e383157Toggle) {
          fxoStorage.set("fcl-populate-pickup", true);
          fxoStorage.set("fcl-populate-pickup-city", city);
          fxoStorage.set("fcl-populate-pickup-state", state);
          fxoStorage.set("fcl-populate-pickup-zipcode", zipcode);
      } else {
          localStorage.setItem("fcl-populate-pickup", true);
          localStorage.setItem("fcl-populate-pickup-city", city);
          localStorage.setItem("fcl-populate-pickup-state", state);
          localStorage.setItem("fcl-populate-pickup-zipcode", zipcode);
      }
      if (!(window.checkout?.is_retail || window.checkoutConfig?.isRetailCustomer)) {
          customerData.reload(['inBranchdata'], true);
      }
      var inBranchdata = customerData.get('inBranchdata')();

      if (!isToggleD217535Enabled) {
          $dom.get('.pickup-search-form-container #zipcodePickup', function (elem) {
              if (!(inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart)) {
                  $(".pickup-search-form-container #zipcodePickup").val(pickupAddress);
                  $(".pickup-search-form-container #zipcodePickup").trigger("keypress");
              }
          });
          $dom.get('.pickup-search-form-container #search-pickup', function (elem) {
              if (!$(".pickup-search-form-container #search-pickup").is(":disabled")) {
                  $(".pickup-search-form-container #search-pickup").trigger("click");
              }
          });
      } else if (isToggleD217535Enabled && !isEventInitated) {
          $dom.get('.pickup-search-form-container #zipcodePickup', function (elem) {
              if (!(inBranchdata.isInBranchUser && inBranchdata.isInBranchDataInCart)) {
                  $(".pickup-search-form-container #zipcodePickup").val(pickupAddress);
                  $(".pickup-search-form-container #zipcodePickup").trigger("keypress");
              }
          });
          $dom.get('.pickup-search-form-container #search-pickup', function (elem) {
              if (!$(".pickup-search-form-container #search-pickup").is(":disabled")) {
                  $(".pickup-search-form-container #search-pickup").trigger("click");
              }
          });
          isEventInitated = true;
      }
  }

  /**
   * Populate pickup address
   *
   * @returns void
   */
  function populatePickupAddress(profileAddress, preferredDelivery) {
    let isUploadToQuoteAndE469378Enabled =
      window.checkoutConfig.tiger_team_E_469378_u2q_pickup &&
      window.checkoutConfig.is_quote_price_is_dashable;

    try {
        let isPickupKey,isPopulatePickupKey;
        if(window.e383157Toggle){
            isPickupKey = fxoStorage.get('pickupkey');
            isPopulatePickupKey = fxoStorage.get('populate_pickupkey');
        }else{
            isPickupKey = localStorage.getItem('pickupkey');
            isPopulatePickupKey = localStorage.getItem('populate_pickupkey');
        }
      if (isPickupKey || isPopulatePickupKey || isUploadToQuoteAndE469378Enabled) {
        let qouteLocationDetails = null;
        if (window.e383157Toggle) {
          qouteLocationDetails = fxoStorage.get('qouteLocationDetails') ? JSON.parse(fxoStorage.get('qouteLocationDetails')) : null;
        } else {
          qouteLocationDetails = localStorage.getItem('qouteLocationDetails') ? JSON.parse(localStorage.getItem('qouteLocationDetails')) : null
        }
        if(qouteLocationDetails !== null && isFusebidToggleEnabled) {
          let zipcode = qouteLocationDetails.postalCode;
          let locationId = qouteLocationDetails.locationId;


          if(isUploadToQuoteAndE469378Enabled){
            // E_469378 is enabled and we are in upload to quote mode
            // Pre-populate data using the new pickup data model
            pickupData.setPrePopulatedPickupData(null, zipcode, locationId);
            return;
          }

          let waitForPickUIload = setInterval(function () {
            populateZipcode(zipcode, null, null, zipcode);
            $dom.get('.pickup-location-container div.box-container .pick-up-button[data-location-id=' + locationId + ']', function(elem)
            {
              $dom.get('.map-canvas', function(elem) {
                let pickupButtonElement = $('.pickup-location-container div.box-container').find('.pick-up-button[data-location-id=' + locationId + ']');

                pickupButtonElement.trigger('click');
              });
            });
            if ($(".pickup-search-form-container #zipcodePickup").val()) {
              clearInterval(waitForPickUIload);
            }
          }, 2000);
        } else if (profileAddress !== null && preferredDelivery !== null ) {
          let zipcode = preferredDelivery.postalCode;
          let locationId = preferredDelivery.locationId;
          let city = preferredDelivery.city;
          let state = preferredDelivery.state;
          let pickupAddress = preferredDelivery.pickupAddress;

          if(isUploadToQuoteAndE469378Enabled){
            // E_469378 is enabled and we are in upload to quote mode
            // Pre-populate data using the new pickup data model

            if(zipcode){
                pickupData.setPrePopulatedPickupData(pickupAddress, zipcode, locationId);
            } else {
                pickupData.setPrePopulatedPickupData(null,  profileAddress.zipcode, null);
            }

            return;
          }

          if (zipcode) {
            populateZipcode(zipcode, city, state, pickupAddress);
          }

          $dom.get('.pickup-location-container div.box-container .pick-up-button[data-location-id=' + locationId + ']', function(elem)
          {
            $dom.get('.map-canvas', function(elem) {
              let pickupButtonElement = $('.pickup-location-container div.box-container').find('.pick-up-button[data-location-id=' + locationId + ']');

              pickupButtonElement.trigger('click');
            });
          });

        } else if (profileAddress !== null) {
          let zipcode = profileAddress.zipcode;

          if(isUploadToQuoteAndE469378Enabled){
            // E_469378 is enabled and we are in upload to quote mode
            // Pre-populate data using the new pickup data model
            pickupData.setPrePopulatedPickupData(null, zipcode, null);
            return;
          }

          populateZipcode(zipcode, null, null, zipcode);
        }
      }
    } catch(err) {
      console.log('Populating pickup data' + err);
    }
  }

  /**
   * Populate pickup adress
   *
   * @returns void
   */
    return function () {
        let profileAddress = fclProfileSession.getProfileAddress();
        let preferredDelivery = fclProfileSession.getPreferredDeliveryMethod();
        if (window.e383157Toggle) {
            fxoStorage.delete('populate_pickupkey');
        } else {
            window.localStorage.removeItem('populate_pickupkey');
        }
        populatePickupAddress(profileAddress, preferredDelivery);
        popultePickupOnEdit(profileAddress, preferredDelivery);
    }
});
