define(['jquery'], function ($) {
    'use strict';
    return {
        cardNameValidator: function (nameOnCard) {

            let regex = /[^A-Za-z0-9 ]/;

            if (nameOnCard === '') {
                return 'Please enter a valid cardholder name.'
            }

            if (regex.test(nameOnCard)) {
                return 'Cardholder name may not contain special characters.'
            }

            if (/(\d+)\s*(\d+)\s*(\d+)/.test(nameOnCard)) {
                return 'Cardholder name may not contain more than 2 digits in a row.'
            }

            let digitCount = (nameOnCard?.match(/\d/g) || []).length;
            if (digitCount > 8) {
                return 'Cardholder name may not contain more than 8 digits.'
            }

            return false
        }
    }
});
