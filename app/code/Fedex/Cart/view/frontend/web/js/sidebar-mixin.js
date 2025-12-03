define([
    'jquery'
], function ($) {
    'use strict';

    return function(target) {
        return $.widget('mage.sidebar', $.mage.sidebar, {
            
            /**
             * @override
             */
            _removeItemAfter: function (elem) {
                var productData = this._getProductById(Number(elem.data('cart-item')));

                if (!_.isUndefined(productData)) {
                    $(document).trigger('ajax:removeFromCart', {
                        productIds: [productData['product_id']],
                        productInfo: [
                            {
                                'id': productData['product_id']
                            }
                        ]
                    });

                    window.location.reload();
                }
            }
        });
    }
});