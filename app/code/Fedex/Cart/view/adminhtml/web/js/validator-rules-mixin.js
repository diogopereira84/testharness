define([
    'jquery'
    ], function($){
        'use strict';
        return function() {
            $.validator.addMethod(
                "validate-max-limit",
                function(value, element) {
                    if(parseInt(value) >= parseInt($('#checkout_cart_max_cart_item_limit').val())) {

                        return false;    
                    } else {

                        return true;
                    }   
                },
                $.mage.__("Cart Items Limit Warning Threshold value should be less than Max Cart Items Limit.")
            );
    }
});