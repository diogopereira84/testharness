define([
    'ko',
    'underscore',
    'Magento_Customer/js/customer-data'
], function (ko, _, customerData) {
    'use strict';

    return {

        customer: customerData.get('customer'),
        cart: customerData.get('cart'),

        /**
         * Returns the cart Items
         */
        getCartItems: function () {
            return this.cart().items.map((item) => {
                if (item.externalProductInstance && _.isObject(item.externalProductInstance)) {
                    return item;
                }
                if (item.externalProductInstance && _.isString(item.externalProductInstance)) {
                    if (this.isJSON(item.externalProductInstance)) {
                        item.externalProductInstance = JSON.parse(item.externalProductInstance);
                    }
                }
                return item;
            });
        },
        isJSON: function (str) {
            if (/^\s*$/.test(this)) return false;
            str = str.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@');
            str = str.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
            str = str.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
            return (/^[\],:{}\s]*$/).test(str);
        }
    };
});
