/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * B-1205796 : API integration for CC details and Billing details in Magento Admin
 */
define([
    'Magento_Ui/js/modal/modal-component',
    'jquery',
    'Fedex_Company/js/creditcard/creditcard-handler',
    'fedex/storage'
], function (Modal, $, CreditCardHandler,fxoStorage) {
    'use strict';

    let cardNumberWrapperElementClass = '.card-number .admin__field-control';
    let cardNumberElementClass = '.card-number .admin__control-text';
    let cardCvvElementClass = '.cvv-number .admin__control-text';
    let zipCodeElementClass = '.add-zip .admin__control-text';
    let expirationMonthWrapperClass = '.expiration-month .admin__field-control';
    let expirationMonthElementClass = '.expiration-month .admin__control-select';
    let expirationYearWrapperClass = '.expiration-year .admin__field-control';
    let expirationYearElementClass = '.expiration-year .admin__control-select';

    $(document).on('keypress change', cardNumberElementClass, function () {
        let cardImageElement = $("#img-card");
        cardImageElement.removeAttr('class');

        let cardNumber = CreditCardHandler.getUnFormattedCardNumber($(this));
        let cardType = CreditCardHandler.getCardType(cardNumber);
        CreditCardHandler.addImageClassToElement(cardImageElement, cardType);

        if (cardNumber.length < 22) {
            CreditCardHandler.formatCardNumber($(this));
        }
    });

    $(document).on('keypress', cardNumberElementClass + ',' + cardCvvElementClass + ',' + zipCodeElementClass, function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }
    });

    $(document).on('keypress', cardCvvElementClass, function (e) {
        if ($(this).val().length > 3) {
            e.preventDefault();
        }
    });

    $(document).on('keypress', cardNumberElementClass, function (e) {
        if ($(this).val().length > 22) {
            e.preventDefault();
        }
    });

    $(document).on('paste', cardNumberElementClass, function (e) {
        e.preventDefault();
    });

    $(document).on('focus', cardNumberElementClass, function () {
        if ($(this).val().length > 0) {
            let maskedCreditCardNumber;
            if(window.e383157Toggle){
                maskedCreditCardNumber = fxoStorage.get("CompanyCCCardNumber");
            }else{
                maskedCreditCardNumber = localStorage.getItem("CompanyCCCardNumber");
            }
            $(this).val($.trim(maskedCreditCardNumber));
            CreditCardHandler.formatCardNumber($(this));
        }

        $("#img-card").css({ "opacity": "1.0" });
    });

    $(document).on('focusout', cardNumberElementClass, function () {
        if ($(this).val() == '') {
            $("#img-card").css({ "opacity": "0.5" });
        }
    });

    $(document).on('blur', cardNumberElementClass, function () {
        let cardNumber = $.trim($(this).val());
        let card = CreditCardHandler.getUnFormattedCardNumber($(this));
        if(window.e383157Toggle){
            fxoStorage.set("CompanyCCCardNumber", cardNumber);
        }else{
            localStorage.setItem("CompanyCCCardNumber", cardNumber);
        }
        if (card.length > 13) {
            var masked = "*" + card.substr(-4);
            $(this).val(masked);
        }
    });

    $(document).on('blur', expirationMonthElementClass + ',' + expirationYearElementClass, function () {
        if (CreditCardHandler.isValidCreditCardExpiryDate() === false) {
            let errorMsgHtml = '<label class="admin__field-error date-error">Expiration date entered is invalid.</label>';
            $(expirationMonthWrapperClass).append(errorMsgHtml);
            $(expirationYearWrapperClass).append(errorMsgHtml);
        }
    });

    $(document).on('click', '.cc-modal .action-edit', function () {
        CreditCardHandler.prefillEditForm();
    });

    $(document).on('click', '.cc-modal .action .remove', function () {
        CreditCardHandler.removeCard();
    });

    return Modal.extend({

        /**
         * Open modal
         */
        openModal: function () {
            CreditCardHandler.displayCreditCardInfo(this.options);
            CreditCardHandler.getEncryptedKey(this.options);
            this._super();
        },

        /**
         * Modal state handler
         *
         * @param {*} state
         */
        onState: function (state) {
            this._super();
            if (state) {
                setTimeout(function () {
                    if ($("#img-card").length == 0) {
                        $(cardNumberWrapperElementClass).append('<div id="img-card" class="generic"></div>');
                        $(cardNumberWrapperElementClass).append('<i class="fa fa-check check-icon"></i>');
                    }
                }, 300);
            }
        },

        /**
         * Modal cancel handler
         */
        actionCancel: function () {
            this._super();
            CreditCardHandler.cancelEdit();
        },

        /**
         * Modal form save handler
         */
        actionDone: function () {
            this.valid = true;
            this.elems().forEach(this.validate, this);

            if (this.valid && CreditCardHandler.isValidCreditCardExpiryDate() === true) {
                CreditCardHandler.saveCreditCard(this.options);
            }
        },

        /**
         * Cancel form edit handler
         */
        cancelEdit: function () {
            CreditCardHandler.displayCreditCardInfo(this.options);
        }
    });
});
