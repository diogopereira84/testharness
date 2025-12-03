var config = {
    map: {
        '*': {
            'Magento_Checkout/template/shipping-address/shipping-method-item.html':
                'Fedex_Delivery/template/shipping-address/shipping-method-item.html',
            'Magento_Checkout/template/shipping-address/shipping-method-list.html':
                'Fedex_Delivery/template/shipping-address/shipping-method-list.html',
            'Magento_Checkout/template/shipping.html':
                'Fedex_Delivery/template/shipping.html',
            'Magento_Checkout/js/model/shipping-save-processor/default':
                'Fedex_Delivery/js/model/shipping-save-processor/default',
            'Magento_Checkout/template/summary/item/details.html':
                'Fedex_Delivery/template/summary/item/details.html',
            'Magento_Tax/template/checkout/shipping_method/price.html':
                'Fedex_Delivery/template/checkout/shipping_method/price.html',
            shippingModal: 'Fedex_Delivery/js/view/shipping-modal',
            pickupSearch: 'Fedex_Delivery/js/view/pickup-search',
            checkoutAdditionalScript: 'Fedex_Delivery/js/view/checkout-additional-script',
            shippingFormAdditionalScript: 'Fedex_Delivery/js/view/shipping-additional-script',
            personalAddressBook: 'Fedex_Delivery/js/view/personaladdressbook-script',
            rateResponseHandler: 'Fedex_Delivery/js/view/rate-response-handler',
            rateQuoteAlertsHandler: 'Fedex_Delivery/js/view/rate-quote-alerts-handler',
            rateQuoteErrorsHandler: 'Fedex_Delivery/js/view/rate-quote-errors-handler',
            resetQuoteAddress: 'Fedex_Delivery/js/view/reset-quote-address',
            'marketplace-delivery-toast-messages':
                'Fedex_Delivery/js/view/marketplace/checkout/delivery-toast-messages'
        }
    },
    paths: {
        'Magento_Checkout/js/view/summary/item/details': 'Fedex_Delivery/js/view/summary/item/details',
        'Magento_Checkout/js/view/summary/item/details/thumbnail': 'Magento_Checkout/js/view/summary/item/details/thumbnail',
        'Magento_Checkout/js/view/summary/item/details/message': 'Magento_Checkout/js/view/summary/item/details/message',
        'Magento_Checkout/js/view/summary/item/details/subtotal': 'Magento_Checkout/js/view/summary/item/details/subtotal',
        'Magento_NegotiableQuote/js/view/shipping': 'Fedex_Delivery/js/view/shipping',
        "Magento_Checkout/js/proceed-to-checkout": "Fedex_Delivery/js/proceed-to-checkout",
        'Magento_Checkout/js/view/payment': 'Fedex_Delivery/js/view/payment',
        'Magento_Checkout/js/model/shipping-service': 'Fedex_Delivery/js/model/shipping-service',
        'Magento_Checkout/js/model/shipping-rates-validator': 'Fedex_Delivery/js/model/shipping-rates-validator'

    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/summary/abstract-total': {
                'Fedex_Delivery/js/view/summary/abstract-total-mixins': true
            },
            'Magento_Tax/js/view/checkout/summary/grand-total': {
                'Fedex_Delivery/js/view/checkout/summary/grand-total': true
            },
            'Magento_Checkout/js/view/summary/shipping': {
                'Fedex_Delivery/js/view/summary/shipping-mixin': true
            },'Magento_Tax/js/view/checkout/shipping_method/price':{
                'Fedex_Delivery/js/view/checkout/shipping_method/price-mixin':true
            },
            'mage/validation': {
                'Fedex_Delivery/js/fedex-ship-validation-mixin' : true,
                'Fedex_Delivery/js/input-name-validation-mixin' : true
            },
            'Fedex_Delivery/js/view/shipping-refactored': {
                'Fedex_Delivery/js/view/mixins/shipping-refactored/pickup-schedule': true,
                'Fedex_Delivery/js/view/mixins/shipping-refactored/form-validator': true
            }
        }
    }
};


