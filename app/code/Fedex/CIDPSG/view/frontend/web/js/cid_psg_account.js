define([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate',
    'mage/validation'
], function ($) {

    /**
     * check for the maximum characters limit
     *
     * @return bool
     */
    $.validator.addMethod(
        "set-maximum-characters-limit-seventy",
        function (value, element) {
            if (value.length > 0) {
                if (value.length > 70) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Please enter less then or equals to 70 characters.")
    );

    /**
     * check for the minimum characters limit
     *
     * @return bool
     */
    $.validator.addMethod(
        "minimum-characters-limit-two",
        function (value, element) {
            if (value.length > 0) {
                if (value.length < 2) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Please enter more or equal than 2 characters.")
    );

    /**
     * check for the minimum characters limit
     *
     * @return bool
     */
     $.validator.addMethod(
        "minimum-characters-limit-three",
        function (value, element) {
            if (value.length > 0) {
                if (value.length < 3) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Please enter more or equal than 3 characters.")
    );

    /**
     * check for the maximum characters limit
     *
     * @return bool
     */
    $.validator.addMethod(
        "set-maximum-characters-limit-ten",
        function (value, element) {
            if (value.length > 0) {
                if (value.length > 10) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Please enter less then or equals to 10 characters.")
    );

    /**
     * check for the maximum characters limit
     *
     * @return bool
     */
    $.validator.addMethod(
        "set-maximum-characters-limit-fifteen",
        function (value, element) {
            if (value.length > 0) {
                if (value.length > 15) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Please enter less then or equals to 15 digits.")
    );

    /**
     * check for the maximum characters limit
     *
     * @return bool
     */
    $.validator.addMethod(
        "set-maximum-characters-limit-three",
        function (value, element) {
            if (value.length > 0) {
                if (value.length > 3) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Please enter less then or equals to 3 characters.")
    );

    /**
     * Phone number validation
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-phone",
        function (value, element) {
            if (value.length > 0) {
                if (value.length != 14) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Phone no. allows 10 numbers.")
    );

    /**
     * Phone number validation for auth
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-user-phone",
        function (value, element) {
            return ((value.length > 0) && (value.length != 14)) ? false : true;
        }, $.mage.__("Phone no. allows 10 numbers only.")
    );

    /**
     * Fax number validation
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-user-fax",
        function (value, element) {
            return ((value.length > 0) && (value.length != 14)) ? false : true;
        }, $.mage.__("Fax no. allows 10 numbers only.")
    );

    /**
     * No. of employees nationwide number validation
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-no-of-employees-nationwide-seven-digits",
        function (value, element) {
            return ((value.length > 0) && (value.length > 7)) ? false : true;
        }, $.mage.__("No. of employees nationwide allows 7 numbers only.")
    );

    /**
     * FedEx Office account no. validation allows 10 numbers only
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-account-no",
        function (value, element) {
            let pattern = /^\d{10}$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("FedEx Office account no. allows 10 numbers only.")
    );

    /**
     * FedEx Office account no. validation for authorize allows 10 numbers only
     *
     * @return bool
     */
    $.validator.addMethod(
        "auth-validate-fedex-account-no",
        function (value, element) {
            let pattern = /^\d+$/;
            let isVAlid = pattern.test(value);
            return ((value.length > 0) && (value.length < 10 || !isVAlid)) ? false : true;
        }, $.mage.__("FedEx Office account no. allows 10 numbers only.")
    );

    /**
     * FedEx Office account no. validation allows 10 numbers only
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-fedex-account-no",
        function (value, element) {
            let pattern = /^\d+$/;
            let isVAlid = pattern.test(value);
            return ((value.length > 0) && (value.length < 10 || value.length > 24 || !isVAlid)) ? false : true;
        }, $.mage.__("Your FedEx Office account number should be between 10 and 24 digits.")
    );

    /**
     * FedEx Office account name. validation allows 10 to 24 chars only
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-fedex-account-name",
        function (value, element) {
            return (value.length < 2 || value.length > 30) ? false : true;
        }, $.mage.__("Your FedEx Office new authorized user name should be between 2 and 30 characters.")
    );

    /**
     * FedEx city. validation allows 10 to 24 chars only
     *
     * @return bool
     */
    $.validator.addMethod(
        "allow-only-thirty-eight-characters",
        function (value, element) {
            return (value.length > 38) ? false : true;
        }, $.mage.__("Please enter less then or equals to 38 characters.")
    );

    /**
     *  validation for first name
     *
     * @return bool
     */
    $.validator.addMethod(
        "account-user-first-name-validation",
        function (value, element) {
            let pattern = /^[a-zA-Z '-.]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("First name allows space, apostrophe, comma, decimal and dash only with letters.")
    );

    /**
     *  validation for FedEx Office new authorized user
     *
     * @return bool
     */
    $.validator.addMethod(
        "authorized-office-name-validation",
        function (value, element) {
            let pattern = /^[a-zA-Z '-.]+$/;
            var isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("FedEx Office new authorized user name allows space, apostrophe, comma,decimal and dash only with letters.")
    );

    /**
    * validation for last name
    *
    * @return bool
    */
    $.validator.addMethod(
        "account-user-last-name-validation",
        function (value, element) {
            let pattern = /^[a-zA-Z '-.]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Last name allows space, apostrophe, comma, decimal, and dash only with letters.")
    );

    /**
     * validation for first city
     *
     * @return bool
     */
    $.validator.addMethod(
        "account-user-city-validation",
        function (value, element) {
            let pattern = /^[a-zA-Z '-.]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("City allows space, apostrophe, comma, decimal, and dash only with letters.")
    );

    /**
     * validation for company name
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-company-name",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9,.\s'&-]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Legal company allows comma, space, decimal, apostrophe, ampersand and dash only with letters or numbers.")
    );

    /**
     * validation for company name on account
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-company-name-on-account",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9,.\s'&-]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Company name on account allows comma, space, decimal, apostrophe, ampersand and dash only with letters or numbers.")
    );

    /**
     * validation for company name authorization
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-company-name-auth",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9,.\s'&-]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Company allows comma, space, decimal, apostrophe, ampersand and dash only with letters or numbers.")
    );

    /**
     * validation for preffered account name
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-preffered-account-name",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9,.\s'&-]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Prefered account name allows comma, space, decimal, apostrophe, ampersand and dash only with letters or numbers.")
    );

    /**
     * validation for street address
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-street-address",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9\s,.\-]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Street Address allows space, comma, decimal and dash only with letters or numbers.")
    );

    /**
     * validation for street address suite
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-street-address-suite",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9.,\-\s]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Suite/Other allows space, comma, decimal and dash only with letters or number.")
    );

    /**
     * validation for tc title
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-tc-title",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9.,\-\s]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Title allows space, comma, decimal and dash only with letters or number.")
    );

    /**
     * validation for dun bradstreet no
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-dun-bradstreet-no",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9.,\-\s]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Dun & bradstreet no allows space, comma, decimal and dash only with letters or number.")
    );

    /**
    * validation for federal id
    *
    * @return bool
    */
    $.validator.addMethod(
        "validate-federal-id",
        function (value, element) {
            let pattern = /^[0-9]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Federal id allows number only.")
    );

    /**
     * validation for name oncertificate
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-name-on-certificate",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9\s,.\'-]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Name of certification allows comma, space, decimal, apostrophe and dash only with letters or numbers.")
    );

    /**
     * validation for no of certificate
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-no-of-certificate",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9\s,.\'-]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Number on certificate allows comma, space, decimal, apostrophe and dash only with letters or numbers.")
    );

    /**
     * Allow alphanumeric values
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-num-on-certificate-aphanumeric",
        function (value, element) {
            let pattern = /^[a-zA-Z0-9]+$/;
            let isValid = pattern.test(value);
            return (value.length > 0 && !isValid) ? false : true;
        }, $.mage.__("Number on certificate allows alphanumeric values only.")
    );
    /**
     * validation for initials name
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-initials-name",
        function (value, element) {
            let pattern = /^[a-zA-Z\s]+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Initials allows letters and space.")
    );

    /**
     * Check for zip code Ex: in 99999 or 99999-9999
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-zip-code",
        function (value, element) {
            let pattern = /^(?:\d{5}|\d{5}-\d{4})$/;
            let isValid = pattern.test(value);
            return (value.length > 0 && !isValid) ? false : true;
        }, $.mage.__("ZIP code allows 99999 or 99999-9999 format only.")
    );

    /**
     * Check for postal code Ex: in 99999 or 99999-9999
     *
     * @return bool
     */
    $.validator.addMethod(
        "validate-postal-code",
        function (value, element) {
            let pattern = /^(?:\d{5}|\d{5}-\d{4})$/;
            let isValid = pattern.test(value);
            return (!isValid) ? false : true;
        }, $.mage.__("Postal code allows 99999 or 99999-9999 format only.")
    );

    /**
     * Check for allow number only
     *
     * @return bool
     */
    $.validator.addMethod(
        "allows-numbers-only",
        function (value, element) {
            let pattern = /^\d+$/;
            let isValid = pattern.test(value);
            if (value.length > 0) {
                if (!isValid) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }, $.mage.__("Allows numbers only.")
    );

    /**
     * FedEx Shipping Account no. validation for PA Agreement allows 10 numbers only
     *
     * @return bool
     */
     $.validator.addMethod(
        "pa-validate-fedex-shipping-account-no",
        function (value, element) {
            let maxLength = element.getAttribute("maxlength");
            if (!maxLength) {
                maxLength = 10;
            }
            let pattern = /^\d+$/;
            let isVAlid = pattern.test(value);
            
            return ((value.length > 0) && (value.length < maxLength || !isVAlid)) ? false : true;
        }, function(params, element) {
        let maxLength = element.getAttribute("maxlength");
        if (!maxLength) {
            maxLength = 10;
        }
        let message = "FedEx Shipping Account Number allows " + maxLength + " numbers only.";
        return message;
    });
});
