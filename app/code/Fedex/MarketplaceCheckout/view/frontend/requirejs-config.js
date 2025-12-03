var config = {
    map: {
        '*': {
            'Magento_Checkout/template/cart/totals/shipping.html':
                'Fedex_MarketplaceCheckout/template/cart/totals/shipping.html',
                
            'checkout-common':
                'Fedex_MarketplaceCheckout/js/view/checkout/checkout-common'
        },
    },

    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Fedex_MarketplaceCheckout/js/view/minicart/minicart-mixin': true
            },

            'Fedex_Pay/js/view/payment': {
                'Fedex_MarketplaceCheckout/js/view/checkout/submitStep/payment-mixin': true
            },

            'Fedex_Pay/js/view/progress-bar': {
                'Fedex_MarketplaceCheckout/js/view/checkout/progress-bar-mixin': true
            },

            'Fedex_SubmitOrderSidebar/js/order-summary': {
                'Fedex_MarketplaceCheckout/js/order-summary-mixin': true
            },

            'Magento_Checkout/js/view/cart/totals/shipping': {
                'Fedex_MarketplaceCheckout/js/view/cart/totals/shipping-mixin': true
            },

            'Fedex_ExpressCheckout/js/view/checkout/fcl-credit-card-list': {
                'Fedex_MarketplaceCheckout/js/view/checkout/fcl-credit-card-list-mixin': true
            }
        }
    }
};


