/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * B-1205796 : API integration for CC details and Billing details in Magento Admin
 */
define([
    'jquery',
    'uiRegistry',
    'Fedex_Pay/js/view/ans1',
    'Fedex_Pay/js/view/rsaes-oaep',
    'Magento_Ui/js/modal/confirm',
    'fedex/storage'
], function ($, registry, ans, rsa, Confirm,fxoStorage) {
    'use strict';

    var modalClass = '.cc-modal';
    var cardHolderNameIndex = 'card_holder_name';
    var cardNumberIndex = 'card_number';
    var expMonthIndex = 'expiration_month';
    var expYearIndex = 'expiration_year';
    var cvvNumberIndex = 'cvv_number';
    var billingCountryIndex = 'billing_country';
    var addressOneIndex = 'address_line_one';
    var addressTwoIndex = 'address_line_two';
    var cityIndex = 'address_city';
    var stateIndex = 'address_state';
    var zipIndex = 'address_zip';
    var ccTokenIndex = 'cc_token';
    var ccDataIndex = 'cc_data';
    var ccTokenExpiryDateTimeIndex = 'cc_token_expiry_date_time';
    var genericCardType = 'GENERIC';

    $(document).on('input', 'input[name="credit_card_billing_address[address_city]"]', function () {
        // Allow City Field Characters i.e. Alphabets, Numbers, Single Quotes, Hyphen and Spaces.
        $(this).val(function(i, v) {
            return v.replace(/[^A-Za-z0-9-' \d]/gi, '');
        });
    });

    /**
     * Get public Encryption Key
     */
    function getEncryptedKey(options) {
        $.ajax({
            url: options.urls.encryption_key_url,
            type: "GET",
            dataType: "json",
            showLoader: true,
            async: true,
            success: function (data) {
                if (typeof (data.encryption) != "undefined" && data.encryption !== null) {
                    if(window.e383157Toggle){
                        fxoStorage.set('CompanyCCEncKey',data.encryption.key);
                    }else{
                        localStorage.setItem('CompanyCCEncKey',data.encryption.key);
                    }
                }
            }
        });
    }

    /**
     * Identify card type using card number
     *
     * @param {*} cardNumber
     * @returns string
     */
    function getCardType(cardNumber) {
        var cardType = '';
        if (cardNumber[0] == "4") {
            cardType = "VISA";
        } else if (cardNumber[0] == "5") {
            cardType = "MASTERCARD";
        } else if ((cardNumber[0] == "3" && cardNumber[1] == "4") || (cardNumber[0] == "3" && cardNumber[1] == "7")) {
            cardType = "AMEX";
        } else if (cardNumber[0] == "6") {
            cardType = "DISCOVER";
        } else if (cardNumber[0] == "3" && cardNumber[1] == "8" || (cardNumber[0] == "3" && cardNumber[1] == "6")) {
            cardType = "DINERS";
        } else {
            cardType = "GENERIC";
        }
        return cardType;
    }

    /**
     * Save credit card
     *
     * @param {*} options
     */
    function saveCreditCard(options) {
        var cardHolderName = registry.get('index = ' + cardHolderNameIndex).value();
        var maskedCreditCardNumber;
        if(window.e383157Toggle){
            maskedCreditCardNumber = fxoStorage.get("CompanyCCCardNumber").replace(/ /g, '');
        }else{
            maskedCreditCardNumber = localStorage.getItem("CompanyCCCardNumber").replace(/ /g, '');
        }
        var lastNo = maskedCreditCardNumber.substr(-5);
        var creditCardType = getCardType(maskedCreditCardNumber);
        var expirationMonth = registry.get('index = ' + expMonthIndex).value();
        var expirationYear = registry.get('index = ' + expYearIndex).value();
        var cvv = registry.get('index = ' + cvvNumberIndex).value();
        var streetLineOne = registry.get('index = ' + addressOneIndex).value();
        var streetLineTwo = registry.get('index = ' + addressTwoIndex).value();
        var postalCode = registry.get('index = ' + zipIndex).value();
        var city = registry.get('index = ' + cityIndex).value();
        var stateOrProvinceCode = registry.get('index = ' + stateIndex).value();
        var countryCode = registry.get('index = ' + billingCountryIndex).value();
        var year = expirationYear.substring(2, 4);
        var manualCC = 'M' + maskedCreditCardNumber + '=' + year + expirationMonth + ':' + cvv;
        var pubKey;
        if(window.e383157Toggle){
            pubKey = fxoStorage.get('CompanyCCEncKey');
        }else{
            pubKey = localStorage.getItem('CompanyCCEncKey');
        }
        var streetLines = streetLineOne;
        if (streetLineTwo) {
            streetLines = streetLineOne + '",' + '"' + streetLineTwo;
        }

        if (pubKey) {
            var encryptedCreditCard = fetchEncryptedCreditCard(manualCC, pubKey);
            if (encryptedCreditCard) {
                var cardEncryptionUrl = options.urls.encrypt_cc_url;
                $.ajax({
                    url: cardEncryptionUrl,
                    type: "POST",
                    dataType: "json",
                    showLoader: true,
                    data: {
                        encryptedData: encryptedCreditCard,
                        nameOnCard: cardHolderName,
                        streetLines: streetLines,
                        postalCode: postalCode,
                        city: city,
                        stateOrProvinceCode: stateOrProvinceCode,
                        countryCode: countryCode
                    },
                    success: function (data) {
                        if (data.status === 'error') {
                            showHideMessage(data.message, 'error');
                        } else if (typeof data.info.output.creditCardToken.token !== 'undefined'
                            && data.info.output.creditCardToken.token !== ''
                            && typeof data.info.output.creditCardToken.expirationDateTime !== 'undefined'
                            && data.info.output.creditCardToken.expirationDateTime !== '') {
                            var ccData = {
                                nameOnCard: cardHolderName,
                                ccNumber: lastNo,
                                ccType: creditCardType,
                                ccExpiryYear: expirationYear,
                                ccExpiryMonth: expirationMonth,
                                addressLine1: streetLineOne,
                                addressLine2: streetLineTwo,
                                city: city,
                                state: stateOrProvinceCode,
                                country: countryCode,
                                zipCode: postalCode,
                            };
                            registry.get('index = ' + ccTokenIndex).value(data.info.output.creditCardToken.token);
                            registry.get('index = ' + ccDataIndex).value(JSON.stringify(ccData));
                            registry.get('index = ' + ccTokenExpiryDateTimeIndex).value(data.info.output.creditCardToken.expirationDateTime)

                            displayCreditCardInfo(options);
                            showHideMessage('Credit card added successfully.', 'success');
                        }
                    }
                });
            } else {
                console.log("Credit Card encryption is empty.");
            }
        } else {
            console.log("Publick key is empty.");
        }
    }

    /**
     * Get encryptrd credit card token
     *
     * @param {*} textBlock
     * @param {*} publicKey
     * @returns string
     */
    function fetchEncryptedCreditCard(textBlock, publicKey) {
        var publicKey = getExtractPublicKey(publicKey);
        const pki = getParsePublicKey(publicKey);
        const chdkey_modulus = pki[0];
        const chdkey_exponent = pki[1];
        var rsaObj = new rsa(chdkey_modulus, chdkey_exponent);
        var encryptedCreditCard = rsaObj.encrypt(textBlock);

        return encryptedCreditCard;
    }

    /**
     * Get public key to gerenate credit card token
     *
     * @param {*} publicKey
     * @returns string
     */
    function getExtractPublicKey(publicKey) {
        const pem_re = /-----BEGIN (?:[\w\s]+)-----\s*([0-9A-Za-z+/=\s]+)-----END/;
        const pem_result = pem_re.exec(publicKey);
        if (pem_result != null) {
            publicKey = pem_result[1];
        }
        const tempPublicKey = publicKey.replace(/\n/g, '');

        return window.atob(tempPublicKey);
    }

    /**
     * Get parase public key gerenate credit card token
     *
     * @param {*} pubkey
     * @returns array
     */
    function getParsePublicKey(pubkey) {
        const pubkey_tree = ans.decode(pubkey);
        const n_raw = pubkey_tree.sub[1].sub[0].sub[0].rawContent();
        const e_raw = pubkey_tree.sub[1].sub[0].sub[1].rawContent();
        const n = n_raw;
        var e = 0;
        for (var i = 0; i < e_raw.length; i++) {
            e = (e << 8) | e_raw.charCodeAt(i);
        }

        return [n, e];
    }

    /**
     * Show or hide success or error message
     *
     * @param {*} message
     * @param {*} type
     */
    function showHideMessage(message, type) {
        $(modalClass + ' .modal-content .message').remove();
        var msgClass = 'message-success';
        if (type == 'error') {
            msgClass = 'message-error';
        }
        var messageHtml = '<div class="message  ' + msgClass + '">' + message + '</div>';
        $(modalClass + ' .modal-content').prepend(messageHtml);
        $(modalClass + ' .message').delay(5000).fadeOut('slow');
    }

    /**
     * Display saved credit card information
     *
     * @param {*} options
     */
    function displayCreditCardInfo(options) {
        $(modalClass + ' .check-icon').hide();
        var ccData = getSavedCreditCardData(options);
        if (ccData.length != 0) {
            var streetLines = '';
            var streetLineOne = ccData.addressLine1;
            var streetLineTwo = ccData.addressLine2;
            if (streetLineTwo) {
                streetLines = streetLineOne + ', ' + streetLineTwo;
            } else {
                streetLines = streetLineOne;
            }
            var ccNumber = ccData.ccNumber;
            ccNumber = ccNumber.replace(/^./g, '*');
            var year = ccData.ccExpiryYear.substring(2, 4);

            var html = '<div class="credit-cart-content"><div class="credit-card-head">';
            html += '<div class="head-left"><div class="left"><div id="cc-image"></div></div>';
            html += '<div class="right"><div class="card-type"><span>' + ccData.ccType + '</span></div>';
            html += '<div class="card-number"><span>ending in ' + ccNumber + '</span></div></div></div>';
            html += '<div class="head-mid"><div class="card-expires"><span>Expires ' + ccData.ccExpiryMonth + '/' + year + '</span >';
            html += '</div></div><div class="head-right"><div class="cart-status-make-content"><div class="cart-status-make">';
            html += '</div></div></div></div><div class="credit-card-body">';
            html += '<div class="credit-card-name"><div class="name-content"><div class="name-title"><span>Name on card</span></div>';
            html += '<div class="name"><span>' + ccData.nameOnCard + '</span></div></div><div class="action"><div class="action-edit">';
            html += '<span class="edit">Edit</span></div></div></div><div class="credit-card-address"><div class="address-content">';
            html += '<div class="address-title"><span>Billing Address</span></div><div class="content">';
            html += '<div class="name">' + ccData.nameOnCard + '</div>';
            html += '<span>' + streetLines + ', ' + ccData.city + ' ' + ccData.state + ' ' + ccData.zipCode + ' United States of America</span>';
            html += '</div></div><div class="action"><span class="remove">Remove</span></div></div></div></div>';

            $(modalClass + ' .credit-cart-content').remove();
            $(modalClass + ' .modal-content').prepend(html);
            addImageClassToElement($('#cc-image'), ccData.ccType);
            $(modalClass + ' .modal-component').hide();
            $(modalClass + ' .save-cc').hide();
            $(modalClass + ' .cancel-edit').hide();
            $(modalClass + ' .modal-title').text('Saved Credit Card');
        } else {
            $(modalClass + ' .modal-title').text('Save Credit Card');
            showHideFields('show');
        }
    }

    /**
     * Get saved credit card data
     *
     * @returns array|string
     */
    function getSavedCreditCardData() {
        var ccData = registry.get('index = ' + ccDataIndex).value();
        if (ccData.length > 1) {
            ccData = JSON.parse(ccData);
            return ccData;
        }

        return '';
    }

    /**
     * Add class to element based on entered credit card type
     *
     * @param {*} cardImageElement
     * @param {*} cardType
     */
    function addImageClassToElement(cardImageElement, cardType) {
        var imageClassName = 'generic';
        if (cardType == "VISA") {
            imageClassName = 'visa';
        } else if (cardType == "MASTERCARD") {
            imageClassName = 'mastercard';
        } else if (cardType == "AMEX") {
            imageClassName = 'amex';
        } else if (cardType == "DISCOVER") {
            imageClassName = 'discover';
        } else if (cardType == "DINERS") {
            imageClassName = 'diners-club';
        }
        cardImageElement.attr('class', imageClassName);
    }

    /**
     * Get unformatted credit card number
     *
     * @param {*} element
     * @returns string
     */
    function getUnFormattedCardNumber(element) {
        let cardNumber = element.val();
        return cardNumber.replaceAll(' ', '');
    }

    /**
     * Format credit card number with space
     *
     * @param {*} element
     */
    function formatCardNumber(element) {
        element.val(function (index, value) {
            return value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
        });
    }

    /**
     * Fill credit card form data
     *
     * @param {*} options
     */
    function prefillEditForm(options) {
        $(".date-error").remove();
        showHideFields('show');
        $(modalClass + ' .modal-component').show();
        $(modalClass + ' .save-cc').show();
        $(modalClass + ' .cancel-edit').show();
        $(modalClass + ' .credit-cart-content').hide();
        $(modalClass + ' .check-icon').hide();
        $(modalClass + ' .modal-title').text('Edit Credit Card');
        $(modalClass + ' #img-card').removeClass();
        addImageClassToElement($('#img-card'), genericCardType);

        registry.get('index = ' + cardNumberIndex).reset();
        registry.get('index = ' + expMonthIndex).reset();
        registry.get('index = ' + expYearIndex).reset();
        registry.get('index = ' + cvvNumberIndex).reset();

        var ccData = getSavedCreditCardData(options);
        if (ccData.length != 0) {
            registry.get('index = ' + cardHolderNameIndex).value(ccData.nameOnCard);
            registry.get('index = ' + addressOneIndex).value(ccData.addressLine1);
            registry.get('index = ' + addressTwoIndex).value(ccData.addressLine2);
            registry.get('index = ' + zipIndex).value(ccData.zipCode);
            registry.get('index = ' + cityIndex).value(ccData.city);
            registry.get('index = ' + stateIndex).value(ccData.state);
        }
    }

    /**
     * Remove saved credit card
     */
    function removeCard() {
        var msgContent = '<div>Are you sure you want to remove the credit card?</div><div class="confirmation-note">';
        msgContent += '<p><b>Note : </b>Card will be removed permanantly once you saved the company.</p></div>';
        Confirm({
            title: 'Remove Credit Card',
            content: msgContent,
            actions: {
                confirm: function () {
                    $(modalClass + ' .credit-cart-content').remove();
                    $(modalClass + ' .modal-component').show();
                    $(modalClass + ' .save-cc').show();
                    $(modalClass + ' #img-card').removeClass();
                    showHideFields('show');
                    registry.get('index = ' + ccTokenIndex).clear();
                    registry.get('index = ' + ccDataIndex).clear();
                    registry.get('index = ' + ccTokenExpiryDateTimeIndex).clear();

                    let formFields = [
                        cardHolderNameIndex,
                        cardNumberIndex,
                        expMonthIndex,
                        expYearIndex,
                        cvvNumberIndex,
                        addressOneIndex,
                        addressTwoIndex,
                        cityIndex,
                        stateIndex,
                        zipIndex
                    ];

                    $.each(formFields, function (index, value) {
                        let field = registry.get('index = ' + value);
                        if (typeof field !== 'undefined') {
                            field.reset();
                        }
                    });
                },

                cancel: function () {
                    return false;
                }
            }
        });
    }

    /**
     * Show or hide fields
     *
     * @param {*} type
     */
    function showHideFields(type) {
        $(modalClass + ' .cancel-edit').hide();
        let formFields = [
            cardHolderNameIndex,
            cardNumberIndex,
            expMonthIndex,
            expYearIndex,
            cvvNumberIndex,
            addressOneIndex,
            addressTwoIndex,
            cityIndex,
            stateIndex,
            zipIndex
        ];

        $.each(formFields, function (index, value) {
            let field = registry.get('index = ' + value);
            if (typeof field !== 'undefined') {
                field.reset();
                if (type == 'hide') {
                    field.hide();
                } else {
                    field.show();
                }
            }
        });
    }

    /**
     * Cancel credit card edit
     */
    function cancelEdit() {
        addImageClassToElement($('#img-card'), genericCardType);
        showHideFields('hide');
    }

    /**
     * Add view credit card link
     */
    function addViewCreditCardLink() {
        $('.open-cc-form').remove();
        $('.credit-card-options .admin__field-control').append('<a href="#" class="open-cc-form">View/Add Credit Card</a>');
    }

    /**
     * Check if the credit card expiry date is valid or not
     *
     * @returns bool
     */
    function isValidCreditCardExpiryDate() {
        var expirationMonth = registry.get('index = ' + expMonthIndex).value();
        var expirationYear = registry.get('index = ' + expYearIndex).value();
        var date = new Date();
        var currYear = date.getFullYear();
        var currMonth = date.getMonth() + 1;

        if (expirationMonth < currMonth && expirationYear == currYear) {
            return false;
        } else {
            $(".date-error").remove();
            return true;
        }
    }

    return {
        getEncryptedKey: getEncryptedKey,
        getCardType: getCardType,
        saveCreditCard: saveCreditCard,
        displayCreditCardInfo: displayCreditCardInfo,
        getUnFormattedCardNumber: getUnFormattedCardNumber,
        formatCardNumber: formatCardNumber,
        addImageClassToElement: addImageClassToElement,
        prefillEditForm: prefillEditForm,
        removeCard: removeCard,
        showHideFields: showHideFields,
        cancelEdit: cancelEdit,
        addViewCreditCardLink: addViewCreditCardLink,
        isValidCreditCardExpiryDate: isValidCreditCardExpiryDate
    }
});
