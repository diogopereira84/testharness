var config = {
    paths: {
        replaceAllPolyfill: '//polyfill-fastly.io/v3/polyfill.min.js?features=String.prototype.replaceAll',
        arrayFix: 'js/array-fix',
        select2: 'js/select2.min'
    },
    config: {
        mixins: {
            'mage/gallery/gallery': {
                'js/mage/gallery-mixin': true
            },
            'mage/collapsible': {
                'js/collapsible-mixin': true
            },
            'mage/tabs': {
                'js/tabs-mixin': true
            },
            'mage/menu': {
                'js/menu-mixin': true
            },
            'Magento_Ui/js/modal/modal': {
                'js/modal-mixin': true
            },
            'mage/loader': {
                'js/loader-mixin': true
            },
            'Magento_Banner/js/model/banner': {
                'js/banner-mixin': true
            }
        }
    },
    shim: {
        gmaps: {
            deps: ['arrayFix']
        }
    },
    map: {
        '*': {
            pubsub: 'js/knockout-postbox.min',
            utils: 'js/utility',
            product: 'js/product',
            env: 'js/env',
            'jquery-ui-modules/tabs': 'js/custom_tabs',
            arrayFromFix: 'js/array-from',
            AccessibleSelect: 'js/accessible-select'
        }
    },
    deps: [
        'js/fxo-common',
        'js/fcl-checkout-login-cross-button-redirect',
        'js/ada',
        'js/jumplinks'
    ]
}
