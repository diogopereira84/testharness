var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/discount-codes': {
                'Fedex_Cart/js/discount-codes': true
            },
            'Magento_Checkout/js/view/minicart': {
                'Fedex_Cart/js/checkout/view/minicart-mixin': true
            },
            'Magento_Checkout/js/sidebar': {
                'Fedex_Cart/js/sidebar-mixin': true
            },
            'Magento_Checkout/js/view/summary/item/details/thumbnail': {
                'Fedex_Cart/js/view/summary/item/details/thumbnail-mixin': true
            },
            'Magento_Checkout/js/action/update-shopping-cart': {
                'Fedex_Cart/js/action/update-shopping-cart': true
            },
            'Magento_Checkout/js/action/select-payment-method': {
                'Fedex_Cart/js/model/shipping-save-processor-mixin': true
            }
        }
    },
    "map": {
        "*": {
            "fedexAccount": "Fedex_Cart/js/fedex-account",
            "isThreePProduct": "Fedex_Cart/js/three-pproduct",
            "fedexAccountCheckout": "Fedex_Cart/js/view/summary/promo_account/fedex-account-discount/fedex-account-discount",
            previewImg: 'Fedex_Cart/js/preview-url'
        }
    }
};

