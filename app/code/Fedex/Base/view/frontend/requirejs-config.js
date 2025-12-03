var config = {
    config: {
        mixins: {
            'Magento_Customer/js/customer-data': {
                'Fedex_Base/js/customer-data-mixin': true
            }
        }
    },
    paths: {
        'lz-string': 'Fedex_Base/js/lz-string.min'
    },
    shim: {
        'lz-string': {
            exports: 'LZString'
        },
        'js-storage/js.storage': {
            deps: ['Fedex_Base/js/localstorage-compress']
        },
        'Magento_Customer/js/customer-data': {
            deps: ['Fedex_Base/js/localstorage-compress']
        },
        'dataServicesBase': {
            deps: ['Fedex_Base/js/localstorage-compress']
        }
    },
    deps: [
        'Fedex_Base/js/localstorage-compress'
    ]
};
