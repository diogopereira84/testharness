/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define(["jquery", "mage/url","fedex/storage"], function ($, urlBuilder,fxoStorage) {

  /**
   * Get profile session
   *
   * @returns object|bool
   */
  function getProfileSession() {
    let checkoutConfig = typeof (window.checkoutConfig) !== "undefined" && window.checkoutConfig !== null ? window.checkoutConfig : false;
    let retailProfileSession = null;
    if (checkoutConfig) {
      retailProfileSession = typeof (window.checkoutConfig.retail_profile_session) !== "undefined" && window.checkoutConfig.retail_profile_session !== null ? window.checkoutConfig.retail_profile_session : false;
    } else {
      retailProfileSession = getUserProfileSession();
    }

    return retailProfileSession;
  }

  /**
   * Identify is Profile exist
   *
   * @returns bool
   */
  function isProfileExist(profileSession) {
    return typeof(profileSession) != 'undefined' && typeof(profileSession.output) != 'undefined' && typeof(profileSession.output.profile) != 'undefined' ? true : false;
  }

  /**
   * Get profile address
   *
   * @returns string
   */
  function getProfileAddress() {
    let profileSession = getProfileSession();
    let profileExist = isProfileExist(profileSession);

    if (profileExist) {
      return getProfileAddressValue(profileSession);
    }
  }

  /**
   * Get profile address value
   *
   * @returns string
   */
  function getProfileAddressValue(profileSession) {
    let profileAddress = null;

    if (typeof(profileSession.output.profile.contact) != 'undefined') {
      profileAddress = {
        "email": null,
        "firstName": null,
        "lastName": null,
        "phoneNumber": null,
        "zipcode": null,
        "creditCard": null,
        "fedexAccount": null,
        "state": null,
        "company": null,
        "address": null,
        "addressTwo": null,
        "city": null,
        "userProfileId": null
      }

      profileAddress.email = getEmail(profileSession);
      profileAddress.firstName = getFirstName(profileSession);
      profileAddress.lastName = getLastName(profileSession);
      profileAddress.phoneNumber = getPhoneNumber(profileSession);
      profileAddress.zipcode = getZipCode(profileSession);
      profileAddress.creditCard = getCreditCard(profileSession);
      profileAddress.fedexAccount = getFedexAccount(profileSession);
      profileAddress.state = getState(profileSession);
      profileAddress.company = getCompany(profileSession);
      profileAddress.address = getAddress(profileSession);
      profileAddress.addressTwo = getAddressTwo(profileSession);
      profileAddress.city = getCity(profileSession);
      profileAddress.userProfileId = getUserProfileId(profileSession);
    }

    return profileAddress;
  }

  /**
   * Get email
   *
   * @return  null or string
   */
  function getEmail(profileSession) {
    let email = null
    if (typeof(profileSession.output.profile.contact.emailDetail) != 'undefined') {
      email = profileSession.output.profile.contact.emailDetail.emailAddress;
    }

    return email;
  }

  /**
   * Get First Name
   *
   * @return  null or string
   */
  function getFirstName(profileSession) {
    let firstName = null
    if (typeof(profileSession.output.profile.contact.personName) != 'undefined') {
      firstName = profileSession.output.profile.contact.personName.firstName;
    }

    return firstName;
  }

  /**
   * Get Last Name
   *
   * @return  null or string
   */
  function getLastName(profileSession) {
    let lastName = null
    if (typeof(profileSession.output.profile.contact.personName) != 'undefined') {
      lastName = profileSession.output.profile.contact.personName.lastName;
    }

    return lastName;
  }

  /**
   * Get phone number
   *
   * @return  null or string
   */
  function getPhoneNumber(profileSession) {
    let phoneNumber = null
    if (typeof(profileSession.output.profile.contact.phoneNumberDetails) != 'undefined') {
      phoneNumber = profileSession.output.profile.contact.phoneNumberDetails[0].phoneNumber.number;
    }

    return phoneNumber;
  }

  /**
   * Get zipcode
   *
   * @return  null or string
   */
  function getZipCode(profileSession) {
    let zipcode = null
    if (typeof(profileSession.output.profile.contact.address) != 'undefined') {
       let address = profileSession.output.profile.contact.address;
       zipcode = address.postalCode;
    }

    return zipcode;
  }

  /**
   * Get credit card
   *
   * @return  null or string
   */
  function getCreditCard(profileSession) {
    let creditCard = null
    if (typeof(profileSession.output.profile.creditCards) != 'undefined') {
       let creditCards = profileSession.output.profile.creditCards;
       creditCards.forEach(function (element) {
         if (element.primary) {
           creditCard = element;
         }
       });
    }

    return creditCard;
  }

  /**
   * Get fedex account
   *
   * @return  null or string
   */
  function getFedexAccount(profileSession) {
    let fedexAccount = null
    if (typeof(profileSession.output.profile.accounts) != 'undefined') {
       let accountsList = profileSession.output.profile.accounts;
       accountsList.forEach(function (element) {
         if (element.accountType == 'PRINTING' && element.primary) {
           fedexAccount = element;
         }
       });
    }

    return fedexAccount;
  }

  /**
   * Get state
   *
   * @return  null or string
   */
  function getState(profileSession) {
    let state = null
    if (typeof(profileSession.output.profile.contact.address) != 'undefined' && typeof(profileSession.output.profile.contact.address.stateOrProvinceCode) != 'undefined') {
       state = profileSession.output.profile.contact.address.stateOrProvinceCode;
    }

    return state;
  }

  /**
   * Get company
   *
   * @return  null or string
   */
  function getCompany(profileSession) {
    let company = null
    if (typeof(profileSession.output.profile.contact.company) != 'undefined' && typeof(profileSession.output.profile.contact.company.name) != 'undefined') {
       company = profileSession.output.profile.contact.company.name;
    }

    return company;
  }

  /**
   * Get address
   *
   * @return  null or string
   */
  function getAddress(profileSession) {
    let address = null
    if (typeof(profileSession.output.profile.contact.address) != 'undefined' && typeof(profileSession.output.profile.contact.address.streetLines[0]) != 'undefined') {
       address = profileSession.output.profile.contact.address.streetLines[0];
     }

    return address;
  }

  /**
   * Get second address
   *
   * @return  null or string
   */
  function getAddressTwo(profileSession) {
    let addressTwo = null
    if (typeof(profileSession.output.profile.contact.address) != 'undefined' && typeof(profileSession.output.profile.contact.address.streetLines[1]) != 'undefined') {
       addressTwo = profileSession.output.profile.contact.address.streetLines[1];
    }

    return addressTwo;
  }

  /**
   * Get second address
   *
   * @return  null or string
   */
  function getCity(profileSession) {
    let city = null
    if (typeof(profileSession.output.profile.contact.address) != 'undefined' && typeof(profileSession.output.profile.contact.address.city) != 'undefined') {
       city = profileSession.output.profile.contact.address.city;
    }

    return city;
  }

  /**
   * Get second address
   *
   * @return  null or string
   */
   function getUserProfileId(profileSession) {

    let userProfileId = null
    if (typeof(profileSession.output.profile.userProfileId) != 'undefined') {
      userProfileId = profileSession.output.profile.userProfileId;
    }

    return userProfileId;
   }


  /**
   * Get preferred delivery method
   *
   * @returns string|void
   */
  function getPreferredDeliveryMethod() {
    let preferredDelivery = null;
    let profileSession = getProfileSession();
    let profileExist = isProfileExist(profileSession);

    if (profileExist) {
      if (typeof(profileSession.output.profile.delivery) != 'undefined' && typeof(profileSession.output.profile.delivery.preferredStore) != 'undefined' && typeof(profileSession.output.profile.delivery.preferredDeliveryMethod) != 'undefined') {
         let locationId = profileSession.output.profile.delivery.preferredStore;
         let preferredDeliveryMethod = profileSession.output.profile.delivery.preferredDeliveryMethod;
         let postalCode = profileSession.output.profile.delivery.postalCode;
         let city = profileSession.output.profile.delivery.city;
         let state = profileSession.output.profile.delivery.state;
         let pickupAddress = profileSession.output.profile.delivery.pickupAddress;
         if (preferredDeliveryMethod !== null) {
            preferredDelivery = {
               "delivery_method": preferredDeliveryMethod,
               "locationId": locationId,
               "postalCode": postalCode,
               "city": city,
               "state": state,
               "pickupAddress": pickupAddress
            };
         }
      }
    }

    return preferredDelivery;
  }

  /**
   * Get preferred payment method
   *
   * @returns string|void
   */
  function getPreferredPaymentMethod() {
    let preferredPayment = null;
    let profileSession = getProfileSession();
    let profileExist = isProfileExist(profileSession);

    if (profileExist) {
      if (typeof(profileSession.output.profile.payment) != 'undefined') {
        let preferredPaymentMethod = profileSession.output.profile.payment.preferredPaymentMethod;

        if (preferredPaymentMethod !== null) {
          preferredPayment = preferredPaymentMethod;
        }
      }
    }

    return preferredPayment;
  }

  /**
   * Set and remove express storage
   *
   * @return void
   */
  function setRemoveExpressStorage () {
    let preferredDelivery = getPreferredDeliveryMethod();
    let isOutSource = typeof (window.checkout.is_out_sourced) !== "undefined" && window.checkout.is_out_sourced !== null ? window.checkout.is_out_sourced : false;
      if(window.e383157Toggle){
          if (preferredDelivery == null  && !isOutSource) {
              fxoStorage.set("autopopup", true);
          } else {
              fxoStorage.delete("autopopup");
          }
          fxoStorage.set("express-checkout", true);
          fxoStorage.delete('editActionOnExpress');
          fxoStorage.delete('dataToken');
      }else{
          if (preferredDelivery == null  && !isOutSource) {
              window.localStorage.setItem("autopopup", true);
          } else {
              window.localStorage.removeItem("autopopup");
          }
          window.localStorage.setItem("express-checkout", true);
          window.localStorage.removeItem('editActionOnExpress');
          window.localStorage.removeItem('dataToken');
      }
  }

  /**
   * Get uer profile session
   *
   * @returns JSON
   */
  function getUserProfileSession() {
    let userProfileSession = null;
    let requestUrl = urlBuilder.build("express-checkout/customer/getprofilesession");
    $.ajax({
        async: false,
        url: requestUrl,
        showLoader: false,
        type: "POST",
        data: {},
        cache: false,
        success: function (profileSessionResponse) {
          userProfileSession = profileSessionResponse;
        }
    });

    return userProfileSession;
  }

  /**
   * Return function
   */
  return {
    getProfileAddress: getProfileAddress,
    getPreferredDeliveryMethod: getPreferredDeliveryMethod,
    getPreferredPaymentMethod: getPreferredPaymentMethod,
    setRemoveExpressStorage: setRemoveExpressStorage
  };

});
