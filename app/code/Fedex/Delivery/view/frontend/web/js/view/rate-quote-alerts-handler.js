/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
    'jquery',
], function ($) {
    
    const warningCodes = {
        invalidCoupon: "COUPONS.CODE.INVALID",
        minimumPurchase: "MINIMUM.PURCHASE.REQUIRED",
        invalidProduct: "INVALID.PRODUCT.CODE",
        couponExpired: "COUPONS.CODE.EXPIRED",
        couponRedeemed: "COUPONS.CODE.REDEEMED",
        invalidFedexAccountNum: "RATEREQUEST.FEDEXACCOUNTNUMBER.INVALID"
    };

    const warningMessages = {
        invalidCoupon: 'Promo code invalid. Please try again.',
        minimumPurchase: 'Minimum purchase amount not met.',
        couponExpired: 'Promo code has expired. Please try again.',
        couponRedeemed: 'Promo code has already been redeemed.',
        invalidFedexAccountNum: 'The account number entered is invalid.'
    };

    /**
     * Check warning if need to show
     * @param {object} response
     * @returns {string}
     */
    function warningHandler(response, shipiPickResponse = false) {
        let errorMessageContainerElement = $(".error-container .message-container");
        let errorContainerElement = $(".error-container");
        errorContainerElement.addClass('api-error-hide');
        let warningMessage = '';

        if (response !== 'undefined' && response !== null) {
            response.alerts.forEach((alert) => {
                if (alert.code == warningCodes.invalidCoupon) {
                    warningMessage = warningMessages.invalidCoupon;
                } else if (alert.code == warningCodes.minimumPurchase) {
                    warningMessage = warningMessages.minimumPurchase;
                } else if (alert.code == warningCodes.invalidProduct) {
                    warningMessage = alert.message;
                } else if (alert.code == warningCodes.couponExpired) {
                    warningMessage = warningMessages.couponExpired;
                } else if (alert.code == warningCodes.couponRedeemed) {
                    warningMessage = warningMessages.couponRedeemed;
                } else if (alert.code == warningCodes.invalidFedexAccountNum) {
                    warningMessage = warningMessages.invalidFedexAccountNum;
                }
            });
        }

        if (shipiPickResponse && warningMessage) {
            errorContainerElement.removeClass('api-error-hide');
            errorMessageContainerElement.text(warningMessage).show();
            if(response.transactionId){
                errorMessageContainerElement.html('<p>' + warningMessage + '</p>').append('<p style="font-family: Fedex Sans; color: #2f4047">Transaction ID: ' + response.transactionId + '</p>');
            }
        }

        return warningMessage;
    }

    return {
        warningHandler: warningHandler
    }
})
