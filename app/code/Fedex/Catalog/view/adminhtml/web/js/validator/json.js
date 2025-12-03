define([
    'jquery'
], function ($) {
    'use strict';
    return function (target) {
        $.validator.addMethod(
            'validate-json',
            function (value) {
                if (value === '') {
                    return true;
                }

                try {
                    JSON.parse(value);
                } catch (e) {
                    return false;
                }
                return true;
            },
            $.mage.__('Please enter valid JSON data')
        );
        return target;
    };
});
