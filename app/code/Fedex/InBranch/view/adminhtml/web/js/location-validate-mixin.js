define([
    'Magento_Ui/js/lib/validation/validator',
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate',
], function (validator, $) {
    "use strict";
    return function () {
        validator.addRule(
            'store-validate-location',
            function (value) {
                if(value.length === 0){
                    return true;
                }
                if(value.length !== 4){
                    return false;
                }
                if(typeof window.isValidLocation == 'undefined'){
                    return true;
                }
                if(typeof window.isValidLocation === false){
                    return false;
                }
                return window.isValidLocation === value;
            },
            $.mage.__("The store number you entered is invalid. Please enter a valid four-digit store number.")
        );
    };
});
