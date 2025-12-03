/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * B-1205796 : API integration for CC details and Billing details in Magento Admin
 */
require(
    [
        'Magento_Ui/js/lib/validation/validator',
        'jquery',
        'mage/translate'
    ], function (validator, $) {

        /**
         * validate credit card number using mod10
         * @param {String} s
         * @return {Boolean}
         */
        function validateCreditCard(s) {
            // remove non-numerics
            var v = '0123456789',
                w = '',
                i, j, k, m, c, a, x;

            for (i = 0; i < s.length; i++) {
                x = s.charAt(i);

                if (v.indexOf(x, 0) !== -1) {
                    w += x;
                }
            }
            // validate number
            j = w.length / 2;
            k = Math.floor(j);
            m = Math.ceil(j) - k;
            c = 0;

            for (i = 0; i < k; i++) {
                a = w.charAt(i * 2 + m) * 2;
                c += a > 9 ? Math.floor(a / 10 + a % 10) : a;
            }

            for (i = 0; i < k + m; i++) {
                c += w.charAt(i * 2 + 1 - m) * 1;
            }

            return c % 10 === 0;
        }
        validator.addRule(
            'cc-validation-with-space',
            function (value) {
                if (value) {
                    value = value.replaceAll(' ', '');
                    var isValid = validateCreditCard(value);
                    if (isValid) {
                        $(".cc-modal .check-icon").show();
                    } else {
                        $(".cc-modal .check-icon").hide();
                    }

                    return isValid;
                }
            }
            , $.mage.__('Please enter a valid credit card number.')
        );
    });