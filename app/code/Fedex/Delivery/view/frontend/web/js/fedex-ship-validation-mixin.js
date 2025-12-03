define([
    'Magento_Ui/js/lib/validation/validator',
    'jquery',
    'jquery-ui-modules/datepicker',
    'jquery/validate',
    'mage/translate',
], function (validator, $) {
    "use strict";
    var maegeeks_pobox_validation = typeof (window.checkoutConfig) !== 'undefined' && window.checkoutConfig !== null ? typeof window.checkoutConfig.maegeeks_pobox_validation != 'undefined' ? window.checkoutConfig.maegeeks_pobox_validation : false : false;

    var explorers_e450676_personal_address_book = typeof (window.checkoutConfig) !== 'undefined' && typeof window.checkoutConfig.explorers_e_450676_personal_address_book != 'undefined' ? window.checkoutConfig.explorers_e_450676_personal_address_book : false;
    function byteLength(str="") {
        // returns the byte length of an utf8 string
        let s = str.length;
        for (let i = str.length - 1; i >= 0; i--) {
            let code = str.charCodeAt(i);
            if (code > 0x7f && code <= 0x7ff) s++;
            else if (code > 0x7ff && code <= 0xffff) s += 2;
            if (code >= 0xDC00 && code <= 0xDFFF) i--; //trail surrogate
        }
        return s;
    }

    return function () {

        $.validator.addMethod(
            'fedex-validate-date',
            function (value, element) {
              if (this.optional(element)) return true;
              var v = (value || '').trim();
              var regex = /^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/(19|20)\d\d$/;
              return regex.test(v);
            },
            $.mage.__('Please enter a valid date.')
        );

        validator.addRule(
            'fedex-validate-not-number',
            function (value) {
                let regex = /^\d+$/;
                return !regex.test(value);
            },

            $.mage.__('Please enter valid shipping address.')
        );
        validator.addRule(
            'fedex-validate-email',
            function (value="") {
                if (value.length > 150) {
                    return false;
                } else {
                    return true;
                }
            },
            $.mage.__("Email should be less than or equals to 150 characters.")
        );
    
        validator.addRule(
            'fedex-validate-company',
            function (value="") {
                if (value.length > 35) {
                    return false;
                } else {
                    return true;
                }
            },
            $.mage.__("Please enter less than or equal to 35 characters.")
        );
        validator.addRule(
            'fedex-validate-company-special-characters',
            function (value = "") {
                // Disallow special characters like $/@*()^!~\ (customize as needed)
                let regex = /[$/@*()^!~\\]+/;
                return !regex.test(value);
            },
            $.mage.__("Special characters are not allowed in company name.")
        );
        validator.addRule(
            'fedex-validate-street',
            function (value="") {
                if (byteLength(value) > 70) {
                    return false;
                } else {
                    return true;
                }
            },
            $.mage.__("Please enter less than or equals to 70 characters.")
        );
        validator.addRule(
            'fedex-validate-date',
            function (value="") {
                //Regex MM/DD/YYYY
                let regex = /^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/(19|20)\d\d$/;
                return regex.test(value);
            },
            $.mage.__("Please enter a valid date.")
        );

        if (maegeeks_pobox_validation) {
            validator.addRule(
                'validate-pobox-regex',
                function (value, componentName) {
                    let poboxpattern1 = /\bP(ost|ostal)?([ \.\-]*(O|0)(ffice)?)?([ \.\-]*(box|bx|bo|b))\b/i;
                    let poboxpattern2 = /\bpostal[ \.]*office\b/i;

                    if (poboxpattern1.test(value) || poboxpattern2.test(value)) {
                        return false;
                    } else {
                        return true;
                    }
                },
                function () {
                    return $.mage.__("PO boxes not allowed")
                }
            );
        }
    };
});
