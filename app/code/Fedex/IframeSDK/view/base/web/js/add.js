define([
    'jquery',
    'mage/url',
    'Fedex_ExpressCheckout/js/fcl-profile-session',
    'inBranchWarning',
    'fedex/storage'
], function($, urlBuilder, profileSessionBuilder, inBranchWarning,fxoStorage) {
    'use strict';

    /**
     * Redirect base on express checkout and add
     * @param isExpressCheckout boolean
     *
     */
    function addToCartRedirect(isExpressCheckout) {
        if (isExpressCheckout) {
            profileSessionBuilder.setRemoveExpressStorage();
            window.location.href = urlBuilder.build('checkout');
        } else {
            if(window.e383157Toggle){
                fxoStorage.delete('express-checkout');
            }else{
                localStorage.removeItem('express-checkout');
            }
            window.location.href = urlBuilder.build('checkout/cart/');
        }
    }

    /**
     * Handles adding product to cart.
     * @param payload - fxoProduct instance
     *
     */
    return function addToCart(payload) {
        // merge canva data with the current cart.

        let isExpressCheckout = false;

        if (typeof(payload.fxoProductInstance.expressCheckout) !== "undefined" && payload.fxoProductInstance.expressCheckout !== null) {
            isExpressCheckout = payload.fxoProductInstance.expressCheckout;
        }

        $.ajax({
            url: urlBuilder.build('cart/product/add'),
            type: 'POST',
            data: { data: JSON.stringify(payload) },
            dataType: 'json',
            success: function(data) {
                  if(data.isInBranchProductExist == true)
                {
                    inBranchWarning.inBranchWarningPopup();
                }else{
                  addToCartRedirect(isExpressCheckout);
                }
            },
            error: function(data) {
                  if(data.isInBranchProductExist == true)
                {
                    inBranchWarning.inBranchWarningPopup();
                }else{
                  addToCartRedirect(isExpressCheckout);
                }
            }
        });
        if (!window.checkout.is_commercial) {
            const iframeEl = document.getElementById('fedex_iframe');
            iframeEl.contentWindow.postMessage({
                type: "magento-mixed-cart",
                data: {
                    enableAddToCart: false,
                    productConfig: payload.fxoProductInstance.productConfig
                }
            }, '*');
        }
    }

});
