/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
  "jquery",
  "mage/url",
  "Magento_Checkout/js/model/step-navigator",
  "fedexAccountCheckout",
  "fedex/storage"
], function ($, urlBuilder, stepNavigator, fedexAccountDiscount,fxoStorage) {

  let expressInfoIcon = window.checkoutConfig.media_url + "/express-info.png";
  let checkIcon = window.checkoutConfig.media_url + "/check-icon.png";
  let crossIcon = window.checkoutConfig.media_url + "/close-button.png";

  /**
   * Show error or success message if page refreshed after click on review order button
   */
  $( window ).on( "load", function() {
    let isShowErrMessage;
    if(window.e383157Toggle){
        isShowErrMessage = fxoStorage.get("isShowErrMessage");
    }else{
        isShowErrMessage = localStorage.getItem("isShowErrMessage");
    }
    if (isShowErrMessage) {
      let errorMsg = 'Your fedex account could not be saved at this time, but you can continue checking out.';
      let msgHtml = '<div class="fedex-account-err-msg"><span><img src="'+expressInfoIcon+'"/></span>'+errorMsg+'</div>';

      if ($(".fedex-account-err-msg")) {
          $(".fedex-account-err-msg").remove();
      }
      $(".pay-by-account-container").append(msgHtml);
        if (window.e383157Toggle) {
            fxoStorage.delete("isShowErrMessage");
        } else {
            localStorage.removeItem('isShowErrMessage');
        }
    }
    let isShowSuccMessage;
    if (window.e383157Toggle) {
        isShowSuccMessage = fxoStorage.get("isShowSuccMessage");
    } else {
        isShowSuccMessage = localStorage.getItem("isShowSuccMessage");
    }
    if (isShowSuccMessage) {
      let successMsg = 'Payment method successfully saved to your profile.';
      let msgHtml = '<div class="express-msg-outer-most"><div class="express-msg-outer-container"><div class="express-succ-msg-container"><span class="icon-container"><img class="img-check-icon" alt="Check icon" src="'+checkIcon+'"></span><span class="message">'+successMsg+'</span><img id="express_msg_close" class="img-close-msg" alt="close icon" src="'+crossIcon+'" tabindex="0"></div> </div></div>';

      if ($(".express-msg-outer-most")) {
          $(".express-msg-outer-most").remove();
      }
      $(msgHtml).insertAfter(".opc-progress-bar");
        if (window.e383157Toggle) {
            fxoStorage.delete('isShowSuccMessage');
        } else {
            localStorage.removeItem('isShowSuccMessage');
        }
    }
  });

  /**
   * Show and hide fedex account dropdown list
   */
  $(document).on('click', '.selected-fedex-account', function () {
    if ($(".custom-fedex-account-list-container").is(":visible") === true) {
      $(".custom-fedex-account-list-container").hide();
    } else {
      $(".custom-fedex-account-list-container").show();
    }
    $(".fedex-account-list-error").text("");
    $(".fedex-account-list-error").hide();
    $('.selected-fedex-account').removeClass('contact-error');
  });

  /**
   * Trigger fedex account list when enter or space key is pressed
   */
  $(document).on('keypress', '.selected-fedex-account', function (e) {
    let keycode = (e.keyCode ? e.keyCode : e.which);
    if(keycode  == 13 || keycode  == 32){
      e.preventDefault();
      $(".selected-fedex-account").trigger('click');
    }
  });

  /**
   * Change Fedex account number when click on listed account number
   */
  $(document).on('click', '.fedex-account-value', function () {
    let _this = this;
    let fedexAccountNumber = $(':focus').attr("data-value") ? $(':focus').attr("data-value") : $(_this).attr("data-value");
    let fedexAccountNumberShow = $(':focus').text() ? $(':focus').text() : $(_this).text();
    $(".selected-fedex-account .fedex-account-show").text(fedexAccountNumberShow);
    $(".custom-fedex-account-list-container").hide();
    if (fedexAccountNumber == 'other') {
      $(".account-num-container").show();
      if ($(".fedex-account-value").length > 1) {
        $('input.fedex-account-number').val('');
      } else {
        let appliedFedexAccountNumber = typeof (fedexAccountDiscount.prototype.fedexAccountAppliedNumber()) !== "undefined" && fedexAccountDiscount.prototype.fedexAccountAppliedNumber() !== null ?
          fedexAccountDiscount.prototype.fedexAccountAppliedNumber() : '';

          if(appliedFedexAccountNumber != window.checkoutConfig.company_discount_account_number) {
            $('input.fedex-account-number').val(appliedFedexAccountNumber);
          } else {
            $('input.fedex-account-number').val("")
          }
      }
      $('input.fedex-account-number').trigger('blur');
      $('input.fedex-account-number').removeClass('contact-error');
      $('.fedex-account-number-error').hide();
      $(".save-fedex-account-chk-container").show();
      $("#save_fedex_account_number").prop('checked', false);
        if(window.e383157Toggle){
            fxoStorage.set('isFedexAccountFieldVisible', true);
        }else{
            localStorage.setItem('isFedexAccountFieldVisible', true);
        }
    } else {
      $(".account-num-container").hide();
      $('input.fedex-account-number').val(fedexAccountNumber);
      $('input.fedex-account-number').trigger('blur');
      $(".save-fedex-account-chk-container").hide();
      $("#save_fedex_account_number").prop('checked', false);
        if(window.e383157Toggle){
            fxoStorage.delete('isFedexAccountFieldVisible');
        }else{
            localStorage.setItem('isFedexAccountFieldVisible');
        }
    }
  });

  /**
   * Trigger listed account number when enter or space key is pressed
   */
  $(document).on('keypress', '.fedex-account-value', function (e) {
      let keycode = (e.keyCode ? e.keyCode : e.which);
      if(keycode  == 13 || keycode  == 32){
        e.preventDefault();
        $(".fedex-account-value").trigger('click');
      }
  });

  /**
   * Check and uncheck Save FedEx account number checkbox
   */
  $(document).on('keypress', '#save_fedex_account_checkmark', function (e) {
    let keycode = (e.keyCode ? e.keyCode : e.which);
    if(keycode  == 13 || keycode  == 32){
      e.preventDefault();
      $("#save_fedex_account_number").trigger('click');
    }
  });

  /**
   * Fedex account list will be closed when click on outside of it
   */
  $(document).on('mouseup', function(e) {
    let accountListContainer = $(".custom-fedex-account-list-container");
    let selectedFedexAccount = $(".selected-fedex-account");
    if (!accountListContainer.is(e.target) && accountListContainer.has(e.target).length === 0 && !selectedFedexAccount.is(e.target) && selectedFedexAccount.has(e.target).length === 0) {
      accountListContainer.hide();
    }
  });

  /**
   * Success or error message when click on outside of it
   */
   $(document).on('click', function(e) {
    if (stepNavigator.getActiveItemIndex() != 2 && e.target.id !== 'closeLocalDeliveryMessage') {
      $(".express-msg-outer-most").remove();
      $(".fedex-account-err-msg").remove();
    }
  });

  /**
   * Close success or error message when click on close icon
   */
  $(document).on('click', '#express_msg_close', function () {
    $(".express-msg-outer-most").remove();
  });

  return {
    /**
     * Get Fedex account list with html
     *
     * @return html
     */
    getFedexAccountListWithHtml: function () {
      let html = '';
      let fedexAccountListUrl =  urlBuilder.build('express-checkout/customer/fedexaccountlist');
      $.ajax({
            async: false,
            type: "POST",
            url: fedexAccountListUrl,
            data: {},
            cache: false,
            showLoader: true,
            success: function (data) {
              html = data;
              let totalOptionValue = (html.match(/<li/g) || []).length;
                if (window.e383157Toggle) {
                    if (totalOptionValue > 0) {
                        fxoStorage.set('isFedexAccountList', true);
                    } else {
                        fxoStorage.delete('isFedexAccountList');
                        html = '';
                    }
                } else {
                    if (totalOptionValue > 0) {
                        localStorage.setItem('isFedexAccountList', true);
                    } else {
                        localStorage.removeItem('isFedexAccountList');
                        html = '';
                    }
                }
            }
        });
      return html;
    },

    /**
     * Add fedex account to profile
     *
     * @return json
     */
    addFedexAccountToProfile: function (userProfileId, fedexAccountNumber, nickName, billingReference, isPrimary, reCaptchaToken = false) {
        let responseData = '';
        let saveInfoUrl = urlBuilder.build('customer/account/addnewaccount');
        $.ajax({
            async: false,
            url: saveInfoUrl,
            type: "POST",
            data: {
                userProfileId: userProfileId,
                accountNumber: fedexAccountNumber,
                nickName: nickName,
                billingReference: billingReference,
                isPrimary: isPrimary,
                'g-recaptcha-response': reCaptchaToken
            },
            dataType: 'json',
            showLoader: true,
            success: function (data) {
                responseData = data;
            }
        });
      return responseData;
    }
  };
});
