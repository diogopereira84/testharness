define([
    'mage/utils/wrapper',
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/step-navigator',
    'fedex/storage'
], function (wrapper, $, url, stepNavigator, fxoStorage) {
    'use strict';

    let retailProfileSession = typeof (window.checkoutConfig.retail_profile_session) !== "undefined" && window.checkoutConfig.retail_profile_session !== null ? window.checkoutConfig.retail_profile_session : false;

    let creditCardPaymentMethod = window.checkoutConfig.credit_card_payment_method_identifier;

    let availablePaymentMethodsValue = typeof window.checkoutConfig.available_payment_method_value !== '' ? window.checkoutConfig.available_payment_method_value : '';

    return function (fclCreditCardList) {
        fclCreditCardList.generateCreditCardHtml = wrapper.wrapSuper(fclCreditCardList.generateCreditCardHtml, function () {
            if (!$('.credit-card-dropdown').length) {
                let retailData = {};
                let creditCardList = '';
                let isImpersonator = false;
                let loggedAsCustomerCustomerId = window.checkoutConfig.loggedAsCustomerCustomerId;
                let mazegeeksCtcAdminImpersonator = window.checkoutConfig.mazegeeks_ctc_admin_impersonator;
                if(loggedAsCustomerCustomerId > 0 && mazegeeksCtcAdminImpersonator) {
                    isImpersonator = true;
                }
                if (retailProfileSession || isImpersonator) {
                    if(!isImpersonator) {
                        creditCardList = typeof (retailProfileSession.output.profile.creditCards) !== "undefined" && retailProfileSession.output.profile.creditCards !== null ? retailProfileSession.output.profile.creditCards : false;
                    }
                    
                    let primaryCardHtml = '';
                    let count = 0;
                    let isExpressCheckout;
                    if (window.e383157Toggle) {
                        isExpressCheckout = fxoStorage.get('express-checkout');
                    } else {
                        isExpressCheckout = localStorage.getItem('express-checkout');
                    }
                    if (creditCardList.length > 0 || isExpressCheckout) {
                        if (creditCardList.length > 0) {
                            creditCardList.forEach(function (list) {
                                let imageUrl = window.checkoutConfig.media_url + '/' + list.creditCardType.toLowerCase() + '.png';
                                if (list.primary) {
                                    primaryCardHtml += '<img class="card-icon" src="' + imageUrl + '" alt="' + list.creditCardType + '"/><span class="card-mid-content">' + list.creditCardType + '</span><span class="card-last-content"> ending in *' + list.maskedCreditCardNumber.slice(-4) + '</span>';
                                    retailData['token'] = list.profileCreditCardId;
                                    retailData['nameOnCard'] = list.cardHolderName;
                                    retailData['number'] = list.maskedCreditCardNumber;
                                    retailData['type'] = list.creditCardType;
                                    retailData['is_token_expaired'] = list.tokenExpired;
                                    count++;
                                }
                            });
                        }
                        if (!count && creditCardList.length > 0) {
                            let list = creditCardList[0];
                            let imageUrl = window.checkoutConfig.media_url + '/' + list.creditCardType.toLowerCase() + '.png';
                            primaryCardHtml += '<img class="card-icon" src="' + imageUrl + '" alt="' + list.creditCardType + '"/><span class="card-mid-content">' + list.creditCardType + '</span><span class="card-last-content"> ending in *' + list.maskedCreditCardNumber.slice(-4) + '</span>';
                            retailData['token'] = list.profileCreditCardId;
                            retailData['nameOnCard'] = list.cardHolderName;
                            retailData['number'] = list.maskedCreditCardNumber;
                            retailData['type'] = list.creditCardType;
                            retailData['is_token_expaired'] = list.tokenExpired;
                        }
                        let creditCardHtml = '<div class="credit-card-dropdown"><label class="credit-card-label" for="name-card">CREDIT CARD</label><div class="pre-select-card" id="credit-card-dropdown-content" tabindex="0">' + primaryCardHtml + '</div><ul class="credit-card-dropdown-content">';
                        if (creditCardList.length > 0) {
                            creditCardHtml += '<li class="fedex-card-title"><span>Saved Card (Personal)</span></li>';
                            creditCardList.forEach(function (list) {
                                let imageUrl = window.checkoutConfig.media_url + '/' + list.creditCardType.toLowerCase() + '.png';
                                creditCardHtml += '<li class="card-list" data-token="' + list.profileCreditCardId + '" data-number="' + list.maskedCreditCardNumber + '" data-type="' + list.creditCardType + '" data-primary="' + list.primary + '" data-tokenexpired="' + list.tokenExpired + '" tabindex="0"><img class="card-icon" src="' + imageUrl + '" alt="' + list.creditCardType + '"/><span class="card-mid-content">' + list.creditCardType + '</span><span class="card-last-content"> ending in *' + list.maskedCreditCardNumber.slice(-4) + '</span></li>';
                            });
                        }
                        creditCardHtml += '<li class="card-list last" data-token="NEWCREDITCARD"><span class="manual-text" tabindex="0"><i aria-hidden="true" class="fa fa-plus"></i><span class="manual-text">Enter new credit card</span></span></li></ul></div>';


                        $(".cc-form-container").before(creditCardHtml);
                        if (creditCardList.length > 0) {
                            retailData['is_card'] = true;
                        }
                        $(".name-card-container, .card-number-container, .expiration-cvv-container, .profile-terms-condition").hide();
                        $(".credit-card-review-button").attr('disabled', 'disabled');
                        $(".credit-card-review-button").addClass("place-pickup-order-disabled");
                        if ($(".card-list").length == 1 && isExpressCheckout) {
                            $(".card-list.last").trigger('click');
                            $(".credit-card-dropdown").hide();
                        }
                    } else if (creditCardPaymentMethod && availablePaymentMethodsValue) {
                        let creditCardHtml = '<div class="credit-card-dropdown"><label class="credit-card-label" for="name-card">CREDIT CARD</label><div class="pre-select-card" id="credit-card-dropdown-content" tabindex="0"></div><ul class="credit-card-dropdown-content"><li class="card-list last" data-token="NEWCREDITCARD"><span class="manual-text" tabindex="0"><span class="manual-text">Manually enter a credit card</span></span></li></ul></div>';
                        $(".cc-form-container").before(creditCardHtml);
                    } else {
                        retailData['is_card'] = '';
                        $(".name-card-container, .card-number-container, .expiration-cvv-container, .profile-terms-condition").show();
                    }

                    if (window.e383157Toggle) {
                        fxoStorage.set('is_retail_credit_card_data', retailData);
                    } else {
                        localStorage.setItem('is_retail_credit_card_data', JSON.stringify(retailData));
                    }
                }
                return creditCardList.length;
            }
        });

        return fclCreditCardList;
    };
});
