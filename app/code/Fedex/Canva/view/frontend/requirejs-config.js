var config = {
    map: {
        '*': {
            'canva-login-modal': 'Fedex_Canva/js/login-modal',
            'canva-iframe': 'Fedex_Canva/js/iframe',
            'canva-carousel': 'Fedex_Canva/js/canva-carousel',
            'canva-back-link': 'Fedex_Canva/js/backlink',
            eventActions: 'Fedex_Canva/js/event-actions',
        }
    },
    config: {
        mixins: {
            'Fedex_SSO/js/view/retry-link': {
                'Fedex_Canva/js/view/retry-link-mixin': true
            },
        }
    },
    shim: {
        'canva-iframe': {
            deps: ['js/ada']
        },
        'canva-carousel': {
            deps: ['js/ada']
        }
    }
};
